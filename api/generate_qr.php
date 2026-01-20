<?php
// api/generate_qr.php
session_start();
require_once '../config.php';
require_once '../includes/QrToken.php';

// 1. Cek Auth Admin (Sederhana)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
}

// 2. Ambil Input
 $expiry = $_POST['expiry_seconds'] ?? QR_EXPIRY_DEFAULT;
 $adminId = $_SESSION['admin_id']; // Asumsi ID admin disimpan di session

// 3. Generate Token
 $rawToken = QrToken::generate($adminId, $expiry);

// 4. Simpan State ke Database (Pencegahan Replay)
try {
    // Decode token untuk ambil nonce
    $tokenData = QrToken::verify($rawToken); 
    $nonce = $tokenData['nonce'];
    $expiresAt = date('Y-m-d H:i:s', $tokenData['timestamp'] + $tokenData['expiry']);

    $stmt = $pdo->prepare("INSERT INTO qr_tokens (nonce, admin_id, created_at, expires_at, status) VALUES (?, ?, NOW(), ?, 'active')");
    $stmt->execute([$nonce, $adminId, $expiresAt]);

} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => 'Database error'], 500);
}

// 5. Generate Gambar QR (Menggunakan library PHP QR Code - Diasumsikan ada)
// Jika library phpqrcode ada di libs/phpqrcode.php
include "../libs/phpqrcode.php"; 
 $tempFilePath = UPLOAD_DIR . 'qr_' . $nonce . '.png';
QRcode::png($rawToken, $tempFilePath); 

 $publicUrl = '/uploads/qr_' . $nonce . '.png'; // Path public

jsonResponse([
    'status' => 'success',
    'data' => [
        'qr_url' => $publicUrl,
        'raw_token' => $rawToken, // Hanya untuk testing/dev, di production comment baris ini
        'expires_at' => $expiresAt
    ]
]);
?>