File ini adalah dokumentasi standar proyek Open Source agar pengguna lain mengerti cara pakainya. 
📷 Sistem Absensi QR Code (Minimalis & Fraud-Proof)

Sistem absensi berbasis web yang ringan, aman, dan mudah digunakan. Dibuat dengan Native PHP 8.1 tanpa framework, cocok untuk shared hosting (seperti InfinityFree) maupun VPS.
✨ Fitur Utama

    Tanpa Biometrik Hardware: Menggunakan QR Code dinamis sebagai validasi utama.
    Anti-Fraud (Burn-After-Use): Satu QR hanya berlaku untuk satu orang dan satu kali pakai.
    Audit Visual: Pegawai mengambil foto selfie, Admin melakukan validasi manual.
    WebRTC Camera: Pengambilan foto langsung dari browser (tanpa upload file manual).
    Export to Excel: Download laporan absensi harian dalam format CSV.
    Mobile First: UI didesain responsif untuk HP.
    No Master Data Pegawai: Sistem bekerja tanpa perlu input data pegawai satu per satu (Input Nama/NIK saat absen).
    Keamanan: Validasi Token (HMAC SHA256), Proteksi SQL Injection (PDO), dan Login Admin.

🛠 Tech Stack

    Backend: PHP 8.1+ (Native/PDO)
    Database: MySQL / MariaDB
    Frontend: HTML5, CSS3, Vanilla JavaScript
    Libraries: 
        html5-qrcode (CDN) untuk scan QR.
        api.qrserver.com (CDN) untuk generate QR.

📋 Persyaratan Sistem

    Web Server (Apache/Nginx)
    PHP 8.1 atau lebih tinggi.
    MySQL / MariaDB.
    Extension PHP: pdo_mysql, gd.
    Koneksi Internet (Untuk memuat Library CDN QR).

🚀 Cara Instalasi

    Clone atau Download repository ini.
    Upload semua file ke folder hosting Anda (misal: htdocs/).
    Buka browser dan akses http://websiteanda.com/install.php.
    Ikuti wizard instalasi:
        Masukkan kredensial Database.
        Buat Username & Password Admin.
    Selesai! File install.php akan terhapus otomatis.

📖 Cara Penggunaan
Untuk Admin

    Login melalui halaman pages/login.php.
    Masuk ke Dashboard.
    Klik Generate QR Baru. QR akan muncul (atau salin Token String jika kamera error).
    Tampilkan QR Code di layar (TV/Proyektor) untuk pegawai.
    Masuk ke menu Audit untuk memverifikasi kehadiran pegawai.
    Klik Download Excel untuk rekapitulasi gaji.

Untuk Pegawai

    Buka http://websiteanda.com/pages/attendance.php.
    Isi Nama (Wajib) dan NIK (Opsional).
    Klik Aktifkan Kamera -> Ambil Foto Selfi.
    Scan QR Code yang ditampilkan Admin.
        Jika kamera error: Salin Token String dari dashboard admin dan tempel di kolom input.
    Klik Kirim Absen.
