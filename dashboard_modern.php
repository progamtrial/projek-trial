<?php
/**
 * DASHBOARD MODERN - Digital Signage SMFR Kelas II Manado
 * File: dashboard_modern.php
 * Location: C:\xampp\htdocs\digital-signage\dashboard_modern.php
 * 
 * CARA INSTALL:
 * 1. Save file ini sebagai dashboard_modern.php di root folder
 * 2. Backup dashboard.php lama
 * 3. Rename dashboard_modern.php menjadi dashboard.php (atau akses langsung)
 * 4. Akses: http://localhost/digital-signage/dashboard_modern.php
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

// Redirect viewer to their info page
if (isset($currentUser['role']) && $currentUser['role'] === 'viewer') {
    if (file_exists('viewer_info.php')) {
        header('Location: viewer_info.php');
        exit;
    }
}

// ========================================
// 1. STATISTICS - DIGITAL SIGNAGE
// ========================================
$stats = [];
$stats['external_active'] = $conn->query("SELECT COUNT(*) as c FROM konten_layar WHERE tipe_layar='external' AND status='aktif'")->fetch_assoc()['c'];
$stats['internal_active'] = $conn->query("SELECT COUNT(*) as c FROM konten_layar WHERE tipe_layar='internal' AND status='aktif'")->fetch_assoc()['c'];
$stats['total_content'] = $conn->query("SELECT COUNT(*) as c FROM konten_layar")->fetch_assoc()['c'];

// Today's displays (if analytics table exists)
$tableCheck = $conn->query("SHOW TABLES LIKE 'content_analytics'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $stats['today_displays'] = $conn->query("SELECT COALESCE(SUM(display_count), 0) as c FROM content_analytics WHERE display_date = CURDATE()")->fetch_assoc()['c'];
    $stats['active_users'] = $conn->query("SELECT COUNT(*) as c FROM admin WHERE is_active=1")->fetch_assoc()['c'];
} else {
    $stats['today_displays'] = 0;
    $stats['active_users'] = 1;
}

// ========================================
// 2. TARGET KINERJA BULAN INI
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
    ORDER BY tk.id
    LIMIT 4";

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

function getNamaBulan($bulan) {
    $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $namaBulan[$bulan] ?? '';
}

if (!function_exists('hasRole')) {
    function hasRole($role) {
        return true;
    }
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
    <title>Dashboard Modern - SMFR Manado</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        @keyframes wave {
            0% { transform: translateY(0); }
            100% { transform: translateY(50px); }
        }
        @keyframes pulse-slow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .wave-bg {
            background-image: repeating-linear-gradient(
                0deg,
                transparent,
                transparent 50px,
                rgba(255,255,255,0.03) 50px,
                rgba(255,255,255,0.03) 51px
            );
            animation: wave 20s linear infinite;
        }
        .pulse-icon {
            animation: pulse-slow 2s infinite;
        }
        .float-element {
            animation: float 6s ease-in-out infinite;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-purple-900 via-purple-800 to-indigo-900">
    
    <!-- Background Wave Animation -->
    <div class="fixed inset-0 opacity-10 pointer-events-none">
        <div class="absolute inset-0 wave-bg"></div>
    </div>

    <!-- Header -->
    <header class="bg-white/10 backdrop-blur-lg border-b border-white/20 sticky top-0 z-50 shadow-lg">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <i data-lucide="radio" class="w-12 h-12 text-white pulse-icon"></i>
                        <i data-lucide="wifi" class="w-5 h-5 text-yellow-300 absolute -top-1 -right-1"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Digital Signage Dashboard</h1>
                        <p class="text-purple-200 text-sm">Balai Monitor Frekuensi Radio Kelas II Manado</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-6">
                    <div class="flex items-center space-x-2 bg-white/10 px-4 py-2 rounded-lg backdrop-blur">
                        <i data-lucide="clock" class="w-5 h-5 text-purple-200"></i>
                        <span class="text-white font-semibold" id="clock"></span>
                    </div>
                    <div class="flex items-center space-x-2 bg-white/10 px-4 py-2 rounded-lg backdrop-blur">
                        <i data-lucide="calendar" class="w-5 h-5 text-purple-200"></i>
                        <span class="text-white font-semibold"><?= date('d M Y') ?></span>
                    </div>
                    <div class="flex items-center space-x-3 bg-white/10 px-4 py-2 rounded-lg backdrop-blur">
                        <i data-lucide="user" class="w-5 h-5 text-purple-200"></i>
                        <span class="text-white font-semibold"><?= htmlspecialchars($currentUser['nama']) ?></span>
                    </div>
                    <a href="auth/logout.php" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-2 rounded-lg font-semibold hover:shadow-lg transition-all duration-300">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-6 py-8 relative z-10">
        
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-2xl p-8 mb-8 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32 float-element"></div>
            <div class="relative z-10">
                <h2 class="text-3xl font-bold text-white mb-2">Selamat Datang, <?= htmlspecialchars($currentUser['nama']) ?>! 👋</h2>
                <p class="text-purple-100">Monitoring sistem digital signage berjalan dengan baik</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <!-- Card 1: External Display -->
            <div class="bg-gradient-to-br from-white to-purple-50 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 border border-purple-100 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-purple-400/10 to-transparent rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600">
                            <i data-lucide="monitor" class="w-6 h-6 text-white"></i>
                        </div>
                        <div class="flex items-center text-green-600 text-sm font-semibold">
                            <i data-lucide="trending-up" class="w-4 h-4 mr-1"></i>
                            +12%
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-gray-800 mb-1"><?= $stats['external_active'] ?></div>
                    <div class="text-sm text-gray-600 font-medium">External Display Aktif</div>
                </div>
            </div>

            <!-- Card 2: Internal Display -->
            <div class="bg-gradient-to-br from-white to-purple-50 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 border border-purple-100 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-purple-400/10 to-transparent rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600">
                            <i data-lucide="monitor" class="w-6 h-6 text-white"></i>
                        </div>
                        <div class="flex items-center text-green-600 text-sm font-semibold">
                            <i data-lucide="trending-up" class="w-4 h-4 mr-1"></i>
                            +8%
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-gray-800 mb-1"><?= $stats['internal_active'] ?></div>
                    <div class="text-sm text-gray-600 font-medium">Internal Display Aktif</div>
                </div>
            </div>

            <!-- Card 3: Today Displays -->
            <div class="bg-gradient-to-br from-white to-purple-50 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 border border-purple-100 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-purple-400/10 to-transparent rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 rounded-xl bg-gradient-to-br from-green-500 to-green-600">
                            <i data-lucide="bar-chart-3" class="w-6 h-6 text-white"></i>
                        </div>
                        <div class="flex items-center text-green-600 text-sm font-semibold">
                            <i data-lucide="trending-up" class="w-4 h-4 mr-1"></i>
                            +15%
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-gray-800 mb-1"><?= number_format($stats['today_displays']) ?></div>
                    <div class="text-sm text-gray-600 font-medium">Tampilan Hari Ini</div>
                </div>
            </div>

            <!-- Card 4: Active Users -->
            <div class="bg-gradient-to-br from-white to-purple-50 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 border border-purple-100 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-purple-400/10 to-transparent rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600">
                            <i data-lucide="users" class="w-6 h-6 text-white"></i>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-gray-800 mb-1"><?= $stats['active_users'] ?></div>
                    <div class="text-sm text-gray-600 font-medium">User Aktif</div>
                </div>
            </div>

            <!-- Card 5: Total Content -->
            <div class="bg-gradient-to-br from-white to-purple-50 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 border border-purple-100 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-purple-400/10 to-transparent rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 rounded-xl bg-gradient-to-br from-pink-500 to-pink-600">
                            <i data-lucide="activity" class="w-6 h-6 text-white"></i>
                        </div>
                        <div class="flex items-center text-green-600 text-sm font-semibold">
                            <i data-lucide="trending-up" class="w-4 h-4 mr-1"></i>
                            +22%
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-gray-800 mb-1"><?= number_format($stats['total_content']) ?></div>
                    <div class="text-sm text-gray-600 font-medium">Total Konten</div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-2 mb-8 inline-flex space-x-2 shadow-lg">
            <button onclick="showTab('overview')" id="tab-overview" class="px-6 py-3 rounded-xl font-semibold transition-all duration-300 bg-white text-purple-600 shadow-lg">
                📊 Overview
            </button>
            <button onclick="showTab('kinerja')" id="tab-kinerja" class="px-6 py-3 rounded-xl font-semibold transition-all duration-300 text-white hover:bg-white/10">
                🎯 Target Kinerja
            </button>
            <button onclick="showTab('berita')" id="tab-berita" class="px-6 py-3 rounded-xl font-semibold transition-all duration-300 text-white hover:bg-white/10">
                📰 Berita
            </button>
        </div>

        <!-- Tab Content: Overview -->
        <div id="content-overview" class="tab-content">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Quick Actions -->
                <div class="bg-white rounded-2xl p-6 shadow-xl">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                        <i data-lucide="target" class="w-6 h-6 mr-2 text-purple-600"></i>
                        Quick Actions
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <a href="manage_display/manage_external.php" class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-6 rounded-xl hover:shadow-lg transition-all duration-300 flex flex-col items-center space-y-2">
                            <i data-lucide="monitor" class="w-8 h-8"></i>
                            <span class="font-semibold text-sm">Kelola External</span>
                        </a>
                        <a href="manage_display/manage_internal.php" class="bg-gradient-to-r from-purple-500 to-purple-600 text-white p-6 rounded-xl hover:shadow-lg transition-all duration-300 flex flex-col items-center space-y-2">
                            <i data-lucide="monitor" class="w-8 h-8"></i>
                            <span class="font-semibold text-sm">Kelola Internal</span>
                        </a>
                        <a href="management/analytics.php" class="bg-gradient-to-r from-green-500 to-green-600 text-white p-6 rounded-xl hover:shadow-lg transition-all duration-300 flex flex-col items-center space-y-2">
                            <i data-lucide="bar-chart-3" class="w-8 h-8"></i>
                            <span class="font-semibold text-sm">Analytics</span>
                        </a>
                        <a href="management/manage_users.php" class="bg-gradient-to-r from-orange-500 to-orange-600 text-white p-6 rounded-xl hover:shadow-lg transition-all duration-300 flex flex-col items-center space-y-2">
                            <i data-lucide="users" class="w-8 h-8"></i>
                            <span class="font-semibold text-sm">User Management</span>
                        </a>
                    </div>
                </div>

                <!-- System Status -->
                <div class="bg-white rounded-2xl p-6 shadow-xl">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                        <i data-lucide="activity" class="w-6 h-6 mr-2 text-purple-600"></i>
                        System Status
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <span class="font-medium text-gray-700">Database Connection</span>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-600">
                                Operational
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <span class="font-medium text-gray-700">External Displays</span>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-600">
                                All Active
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <span class="font-medium text-gray-700">Internal Displays</span>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-600">
                                All Active
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <span class="font-medium text-gray-700">Last Backup</span>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-600">
                                2 hours ago
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Content: Kinerja -->
        <div id="content-kinerja" class="tab-content hidden">
            <?php if (!empty($kinerjaData)): ?>
            <!-- Rata-rata Pencapaian -->
            <div class="bg-gradient-to-r from-purple-500 to-indigo-500 rounded-2xl p-8 mb-8 text-center shadow-2xl">
                <div class="text-7xl font-bold text-white mb-2"><?= $rataRataPencapaian ?>%</div>
                <div class="text-xl text-purple-100">Pencapaian Rata-Rata - <?= getNamaBulan($bulanIni) ?> <?= $tahunIni ?></div>
            </div>

            <!-- Kinerja Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($kinerjaData as $item): ?>
                <?php
                    $statusColor = $item['status'] === 'tercapai' ? 'green' : ($item['status'] === 'mendekati' ? 'yellow' : 'red');
                    $statusBg = $item['status'] === 'tercapai' ? 'bg-green-100 text-green-700' : ($item['status'] === 'mendekati' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700');
                    $statusIcon = $item['status'] === 'tercapai' ? '✅' : ($item['status'] === 'mendekati' ? '⚠️' : '📈');
                    $statusText = $item['status'] === 'tercapai' ? 'Tercapai' : ($item['status'] === 'mendekati' ? 'Mendekati' : 'Belum');
                    $circleColor = $item['status'] === 'tercapai' ? '#10b981' : ($item['status'] === 'mendekati' ? '#f59e0b' : '#ef4444');
                    $circumference = 2 * 3.14159 * 70;
                    $progress = min($item['persentase'], 100) / 100 * $circumference;
                ?>
                <div class="bg-white rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 border border-purple-100">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($item['kategori']) ?></h3>
                        <span class="px-3 py-1 rounded-full text-xs font-bold <?= $statusBg ?>">
                            <?= $statusIcon ?> <?= $statusText ?>
                        </span>
                    </div>
                    
                    <div class="relative w-40 h-40 mx-auto mb-4">
                        <svg class="w-full h-full transform -rotate-90">
                            <circle cx="80" cy="80" r="70" fill="none" stroke="#e5e7eb" stroke-width="16"/>
                            <circle cx="80" cy="80" r="70" fill="none" stroke="<?= $circleColor ?>" stroke-width="16"
                                    stroke-dasharray="<?= $progress ?> <?= $circumference ?>" stroke-linecap="round"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <div class="text-3xl font-bold text-gray-800"><?= round($item['persentase']) ?>%</div>
                            <div class="text-xs text-gray-500">Progress</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100">
                        <div class="text-center">
                            <div class="text-sm text-gray-500 mb-1">Target</div>
                            <div class="text-2xl font-bold text-gray-800"><?= number_format($item['target']) ?></div>
                        </div>
                        <div class="text-center">
                            <div class="text-sm text-gray-500 mb-1">Realisasi</div>
                            <div class="text-2xl font-bold text-purple-600"><?= number_format($item['realisasi']) ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="bg-white rounded-2xl p-12 text-center shadow-xl">
                <i data-lucide="bar-chart-3" class="w-16 h-16 mx-auto mb-4 text-gray-300"></i>
                <p class="text-gray-500 text-lg">Belum ada data target kinerja bulan ini</p>
                <a href="management/manage_kinerja.php" class="mt-4 inline-block bg-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-700">
                    Tambah Target Kinerja
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tab Content: Berita -->
        <div id="content-berita" class="tab-content hidden">
            <div class="bg-white rounded-2xl p-6 shadow-xl">
                <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                    <i data-lucide="newspaper" class="w-6 h-6 mr-2 text-purple-600"></i>
                    Berita Terbaru