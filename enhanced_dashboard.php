<?php
require_once 'enhanced_config.php';
requireLogin();

$conn = getConnection();
$currentUser = getCurrentUser();

// Get statistics
$stats = [];

// Content stats
$stats['external_active'] = $conn->query("SELECT COUNT(*) as c FROM konten_layar WHERE tipe_layar='external' AND status='aktif'")->fetch_assoc()['c'];
$stats['internal_active'] = $conn->query("SELECT COUNT(*) as c FROM konten_layar WHERE tipe_layar='internal' AND status='aktif'")->fetch_assoc()['c'];
$stats['total_content'] = $conn->query("SELECT COUNT(*) as c FROM konten_layar")->fetch_assoc()['c'];

// Today's displays
$stats['today_displays'] = $conn->query("SELECT COALESCE(SUM(display_count), 0) as c FROM content_analytics WHERE display_date = CURDATE()")->fetch_assoc()['c'];

// Active users
$stats['active_users'] = $conn->query("SELECT COUNT(*) as c FROM admin WHERE is_active=1")->fetch_assoc()['c'];

// RSS feeds
$stats['rss_feeds'] = $conn->query("SELECT COUNT(*) as c FROM rss_feeds WHERE is_active=1")->fetch_assoc()['c'];

// Recent activities
$recentActivities = getRecentActivities(10);

// Top content this week
$topContentQuery = "SELECT k.id, k.judul, k.tipe_layar, SUM(ca.display_count) as displays
                    FROM konten_layar k
                    JOIN content_analytics ca ON k.id = ca.konten_id
                    WHERE ca.display_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    GROUP BY k.id
                    ORDER BY displays DESC
                    LIMIT 5";
$topContent = $conn->query($topContentQuery)->fetch_all(MYSQLI_ASSOC);

// System health check
$systemHealth = [
    'database' => $conn->ping(),
    'uploads_dir' => is_writable(UPLOAD_DIR),
    'backup_dir' => is_writable(BACKUP_DIR),
    'gd_extension' => extension_loaded('gd'),
];

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Dashboard - Digital Signage BMFR</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        
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
        .user-info {
            text-align: right;
        }
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
        
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .activity-item {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        .activity-item:last-child { border-bottom: none; }
        .activity-time {
            color: #999;
            font-size: 12px;
        }
        
        .health-status {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            background: #f8f9fa;
        }
        .health-status.ok { background: #d4edda; color: #155724; }
        .health-status.error { background: #f8d7da; color: #721c24; }
        
        .top-content-item {
            padding: 12px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .top-content-item:last-child { border-bottom: none; }
        .badge {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-external { background: #e3f2fd; color: #1976d2; }
        .badge-internal { background: #f3e5f5; color: #7b1fa2; }
        
        .preview-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .preview-link {
            padding: 12px;
            background: #f0f0f0;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            text-align: center;
            font-size: 14px;
            transition: background 0.3s;
        }
        .preview-link:hover { background: #e0e0e0; }
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
                <div class="user-role"><?= ROLES[$currentUser['role']] ?></div>
                <a href="auth/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
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
                <h3>Tampilan Hari Ini</h3>
                <div class="value"><?= number_format($stats['today_displays']) ?></div>
            </div>
            <div class="stat-card">
                <div class="icon">👥</div>
                <h3>User Aktif</h3>
                <div class="value"><?= $stats['active_users'] ?></div>
            </div>
            <div class="stat-card">
                <div class="icon">📰</div>
                <h3>RSS Feeds</h3>
                <div class="value"><?= $stats['rss_feeds'] ?></div>
            </div>
            <div class="stat-card">
                <div class="icon">📁</div>
                <h3>Total Konten</h3>
                <div class="value"><?= $stats['total_content'] ?></div>
            </div>
        </div>
        
        <!-- Main Menu -->
        <div class="card">
            <h2>🎯 Menu Utama</h2>
            <div class="menu-grid">
                <?php if (hasRole('editor')): ?>
                <a href="manage_display/manage_external.php" class="menu-card">
                    <div class="icon">📺</div>
                    <h3>Kelola External Display</h3>
                    <p>Kelola konten untuk 4 layar eksternal</p>
                </a>
                
                <a href="manage_display/manage_internal.php" class="menu-card">
                    <div class="icon">🖥️</div>
                    <h3>Kelola Internal Display</h3>
                    <p>Kelola konten untuk 3 layar internal</p>
                </a>
                <?php endif; ?>
                
                <?php if (hasRole('admin')): ?>
                <a href="management/analytics.php" class="menu-card">
                    <div class="icon">📊</div>
                    <h3>Analytics & Reports</h3>
                    <p>Lihat statistik dan laporan konten</p>
                </a>
                
                <a href="management/manage_users.php" class="menu-card">
                    <div class="icon">👥</div>
                    <h3>User Management</h3>
                    <p>Kelola user dan role akses</p>
                </a>
                
                <a href="management/manage_rss.php" class="menu-card">
                    <div class="icon">📰</div>
                    <h3>RSS Feed Manager</h3>
                    <p>Kelola feed berita otomatis</p>
                </a>
                
                <a href="management/manage_backup.php" class="menu-card">
                    <div class="icon">💾</div>
                    <h3>Backup & Restore</h3>
                    <p>Kelola backup database dan file</p>
                </a>
                <?php endif; ?>
                
                <a href="management/activity_log.php" class="menu-card">
                    <div class="icon">📝</div>
                    <h3>Activity Log</h3>
                    <p>Lihat riwayat aktivitas sistem</p>
                </a>
                
                <?php if (hasRole('admin')): ?>
                <a href="management/settings.php" class="menu-card">
                    <div class="icon">⚙️</div>
                    <h3>System Settings</h3>
                    <p>Konfigurasi sistem dan preferensi</p>
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Top Content -->
            <div class="card">
                <h2>🏆 Top Konten (7 Hari)</h2>
                <?php if (empty($topContent)): ?>
                    <p style="color: #999;">Belum ada data</p>
                <?php else: ?>
                    <?php foreach ($topContent as $content): ?>
                    <div class="top-content-item">
                        <div>
                            <strong><?= htmlspecialchars($content['judul']) ?></strong>
                            <br>
                            <span class="badge badge-<?= $content['tipe_layar'] ?>">
                                <?= ucfirst($content['tipe_layar']) ?>
                            </span>
                        </div>
                        <strong><?= number_format($content['displays']) ?> views</strong>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Recent Activity -->
            <div class="card">
                <h2>📝 Aktivitas Terkini</h2>
                <?php foreach (array_slice($recentActivities, 0, 5) as $activity): ?>
                <div class="activity-item">
                    <strong><?= htmlspecialchars($activity['nama']) ?></strong> 
                    <?= htmlspecialchars($activity['action']) ?> 
                    <em><?= htmlspecialchars($activity['module']) ?></em>
                    <div class="activity-time"><?= timeAgo($activity['created_at']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- System Health -->
            <div class="card">
                <h2>🏥 System Health</h2>
                <div class="health-status <?= $systemHealth['database'] ? 'ok' : 'error' ?>">
                    <?= $systemHealth['database'] ? '✅' : '❌' ?> Database Connection
                </div>
                <div class="health-status <?= $systemHealth['uploads_dir'] ? 'ok' : 'error' ?>">
                    <?= $systemHealth['uploads_dir'] ? '✅' : '❌' ?> Uploads Directory
                </div>
                <div class="health-status <?= $systemHealth['backup_dir'] ? 'ok' : 'error' ?>">
                    <?= $systemHealth['backup_dir'] ? '✅' : '❌' ?> Backup Directory
                </div>
                <div class="health-status <?= $systemHealth['gd_extension'] ? 'ok' : 'error' ?>">
                    <?= $systemHealth['gd_extension'] ? '✅' : '❌' ?> GD Extension
                </div>
            </div>
        </div>
        
        <!-- Preview Links -->
        <div class="card">
            <h2>👀 Preview Display</h2>
            <div class="preview-links">
                <a href="display/display_external.php" target="_blank" class="preview-link">External 1</a>
                <a href="display/display_external.php?nomor=2" target="_blank" class="preview-link">External 2</a>
                <a href="display/display_external.php?nomor=3" target="_blank" class="preview-link">External 3</a>
                <a href="display/display_external.php?nomor=4" target="_blank" class="preview-link">External 4</a>
                <a href="display/display_internal.php?nomor=1" target="_blank" class="preview-link">Internal 1</a>
                <a href="display/display_internal.php?nomor=2" target="_blank" class="preview-link">Internal 2</a>
                <a href="display/display_internal.php?nomor=3" target="_blank" class="preview-link">Internal 3</a>
            </div>
        </div>
    </div>
</body>
</html>