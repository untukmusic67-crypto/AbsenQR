<?php
// api/audit_list.php
require_once '../config.php';
requireAuth(); // Wajib Login

// Parameter Filter
 $dateFilter = $_GET['date'] ?? date('Y-m-d');
 $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

header('Content-Type: application/json');

try {
    // Query menggabungkan absensi dan info admin (opsional) atau hanya absensi
    // Kita ambil data absensi saja sesuai constraint simplified
    $stmt = $pdo->prepare("
        SELECT id, nik, nama, photo_path, submit_time 
        FROM absensi 
        WHERE DATE(submit_time) = ? 
        ORDER BY submit_time DESC 
        LIMIT ?
    ");
    $stmt->execute([$dateFilter, $limit]);
    $data = $stmt->fetchAll();

    jsonResponse([
        'status' => 'success',
        'data' => $data,
        'date' => $dateFilter
    ]);

} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
?>