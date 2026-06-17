<?php
// admin.php
include 'db.php';
session_start();

// Access check: only admins can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$error_course = '';
$success_course = '';
$error_user = '';
$success_user = '';

// Handle Course Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Add Course
    if (isset($_POST['add_course'])) {
        $code = strtoupper(trim($_POST['code']));
        $name = trim($_POST['name']);
        $desc = trim($_POST['desc']);
        $dosen_id = empty($_POST['dosen_id']) ? null : intval($_POST['dosen_id']);

        if (empty($code) || empty($name)) {
            $error_course = "Kode dan Nama mata kuliah wajib diisi!";
        } else {
            // Check duplicate code/name
            $chk = mysqli_prepare($conn, "SELECT id FROM courses WHERE code = ? OR name = ?");
            mysqli_stmt_bind_param($chk, "ss", $code, $name);
            mysqli_stmt_execute($chk);
            mysqli_stmt_store_result($chk);
            
            if (mysqli_stmt_num_rows($chk) > 0) {
                $error_course = "Kode atau Nama mata kuliah sudah terdaftar!";
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO courses (name, code, description, dosen_id) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sssi", $name, $code, $desc, $dosen_id);
                if (mysqli_stmt_execute($stmt)) {
                    $success_course = "Mata kuliah berhasil ditambahkan!";
                    header("Location: admin.php");
                    exit();
                } else {
                    $error_course = "Gagal menyimpan mata kuliah.";
                }
                mysqli_stmt_close($stmt);
            }
            mysqli_stmt_close($chk);
        }
    }

    // 2. Update Course
    if (isset($_POST['update_course'])) {
        $edit_id = intval($_POST['edit_id']);
        $code = strtoupper(trim($_POST['code']));
        $name = trim($_POST['name']);
        $desc = trim($_POST['desc']);
        $dosen_id = empty($_POST['dosen_id']) ? null : intval($_POST['dosen_id']);

        if (empty($code) || empty($name)) {
            $error_course = "Kode dan Nama mata kuliah wajib diisi!";
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE courses SET name = ?, code = ?, description = ?, dosen_id = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "sssii", $name, $code, $desc, $dosen_id, $edit_id);
            if (mysqli_stmt_execute($stmt)) {
                $success_course = "Mata kuliah berhasil diperbarui!";
                header("Location: admin.php");
                exit();
            } else {
                $error_course = "Gagal memperbarui mata kuliah.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // 3. Delete Course
    if (isset($_POST['delete_course'])) {
        $delete_id = intval($_POST['course_id']);
        $stmt = mysqli_prepare($conn, "DELETE FROM courses WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        if (mysqli_stmt_execute($stmt)) {
            header("Location: admin.php");
            exit();
        } else {
            $error_course = "Gagal menghapus mata kuliah.";
        }
        mysqli_stmt_close($stmt);
    }

    // 4. Update User Role
    if (isset($_POST['update_user_role'])) {
        $target_user_id = intval($_POST['user_id']);
        $new_role = $_POST['role'];
        if (in_array($new_role, ['admin', 'dosen', 'mahasiswa'])) {
            if ($target_user_id == $_SESSION['user_id'] && $new_role !== 'admin') {
                $error_user = "Anda tidak dapat menurunkan peran admin Anda sendiri!";
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE users SET role = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "si", $new_role, $target_user_id);
                if (mysqli_stmt_execute($stmt)) {
                    $success_user = "Role user berhasil diubah!";
                    header("Location: admin.php");
                    exit();
                } else {
                    $error_user = "Gagal mengubah role user.";
                }
                mysqli_stmt_close($stmt);
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
                $success_user = "User berhasil dihapus.";
                header("Location: admin.php");
                exit();
            } else {
                $error_user = "Gagal menghapus user.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch all lecturers (dosen) for assignment lists
$dosen_res = mysqli_query($conn, "SELECT id, nama FROM users WHERE role = 'dosen' ORDER BY nama ASC");
$lecturers = [];
while ($d = mysqli_fetch_assoc($dosen_res)) {
    $lecturers[] = $d;
}

// Check if we are in Course Edit Mode
$edit_mode = false;
$course_to_edit = null;
if (isset($_GET['edit_course'])) {
    $edit_mode = true;
    $edit_id = intval($_GET['edit_course']);
    $stmt = mysqli_prepare($conn, "SELECT id, name, code, description, dosen_id FROM courses WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $edit_id);
    mysqli_stmt_execute($stmt);
    $course_to_edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

// Fetch all courses for management table
$courses_res = mysqli_query($conn, "
    SELECT c.id, c.code, c.name, c.description, u.nama AS dosen_name, c.dosen_id 
    FROM courses c 
    LEFT JOIN users u ON c.dosen_id = u.id 
    ORDER BY c.code ASC
");

// Fetch all users for user management table
$users_res = mysqli_query($conn, "SELECT id, username, nama, role, jurusan, angkatan FROM users ORDER BY role ASC, id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - CommunityHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav>
    <h1><a href="dashboard.php">CommunityHub</a></h1>
    <div class="nav-links">
        <a href="dashboard.php" class="nav-item">Dashboard</a>
        <?php if ($_SESSION['role'] === 'admin') { ?>
            <a href="admin.php" class="nav-item">Panel Admin</a>
        <?php } ?>
        <a href="profile.php" class="nav-item">Profil Saya</a>
        <div class="nav-user-badge">
            <span><?php echo htmlspecialchars($_SESSION['nama']); ?></span>
            <span class="role-badge <?php echo htmlspecialchars($_SESSION['role']); ?>"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
        </div>
        <a href="logout.php" class="btn btn-secondary btn-sm">Keluar</a>
    </div>
</nav>

<div class="container">
    <div class="breadcrumbs">
        <a href="dashboard.php">Dashboard</a> &raquo; Panel Admin
    </div>

    <h2 style="margin-bottom: 25px;">Dashboard Pengelolaan Admin</h2>

    <div class="dashboard-grid" style="grid-template-columns: 1.2fr 1.8fr; gap:30px; margin-bottom:40px;">
        <!-- Left: Course Add/Edit Form -->
        <div>
            <div class="form-container" style="max-width:100%; margin:0; padding:24px;">
                <h3 style="margin-bottom:15px; font-weight:700; color:var(--text-main);">
                    <?php echo $edit_mode ? 'Edit Mata Kuliah' : 'Tambah Mata Kuliah Baru'; ?>
                </h3>

                <?php if (!empty($error_course)) { ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_course); ?></div>
                <?php } ?>
                
                <?php if (!empty($success_course)) { ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_course); ?></div>
                <?php } ?>

                <form method="POST">
                    <?php if ($edit_mode) { ?>
                        <input type="hidden" name="edit_id" value="<?php echo $course_to_edit['id']; ?>">
                    <?php } ?>

                    <div class="form-group">
                        <label for="code">Kode Mata Kuliah</label>
                        <input type="text" id="code" name="code" placeholder="Contoh: IF-301" required value="<?php echo $edit_mode ? htmlspecialchars($course_to_edit['code']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="name">Nama Mata Kuliah</label>
                        <input type="text" id="name" name="name" placeholder="Contoh: Pemrograman Web" required value="<?php echo $edit_mode ? htmlspecialchars($course_to_edit['name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="desc">Deskripsi Singkat</label>
                        <textarea id="desc" name="desc" rows="4" placeholder="Penjelasan tentang cakupan mata kuliah..." required><?php echo $edit_mode ? htmlspecialchars($course_to_edit['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="dosen_id">Dosen Pengampu / Moderator</label>
                        <select id="dosen_id" name="dosen_id">
                            <option value="">-- Belum Ditentukan (Kosong) --</option>
                            <?php foreach ($lecturers as $l) { 
                                $selected = '';
                                if ($edit_mode && $course_to_edit['dosen_id'] == $l['id']) {
                                    $selected = 'selected';
                                }
                            ?>
                                <option value="<?php echo $l['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($l['nama']); ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div style="display:flex; gap:10px;">
                        <button type="submit" name="<?php echo $edit_mode ? 'update_course' : 'add_course'; ?>" class="btn" style="flex:1;">
                            <?php echo $edit_mode ? 'Perbarui' : 'Tambahkan'; ?>
                        </button>
                        <?php if ($edit_mode) { ?>
                            <a href="admin.php" class="btn btn-secondary" style="flex:1;">Batal</a>
                        <?php } ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right: Course List Management -->
        <div>
            <div class="section-title" style="margin-bottom:15px;">
                <h3>Daftar Mata Kuliah</h3>
            </div>
            
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Mata Kuliah</th>
                            <th>Dosen/Moderator</th>
                            <th style="text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($courses_res) > 0) { ?>
                            <?php while ($row = mysqli_fetch_assoc($courses_res)) { ?>
                                <tr>
                                    <td><span class="course-code" style="margin:0;"><?php echo htmlspecialchars($row['code']); ?></span></td>
                                    <td style="font-weight:600;"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo $row['dosen_name'] ? htmlspecialchars($row['dosen_name']) : '<em>Tidak ada</em>'; ?></td>
                                    <td style="text-align:right;">
                                        <a href="admin.php?edit_course=<?php echo $row['id']; ?>" class="btn-inline" style="margin-right:10px;">Edit</a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus mata kuliah ini? Semua thread & komentar di dalamnya akan terhapus!');">
                                            <input type="hidden" name="course_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="delete_course" class="btn-inline btn-inline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="4" style="text-align:center; color:var(--text-muted);">Belum ada mata kuliah terdaftar.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section 2: User Management -->
    <div>
        <div class="section-title" style="margin-bottom:15px;">
            <h3>Kelola Data & Hak Akses Pengguna</h3>
        </div>

        <?php if (!empty($error_user)) { ?>
            <div class="alert alert-danger" style="margin-bottom:15px;"><?php echo htmlspecialchars($error_user); ?></div>
        <?php } ?>
        
        <?php if (!empty($success_user)) { ?>
            <div class="alert alert-success" style="margin-bottom:15px;"><?php echo htmlspecialchars($success_user); ?></div>
        <?php } ?>

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
                                <td><?php echo htmlspecialchars($row['jurusan']); ?> (<?php echo htmlspecialchars($row['angkatan']); ?>)</td>
                                <td>
                                    <!-- Change role form -->
                                    <form method="POST" style="display:inline-flex; align-items:center; gap:5px;">
                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                        <select name="role" style="padding: 4px 8px; font-size:12px; width:auto; border-radius:4px;" onchange="this.form.submit()">
                                            <option value="mahasiswa" <?php echo $row['role'] === 'mahasiswa' ? 'selected' : ''; ?>>Mahasiswa</option>
                                            <option value="dosen" <?php echo $row['role'] === 'dosen' ? 'selected' : ''; ?>>Dosen</option>
                                            <option value="admin" <?php echo $row['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                        <input type="hidden" name="update_user_role" value="1">
                                    </form>
                                </td>
                                <td style="text-align:right;">
                                    <?php if ($row['id'] != $_SESSION['user_id']) { ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini beserta seluruh kontribusinya?');">
                                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn btn-danger btn-sm" style="padding:4px 8px; font-size:11px;">Hapus Akun</button>
                                        </form>
                                    <?php } else { ?>
                                        <span style="color:var(--text-muted); font-size:11px;">Akun Anda</span>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="5" style="text-align:center; color:var(--text-muted);">Belum ada user.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<footer>
    &copy; <?php echo date('Y'); ?> CommunityHub. All rights reserved.
</footer>

</body>
</html>
