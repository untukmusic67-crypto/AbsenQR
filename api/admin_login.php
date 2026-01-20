<?php
// api/admin_login.php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        jsonResponse(['status' => 'error', 'message' => 'Field tidak boleh kosong'], 400);
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // 1. Set Session
        session_regenerate_id(true); 
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        
        // 2. Kembalikan perintah refresh/root
        // Menggunakan '/' (slash tunggal) adalah cara paling aman untuk kembali ke root domain
        jsonResponse(['status' => 'success', 'redirect' => '/']);
    } else {
        jsonResponse(['status' => 'error', 'message' => 'Username atau password salah'], 401);
    }
} else {
    jsonResponse(['status' => 'error', 'message' => 'Method not allowed'], 405);
}
?>