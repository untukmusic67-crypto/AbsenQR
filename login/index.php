<?php
// index.php
require_once '../config.php';

// Logika Router Sederhana
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    // SUDAH LOGIN -> Masuk Dashboard
    header("Location: pages/dashboard_admin.php");
} else {
    // BELUM LOGIN -> Masuk Halaman Login Admin
    // Jika ingin user umum (pegawai) masuk ke form absen, ganti baris bawah dengan header("Location: pages/attendance.php");
    header("Location: ../pages/login.php");
}
exit;
?>