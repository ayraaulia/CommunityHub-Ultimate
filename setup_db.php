<?php
// setup_db.php
// Script to set up database and seed initial data.

$host = "localhost";
$username = "root";
$password = "";
$dbname = "communityhub";

// 1. Connect to MySQL without specifying database
$conn = mysqli_connect($host, $username, $password);
if (!$conn) {
    die("<div style='color:red; font-family:sans-serif;'>Connection failed: " . mysqli_connect_error() . "</div>");
}

// 2. Drop and Create database to ensure clean engine state (fixes orphaned InnoDB tables)
mysqli_query($conn, "DROP DATABASE IF EXISTS `$dbname`");
$db_query = "CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
if (!mysqli_query($conn, $db_query)) {
    die("<div style='color:red; font-family:sans-serif;'>Error creating database: " . mysqli_error($conn) . "</div>");
}

// 3. Connect to the specific database
mysqli_select_db($conn, $dbname);

// 4. Read and execute schema.sql
$schema_file = __DIR__ . '/schema.sql';
if (!file_exists($schema_file)) {
    die("<div style='color:red; font-family:sans-serif;'>schema.sql file not found!</div>");
}

$schema_sql = file_get_contents($schema_file);

// Execute multi query
if (mysqli_multi_query($conn, $schema_sql)) {
    // Flush results of multi_query to free connection
    do {
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    } while (mysqli_next_result($conn));
} else {
    die("<div style='color:red; font-family:sans-serif;'>Error executing schema: " . mysqli_error($conn) . "</div>");
}

// 5. Seed initial data
// Clear existing data to allow clean re-runs
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
mysqli_query($conn, "TRUNCATE TABLE upvotes");
mysqli_query($conn, "TRUNCATE TABLE comments");
mysqli_query($conn, "TRUNCATE TABLE threads");
mysqli_query($conn, "TRUNCATE TABLE courses");
mysqli_query($conn, "TRUNCATE TABLE users");
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

// Inserts users (admin, dosen, mahasiswa)
$users = [
    [
        'username' => 'admin',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'nama' => 'Super Administrator',
        'jurusan' => 'Teknik Informatika',
        'angkatan' => 2020,
        'role' => 'admin'
    ],
    [
        'username' => 'pakbudi',
        'password' => password_hash('dosen123', PASSWORD_DEFAULT),
        'nama' => 'Dr. Budi Santoso',
        'jurusan' => 'Teknik Informatika',
        'angkatan' => 2005,
        'role' => 'dosen'
    ],
    [
        'username' => 'ibuani',
        'password' => password_hash('dosen123', PASSWORD_DEFAULT),
        'nama' => 'Ani Wijaya, M.T.',
        'jurusan' => 'Sistem Informasi',
        'angkatan' => 2012,
        'role' => 'dosen'
    ],
    [
        'username' => 'mahasiswa',
        'password' => password_hash('mhs123', PASSWORD_DEFAULT),
        'nama' => 'Ahmad Fauzi',
        'jurusan' => 'Teknik Informatika',
        'angkatan' => 2023,
        'role' => 'mahasiswa'
    ],
    [
        'username' => 'citra',
        'password' => password_hash('mhs123', PASSWORD_DEFAULT),
        'nama' => 'Citra Lestari',
        'jurusan' => 'Sistem Informasi',
        'angkatan' => 2022,
        'role' => 'mahasiswa'
    ],
    [
        'username' => 'dodi',
        'password' => password_hash('mhs123', PASSWORD_DEFAULT),
        'nama' => 'Dodi Hermawan',
        'jurusan' => 'Teknik Komputer',
        'angkatan' => 2023,
        'role' => 'mahasiswa'
    ]
];

$user_ids = [];
foreach ($users as $u) {
    $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, nama, jurusan, angkatan, role) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssssis", $u['username'], $u['password'], $u['nama'], $u['jurusan'], $u['angkatan'], $u['role']);
    mysqli_stmt_execute($stmt);
    $user_ids[$u['username']] = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
}

// Inserts courses
$courses = [
    [
        'name' => 'Pemrograman Web',
        'code' => 'IF-301',
        'description' => 'Mata kuliah yang mempelajari perancangan dan pengembangan aplikasi berbasis web, mencakup HTML, CSS, JavaScript, PHP, dan manajemen database.',
        'dosen_id' => $user_ids['pakbudi']
    ],
    [
        'name' => 'Basis Data',
        'code' => 'IF-302',
        'description' => 'Mata kuliah dasar tentang pemodelan data, SQL, normalisasi, dan perancangan database relasional.',
        'dosen_id' => $user_ids['ibuani']
    ],
    [
        'name' => 'Kecerdasan Buatan',
        'code' => 'IF-401',
        'description' => 'Pengenalan konsep AI, machine learning, neural networks, search algorithms, dan representasi pengetahuan.',
        'dosen_id' => $user_ids['pakbudi']
    ],
    [
        'name' => 'Jaringan Komputer',
        'code' => 'IF-304',
        'description' => 'Mempelajari arsitektur jaringan komputer, protokol TCP/IP, routing, subnetting, dan keamanan jaringan dasar.',
        'dosen_id' => null // No assigned dosen initially
    ]
];

$course_ids = [];
foreach ($courses as $c) {
    $stmt = mysqli_prepare($conn, "INSERT INTO courses (name, code, description, dosen_id) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssi", $c['name'], $c['code'], $c['description'], $c['dosen_id']);
    mysqli_stmt_execute($stmt);
    $course_ids[$c['name']] = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
}

// Inserts threads
$threads = [
    [
        'course_id' => $course_ids['Pemrograman Web'],
        'user_id' => $user_ids['mahasiswa'],
        'title' => 'Bagaimana cara mengatasi error CORS pada API PHP?',
        'content' => 'Halo semuanya, saya sedang mencoba memanggil API PHP yang saya buat di localhost:8000 dari frontend React di localhost:3000, tapi selalu kena block CORS. Adakah header khusus yang harus saya tambahkan di script PHP saya? Terima kasih!',
        'is_pinned' => 1,
        'is_solved' => 1
    ],
    [
        'course_id' => $course_ids['Pemrograman Web'],
        'user_id' => $user_ids['citra'],
        'title' => 'Rekomendasi framework CSS untuk tugas besar?',
        'content' => 'Apakah lebih baik pakai Vanilla CSS saja atau diperbolehkan menggunakan framework seperti Tailwind/Bootstrap untuk pengerjaan tugas besar? Mohon masukannya, terutama dari pak @pakbudi.',
        'is_pinned' => 0,
        'is_solved' => 0
    ],
    [
        'course_id' => $course_ids['Basis Data'],
        'user_id' => $user_ids['dodi'],
        'title' => 'Pertanyaan mengenai perbedaan INNER JOIN dan LEFT JOIN',
        'content' => 'Saya masih sedikit bingung dengan konsep JOIN. Kapan situasi terbaik kita menggunakan LEFT JOIN dibanding INNER JOIN? Contoh kasusnya seperti apa ya?',
        'is_pinned' => 0,
        'is_solved' => 0
    ],
    [
        'course_id' => $course_ids['Kecerdasan Buatan'],
        'user_id' => $user_ids['mahasiswa'],
        'title' => 'Materi UTS Kecerdasan Buatan bab apa saja?',
        'content' => 'Permisi pak @pakbudi, mau menanyakan untuk kisi-kisi UTS minggu depan, apakah mencakup sampai algoritma A* (A-star) atau hanya sampai BFS/DFS saja? Terima kasih pak.',
        'is_pinned' => 1,
        'is_solved' => 0
    ]
];

$thread_ids = [];
foreach ($threads as $t) {
    $stmt = mysqli_prepare($conn, "INSERT INTO threads (course_id, user_id, title, content, is_pinned, is_solved) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iissii", $t['course_id'], $t['user_id'], $t['title'], $t['content'], $t['is_pinned'], $t['is_solved']);
    mysqli_stmt_execute($stmt);
    $thread_ids[] = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
}

// Inserts comments
$comments = [
    [
        'thread_id' => $thread_ids[0], // CORS error (solved)
        'user_id' => $user_ids['pakbudi'],
        'content' => "Untuk menyelesaikan masalah CORS di PHP, kamu perlu menambahkan header CORS di awal file PHP kamu sebelum mengeluarkan output apapun.\n\nContoh code:\n```php\nheader('Access-Control-Allow-Origin: *');\nheader('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');\nheader('Access-Control-Allow-Headers: Content-Type, Authorization');\n```\nJika menggunakan request ber-credential (cookie/session), origin `*` harus diganti dengan domain spesifik seperti `http://localhost:3000` dan tambahkan `Access-Control-Allow-Credentials: true`."
    ],
    [
        'thread_id' => $thread_ids[0], // CORS error
        'user_id' => $user_ids['citra'],
        'content' => "Wah terima kasih pak Budi! Kemarin saya juga kena error yang sama dan solusi dari bapak langsung berhasil saat saya coba di local."
    ],
    [
        'thread_id' => $thread_ids[1], // CSS Framework
        'user_id' => $user_ids['pakbudi'],
        'content' => "Untuk tugas besar Pemrograman Web, dibebaskan menggunakan framework CSS. Namun nilai plus akan diberikan bagi kelompok yang memahami dasar layouting grid/flexbox sendiri. Pastikan responsivitasnya berjalan baik di mobile."
    ],
    [
        'thread_id' => $thread_ids[2], // JOIN
        'user_id' => $user_ids['ibuani'],
        'content' => "INNER JOIN hanya menampilkan baris yang memiliki kecocokan di kedua tabel. Sedangkan LEFT JOIN akan menampilkan semua baris dari tabel kiri, meskipun tabel kanan tidak memiliki kecocokan (kolom tabel kanan akan bernilai NULL).\n\nContoh: Jika ingin menampilkan daftar Mahasiswa beserta Mata Kuliah yang diambil (di mana ada mahasiswa yang belum mengambil matkul), gunakan LEFT JOIN agar mahasiswa tersebut tetap tampil di daftar."
    ]
];

$comment_ids = [];
foreach ($comments as $com) {
    $stmt = mysqli_prepare($conn, "INSERT INTO comments (thread_id, user_id, content) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iis", $com['thread_id'], $com['user_id'], $com['content']);
    mysqli_stmt_execute($stmt);
    $comment_ids[] = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
}

// Inserts upvotes
$upvotes = [
    [
        'comment_id' => $comment_ids[0], // Pak Budi's CORS comment
        'user_id' => $user_ids['mahasiswa']
    ],
    [
        'comment_id' => $comment_ids[0],
        'user_id' => $user_ids['citra']
    ],
    [
        'comment_id' => $comment_ids[2], // Pak Budi's CSS comment
        'user_id' => $user_ids['mahasiswa']
    ],
    [
        'comment_id' => $comment_ids[3], // Ibu Ani's JOIN comment
        'user_id' => $user_ids['dodi']
    ]
];

foreach ($upvotes as $up) {
    $stmt = mysqli_prepare($conn, "INSERT INTO upvotes (comment_id, user_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "ii", $up['comment_id'], $up['user_id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - CommunityHub</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0f172a;
            color: #f8fafc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: #1e293b;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            text-align: center;
            max-width: 500px;
            width: 90%;
            border: 1px solid #334155;
        }
        h1 {
            color: #38bdf8;
            margin-top: 0;
            font-size: 28px;
        }
        p {
            color: #94a3b8;
            line-height: 1.6;
        }
        .success-badge {
            background: #065f46;
            color: #34d399;
            padding: 8px 16px;
            border-radius: 9999px;
            display: inline-block;
            font-weight: bold;
            margin: 15px 0;
            font-size: 14px;
            border: 1px solid #059669;
        }
        .credentials {
            background: #0f172a;
            padding: 15px;
            border-radius: 8px;
            text-align: left;
            margin: 20px 0;
            font-size: 14px;
            border: 1px solid #1e293b;
        }
        .credentials h3 {
            margin-top: 0;
            color: #cbd5e1;
            font-size: 15px;
        }
        .credentials ul {
            padding-left: 20px;
            margin: 5px 0;
            color: #94a3b8;
        }
        .btn {
            background: linear-gradient(135deg, #38bdf8, #0284c7);
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: bold;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(56, 189, 248, 0.3);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(56, 189, 248, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>CommunityHub Database Setup</h1>
        <div class="success-badge">Database Berhasil Diinisialisasi!</div>
        <p>Database <code>communityhub</code> telah berhasil dibuat dan diisi dengan data demo. Tabel users, courses, threads, comments, dan upvotes siap digunakan.</p>
        
        <div class="credentials">
            <h3>Akun Demo yang Tersedia:</h3>
            <ul>
                <li><strong>Admin:</strong> admin / admin123</li>
                <li><strong>Dosen:</strong> pakbudi / dosen123</li>
                <li><strong>Mahasiswa:</strong> mahasiswa / mhs123</li>
            </ul>
        </div>
        
        <a href="login.php" class="btn">Menuju ke Login</a>
    </div>
</body>
</html>
