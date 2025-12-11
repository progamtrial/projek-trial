<?php
/**
 * DASHBOARD DENGAN FITUR TARGET KINERJA TERINTEGRASI
 * SMFR Manado - Version 2.0
 */

if (file_exists('enhanced_config.php')) {
    require_once 'enhanced_config.php';
} else {
    require_once 'config.php';
}

requireLogin();
$conn = getConnection();
$currentUser = getCurrentUser();

// Handle AJAX for Target Kinerja
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'];
    $response = ['success' => false];
    
    try {
        if ($action === 'add_target') {
            $stmt = $conn->prepare("INSERT INTO target_kinerja (kategori, target, realisasi, satuan, bulan, tahun, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siiisii", $_POST['kategori'], $_POST['target'], $_POST['realisasi'], $_POST['satuan'], $_POST['bulan'], $_POST['tahun'], $_SESSION['admin_id']);
            $response['success'] = $stmt->execute();
            $stmt->close();
        } elseif ($action === 'update_realisasi') {
            $stmt = $conn->prepare("UPDATE target_kinerja SET realisasi = ? WHERE id = ?");
            $stmt->bind_param("ii", $_POST['realisasi'], $_POST['id']);
            $response['success'] = $stmt->execute();
            $stmt->close();
        } elseif ($action === 'delete_target') {
            $stmt = $conn->prepare("DELETE FROM target_kinerja WHERE id = ?");
            $stmt->bind_param("i", $_POST['id']);
            $response['success'] = $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

// Get Stats
$stats = [
    'external_active' => $conn->query("SELECT COUNT(*) as c FROM konten_layar WHERE tipe_layar='external' AND status='aktif'")->fetch_assoc()['c'],
    'internal_active' => $conn->query("SELECT COUNT(*) as c FROM konten_layar WHERE tipe_layar='internal' AND status='aktif'")->fetch_assoc()['c'],
    'total_content' => $conn->query("SELECT COUNT(*) as c FROM konten_layar")->fetch_assoc()['c']
];

// Get Target Kinerja
$bulanIni = date('n');
$tahunIni = date('Y');
$kinerjaData = [];
$rataRata = 0;

$hasTable = $conn->query("SHOW TABLES LIKE 'target_kinerja'")->num_rows > 0;
if ($hasTable) {
    $result = $conn->query("SELECT *, ROUND((realisasi / NULLIF(target, 0)) * 100, 2) as persentase FROM target_kinerja WHERE bulan = $bulanIni AND tahun = $tahunIni");
    $kinerjaData = $result->fetch_all(MYSQLI_ASSOC);
    if (count($kinerjaData) > 0) {
        $rataRata = round(array_sum(array_column($kinerjaData, 'persentase')) / count($kinerjaData), 2);
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - SMFR Manado</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        @keyframes slideUp { from { transform: translateY(50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-content { animation: slideUp 0.3s; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 min-h-screen">
    
    <!-- Header -->
    <header class="bg-gradient-to-r from-slate-900/90 to-purple-900/90 backdrop-blur-xl border-b border-white/10 py-6">
        <div class="max-w-7xl mx-auto px-6 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-purple-600 rounded-full flex items-center justify-center">
                    <span class="text-2xl">📡</span>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">SMFR Dashboard</h1>
                    <p class="text-purple-200">Balai Monitor Frekuensi Radio Kelas II Manado</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <div class="bg-white/5 px-4 py-2 rounded-xl border border-white/10">
                    <span class="text-white font-semibold"><?= htmlspecialchars($currentUser['nama']) ?></span>
                </div>
                <a href="auth/logout.php" class="bg-red-500/20 hover:bg-red-500/30 text-red-300 px-4 py-2 rounded-xl font-semibold">Logout</a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-8">
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white/5 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                <div class="text-4xl font-bold text-white mb-2"><?= $stats['external_active'] ?></div>
                <div class="text-blue-200">External Display Aktif</div>
            </div>
            <div class="bg-white/5 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                <div class="text-4xl font-bold text-white mb-2"><?= $stats['internal_active'] ?></div>
                <div class="text-purple-200">Internal Display Aktif</div>
            </div>
            <div class="bg-white/5 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                <div class="text-4xl font-bold text-white mb-2"><?= $stats['total_content'] ?></div>
                <div class="text-pink-200">Total Konten</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white/5 backdrop-blur-xl rounded-2xl p-6 border border-white/10 mb-8">
            <h2 class="text-2xl font-bold text-white mb-4">Quick Actions</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="manage_display/manage_external.php" class="bg-blue-500/20 hover:bg-blue-500/30 p-6 rounded-xl border border-blue-500/30 text-center text-white font-semibold">
                    📺 External
                </a>
                <a href="manage_display/manage_internal.php" class="bg-purple-500/20 hover:bg-purple-500/30 p-6 rounded-xl border border-purple-500/30 text-center text-white font-semibold">
                    🖥️ Internal
                </a>
                <a href="management/analytics.php" class="bg-green-500/20 hover:bg-green-500/30 p-6 rounded-xl border border-green-500/30 text-center text-white font-semibold">
                    📊 Analytics
                </a>
                <a href="management/manage_users.php" class="bg-orange-500/20 hover:bg-orange-500/30 p-6 rounded-xl border border-orange-500/30 text-center text-white font-semibold">
                    👥 Users
                </a>
            </div>
        </div>

        <!-- TARGET KINERJA SECTION -->
        <?php if ($hasTable): ?>
        <div class="bg-white/5 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-white">🎯 Target Kinerja - <?= date('F Y') ?></h2>
                <button onclick="openAddModal()" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg font-semibold">
                    ➕ Tambah Target
                </button>
            </div>

            <!-- Rata-rata Pencapaian -->
            <div class="text-center mb-8">
                <div class="text-7xl font-bold text-white mb-2"><?= $rataRata ?>%</div>
                <div class="text-xl text-purple-200">Pencapaian Rata-Rata</div>
            </div>

            <!-- Daftar Target -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="kinerjaList">
                <?php foreach ($kinerjaData as $item): ?>
                <div class="bg-white/10 rounded-xl p-5 border border-white/20" id="kinerja-<?= $item['id'] ?>">
                    <div class="flex justify-between items-start mb-3">
                        <h3 class="text-lg font-bold text-white"><?= htmlspecialchars($item['kategori']) ?></h3>
                        <button onclick="deleteTarget(<?= $item['id'] ?>)" class="text-red-400 hover:text-red-300">🗑️</button>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-3">
                        <div>
                            <div class="text-white/60 text-sm">Target</div>
                            <div class="text-2xl font-bold text-white"><?= number_format($item['target']) ?> <?= $item['satuan'] ?></div>
                        </div>
                        <div>
                            <div class="text-white/60 text-sm">Realisasi</div>
                            <input type="number" value="<?= $item['realisasi'] ?>" 
                                   onchange="updateRealisasi(<?= $item['id'] ?>, this.value)"
                                   class="w-full bg-white/10 text-2xl font-bold text-white border border-white/20 rounded px-2 py-1">
                        </div>
                    </div>
                    
                    <div class="h-8 bg-white/20 rounded-full overflow-hidden mb-2">
                        <div class="h-full bg-gradient-to-r from-green-500 to-blue-500 flex items-center justify-center text-white font-bold text-sm"
                             style="width: <?= min($item['persentase'], 100) ?>%">
                            <?= round($item['persentase']) ?>%
                        </div>
                    </div>
                    
                    <div class="text-right">
                        <?php if ($item['persentase'] >= 100): ?>
                            <span class="px-3 py-1 bg-green-500/30 text-green-300 rounded-full text-sm font-semibold">✅ Tercapai</span>
                        <?php elseif ($item['persentase'] >= 80): ?>
                            <span class="px-3 py-1 bg-yellow-500/30 text-yellow-300 rounded-full text-sm font-semibold">⚠️ Mendekati</span>
                        <?php else: ?>
                            <span class="px-3 py-1 bg-red-500/30 text-red-300 rounded-full text-sm font-semibold">📈 Belum</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($kinerjaData)): ?>
            <div class="text-center py-12 text-white/60">
                <div class="text-6xl mb-4">📊</div>
                <p class="text-xl">Belum ada target kinerja untuk bulan ini</p>
                <button onclick="openAddModal()" class="mt-6 bg-yellow-500 hover:bg-yellow-600 text-white px-8 py-3 rounded-lg font-semibold">
                    ➕ Tambah Target Pertama
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Display Preview Links -->
        <div class="mt-8 bg-white/5 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
            <h2 class="text-2xl font-bold text-white mb-4">👁️ Preview Display</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <?php for ($i = 1; $i <= 4; $i++): ?>
                <a href="display/display_external.php<?= $i > 1 ? '?nomor=' . $i : '' ?>" target="_blank" 
                   class="bg-blue-500/20 hover:bg-blue-500/30 p-4 rounded-xl border border-blue-500/30 text-center text-white">
                    📺 External <?= $i ?>
                </a>
                <?php endfor; ?>
                <?php for ($i = 1; $i <= 3; $i++): ?>
                <a href="display/display_internal.php?nomor=<?= $i ?>" target="_blank" 
                   class="bg-purple-500/20 hover:bg-purple-500/30 p-4 rounded-xl border border-purple-500/30 text-center text-white">
                    🖥️ Internal <?= $i ?>
                </a>
                <?php endfor; ?>
            </div>
        </div>
    </main>

    <!-- Modal Tambah Target -->
    <div id="addModal" class="modal">
        <div class="modal-content bg-white rounded-2xl p-8 max-w-2xl w-full mx-4">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-gray-800">➕ Tambah Target Kinerja</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-3xl">×</button>
            </div>
            
            <form id="addForm" onsubmit="submitTarget(event)" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Kategori / Nama Target *</label>
                        <input type="text" name="kategori" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Satuan *</label>
                        <input type="text" name="satuan" required placeholder="stasiun, izin, inspeksi" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Target *</label>
                        <input type="number" name="target" required min="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Realisasi Awal</label>
                        <input type="number" name="realisasi" value="0" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Bulan</label>
                        <input type="number" name="bulan" value="<?= $bulanIni ?>" min="1" max="12" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tahun</label>
                        <input type="number" name="tahun" value="<?= $tahunIni ?>" min="2020" max="2030" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:opacity-90">
                        💾 Simpan Target
                    </button>
                    <button type="button" onclick="closeModal()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('addModal').classList.remove('active');
            document.getElementById('addForm').reset();
        }
        
        async function submitTarget(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('ajax_action', 'add_target');
            
            const res = await fetch('', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                alert('✅ Target berhasil ditambahkan!');
                location.reload();
            } else {
                alert('❌ Gagal menambahkan target');
            }
        }
        
        async function updateRealisasi(id, value) {
            const formData = new FormData();
            formData.append('ajax_action', 'update_realisasi');
            formData.append('id', id);
            formData.append('realisasi', value);
            
            const res = await fetch('', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                location.reload();
            }
        }
        
        async function deleteTarget(id) {
            if (!confirm('Yakin hapus target ini?')) return;
            
            const formData = new FormData();
            formData.append('ajax_action', 'delete_target');
            formData.append('id', id);
            
            const res = await fetch('', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                document.getElementById('kinerja-' + id).remove();
                location.reload();
            }
        }
    </script>
</body>
</html>