<?php
// api/submit_attendance.php
require_once '../config.php';
require_once '../includes/QrToken.php';

// 1. Validasi Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'Method not allowed'], 405);
}

// 2. Ambil Input
 $nik = trim($_POST['nik'] ?? ''); // Opsional
 $nama = trim($_POST['nama'] ?? '');
 $qrToken = $_POST['qr_token'] ?? '';

// Validasi dasar input
if (empty($nama) || empty($qrToken)) {
    jsonResponse(['status' => 'error', 'message' => 'Nama dan QR Token wajib diisi'], 400);
}

// 3. Verifikasi Token Kriptografi
 $tokenData = QrToken::verify($qrToken);
if (!$tokenData) {
    jsonResponse(['status' => 'error', 'message' => 'QR Token Tidak Valid (Signature Salah)'], 400);
}

// Cek Drift Waktu (Server Time vs Token Time) - Toleransi 5 menit
if (abs(time() - $tokenData['timestamp']) > 300) {
    jsonResponse(['status' => 'error', 'message' => 'QR Token Kadaluarsa (Waktu tidak sesuai)'], 400);
}

// 4. Upload Foto
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['status' => 'error', 'message' => 'Foto wajib diupload'], 400);
}

 $photoExt = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
 $photoName = 'absen_' . $tokenData['nonce'] . '_' . time() . '.' . $photoExt;
 $destination = UPLOAD_DIR . $photoName;

if (!move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
    jsonResponse(['status' => 'error', 'message' => 'Gagal menyimpan foto'], 500);
}

// 5. Database Transaction (Validasi State & Simpan)
try {
    $pdo->beginTransaction();

    // Cek status token di DB (Pencegahan Replay / Pakai 2 kali)
    $stmt = $pdo->prepare("SELECT id, status, expires_at FROM qr_tokens WHERE nonce = ? FOR UPDATE");
    $stmt->execute([$tokenData['nonce']]);
    $qrRecord = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$qrRecord) {
        throw new Exception("QR Token tidak dikenali.");
    }

    if ($qrRecord['status'] !== 'active') {
        throw new Exception("QR Token sudah dipakai.");
    }

    if (strtotime($qrRecord['expires_at']) < time()) {
        throw new Exception("QR Token sudah lewat masa berlaku.");
    }

    // Jika lolos, Update Status QR jadi USED
    $stmtUpdate = $pdo->prepare("UPDATE qr_tokens SET status = 'used', used_by_nik = ?, used_at = NOW() WHERE id = ?");
    $stmtUpdate->execute([$nik, $qrRecord['id']]);

    // Simpan Data Absensi
    $stmtInsert = $pdo->prepare("INSERT INTO absensi (nik, nama, photo_path, qr_nonce, submit_time) VALUES (?, ?, ?, ?, NOW())");
    $stmtInsert->execute([$nik, $nama, $photoName, $tokenData['nonce']]);

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    // Hapus foto yang sudah terupload jika transaksi gagal (agar tidak sampah)
    if (file_exists($destination)) unlink($destination);
    
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 403);
}

jsonResponse([
    'status' => 'success',
    'message' => 'Absensi Berhasil!',
    'data' => [
        'nama' => $nama,
        'waktu' => date('H:i:s')
    ]
]);
?>