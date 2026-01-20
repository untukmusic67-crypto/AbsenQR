<?php
// pages/profile.php
require_once '../config.php';
requireAuthPage(); // Cek login
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Password Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="dashboard_admin.php">&larr; Kembali</a>
            <h3>Ubah Password</h3>
        </div>

        <div class="card">
            <p style="font-size:0.9rem; color:#666; margin-bottom:15px;">
                Username Anda: <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong>
            </p>

            <form id="profileForm">
                <div class="form-group">
                    <label>Password Baru</label>
                    <input type="password" id="new_password" required minlength="6" placeholder="Minimal 6 karakter">
                </div>
                
                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <input type="password" id="confirm_password" required minlength="6" placeholder="Ulangi password baru">
                </div>

                <div id="message-area"></div>

                <button type="submit" class="btn">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('profileForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const msgArea = document.getElementById('message-area');
            const btn = document.querySelector('button[type="submit"]');

            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;

            // Validasi Sisi Client (UX Cepat)
            if (newPass !== confirmPass) {
                msgArea.innerHTML = '<div class="alert alert-error">Konfirmasi password tidak cocok!</div>';
                return;
            }

            // Tampilkan Loading
            msgArea.innerHTML = '<div class="alert">Memproses...</div>';
            btn.disabled = true;
            btn.innerText = "Menyimpan...";

            const formData = new FormData();
            formData.append('new_password', newPass);
            formData.append('confirm_password', confirmPass);

            try {
                const res = await fetch('../api/admin_update_profile.php', {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();

                if (json.status === 'success') {
                    msgArea.innerHTML = `<div class="alert alert-success">${json.message}</div>`;
                    
                    // Redirect ke Login setelah 2 detik
                    setTimeout(() => {
                        window.location.href = '/'; // Arahkan ke root biar index.php handle
                    }, 2000);
                } else {
                    msgArea.innerHTML = `<div class="alert alert-error">${json.message}</div>`;
                    btn.disabled = false;
                    btn.innerText = "Simpan Perubahan";
                }
            } catch (err) {
                console.error(err);
                msgArea.innerHTML = '<div class="alert alert-error">Gagal menghubungi server.</div>';
                btn.disabled = false;
                btn.innerText = "Simpan Perubahan";
            }
        });
    </script>
</body>
</html>