<?php
/**
 * DASHBOARD WITH TARGET KINERJA - PRODUCTION VERSION
 * Digital Signage BMFR Kelas II Manado
 * 
 * CARA PAKAI:
 * 1. Save file ini sebagai: dashboard.php (REPLACE yang lama)
 * 2. Import database_kinerja_berita.sql ke phpMyAdmin
 * 3. Login dan refresh dashboard
 */

// Try enhanced config first, fallback to basic config
if (file_exists('enhanced_config.php')) {
    require_once 'enhanced_config.php';
} else {
    require_once 'config.php';
}
requireLogin();

// REDIRECT VIEWER TO THEIR INFO PAGE
$currentUser = getCurrentUser();
if (!$currentUser) {
    // Fallback if getCurrentUser doesn't exist
    $currentUser = [
        'id' => $_SESSION['admin_id'] ?? 1,
        'nama' => $_SESSION['admin_nama'] ?? 'Admin',
        'username' => $_SESSION['admin_username'] ?? 'admin',
        'role' => $_SESSION['admin_role'] ?? 'admin'
    ];
}

if (isset($currentUser['role']) && $currentUser['role'] === 'viewer') {
    if (file_exists('viewer_info.php')) {
        header('Location: viewer_info.php');
        exit;
    }
}

$conn = getConnection();

// ========================================
// 1. STATISTICS - DIGITAL SIGNAGE
// ========================================
$stats = [];
$stats['external_active'] = $conn->query("SELECT COUNT(*) as c FROM konten_layar WHERE tipe_layar='external' AND status='aktif'")->fetch_assoc()['c'];
$stats['internal_active'] = $conn->query("SELECT COUNT(*) as c FROM konten_layar WHERE tipe_layar='internal' AND status='aktif'")->fetch_assoc()['c'];
$stats['total_content'] = $conn->query("SELECT COUNT(*) as c FROM konten_layar")->fetch_assoc()['c'];
$stats['today_displays'] = $conn->query("SELECT COALESCE(SUM(display_count), 0) as c FROM content_analytics WHERE display_date = CURDATE()")->fetch_assoc()['c'];

// ========================================
// 2. TARGET KINERJA BULAN INI
// ========================================
$kinerjaData = [];
$rataRataPencapaian = 0;
$bulanIni = date('n');
$tahunIni = date('Y');

// Check if table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'target_kinerja'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $queryKinerja = "SELECT 
        tk.*,
        ROUND((tk.realisasi / NULLIF(tk.target, 0)) * 100, 2) as persentase,
        CASE 
            WHEN tk.realisasi >= tk.target THEN 'tercapai'
            WHEN tk.realisasi >= (tk.target * 0.8) THEN 'mendekati'
            ELSE 'belum'
        END as status
    FROM target_kinerja tk
    WHERE tk.tahun = $tahunIni AND tk.bulan = $bulanIni
    ORDER BY tk.id";

    $result = $conn->query($queryKinerja);
    if ($result) {
        $kinerjaData = $result->fetch_all(MYSQLI_ASSOC);
        
        // Hitung rata-rata pencapaian
        $totalPersentase = 0;
        $jumlahKategori = count($kinerjaData);
        foreach ($kinerjaData as $item) {
            $totalPersentase += $item['persentase'];
        }
        $rataRataPencapaian = $jumlahKategori > 0 ? round($totalPersentase / $jumlahKategori, 2) : 0;
    }
}

// ========================================
// 3. BERITA TERBARU
// ========================================
$beritaTerbaru = [];
$tableCheck = $conn->query("SHOW TABLES LIKE 'berita'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $result = $conn->query("SELECT * FROM berita WHERE is_active=1 ORDER BY is_priority DESC, tanggal_berita DESC LIMIT 5");
    if ($result) {
        $beritaTerbaru = $result->fetch_all(MYSQLI_ASSOC);
    }
}

$conn->close();

// Fungsi untuk nama bulan Indonesia
function getNamaBulan($bulan) {
    $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $namaBulan[$bulan] ?? '';
}

// Fungsi hasRole fallback
if (!function_exists('hasRole')) {
    function hasRole($role) {
        if (!isset($_SESSION['admin_role'])) return true;
        return true; // Allow all for backward compatibility
    }
}

// Define ROLES if not exists
if (!defined('ROLES')) {
    define('ROLES', [
        'superadmin' => 'Super Admin',
        'admin' => 'Administrator',
        'editor' => 'Editor',
        'viewer' => 'Viewer'
    ]);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Digital Signage BMFR</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-content {
            max-width: 1600px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-info { text-align: right; }
        .user-role {
            font-size: 12px;
            opacity: 0.9;
            padding: 3px 10px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: inline-block;
            margin-top: 5px;
        }
        .logout-btn {
            margin-top: 10px;
            padding: 8px 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        
        .container {
            max-width: 1600px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        /* STATISTICS CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card .icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        
        /* TARGET KINERJA SECTION */
        .kinerja-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .kinerja-section h2 {
            color: #667eea;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .kinerja-subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        /* PENCAPAIAN RATA-RATA CARD */
        .pencapaian-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
        }
        .pencapaian-card .big-number {
            font-size: 64px;
            font-weight: bold;
            margin: 20px 0;
        }
        .pencapaian-card .label {
            font-size: 18px;
            opacity: 0.95;
        }
        
        /* KINERJA GRID */
        .kinerja-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
        }
        
        .kinerja-item {
            background: white;
            padding: 25px;
            border-radius: 15px;
            border-left: 5px solid #667eea;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .kinerja-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .kinerja-item.tercapai { 
            border-left-color: #10b981; 
            background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
        }
        .kinerja-item.mendekati { 
            border-left-color: #f59e0b; 
            background: linear-gradient(135deg, #ffffff 0%, #fffbeb 100%);
        }
        .kinerja-item.belum { 
            border-left-color: #ef4444; 
            background: linear-gradient(135deg, #ffffff 0%, #fef2f2 100%);
        }
        
        .kinerja-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .kinerja-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        .kinerja-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }
        .badge-tercapai { background: #10b981; color: white; }
        .badge-mendekati { background: #f59e0b; color: white; }
        .badge-belum { background: #ef4444; color: white; }
        
        /* DONUT CHART */
        .donut-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
        }
        .donut-chart {
            width: 180px;
            height: 180px;
        }
        .donut-progress {
            transition: stroke-dasharray 1s ease-in-out;
        }
        .donut-percent {
            font-size: 32px;
            font-weight: bold;
            fill: #333;
        }
        .donut-label {
            font-size: 14px;
            fill: #666;
        }
        
        .kinerja-numbers {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }
        .number-item {
            text-align: center;
            flex: 1;
        }
        .number-item .label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-weight: 600;
        }
        .number-item .value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .keterangan {
            margin-top: 15px;
            padding: 10px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 8px;
            font-size: 13px;
            color: #666;
        }
        
        /* BERITA SECTION */
        .berita-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .berita-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .berita-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background 0.3s;
        }
        .berita-item:last-child { border-bottom: none; }
        .berita-item:hover { background: #f8f9fa; }
        .berita-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .berita-meta {
            font-size: 13px;
            color: #666;
            display: flex;
            gap: 15px;
        }
        .berita-badge {
            display: inline-block;
            padding: 3px 10px;
            background: #667eea;
            color: white;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        /* QUICK LINKS */
        /* MENU SECTION */
        .menu-section {
            margin-bottom: 30px;
        }
        .menu-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 20px;
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .menu-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .menu-card .icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        .menu-card h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .menu-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }
        .menu-card .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-badge.available {
            background: #d4edda;
            color: #155724;
        }
        
        /* PREVIEW SECTION */
        .preview-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .preview-section h2 {
            color: #667eea;
            margin-bottom: 20px;
        }
        .preview-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        .preview-link {
            padding: 15px;
            background: #f0f0f0;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            text-align: center;
            transition: background 0.3s;
        }
        .preview-link:hover {
            background: #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div>
                <h1>🖥️ Digital Signage Dashboard</h1>
                <p>Balai Monitor Frekuensi Radio Kelas II Manado</p>
            </div>
            <div class="user-info">
                <div>Selamat datang, <strong><?= htmlspecialchars($currentUser['nama']) ?></strong></div>
                <div class="user-role"><?= ROLES[$currentUser['role']] ?? 'Admin' ?></div>
                <a href="auth/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- DIGITAL SIGNAGE STATISTICS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">📺</div>
                <h3>External Display Aktif</h3>
                <div class="value"><?= $stats['external_active'] ?></div>
            </div>
            <div class="stat-card">
                <div class="icon">🖥️</div>
                <h3>Internal Display Aktif</h3>
                <div class="value"><?= $stats['internal_active'] ?></div>
            </div>
            <div class="stat-card">
                <div class="icon">📊</div>
                <h3>Total Konten</h3>
                <div class="value"><?= $stats['total_content'] ?></div>
            </div>
            <div class="stat-card">
                <div class="icon">📈</div>
                <h3>Tampilan Hari Ini</h3>
                <div class="value"><?= number_format($stats['today_displays']) ?></div>
            </div>
        </div>
        
        <!-- TARGET KINERJA SECTION WITH DONUT CHARTS -->
        <div class="kinerja-section">
            <h2>
                <span>🎯</span>
                <span>Target Pencapaian Kinerja</span>
            </h2>
            <p class="kinerja-subtitle">
                Periode: <?= getNamaBulan($bulanIni) ?> <?= $tahunIni ?>
            </p>
            
            <!-- PENCAPAIAN RATA-RATA -->
            <div class="pencapaian-card">
                <div class="label">📊 PENCAPAIAN RATA-RATA</div>
                <div class="big-number"><?= $rataRataPencapaian ?>%</div>
                <div class="label">
                    <?php if ($rataRataPencapaian >= 100): ?>
                        ✅ Target Tercapai!
                    <?php elseif ($rataRataPencapaian >= 80): ?>
                        ⚠️ Mendekati Target
                    <?php else: ?>
                        📈 Perlu Ditingkatkan
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- DETAIL KINERJA PER KATEGORI WITH DONUT CHART -->
            <div class="kinerja-grid">
                <?php foreach ($kinerjaData as $index => $item): ?>
                <div class="kinerja-item <?= $item['status'] ?>">
                    <div class="kinerja-header">
                        <div class="kinerja-title"><?= htmlspecialchars($item['kategori']) ?></div>
                        <div class="kinerja-badge badge-<?= $item['status'] ?>">
                            <?php if ($item['status'] == 'tercapai'): ?>
                                ✅ Tercapai
                            <?php elseif ($item['status'] == 'mendekati'): ?>
                                ⚠️ Mendekati
                            <?php else: ?>
                                📈 Belum
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- DONUT CHART -->
                    <div class="donut-container">
                        <svg class="donut-chart" viewBox="0 0 200 200">
                            <!-- Background circle -->
                            <circle cx="100" cy="100" r="80" fill="none" stroke="#e5e7eb" stroke-width="20"></circle>
                            
                            <!-- Progress circle -->
                            <circle 
                                cx="100" 
                                cy="100" 
                                r="80" 
                                fill="none" 
                                stroke="<?php 
                                    echo $item['status'] == 'tercapai' ? '#10b981' : 
                                         ($item['status'] == 'mendekati' ? '#f59e0b' : '#ef4444'); 
                                ?>" 
                                stroke-width="20"
                                stroke-dasharray="<?= min($item['persentase'], 100) * 5.026 ?> 502.6"
                                stroke-dashoffset="125.65"
                                transform="rotate(-90 100 100)"
                                class="donut-progress"
                            ></circle>
                            
                            <!-- Center text -->
                            <text x="100" y="95" text-anchor="middle" class="donut-percent"><?= round($item['persentase']) ?>%</text>
                            <text x="100" y="115" text-anchor="middle" class="donut-label"><?= htmlspecialchars($item['satuan']) ?></text>
                        </svg>
                    </div>
                    
                    <div class="kinerja-numbers">
                        <div class="number-item">
                            <div class="label">Target</div>
                            <div class="value"><?= number_format($item['target']) ?></div>
                        </div>
                        <div class="number-item">
                            <div class="label">Realisasi</div>
                            <div class="value" style="color: #667eea;"><?= number_format($item['realisasi']) ?></div>
                        </div>
                    </div>
                    
                    <?php if ($item['keterangan']): ?>
                    <div class="keterangan">
                        <small>💬 <?= htmlspecialchars($item['keterangan']) ?></small>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- BERITA TERBARU -->
        <div class="berita-section">
            <h2>
                <span>📰</span>
                <span>Berita Terbaru</span>
            </h2>
            
            <?php if (empty($beritaTerbaru)): ?>
                <p style="color: #999; text-align: center; padding: 20px;">Belum ada berita</p>
            <?php else: ?>
                <?php foreach ($beritaTerbaru as $berita): ?>
                <div class="berita-item">
                    <div class="berita-title">
                        <?php if ($berita['is_priority']): ?>
                            <span style="color: #ef4444;">🔴</span>
                        <?php endif; ?>
                        <?= htmlspecialchars($berita['judul']) ?>
                    </div>
                    <div class="berita-meta">
                        <span>📅 <?= date('d M Y, H:i', strtotime($berita['tanggal_berita'])) ?></span>
                        <span>📡 <?= htmlspecialchars($berita['sumber']) ?></span>
                        <span class="berita-badge"><?= htmlspecialchars($berita['kategori']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- MAIN MENU (DARI DASHBOARD LAMA) -->
        <div class="menu-section">
            <h2>📋 Kelola Konten Display</h2>
            <div class="menu-grid">
                <?php if (hasRole('editor')): ?>
                <a href="manage_display/manage_external.php" class="menu-card">
                    <span class="status-badge available">✓ Available</span>
                    <div class="icon">📺</div>
                    <h3>Kelola External Display</h3>
                    <p>Kelola konten untuk 4 layar eksternal yang dapat diakses publik.</p>
                </a>
                
                <a href="manage_display/manage_internal.php" class="menu-card">
                    <span class="status-badge available">✓ Available</span>
                    <div class="icon">🖥️</div>
                    <h3>Kelola Internal Display</h3>
                    <p>Kelola konten untuk 3 layar internal khusus pegawai.</p>
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- ENHANCED FEATURES MENU -->
        <div class="menu-section">
            <h2>🎯 Fitur Tambahan</h2>
            <div class="menu-grid">
                <?php if (hasRole('editor')): ?>
                <a href="management/manage_kinerja.php" class="menu-card">
                    <span class="status-badge available">✓ Available</span>
                    <div class="icon">🎯</div>
                    <h3>Target Kinerja</h3>
                    <p>Kelola target dan realisasi pencapaian kinerja BMFR.</p>
                </a>
                
                <a href="management/manage_berita.php" class="menu-card">
                    <span class="status-badge available">✓ Available</span>
                    <div class="icon">📰</div>
                    <h3>Kelola Berita</h3>
                    <p>Kelola berita yang ditampilkan di running text external.</p>
                </a>
                <?php endif; ?>
                
                <a href="management/analytics.php" class="menu-card">
                    <span class="status-badge available">✓ Available</span>
                    <div class="icon">📊</div>
                    <h3>Analytics & Reports</h3>
                    <p>Lihat statistik tampilan konten dan laporan performa.</p>
                </a>
                
                <?php if (hasRole('admin')): ?>
                <a href="management/manage_users.php" class="menu-card">
                    <span class="status-badge available">✓ Available</span>
                    <div class="icon">👥</div>
                    <h3>User Management</h3>
                    <p>Kelola user, role akses, dan permission sistem.</p>
                </a>
                
                <a href="management/manage_backup.php" class="menu-card">
                    <span class="status-badge available">✓ Available</span>
                    <div class="icon">💾</div>
                    <h3>Backup & Restore</h3>
                    <p>Kelola backup database dan file secara otomatis.</p>
                </a>
                <?php endif; ?>
                
                <a href="management/activity_log.php" class="menu-card">
                    <span class="status-badge available">✓ Available</span>
                    <div class="icon">📝</div>
                    <h3>Activity Log</h3>
                    <p>Lihat riwayat aktivitas dan perubahan sistem.</p>
                </a>
                
                <?php if (hasRole('admin')): ?>
                <a href="management/settings.php" class="menu-card">
                    <span class="status-badge available">✓ Available</span>
                    <div class="icon">⚙️</div>
                    <h3>System Settings</h3>
                    <p>Konfigurasi sistem dan preferensi aplikasi.</p>
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- PREVIEW LINKS -->
        <div class="preview-section">
            <h2>👀 Preview Layar Digital Signage</h2>
            <div class="preview-links">
                <a href="display/display_external.php" target="_blank" class="preview-link">
                    <strong>External Layar 1</strong>
                </a>
                <a href="display/display_external.php?nomor=2" target="_blank" class="preview-link">
                    <strong>External Layar 2</strong>
                </a>
                <a href="display/display_external.php?nomor=3" target="_blank" class="preview-link">
                    <strong>External Layar 3</strong>
                </a>
                <a href="display/display_external.php?nomor=4" target="_blank" class="preview-link">
                    <strong>External Layar 4</strong>
                </a>
                <a href="display/display_internal.php?nomor=1" target="_blank" class="preview-link">
                    <strong>Internal Layar 1</strong>
                </a>
                <a href="display/display_internal.php?nomor=2" target="_blank" class="preview-link">
                    <strong>Internal Layar 2</strong>
                </a>
                <a href="display/display_internal.php?nomor=3" target="_blank" class="preview-link">
                    <strong>Internal Layar 3</strong>
                </a>
            </div>
        </div>
    </div>
</body>
</html>