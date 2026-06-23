<?php
// thread.php
session_start();
require_once 'includes/db.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$thread_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id   = $_SESSION['user_id'];
$error_msg = '';

// 1. Fetch thread details
$stmt_thread = mysqli_prepare($conn, "
    SELECT t.id, t.course_id, t.user_id, t.title, t.content, t.is_pinned, t.is_solved, t.created_at,
           u.nama AS author_name, u.role AS author_role, u.username AS author_username, u.foto_profil AS author_avatar
    FROM threads t
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
");
mysqli_stmt_bind_param($stmt_thread, "i", $thread_id);
mysqli_stmt_execute($stmt_thread);
$thread = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_thread));
mysqli_stmt_close($stmt_thread);

if (!$thread) {
    header("Location: dashboard.php");
    exit();
}

// 2. Fetch course details to check Dosen/Moderator
$stmt_course = mysqli_prepare($conn, "SELECT name, code, dosen_id FROM courses WHERE id = ?");
mysqli_stmt_bind_param($stmt_course, "i", $thread['course_id']);
mysqli_stmt_execute($stmt_course);
$course = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_course));
mysqli_stmt_close($stmt_course);

// 3. Define Roles & Permissions
$is_admin     = ($_SESSION['role'] === 'admin');
$is_moderator = ($_SESSION['role'] === 'dosen' && $_SESSION['user_id'] == $course['dosen_id']);
$is_author    = ($_SESSION['user_id'] == $thread['user_id']);

// 4. Handle POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Action A: Create comment
    if (isset($_POST['create_comment'])) {
        $comment_content = trim($_POST['comment_content']);
        if (empty($comment_content)) {
            $error_msg = "Komentar tidak boleh kosong!";
        } elseif (strlen($comment_content) > 5000) {
            $error_msg = "Komentar terlalu panjang (maks. 5000 karakter)!";
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO comments (thread_id, user_id, content) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iis", $thread_id, $user_id, $comment_content);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            header("Location: thread.php?id=" . $thread_id . "#comments");
            exit();
        }
    }

    // Action B: Toggle Solved (Author only)
    if (isset($_POST['toggle_solved'])) {
        if ($is_author) {
            $new_solved = $thread['is_solved'] ? 0 : 1;
            $stmt = mysqli_prepare($conn, "UPDATE threads SET is_solved = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $new_solved, $thread_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            header("Location: thread.php?id=" . $thread_id);
            exit();
        }
    }

    // Action C: Toggle Pin (Admin / Moderator of this course only)
    if (isset($_POST['toggle_pin'])) {
        if ($is_admin || $is_moderator) {
            $new_pinned = $thread['is_pinned'] ? 0 : 1;
            $stmt = mysqli_prepare($conn, "UPDATE threads SET is_pinned = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $new_pinned, $thread_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            header("Location: thread.php?id=" . $thread_id);
            exit();
        }
    }

    // Action D: Delete Comment (Admin / Moderator / Comment Owner)
    if (isset($_POST['delete_comment'])) {
        $comment_id = intval($_POST['comment_id']);

        // Check comment ownership
        $chk = mysqli_prepare($conn, "SELECT user_id FROM comments WHERE id = ? AND thread_id = ?");
        mysqli_stmt_bind_param($chk, "ii", $comment_id, $thread_id);
        mysqli_stmt_execute($chk);
        $owner_row = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
        mysqli_stmt_close($chk);

        $is_comment_owner = ($owner_row && $owner_row['user_id'] == $user_id);

        if ($is_admin || $is_moderator || $is_comment_owner) {
            $stmt = mysqli_prepare($conn, "DELETE FROM comments WHERE id = ? AND thread_id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $comment_id, $thread_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            header("Location: thread.php?id=" . $thread_id);
            exit();
        }
    }

    // Action E: Delete Thread (Admin / Moderator / Thread Author)
    if (isset($_POST['delete_thread'])) {
        if ($is_admin || $is_moderator || $is_author) {
            $stmt = mysqli_prepare($conn, "DELETE FROM threads WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $thread_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            header("Location: course.php?id=" . $thread['course_id']);
            exit();
        }
    }

    // Action F: Upvote comment (All logged-in users)
    if (isset($_POST['upvote_comment'])) {
        $comment_id = intval($_POST['comment_id']);

        // Check if already upvoted
        $chk_stmt = mysqli_prepare($conn, "SELECT id FROM upvotes WHERE comment_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($chk_stmt, "ii", $comment_id, $user_id);
        mysqli_stmt_execute($chk_stmt);
        mysqli_stmt_store_result($chk_stmt);
        $has_upvoted = mysqli_stmt_num_rows($chk_stmt) > 0;
        mysqli_stmt_close($chk_stmt);

        if ($has_upvoted) {
            $del_stmt = mysqli_prepare($conn, "DELETE FROM upvotes WHERE comment_id = ? AND user_id = ?");
            mysqli_stmt_bind_param($del_stmt, "ii", $comment_id, $user_id);
            mysqli_stmt_execute($del_stmt);
            mysqli_stmt_close($del_stmt);
        } else {
            $ins_stmt = mysqli_prepare($conn, "INSERT INTO upvotes (comment_id, user_id) VALUES (?, ?)");
            mysqli_stmt_bind_param($ins_stmt, "ii", $comment_id, $user_id);
            mysqli_stmt_execute($ins_stmt);
            mysqli_stmt_close($ins_stmt);
        }

        header("Location: thread.php?id=" . $thread_id . "#comments");
        exit();
    }
}

// 5. Fetch Comments list (ordered by upvotes desc, date asc)
$comments_query = "
    SELECT
        c.id,
        c.content,
        c.created_at,
        u.nama AS author_name,
        u.role AS author_role,
        u.foto_profil AS author_avatar,
        c.user_id AS comment_author_id,
        COUNT(up.id) AS total_upvotes,
        SUM(CASE WHEN up.user_id = ? THEN 1 ELSE 0 END) AS has_user_upvoted
    FROM comments c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN upvotes up ON c.id = up.comment_id
    WHERE c.thread_id = ?
    GROUP BY c.id, c.content, c.created_at, u.nama, u.role, u.foto_profil, c.user_id
    ORDER BY total_upvotes DESC, c.created_at ASC
";
$stmt_comments = mysqli_prepare($conn, $comments_query);
mysqli_stmt_bind_param($stmt_comments, "ii", $user_id, $thread_id);
mysqli_stmt_execute($stmt_comments);
$comments_result = mysqli_stmt_get_result($stmt_comments);

// Store in array to close stmt before HTML output
$comments_data  = [];
while ($cmt = mysqli_fetch_assoc($comments_result)) {
    $comments_data[] = $cmt;
}
mysqli_stmt_close($stmt_comments);
$comments_count = count($comments_data);

// Set page title and include header AFTER all logic & redirects
$page_title = $thread['title'];
include 'includes/header.php';
?>

<div class="container">
    <div class="breadcrumbs">
        <a href="dashboard.php">Dashboard</a> &raquo;
        <a href="course.php?id=<?php echo $thread['course_id']; ?>"><?php echo htmlspecialchars($course['code']); ?></a> &raquo;
        Detail Diskusi
    </div>

    <div class="thread-detail-container">
        <!-- Main Question Card -->
        <div class="thread-main-card" style="<?php echo $thread['is_pinned'] ? 'border-left: 6px solid var(--warning);' : ''; ?>">
            <div class="thread-meta-top">
                <div class="thread-meta-top-left">
                    <?php
                    $author_pic = 'uploads/profile_pics/' . $thread['author_avatar'];
                    if (!empty($thread['author_avatar']) && file_exists(__DIR__ . '/' . $author_pic)) { ?>
                        <img src="<?php echo htmlspecialchars($author_pic); ?>" alt="Avatar" class="small-avatar">
                    <?php } else { ?>
                        <div class="small-avatar small-avatar-text">
                            <?php echo strtoupper(substr($thread['author_name'], 0, 1)); ?>
                        </div>
                    <?php } ?>
                    <strong><?php echo htmlspecialchars($thread['author_name']); ?></strong>
                    <span class="role-badge <?php echo htmlspecialchars($thread['author_role']); ?>" style="font-size: 10px; padding: 1px 6px;"><?php echo htmlspecialchars($thread['author_role']); ?></span>
                    <span style="color:var(--text-muted);">@<?php echo htmlspecialchars($thread['author_username']); ?></span>
                </div>
                <div style="color:var(--text-muted); margin-left:auto;">
                    <span>Diposting pada <?php echo date('d M Y, H:i', strtotime($thread['created_at'])); ?></span>
                </div>
                <div class="thread-badges" style="width:100%; margin-top:8px;">
                    <?php if ($thread['is_pinned']) { ?><span class="badge pinned">📌 Pinned</span><?php } ?>
                    <?php if ($thread['is_solved']) { ?><span class="badge solved">✓ Terjawab</span><?php } ?>
                </div>
            </div>

            <h2><?php echo htmlspecialchars($thread['title']); ?></h2>

            <div class="thread-content-body"><?php echo nl2br(htmlspecialchars($thread['content'])); ?></div>

            <div class="thread-actions">
                <!-- Solved Toggle Form (Author only) -->
                <?php if ($is_author) { ?>
                    <form method="POST" style="display:inline;">
                        <button type="submit" name="toggle_solved" class="btn <?php echo $thread['is_solved'] ? 'btn-secondary' : 'btn-success'; ?> btn-sm">
                            <?php echo $thread['is_solved'] ? '✗ Tandai Belum Terjawab' : '✓ Tandai Sudah Terjawab'; ?>
                        </button>
                    </form>
                <?php } ?>

                <!-- Pin Toggle Form (Admin / Moderator only) -->
                <?php if ($is_admin || $is_moderator) { ?>
                    <form method="POST" style="display:inline;">
                        <button type="submit" name="toggle_pin" class="btn btn-secondary btn-sm" style="color: var(--warning); border-color: var(--warning);">
                            <?php echo $thread['is_pinned'] ? '📌 Unpin Thread' : '📌 Pin Thread'; ?>
                        </button>
                    </form>
                <?php } ?>

                <!-- Delete Thread (Admin / Moderator / Author) -->
                <?php if ($is_admin || $is_moderator || $is_author) { ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus thread ini? Semua komentar di dalamnya akan ikut terhapus.');">
                        <button type="submit" name="delete_thread" class="btn btn-danger btn-sm">
                            🗑 Hapus Thread
                        </button>
                    </form>
                <?php } ?>
            </div>
        </div>

        <!-- Comments Stream -->
        <div id="comments">
            <h3 class="comments-header">💬 Komentar &amp; Jawaban (<?php echo $comments_count; ?>)</h3>

            <?php if ($comments_count > 0) { ?>
                <?php foreach ($comments_data as $row) {
                    $comment_is_own = ($row['comment_author_id'] == $user_id);
                ?>
                    <div class="comment-card" style="<?php echo ($row['author_role'] === 'dosen') ? 'border-left: 3px solid var(--warning);' : ''; ?>">
                        <div class="comment-header">
                            <div class="comment-header-left">
                                <?php
                                $comment_pic = 'uploads/profile_pics/' . $row['author_avatar'];
                                if (!empty($row['author_avatar']) && file_exists(__DIR__ . '/' . $comment_pic)) { ?>
                                    <img src="<?php echo htmlspecialchars($comment_pic); ?>" alt="Avatar" class="small-avatar">
                                <?php } else { ?>
                                    <div class="small-avatar small-avatar-text">
                                        <?php echo strtoupper(substr($row['author_name'], 0, 1)); ?>
                                    </div>
                                <?php } ?>
                                <span class="comment-author"><?php echo htmlspecialchars($row['author_name']); ?></span>
                                <span class="role-badge <?php echo htmlspecialchars($row['author_role']); ?>" style="font-size: 8px; padding: 1px 3px;"><?php echo htmlspecialchars($row['author_role']); ?></span>
                                <span style="margin-left: 5px; color: var(--text-muted);"><?php echo date('d M Y, H:i', strtotime($row['created_at'])); ?></span>
                            </div>
                        </div>

                        <div class="comment-content"><?php echo nl2br(htmlspecialchars($row['content'])); ?></div>

                        <div class="comment-footer">
                            <div class="comment-footer-left">
                                <!-- Upvote Form -->
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="comment_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="upvote_comment" class="upvote-btn <?php echo $row['has_user_upvoted'] ? 'upvoted' : ''; ?>">
                                        ▲ Membantu (<?php echo $row['total_upvotes']; ?>)
                                    </button>
                                </form>
                            </div>

                            <!-- Delete comment: Admin / Moderator / Comment Owner -->
                            <?php if ($is_admin || $is_moderator || $comment_is_own) { ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus komentar ini?');">
                                    <input type="hidden" name="comment_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="delete_comment" class="btn-inline btn-inline-danger">
                                        Hapus
                                    </button>
                                </form>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="empty-state" style="margin-bottom: 20px;">
                    <p style="font-size:28px; margin-bottom:10px;">💬</p>
                    <strong>Belum ada tanggapan untuk diskusi ini.</strong>
                    <p style="margin-top:8px;">Jadilah yang pertama menjawab!</p>
                </div>
            <?php } ?>

            <!-- Add Comment Form -->
            <div class="form-container" style="max-width:100%; margin:20px 0 0; padding:25px;">
                <h3 style="margin-bottom:15px; font-size:15px; font-weight:700;">✏️ Tulis Tanggapan Anda</h3>

                <?php if (!empty($error_msg)) { ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php } ?>

                <form method="POST">
                    <div class="form-group">
                        <textarea name="comment_content" rows="4"
                                  placeholder="Ketik komentar, tanggapan, atau jawaban yang membantu di sini..."
                                  maxlength="5000"
                                  required></textarea>
                    </div>
                    <button type="submit" name="create_comment" class="btn">Kirim Tanggapan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
