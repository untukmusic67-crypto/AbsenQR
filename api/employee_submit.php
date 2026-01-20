<?php
// api/employee_submit.php
require_once '../config.php';
require_once '../includes/QrToken.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'Method not allowed'], 405);
}

// 1. Ambil Input
 $nik = trim($_POST['nik'] ?? '');
 $nama = trim($_POST['nama'] ?? '');
 $qrToken = $_POST['qr_token'] ?? '';

if (empty($nama) || empty($qrToken)) {
    jsonResponse(['status' => 'error', 'message' => 'Nama dan QR Token wajib diisi.'], 400);
}

// 2. Verifikasi Signature Token
 $tokenData = QrToken::verify($qrToken);
if (!$tokenData) {
    jsonResponse(['status' => 'error', 'message' => 'QR Token Tidak Valid / Palsu.'], 400);
}

// Cek Drift Waktu (Server vs Token) - Max toleransi 5 menit
if (abs(time() - $tokenData['timestamp']) > 300) {
    jsonResponse(['status' => 'error', 'message' => 'QR Token Kadaluarsa (Waktu tidak sinkron).'], 400);
}

// 3. Validasi File Foto
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['status' => 'error', 'message' => 'Foto wajib diupload.'], 400);
}

 $allowedExt = ['jpg', 'jpeg', 'png'];
 $fileExt = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

if (!in_array($fileExt, $allowedExt)) {
    jsonResponse(['status' => 'error', 'message' => 'Format foto harus JPG/PNG.'], 400);
}

// Siapkan path penyimpanan
 $photoName = 'absen_' . $tokenData['nonce'] . '_' . time() . '.' . $fileExt;
 $destination = UPLOAD_DIR . $photoName;

if (!move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
    jsonResponse(['status' => 'error', 'message' => 'Gagal menyimpan file foto.'], 500);
}

// 4. Transaksi Database (Pencegahan Race Condition)
try {
    $pdo->beginTransaction();

    // Cek Token di DB
    $stmt = $pdo->prepare("SELECT id, status, expires_at FROM qr_tokens WHERE nonce = ? FOR UPDATE");
    $stmt->execute([$tokenData['nonce']]);
    $qrRecord = $stmt->fetch();

    if (!$qrRecord) {
        throw new Exception("QR Token tidak ditemukan di database.");
    }

    if ($qrRecord['status'] !== 'active') {
        throw new Exception("QR Code ini sudah pernah dipakai.");
    }

    if (strtotime($qrRecord['expires_at']) < time()) {
        throw new Exception("Masa berlaku QR Code sudah habis.");
    }

    // SUKSES: Update Status QR jadi USED
    $stmtUpdate = $pdo->prepare("UPDATE qr_tokens SET status = 'used', used_by_nik = ?, used_at = NOW() WHERE id = ?");
    $stmtUpdate->execute([$nik, $qrRecord['id']]);

    // Simpan Log Absensi
    $stmtInsert = $pdo->prepare("INSERT INTO absensi (nik, nama, photo_path, qr_nonce, submit_time) VALUES (?, ?, ?, ?, NOW())");
    $stmtInsert->execute([$nik, $nama, $photoName, $tokenData['nonce']]);

    $pdo->commit();

    jsonResponse([
        'status' => 'success',
        'message' => 'Absensi Berhasil!',
        'data' => ['waktu' => date('H:i:s')]
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    // Hapus foto yang sudah terupload jika transaksi gagal
    if (file_exists($destination)) unlink($destination);
    
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 403);
}
?>