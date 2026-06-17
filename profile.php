<?php
// profile.php
include 'db.php';
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $nama = trim($_POST['nama']);
    $jurusan = trim($_POST['jurusan']);
    $angkatan = intval($_POST['angkatan']);

    if (empty($nama) || empty($jurusan) || empty($angkatan)) {
        $error = "Semua field wajib diisi!";
    } elseif ($angkatan < 1950 || $angkatan > date('Y') + 2) {
        $error = "Tahun angkatan tidak valid!";
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET nama = ?, jurusan = ?, angkatan = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssii", $nama, $jurusan, $angkatan, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "Profil berhasil diperbarui!";
            // Update session data
            $_SESSION['nama'] = $nama;
            $_SESSION['jurusan'] = $jurusan;
            $_SESSION['angkatan'] = $angkatan;
        } else {
            $error = "Gagal memperbarui profil. Silakan coba lagi.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch user data from DB (always get fresh data)
$stmt = mysqli_prepare($conn, "SELECT username, nama, role, jurusan, angkatan FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_res = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($user_res);
mysqli_stmt_close($stmt);

// Fetch stats: total threads
$stmt_threads = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM threads WHERE user_id = ?");
mysqli_stmt_bind_param($stmt_threads, "i", $user_id);
mysqli_stmt_execute($stmt_threads);
$res_threads = mysqli_stmt_get_result($stmt_threads);
$threads_count = mysqli_fetch_assoc($res_threads)['total'];
mysqli_stmt_close($stmt_threads);

// Fetch stats: total comments
$stmt_comments = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM comments WHERE user_id = ?");
mysqli_stmt_bind_param($stmt_comments, "i", $user_id);
mysqli_stmt_execute($stmt_comments);
$res_comments = mysqli_stmt_get_result($stmt_comments);
$comments_count = mysqli_fetch_assoc($res_comments)['total'];
mysqli_stmt_close($stmt_comments);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - CommunityHub</title>
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
        <a href="dashboard.php">Dashboard</a> &raquo; Profil Saya
    </div>

    <div class="dashboard-grid">
        <!-- Profile Form Info -->
        <div>
            <div class="form-container" style="max-width:100%; margin:0; padding:30px;">
                <h2 style="text-align:left; margin-bottom:15px;">Edit Profil</h2>
                <p style="text-align:left; color:var(--text-muted); margin-bottom:20px; font-size:14px; margin-top:-10px;">
                    Username Anda: <strong>@<?php echo htmlspecialchars($user_data['username']); ?></strong>
                </p>

                <?php if (!empty($error)) { ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php } ?>

                <?php if (!empty($success)) { ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php } ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="nama">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($user_data['nama']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="jurusan">Jurusan</label>
                        <input type="text" id="jurusan" name="jurusan" value="<?php echo htmlspecialchars($user_data['jurusan']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="angkatan">Angkatan (Tahun)</label>
                        <input type="number" id="angkatan" name="angkatan" value="<?php echo htmlspecialchars($user_data['angkatan']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" value="<?php echo ucfirst(htmlspecialchars($user_data['role'])); ?>" disabled style="background:#f1f5f9; cursor:not-allowed;">
                    </div>

                    <button type="submit" name="update_profile" class="btn">Simpan Perubahan</button>
                </form>
            </div>
        </div>

        <!-- Sidebar stats -->
        <div>
            <div class="profile-widget">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user_data['nama'], 0, 1)); ?>
                </div>
                <h3><?php echo htmlspecialchars($user_data['nama']); ?></h3>
                <p class="role"><span class="role-badge <?php echo htmlspecialchars($user_data['role']); ?>"><?php echo htmlspecialchars($user_data['role']); ?></span></p>
                
                <div class="profile-details" style="text-align:center;">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:15px;">
                        <div style="background:#f8fafc; padding:15px; border-radius:8px; border:1px solid #e2e8f0;">
                            <div style="font-size:24px; font-weight:700; color:var(--primary);"><?php echo $threads_count; ?></div>
                            <div style="font-size:11px; color:var(--text-muted); font-weight:500;">Thread Dibuat</div>
                        </div>
                        <div style="background:#f8fafc; padding:15px; border-radius:8px; border:1px solid #e2e8f0;">
                            <div style="font-size:24px; font-weight:700; color:var(--success);"><?php echo $comments_count; ?></div>
                            <div style="font-size:11px; color:var(--text-muted); font-weight:500;">Komentar / Reply</div>
                        </div>
                    </div>
                </div>
                
                <div style="text-align:left; border-top:1px solid var(--border); padding-top:15px; font-size:13px; color:var(--text-muted);">
                    <p style="margin-bottom:6px;"><strong>Jurusan:</strong> <?php echo htmlspecialchars($user_data['jurusan']); ?></p>
                    <p><strong>Angkatan:</strong> <?php echo htmlspecialchars($user_data['angkatan']); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<footer>
    &copy; <?php echo date('Y'); ?> CommunityHub. All rights reserved.
</footer>

</body>
</html>
