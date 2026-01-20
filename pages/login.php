<?php
// pages/login.php
require_once '../config.php';

// UX: Jika sudah login, jangan tampilkan form login, tendang ke dashboard
// Menggunakan absolute path (/pages/...) agar konsisten dengan API response
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: pages/dashboard_admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="container">
        <h2 style="text-align: center;">Login Admin</h2>
        
        <div id="message-area"></div>

        <form id="loginForm">
            <div class="form-group">
                <label>Username</label>
                <input type="text" id="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" id="password" required>
            </div>
            <button type="submit" class="btn">Masuk</button>
            </br>
            <a href="../pages/attendance.php" class="btn btn-outline" style="margin-bottom:3px;">Absen</a>
        </form>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const msgArea = document.getElementById('message-area');
            const btn = document.querySelector('button[type="submit"]');
            
            // Tampilkan loading
            msgArea.innerHTML = '<div class="alert">Memproses login...</div>';
            btn.disabled = true; // Mencegah double click
            btn.innerText = "Loading...";

            const formData = new FormData();
            formData.append('username', document.getElementById('username').value);
            formData.append('password', document.getElementById('password').value);

            try {
                // Panggil API Login
                const res = await fetch('../api/admin_login.php', {
                    method: 'POST',
                    body: formData
                });
                
                const json = await res.json();

                if(json.status === 'success') {
                    // Redirect ke URL yang diberikan server (Absolute Path)
                    // json.redirect harus berisi '/pages/dashboard_admin.php'
                    window.location.href = json.redirect;
                } else {
                    // Tampilkan pesan error dari server
                    msgArea.innerHTML = `<div class="alert alert-error">${json.message}</div>`;
                    btn.disabled = false;
                    btn.innerText = "Masuk";
                }
            } catch (err) {
                console.error(err);
                msgArea.innerHTML = '<div class="alert alert-error">Terjadi kesalahan koneksi ke server.</div>';
                btn.disabled = false;
                btn.innerText = "Masuk";
            }
        });
    </script>
</body>
</html>