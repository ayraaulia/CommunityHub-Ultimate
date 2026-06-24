# CommunityHub — Platform Komunitas & Forum Diskusi Akademik

> Forum diskusi akademik berbasis web yang menghubungkan mahasiswa dan dosen dalam satu platform terintegrasi, terorganisir per mata kuliah.

---

## 📋 Deskripsi Website

**CommunityHub** adalah platform forum diskusi akademik yang dirancang untuk memfasilitasi komunikasi dan pertukaran ilmu antara mahasiswa dan dosen di lingkungan kampus. Diskusi dikelompokkan berdasarkan kategori mata kuliah sehingga pencarian referensi belajar menjadi terstruktur dan mudah ditemukan.

### Fitur Utama

- 📚 **Forum per Mata Kuliah** — Setiap mata kuliah memiliki ruang diskusi sendiri yang dikelola oleh dosen pengampu sebagai moderator.
- 🔒 **Sistem Role Bertingkat** — Tiga level akses: **Mahasiswa**, **Dosen/Moderator**, dan **Admin**, masing-masing dengan hak yang berbeda.
- 💬 **Thread & Komentar** — Mahasiswa dapat membuka thread pertanyaan, dan siapa pun dapat memberikan tanggapan/jawaban.
- ▲ **Upvote Komentar** — Pengguna dapat memberikan upvote pada komentar yang membantu, sehingga jawaban terbaik muncul di atas.
- ✅ **Status Terjawab** — Pembuat thread dapat menandai diskusi sebagai *[Terjawab]* ketika solusi telah ditemukan.
- 📌 **Pin Thread** — Dosen dan admin dapat menyematkan thread penting agar mudah ditemukan.
- 🌙 **Dark Mode** — Mendukung tampilan gelap dengan deteksi preferensi sistem otomatis.
- 👤 **Profil & Foto Pengguna** — Setiap pengguna dapat mengelola profil dan mengunggah foto profil.
- 🛡️ **Panel Admin** — Admin dapat mengelola seluruh mata kuliah, pengguna, dan menetapkan dosen moderator.

---

## 👥 Tim & Peran

| Nama Anggota | Role | Tanggung Jawab |
|---|---|---|
| Ulul Asmi | Frontend & Backend Developer | Mendesain tampilan website, mengembangkan fitur sistem, dan mengelola integrasi frontend-backend |
| Ayra Aulia Saputri Hidayat | Frontend & Backend Developer | Membuat tampilan antarmuka pengguna serta mengembangkan logika sistem dan fitur backend |

---

## 🗺️ Menu / Sitemap

```
CommunityHub
│
├── PUBLIK (Tanpa Login)
│   ├── Landing Page (index.php)
│   │   └── Statistik komunitas (total anggota, thread, komentar)
│   ├── Login (login.php)
│   └── Registrasi Akun (register.php)
│
├── MAHASISWA
│   │
│   └── Dashboard (dashboard.php)
│       ├── Daftar Semua Mata Kuliah
│       ├── Profil & Sidebar Widget
│       │
│       └── Halaman Mata Kuliah (course.php?id=X)
│           ├── Daftar Thread Diskusi
│           ├── Filter (Terbaru / Terpopuler / Belum Dijawab)
│           ├── Pencarian Thread
│           ├── Buat Thread Baru
│           │
│           └── Detail Thread (thread.php?id=X)
│               ├── Isi Pertanyaan Thread
│               ├── Tandai Thread Sebagai Terjawab (pemilik)
│               ├── Hapus Thread (pemilik)
│               ├── Daftar Komentar (urut upvote)
│               ├── Upvote Komentar
│               ├── Hapus Komentar (pemilik komentar)
│               └── Tulis Komentar / Jawaban Baru
│
├── DOSEN / MODERATOR
│   │  (Semua fitur Mahasiswa, ditambah:)
│   └── Halaman Mata Kuliah yang Diampu
│       ├── Pin / Unpin Thread
│       ├── Hapus Thread Siapapun
│       └── Hapus Komentar Siapapun
│
└── ADMIN
    │
    └── Panel Admin (admin.php)
        ├── Dashboard Statistik Sistem
        │   ├── Total Pengguna
        │   ├── Total Mata Kuliah
        │   ├── Total Thread Diskusi
        │   └── Total Komentar
        ├── Kelola Mata Kuliah
        │   ├── Tambah Mata Kuliah Baru
        │   ├── Edit Mata Kuliah (kode, nama, deskripsi, dosen)
        │   └── Hapus Mata Kuliah
        ├── Kelola Pengguna
        │   ├── Ubah Role Pengguna (mahasiswa/dosen/admin)
        │   └── Hapus Akun Pengguna
        └── Logout (logout.php)
```

---

## 📁 Struktur Direktori & File

```
CommunityHub-Ultimate/
├── index.php              # Landing page publik (beranda + statistik komunitas)
├── login.php              # Halaman login pengguna
├── register.php           # Halaman registrasi akun baru (mahasiswa / dosen)
├── dashboard.php          # Dashboard utama (daftar mata kuliah + widget profil)
├── course.php             # Halaman forum per mata kuliah (thread + buat thread)
├── thread.php             # Detail thread diskusi (komentar, upvote, aksi)
├── profile.php            # Halaman edit profil & unggah foto pengguna
├── admin.php              # Panel manajemen admin (kelola mata kuliah & user)
├── logout.php             # Logika logout dan destroy session
├── setup_db.php           # Script setup & inisialisasi database otomatis
├── schema.sql             # File SQL schema database (backup/referensi)
├── README.md              # Dokumentasi proyek ini
│
├── includes/              # Komponen PHP yang digunakan bersama (shared)
│   ├── db.php             # Konfigurasi koneksi database + auto-migration
│   ├── header.php         # Template header global (HTML head, navbar, dark mode)
│   └── footer.php         # Template footer global (penutup body & HTML)
│
├── assets/                # Aset statis frontend
│   ├── css/
│   │   └── style.css      # Stylesheet utama (design system, dark mode, komponen)
│   └── js/
│       └── main.js        # JavaScript utama (dark mode, hamburger, animasi, dsb.)
│
└── uploads/               # Penyimpanan file yang diunggah pengguna
    └── profile_pics/      # Folder penyimpanan foto profil pengguna
```

---

## 🛠️ Tech Stack

| Kategori | Teknologi |
|---|---|
| **Backend** | PHP 8+ (Native / Prosedural) |
| **Database** | MySQL (via MySQLi dengan Prepared Statements) |
| **Frontend** | HTML5, Vanilla CSS, Vanilla JavaScript |
| **Tipografi** | Google Fonts — Inter |
| **Web Server** | Apache (XAMPP) |
| **Keamanan** | `password_hash()` / `password_verify()` (bcrypt), `htmlspecialchars()`, Prepared Statements (SQL Injection prevention), finfo MIME validation |
| **Fitur Browser** | LocalStorage (dark mode), IntersectionObserver (animasi), FileReader API (preview foto) |

---

## 🗄️ Spesifikasi Database

### Tabel `users`

| Field | Type | Keterangan |
|---|---|---|
| `id` | INT (PK, AUTO_INCREMENT) | ID unik pengguna |
| `username` | VARCHAR(50) UNIQUE | Username untuk login |
| `password` | VARCHAR(255) | Password terenkripsi (bcrypt) |
| `nama` | VARCHAR(100) | Nama lengkap pengguna |
| `jurusan` | VARCHAR(100) | Jurusan / departemen |
| `angkatan` | INT | Tahun masuk (mahasiswa) atau tahun mulai mengajar (dosen) |
| `role` | ENUM('admin','dosen','mahasiswa') | Level akses pengguna |
| `foto_profil` | VARCHAR(255) | Nama file foto profil (nullable) |
| `created_at` | TIMESTAMP | Tanggal akun dibuat |

### Tabel `courses`

| Field | Type | Keterangan |
|---|---|---|
| `id` | INT (PK, AUTO_INCREMENT) | ID unik mata kuliah |
| `name` | VARCHAR(150) | Nama mata kuliah |
| `code` | VARCHAR(50) UNIQUE | Kode mata kuliah |
| `description` | TEXT | Deskripsi singkat mata kuliah |
| `dosen_id` | INT (FK → users.id) | Dosen pengampu / moderator (nullable) |
| `created_at` | TIMESTAMP | Tanggal mata kuliah dibuat |

### Tabel `threads`

| Field | Type | Keterangan |
|---|---|---|
| `id` | INT (PK, AUTO_INCREMENT) | ID unik thread |
| `course_id` | INT (FK → courses.id) | Mata kuliah tempat thread berada |
| `user_id` | INT (FK → users.id) | Pengguna pembuat thread |
| `title` | VARCHAR(255) | Judul thread / pertanyaan |
| `content` | TEXT | Isi detail thread |
| `is_pinned` | TINYINT(1) | Status disematkan (0=tidak, 1=ya) |
| `is_solved` | TINYINT(1) | Status terjawab (0=belum, 1=sudah) |
| `created_at` | TIMESTAMP | Tanggal thread dibuat |

### Tabel `comments`

| Field | Type | Keterangan |
|---|---|---|
| `id` | INT (PK, AUTO_INCREMENT) | ID unik komentar |
| `thread_id` | INT (FK → threads.id) | Thread yang dikomentari |
| `user_id` | INT (FK → users.id) | Pengguna pembuat komentar |
| `content` | TEXT | Isi komentar / jawaban |
| `created_at` | TIMESTAMP | Tanggal komentar dibuat |

### Tabel `upvotes`

| Field | Type | Keterangan |
|---|---|---|
| `id` | INT (PK, AUTO_INCREMENT) | ID unik upvote |
| `comment_id` | INT (FK → comments.id) | Komentar yang di-upvote |
| `user_id` | INT (FK → users.id) | Pengguna yang memberi upvote |
| `created_at` | TIMESTAMP | Tanggal upvote diberikan |
| *(UNIQUE)* | (`comment_id`, `user_id`) | Satu pengguna hanya bisa upvote 1x per komentar |

---

## 🔗 Relasi Database

```
users ────────────────────────────────────────────────────┐
  │ id                                                     │
  │                                                        │
  ├──< courses (dosen_id → users.id)  [SET NULL on delete] │
  │                                                        │
  ├──< threads (user_id → users.id)   [CASCADE on delete]  │
  │       │ id                                             │
  │       │                                               │
  │       └──< comments (thread_id → threads.id) [CASCADE] │
  │               │ id                                     │
  │               │                                        │
  │               └──< upvotes (comment_id → comments.id) [CASCADE]
  │                                                        │
  └──< upvotes (user_id → users.id)   [CASCADE on delete] ─┘
```

**Ringkasan relasi:**
- Satu **user** bisa menjadi dosen pengampu banyak **courses**
- Satu **user** bisa membuat banyak **threads** dan banyak **comments**
- Satu **course** bisa memiliki banyak **threads**
- Satu **thread** bisa memiliki banyak **comments**
- Satu **comment** bisa menerima banyak **upvotes** (maks. 1 per user)
- Penghapusan user akan **cascade** menghapus semua thread, komentar, dan upvote miliknya

---

## 🐛 Bug Log

| # | File | Deskripsi Bug | Status |
|---|---|---|---|
| 1 | `profile.php` | Validasi angkatan tidak membedakan role dosen & mahasiswa — dosen dengan angkatan `0` selalu gagal validasi karena `0 < 1950` | 🔴 Open |
| 2 | `admin.php` | `dosen_id = null` saat INSERT course bisa menyebabkan error bind_param pada beberapa versi PHP karena null di-bind ke tipe integer | 🔴 Open |
| 3 | `dashboard.php` | Sidebar menampilkan **"Angkatan: 0"** untuk akun dosen karena angkatan dosen disimpan sebagai `0` | 🔴 Open |
| 4 | `profile.php` | Field angkatan tidak memiliki batas bawah/atas yang tepat untuk role dosen pada saat edit profil | 🔴 Open |

---

## 🤖 AI Usage Statement

Sesuai dengan aturan pengerjaan proyek, berikut adalah pernyataan penggunaan AI selama pengembangan aplikasi **CommunityHub**:

**1) Tool yang digunakan:**
Google Gemini / Claude (Antigravity)

**2) Untuk apa AI digunakan:**
Membantu proses desain antarmuka, mengatasi bug yang ditemukan selama pengembangan, memberikan ide dan saran fitur, serta membantu implementasi fitur-fitur baru.

**3) 2–3 prompt utama yang digunakan:**

> *"Buatkan web forum diskusi akademik menggunakan PHP & MySQL dengan fitur thread, komentar, upvote, dark mode, dan panel admin. Desain harus modern dan responsif."*

> *"masi ada bug ga web itu?"* — untuk melakukan audit bug menyeluruh pada semua file PHP.

> *"tolong tambahin fitur hamburger menu buat mobile dan photo preview waktu ganti foto profil"*

**4) Bagian output AI yang dipakai:**
Struktur keseluruhan aplikasi (arsitektur file, skema database, sistem role), implementasi dark mode dengan LocalStorage, fitur upvote toggle, validasi MIME server-side untuk upload foto profil, dan hamburger menu responsif untuk mobile.

**5) Bagian yang diubah + alasan:**
Beberapa logika validasi disesuaikan dengan kebutuhan spesifik (misalnya membedakan validasi angkatan antara role mahasiswa dan dosen). Desain visual dikustomisasi menggunakan palet warna dan tipografi yang disesuaikan dengan identitas platform akademik. Query database dioptimalkan untuk kompatibilitas MySQL `ONLY_FULL_GROUP_BY` mode.
