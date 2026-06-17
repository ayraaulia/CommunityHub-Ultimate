<?php
// index.php
session_start();
include 'db.php';

// Safe queries for statistics
$total_users = 0;
$total_courses = 0;
$total_threads = 0;
$total_comments = 0;

$u_res = mysqli_query($conn, "SELECT COUNT(*) AS count FROM users");
if ($u_res) { $total_users = mysqli_fetch_assoc($u_res)['count']; }

$c_res = mysqli_query($conn, "SELECT COUNT(*) AS count FROM courses");
if ($c_res) { $total_courses = mysqli_fetch_assoc($c_res)['count']; }

$t_res = mysqli_query($conn, "SELECT COUNT(*) AS count FROM threads");
if ($t_res) { $total_threads = mysqli_fetch_assoc($t_res)['count']; }

$com_res = mysqli_query($conn, "SELECT COUNT(*) AS count FROM comments");
if ($com_res) { $total_comments = mysqli_fetch_assoc($com_res)['count']; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CommunityHub - Forum Diskusi Akademik</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            padding: 24px;
            border-radius: var(--radius);
            text-align: center;
            box-shadow: var(--shadow);
        }
        .stat-num {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

<nav>
    <h1><a href="index.php">CommunityHub</a></h1>
    <div class="nav-links">
        <?php if (isset($_SESSION['user_id'])) { ?>
            <a href="dashboard.php" class="nav-item">Dashboard</a>
            <a href="profile.php" class="nav-item">Profil Saya</a>
            <div class="nav-user-badge">
                <span><?php echo htmlspecialchars($_SESSION['nama']); ?></span>
                <span class="role-badge <?php echo htmlspecialchars($_SESSION['role']); ?>"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
            </div>
            <a href="logout.php" class="btn btn-secondary btn-sm">Keluar</a>
        <?php } else { ?>
            <a href="login.php" class="nav-item">Masuk</a>
            <a href="register.php" class="nav-item">Daftar</a>
            <a href="register.php" class="btn btn-sm">Gabung Sekarang</a>
        <?php } ?>
    </div>
</nav>

<section class="hero">
    <h2>Platform Komunitas & Forum Diskusi Akademik</h2>
    <p>Tempat bertukar pikiran, berdiskusi mengenai materi perkuliahan, dan menyelesaikan pertanyaan sulit bersama dosen dan mahasiswa lainnya.</p>
    <?php if (isset($_SESSION['user_id'])) { ?>
        <a href="dashboard.php" class="btn" style="background: white; color: var(--primary); font-weight:700;">Masuk ke Dashboard Diskusi &raquo;</a>
    <?php } else { ?>
        <a href="register.php" class="btn" style="background: white; color: var(--primary); font-weight:700;">Buat Akun Sekarang &raquo;</a>
    <?php } ?>
</section>

<div class="container" style="margin-top: -30px; position: relative; z-index: 10;">
    <!-- Live Stats -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-num"><?php echo $total_users; ?></div>
            <div class="stat-label">Anggota Komunitas</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?php echo $total_courses; ?></div>
            <div class="stat-label">Kategori Mata Kuliah</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?php echo $total_threads; ?></div>
            <div class="stat-label">Thread Diskusi</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?php echo $total_comments; ?></div>
            <div class="stat-label">Komentar Solutif</div>
        </div>
    </div>

    <!-- Core Features Section -->
    <div class="section-title" style="margin-top: 50px; justify-content: center;">
        <h2 style="font-size: 28px;">Mengapa Menggunakan CommunityHub?</h2>
    </div>

    <div class="features" style="margin-top: 20px; padding: 0;">
        <div class="card">
            <h3>Disusun per Mata Kuliah</h3>
            <p>Diskusi dikelompokkan berdasarkan kategori mata kuliah sehingga pencarian referensi belajar menjadi sangat mudah dan terstruktur.</p>
        </div>

        <div class="card">
            <h3>Moderasi Dosen & Admin</h3>
            <p>Dosen pengampu bertindak sebagai moderator. Dosen dapat menyematkan (pin) thread penting dan merapikan diskusi di mata kuliah yang diampu.</p>
        </div>

        <div class="card">
            <h3>Upvote & Penjawab Terpercaya</h3>
            <p>Berikan apresiasi pada komentar yang membantu dengan fitur Upvote. Pembuat pertanyaan dapat menandai thread sebagai [Terjawab] saat solusi ditemukan.</p>
        </div>
    </div>
</div>

<footer>
    &copy; <?php echo date('Y'); ?> CommunityHub. All rights reserved.
</footer>

</body>
</html>
