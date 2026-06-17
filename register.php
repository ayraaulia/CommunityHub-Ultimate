<?php
// register.php
include 'db.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $nama = trim($_POST['nama']);
    $jurusan = trim($_POST['jurusan']);
    $angkatan = intval($_POST['angkatan']);
    $role = $_POST['role'];

    // Input validation
    if (empty($username) || empty($password) || empty($nama) || empty($jurusan) || empty($angkatan)) {
        $error = "Semua field wajib diisi!";
    } elseif ($angkatan < 1950 || $angkatan > date('Y') + 2) {
        $error = "Tahun angkatan tidak valid!";
    } elseif (!in_array($role, ['mahasiswa', 'dosen'])) {
        $error = "Role tidak valid!";
    } else {
        // Check if username already exists
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "Username sudah digunakan!";
        } else {
            // Insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, nama, jurusan, angkatan, role) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($insert_stmt, "ssssis", $username, $hashed_password, $nama, $jurusan, $angkatan, $role);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $success = "Registrasi berhasil! Silakan masuk.";
            } else {
                $error = "Terjadi kesalahan saat pendaftaran. Silakan coba lagi.";
            }
            mysqli_stmt_close($insert_stmt);
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - CommunityHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav>
    <h1><a href="index.php">CommunityHub</a></h1>
    <div class="nav-links">
        <a href="login.php" class="nav-item">Masuk</a>
        <a href="register.php" class="nav-item">Daftar</a>
    </div>
</nav>

<div class="container" style="display:flex; justify-content:center; align-items:center; min-height:80vh; margin-top:0; margin-bottom:0;">
    <div class="form-container" style="margin:20px 0;">
        <h2>Daftar Akun</h2>

        <?php if (!empty($error)) { ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <?php if (!empty($success)) { ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php } ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Masukkan username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Masukkan password" required>
            </div>

            <div class="form-group">
                <label for="nama">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" placeholder="Nama lengkap Anda" required value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="jurusan">Jurusan</label>
                <input type="text" id="jurusan" name="jurusan" placeholder="Contoh: Teknik Informatika" required value="<?php echo isset($_POST['jurusan']) ? htmlspecialchars($_POST['jurusan']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="angkatan">Angkatan (Tahun)</label>
                <input type="number" id="angkatan" name="angkatan" placeholder="Contoh: 2023" required value="<?php echo isset($_POST['angkatan']) ? htmlspecialchars($_POST['angkatan']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="role">Mendaftar Sebagai</label>
                <select id="role" name="role" required>
                    <option value="mahasiswa" <?php echo (isset($_POST['role']) && $_POST['role'] == 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                    <option value="dosen" <?php echo (isset($_POST['role']) && $_POST['role'] == 'dosen') ? 'selected' : ''; ?>>Dosen / Pengajar</option>
                </select>
            </div>

            <button type="submit" name="register" class="btn" style="width:100%; margin-top:10px;">Daftar Sekarang</button>
        </form>

        <p>Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
    </div>
</div>

<footer>
    &copy; <?php echo date('Y'); ?> CommunityHub. All rights reserved.
</footer>

</body>
</html>
