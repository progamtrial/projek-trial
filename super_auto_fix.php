<?php
/**
 * SUPER AUTO FIX - One Click Fix Everything
 * Path: super_auto_fix.php (di root folder)
 * Akses: http://localhost/digital-balmon2/super_auto_fix.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$results = [];
$errors = [];

// Jalankan fix jika tombol diklik
if (isset($_POST['run_fix'])) {
    
    // ==========================================
    // FIX 1: Create/Check uploads folder
    // ==========================================
    $results[] = "=== FIX 1: UPLOADS FOLDER ===";
    
    $uploadsDir = __DIR__ . '/uploads/';
    
    if (!file_exists($uploadsDir)) {
        if (mkdir($uploadsDir, 0777, true)) {
            chmod($uploadsDir, 0777);
            $results[] = "✅ Folder uploads/ berhasil dibuat";
        } else {
            $errors[] = "❌ Gagal membuat folder uploads/";
        }
    } else {
        $results[] = "ℹ️ Folder uploads/ sudah ada";
    }
    
    // Check writable
    if (is_writable($uploadsDir)) {
        $results[] = "✅ Folder uploads/ WRITABLE";
        
        // Test write
        $testFile = $uploadsDir . 'test_' . time() . '.txt';
        if (file_put_contents($testFile, 'test')) {
            $results[] = "✅ Test write: BERHASIL";
            unlink($testFile);
        } else {
            $errors[] = "❌ Test write: GAGAL";
        }
    } else {
        $errors[] = "❌ Folder uploads/ TIDAK WRITABLE";
        
        // Try fix permission
        if (chmod($uploadsDir, 0777)) {
            $results[] = "✅ Permission diubah ke 0777";
        } else {
            $errors[] = "❌ Gagal ubah permission";
        }
    }
    
    // ==========================================
    // FIX 2: Create uploads/.htaccess
    // ==========================================
    $results[] = "\n=== FIX 2: SECURITY .HTACCESS ===";
    
    $htaccessPath = $uploadsDir . '.htaccess';
    $htaccessContent = <<<'HTACCESS'
# Prevent directory listing
Options -Indexes

# Prevent PHP execution
<FilesMatch "\.php$">
    Require all denied
</FilesMatch>

# Allow only media files
<FilesMatch "\.(jpg|jpeg|png|gif|webp|mp4|webm|ogg)$">
    Require all granted
</FilesMatch>

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
</IfModule>
HTACCESS;
    
    if (file_put_contents($htaccessPath, $htaccessContent)) {
        $results[] = "✅ uploads/.htaccess berhasil dibuat";
    } else {
        $errors[] = "❌ Gagal membuat uploads/.htaccess";
    }
    
    // ==========================================
    // FIX 3: Create manage_display/upload_handler.php
    // ==========================================
    $results[] = "\n=== FIX 3: UPLOAD HANDLER ===";
    
    $handlerDir = __DIR__ . '/manage_display/';
    if (!file_exists($handlerDir)) {
        mkdir($handlerDir, 0755, true);
    }
    
    $handlerPath = $handlerDir . 'upload_handler.php';
    $handlerContent = <<<'PHP'
<?php
/**
 * UPLOAD HANDLER - Digital Signage
 * Auto-created by super_auto_fix.php
 */

error_reporting(0);
ini_set('display_errors', 0);

while (ob_get_level()) ob_end_clean();
ob_start();

header('Content-Type: application/json; charset=utf-8');

function sendJSON($success, $message, $data = null) {
    while (ob_get_level()) ob_end_clean();
    $response = ['success' => $success, 'message' => $message];
    if ($data) $response['data'] = $data;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    session_start();
    if (!isset($_SESSION['admin_id'])) {
        sendJSON(false, 'Login required');
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJSON(false, 'Method must be POST');
    }
    
    require_once '../config.php';
    $conn = getConnection();
    
    // Validate fields
    $required = ['judul', 'tipe_layar', 'nomor_layar', 'durasi'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            sendJSON(false, "Field '$field' wajib diisi");
        }
    }
    
    $judul = trim($_POST['judul']);
    $deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';
    $tipeLayar = trim($_POST['tipe_layar']);
    $nomorLayar = (int)$_POST['nomor_layar'];
    $durasi = (int)$_POST['durasi'];
    $urutan = isset($_POST['urutan']) ? (int)$_POST['urutan'] : 0;
    
    if (!in_array($tipeLayar, ['external', 'internal'])) {
        sendJSON(false, 'Tipe layar tidak valid');
    }
    
    // Check file
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        sendJSON(false, 'File upload error');
    }
    
    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'ogg'];
    if (!in_array($ext, $allowedExt)) {
        sendJSON(false, 'Format tidak didukung');
    }
    
    // Check size (300MB)
    if ($file['size'] > 200 * 1024 * 1024) {
        sendJSON(false, 'File terlalu besar (max 300MB)');
    }
    
    // Create uploads dir
    $uploadDir = '../uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate filename
    $newFilename = bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
    $uploadPath = $uploadDir . $newFilename;
    
    // Move file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        sendJSON(false, 'Gagal move file');
    }
    
    // Determine type
    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    
    // Insert to DB
    $createdBy = $_SESSION['admin_id'];
    
    if ($isImage) {
        $stmt = $conn->prepare(
            "INSERT INTO konten_layar 
            (tipe_layar, nomor_layar, judul, deskripsi, gambar, durasi, urutan, status, created_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'aktif', ?, NOW())"
        );
        $stmt->bind_param("sissiii", $tipeLayar, $nomorLayar, $judul, $deskripsi, $newFilename, $durasi, $urutan, $createdBy);
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO konten_layar 
            (tipe_layar, nomor_layar, judul, deskripsi, video, durasi, urutan, status, created_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'aktif', ?, NOW())"
        );
        $stmt->bind_param("sissiiii", $tipeLayar, $nomorLayar, $judul, $deskripsi, $newFilename, $durasi, $urutan, $createdBy);
    }
    
    if (!$stmt->execute()) {
        @unlink($uploadPath);
        sendJSON(false, 'Gagal save ke database');
    }
    
    $insertId = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    
    sendJSON(true, 'Upload berhasil!', [
        'id' => $insertId,
        'filename' => $newFilename
    ]);
    
} catch (Exception $e) {
    sendJSON(false, 'Error: ' . $e->getMessage());
}
?>
PHP;
    
    if (file_put_contents($handlerPath, $handlerContent)) {
        $results[] = "✅ upload_handler.php berhasil dibuat";
        $results[] = "   Path: manage_display/upload_handler.php";
    } else {
        $errors[] = "❌ Gagal membuat upload_handler.php";
    }
    
    // ==========================================
    // FIX 4: Check PHP Settings
    // ==========================================
    $results[] = "\n=== FIX 4: PHP SETTINGS ===";
    
    $uploadMax = ini_get('upload_max_filesize');
    $postMax = ini_get('post_max_size');
    $memoryLimit = ini_get('memory_limit');
    
    $results[] = "upload_max_filesize: $uploadMax";
    $results[] = "post_max_size: $postMax";
    $results[] = "memory_limit: $memoryLimit";
    
    function parseSize($size) {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }
        return round($size);
    }
    
    $uploadBytes = parseSize($uploadMax);
    $targetSize = 200 * 1024 * 1024; // 300MB
    
    if ($uploadBytes < $targetSize) {
        $errors[] = "⚠️ upload_max_filesize terlalu kecil (current: $uploadMax, need: 200M)";
        $results[] = "\n📋 CARA FIX PHP SETTINGS:";
        $results[] = "1. Edit C:\\xampp\\php\\php.ini";
        $results[] = "2. Cari: upload_max_filesize";
        $results[] = "3. Ubah jadi: upload_max_filesize = 200M";
        $results[] = "4. Cari: post_max_size";
        $results[] = "5. Ubah jadi: post_max_size = 210M";
        $results[] = "6. Save dan restart Apache";
    } else {
        $results[] = "✅ PHP settings OK";
    }
    
    // ==========================================
    // FIX 5: Check Database
    // ==========================================
    $results[] = "\n=== FIX 5: DATABASE ===";
    
    try {
        require_once 'config.php';
        $conn = getConnection();
        $results[] = "✅ Database connection: OK";
        
        $tableCheck = $conn->query("SHOW TABLES LIKE 'konten_layar'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            $results[] = "✅ Table konten_layar: EXISTS";
            
            // Check video column
            $colCheck = $conn->query("SHOW COLUMNS FROM konten_layar LIKE 'video'");
            if ($colCheck && $colCheck->num_rows > 0) {
                $results[] = "✅ Column 'video': EXISTS";
            } else {
                $results[] = "⚠️ Column 'video': NOT EXISTS (optional)";
            }
        } else {
            $errors[] = "❌ Table konten_layar: NOT EXISTS";
        }
        
        $conn->close();
    } catch (Exception $e) {
        $errors[] = "❌ Database error: " . $e->getMessage();
    }
    
    // ==========================================
    // SUMMARY
    // ==========================================
    $results[] = "\n=== SUMMARY ===";
    $results[] = "Total fixes: " . count($results);
    $results[] = "Errors: " . count($errors);
    
    if (count($errors) === 0) {
        $results[] = "\n✅ ALL FIXES COMPLETED SUCCESSFULLY!";
        $results[] = "System ready untuk upload.";
    } else {
        $results[] = "\n⚠️ BEBERAPA ERROR PERLU DIPERBAIKI MANUAL:";
        $results[] = "Lihat daftar error di atas.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Auto Fix - Upload System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #252526;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        h1 {
            color: #4ec9b0;
            margin-bottom: 20px;
            font-size: 28px;
            text-align: center;
        }
        .warning {
            background: #5a4e1e;
            border-left: 4px solid #dcdcaa;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .warning h3 {
            color: #dcdcaa;
            margin-bottom: 15px;
        }
        .warning ul {
            margin-left: 20px;
            color: #d4d4d4;
        }
        .result-box {
            background: #1e1e1e;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 13px;
            white-space: pre-wrap;
            max-height: 600px;
            overflow-y: auto;
        }
        .error-box {
            background: #5a1e1e;
            border-left: 4px solid #f44747;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .error-box h3 {
            color: #f44747;
            margin-bottom: 15px;
        }
        .success-box {
            background: #1e5a1e;
            border-left: 4px solid #4ec9b0;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success-box h3 {
            color: #4ec9b0;
            margin-bottom: 15px;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 15px;
            background: #0e639c;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Segoe UI', sans-serif;
            margin-bottom: 10px;
        }
        .btn:hover {
            background: #1177bb;
        }
        .btn-success {
            background: #388e3c;
        }
        .btn-secondary {
            background: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 SUPER AUTO FIX - Upload System</h1>
        
        <?php if (empty($results) && empty($errors)): ?>
        
        <div class="warning">
            <h3>⚡ One-Click Fix Everything</h3>
            <ul>
                <li>✅ Create/check uploads/ folder</li>
                <li>✅ Create uploads/.htaccess (security)</li>
                <li>✅ Create manage_display/upload_handler.php</li>
                <li>✅ Check PHP settings (upload_max_filesize)</li>
                <li>✅ Check database connection</li>
            </ul>
        </div>
        
        <form method="POST">
            <button type="submit" name="run_fix" class="btn" onclick="this.disabled=true; this.textContent='⏳ Running...';">
                🚀 RUN SUPER AUTO FIX
            </button>
        </form>
        
        <a href="check_upload.php" class="btn btn-secondary">
            🔍 Run Diagnostic First
        </a>
        
        <?php else: ?>
        
        <?php if (count($errors) === 0): ?>
        <div class="success-box">
            <h3>✅ SUCCESS - ALL FIXED!</h3>
            <p>Upload system siap digunakan.</p>
        </div>
        <?php else: ?>
        <div class="error-box">
            <h3>⚠️ BEBERAPA ERROR PERLU MANUAL FIX</h3>
            <p>Lihat detail di bawah.</p>
        </div>
        <?php endif; ?>
        
        <div class="result-box"><?= implode("\n", $results) ?></div>
        
        <?php if (!empty($errors)): ?>
        <div class="error-box">
            <h3>❌ ERRORS:</h3>
            <div class="result-box"><?= implode("\n", $errors) ?></div>
        </div>
        <?php endif; ?>
        
        <a href="manage_display/manage_external.php" class="btn btn-success">
            📺 Test Upload Sekarang
        </a>
        
        <a href="check_upload.php" class="btn btn-secondary">
            🔍 Run Full Diagnostic
        </a>
        
        <button onclick="location.reload()" class="btn btn-secondary">
            🔄 Run Fix Again
        </button>
        
        <?php endif; ?>
    </div>
</body>
</html>