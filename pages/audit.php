<?php
// pages/audit.php
require_once '../config.php';
requireAuthPage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Kehadiran</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .audit-card { display: flex; gap: 15px; align-items: flex-start; }
        .audit-img-container { 
            width: 80px; 
            height: 80px; 
            flex-shrink: 0;
            background: #eee; 
            border-radius: 8px; 
            overflow: hidden;
            display:flex; align-items:center; justify-content:center;
        }
        .audit-img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
        }
        .audit-placeholder {
            font-size:0.7rem; color:#999; text-align:center; padding:5px;
        }
        .audit-info { flex: 1; }
        .audit-name { font-weight: bold; font-size: 1rem; }
        .audit-meta { font-size: 0.8rem; color: #666; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="dashboard_admin.php">&larr; Kembali</a>
            <h3>Audit Log</h3>
        </div>

        <div class="form-group" style="margin-bottom:20px;">
            <input type="date" id="dateFilter" value="<?php echo date('Y-m-d'); ?>">
        </div>

        <div id="auditList">
            <div style="text-align:center; padding:20px;">Memuat data...</div>
        </div>
    </div>

    <script>
        const listContainer = document.getElementById('auditList');
        const dateInput = document.getElementById('dateFilter');

        async function loadAudit() {
            const date = dateInput.value;
            listContainer.innerHTML = '<div style="text-align:center;">Loading...</div>';

            try {
                const req = await fetch(`../api/audit_list.php?date=${date}`);
                const res = await req.json();

                if (res.status === 'success' && res.data.length > 0) {
                    let html = '';
                    res.data.forEach(item => {
                        // PATH GAMBAR: Gabungkan folder uploads + nama file dari DB
                        const imgUrl = `../uploads/${item.photo_path}`;
                        
                        html += `
                        <div class="card audit-card">
                            <div class="audit-img-container" onclick="viewImage('${imgUrl}')" style="cursor:pointer;">
                                <img src="${imgUrl}" class="audit-img" 
                                     onerror="this.onerror=null;this.style.display='none';this.parentElement.innerHTML='<span class=\\'audit-placeholder\\'>No Img</span>'" 
                                     alt="Foto">
                            </div>
                            <div class="audit-info">
                                <div class="audit-name">${escapeHtml(item.nama)}</div>
                                <div class="audit-meta">NIK: ${escapeHtml(item.nik || '-')}</div>
                                <div class="audit-meta">Waktu: ${item.submit_time}</div>
                            </div>
                        </div>`;
                    });
                    listContainer.innerHTML = html;
                } else {
                    listContainer.innerHTML = '<div style="text-align:center; padding:20px;">Tidak ada data absensi pada tanggal ini.</div>';
                }
            } catch (err) {
                listContainer.innerHTML = '<div style="text-align:center; color:red;">Gagal memuat data.</div>';
                console.error(err);
            }
        }

        function escapeHtml(text) {
            if(!text) return '';
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        function viewImage(src) {
            const win = window.open("");
            win.document.write(`<img src="${src}" style="width:100%;">`);
        }

        dateInput.addEventListener('change', loadAudit);
        loadAudit();
    </script>
</body>
</html>