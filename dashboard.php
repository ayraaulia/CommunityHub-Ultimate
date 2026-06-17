<?php
// dashboard.php
include 'db.php';
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all courses along with assigned dosen name and thread stats
$courses_query = "
    SELECT 
        c.id, 
        c.name, 
        c.code, 
        c.description, 
        u.nama AS dosen_name,
        COUNT(t.id) AS total_threads,
        SUM(CASE WHEN t.is_solved = 1 THEN 1 ELSE 0 END) AS solved_threads
    FROM courses c
    LEFT JOIN users u ON c.dosen_id = u.id
    LEFT JOIN threads t ON c.id = t.course_id
    GROUP BY c.id
    ORDER BY c.code ASC
";
$courses_result = mysqli_query($conn, $courses_query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CommunityHub</title>
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
    <div class="dashboard-grid">
        <!-- Main Content (Courses list) -->
        <div>
            <div class="section-title">
                <h2>Kategori Mata Kuliah</h2>
            </div>
            
            <?php if (mysqli_num_rows($courses_result) > 0) { ?>
                <div class="course-list">
                    <?php while ($row = mysqli_fetch_assoc($courses_result)) { 
                        $total = intval($row['total_threads']);
                        $solved = intval($row['solved_threads']);
                        $unsolved = $total - $solved;
                    ?>
                        <div class="course-card">
                            <div class="course-info">
                                <h3>
                                    <span class="course-code"><?php echo htmlspecialchars($row['code']); ?></span>
                                    <a href="course.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></a>
                                </h3>
                                <p class="desc"><?php echo htmlspecialchars($row['description']); ?></p>
                                
                                <div class="meta">
                                    <span>
                                        <strong>Dosen/Moderator:</strong> 
                                        <?php echo $row['dosen_name'] ? htmlspecialchars($row['dosen_name']) : '<em style="color:var(--text-muted);">Belum ditentukan</em>'; ?>
                                    </span>
                                    <span>
                                        <strong>Total Thread:</strong> <?php echo $total; ?>
                                    </span>
                                    <span>
                                        <strong>Belum Terjawab:</strong> <span style="color: <?php echo $unsolved > 0 ? 'var(--warning)' : 'var(--text-muted)'; ?>; font-weight: 600;"><?php echo $unsolved; ?></span>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <a href="course.php?id=<?php echo $row['id']; ?>" class="btn btn-secondary btn-sm" style="white-space:nowrap;">Lihat Diskusi &raquo;</a>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="empty-state">
                    Belum ada mata kuliah / kategori diskusi yang terdaftar. Hubungi Administrator untuk menambahkannya.
                </div>
            <?php } ?>
        </div>

        <!-- Sidebar Widget -->
        <div>
            <div class="profile-widget">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?>
                </div>
                <h3><?php echo htmlspecialchars($_SESSION['nama']); ?></h3>
                <p class="role" style="margin-top: 5px;"><span class="role-badge <?php echo htmlspecialchars($_SESSION['role']); ?>"><?php echo htmlspecialchars($_SESSION['role']); ?></span></p>
                
                <div class="profile-details">
                    <p style="margin-bottom: 8px;"><strong>Jurusan:</strong> <?php echo htmlspecialchars($_SESSION['jurusan']); ?></p>
                    <p style="margin-bottom: 8px;"><strong>Angkatan:</strong> <?php echo htmlspecialchars($_SESSION['angkatan']); ?></p>
                </div>
                
                <a href="profile.php" class="btn btn-secondary btn-sm" style="width: 100%; margin-top: 15px;">Edit Profil Saya</a>
            </div>
            
            <div class="card" style="margin-top: 20px; padding: 20px;">
                <h4 style="margin-bottom: 10px; color: var(--text-main); font-size: 15px; font-weight: 600;">Panduan Forum</h4>
                <ul style="padding-left: 18px; font-size: 12px; color: var(--text-muted); line-height: 1.6;">
                    <li>Pilih Mata Kuliah untuk melihat sub-topik diskusi.</li>
                    <li>Gunakan fitur Pencarian dan Filter di halaman mata kuliah.</li>
                    <li>Thread dapat ditandai sebagai <strong>[Terjawab]</strong> oleh pembuat thread tersebut.</li>
                    <li>Bantu sesama mahasiswa dengan memberikan <strong>Upvote</strong> pada komentar yang bermanfaat.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<footer>
    &copy; <?php echo date('Y'); ?> CommunityHub. All rights reserved.
</footer>

</body>
</html>
