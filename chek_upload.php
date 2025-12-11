<?php
/**
 * UPLOAD DIAGNOSTIC TOOL
 * Cek semua masalah upload
 * Save as: check_upload.php di root folder
 * Akses: http://localhost/digital-signage/check_upload.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$results = [];
$errors = [];
$warnings = [];

// ==========================================
// 1. CHECK PHP UPLOAD SETTINGS
// ==========================================
$results[] = "=== 1. PHP UPLOAD SETTINGS ===";

$upload_max = ini_get('upload_max_filesize');
$post_max = ini_get('post_max_size');
$memory_limit = ini_get('memory_limit');
$max_execution = ini_get('max_execution_time');

$results[] = "upload_max_filesize: $upload_max";
$results[] = "post_max_size: $post_max";
$results[] = "memory_limit: $memory_limit";
$results[] = "max_execution_time: $max_execution";

// Convert to bytes for comparison
function parseSize($size) {
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
    $size = preg_replace('/[^0-9\.]/', '', $size);
    if ($unit) {
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    }
    return round($size);
}

$upload_bytes = parseSize($upload_max);
$post_bytes = parseSize($post_max);
$target_size = 200 * 1024 * 1024; // 300MB

if ($upload_bytes < $target_size) {
    $errors[] = "❌ upload_max_filesize terlalu kecil! Target: 200M, Current: $upload_max";
} else {
    $results[] = "✅ upload_max_filesize OK";
}

if ($post_bytes < $target_size) {
    $errors[] = "❌ post_max_size terlalu kecil! Target: 210M, Current: $post_max";
} else {
    $results[] = "✅ post_max_size OK";
}

// ==========================================
// 2. CHECK FOLDER UPLOADS
// ==========================================
$results[] = "\n=== 2. FOLDER UPLOADS ===";

$uploadDir = __DIR__ . '/uploads/';
$results[] = "Upload dir path: $uploadDir";

if (!file_exists($uploadDir)) {
    $errors[] = "❌ Folder uploads TIDAK ADA!";
    if (mkdir($uploadDir, 0777, true)) {
        $results[] = "✅ Folder uploads berhasil dibuat";
    } else {
        $errors[] = "❌ Gagal membuat folder uploads";
    }
} else {
    $results[] = "✅ Folder uploads ADA";
}

// Check writable
if (is_writable($uploadDir)) {
    $results[] = "✅ Folder uploads WRITABLE";
    
    // Test write
    $testFile = $uploadDir . 'test_' . time() . '.txt';
    if (file_put_contents($testFile, 'test')) {
        $results[] = "✅ Test write file: BERHASIL";
        unlink($testFile);
    } else {
        $errors[] = "❌ Test write file: GAGAL";
    }
} else {
    $errors[] = "❌ Folder uploads TIDAK WRITABLE!";
    
    // Try fix permission
    if (chmod($uploadDir, 0777)) {
        $warnings[] = "⚠️ Permission diubah ke 0777. Coba lagi.";
    }
}

// Check permission
$perms = substr(sprintf('%o', fileperms($uploadDir)), -4);
$results[] = "Permission: $perms";

if ($perms != '0777' && $perms != '0755') {
    $warnings[] = "⚠️ Permission tidak optimal. Recommended: 0755 atau 0777";
}

// ==========================================
// 3. CHECK FILE UPLOAD_HANDLER.PHP
// ==========================================
$results[] = "\n=== 3. CHECK UPLOAD HANDLER ===";

$handlerPaths = [
    __DIR__ . '/upload_handler.php',
    __DIR__ . '/manage_display/upload_handler.php'
];

$handlerFound = false;
foreach ($handlerPaths as $path) {
    if (file_exists($path)) {
        $results[] = "✅ Found: $path";
        $handlerFound = true;
    } else {
        $results[] = "❌ Not found: $path";
    }
}

if (!$handlerFound) {
    $errors[] = "❌ upload_handler.php TIDAK DITEMUKAN!";
}

// ==========================================
// 4. CHECK .HTACCESS
// ==========================================
$results[] = "\n=== 4. CHECK .HTACCESS ===";

$htaccessPath = __DIR__ . '/.htaccess';
if (file_exists($htaccessPath)) {
    $results[] = "✅ .htaccess ADA";
    
    $content = file_get_contents($htaccessPath);
    
    // Check upload settings
    if (strpos($content, 'upload_max_filesize') !== false) {
        $results[] = "✅ .htaccess mengandung upload_max_filesize";
    } else {
        $warnings[] = "⚠️ .htaccess tidak mengandung upload_max_filesize";
    }
    
    if (strpos($content, 'post_max_size') !== false) {
        $results[] = "✅ .htaccess mengandung post_max_size";
    } else {
        $warnings[] = "⚠️ .htaccess tidak mengandung post_max_size";
    }
} else {
    $warnings[] = "⚠️ .htaccess TIDAK ADA di root";
}

// Check uploads/.htaccess
$uploadsHtaccess = $uploadDir . '.htaccess';
if (file_exists($uploadsHtaccess)) {
    $results[] = "✅ uploads/.htaccess ADA";
} else {
    $warnings[] = "⚠️ uploads/.htaccess TIDAK ADA (optional, tapi direkomendasikan)";
}

// ==========================================
// 5. CHECK PHP EXTENSIONS
// ==========================================
$results[] = "\n=== 5. CHECK PHP EXTENSIONS ===";

$required_extensions = [
    'gd' => 'Image processing',
    'fileinfo' => 'File type detection',
    'mysqli' => 'Database'
];

foreach ($required_extensions as $ext => $desc) {
    if (extension_loaded($ext)) {
        $results[] = "✅ $ext: LOADED ($desc)";
    } else {
        $errors[] = "❌ $ext: NOT LOADED ($desc)";
    }
}

// ==========================================
// 6. CHECK DATABASE
// ==========================================
$results[] = "\n=== 6. CHECK DATABASE ===";

if (file_exists('config.php')) {
    require_once 'config.php';
    
    try {
        $conn = getConnection();
        $results[] = "✅ Database connection: OK";
        
        // Check konten_layar table
        $tableCheck = $conn->query("SHOW TABLES LIKE 'konten_layar'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            $results[] = "✅ Table konten_layar: EXISTS";
            
            // Check columns
            $columns = $conn->query("SHOW COLUMNS FROM konten_layar")->fetch_all(MYSQLI_ASSOC);
            $columnNames = array_column($columns, 'Field');
            
            if (in_array('gambar', $columnNames)) {
                $results[] = "✅ Column 'gambar': EXISTS";
            } else {
                $errors[] = "❌ Column 'gambar': NOT EXISTS";
            }
            
            if (in_array('video', $columnNames)) {
                $results[] = "✅ Column 'video': EXISTS";
            } else {
                $warnings[] = "⚠️ Column 'video': NOT EXISTS (optional)";
            }
        } else {
            $errors[] = "❌ Table konten_layar: NOT EXISTS";
        }
        
        $conn->close();
    } catch (Exception $e) {
        $errors[] = "❌ Database error: " . $e->getMessage();
    }
} else {
    $errors[] = "❌ config.php NOT FOUND";
}

// ==========================================
// 7. SIMULATE UPLOAD TEST
// ==========================================
$results[] = "\n=== 7. UPLOAD CAPABILITY TEST ===";

$tempFile = $uploadDir . 'temp_test_' . time() . '.jpg';
$testData = str_repeat('x', 1024 * 1024); // 1MB test data

if (file_put_contents($tempFile, $testData)) {
    $results[] = "✅ Can write 1MB file";
    $fileSize = filesize($tempFile);
    $results[] = "✅ File size verified: " . number_format($fileSize) . " bytes";
    unlink($tempFile);
} else {
    $errors[] = "❌ Cannot write test file!";
}

// ==========================================
// SUMMARY
// ==========================================
$results[] = "\n=== SUMMARY ===";
$results[] = "Total checks: " . count($results);
$results[] = "Errors: " . count($errors);
$results[] = "Warnings: " . count($warnings);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Diagnostic</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
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
            font-size: 24px;
        }
        .result-box {
            background: #1e1e1e;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 13px;
            line-height: 1.8;
            overflow-x: auto;
        }
        .result-box pre {
            white-space: pre-wrap;
            margin: 0;
        }
        .error-box {
            background: #5a1e1e;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #f44747;
        }
        .warning-box {
            background: #5a4e1e;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #dcdcaa;
        }
        .fix-section {
            background: #1e3a5a;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
            border-left: 4px solid #4ec9b0;
        }
        .fix-section h3 {
            color: #4ec9b0;
            margin-bottom: 15px;
        }
        .fix-section ol {
            margin-left: 20px;
            line-height: 2;
        }
        .fix-section code {
            background: #2d2d30;
            padding: 2px 6px;
            border-radius: 3px;
            color: #ce9178;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #0e639c;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 10px 10px 0;
            font-weight: 600;
        }
        .btn:hover {
            background: #1177bb;
        }
        .btn-success {
            background: #388e3c;
        }
        .btn-danger {
            background: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 UPLOAD DIAGNOSTIC TOOL</h1>
        
        <?php if (!empty($errors)): ?>
        <div class="error-box">
            <h3 style="color: #f44747; margin-bottom: 10px;">❌ ERRORS FOUND (<?= count($errors) ?>)</h3>
            <?php foreach ($errors as $error): ?>
            <div><?= $error ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($warnings)): ?>
        <div class="warning-box">
            <h3 style="color: #dcdcaa; margin-bottom: 10px;">⚠️ WARNINGS (<?= count($warnings) ?>)</h3>
            <?php foreach ($warnings as $warning): ?>
            <div><?= $warning ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="result-box">
            <pre><?= implode("\n", $results) ?></pre>
        </div>
        
        <?php if (!empty($errors)): ?>
        <div class="fix-section">
            <h3>🔧 CARA MEMPERBAIKI</h3>
            <ol>
                <?php if (strpos(implode('', $errors), 'upload_max_filesize') !== false): ?>
                <li>
                    <strong>Fix upload_max_filesize:</strong><br>
                    Edit <code>C:\xampp\php\php.ini</code><br>
                    Ubah: <code>upload_max_filesize = 200M</code><br>
                    Restart Apache
                </li>
                <?php endif; ?>
                
                <?php if (strpos(implode('', $errors), 'post_max_size') !== false): ?>
                <li>
                    <strong>Fix post_max_size:</strong><br>
                    Edit <code>C:\xampp\php\php.ini</code><br>
                    Ubah: <code>post_max_size = 210M</code><br>
                    Restart Apache
                </li>
                <?php endif; ?>
                
                <?php if (strpos(implode('', $errors), 'Folder uploads TIDAK ADA') !== false): ?>
                <li>
                    <strong>Buat folder uploads:</strong><br>
                    <code>mkdir C:\xampp\htdocs\digital-signage\uploads</code>
                </li>
                <?php endif; ?>
                
                <?php if (strpos(implode('', $errors), 'TIDAK WRITABLE') !== false): ?>
                <li>
                    <strong>Fix permission uploads:</strong><br>
                    Klik kanan folder <code>uploads</code> → Properties → Security<br>
                    Beri Full Control ke Everyone
                </li>
                <?php endif; ?>
                
                <?php if (strpos(implode('', $errors), 'gd') !== false): ?>
                <li>
                    <strong>Aktifkan extension GD:</strong><br>
                    Edit <code>C:\xampp\php\php.ini</code><br>
                    Cari: <code>;extension=gd</code><br>
                    Hapus ; sehingga jadi: <code>extension=gd</code><br>
                    Restart Apache
                </li>
                <?php endif; ?>
                
                <?php if (strpos(implode('', $errors), 'upload_handler.php TIDAK DITEMUKAN') !== false): ?>
                <li>
                    <strong>Pastikan file upload_handler.php ada di root folder</strong><br>
                    Path: <code>C:\xampp\htdocs\digital-signage\upload_handler.php</code>
                </li>
                <?php endif; ?>
            </ol>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="javascript:location.reload()" class="btn">🔄 Refresh Check</a>
            <a href="dashboard_modern.php" class="btn btn-success">📊 Dashboard</a>
            <?php if (count($errors) == 0): ?>
            <a href="manage_display/manage_external.php" class="btn btn-success">✅ Test Upload</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>