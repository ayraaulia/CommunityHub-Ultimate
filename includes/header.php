<?php
// includes/header.php
// Global header template

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

// Fetch fresh user details if logged in (ensures avatar & name are always synchronized)
$current_user = null;
if (isset($_SESSION['user_id'])) {
    $header_stmt = mysqli_prepare($conn, "SELECT id, username, nama, role, foto_profil FROM users WHERE id = ?");
    mysqli_stmt_bind_param($header_stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($header_stmt);
    $current_user = mysqli_fetch_assoc(mysqli_stmt_get_result($header_stmt));
    mysqli_stmt_close($header_stmt);
    
    if (!$current_user) {
        // If user was deleted in the database, clear session and log out
        $_SESSION = array();
        session_destroy();
        header("Location: login.php");
        exit();
    }
}

$title_prefix = isset($page_title) ? $page_title . " - " : "";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title_prefix); ?>CommunityHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Dark Mode Initial Detection Script (Avoids Visual Flashing/Jittering) -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else if (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                // If no preference is saved, fallback to system preference
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body>

<nav>
    <h1><a href="index.php">CommunityHub</a></h1>
    <div class="nav-links">
        <!-- Hamburger button (visible on mobile, hidden on desktop) -->
        <button id="nav-toggle" class="nav-hamburger" aria-label="Toggle Navigation" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <?php if ($current_user) { ?>
            <a href="dashboard.php" class="nav-item">Dashboard</a>
            <?php if ($current_user['role'] === 'admin') { ?>
                <a href="admin.php" class="nav-item">Panel Admin</a>
            <?php } ?>
            <a href="profile.php" class="nav-item">Profil Saya</a>
            
            <div class="nav-user-badge">
                <?php 
                $pic_path = 'uploads/profile_pics/' . $current_user['foto_profil'];
                if (!empty($current_user['foto_profil']) && file_exists(__DIR__ . '/../' . $pic_path)) { ?>
                    <img src="<?php echo htmlspecialchars($pic_path); ?>" alt="Foto Profil" class="nav-avatar">
                <?php } else { ?>
                    <div class="nav-avatar nav-avatar-text">
                        <?php echo strtoupper(substr($current_user['nama'], 0, 1)); ?>
                    </div>
                <?php } ?>
                <span><?php echo htmlspecialchars($current_user['nama']); ?></span>
                <span class="role-badge <?php echo htmlspecialchars($current_user['role']); ?>"><?php echo htmlspecialchars($current_user['role']); ?></span>
            </div>
            <a href="logout.php" class="btn btn-secondary btn-sm">Keluar</a>
        <?php } else { ?>
            <a href="login.php" class="nav-item">Masuk</a>
            <a href="register.php" class="nav-item">Daftar</a>
            <a href="register.php" class="btn btn-sm">Gabung Sekarang</a>
        <?php } ?>
        
        <!-- Dark Mode Toggle Button -->
        <button id="theme-toggle" class="theme-toggle-btn" aria-label="Toggle Theme" title="Ubah Tema">
            <!-- Sun Icon (shows in dark mode) -->
            <svg class="sun-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m2.828-9.9a5 5 0 117.071 7.071l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707"></path>
            </svg>
            <!-- Moon Icon (shows in light mode) -->
            <svg class="moon-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            </svg>
        </button>
    </div>
</nav>
