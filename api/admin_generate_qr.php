<?php
// api/admin_generate_qr.php
require_once '../config.php';
require_once '../includes/QrToken.php';

requireAuth();

// Tidak perlu include library phpqrcode lagi
// Tidak perlu cek folder uploads lagi

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expiry = $_POST['expiry_seconds'] ?? QR_EXPIRY_DEFAULT;
    $adminId = $_SESSION['admin_id'];

    // 1. Buat Token
    $rawToken = QrToken::generate($adminId, $expiry);
    
    // Verifikasi ulang untuk mendapatkan data nonce dari token
    $tokenData = QrToken::verify($rawToken); 
    $nonce = $tokenData['nonce'];
    
    // Hitung waktu expires untuk DB
    $expiresAt = date('Y-m-d H:i:s', $tokenData['timestamp'] + $tokenData['expiry']);

    try {
        // 2. Simpan State ke DB
        $stmt = $pdo->prepare("INSERT INTO qr_tokens (nonce, admin_id, expires_at, status) VALUES (?, ?, ?, 'active')");
        $stmt->execute([$nonce, $adminId, $expiresAt]);

        // 3. Generate URL Gambar QR via API Publik (CDN)
        // Menggunakan API dari qrserver.com (gratis & stabil)
        $qrPublicUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($rawToken);

        jsonResponse([
            'status' => 'success',
            'data' => [
                'qr_url' => $qrPublicUrl, // Ini URL gambar eksternal
                'expires_at' => $expiresAt,
                'raw_token' => $rawToken,
                'token_short' => substr($rawToken, 0, 30) . "..."
            ]
        ]);

    } catch (Exception $e) {
        jsonResponse(['status' => 'error', 'message' => 'Gagal generate QR: ' . $e->getMessage()], 500);
    }
}
?>