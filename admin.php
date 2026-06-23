<?php
// admin.php
session_start();
require_once 'includes/db.php';

// Access check: only admins can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$error_course   = '';
$success_course = '';
$error_user     = '';
$success_user   = '';

// Handle Course & User Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Add Course
    if (isset($_POST['add_course'])) {
        $code     = strtoupper(trim($_POST['code']));
        $name     = trim($_POST['name']);
        $desc     = trim($_POST['desc']);
        $dosen_id = empty($_POST['dosen_id']) ? null : intval($_POST['dosen_id']);

        if (empty($code) || empty($name)) {
            $error_course = "Kode dan Nama mata kuliah wajib diisi!";
        } else {
            // Check duplicate code or name
            $chk = mysqli_prepare($conn, "SELECT id FROM courses WHERE code = ? OR name = ?");
            mysqli_stmt_bind_param($chk, "ss", $code, $name);
            mysqli_stmt_execute($chk);
            mysqli_stmt_store_result($chk);

            if (mysqli_stmt_num_rows($chk) > 0) {
                $error_course = "Kode atau Nama mata kuliah sudah terdaftar!";
                mysqli_stmt_close($chk);
            } else {
                mysqli_stmt_close($chk);
                $stmt = mysqli_prepare($conn, "INSERT INTO courses (name, code, description, dosen_id) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sssi", $name, $code, $desc, $dosen_id);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    header("Location: admin.php");
                    exit();
                } else {
                    $error_course = "Gagal menyimpan mata kuliah.";
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }

    // 2. Update Course
    if (isset($_POST['update_course'])) {
        $edit_id  = intval($_POST['edit_id']);
        $code     = strtoupper(trim($_POST['code']));
        $name     = trim($_POST['name']);
        $desc     = trim($_POST['desc']);
        $dosen_id = empty($_POST['dosen_id']) ? null : intval($_POST['dosen_id']);

        if (empty($code) || empty($name)) {
            $error_course = "Kode dan Nama mata kuliah wajib diisi!";
        } else {
            // Check duplicate (exclude current course itself)
            $chk = mysqli_prepare($conn, "SELECT id FROM courses WHERE (code = ? OR name = ?) AND id != ?");
            mysqli_stmt_bind_param($chk, "ssi", $code, $name, $edit_id);
            mysqli_stmt_execute($chk);
            mysqli_stmt_store_result($chk);

            if (mysqli_stmt_num_rows($chk) > 0) {
                $error_course = "Kode atau Nama mata kuliah sudah digunakan oleh mata kuliah lain!";
                mysqli_stmt_close($chk);
            } else {
                mysqli_stmt_close($chk);
                $stmt = mysqli_prepare($conn, "UPDATE courses SET name = ?, code = ?, description = ?, dosen_id = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "sssii", $name, $code, $desc, $dosen_id, $edit_id);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    header("Location: admin.php");
                    exit();
                } else {
                    $error_course = "Gagal memperbarui mata kuliah.";
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }

    // 3. Delete Course
    if (isset($_POST['delete_course'])) {
        $delete_id = intval($_POST['course_id']);
        $stmt = mysqli_prepare($conn, "DELETE FROM courses WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: admin.php");
            exit();
        } else {
            $error_course = "Gagal menghapus mata kuliah.";
            mysqli_stmt_close($stmt);
        }
    }

    // 4. Update User Role
    if (isset($_POST['update_user_role'])) {
        $target_user_id = intval($_POST['user_id']);
        $new_role       = $_POST['role'];
        if (in_array($new_role, ['admin', 'dosen', 'mahasiswa'])) {
            if ($target_user_id == $_SESSION['user_id'] && $new_role !== 'admin') {
                $error_user = "Anda tidak dapat menurunkan peran admin Anda sendiri!";
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE users SET role = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "si", $new_role, $target_user_id);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    header("Location: admin.php");
                    exit();
                } else {
                    $error_user = "Gagal mengubah role user.";
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }

    // 5. Delete User
    if (isset($_POST['delete_user'])) {
        $target_user_id = intval($_POST['user_id']);
        if ($target_user_id == $_SESSION['user_id']) {
            $error_user = "Anda tidak dapat menghapus akun Anda sendiri!";
        } else {
            $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $target_user_id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header("Location: admin.php");
                exit();
            } else {
                $error_user = "Gagal menghapus user.";
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Fetch all lecturers (dosen) for assignment dropdown
$dosen_res = mysqli_query($conn, "SELECT id, nama FROM users WHERE role = 'dosen' ORDER BY nama ASC");
$lecturers = [];
while ($d = mysqli_fetch_assoc($dosen_res)) {
    $lecturers[] = $d;
}

// Check if in Course Edit Mode
$edit_mode      = false;
$course_to_edit = null;
if (isset($_GET['edit_course'])) {
    $edit_mode = true;
    $edit_id   = intval($_GET['edit_course']);
    $stmt = mysqli_prepare($conn, "SELECT id, name, code, description, dosen_id FROM courses WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $edit_id);
    mysqli_stmt_execute($stmt);
    $course_to_edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$course_to_edit) {
        $edit_mode = false; // Guard against invalid id in URL
    }
}

// Fetch all courses for management table (with thread count)
$courses_res = mysqli_query($conn, "
    SELECT c.id, c.code, c.name, c.description, u.nama AS dosen_name, c.dosen_id,
           COUNT(t.id) AS thread_count
    FROM courses c
    LEFT JOIN users u ON c.dosen_id = u.id
    LEFT JOIN threads t ON c.id = t.course_id
    GROUP BY c.id, c.code, c.name, c.description, u.nama, c.dosen_id
    ORDER BY c.code ASC
");

// Fetch all users for user management table
$users_res = mysqli_query($conn, "SELECT id, username, nama, role, jurusan, angkatan FROM users ORDER BY role ASC, id DESC");

// System stats
$stats_q   = mysqli_query($conn, "
    SELECT
        (SELECT COUNT(*) FROM users)    AS total_users,
        (SELECT COUNT(*) FROM courses)  AS total_courses,
        (SELECT COUNT(*) FROM threads)  AS total_threads,
        (SELECT COUNT(*) FROM comments) AS total_comments
");
$sys_stats = mysqli_fetch_assoc($stats_q);

$page_title = "Panel Admin";
include 'includes/header.php';
?>

<div class="container">
    <div class="breadcrumbs">
        <a href="dashboard.php">Dashboard</a> &raquo; Panel Admin
    </div>

    <h2 style="margin-bottom: 20px;">🛡️ Dashboard Pengelolaan Admin</h2>

    <!-- System Stats -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:16px; margin-bottom:30px;">
        <div class="card" style="text-align:center; padding:18px;">
            <div style="font-size:28px; font-weight:800; color:var(--primary);"><?php echo $sys_stats['total_users']; ?></div>
            <div style="font-size:12px; color:var(--text-muted); margin-top:4px;">Total Pengguna</div>
        </div>
        <div class="card" style="text-align:center; padding:18px;">
            <div style="font-size:28px; font-weight:800; color:var(--primary);"><?php echo $sys_stats['total_courses']; ?></div>
            <div style="font-size:12px; color:var(--text-muted); margin-top:4px;">Mata Kuliah</div>
        </div>
        <div class="card" style="text-align:center; padding:18px;">
            <div style="font-size:28px; font-weight:800; color:var(--primary);"><?php echo $sys_stats['total_threads']; ?></div>
            <div style="font-size:12px; color:var(--text-muted); margin-top:4px;">Thread Diskusi</div>
        </div>
        <div class="card" style="text-align:center; padding:18px;">
            <div style="font-size:28px; font-weight:800; color:var(--primary);"><?php echo $sys_stats['total_comments']; ?></div>
            <div style="font-size:12px; color:var(--text-muted); margin-top:4px;">Total Komentar</div>
        </div>
    </div>

    <?php if (!empty($error_course)) { ?>
        <div class="alert alert-danger" style="margin-bottom:20px;"><?php echo htmlspecialchars($error_course); ?></div>
    <?php } ?>
    <?php if (!empty($error_user)) { ?>
        <div class="alert alert-danger" style="margin-bottom:20px;"><?php echo htmlspecialchars($error_user); ?></div>
    <?php } ?>

    <!-- Course Management Section -->
    <div class="dashboard-grid" style="grid-template-columns: 1.2fr 1.8fr; gap:30px; margin-bottom:40px;">
        <!-- Left: Course Add/Edit Form -->
        <div>
            <div class="form-container" style="max-width:100%; margin:0; padding:24px;">
                <h3 style="margin-bottom:15px; font-weight:700; color:var(--text-main);">
                    <?php echo $edit_mode ? '✏️ Edit Mata Kuliah' : '➕ Tambah Mata Kuliah Baru'; ?>
                </h3>

                <form method="POST">
                    <?php if ($edit_mode) { ?>
                        <input type="hidden" name="edit_id" value="<?php echo $course_to_edit['id']; ?>">
                    <?php } ?>

                    <div class="form-group">
                        <label for="code">Kode Mata Kuliah</label>
                        <input type="text" id="code" name="code" placeholder="Contoh: MK001" required maxlength="50"
                               value="<?php echo $edit_mode ? htmlspecialchars($course_to_edit['code']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="name">Nama Mata Kuliah</label>
                        <input type="text" id="name" name="name" placeholder="Contoh: Pemrograman Web" required maxlength="150"
                               value="<?php echo $edit_mode ? htmlspecialchars($course_to_edit['name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="desc">Deskripsi Singkat</label>
                        <textarea id="desc" name="desc" rows="3"
                                  placeholder="Penjelasan tentang cakupan mata kuliah..."><?php echo $edit_mode ? htmlspecialchars($course_to_edit['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="dosen_id">Dosen Pengampu / Moderator</label>
                        <select id="dosen_id" name="dosen_id">
                            <option value="">-- Belum Ditentukan --</option>
                            <?php foreach ($lecturers as $l) {
                                $selected = ($edit_mode && $course_to_edit['dosen_id'] == $l['id']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $l['id']; ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($l['nama']); ?>
                                </option>
                            <?php } ?>
                        </select>
                        <?php if (empty($lecturers)) { ?>
                            <small style="color:var(--warning); font-size:11px; margin-top:4px; display:block;">
                                ⚠️ Belum ada user dengan role Dosen. Ubah role user terlebih dahulu.
                            </small>
                        <?php } ?>
                    </div>

                    <div style="display:flex; gap:10px;">
                        <button type="submit" name="<?php echo $edit_mode ? 'update_course' : 'add_course'; ?>" class="btn" style="flex:1;">
                            <?php echo $edit_mode ? 'Perbarui' : 'Tambahkan'; ?>
                        </button>
                        <?php if ($edit_mode) { ?>
                            <a href="admin.php" class="btn btn-secondary" style="flex:1; justify-content:center;">Batal</a>
                        <?php } ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right: Course List Management -->
        <div>
            <div class="section-title" style="margin-bottom:15px;">
                <h3>📋 Daftar Mata Kuliah</h3>
            </div>

            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Mata Kuliah</th>
                            <th>Dosen/Moderator</th>
                            <th style="text-align:center;">Thread</th>
                            <th style="text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($courses_res) > 0) { ?>
                            <?php while ($row = mysqli_fetch_assoc($courses_res)) { ?>
                                <tr>
                                    <td><span class="course-code" style="margin:0;"><?php echo htmlspecialchars($row['code']); ?></span></td>
                                    <td style="font-weight:600;"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo $row['dosen_name'] ? htmlspecialchars($row['dosen_name']) : '<em style="color:var(--text-muted);">Belum ada</em>'; ?></td>
                                    <td style="text-align:center; font-weight:600; color:var(--primary);"><?php echo $row['thread_count']; ?></td>
                                    <td style="text-align:right; white-space:nowrap;">
                                        <a href="admin.php?edit_course=<?php echo $row['id']; ?>" class="btn-inline" style="margin-right:8px;">✏️ Edit</a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus mata kuliah \'<?php echo htmlspecialchars(addslashes($row['name'])); ?>\'? Semua thread &amp; komentar akan ikut terhapus!');">
                                            <input type="hidden" name="course_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="delete_course" class="btn-inline btn-inline-danger">🗑 Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr><td colspan="5" style="text-align:center; color:var(--text-muted); padding:30px;">Belum ada mata kuliah terdaftar.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- User Management Section -->
    <div>
        <div class="section-title" style="margin-bottom:15px;">
            <h3>👥 Kelola Data &amp; Hak Akses Pengguna</h3>
        </div>

        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Jurusan (Angkatan)</th>
                        <th>Level Akses (Role)</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($users_res) > 0) { ?>
                        <?php while ($row = mysqli_fetch_assoc($users_res)) { ?>
                            <tr>
                                <td style="font-family:monospace; font-weight:600;">@<?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['jurusan']); ?>
                                    <span style="color:var(--text-muted);">(<?php echo htmlspecialchars($row['angkatan']); ?>)</span>
                                </td>
                                <td>
                                    <!-- Change role form (submit on change) -->
                                    <form method="POST" style="display:inline-flex; align-items:center; gap:5px;"
                                          <?php echo ($row['id'] == $_SESSION['user_id']) ? 'onsubmit="return false;"' : ''; ?>>
                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="update_user_role" value="1">
                                        <select name="role"
                                                style="padding: 4px 8px; font-size:12px; width:auto; border-radius:4px;"
                                                onchange="if(confirm('Ubah role menjadi ' + this.value + '?')) { this.form.submit(); } else { this.value = '<?php echo $row['role']; ?>'; }"
                                                <?php echo ($row['id'] == $_SESSION['user_id']) ? 'disabled title="Tidak dapat mengubah role sendiri"' : ''; ?>>
                                            <option value="mahasiswa" <?php echo $row['role'] === 'mahasiswa' ? 'selected' : ''; ?>>Mahasiswa</option>
                                            <option value="dosen"     <?php echo $row['role'] === 'dosen' ? 'selected' : ''; ?>>Dosen</option>
                                            <option value="admin"     <?php echo $row['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                    </form>
                                </td>
                                <td style="text-align:right;">
                                    <?php if ($row['id'] != $_SESSION['user_id']) { ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus akun @<?php echo htmlspecialchars(addslashes($row['username'])); ?> beserta seluruh kontribusinya?');">
                                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn btn-danger btn-sm" style="padding:4px 8px; font-size:11px;">Hapus Akun</button>
                                        </form>
                                    <?php } else { ?>
                                        <span style="color:var(--primary); font-size:11px; font-weight:600;">👤 Anda</span>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr><td colspan="5" style="text-align:center; color:var(--text-muted); padding:30px;">Belum ada user terdaftar.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
