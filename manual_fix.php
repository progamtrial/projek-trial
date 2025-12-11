<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Fix Guide</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #667eea;
            margin-bottom: 20px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
        }
        h2 {
            color: #333;
            margin: 30px 0 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
        }
        h3 {
            color: #667eea;
            margin: 20px 0 10px 0;
        }
        .step {
            background: #f8f9fa;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .code {
            background: #2d3748;
            color: #48bb78;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.6;
        }
        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .warning h4 {
            color: #856404;
            margin-bottom: 10px;
        }
        .success {
            background: #d4edda;
            border: 2px solid #28a745;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .error {
            background: #f8d7da;
            border: 2px solid #dc3545;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        ol, ul {
            margin-left: 30px;
            line-height: 2;
        }
        .highlight {
            background: #fef5e7;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
        }
        .path {
            background: #e3f2fd;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            margin: 10px 0;
            word-break: break-all;
        }
        @media print {
            body { background: white; padding: 0; }
            .container { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Manual Fix Guide - Upload System</h1>
        <p style="color: #666; margin-bottom: 30px;">
            Panduan lengkap untuk memperbaiki sistem upload secara manual jika auto-fix gagal.
        </p>
        
        <!-- ============================================ -->
        <h2>🗂️ FIX 1: Folder uploads/</h2>
        
        <div class="step">
            <h3>Opsi A: Via File Explorer (Windows)</h3>
            <ol>
                <li>Buka folder: <span class="highlight">C:\xampp\htdocs\digital-balmon2\</span></li>
                <li>Klik kanan → <strong>New Folder</strong></li>
                <li>Nama folder: <span class="highlight">uploads</span> (huruf kecil semua)</li>
                <li>Klik kanan folder uploads → <strong>Properties</strong></li>
                <li>Tab <strong>Security</strong> → <strong>Edit</strong></li>
                <li>Pilih <strong>Everyone</strong> → Centang <strong>Full Control</strong></li>
                <li>Klik <strong>Apply</strong> → <strong>OK</strong></li>
            </ol>
        </div>
        
        <div class="step">
            <h3>Opsi B: Via PHP Script</h3>
            <p>Buat file <span class="highlight">create_uploads.php</span> di root:</p>
            <div class="code">&lt;?php
$dir = __DIR__ . '/uploads/';

if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
    chmod($dir, 0777);
    echo "✅ Folder created: $dir";
} else {
    echo "ℹ️ Folder already exists";
}

if (is_writable($dir)) {
    echo "&lt;br&gt;✅ Folder is WRITABLE";
} else {
    echo "&lt;br&gt;❌ Folder NOT writable - fix permission";
}
?&gt;</div>
            <p>Akses: <span class="highlight">http://localhost/digital-balmon2/create_uploads.php</span></p>
        </div>
        
        <div class="warning">
            <h4>⚠️ Jika Masih Error "Not Writable"</h4>
            <ol>
                <li>Matikan antivirus sementara</li>
                <li>Run XAMPP sebagai Administrator</li>
                <li>Coba ubah permission via CMD:
                    <div class="code">icacls "C:\xampp\htdocs\digital-balmon2\uploads" /grant Everyone:F</div>
                </li>
            </ol>
        </div>
        
        <!-- ============================================ -->
        <h2>🔒 FIX 2: File uploads/.htaccess</h2>
        
        <div class="step">
            <h3>Cara Manual Create .htaccess</h3>
            <ol>
                <li>Buka <strong>Notepad++</strong> atau <strong>VS Code</strong></li>
                <li>Copy kode berikut:</li>
            </ol>
            <div class="code"># Prevent directory listing
Options -Indexes

# Prevent PHP execution
&lt;FilesMatch "\.php$"&gt;
    Require all denied
&lt;/FilesMatch&gt;

# Allow only media files
&lt;FilesMatch "\.(jpg|jpeg|png|gif|webp|mp4|webm|ogg)$"&gt;
    Require all granted
&lt;/FilesMatch&gt;

# Security headers
&lt;IfModule mod_headers.c&gt;
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
&lt;/IfModule&gt;</div>
            <ol start="3">
                <li><strong>Save As...</strong></li>
                <li>File name: <span class="highlight">.htaccess</span> (dengan titik di depan)</li>
                <li>Save to: <span class="highlight">C:\xampp\htdocs\digital-balmon2\uploads\.htaccess</span></li>
                <li>Save as type: <strong>All Files (*.*)</strong></li>
            </ol>
        </div>
        
        <div class="warning">
            <h4>⚠️ Windows: Cara Save File dengan Titik di Depan</h4>
            <p>Windows tidak suka nama file yang mulai dengan titik. Cara fix:</p>
            <ol>
                <li>Buka CMD sebagai Administrator</li>
                <li>Jalankan:
                    <div class="code">cd C:\xampp\htdocs\digital-balmon2\uploads
echo # Security > .htaccess
notepad .htaccess</div>
                </li>
                <li>Paste konten .htaccess</li>
                <li>Save dan close</li>
            </ol>
        </div>
        
        <!-- ============================================ -->
        <h2>📄 FIX 3: File upload_handler.php</h2>
        
        <div class="step">
            <h3>Location WAJIB:</h3>
            <div class="path">
                C:\xampp\htdocs\digital-balmon2\manage_display\upload_handler.php
            </div>
            
            <p style="margin-top: 15px;"><strong>Steps:</strong></p>
            <ol>
                <li>Buat folder <span class="highlight">manage_display</span> jika belum ada</li>
                <li>Download atau copy code upload_handler.php dari Artifact sebelumnya</li>
                <li>Save ke path di atas</li>
                <li>Verify file exists:
                    <div class="code">http://localhost/digital-balmon2/manage_display/upload_handler.php</div>
                    Harusnya muncul error "Method must be POST" (itu berarti file ada)
                </li>
            </ol>
        </div>
        
        <div class="warning">
            <h4>⚠️ Jika File Tidak Terbaca</h4>
            <p>Kemungkinan path salah. Test dengan file sederhana:</p>
            <ol>
                <li>Buat file <span class="highlight">test.php</span> di manage_display/</li>
                <li>Isi: <code>&lt;?php echo "File found!"; ?&gt;</code></li>
                <li>Akses: <code>http://localhost/digital-balmon2/manage_display/test.php</code></li>
                <li>Jika muncul "File found!" berarti path sudah benar</li>
            </ol>
        </div>
        
        <!-- ============================================ -->
        <h2>⚙️ FIX 4: PHP Settings (upload_max_filesize)</h2>
        
        <div class="step">
            <h3>Edit php.ini untuk 300MB Upload</h3>
            <ol>
                <li>Buka file: <span class="highlight">C:\xampp\php\php.ini</span></li>
                <li>Cari (Ctrl+F): <span class="highlight">upload_max_filesize</span></li>
                <li>Ubah jadi:
                    <div class="code">upload_max_filesize = 200M</div>
                </li>
                <li>Cari: <span class="highlight">post_max_size</span></li>
                <li>Ubah jadi:
                    <div class="code">post_max_size = 210M</div>
                </li>
                <li>Cari: <span class="highlight">memory_limit</span></li>
                <li>Ubah jadi:
                    <div class="code">memory_limit = 512M</div>
                </li>
                <li>Cari: <span class="highlight">max_execution_time</span></li>
                <li>Ubah jadi:
                    <div class="code">max_execution_time = 600</div>
                </li>
                <li><strong>Save file</strong></li>
                <li><strong>Restart Apache</strong> di XAMPP Control Panel</li>
            </ol>
        </div>
        
        <div class="success">
            <h4>✅ Verify PHP Settings</h4>
            <p>Buat file <span class="highlight">info.php</span>:</p>
            <div class="code">&lt;?php phpinfo(); ?&gt;</div>
            <p>Akses: <code>http://localhost/digital-balmon2/info.php</code></p>
            <p>Cari "upload_max_filesize" dan pastikan nilainya <strong>200M</strong></p>
        </div>
        
        <!-- ============================================ -->
        <h2>💾 FIX 5: Database Connection</h2>
        
        <div class="step">
            <h3>Test Database dengan Script Ini</h3>
            <p>Buat file <span class="highlight">test_db.php</span>:</p>
            <div class="code">&lt;?php
require_once 'config.php';

try {
    $conn = getConnection();
    echo "✅ Database Connected&lt;br&gt;";
    
    // Check table
    $result = $conn->query("SHOW TABLES LIKE 'konten_layar'");
    if ($result && $result->num_rows > 0) {
        echo "✅ Table 'konten_layar' EXISTS&lt;br&gt;";
        
        // Count records
        $count = $conn->query("SELECT COUNT(*) as c FROM konten_layar")->fetch_assoc()['c'];
        echo "✅ Records: $count&lt;br&gt;";
    } else {
        echo "❌ Table 'konten_layar' NOT FOUND&lt;br&gt;";
        echo "➡️ Import database.sql di phpMyAdmin&lt;br&gt;";
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage();
}
?&gt;</div>
            <p>Akses: <code>http://localhost/digital-balmon2/test_db.php</code></p>
        </div>
        
        <div class="error">
            <h4>❌ Jika Database Connection Failed</h4>
            <ol>
                <li>Pastikan MySQL running di XAMPP</li>
                <li>Check <span class="highlight">config.php</span>:
                    <div class="code">define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'digital_signage');</div>
                </li>
                <li>Test di phpMyAdmin: <code>http://localhost/phpmyadmin</code></li>
                <li>Cek apakah database <span class="highlight">digital_signage</span> ada</li>
                <li>Jika tidak ada, import <span class="highlight">database.sql</span></li>
            </ol>
        </div>
        
        <!-- ============================================ -->
        <h2>🧪 FIX 6: Test Upload</h2>
        
        <div class="step">
            <h3>Test Upload Step by Step</h3>
            <ol>
                <li>Akses: <code>http://localhost/digital-balmon2/manage_display/manage_external.php</code></li>
                <li>Login jika diminta (username: <span class="highlight">admin</span>, password: <span class="highlight">admin123</span>)</li>
                <li>Klik <strong>Tambah Konten</strong></li>
                <li>Isi form:
                    <ul>
                        <li>Judul: Test Upload</li>
                        <li>Durasi: 5</li>
                        <li>Pilih file gambar kecil (< 1MB untuk test)</li>
                    </ul>
                </li>
                <li>Klik <strong>Upload</strong></li>
                <li>Buka <strong>Console</strong> browser (F12) untuk lihat error jika ada</li>
            </ol>
        </div>
        
        <div class="warning">
            <h4>⚠️ Common Upload Errors</h4>
            <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                <tr style="background: #f8f9fa;">
                    <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Error</th>
                    <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Solution</th>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #ddd;">404 Not Found</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">upload_handler.php tidak ada atau path salah</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #ddd;">403 Forbidden</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">Permission folder salah</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #ddd;">500 Internal Error</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">Syntax error di PHP atau database error</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #ddd;">Failed to move file</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">Folder uploads tidak writable</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #ddd;">File too large</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">PHP settings belum 200M</td>
                </tr>
            </table>
        </div>
        
        <!-- ============================================ -->
        <h2>📞 Need Help?</h2>
        
        <div class="step">
            <h3>Jika Masih Error Setelah Semua Fix:</h3>
            <ol>
                <li>Screenshot error message (full screen)</li>
                <li>Screenshot browser console (F12)</li>
                <li>Check error log:
                    <ul>
                        <li>Apache: <span class="highlight">C:\xampp\apache\logs\error.log</span></li>
                        <li>PHP: <span class="highlight">C:\xampp\php\logs\php_error_log</span></li>
                    </ul>
                </li>
                <li>Run diagnostic: <code>http://localhost/digital-balmon2/check_upload.php</code></li>
                <li>Kirim semua info di atas untuk troubleshooting</li>
            </ol>
        </div>
        
        <div class="success" style="margin-top: 30px;">
            <h4>✅ Checklist - Semua Harus ✅</h4>
            <ul style="list-style: none; margin-left: 0;">
                <li>☐ Folder <span class="highlight">uploads/</span> ada dan writable</li>
                <li>☐ File <span class="highlight">uploads/.htaccess</span> ada</li>
                <li>☐ File <span class="highlight">manage_display/upload_handler.php</span> ada</li>
                <li>☐ PHP settings: upload_max_filesize = 200M</li>
                <li>☐ PHP settings: post_max_size = 210M</li>
                <li>☐ Database connected</li>
                <li>☐ Table konten_layar exists</li>
                <li>☐ Apache restarted after php.ini changes</li>
            </ul>
        </div>
        
        <div style="text-align: center; margin-top: 40px; padding-top: 30px; border-top: 2px solid #eee;">
            <p style="color: #666;">Digital Signage BMFR Kelas II Manado</p>
            <p style="color: #999; font-size: 14px;">Manual Fix Guide v1.0</p>
        </div>
    </div>
</body>
</html>