<?php
// course.php
session_start();
require_once 'includes/db.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id   = $_SESSION['user_id'];

// Fetch course details
$stmt = mysqli_prepare($conn, "
    SELECT c.name, c.code, c.description, u.nama AS dosen_name, c.dosen_id 
    FROM courses c 
    LEFT JOIN users u ON c.dosen_id = u.id 
    WHERE c.id = ?
");
mysqli_stmt_bind_param($stmt, "i", $course_id);
mysqli_stmt_execute($stmt);
$course = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$course) {
    header("Location: dashboard.php");
    exit();
}

$error_msg   = '';
$success_msg = isset($_GET['success']) ? 'Thread berhasil diposting!' : '';

// Handle Create Thread
if (isset($_POST['create_thread'])) {
    $title   = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (empty($title) || empty($content)) {
        $error_msg = "Judul dan isi thread tidak boleh kosong!";
    } elseif (strlen($title) > 255) {
        $error_msg = "Judul thread terlalu panjang (maks. 255 karakter)!";
    } elseif (strlen($content) > 10000) {
        $error_msg = "Isi thread terlalu panjang (maks. 10.000 karakter)!";
    } else {
        $stmt_insert = mysqli_prepare($conn, "INSERT INTO threads (course_id, user_id, title, content) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt_insert, "iiss", $course_id, $user_id, $title, $content);

        if (mysqli_stmt_execute($stmt_insert)) {
            mysqli_stmt_close($stmt_insert);
            header("Location: course.php?id=" . $course_id . "&success=1");
            exit();
        } else {
            $error_msg = "Gagal membuat thread. Silakan coba lagi.";
            mysqli_stmt_close($stmt_insert);
        }
    }
}

// Fetch Search and Filter query parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'latest';
if (!in_array($filter, ['latest', 'popular', 'unsolved'])) {
    $filter = 'latest';
}

// Build Threads Query dynamically
$threads_query = "
    SELECT 
        t.id, 
        t.title, 
        t.content, 
        t.is_pinned, 
        t.is_solved, 
        t.created_at, 
        u.nama AS author_name, 
        u.role AS author_role,
        u.foto_profil AS author_avatar,
        COUNT(com.id) AS total_comments
    FROM threads t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN comments com ON t.id = com.thread_id
    WHERE t.course_id = ?
";

$bind_types  = "i";
$bind_params = [$course_id];

if (!empty($search)) {
    $threads_query .= " AND (t.title LIKE ? OR t.content LIKE ?) ";
    $search_param  = '%' . $search . '%';
    $bind_types   .= "ss";
    $bind_params[] = $search_param;
    $bind_params[] = $search_param;
}

if ($filter === 'unsolved') {
    $threads_query .= " AND t.is_solved = 0 ";
}

$threads_query .= " GROUP BY t.id, t.title, t.content, t.is_pinned, t.is_solved, t.created_at, u.nama, u.role, u.foto_profil ";

if ($filter === 'popular') {
    $threads_query .= " ORDER BY t.is_pinned DESC, total_comments DESC, t.id DESC ";
} else {
    $threads_query .= " ORDER BY t.is_pinned DESC, t.id DESC ";
}

$stmt_threads = mysqli_prepare($conn, $threads_query);
mysqli_stmt_bind_param($stmt_threads, $bind_types, ...$bind_params);
mysqli_stmt_execute($stmt_threads);
$threads_result = mysqli_stmt_get_result($stmt_threads);

// Store in array (prevents double-query and closes stmt before HTML)
$threads_data = [];
while ($row = mysqli_fetch_assoc($threads_result)) {
    $threads_data[] = $row;
}
mysqli_stmt_close($stmt_threads);

// Set page title and include header AFTER all logic & redirects
$page_title = $course['code'] . " - " . $course['name'];
include 'includes/header.php';
?>

<div class="container">
    <div class="breadcrumbs">
        <a href="dashboard.php">Dashboard</a> &raquo; <?php echo htmlspecialchars($course['code']); ?>
    </div>

    <!-- Course Header Jumbotron -->
    <div class="card" style="margin-bottom: 30px; border-left: 6px solid var(--primary); padding: 25px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
            <div>
                <span class="course-code" style="font-size:13px; padding:4px 10px;"><?php echo htmlspecialchars($course['code']); ?></span>
                <h2 style="font-size:24px; font-weight:700; margin-top:8px;"><?php echo htmlspecialchars($course['name']); ?></h2>
                <p style="color:var(--text-muted); font-size:14px; margin-top:6px; line-height:1.5;"><?php echo htmlspecialchars($course['description']); ?></p>
            </div>
            <div style="font-size:13px; color:var(--text-muted); background:var(--bg-main); padding:10px 15px; border-radius:8px; border:1px solid var(--border);">
                <strong>Dosen Pengampu:</strong><br>
                <?php echo $course['dosen_name'] ? htmlspecialchars($course['dosen_name']) : 'Belum ditentukan'; ?>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Main Area: Threads Feed -->
        <div>
            <!-- Search & Filters -->
            <div class="search-filter-box">
                <form method="GET" action="course.php" class="search-form">
                    <input type="hidden" name="id" value="<?php echo $course_id; ?>">
                    <input type="text" name="search" placeholder="Cari pertanyaan berdasarkan kata kunci..."
                           value="<?php echo htmlspecialchars($search); ?>" style="flex:1;">
                    <button type="submit" class="btn">🔍 Cari</button>
                    <?php if (!empty($search)) { ?>
                        <a href="course.php?id=<?php echo $course_id; ?>&filter=<?php echo $filter; ?>" class="btn btn-secondary">Reset</a>
                    <?php } ?>
                </form>

                <div class="filter-tabs">
                    <a href="course.php?id=<?php echo $course_id; ?>&filter=latest<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                       class="filter-tab <?php echo $filter === 'latest' ? 'active' : ''; ?>">Terbaru</a>
                    <a href="course.php?id=<?php echo $course_id; ?>&filter=popular<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                       class="filter-tab <?php echo $filter === 'popular' ? 'active' : ''; ?>">Terpopuler</a>
                    <a href="course.php?id=<?php echo $course_id; ?>&filter=unsolved<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                       class="filter-tab <?php echo $filter === 'unsolved' ? 'active' : ''; ?>">Belum Dijawab</a>
                </div>
            </div>

            <!-- Thread list -->
            <div class="thread-feed">
                <?php if (count($threads_data) > 0) { ?>
                    <?php foreach ($threads_data as $row) { ?>
                        <a href="thread.php?id=<?php echo $row['id']; ?>" class="thread-card-link" style="<?php echo $row['is_pinned'] ? 'border-left: 4px solid var(--warning);' : ''; ?>">
                            <div class="thread-card-inner">
                                <div class="thread-header">
                                    <div class="thread-header-left">
                                        <div class="author-meta">
                                            <?php
                                            $author_pic = 'uploads/profile_pics/' . $row['author_avatar'];
                                            if (!empty($row['author_avatar']) && file_exists(__DIR__ . '/' . $author_pic)) { ?>
                                                <img src="<?php echo htmlspecialchars($author_pic); ?>" alt="Avatar" class="small-avatar">
                                            <?php } else { ?>
                                                <div class="small-avatar small-avatar-text">
                                                    <?php echo strtoupper(substr($row['author_name'], 0, 1)); ?>
                                                </div>
                                            <?php } ?>
                                            <strong><?php echo htmlspecialchars($row['author_name']); ?></strong>
                                        </div>
                                        <span class="role-badge <?php echo htmlspecialchars($row['author_role']); ?>" style="font-size: 9px; padding: 1px 4px;"><?php echo htmlspecialchars($row['author_role']); ?></span>
                                        <span>&bull;</span>
                                        <span><?php echo date('d M Y, H:i', strtotime($row['created_at'])); ?></span>
                                    </div>
                                    <div class="thread-badges">
                                        <?php if ($row['is_pinned']) { ?><span class="badge pinned">📌 Pinned</span><?php } ?>
                                        <?php if ($row['is_solved']) { ?><span class="badge solved">✓ Terjawab</span><?php } ?>
                                    </div>
                                </div>

                                <h3 class="thread-title"><?php echo htmlspecialchars($row['title']); ?></h3>

                                <p class="thread-excerpt">
                                    <?php
                                        $excerpt = strip_tags($row['content']);
                                        echo htmlspecialchars(strlen($excerpt) > 150 ? substr($excerpt, 0, 150) . '...' : $excerpt);
                                    ?>
                                </p>

                                <div class="thread-footer">
                                    <span>Kategori: <strong><?php echo htmlspecialchars($course['code']); ?></strong></span>
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <span style="font-weight: 600; color: var(--primary);">💬 <?php echo $row['total_comments']; ?> Komentar</span>
                                        <span class="btn btn-sm" style="padding:5px 14px; font-size:12px; pointer-events:none;">Buka Thread →</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php } ?>
                <?php } else { ?>
                    <div class="empty-state">
                        <?php if (!empty($search)) { ?>
                            <p style="font-size:28px; margin-bottom:10px;">🔍</p>
                            <strong>Tidak ada thread ditemukan untuk "<?php echo htmlspecialchars($search); ?>"</strong>
                            <p style="margin-top:8px;"><a href="course.php?id=<?php echo $course_id; ?>">Lihat semua thread</a></p>
                        <?php } else { ?>
                            <p style="font-size:28px; margin-bottom:10px;">💬</p>
                            <strong>Belum ada thread di sini.</strong>
                            <p style="margin-top:8px;">Jadilah orang pertama yang memulai diskusi!</p>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>

        <!-- Sidebar Area: Create Thread Form -->
        <div>
            <div class="form-container" style="max-width:100%; margin:0; padding:24px;">
                <h3 style="margin-bottom:15px; font-size:16px; font-weight:700; color:var(--text-main);">💬 Buat Thread Baru</h3>

                <?php if (!empty($error_msg)) { ?>
                    <div class="alert alert-danger" style="padding: 8px 12px; font-size:12px;"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php } ?>

                <?php if (!empty($success_msg)) { ?>
                    <div class="alert alert-success" style="padding: 8px 12px; font-size:12px;"><?php echo htmlspecialchars($success_msg); ?></div>
                <?php } ?>

                <form method="POST">
                    <div class="form-group" style="margin-bottom:15px;">
                        <label for="title" style="font-size:12px;">Judul Diskusi</label>
                        <input type="text" id="title" name="title" placeholder="Pertanyaan / topik utama" required
                               maxlength="255"
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                    </div>

                    <div class="form-group" style="margin-bottom:15px;">
                        <label for="content" style="font-size:12px;">Isi Detail Diskusi</label>
                        <textarea id="content" name="content" rows="6"
                                  placeholder="Tulis rincian pertanyaan atau topik bahasan di sini..."
                                  required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                    </div>

                    <button type="submit" name="create_thread" class="btn" style="width:100%;">Posting Thread</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
