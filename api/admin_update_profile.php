<?php
// api/admin_update_profile.php
require_once '../config.php';
requireAuth(); // Pastikan hanya admin yang login

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $usernameCurrent = $_SESSION['admin_username']; // Ambil username session
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    // 1. Validasi Input
    if (empty($newPassword) || empty($confirmPassword)) {
        jsonResponse(['status' => 'error', 'message' => 'Password baru wajib diisi.'], 400);
    }

    if ($newPassword !== $confirmPassword) {
        jsonResponse(['status' => 'error', 'message' => 'Konfirmasi password tidak cocok.'], 400);
    }

    if (strlen($newPassword) < 6) {
        jsonResponse(['status' => 'error', 'message' => 'Password minimal 6 karakter.'], 400);
    }

    try {
        // 2. Update Password di Database
        // Hash password baru
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
        $stmt->execute([$newHash, $usernameCurrent]);

        // 3. (Opsional) Regenerate Session ID untuk keamanan ekstra setelah ganti pass
        session_regenerate_id(true);

        jsonResponse([
            'status' => 'success', 
            'message' => 'Password berhasil diubah. Silakan login ulang.'
        ]);

    } catch (Exception $e) {
        jsonResponse(['status' => 'error', 'message' => 'Gagal mengupdate password.'], 500);
    }

} else {
    jsonResponse(['status' => 'error', 'message' => 'Method not allowed'], 405);
}
?>