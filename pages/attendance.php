<?php
// pages/attendance.php
require_once '../config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Absen Pegawai</title>
    <link rel="stylesheet" href="../assets/style.css">
    
    <!-- Library QR Scanner (CDN) -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    
    <style>
        /* Style Tambahan untuk Kamera WebRTC */
        .camera-container {
            position: relative;
            width: 100%;
            max-width: 320px;
            height: 240px; /* Aspect ratio 4:3 umum webcam */
            background-color: #000;
            margin: 0 auto;
            border-radius: 8px;
            overflow: hidden;
            display: none; /* Hidden by default */
        }
        
        #webcamVideo, #photoCanvas {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        #photoCanvas {
            display: none; /* Hidden sampai foto diambil */
        }

        .camera-controls {
            text-align: center;
            margin-top: 10px;
            display: none;
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            border-radius: 4px;
            margin: 0 5px;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-secondary { background: #6b7280; color: white; }

        /* Fallback input file */
        #fallback-upload { display: none; margin-top:10px; border: 2px dashed #ccc; padding:10px; text-align:center;}
    </style>
</head>
<body>
    <a href="../login/" class="btn btn-outline" style="margin-bottom:3px;">Login</a>
<div class="container">
    <h2 style="text-align: center;">Form Absensi</h2>
    
    <!-- Loading Overlay -->
    <div id="loading" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.9); z-index:999; text-align:center; padding-top:50%;">
        <h3>Memproses Data...</h3>
        <p>Mohon tunggu sebentar.</p>
    </div>

    <!-- Area Pesan -->
    <div id="message-area"></div>

    <form id="attendanceForm">
        <!-- Step 1: Identitas -->
        <div class="form-group">
            <label>NIK (Opsional)</label>
            <input type="text" id="nik" name="nik" placeholder="Contoh: 12345">
        </div>
        
        <div class="form-group">
            <label>Nama Lengkap (Wajib)</label>
            <input type="text" id="nama" name="nama" placeholder="Masukkan nama Anda" required>
        </div>

        <!-- Step 2: Foto (WebRTC) -->
        <div class="form-group">
            <label>Foto Selfi</label>
            
            <!-- Button Start Camera -->
            <button type="button" id="btnStartCamera" class="btn btn-outline" style="margin-bottom:10px;">
                📷 Aktifkan Kamera
            </button>

            <!-- Container Kamera WebRTC -->
            <div id="cameraWrapper" class="camera-container">
                <video id="webcamVideo" autoplay playsinline muted></video>
                <canvas id="photoCanvas"></canvas>
            </div>

            <!-- Tombol Ambil Foto / Retake -->
            <div id="cameraControls" class="camera-controls">
                <button type="button" id="btnCapture" class="btn-small btn-primary">Ambil Foto</button>
                <button type="button" id="btnRetake" class="btn-small btn-secondary" style="display:none;">Foto Ulang</button>
            </div>

            <!-- Hidden Input untuk menyimpan Base64 nanti -->
            <input type="hidden" id="photo_data" name="photo">

            <!-- Fallback Input File jika kamera gagal -->
            <div id="fallback-upload">
                <p style="margin:0 0 5px 0; font-size:0.8rem; color:red;">Kamera tidak terdeteksi. Gunakan input di bawah:</p>
                <input type="file" id="photo_fallback" name="photo_fallback" accept="image/*">
            </div>
        </div>

        <!-- Step 3: QR Code -->
        <div class="form-group">
            <label>QR Token / Hasil Scan</label>
            
            <div id="reader-container" style="display:none; background:#eee; padding:10px; border-radius:4px; margin-bottom:10px;">
                <div id="reader" style="width:100%; min-height:250px;"></div>
            </div>

            <button type="button" id="btnScan" class="btn btn-outline" style="margin-bottom:10px;">
                📷 Scan QR Code
            </button>

            <input type="text" id="qr_token" name="qr_token" placeholder="Scan atau ketik kode QR di sini" required autocomplete="off">
            <small style="color:#666; display:block; margin-top:5px;">
                Pastikan kolom ini terisi sebelum mengirim.
            </small>
        </div>

        <!-- Tombol Submit -->
        <button type="submit" id="submitBtn" class="btn" disabled>Kirim Absen</button>
    </form>
</div>

<script>
    let html5QrcodeScanner = null;
    let isScanning = false;
    
    // --- WebRTC Variables ---
    let videoStream = null;
    const videoElement = document.getElementById('webcamVideo');
    const canvasElement = document.getElementById('photoCanvas');
    const photoDataInput = document.getElementById('photo_data');
    let isPhotoTaken = false;

    document.addEventListener("DOMContentLoaded", () => {
        
        // --- LOGIKA WEBCRTC (FOTO) ---
        const btnStartCamera = document.getElementById('btnStartCamera');
        const cameraWrapper = document.getElementById('cameraWrapper');
        const cameraControls = document.getElementById('cameraControls');
        const btnCapture = document.getElementById('btnCapture');
        const btnRetake = document.getElementById('btnRetake');
        const fallbackUpload = document.getElementById('fallback-upload');

        // 1. Start Kamera
        btnStartCamera.addEventListener('click', async () => {
            try {
                // Minta akses kamera (depan 'user')
                videoStream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: "user" }, 
                    audio: false 
                });

                videoElement.srcObject = videoStream;
                cameraWrapper.style.display = 'block';
                cameraControls.style.display = 'block';
                btnStartCamera.style.display = 'none'; // Sembunyikan tombol start
                fallbackUpload.style.display = 'none';

            } catch (err) {
                console.error("Camera Error:", err);
                alert("Gagal mengakses kamera: " + err.message + ". Menggunakan input file biasa.");
                btnStartCamera.style.display = 'none';
                fallbackUpload.style.display = 'block';
            }
        });

        // 2. Capture Foto
        btnCapture.addEventListener('click', () => {
            if (!videoStream) return;

            // Set ukuran canvas sama dengan video
            canvasElement.width = videoElement.videoWidth;
            canvasElement.height = videoElement.videoHeight;

            // Gambar frame video ke canvas
            const context = canvasElement.getContext('2d');
            context.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);

            // Konversi ke Base64
            const dataUrl = canvasElement.toDataURL('image/jpeg', 0.8); // Kualitas 80%
            photoDataInput.value = dataUrl;

            // UI Update: Matikan Video, Tampilkan Canvas
            videoElement.style.display = 'none';
            canvasElement.style.display = 'block';
            btnCapture.style.display = 'none';
            btnRetake.style.display = 'inline-block';

            // Matikan stream kamera sebentar untuk hemat battery
            videoStream.getTracks().forEach(track => track.stop());
            videoStream = null;
            isPhotoTaken = true;
        });

        // 3. Retake / Foto Ulang
        btnRetake.addEventListener('click', async () => {
            // Reset UI
            canvasElement.style.display = 'none';
            videoElement.style.display = 'block';
            photoDataInput.value = ''; // Kosongkan data
            btnCapture.style.display = 'inline-block';
            btnRetake.style.display = 'none';
            isPhotoTaken = false;

            // Nyalakan kamera lagi
            try {
                videoStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" }, audio: false });
                videoElement.srcObject = videoStream;
            } catch (err) {
                alert("Gagal membuka kamera lagi.");
            }
        });

        // --- LOGIKA SCANNER QR ---
        const btnScan = document.getElementById('btnScan');
        const readerContainer = document.getElementById('reader-container');
        const tokenInput = document.getElementById('qr_token');

        btnScan.addEventListener('click', async () => {
            if (!isScanning) {
                try {
                    readerContainer.style.display = 'block';
                    html5QrcodeScanner = new Html5Qrcode("reader");
                    
                    await html5QrcodeScanner.start(
                        { facingMode: "environment" },
                        { fps: 10, qrbox: { width: 250, height: 250 } },
                        (decodedText, decodedResult) => {
                            tokenInput.value = decodedText;
                            tokenInput.style.borderColor = "#10b981";
                            tokenInput.style.backgroundColor = "#d1fae5";
                            document.getElementById('submitBtn').disabled = false;
                            // Jangan matikan otomatis, biarkan user kontrol
                        },
                        () => {} // Ignore errors
                    );
                    isScanning = true;
                    btnScan.innerText = "❌ Tutup Scanner";
                    btnScan.classList.replace('btn-outline', 'btn-danger');

                } catch (err) {
                    alert("Gagal membuka scanner QR: " + err);
                }
            } else {
                // Stop Scanner
                if (html5QrcodeScanner && isScanning) {
                    await html5QrcodeScanner.stop();
                    html5QrcodeScanner.clear();
                    isScanning = false;
                    readerContainer.style.display = 'none';
                    btnScan.innerText = "📷 Scan QR Code";
                    btnScan.classList.replace('btn-danger', 'btn-outline');
                }
            }
        });

        tokenInput.addEventListener('input', function() {
            const submitBtn = document.getElementById('submitBtn');
            if (this.value.trim().length > 0) {
                submitBtn.disabled = false;
                this.style.borderColor = "#2563eb";
            } else {
                submitBtn.disabled = true;
                this.style.borderColor = "#d1d5db";
            }
        });

        // --- SUBMIT & VERIFIKASI ---
        document.getElementById('attendanceForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const loading = document.getElementById('loading');
            const msgArea = document.getElementById('message-area');
            const submitBtn = document.getElementById('submitBtn');
            
            msgArea.innerHTML = '';
            loading.style.display = 'block';
            submitBtn.disabled = true;

            // Siapkan FormData
            const formData = new FormData();
            formData.append('nik', document.getElementById('nik').value);
            formData.append('nama', document.getElementById('nama').value);
            formData.append('qr_token', document.getElementById('qr_token').value);
            
            // Cek sumber foto (WebRTC Base64 atau Fallback File)
            const photoBase64 = photoDataInput.value;
            const fallbackFile = document.getElementById('photo_fallback').files[0];

            if (photoBase64) {
                // Konversi Base64 ke Blob
                const fetchRes = await fetch(photoBase64);
                const blob = await fetchRes.blob();
                formData.append('photo', blob, 'selfie.jpg');
            } else if (fallbackFile) {
                formData.append('photo', fallbackFile);
            } else {
                loading.style.display = 'none';
                msgArea.innerHTML = '<div class="alert alert-error">Wajib mengambil foto.</div>';
                submitBtn.disabled = false;
                return;
            }

            try {
                const res = await fetch('../api/employee_submit.php', {
                    method: 'POST',
                    body: formData
                });
                
                const json = await res.json();

                loading.style.display = 'none';

                if (json.status === 'success') {
                    const namaPegawai = document.getElementById('nama').value;
                    const waktu = json.data ? json.data.waktu : '-';

                    msgArea.innerHTML = `
                        <div class="alert alert-success" style="text-align:center;">
                            <h3 style="margin:0;">Absensi Berhasil!</h3>
                            <p style="margin:5px 0;">Karyawan: <strong>${escapeHtml(namaPegawai)}</strong></p>
                            <p style="margin:5px 0; font-size:0.9em;">Waktu: ${waktu}</p>
                        </div>
                    `;

                    setTimeout(() => { location.reload(); }, 2000);

                } else {
                    msgArea.innerHTML = `
                        <div class="alert alert-error" style="text-align:center;">
                            <strong>Absensi Ditolak</strong>
                            <p style="margin:5px 0;">${json.message}</p>
                            <small>Silakan coba lagi.</small>
                        </div>
                    `;
                    submitBtn.disabled = false;
                }

            } catch (err) {
                console.error(err);
                loading.style.display = 'none';
                msgArea.innerHTML = '<div class="alert alert-error">Terjadi kesalahan sistem.</div>';
                submitBtn.disabled = false;
            }
        });

        function escapeHtml(text) {
            if(!text) return '';
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }
    });
</script>
</body>
</html>