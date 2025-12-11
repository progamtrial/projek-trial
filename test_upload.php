<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Upload Path</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { 
            color: #667eea; 
            margin-bottom: 20px;
        }
        .info {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-family: monospace;
        }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
        }
        .test-section h3 {
            color: #333;
            margin-bottom: 15px;
        }
        .btn {
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .btn:hover {
            background: #5568d3;
        }
        .result {
            margin-top: 15px;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .path-list {
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .path-list code {
            display: block;
            padding: 5px;
            margin: 3px 0;
            background: white;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Test Upload Path Finder</h1>
        
        <div class="info">
            <strong>File ini berada di:</strong><br>
            <?php echo __FILE__; ?><br><br>
            <strong>Directory:</strong><br>
            <?php echo __DIR__; ?>
        </div>

        <div class="test-section">
            <h3>📍 Informasi Path</h3>
            <div class="path-list">
                <strong>Kemungkinan lokasi upload_handler.php:</strong>
                <?php
                // Dapatkan struktur folder
                $currentFile = __FILE__;
                $currentDir = __DIR__;
                
                // Cek apakah file ini ada di subfolder
                $pathParts = explode(DIRECTORY_SEPARATOR, $currentDir);
                $docRoot = $_SERVER['DOCUMENT_ROOT'];
                
                echo "<code>1. " . $docRoot . "/upload_handler.php</code>";
                echo "<code>2. " . dirname($currentDir) . "/upload_handler.php</code>";
                echo "<code>3. " . $currentDir . "/upload_handler.php</code>";
                echo "<code>4. " . $currentDir . "/../upload_handler.php</code>";
                
                // Cek mana yang ada
                echo "<br><br><strong>File yang ADA:</strong>";
                $paths = [
                    $docRoot . "/upload_handler.php",
                    dirname($currentDir) . "/upload_handler.php",
                    $currentDir . "/upload_handler.php",
                    $currentDir . "/../upload_handler.php"
                ];
                
                $found = false;
                foreach ($paths as $path) {
                    $realPath = realpath($path);
                    if ($realPath && file_exists($realPath)) {
                        echo "<code style='background: #d4edda; color: #155724;'>✅ " . $path . "</code>";
                        $found = true;
                    }
                }
                
                if (!$found) {
                    echo "<code style='background: #f8d7da; color: #721c24;'>❌ upload_handler.php TIDAK DITEMUKAN!</code>";
                }
                ?>
            </div>
        </div>

        <div class="test-section">
            <h3>🧪 Test Fetch ke Berbagai Path</h3>
            <p style="margin-bottom: 15px;">Klik tombol di bawah untuk test fetch ke berbagai kemungkinan path:</p>
            
            <button class="btn" onclick="testPath('./upload_handler.php')">Test: ./upload_handler.php</button>
            <button class="btn" onclick="testPath('../upload_handler.php')">Test: ../upload_handler.php</button>
            <button class="btn" onclick="testPath('/upload_handler.php')">Test: /upload_handler.php</button>
            <button class="btn" onclick="testPath('<?php echo basename(dirname($currentDir)); ?>/upload_handler.php')">
                Test: /<?php echo basename(dirname($currentDir)); ?>/upload_handler.php
            </button>
            
            <div id="testResult"></div>
        </div>

        <div class="test-section">
            <h3>📁 Struktur Folder</h3>
            <div class="path-list">
                <?php
                // Tampilkan struktur folder
                function listDirectory($dir, $level = 0) {
                    $items = @scandir($dir);
                    if (!$items) return;
                    
                    foreach ($items as $item) {
                        if ($item === '.' || $item === '..') continue;
                        
                        $path = $dir . DIRECTORY_SEPARATOR . $item;
                        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
                        
                        if (is_dir($path) && $level < 2) {
                            echo "<code>{$indent}📁 {$item}/</code>";
                            listDirectory($path, $level + 1);
                        } elseif (is_file($path) && pathinfo($item, PATHINFO_EXTENSION) === 'php') {
                            echo "<code>{$indent}📄 {$item}</code>";
                        }
                    }
                }
                
                // List dari parent directory
                $parentDir = dirname($currentDir);
                echo "<strong>Struktur dari: " . basename($parentDir) . "/</strong><br>";
                listDirectory($parentDir);
                ?>
            </div>
        </div>

        <div class="test-section">
            <h3>🔧 Solusi</h3>
            <div class="path-list">
                <p><strong>Langkah-langkah perbaikan:</strong></p>
                <ol style="margin-left: 20px; line-height: 1.8;">
                    <li>Pastikan file <code>upload_handler.php</code> ada di folder root proyek</li>
                    <li>Gunakan path yang berhasil di test di atas</li>
                    <li>Update JavaScript di manage_external.php dengan path yang benar</li>
                </ol>
            </div>
        </div>
    </div>

    <script>
        async function testPath(path) {
            const resultDiv = document.getElementById('testResult');
            resultDiv.innerHTML = '<div class="result">Testing path: ' + path + '...</div>';
            
            try {
                const response = await fetch(path, {
                    method: 'GET', // Test dengan GET dulu
                });
                
                const text = await response.text();
                
                let resultClass = 'success';
                let resultText = '✅ Path DITEMUKAN!\n\n';
                resultText += 'Status: ' + response.status + '\n';
                resultText += 'Response (200 karakter pertama):\n' + text.substring(0, 200);
                
                if (text.includes('404') || text.includes('Not Found')) {
                    resultClass = 'error';
                    resultText = '❌ Path TIDAK DITEMUKAN (404)\n\nGunakan path lain!';
                }
                
                resultDiv.innerHTML = '<div class="result ' + resultClass + '">' + resultText + '</div>';
                
            } catch (error) {
                resultDiv.innerHTML = '<div class="result error">❌ Error: ' + error.message + '</div>';
            }
        }
    </script>
</body>
</html>