<?php
// login.php
include 'db.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Username dan password wajib diisi!";
    } else {
        // Query user details
        $stmt = mysqli_prepare($conn, "SELECT id, username, password, nama, role, jurusan, angkatan FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            // Verify password using bcrypt hash
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['nama'] = $row['nama'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['jurusan'] = $row['jurusan'];
                $_SESSION['angkatan'] = $row['angkatan'];

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Username atau password salah!";
            }
        } else {
            // Check if md5 is used for legacy support in database (if schema is old, but setup_db handles seeding, so this is just in case, but bcrypt is primary now)
            $error = "Username atau password salah!";
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
    <title>Masuk - CommunityHub</title>
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

<div class="container" style="display:flex; justify-content:center; align-items:center; min-height:75vh; margin-top:0; margin-bottom:0;">
    <div class="form-container" style="margin:20px 0;">
        <h2>Login</h2>

        <?php if (!empty($error)) { ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
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

            <button type="submit" name="login" class="btn" style="width:100%; margin-top:10px;">Masuk</button>
        </form>

        <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
    </div>
</div>

<footer>
    &copy; <?php echo date('Y'); ?> CommunityHub. All rights reserved.
</footer>

</body>
</html>
