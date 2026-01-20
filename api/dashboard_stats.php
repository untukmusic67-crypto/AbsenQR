<?php
// api/dashboard_stats.php
require_once '../config.php';
requireAuth();

header('Content-Type: application/json');

try {
    // 1. Hitung Total Absensi Hari Ini
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM absensi WHERE DATE(submit_time) = CURDATE()");
    $stmt->execute();
    $resultTotal = $stmt->fetch();
    $totalHadir = $resultTotal['total'];

    // 2. (Opsional) Hitung Jumlah QR yang aktif saat ini
    // Ini berguna untuk mengetahui berapa QR yang sedang 'menggantung' dan belum dipakai
    $stmtActive = $pdo->prepare("SELECT COUNT(*) as active FROM qr_tokens WHERE status = 'active' AND expires_at > NOW()");
    $stmtActive->execute();
    $resultActive = $stmtActive->fetch();
    $qrActive = $resultActive['active'];

    jsonResponse([
        'status' => 'success',
        'data' => [
            'total_hadir_hari_ini' => $totalHadir,
            'qr_code_aktif' => $qrActive
        ]
    ]);

} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
?>