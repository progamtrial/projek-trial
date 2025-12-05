<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Reference - Berita & Running Text</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 36px;
            text-align: center;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 40px;
            font-size: 18px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        .card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            border-left: 5px solid #667eea;
        }
        .card h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card h3 {
            color: #333;
            margin: 20px 0 10px 0;
            font-size: 18px;
        }
        .card ul {
            margin-left: 20px;
            line-height: 1.8;
        }
        .card li {
            margin-bottom: 10px;
        }
        .code {
            background: #2d3748;
            color: #48bb78;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            margin: 15px 0;
            overflow-x: auto;
        }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        .badge-new { background: #48bb78; color: white; }
        .badge-important { background: #f56565; color: white; }
        .badge-optional { background: #ed8936; color: white; }
        .table-container {
            overflow-x: auto;
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        tr:hover {
            background: #f7fafc;
        }
        .highlight {
            background: #fef5e7;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: 600;
        }
        .alert {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 10px;
            margin: 30px 0;
        }
        .alert h3 {
            color: #856404;
            margin-bottom: 15px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 10px 10px 10px 0;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-success {
            background: #28a745;
        }
        @media print {
            body { background: white; padding: 0; }
            .container { box-shadow: none; }
            .btn { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Quick Reference Guide</h1>
        <p class="subtitle">Berita & Running Text Feature - Digital Signage SMFR</p>
        
        <div style="text-align: center; margin-bottom: 40px;">
            <a href="installation_guide.html" class="btn">📥 Installation Guide</a>
            <a href="../dashboard.php" class="btn btn-success">📊 Dashboard</a>
            <a href="javascript:window.print()" class="btn btn-secondary">🖨️ Print</a>
        </div>
        
        <div class="alert">
            <h3>⚡ Quick Start (3 Langkah)</h3>
            <ol style="margin-left: 20px; line-height: 2;">
                <li>Import <code>database_enhancement_berita.sql</code> ke phpMyAdmin</li>
                <li>Upload file PHP ke folder yang sesuai</li>
                <li>Tambahkan running text component ke display</li>
            </ol>
        </div>
        
        <!-- FITUR OVERVIEW -->
        <div class="grid">
            <div class="card">
                <h2>🆕 Fitur Baru</h2>
                
                <h3>1. Kelola Berita <span class="badge badge-new">NEW</span></h3>
                <ul>
                    <li>Tambah/edit berita manual</li>
                    <li>Kategori: Eksternal, Internal, Cybercrime, Pengumuman</li>
                    <li>Berita prioritas (urgent)</li>
                    <li>Pilih tampil di external/internal/both</li>
                </ul>
                
                <h3>2. RSS Feeds <span class="badge badge-new">NEW</span></h3>
                <ul>
                    <li>Auto-fetch dari Kominfo, BSSN, Detik</li>
                    <li>Cache di database</li>
                    <li>Update otomatis via CRON</li>
                </ul>
                
                <h3>3. Target Kinerja Display <span class="badge badge-new">NEW</span></h3>
                <ul>
                    <li>Tampilkan pencapaian di internal</li>
                    <li>Set display priority (0-10)</li>
                    <li>Toggle on/off per target</li>
                </ul>
                
                <h3>4. Running Text <span class="badge badge-new">NEW</span></h3>
                <ul>
                    <li>Smooth scrolling animation</li>
                    <li>Prioritas berita urgent</li>
                    <li>Auto-refresh every 60 detik</li>
                    <li>Configurable scroll speed</li>
                </ul>
            </div>
            
            <div class="card">
                <h2>📂 File Structure</h2>
                
                <div class="code">
digital-signage/
├── api/
│   └── get_running_text.php <span class="badge badge-new">NEW</span>
│
├── cron/
│   └── fetch_rss_feeds.php <span class="badge badge-optional">OPTIONAL</span>
│
├── management/
│   ├── manage_berita.php <span class="badge badge-new">NEW</span>
│   └── manage_kinerja.php <span class="badge badge-important">UPDATED</span>
│
├── logs/
│   └── rss_fetcher.log (auto)
│
└── running_text.html <span class="badge badge-new">NEW</span>
                </div>
            </div>
        </div>
        
        <!-- DATABASE TABLES -->
        <div class="card" style="margin-bottom: 30px;">
            <h2>🗄️ Database Tables (Baru)</h2>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Table Name</th>
                            <th>Purpose</th>
                            <th>Key Columns</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>berita</code></td>
                            <td>Berita manual untuk running text</td>
                            <td>judul, kategori, display_on, is_priority</td>
                        </tr>
                        <tr>
                            <td><code>rss_feeds</code></td>
                            <td>Daftar RSS feed sources</td>
                            <td>name, url, category, is_active</td>
                        </tr>
                        <tr>
                            <td><code>rss_items</code></td>
                            <td>Cache berita dari RSS</td>
                            <td>title, description, pub_date</td>
                        </tr>
                        <tr>
                            <td><code>running_text_settings</code></td>
                            <td>Setting per display</td>
                            <td>show_berita, show_target, scroll_speed</td>
                        </tr>
                        <tr>
                            <td><code>target_kinerja</code></td>
                            <td>Target kinerja (updated)</td>
                            <td>display_on_internal, display_priority</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- MENU LOCATIONS -->
        <div class="grid">
            <div class="card">
                <h2>🎯 Menu Locations</h2>
                
                <h3>Dashboard Menu</h3>
                <ul>
                    <li><strong>Kelola Berita</strong><br>
                        → <code>management/manage_berita.php</code>
                    </li>
                    <li><strong>Target Kinerja</strong><br>
                        → <code>management/manage_kinerja.php</code>
                    </li>
                </ul>
                
                <h3>Required Role</h3>
                <ul>
                    <li>✅ <span class="highlight">Editor</span> - Bisa kelola berita & target</li>
                    <li>✅ <span class="highlight">Admin</span> - Full access</li>
                    <li>✅ <span class="highlight">Superadmin</span> - Full access</li>
                    <li>❌ Viewer - Read-only</li>
                </ul>
            </div>
            
            <div class="card">
                <h2>⚙️ Configuration</h2>
                
                <h3>Running Text Display</h3>
                <div class="code">
&lt;!-- External Display --&gt;
&lt;iframe src="../running_text.html
  ?display_type=external
  &nomor_layar=1"
  width="100%" height="60"&gt;
&lt;/iframe&gt;

&lt;!-- Internal Display --&gt;
&lt;iframe src="../running_text.html
  ?display_type=internal
  &nomor_layar=1"
  width="100%" height="60"&gt;
&lt;/iframe&gt;
                </div>
                
                <h3>Scroll Speed Settings</h3>
                <ul>
                    <li>20 = Very slow (untuk baca detail)</li>
                    <li>50 = Normal (default)</li>
                    <li>80 = Fast (banyak berita)</li>
                    <li>100 = Very fast</li>
                </ul>
            </div>
        </div>
        
        <!-- COMMON TASKS -->
        <div class="grid">
            <div class="card">
                <h2>📝 Common Tasks</h2>
                
                <h3>1. Tambah Berita Baru</h3>
                <ol style="margin-left: 20px; line-height: 2;">
                    <li>Dashboard → Kelola Berita</li>
                    <li>Isi form berita</li>
                    <li>Pilih kategori & display</li>
                    <li>Centang "Priority" jika urgent</li>
                    <li>Save</li>
                </ol>
                
                <h3>2. Set Target Display Internal</h3>
                <ol style="margin-left: 20px; line-height: 2;">
                    <li>Dashboard → Target Kinerja</li>
                    <li>Edit target existing</li>
                    <li>✓ Display on Internal</li>
                    <li>Set Priority (0-10)</li>
                    <li>Update</li>
                </ol>
                
                <h3>3. Atur Running Text Settings</h3>
                <ol style="margin-left: 20px; line-height: 2;">
                    <li>Kelola Berita → Tab Settings</li>
                    <li>Pilih display (External/Internal)</li>
                    <li>Toggle: Berita, Target, RSS</li>
                    <li>Atur scroll speed</li>
                    <li>Save</li>
                </ol>
            </div>
            
            <div class="card">
                <h2>🔍 Troubleshooting</h2>
                
                <h3>Running text tidak muncul</h3>
                <ul>
                    <li>✅ Cek API: <code>api/get_running_text.php</code></li>
                    <li>✅ Cek browser console (F12)</li>
                    <li>✅ Cek path iframe benar</li>
                    <li>✅ Hard refresh: Ctrl+F5</li>
                </ul>
                
                <h3>RSS tidak fetch</h3>
                <ul>
                    <li>✅ Test manual: <code>cron/fetch_rss_feeds.php</code></li>
                    <li>✅ Cek log: <code>logs/rss_fetcher.log</code></li>
                    <li>✅ Cek firewall allow outbound</li>
                    <li>✅ Verify RSS URL valid</li>
                </ul>
                
                <h3>Target tidak muncul di internal</h3>
                <ul>
                    <li>✅ Cek <code>display_on_internal</code> = TRUE</li>
                    <li>✅ Cek running text settings</li>
                    <li>✅ Pastikan display_type = 'internal'</li>
                </ul>
                
                <h3>Berita tidak update</h3>
                <ul>
                    <li>✅ Refresh interval: 60 detik</li>
                    <li>✅ Hard refresh browser</li>
                    <li>✅ Clear cache</li>
                    <li>✅ Cek API timestamp</li>
                </ul>
            </div>
        </div>
        
        <!-- API ENDPOINTS -->
        <div class="card" style="margin-bottom: 30px;">
            <h2>🔌 API Endpoints</h2>
            
            <h3>1. Get Running Text Data</h3>
            <div class="code">
GET /api/get_running_text.php
    ?display_type={external|internal}
    &nomor_layar={1-7}

Response:
{
  "success": true,
  "display_type": "external",
  "nomor_layar": 1,
  "settings": {
    "show_berita": true,
    "show_target_kinerja": false,
    "show_rss": true,
    "scroll_speed": 50
  },
  "count": 25,
  "news": [
    {
      "type": "berita",
      "text": "Selamat datang di SMFR",
      "sumber": "SMFR",
      "icon": "📡",
      "priority": false
    },
    ...
  ]
}
            </div>
            
            <h3>2. Fetch RSS Feeds (Manual)</h3>
            <div class="code">
GET /cron/fetch_rss_feeds.php

Response:
{
  "success": true,
  "feeds_processed": 4,
  "items_fetched": 87,
  "items_new": 12,
  "errors": 0
}
            </div>
        </div>
        
        <!-- SQL QUERIES -->
        <div class="card" style="margin-bottom: 30px;">
            <h2>💾 Useful SQL Queries</h2>
            
            <h3>Lihat Berita Aktif</h3>
            <div class="code">
SELECT id, judul, kategori, display_on, is_priority
FROM berita 
WHERE is_active = TRUE
ORDER BY is_priority DESC, tanggal_berita DESC;
            </div>
            
            <h3>Lihat RSS Items Terbaru</h3>
            <div class="code">
SELECT ri.title, rf.name, ri.pub_date
FROM rss_items ri
JOIN rss_feeds rf ON ri.feed_id = rf.id
ORDER BY ri.pub_date DESC
LIMIT 20;
            </div>
            
            <h3>Lihat Target yang Ditampilkan</h3>
            <div class="code">
SELECT kategori, target, realisasi, 
       ROUND((realisasi/target)*100, 2) as persen,
       display_on_internal, display_priority
FROM target_kinerja
WHERE display_on_internal = TRUE
ORDER BY display_priority DESC;
            </div>
            
            <h3>Cleanup Old RSS</h3>
            <div class="code">
DELETE FROM rss_items 
WHERE pub_date < DATE_SUB(NOW(), INTERVAL 30 DAY);
            </div>
        </div>
        
        <!-- BEST PRACTICES -->
        <div class="grid">
            <div class="card">
                <h2>✨ Best Practices</h2>
                
                <h3>Berita</h3>
                <ul>
                    <li>Judul singkat, jelas (max 100 karakter)</li>
                    <li>Gunakan kategori yang tepat</li>
                    <li>Priority hanya untuk urgent</li>
                    <li>Review & hapus berita lama</li>
                </ul>
                
                <h3>Target Kinerja</h3>
                <ul>
                    <li>Update realisasi setiap minggu</li>
                    <li>Display priority: 5-10 untuk penting</li>
                    <li>Hanya tampilkan yang relevan</li>
                    <li>Berikan keterangan jika perlu</li>
                </ul>
                
                <h3>RSS Feeds</h3>
                <ul>
                    <li>Setup CRON untuk auto-fetch</li>
                    <li>Monitor log untuk error</li>
                    <li>Nonaktifkan feed yang sering error</li>
                    <li>Cleanup old items berkala</li>
                </ul>
            </div>
            
            <div class="card">
                <h2>🎨 Customization Tips</h2>
                
                <h3>Warna Running Text</h3>
                <p>Edit <code>running_text.html</code>:</p>
                <div class="code">
.running-text-container {
  border-top: 3px solid #667eea;
}
                </div>
                
                <h3>Font Size</h3>
                <div class="code">
.news-item {
  font-size: 24px; /* Ganti */
}
                </div>
                
                <h3>Scroll Speed (Code)</h3>
                <div class="code">
// running_text.html
const REFRESH_INTERVAL = 60000;
let scrollSpeed = 50;
                </div>
                
                <h3>Tambah RSS Feed</h3>
                <div class="code">
INSERT INTO rss_feeds 
(name, url, category) VALUES
('Your Feed', 'https://...', 'news');
                </div>
            </div>
        </div>
        
        <!-- FOOTER -->
        <div style="margin-top: 40px; padding-top: 30px; border-top: 2px solid #eee; text-align: center; color: #666;">
            <p><strong>Digital Signage SMFR Kelas II Manado</strong></p>
            <p>Berita & Running Text Feature v1.0</p>
            <p style="margin-top: 20px;">
                <a href="installation_guide.html" class="btn">📥 Full Installation Guide</a>
                <a href="../dashboard.php" class="btn btn-success">📊 Go to Dashboard</a>
            </p>
        </div>
    </div>
</body>
</html>