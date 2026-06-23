<?php
// profile.php
session_start();
require_once 'includes/db.php';

$page_title = "Profil Saya";


// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle Profile Update (text fields + photo)
if (isset($_POST['update_profile'])) {
    $nama = trim($_POST['nama']);
    $jurusan = trim($_POST['jurusan']);
    $angkatan = intval($_POST['angkatan']);

    if (empty($nama) || empty($jurusan) || empty($angkatan)) {
        $error = "Semua field wajib diisi!";
    } elseif ($angkatan < 1950 || $angkatan > date('Y') + 2) {
        $error = "Tahun angkatan tidak valid!";
    } else {
        // --- Handle Profile Picture Upload ---
        $new_foto_profil = null; // null means no change
        
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['foto_profil'];
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
            $max_size = 2 * 1024 * 1024; // 2MB
            $upload_dir = __DIR__ . '/uploads/profile_pics/';

            // Server-side validation
            if (!in_array($file['type'], $allowed_types)) {
                $error = "Format foto tidak valid. Hanya .jpg, .jpeg, dan .png yang diizinkan.";
            } elseif ($file['size'] > $max_size) {
                $error = "Ukuran foto terlalu besar. Maksimal 2MB.";
            } else {
                // Create upload directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // Generate a unique, sanitized filename
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $new_filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
                $destination = $upload_dir . $new_filename;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    // Delete the old profile picture if it exists
                    $old_stmt = mysqli_prepare($conn, "SELECT foto_profil FROM users WHERE id = ?");
                    mysqli_stmt_bind_param($old_stmt, "i", $user_id);
                    mysqli_stmt_execute($old_stmt);
                    $old_result = mysqli_stmt_get_result($old_stmt);
                    $old_data = mysqli_fetch_assoc($old_result);
                    mysqli_stmt_close($old_stmt);
                    
                    if (!empty($old_data['foto_profil'])) {
                        $old_file_path = $upload_dir . $old_data['foto_profil'];
                        if (file_exists($old_file_path)) {
                            @unlink($old_file_path);
                        }
                    }
                    $new_foto_profil = $new_filename;
                } else {
                    $error = "Gagal mengunggah foto. Silakan coba lagi.";
                }
            }
        }

        // Update database only if no error occurred during upload
        if (empty($error)) {
            if ($new_foto_profil !== null) {
                // Update nama, jurusan, angkatan, AND foto_profil
                $stmt = mysqli_prepare($conn, "UPDATE users SET nama = ?, jurusan = ?, angkatan = ?, foto_profil = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "sssii", $nama, $jurusan, $angkatan, $new_foto_profil, $user_id);
            } else {
                // Update only nama, jurusan, angkatan (no photo change)
                $stmt = mysqli_prepare($conn, "UPDATE users SET nama = ?, jurusan = ?, angkatan = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "ssii", $nama, $jurusan, $angkatan, $user_id);
            }

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
}

// Fetch fresh user data from DB
$stmt = mysqli_prepare($conn, "SELECT username, nama, role, jurusan, angkatan, foto_profil FROM users WHERE id = ?");
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

include 'includes/header.php';
?>

<div class="container">
    <div class="breadcrumbs">
        <a href="dashboard.php">Dashboard</a> &raquo; Profil Saya
    </div>

    <div class="dashboard-grid">
        <!-- Profile Edit Form -->
        <div>
            <div class="form-container" style="max-width:100%; margin:0; padding:30px;">
                <h2 style="text-align:left; margin-bottom:5px;">Edit Profil</h2>
                <p style="text-align:left; color:var(--text-muted); margin-bottom:25px; font-size:14px;">
                    Username Anda: <strong>@<?php echo htmlspecialchars($user_data['username']); ?></strong>
                </p>

                <?php if (!empty($error)) { ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php } ?>

                <?php if (!empty($success)) { ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php } ?>

                <form method="POST" enctype="multipart/form-data">
                    <!-- Profile Picture Upload Section -->
                    <div class="form-group">
                        <label>Foto Profil</label>
                        <div class="profile-pic-container">
                            <div class="profile-pic-preview-wrapper">
                                <?php
                                $pic_path = 'uploads/profile_pics/' . $user_data['foto_profil'];
                                $has_pic = !empty($user_data['foto_profil']) && file_exists(__DIR__ . '/' . $pic_path);
                                ?>
                                <?php if ($has_pic) { ?>
                                    <img src="<?php echo htmlspecialchars($pic_path); ?>" 
                                         alt="Foto Profil" 
                                         class="profile-pic-preview" 
                                         id="preview-image">
                                <?php } else { ?>
                                    <div class="profile-pic-preview-text" id="preview-placeholder">
                                        <?php echo strtoupper(substr($user_data['nama'], 0, 1)); ?>
                                    </div>
                                    <img src="" alt="Preview" class="profile-pic-preview" id="preview-image" style="display:none;">
                                <?php } ?>
                            </div>
                            
                            <div style="text-align:center;">
                                <label for="foto_profil" class="btn btn-secondary" style="cursor:pointer; display:inline-flex;">
                                    📷 Pilih Foto
                                </label>
                                <input type="file" id="foto_profil" name="foto_profil" 
                                       accept=".jpg,.jpeg,.png" style="display:none;">
                                <p style="font-size:12px; color:var(--text-muted); margin-top:8px;">
                                    Format: JPG, PNG &bull; Maks. 2MB
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="nama">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" 
                               value="<?php echo htmlspecialchars($user_data['nama']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="jurusan">Jurusan</label>
                        <input type="text" id="jurusan" name="jurusan" 
                               value="<?php echo htmlspecialchars($user_data['jurusan']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="angkatan">Angkatan (Tahun)</label>
                        <input type="number" id="angkatan" name="angkatan" 
                               value="<?php echo htmlspecialchars($user_data['angkatan']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" value="<?php echo ucfirst(htmlspecialchars($user_data['role'])); ?>" 
                               disabled style="cursor:not-allowed; opacity:0.6;">
                    </div>

                    <button type="submit" name="update_profile" class="btn" style="width:100%;">
                        Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>

        <!-- Profile Stats Sidebar -->
        <div>
            <div class="profile-widget">
                <?php if ($has_pic) { ?>
                    <img src="<?php echo htmlspecialchars($pic_path); ?>" 
                         alt="Foto Profil" class="profile-avatar-img">
                <?php } else { ?>
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user_data['nama'], 0, 1)); ?>
                    </div>
                <?php } ?>
                <h3><?php echo htmlspecialchars($user_data['nama']); ?></h3>
                <p class="role"><span class="role-badge <?php echo htmlspecialchars($user_data['role']); ?>"><?php echo htmlspecialchars($user_data['role']); ?></span></p>
                
                <div class="profile-details" style="text-align:center;">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:15px;">
                        <div style="background:var(--bg-main); padding:15px; border-radius:8px; border:1px solid var(--border);">
                            <div style="font-size:24px; font-weight:800; color:var(--primary);"><?php echo $threads_count; ?></div>
                            <div style="font-size:11px; color:var(--text-muted); font-weight:600;">Thread Dibuat</div>
                        </div>
                        <div style="background:var(--bg-main); padding:15px; border-radius:8px; border:1px solid var(--border);">
                            <div style="font-size:24px; font-weight:800; color:var(--success);"><?php echo $comments_count; ?></div>
                            <div style="font-size:11px; color:var(--text-muted); font-weight:600;">Komentar</div>
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

<?php include 'includes/footer.php'; ?>
