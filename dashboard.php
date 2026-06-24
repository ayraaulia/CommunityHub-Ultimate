<?php
// dashboard.php
session_start();
require_once 'includes/db.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = "Dashboard";
include 'includes/header.php';



$user_id = $_SESSION['user_id'];

// Ambil parameter pencarian mata kuliah
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Ambil semua mata kuliah beserta nama dosen yang ditugaskan dan statistik thread
// Perbaikan bug ONLY_FULL_GROUP_BY dengan mengelompokkan semua kolom non-agregat
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
";

$bind_types  = '';
$bind_params = [];

if (!empty($search)) {
    $courses_query .= " WHERE (c.name LIKE ? OR c.code LIKE ? OR c.description LIKE ?) ";
    $search_param   = '%' . $search . '%';
    $bind_types    .= 'sss';
    $bind_params[]  = $search_param;
    $bind_params[]  = $search_param;
    $bind_params[]  = $search_param;
}

$courses_query .= " GROUP BY c.id, c.name, c.code, c.description, u.nama ORDER BY c.code ASC ";

$stmt_courses = mysqli_prepare($conn, $courses_query);
if (!empty($bind_params)) {
    mysqli_stmt_bind_param($stmt_courses, $bind_types, ...$bind_params);
}
mysqli_stmt_execute($stmt_courses);
$courses_result = mysqli_stmt_get_result($stmt_courses);

// Simpan ke array agar stmt bisa ditutup sebelum output HTML
$courses_data = [];
while ($row = mysqli_fetch_assoc($courses_result)) {
    $courses_data[] = $row;
}
mysqli_stmt_close($stmt_courses);
?>

<div class="container">
    <div class="dashboard-grid">
        <!-- Konten Utama (Daftar Mata Kuliah) -->
        <div>
            <div class="section-title">
                <h2>Kategori Mata Kuliah</h2>
            </div>

            <!-- Search Bar Mata Kuliah -->
            <form method="GET" action="dashboard.php" class="search-filter-box" style="margin-bottom: 20px;">
                <div class="search-form">
                    <input type="text" name="search"
                           placeholder="Cari mata kuliah berdasarkan nama, kode, atau deskripsi..."
                           value="<?php echo htmlspecialchars($search); ?>"
                           style="flex:1;">
                    <button type="submit" class="btn">🔍 Cari</button>
                    <?php if (!empty($search)) { ?>
                        <a href="dashboard.php" class="btn btn-secondary">Reset</a>
                    <?php } ?>
                </div>
            </form>

            <?php if (!empty($search)) { ?>
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;">
                    Menampilkan <strong><?php echo count($courses_data); ?></strong> hasil untuk kata kunci: "<strong><?php echo htmlspecialchars($search); ?></strong>"
                </p>
            <?php } ?>

            <?php if (count($courses_data) > 0) { ?>
                <div class="course-list">
                    <?php foreach ($courses_data as $row) { 
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
            <?php } elseif (!empty($search)) { ?>
                <div class="empty-state">
                    <p style="font-size:28px; margin-bottom:10px;">🔍</p>
                    <strong>Tidak ada mata kuliah ditemukan untuk "<?php echo htmlspecialchars($search); ?>"</strong>
                    <p style="margin-top:8px;"><a href="dashboard.php">Lihat semua mata kuliah</a></p>
                </div>
            <?php } else { ?>
                <div class="empty-state">
                    Belum ada mata kuliah / kategori diskusi yang terdaftar. Hubungi Administrator untuk menambahkannya.
                </div>
            <?php } ?>
        </div>

        <!-- Widget Sidebar -->
        <div>
            <div class="profile-widget">
                <?php 
                $pic_path = 'uploads/profile_pics/' . $current_user['foto_profil'];
                if (!empty($current_user['foto_profil']) && file_exists(__DIR__ . '/' . $pic_path)) { ?>
                    <img src="<?php echo htmlspecialchars($pic_path); ?>" alt="Foto Profil" class="profile-avatar-img">
                <?php } else { ?>
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($current_user['nama'], 0, 1)); ?>
                    </div>
                <?php } ?>
                <h3><?php echo htmlspecialchars($current_user['nama']); ?></h3>
                <p class="role" style="margin-top: 5px;"><span class="role-badge <?php echo htmlspecialchars($current_user['role']); ?>"><?php echo htmlspecialchars($current_user['role']); ?></span></p>
                
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

<?php include 'includes/footer.php'; ?>
