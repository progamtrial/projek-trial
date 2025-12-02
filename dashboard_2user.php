<?php
require_once 'enhanced_config_2users.php';
requireLogin();

$conn = getConnection();
$currentUser = getCurrentUser();

// Get statistics
$stats = [];
$stats['external_active'] = $conn->query("SELECT COUNT(*) as c FROM konten_layar WHERE tipe_layar='external' AND status='aktif'")->fetch_assoc()['c'];
$stats['internal_active'] = $conn->query("SELECT COUNT(*) as c FROM konten_layar WHERE tipe_layar='internal' AND status='aktif'")->fetch_assoc()['c'];
$stats['total_content'] = $conn->query("SELECT COUNT(*) as c FROM konten_layar")->fetch_assoc()['c'];

// Check enhanced features
$hasEnhanced = false;
$tableCheck = $conn->query("SHOW TABLES LIKE 'content_analytics'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $hasEnhanced = true;
    $stats['today_displays'] = $conn->query("SELECT COALESCE(SUM(display_count), 0) as c FROM content_analytics WHERE display_date = CURDATE()")->fetch_assoc()['c'];
    $stats['active_users'] = $conn->query("SELECT COUNT(*) as c FROM admin WHERE is_active=1")->fetch_assoc()['c'];
    
    $rssCheck = $conn->query("SHOW TABLES LIKE 'rss_feeds'");
    if ($rssCheck && $rssCheck->num_rows > 0) {
        $stats['rss_feeds'] = $conn->query("SELECT COUNT(*) as c FROM rss_feeds WHERE is_active=1")->fetch_assoc()['c'];
    }
}

$conn->close();
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
            display: inline-block;
        }
        
        .container {
            max-width: 1600px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
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
        
        .alert {
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            background: #d4edda;
            border: 1px solid #28a745;
            color: #155724;
        }
        .alert h3 { margin-bottom: 10px; }
        
        .role-info {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .role-info h3 {
            color: #667eea;
            margin-bottom: 15px;
        }
        .role-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            margin: 5px;
        }
        .role-badge.superadmin {
            background: #e3f2fd;
            color: #1976d2;
        }
        .role-badge.editor {
            background: #fff3e0;
            color: #f57c00;
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
                <div class="user-role"><?= ROLES[$currentUser['role']] ?? 'Editor' ?></div>
                <a href="auth/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Role Info -->
        <div class="role-info">
            <h3>ℹ️ Informasi Role</h3>
            <p>Sistem ini memiliki 2 role:</p>
            <div style="margin-top: 15px;">
                <span class="role-badge superadmin">🔑 Superadmin</span> Full access ke semua fitur
            </div>
            <div>
                <span class="role-badge editor">✏️ Editor</span> Kelola konten display dan RSS feeds
            </div>
            <p style="margin-top: 15px; color: #666; font-size: 14px;">
                Role Anda saat ini: <strong><?= ROLES[$currentUser['role']] ?? 'Editor' ?></strong>
            </p>
        </div>
        
        <!-- Statistics -->
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
            <?php if ($hasEnhanced): ?>
            <div class="stat-card">
                <div class="icon">📈</div>
                <h3>Tampilan Hari Ini</h3>
                <div class="value"><?= number_format($stats['today_displays'] ?? 0) ?></div>
            </div>
            <div class="stat-card">
                <div class="icon">👥</div>
                <h3>User Aktif</h3>
                <div class="value"><?= $stats['active_users'] ?? 0 ?></div>
            </div>
            <?php if (isset($stats['rss_feeds'])): ?>
            <div class="stat-card">
                <div class="icon">📰</div>
                <h3>RSS Feeds</h3>
                <div class="value"><?= $stats['rss_feeds'] ?></div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Main Menu - EDITOR ACCESS -->
        <div class="menu-section">
            <h2>📋 Kelola Konten Display</h2>
            <div class="menu-grid">
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
            </div>
        </div>
        
        <!-- Enhanced Features Menu -->
        <div class="menu-section">
            <h2>🎯 Fitur Tambahan</h2>
            <div class="menu-grid">
                <a href="management/analytics.php" class="menu-card">
                    <span class="status-badge available">✓ Available</span>
                    <div class="icon">📊</div>
                    <h3>Analytics & Reports</h3>
                    <p>Lihat statistik tampilan konten dan laporan performa.</p>
                </a>
                
                <?php if ($currentUser['role'] === 'superadmin'): ?>
                <a href="management/manage_users.php" class="menu-card">
                    <span class="status-badge available">✓ Superadmin Only</span>
                    <div class="icon">👥</div>
                    <h3>User Management</h3>
                    <p>Kelola user dan role akses sistem (Superadmin & Editor).</p>
                </a>
                <?php endif; ?>
                
                <a href="management/manage_rss.php" class="menu-card">
                    <span class="status-badge available">✓ Available</span>
                    <div class="icon">📰</div>
                    <h3>RSS Feed Manager</h3>
                    <p>Kelola feed berita otomatis untuk ditampilkan di layar.</p>
                </a>
                
                <?php if ($currentUser['role'] === 'superadmin'): ?>
                <a href="management/manage_backup.php" class="menu-card">
                    <span class="status-badge available">✓ Superadmin Only</span>
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
                
                <?php if ($currentUser['role'] === 'superadmin'): ?>
                <a href="management/settings.php" class="menu-card">
                    <span class="status-badge available">✓ Superadmin Only</span>
                    <div class="icon">⚙️</div>
                    <h3>System Settings</h3>
                    <p>Konfigurasi sistem dan preferensi aplikasi.</p>
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Preview Links -->
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