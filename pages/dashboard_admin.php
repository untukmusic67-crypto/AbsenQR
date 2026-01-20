<?php
// pages/dashboard_admin.php
require_once '../config.php';
requireAuthPage(); // Menggunakan fungsi redirect halaman
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .token-box {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 10px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.85rem;
            word-break: break-all;
            margin-top: 10px;
            color: #374151;
        }
        .copy-btn {
            margin-top: 5px;
            font-size: 0.8rem;
            color: var(--primary);
            background: none;
            border: none;
            cursor: pointer;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h3>Hi, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></h3>
            <br>
            <a href="profile.php" class="btn btn-outline" style="margin-bottom:3px;">Ubah Password</a>
            <a href="../api/admin_logout.php" class="btn btn-outline" style="margin-bottom:3px;">Logout</a>
            
        </div>

        <!-- WIDGET STATISTIK -->
        <div style="display:flex; gap:10px; margin-bottom:20px;">
            <div style="flex:1; background:white; border:1px solid #ddd; border-radius:8px; padding:15px; text-align:center;">
                <div style="font-size:0.8rem; color:#666; margin-bottom:5px;">Hadir Hari Ini</div>
                <div id="stat-hadir" style="font-size:1.5rem; font-weight:bold; color:var(--primary);">-</div>
            </div>
            <div style="flex:1; background:white; border:1px solid #ddd; border-radius:8px; padding:15px; text-align:center;">
                <div style="font-size:0.8rem; color:#666; margin-bottom:5px;">QR Aktif</div>
                <div id="stat-qr" style="font-size:1.5rem; font-weight:bold; color:var(--success);">-</div>
            </div>
        </div>

        <!-- Area Generate QR -->
        <div class="card">
            <h4>Buat Sesi Absen</h4>
            <p style="font-size:0.85rem; color:#666;">
                QR Code digenerate via API Publik (CDN).<br>
                QR hanya berlasi selama 2 menit.
            </p>
            
            <div class="qr-display" id="qrResult">
                <span style="color:#999; font-style:italic;">QR Code akan muncul di sini...</span>
            </div>

            <button id="btnGenerate" class="btn">Generate QR Baru</button>
        </div>

        <div class="card">
            <a href="audit.php" class="btn btn-outline">Lihat Audit Kehadiran</a>
        </div>
    </div>

    <script>
        // 1. Load Statistik
        async function loadStats() {
            try {
                const req = await fetch('../api/dashboard_stats.php');
                const res = await req.json();
                if (res.status === 'success') {
                    document.getElementById('stat-hadir').innerText = res.data.total_hadir_hari_ini;
                    document.getElementById('stat-qr').innerText = res.data.qr_code_aktif;
                }
            } catch (err) { console.error(err); }
        }
        loadStats();

        // 2. Logic Generate QR
        document.getElementById('btnGenerate').addEventListener('click', async () => {
            const btn = document.getElementById('btnGenerate');
            const qrDiv = document.getElementById('qrResult');
            btn.disabled = true;
            btn.innerText = "Memproses...";
            qrDiv.innerHTML = '<span style="color:#666;">Sedang membuat QR...</span>';

            const formData = new FormData();
            formData.append('expiry_seconds', 120);

            try {
                const req = await fetch('../api/admin_generate_qr.php', { method: 'POST', body: formData });
                const res = await req.json();

                if (res.status === 'success') {
                    const timestamp = new Date(res.data.expires_at).toLocaleTimeString();
                    qrDiv.innerHTML = `
                        <img src="${res.data.qr_url}" alt="QR Code" style="border:1px solid #ddd; margin-bottom:10px;">
                        <p style="margin:5px 0; font-size:0.8rem; color:#666;">Valid sampai: ${timestamp}</p>
                        <div style="margin-top:15px; border-top:1px solid #eee; padding-top:10px;">
                            <label style="font-size:0.8rem; font-weight:bold;">Token String (Manual):</label>
                            <div class="token-box" id="tokenText">${res.data.raw_token}</div>
                            <button type="button" class="copy-btn" onclick="copyToken()">📋 Salin Token</button>
                        </div>
                    `;
                    loadStats();
                } else {
                    qrDiv.innerHTML = `<span style="color:red;">Error: ${res.message}</span>`;
                }
            } catch (err) {
                qrDiv.innerHTML = `<span style="color:red;">Gagal menghubungi server.</span>`;
            } finally {
                btn.disabled = false;
                btn.innerText = "Generate QR Baru";
            }
        });

        // 3. Fitur Copy Token
        function copyToken() {
            const tokenText = document.getElementById('tokenText').innerText;
            navigator.clipboard.writeText(tokenText).then(() => {
                alert("Token berhasil disalin!");
            }).catch(err => {
                alert("Gagal menyalin otomatis.");
            });
        }
    </script>
</body>
</html>