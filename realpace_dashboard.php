<?php
/**
 * REPLACE DASHBOARD SCRIPT
 * Script untuk mengganti dashboard lama dengan dashboard kinerja baru
 * Akses: http://localhost/digital-signage/replace_dashboard.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$results = [];
$errors = [];

if (isset($_POST['replace'])) {
    // 1. Backup dashboard lama
    if (file_exists('dashboard.php')) {
        $backupName = 'dashboard_old_backup_' . date('Y-m-d_His') . '.php';
        if (copy('dashboard.php', $backupName)) {
            $results[] = "✅ Dashboard lama di-backup ke: $backupName";
        } else {
            $errors[] = "❌ Gagal backup dashboard lama";
        }
    } else {
        $results[] = "ℹ️ File dashboard.php tidak ditemukan";
    }
    
    // 2. Rename dashboard_kinerja.php menjadi dashboard.php
    if (file_exists('dashboard_kinerja.php')) {
        // Hapus dashboard.php lama jika ada
        if (file_exists('dashboard.php')) {
            unlink('dashboard.php');
        }
        
        // Copy dashboard_kinerja.php ke dashboard.php
        if (copy('dashboard_kinerja.php', 'dashboard.php')) {
            $results[] = "✅ Dashboard baru berhasil di-apply ke dashboard.php";
        } else {
            $errors[] = "❌ Gagal copy dashboard_kinerja.php ke dashboard.php";
        }
    } else {
        $errors[] = "❌ File dashboard_kinerja.php tidak ditemukan";
    }
    
    // 3. Import database kinerja jika belum
    require_once 'config.php';
    $conn = getConnection();
    
    $tableCheck = $conn->query("SHOW TABLES LIKE 'target_kinerja'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        $results[] = "⚠️ Tabel target_kinerja belum ada. Silakan import database_kinerja.sql";
    } else {
        $results[] = "✅ Database target kinerja sudah ada";
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Replace Dashboard - Digital Signage</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .warning h3 {
            color: #856404;
            margin-bottom: 10px;
        }
        .warning ul {
            margin-left: 20px;
            color: #856404;
        }
        .info-box {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #1976d2;
        }
        .info-box h3 {
            color: #1976d2;
            margin-bottom: 15px;
        }
        .success {
            background: #d4edda;
            border: 2px solid #28a745;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border: 2px solid #dc3545;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            color: #721c24;
        }
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            margin-top: 20px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            opacity: 0.9;
        }
        .comparison {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        .comparison-item {
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #eee;
        }
        .comparison-item h4 {
            margin-bottom: 15px;
        }
        .comparison-item.old {
            background: #f8f9fa;
        }
        .comparison-item.new {
            background: #e3f2fd;
        }
        .comparison-item ul {
            margin-left: 20px;
        }
        .comparison-item li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Replace Dashboard</h1>
        <p class="subtitle">Ganti dashboard lama dengan dashboard kinerja baru</p>
        
        <?php if (!empty($results)): ?>
            <?php foreach ($results as $msg): ?>
                <div class="success"><?= $msg ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $msg): ?>
                <div class="error"><?= $msg ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (empty($results) && empty($errors)): ?>
        
        <div class="warning">
            <h3>⚠️ PENTING - Baca Sebelum Menjalankan!</h3>
            <ul>
                <li>Script ini akan <strong>mengganti dashboard.php</strong> dengan dashboard kinerja baru</li>
                <li>Dashboard lama akan di-backup dengan nama <code>dashboard_old_backup_[timestamp].php</code></li>
                <li>Pastikan Anda sudah import <strong>database_kinerja.sql</strong> terlebih dahulu</li>
                <li>Proses ini tidak bisa di-undo secara otomatis</li>
            </ul>
        </div>
        
        <div class="info-box">
            <h3>📋 Apa yang Berbeda?</h3>
            <div class="comparison">
                <div class="comparison-item old">
                    <h4>❌ Dashboard Lama</h4>
                    <ul>
                        <li>Hanya menampilkan konten digital signage</li>
                        <li>Statistik basic (external, internal aktif)</li>
                        <li>Menu management standar</li>
                        <li>Tidak ada tracking kinerja</li>
                    </ul>
                </div>
                <div class="comparison-item new">
                    <h4>✅ Dashboard Baru (Kinerja)</h4>
                    <ul>
                        <li><strong>Target Kinerja Real-Time</strong></li>
                        <li><strong>Diagram Bulat Pencapaian</strong></li>
                        <li><strong>Statistik per Kategori</strong></li>
                        <li><strong>Top 5 Target Tercapai</strong></li>
                        <li><strong>Management Berita</strong></li>
                        <li>Semua fitur dashboard lama tetap ada</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="info-box">
            <h3>🎯 Fitur Baru yang Ditambahkan:</h3>
            <ol style="margin-left: 20px; margin-top: 10px;">
                <li><strong>Target Kinerja:</strong> Track pencapaian target monitoring, inspeksi, perizinan, dll</li>
                <li><strong>Diagram Circular:</strong> Visualisasi persentase pencapaian rata-rata</li>
                <li><strong>Progress Kategori:</strong> Bar chart pencapaian per kategori</li>
                <li><strong>Statistic Cards:</strong> 8 kartu statistik lengkap dengan ikon</li>
                <li><strong>Running Text Berita:</strong> Kelola berita untuk tampil di layar external</li>
                <li><strong>Top Performer:</strong> Daftar 5 target dengan pencapaian tertinggi</li>
            </ol>
        </div>
        
        <form method="POST">
            <button type="submit" name="replace" class="btn btn-primary" onclick="return confirm('Yakin ingin mengganti dashboard?\n\nDashboard lama akan di-backup.')">
                🚀 Replace Dashboard Sekarang
            </button>
        </form>
        
        <?php else: ?>
        
        <div class="info-box">
            <h3>✅ Selesai!</h3>
            <p>Dashboard berhasil diganti. Silakan:</p>
            <ol style="margin-left: 20px; margin-top: 10px;">
                <li>Logout dari sistem</li>
                <li>Login kembali</li>
                <li>Lihat dashboard baru</li>
                <li>Import <strong>database_kinerja.sql</strong> jika belum</li>
            </ol>
        </div>
        
        <a href="dashboard.php" class="btn btn-primary" style="display: block; text-align: center; text-decoration: none;">
            📊 Lihat Dashboard Baru
        </a>
        
        <?php endif; ?>
    </div>
</body>
</html>