<?php
// db.php
// Database connection file

$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "communityhub"; 

$conn = @mysqli_connect($host, $db_user, $db_pass);

if (!$conn) {
    die("<div style='font-family:sans-serif; text-align:center; padding: 50px; background:#0f172a; color:#f8fafc; height:100vh; margin:0;'>
        <h2 style='color:#ef4444;'>Koneksi Database Gagal</h2>
        <p>Pastikan MySQL XAMPP Anda sudah aktif.</p>
    </div>");
}

$db_selected = @mysqli_select_db($conn, $db_name);
$tables_exist = false;
if ($db_selected) {
    $table_check = @mysqli_query($conn, "SHOW TABLES LIKE 'users'");
    if ($table_check && mysqli_num_rows($table_check) > 0) {
        $tables_exist = true;
    }
}

if (!$db_selected || !$tables_exist) {
    die("<div style='font-family:sans-serif; text-align:center; padding: 50px; background:#0f172a; color:#f8fafc; height:100vh; margin:0;'>
        <h2 style='color:#eab308;'>Database Belum Diinisialisasi</h2>
        <p>Database <strong>$db_name</strong> belum dibuat atau tabel belum lengkap.</p>
        <p style='margin-top:20px;'><a href='setup_db.php' style='background:#38bdf8; color:#0f172a; padding:10px 20px; border-radius:5px; text-decoration:none; font-weight:bold;'>Jalankan Setup Database Sekarang</a></p>
    </div>");
}
?>
