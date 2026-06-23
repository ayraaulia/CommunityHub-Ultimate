<?php
// register.php
session_start();
require_once 'includes/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error   = '';
$success = '';

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $nama     = trim($_POST['nama']);
    $jurusan  = trim($_POST['jurusan']);
    $role     = isset($_POST['role']) ? $_POST['role'] : '';
    // Angkatan hanya untuk mahasiswa; dosen disimpan sebagai 0
    $angkatan = ($role === 'mahasiswa') ? intval($_POST['angkatan']) : 0;

    if (empty($username) || empty($password) || empty($nama) || empty($jurusan)) {
        $error = "Semua field wajib diisi!";
    } elseif (!in_array($role, ['mahasiswa', 'dosen'])) {
        $error = "Role tidak valid!";
    } elseif ($role === 'mahasiswa' && empty($_POST['angkatan'])) {
        $error = "Tahun angkatan wajib diisi untuk mahasiswa!";
    } elseif ($role === 'mahasiswa' && ($angkatan < 1950 || $angkatan > date('Y') + 2)) {
        $error = "Tahun angkatan tidak valid!";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "Username sudah digunakan!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $ins = mysqli_prepare($conn, "INSERT INTO users (username, password, nama, jurusan, angkatan, role) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($ins, "ssssis", $username, $hashed_password, $nama, $jurusan, $angkatan, $role);
            if (mysqli_stmt_execute($ins)) {
                $success = "Registrasi berhasil! Silakan masuk.";
            } else {
                $error = "Terjadi kesalahan saat pendaftaran. Silakan coba lagi.";
            }
            mysqli_stmt_close($ins);
        }
        mysqli_stmt_close($stmt);
    }
}

// Nilai POST-back untuk mempertahankan input
$post_role     = isset($_POST['role'])     ? $_POST['role']     : 'mahasiswa';
$post_username = isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '';
$post_nama     = isset($_POST['nama'])     ? htmlspecialchars($_POST['nama'])     : '';
$post_jurusan  = isset($_POST['jurusan'])  ? htmlspecialchars($_POST['jurusan'])  : '';
$post_angkatan = isset($_POST['angkatan']) ? htmlspecialchars($_POST['angkatan']) : '';

$page_title = "Daftar Akun";
include 'includes/header.php';
?>

<div class="container" style="display:flex; justify-content:center; align-items:center; min-height:80vh; margin-top:0; margin-bottom:0;">
    <div class="form-container" style="margin:20px 0;">
        <h2>Daftar Akun</h2>

        <?php if (!empty($error)) { ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <?php if (!empty($success)) { ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php } ?>

        <form method="POST" id="register-form">
            <!-- 1. Username -->
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Masukkan username" required
                       value="<?php echo $post_username; ?>">
            </div>

            <!-- 2. Password -->
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Masukkan password" required>
            </div>

            <!-- 3. Nama Lengkap -->
            <div class="form-group">
                <label for="nama">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" placeholder="Nama lengkap Anda" required
                       value="<?php echo $post_nama; ?>">
            </div>

            <!-- 4. Jurusan -->
            <div class="form-group">
                <label for="jurusan">Jurusan / Departemen</label>
                <input type="text" id="jurusan" name="jurusan" placeholder="Contoh: Teknik Informatika" required
                       value="<?php echo $post_jurusan; ?>">
            </div>

            <!-- 5. Mendaftar Sebagai (ROLE) -->
            <div class="form-group">
                <label for="role">Mendaftar Sebagai</label>
                <select id="role" name="role" required>
                    <option value="mahasiswa" <?php echo $post_role === 'mahasiswa' ? 'selected' : ''; ?>>Mahasiswa</option>
                    <option value="dosen"     <?php echo $post_role === 'dosen'     ? 'selected' : ''; ?>>Dosen / Pengajar</option>
                </select>
            </div>

            <!-- 6. Angkatan — HANYA muncul jika pilih Mahasiswa -->
            <div class="form-group" id="angkatan-group"
                 style="<?php echo $post_role === 'dosen' ? 'display:none;' : ''; ?>">
                <label for="angkatan">Angkatan (Tahun Masuk)</label>
                <input type="number" id="angkatan" name="angkatan"
                       placeholder="Contoh: 2023"
                       value="<?php echo $post_angkatan; ?>">
                <small style="font-size:11px; color:var(--text-muted); margin-top:4px; display:block;">
                    Tahun pertama kali Anda kuliah di kampus ini.
                </small>
            </div>

            <button type="submit" name="register" class="btn" style="width:100%; margin-top:10px;">
                Daftar Sekarang
            </button>
        </form>

        <p>Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
    </div>
</div>

<script>
// Script langsung jalan — elemen form sudah ada di atas script ini
(function () {
    var roleSelect    = document.getElementById('role');
    var angkatanGroup = document.getElementById('angkatan-group');
    var angkatanInput = document.getElementById('angkatan');

    if (!roleSelect || !angkatanGroup || !angkatanInput) return;

    function updateAngkatan() {
        if (roleSelect.value === 'dosen') {
            angkatanGroup.style.display = 'none';
            angkatanInput.removeAttribute('required');
            angkatanInput.value = '';
        } else {
            angkatanGroup.style.display = 'block';
            angkatanInput.setAttribute('required', 'required');
        }
    }

    roleSelect.addEventListener('change', updateAngkatan);
    updateAngkatan(); // jalankan langsung sekarang
})();
</script>

<?php include 'includes/footer.php'; ?>
