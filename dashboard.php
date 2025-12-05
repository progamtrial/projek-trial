<?php
/**
 * DASHBOARD MODERN - BMFR Kelas II Manado
 * Tema: Monitoring Frekuensi Radio dengan Visual Canggih
 */

// Load config
if (file_exists('enhanced_config.php')) {
    require_once 'enhanced_config.php';
} else {
    require_once 'config.php';
}

requireLogin();

$conn = getConnection();
$currentUser = getCurrentUser();

// Redirect viewer
if (isset($currentUser['role']) && $currentUser['role'] === 'viewer') {
    if (file_exists('viewer_info.php')) {
        header('Location: viewer_info.php');
        exit;
    }
}

// ========================================
// STATISTICS - DIGITAL SIGNAGE
// ========================================
$stats = [];
$stats['external_active'] = $conn->query("SELECT COUNT(*) as c FROM konten_layar WHERE tipe_layar='external' AND status='aktif'")->fetch_assoc()['c'];
$stats['internal_active'] = $conn->query("SELECT COUNT(*) as c FROM konten_layar WHERE tipe_layar='internal' AND status='aktif'")->fetch_assoc()['c'];
$stats['total_content'] = $conn->query("SELECT COUNT(*) as c FROM konten_layar")->fetch_assoc()['c'];

// Today's displays
$tableCheck = $conn->query("SHOW TABLES LIKE 'content_analytics'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $stats['today_displays'] = $conn->query("SELECT COALESCE(SUM(display_count), 0) as c FROM content_analytics WHERE display_date = CURDATE()")->fetch_assoc()['c'];
    $stats['week_displays'] = $conn->query("SELECT COALESCE(SUM(display_count), 0) as c FROM content_analytics WHERE display_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['c'];
} else {
    $stats['today_displays'] = 0;
    $stats['week_displays'] = 0;
}

// Active users
$stats['active_users'] = $conn->query("SELECT COUNT(*) as c FROM admin WHERE is_active=1")->fetch_assoc()['c'];

// Recent activities (last 5)
$recentActivities = [];
$tableCheck = $conn->query("SHOW TABLES LIKE 'activity_log'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $activityQuery = "SELECT al.*, a.nama FROM activity_log al 
                     LEFT JOIN admin a ON al.user_id = a.id 
                     ORDER BY al.created_at DESC LIMIT 5";
    $recentActivities = $conn->query($activityQuery)->fetch_all(MYSQLI_ASSOC);
}

// Top content this week
$topContent = [];
$tableCheck = $conn->query("SHOW TABLES LIKE 'content_analytics'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $topQuery = "SELECT k.id, k.judul, k.tipe_layar, k.nomor_layar, SUM(ca.display_count) as displays
                FROM konten_layar k
                JOIN content_analytics ca ON k.id = ca.konten_id
                WHERE ca.display_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY k.id
                ORDER BY displays DESC
                LIMIT 5";
    $topContent = $conn->query($topQuery)->fetch_all(MYSQLI_ASSOC);
}

// System health
$systemHealth = [
    'database' => $conn->ping(),
    'uploads_dir' => is_writable(UPLOAD_DIR),
    'backup_dir' => is_writable(BACKUP_DIR ?? __DIR__ . '/backups/')
];

// ========================================
// TARGET KINERJA BULAN INI
// ========================================
$kinerjaData = [];
$rataRataPencapaian = 0;
$bulanIni = date('n');
$tahunIni = date('Y');

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
        
        $totalPersentase = 0;
        $jumlahKategori = count($kinerjaData);
        foreach ($kinerjaData as $item) {
            $totalPersentase += $item['persentase'];
        }
        $rataRataPencapaian = $jumlahKategori > 0 ? round($totalPersentase / $jumlahKategori, 2) : 0;
    }
}

// ========================================
// BERITA TERBARU
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

function getNamaBulan($bulan) {
    $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $namaBulan[$bulan] ?? '';
}

// Helper functions
if (!function_exists('hasRole')) {
    function hasRole($role) { return true; }
}
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
    <title>Dashboard Modern - BMFR Manado</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#667eea',
                        secondary: '#764ba2',
                        radio: '#00d4ff',
                        signal: '#7928ca'
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes wave {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(0, 212, 255, 0.3); }
            50% { box-shadow: 0 0 40px rgba(0, 212, 255, 0.6); }
        }
        @keyframes radar-sweep {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .wave-animate { animation: wave 3s ease-in-out infinite; }
        .pulse-glow { animation: pulse-glow 2s ease-in-out infinite; }
        .radar-sweep { animation: radar-sweep 4s linear infinite; }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .signal-bar {
            animation: signal-pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes signal-pulse {
            0%, 100% { opacity: 0.4; transform: scaleY(0.8); }
            50% { opacity: 1; transform: scaleY(1); }
        }
        
        .frequency-line {
            background: linear-gradient(90deg, 
                transparent 0%, 
                #00d4ff 50%, 
                transparent 100%
            );
            height: 2px;
            animation: frequency-scan 2s linear infinite;
        }
        
        @keyframes frequency-scan {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 min-h-screen">
    
    <!-- Animated Background -->
    <div class="fixed inset-0 opacity-10 pointer-events-none">
        <div class="absolute inset-0" style="background-image: radial-gradient(circle at 20% 50%, rgba(102, 126, 234, 0.2) 0%, transparent 50%), radial-gradient(circle at 80% 80%, rgba(0, 212, 255, 0.2) 0%, transparent 50%);"></div>
    </div>

    <!-- Header with Radio Theme -->
    <header class="relative bg-gradient-to-r from-slate-900/90 to-purple-900/90 backdrop-blur-xl border-b border-white/10 shadow-2xl">
        <div class="max-w-7xl mx-auto px-6 py-6">
            <div class="flex items-center justify-between">
                <!-- Logo & Title -->
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <div class="absolute inset-0 bg-radio/20 rounded-full blur-xl pulse-glow"></div>
                        <div class="relative w-16 h-16 bg-gradient-to-br from-radio to-signal rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white flex items-center space-x-2">
                            <span>BMFR Monitoring Station</span>
                            <span class="text-radio text-2xl wave-animate">📡</span>
                        </h1>
                        <p class="text-purple-200">Balai Monitor Frekuensi Radio Kelas II Manado</p>
                    </div>
                </div>
                
                <!-- User Info & Clock -->
                <div class="flex items-center space-x-6">
                    <!-- Live Clock -->
                    <div class="flex items-center space-x-3 bg-white/5 px-5 py-3 rounded-xl backdrop-blur border border-white/10">
                        <svg class="w-5 h-5 text-radio" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-white font-mono text-lg" id="liveClock"></span>
                    </div>
                    
                    <!-- User Profile -->
                    <div class="flex items-center space-x-3 bg-white/5 px-5 py-3 rounded-xl backdrop-blur border border-white/10">
                        <div class="w-10 h-10 bg-gradient-to-br from-radio to-signal rounded-full flex items-center justify-center">
                            <span class="text-white font-bold"><?= strtoupper(substr($currentUser['nama'], 0, 1)) ?></span>
                        </div>
                        <div>
                            <div class="text-white font-semibold"><?= htmlspecialchars($currentUser['nama']) ?></div>
                            <div class="text-purple-300 text-xs"><?= ROLES[$currentUser['role']] ?? 'User' ?></div>
                        </div>
                    </div>
                    
                    <!-- Logout -->
                    <a href="auth/logout.php" class="bg-red-500/20 hover:bg-red-500/30 text-red-300 px-5 py-3 rounded-xl font-semibold transition-all duration-300 border border-red-500/30">
                        Logout
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Frequency Scanner Line -->
        <div class="absolute bottom-0 left-0 right-0 h-0.5 overflow-hidden">
            <div class="frequency-line"></div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="relative max-w-7xl mx-auto px-6 py-8 z-10">
        
        <!-- Stats Cards with Radio Theme -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Card 1: External Displays -->
            <div class="card-hover bg-gradient-to-br from-blue-500/10 to-blue-600/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-1 h-6 bg-blue-400 signal-bar" style="animation-delay: 0s"></div>
                        <div class="w-1 h-8 bg-blue-400 signal-bar" style="animation-delay: 0.2s"></div>
                        <div class="w-1 h-10 bg-blue-400 signal-bar" style="animation-delay: 0.4s"></div>
                    </div>
                </div>
                <div class="text-4xl font-bold text-white mb-2"><?= $stats['external_active'] ?></div>
                <div class="text-blue-200 font-medium">External Display Aktif</div>
            </div>

            <!-- Card 2: Internal Displays -->
            <div class="card-hover bg-gradient-to-br from-purple-500/10 to-purple-600/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-1 h-6 bg-purple-400 signal-bar" style="animation-delay: 0s"></div>
                        <div class="w-1 h-8 bg-purple-400 signal-bar" style="animation-delay: 0.2s"></div>
                        <div class="w-1 h-10 bg-purple-400 signal-bar" style="animation-delay: 0.4s"></div>
                    </div>
                </div>
                <div class="text-4xl font-bold text-white mb-2"><?= $stats['internal_active'] ?></div>
                <div class="text-purple-200 font-medium">Internal Display Aktif</div>
            </div>

            <!-- Card 3: Today's Displays -->
            <div class="card-hover bg-gradient-to-br from-green-500/10 to-green-600/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center pulse-glow">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <div class="text-green-400 text-sm font-semibold">+<?= number_format($stats['week_displays']) ?> minggu ini</div>
                </div>
                <div class="text-4xl font-bold text-white mb-2"><?= number_format($stats['today_displays']) ?></div>
                <div class="text-green-200 font-medium">Tampilan Hari Ini</div>
            </div>

            <!-- Card 4: Total Content -->
            <div class="card-hover bg-gradient-to-br from-orange-500/10 to-orange-600/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-orange-400 to-orange-600 rounded-xl flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                    <div class="text-orange-400 text-sm font-semibold">📊</div>
                </div>
                <div class="text-4xl font-bold text-white mb-2"><?= $stats['total_content'] ?></div>
                <div class="text-orange-200 font-medium">Total Konten</div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            
            <!-- Quick Actions - 2 columns -->
            <div class="lg:col-span-2 bg-white/5 backdrop-blur-xl rounded-2xl p-6 border border-white/10 shadow-xl">
                <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                    <svg class="w-7 h-7 mr-3 text-radio" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Quick Actions
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <?php if (hasRole('editor')): ?>
                    <a href="manage_display/manage_external.php" class="group bg-gradient-to-br from-blue-500/20 to-blue-600/20 hover:from-blue-500/30 hover:to-blue-600/30 p-6 rounded-xl border border-blue-500/30 transition-all duration-300">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-white font-semibold text-lg">External Display</div>
                                <div class="text-blue-200 text-sm">Kelola 4 layar eksternal</div>
                            </div>
                        </div>
                    </a>

                    <a href="manage_display/manage_internal.php" class="group bg-gradient-to-br from-purple-500/20 to-purple-600/20 hover:from-purple-500/30 hover:to-purple-600/30 p-6 rounded-xl border border-purple-500/30 transition-all duration-300">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-white font-semibold text-lg">Internal Display</div>
                                <div class="text-purple-200 text-sm">Kelola 3 layar internal</div>
                            </div>
                        </div>
                    </a>
                    <?php endif; ?>

                    <a href="management/analytics.php" class="group bg-gradient-to-br from-green-500/20 to-green-600/20 hover:from-green-500/30 hover:to-green-600/30 p-6 rounded-xl border border-green-500/30 transition-all duration-300">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-white font-semibold text-lg">Analytics</div>
                                <div class="text-green-200 text-sm">Lihat statistik lengkap</div>
                            </div>
                        </div>
                    </a>

                    <?php if (hasRole('admin')): ?>
                    <a href="management/manage_users.php" class="group bg-gradient-to-br from-orange-500/20 to-orange-600/20 hover:from-orange-500/30 hover:to-orange-600/30 p-6 rounded-xl border border-orange-500/30 transition-all duration-300">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-white font-semibold text-lg">User Management</div>
                                <div class="text-orange-200 text-sm">Kelola user & role</div>
                            </div>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- System Health -->
            <div class="bg-white/5 backdrop-blur-xl rounded-2xl p-6 border border-white/10 shadow-xl">
                <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                    <svg class="w-7 h-7 mr-3 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    System Health
                </h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-<?= $systemHealth['database'] ? 'green' : 'red' ?>-500/10 rounded-lg border border-<?= $systemHealth['database'] ? 'green' : 'red' ?>-500/30">
                        <span class="text-white font-medium">Database</span>
                        <span class="text-<?= $systemHealth['database'] ? 'green' : 'red' ?>-400 text-2xl">
                            <?= $systemHealth['database'] ? '✓' : '✗' ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-<?= $systemHealth['uploads_dir'] ? 'green' : 'red' ?>-500/10 rounded-lg border border-<?= $systemHealth['uploads_dir'] ? 'green' : 'red' ?>-500/30">
                        <span class="text-white font-medium">Uploads Directory</span>
                        <span class="text-<?= $systemHealth['uploads_dir'] ? 'green' : 'red' ?>-400 text-2xl">
                            <?= $systemHealth['uploads_dir'] ? '✓' : '✗' ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-<?= $systemHealth['backup_dir'] ? 'green' : 'red' ?>-500/10 rounded-lg border border-<?= $systemHealth['backup_dir'] ? 'green' : 'red' ?>-500/30">
                        <span class="text-white font-medium">Backup Directory</span>
                        <span class="text-<?= $systemHealth['backup_dir'] ? 'green' : 'red' ?>-400 text-2xl">
                            <?= $systemHealth['backup_dir'] ? '✓' : '✗' ?>
                        </span>
                    </div>
                    <div class="mt-4 p-4 bg-blue-500/10 rounded-lg border border-blue-500/30">
                        <div class="text-blue-200 text-sm mb-2">Uptime</div>
                        <div class="text-white text-2xl font-bold">99.9%</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Target Kinerja dengan Chart -->
            <div class="lg:col-span-2 bg-white/5 backdrop-blur-xl rounded-2xl p-6 border border-white/10 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-white flex items-center">
                        <svg class="w-7 h-7 mr-3 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Target Kinerja - <?= getNamaBulan($bulanIni) ?> <?= $tahunIni ?>
                    </h2>
                    <a href="management/manage_kinerja.php" class="bg-yellow-500/20 hover:bg-yellow-500/30 text-yellow-300 px-4 py-2 rounded-lg font-semibold transition-all duration-300 border border-yellow-500/30">
                        Kelola Target
                    </a>
                </div>

                <?php if (!empty($kinerjaData)): ?>
                <!-- Pencapaian Rata-rata dengan Circular Progress -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-gradient-to-br from-yellow-500/20 to-orange-500/20 rounded-2xl p-6 border border-yellow-500/30 text-center">
                        <div class="relative w-40 h-40 mx-auto mb-4">
                            <svg class="transform -rotate-90 w-40 h-40">
                                <circle cx="80" cy="80" r="70" stroke="rgba(255,255,255,0.1)" stroke-width="12" fill="none"/>
                                <circle cx="80" cy="80" r="70" stroke="url(#gradient1)" stroke-width="12" fill="none"
                                        stroke-dasharray="<?= min($rataRataPencapaian, 100) * 4.4 ?> 440" stroke-linecap="round"/>
                            </svg>
                            <defs>
                                <linearGradient id="gradient1" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#fbbf24;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#f97316;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="text-5xl font-bold text-white"><?= $rataRataPencapaian ?>%</div>
                            </div>
                        </div>
                        <div class="text-yellow-200 font-semibold text-lg">Pencapaian Rata-rata</div>
                    </div>

                    <div class="bg-gradient-to-br from-green-500/20 to-green-600/20 rounded-2xl p-6 border border-green-500/30">
                        <div class="text-6xl font-bold text-white mb-2"><?= count(array_filter($kinerjaData, fn($k) => $k['status'] === 'tercapai')) ?></div>
                        <div class="text-green-200 font-semibold mb-4">Target Tercapai</div>
                        <div class="text-green-300 text-sm">dari <?= count($kinerjaData) ?> total target</div>
                    </div>

                    <div class="bg-gradient-to-br from-blue-500/20 to-blue-600/20 rounded-2xl p-6 border border-blue-500/30">
                        <div class="text-6xl font-bold text-white mb-2"><?= array_sum(array_column($kinerjaData, 'realisasi')) ?></div>
                        <div class="text-blue-200 font-semibold mb-4">Total Realisasi</div>
                        <div class="text-blue-300 text-sm">dari <?= array_sum(array_column($kinerjaData, 'target')) ?> target</div>
                    </div>
                </div>

                <!-- Chart Target vs Realisasi -->
                <div class="bg-white/5 rounded-xl p-6 border border-white/10 mb-6">
                    <h3 class="text-xl font-bold text-white mb-4">📊 Target vs Realisasi</h3>
                    <canvas id="kinerjaChart" height="100"></canvas>
                </div>

                <!-- Detail Kinerja Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($kinerjaData as $item): ?>
                    <?php
                        $statusColor = $item['status'] === 'tercapai' ? 'green' : ($item['status'] === 'mendekati' ? 'yellow' : 'red');
                        $statusIcon = $item['status'] === 'tercapai' ? '✅' : ($item['status'] === 'mendekati' ? '⚠️' : '📈');
                    ?>
                    <div class="bg-<?= $statusColor ?>-500/10 backdrop-blur-lg rounded-xl p-5 border border-<?= $statusColor ?>-500/30 card-hover">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h4 class="text-white font-bold text-lg mb-2"><?= htmlspecialchars($item['kategori']) ?></h4>
                                <span class="text-<?= $statusColor ?>-400 text-sm font-semibold"><?= $statusIcon ?> <?= ucfirst($item['status']) ?></span>
                            </div>
                            <div class="text-right">
                                <div class="text-3xl font-bold text-white"><?= round($item['persentase']) ?>%</div>
                                <div class="text-<?= $statusColor ?>-300 text-xs"><?= $item['satuan'] ?></div>
                            </div>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="w-full h-3 bg-white/10 rounded-full overflow-hidden mb-4">
                            <div class="h-full bg-gradient-to-r from-<?= $statusColor ?>-400 to-<?= $statusColor ?>-600 rounded-full transition-all duration-1000" 
                                 style="width: <?= min($item['persentase'], 100) ?>%"></div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 text-center">
                            <div class="bg-white/5 rounded-lg p-3">
                                <div class="text-white/60 text-xs mb-1">Target</div>
                                <div class="text-white font-bold text-xl"><?= number_format($item['target']) ?></div>
                            </div>
                            <div class="bg-white/5 rounded-lg p-3">
                                <div class="text-white/60 text-xs mb-1">Realisasi</div>
                                <div class="text-<?= $statusColor ?>-400 font-bold text-xl"><?= number_format($item['realisasi']) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-12">
                    <svg class="w-20 h-20 mx-auto text-white/20 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <p class="text-white/60 text-lg mb-4">Belum ada data target kinerja bulan ini</p>
                    <a href="management/manage_kinerja.php" class="inline-block bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg font-semibold transition-all">
                        + Tambah Target Kinerja
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Recent Activity -->
            <div class="bg-white/5 backdrop-blur-xl rounded-2xl p-6 border border-white/10 shadow-xl">
                <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                    <svg class="w-7 h-7 mr-3 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Recent Activity
                </h2>
                <?php if (!empty($recentActivities)): ?>
                <div class="space-y-3">
                    <?php foreach ($recentActivities as $activity): ?>
                    <div class="bg-white/5 rounded-lg p-4 border border-white/10 hover:bg-white/10 transition-all">
                        <div class="flex items-start space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-white font-bold text-sm"><?= strtoupper(substr($activity['nama'] ?? 'U', 0, 1)) ?></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-white font-medium text-sm"><?= htmlspecialchars($activity['nama'] ?? 'User') ?></p>
                                <p class="text-purple-200 text-xs truncate"><?= htmlspecialchars($activity['description']) ?></p>
                                <p class="text-purple-400 text-xs mt-1"><?= date('d M Y H:i', strtotime($activity['created_at'])) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-white/60 text-center py-8">Belum ada aktivitas</p>
                <?php endif; ?>
            </div>

            <!-- Top Content & Berita -->
            <div class="bg-white/5 backdrop-blur-xl rounded-2xl p-6 border border-white/10 shadow-xl">
                <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                    <svg class="w-7 h-7 mr-3 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    Top Content (7 Hari)
                </h2>
                <?php if (!empty($topContent)): ?>
                <div class="space-y-3">
                    <?php foreach ($topContent as $idx => $content): ?>
                    <div class="bg-white/5 rounded-lg p-4 border border-white/10 hover:bg-white/10 transition-all">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-orange-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                <span class="text-white font-bold text-lg">#<?= $idx + 1 ?></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-white font-medium text-sm truncate"><?= htmlspecialchars($content['judul']) ?></p>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span class="text-xs px-2 py-1 bg-<?= $content['tipe_layar'] === 'external' ? 'blue' : 'purple' ?>-500/30 text-<?= $content['tipe_layar'] === 'external' ? 'blue' : 'purple' ?>-300 rounded">
                                        <?= ucfirst($content['tipe_layar']) ?> <?= $content['nomor_layar'] ?>
                                    </span>
                                    <span class="text-orange-400 text-xs font-bold"><?= number_format($content['displays']) ?> views</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-white/60 text-center py-8">Belum ada data</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Display Preview Links -->
        <div class="mt-8 bg-white/5 backdrop-blur-xl rounded-2xl p-6 border border-white/10 shadow-xl">
            <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                <svg class="w-7 h-7 mr-3 text-radio" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                Preview Display Monitor
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                <?php for ($i = 1; $i <= 4; $i++): ?>
                <a href="display/display_external.php<?= $i > 1 ? '?nomor=' . $i : '' ?>" target="_blank" class="group bg-gradient-to-br from-blue-500/20 to-blue-600/20 hover:from-blue-500/30 hover:to-blue-600/30 p-4 rounded-xl border border-blue-500/30 text-center transition-all duration-300">
                    <svg class="w-10 h-10 mx-auto mb-2 text-blue-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <div class="text-white font-semibold text-sm">External <?= $i ?></div>
                </a>
                <?php endfor; ?>
                <?php for ($i = 1; $i <= 3; $i++): ?>
                <a href="display/display_internal.php?nomor=<?= $i ?>" target="_blank" class="group bg-gradient-to-br from-purple-500/20 to-purple-600/20 hover:from-purple-500/30 hover:to-purple-600/30 p-4 rounded-xl border border-purple-500/30 text-center transition-all duration-300">
                    <svg class="w-10 h-10 mx-auto mb-2 text-purple-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <div class="text-white font-semibold text-sm">Internal <?= $i ?></div>
                </a>
                <?php endfor; ?>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script>
        // Live Clock
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('liveClock').textContent = `${hours}:${minutes}:${seconds}`;
        }
        updateClock();
        setInterval(updateClock, 1000);

        <?php if (!empty($kinerjaData)): ?>
        // Chart Target vs Realisasi
        const ctx = document.getElementById('kinerjaChart').getContext('2d');
        const kinerjaChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($kinerjaData, 'kategori')) ?>,
                datasets: [
                    {
                        label: 'Target',
                        data: <?= json_encode(array_column($kinerjaData, 'target')) ?>,
                        backgroundColor: 'rgba(251, 191, 36, 0.3)',
                        borderColor: 'rgba(251, 191, 36, 1)',
                        borderWidth: 2,
                        borderRadius: 8
                    },
                    {
                        label: 'Realisasi',
                        data: <?= json_encode(array_column($kinerjaData, 'realisasi')) ?>,
                        backgroundColor: 'rgba(34, 197, 94, 0.3)',
                        borderColor: 'rgba(34, 197, 94, 1)',
                        borderWidth: 2,
                        borderRadius: 8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: 'rgba(255, 255, 255, 0.8)',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.6)'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.6)',
                            font: {
                                size: 11
                            }
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>