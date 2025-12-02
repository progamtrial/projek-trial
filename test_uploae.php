<?php
/**
 * SIMPLE TEST UPLOAD - Tanpa Dependency
 * Path: manage_display/test_upload_simple.php
 * 
 * Akses: http://localhost/digital-signage/manage_display/test_upload_simple.php
 */

// Turn off display errors untuk clean response
ini_set('display_errors', 0);
error_reporting(0);

// Start session
session_start();

// Simulasi login (remove this di production)
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['admin_id'] = 1;
    $_SESSION['admin_username'] = 'test';
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Upload Simple</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border 0.3s;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }
        .file-input-label {
            display: block;
            padding: 15px;
            background: #f8f9fa;
            border: 2px dashed #667eea;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-input-label:hover {
            background: #667eea;
            color: white;
        }
        .file-name {
            margin-top: 10px;
            padding: 10px;
            background: #e3f2fd;
            border-radius: 5px;
            font-size: 13px;
            display: none;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        #result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            display: none;
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
        .loading {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .debug-log {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 12px;
            font-family: monospace;
            max-height: 300px;
            overflow-y: auto;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Upload Simple</h1>
        <p class="subtitle">Test upload tanpa dependency - untuk debugging</p>
        
        <div class="info-box">
            <strong>ℹ️ Info:</strong> Form ini akan langsung upload ke handler test. 
            Lihat console browser (F12) untuk debug detail.
        </div>
        
        <form id="uploadForm" enctype="multipart/form-data">
            <div class="form-group">
                <label>Tipe Layar</label>
                <select name="tipe_layar" required>
                    <option value="external">External</option>
                    <option value="internal">Internal</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Nomor Layar</label>
                <input type="number" name="nomor_layar" value="1" min="1" max="4" required>
            </div>
            
            <div class="form-group">
                <label>Judul</label>
                <input type="text" name="judul" placeholder="Masukkan judul konten" required>
            </div>
            
            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="deskripsi" rows="3" placeholder="Deskripsi opsional"></textarea>
            </div>
            
            <div class="form-group">
                <label>Durasi Tampil (detik)</label>
                <input type="number" name="durasi" value="5" min="1" max="60" required>
            </div>
            
            <div class="form-group">
                <label>Urutan</label>
                <input type="number" name="urutan" value="0" min="0" required>
            </div>
            
            <div class="form-group">
                <label>File (Gambar atau Video)</label>
                <div class="file-input-wrapper">
                    <input type="file" name="file" id="fileInput" accept="image/*,video/*" required>
                    <label for="fileInput" class="file-input-label">
                        📁 Klik untuk pilih file
                    </label>
                </div>
                <div id="fileName" class="file-name"></div>
            </div>
            
            <button type="submit" id="submitBtn">
                🚀 Upload Sekarang
            </button>
        </form>
        
        <div id="result"></div>
        <div id="debugLog" class="debug-log"></div>
    </div>
    
    <script>
        const form = document.getElementById('uploadForm');
        const fileInput = document.getElementById('fileInput');
        const fileName = document.getElementById('fileName');
        const result = document.getElementById('result');
        const submitBtn = document.getElementById('submitBtn');
        const debugLog = document.getElementById('debugLog');
        
        // Show selected file name
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                const size = (file.size / (1024 * 1024)).toFixed(2);
                fileName.textContent = `📄 ${file.name} (${size} MB)`;
                fileName.style.display = 'block';
                
                log(`File selected: ${file.name} (${file.type}) - ${size}MB`);
            }
        });
        
        // Handle form submit
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            log('=== UPLOAD START ===');
            
            // Validate file
            if (fileInput.files.length === 0) {
                showResult('error', '❌ Pilih file terlebih dahulu!');
                return;
            }
            
            const file = fileInput.files[0];
            const maxSize = 50 * 1024 * 1024; // 50MB
            
            if (file.size > maxSize) {
                showResult('error', '❌ File terlalu besar! Maksimal 50MB');
                return;
            }
            
            log(`Validasi OK. Starting upload...`);
            
            // Prepare form data
            const formData = new FormData(form);
            
            // Show loading
            showResult('loading', '⏳ Uploading... mohon tunggu');
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Uploading...';
            
            try {
                log(`Sending POST to test_upload_handler.php`);
                
                const response = await fetch('test_upload_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                log(`Response status: ${response.status} ${response.statusText}`);
                log(`Response headers: ${response.headers.get('content-type')}`);
                
                // Get response text first untuk debug
                const responseText = await response.text();
                log(`Response length: ${responseText.length} chars`);
                log(`Response preview: ${responseText.substring(0, 200)}...`);
                
                // Try parse JSON
                let data;
                try {
                    data = JSON.parse(responseText);
                    log(`JSON parsed successfully`);
                } catch (parseError) {
                    log(`JSON PARSE ERROR: ${parseError.message}`);
                    log(`Full response text:\n${responseText}`);
                    throw new Error(`Server tidak mengembalikan JSON valid. Response: ${responseText.substring(0, 500)}`);
                }
                
                if (data.success) {
                    log(`Upload SUCCESS!`);
                    showResult('success', `✅ ${data.message || 'Upload berhasil!'}`);
                    
                    if (data.data) {
                        log(`File: ${data.data.filename}`);
                        log(`Type: ${data.data.type}`);
                        log(`Size: ${data.data.size}`);
                    }
                    
                    // Reset form
                    form.reset();
                    fileName.style.display = 'none';
                } else {
                    log(`Upload FAILED: ${data.error}`);
                    showResult('error', `❌ ${data.error || 'Upload gagal'}`);
                }
                
            } catch (error) {
                log(`FETCH ERROR: ${error.message}`);
                console.error('Upload error:', error);
                showResult('error', `❌ Error: ${error.message}`);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = '🚀 Upload Sekarang';
            }
        });
        
        function showResult(type, message) {
            result.className = type;
            result.textContent = message;
            result.style.display = 'block';
            
            // Auto hide setelah 5 detik untuk success
            if (type === 'success') {
                setTimeout(() => {
                    result.style.display = 'none';
                }, 5000);
            }
        }
        
        function log(message) {
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = `[${timestamp}] ${message}\n`;
            debugLog.textContent += logEntry;
            debugLog.style.display = 'block';
            debugLog.scrollTop = debugLog.scrollHeight;
            console.log(message);
        }
    </script>
</body>
</html>