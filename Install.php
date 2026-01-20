<?php
// install.php
// Installer Otomatis Sistem Absensi QR
// Mendukung Root Directory dan Subfolder

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Cek apakah sudah terinstall
if (file_exists('config.php') && file_exists('.installed')) {
    die("<h3>⚠️ Sistem Sudah Terinstall</h3>
         <p>Sistem sudah berjalan. Jika ingin install ulang (reset data), 
         hapus file <b>config.php</b> dan <b>.installed</b> terlebih dahulu.</p>");
}

 $step = isset($_POST['step']) ? $_POST['step'] : 1;
 $error = "";

// --- LOGIKA PROSES INSTALL ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // STEP 2: Validasi Koneksi Database
    if ($step == 2) {
        $host = trim($_POST['db_host']);
        $name = trim($_POST['db_name']);
        $user = trim($_POST['db_user']);
        $pass = trim($_POST['db_pass']);
        
        try {
            // Coba connect tanpa select DB dulu
            $dsn = "mysql:host=$host;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Cek apakah database ada, jika tidak buat baru
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name`");
            $pdo->exec("USE `$name`");
            
            // Jika sukses, lanjut ke step 3 via JS
            echo "<script>document.getElementById('installForm').step.value='3';</script>";
            
        } catch (PDOException $e) {
            $error = "Koneksi Database Gagal: " . $e->getMessage();
        }
    }

    // STEP 3: Proses Install (Tabel, Admin, Config)
    if ($step == 3) {
        $host = trim($_POST['db_host']);
        $name = trim($_POST['db_name']);
        $user = trim($_POST['db_user']);
        $pass = trim($_POST['db_pass']);
        
        $adminUser = trim($_POST['admin_user']);
        $adminPass = trim($_POST['admin_pass']);

        if (empty($adminUser) || empty($adminPass)) {
            $error = "Username dan Password Admin wajib diisi.";
        } else {
            try {
                $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // --- 1. Buat Tabel Users ---
                $sqlUsers = "CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                $pdo->exec($sqlUsers);

                // --- 2. Buat Tabel QR Tokens ---
                $sqlQr = "CREATE TABLE IF NOT EXISTS qr_tokens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nonce CHAR(64) NOT NULL UNIQUE,
                    admin_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    expires_at TIMESTAMP NOT NULL,
                    status ENUM('active', 'used') DEFAULT 'active',
                    used_by_nik VARCHAR(100) NULL,
                    used_at TIMESTAMP NULL,
                    INDEX idx_nonce (nonce),
                    INDEX idx_expires (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                $pdo->exec($sqlQr);

                // --- 3. Buat Tabel Absensi ---
                $sqlAbsensi = "CREATE TABLE IF NOT EXISTS absensi (
                    id BIGINT AUTO_INCREMENT PRIMARY KEY,
                    nik VARCHAR(100) NULL,
                    nama VARCHAR(100) NOT NULL,
                    photo_path VARCHAR(255) NOT NULL,
                    qr_nonce CHAR(64) NOT NULL,
                    submit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    ip_address VARCHAR(45) NULL,
                    INDEX idx_qr_nonce (qr_nonce),
                    INDEX idx_submit_time (submit_time)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                $pdo->exec($sqlAbsensi);

                // --- 4. Buat Tabel Audit Logs ---
                $sqlAudit = "CREATE TABLE IF NOT EXISTS audit_logs (
                    id BIGINT AUTO_INCREMENT PRIMARY KEY,
                    actor VARCHAR(50) NOT NULL,
                    action VARCHAR(50) NOT NULL,
                    details TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                $pdo->exec($sqlAudit);

                // --- 5. Insert Admin User ---
                $hash = password_hash($adminPass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
                $stmt->execute([$adminUser, $hash]);

                // --- 6. Buat Folder Uploads & Security ---
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0755, true);
                }
                file_put_contents('uploads/.htaccess', "Options -Indexes\n<FilesMatch \"\.php$\">\nOrder Allow,Deny\nDeny from all\n</FilesMatch>\n<FilesMatch \"\.(jpg|jpeg|png|gif)$\">\nOrder Allow,Deny\nAllow from all\n</FilesMatch>");

                // --- 7. Generate File config.php ---
                $configContent = "<?php
// config.php (Generated by installer)
session_start();

// Security & Performance Headers
header(\"Access-Control-Allow-Origin: *\");
header(\"Access-Control-Allow-Methods: GET, POST, OPTIONS\");
header(\"Access-Control-Allow-Headers: Content-Type, Authorization\");
header(\"Link: <https://unpkg.com>; rel=preconnect\", false);
header(\"Link: <https://api.qrserver.com>; rel=preconnect\", false);

\$uri = \$_SERVER['REQUEST_URI'];
if (strpos(\$uri, '.css') !== false || strpos(\$uri, '.js') !== false || strpos(\$uri, '.png') !== false || strpos(\$uri, '.jpg') !== false) {
    header(\"Cache-Control: public, max-age=3600\"); 
} else {
    header(\"Cache-Control: no-store, no-cache, must-revalidate, max-age=0\"); 
}

// Database Settings
define('DB_HOST', '$host');
define('DB_NAME', '$name');
define('DB_USER', '$user');
define('DB_PASS', '$pass');

// Security Settings
define('SECRET_KEY', '" . bin2hex(random_bytes(32)) . "'); 
define('QR_EXPIRY_DEFAULT', 120);
define('UPLOAD_DIR', __DIR__ . '/uploads/');

try {
    \$pdo = new PDO(\"mysql:host=\".DB_HOST.\";dbname=\".DB_NAME.\";charset=utf8mb4\", DB_USER, DB_PASS);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    \$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException \$e) {
    die(\"Koneksi Database Gagal.\");
}

function jsonResponse(\$data, \$code = 200) {
    http_response_code(\$code);
    header('Content-Type: application/json');
    echo json_encode(\$data);
    exit;
}

function requireAuth() {
    if (!isset(\$_SESSION['admin_logged_in']) || \$_SESSION['admin_logged_in'] !== true) {
        if (strpos(\$_SERVER['REQUEST_URI'], '/api/') !== false) {
            jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        header(\"Location: index.php\");
        exit;
    }
}

function requireAuthPage() {
    if (!isset(\$_SESSION['admin_logged_in']) || \$_SESSION['admin_logged_in'] !== true) {
        header(\"Location: index.php\");
        exit;
    }
}
?>";
                
                if (file_put_contents('config.php', $configContent)) {
                    // 8. Buat File Lock .installed
                    file_put_contents('.installed', date('Y-m-d H:i:s'));
                    
                    $success = true;
                } else {
                    throw new Exception("Gagal menulis file config.php. Pastikan folder ini writable (CHMOD 777 atau 755).");
                }

            } catch (Exception $e) {
                $error = "Terjadi kesalahan saat instalasi: " . $e->getMessage();
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalasi Sistem Absensi</title>
    <style>
        body { font-family: sans-serif; background: #f0f2f5; padding: 20px; }
        .installer-box { max-width: 500px; margin: 50px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 0.9em; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; }
        button:hover { background: #1d4ed8; }
        .error { background: #fee2e2; color: #991b1c; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 0.9em; }
        .success { background: #d1fae5; color: #065f46; padding: 20px; border-radius: 4px; text-align: center; }
        .step-indicator { margin-bottom: 15px; font-size: 0.9em; color: #666; border-bottom: 1px solid #eee; padding-bottom:10px; }
    </style>
</head>
<body>

<div class="installer-box">
    <?php if (isset($success) && $success): ?>
        <div class="success">
            <h3>🎉 Instalasi Berhasil!</h3>
            <p>Sistem siap digunakan.</p>
            <br>
            <a href="index.php"><button style="background: #10b981;">Masuk ke Sistem</button></a>
        </div>
        <?php unlink(__FILE__); ?>
    <?php else: ?>
        
        <h2>Setup Sistem Absensi</h2>
        
        <?php if ($error): ?>
            <div class="error"><strong>Error:</strong> <?php echo $error; ?></div>
        <?php endif; ?>

        <form id="installForm" method="POST">
            <input type="hidden" name="step" value="2">
            
            <div class="step-indicator">
                <strong>Step 1/2:</strong> Konfigurasi Database
            </div>

            <div class="form-group">
                <label>Database Host</label>
                <input type="text" name="db_host" value="localhost" required placeholder="sqlxxx.infinityfree.com atau localhost">
            </div>
            
            <div class="form-group">
                <label>Database Name</label>
                <input type="text" name="db_name" required placeholder="Nama Database Baru">
            </div>

            <div class="form-group">
                <label>Database User</label>
                <input type="text" name="db_user" required placeholder="Username MySQL">
            </div>

            <div class="form-group">
                <label>Database Password</label>
                <input type="password" name="db_pass" placeholder="Password MySQL">
            </div>

            <div class="step-indicator" style="margin-top:20px;">
                <strong>Step 2/2:</strong> Buat Akun Admin
            </div>

            <div class="form-group">
                <label>Username Admin</label>
                <input type="text" name="admin_user" required>
            </div>

            <div class="form-group">
                <label>Password Admin</label>
                <input type="password" name="admin_pass" required>
            </div>

            <button type="submit" onclick="this.form.step.value='3'">Mulai Instalasi</button>
        </form>

    <?php endif; ?>
</div>

</body>
</html>
