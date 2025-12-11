<?php
/**
 * AUTO CREATE .HTACCESS FILES
 * Script untuk membuat file .htaccess otomatis
 * Save as: create_htaccess.php
 * Akses: http://localhost/digital-signage/create_htaccess.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$results = [];
$errors = [];

if (isset($_POST['create'])) {
    
    // ==========================================
    // 1. CREATE ROOT .htaccess
    // ==========================================
    $rootHtaccess = __DIR__ . '/.htaccess';
    
    $rootContent = <<<'HTACCESS'
# .htaccess - Digital Signage BMFR Kelas II Manado
# Root project .htaccess

# Increase PHP upload limits untuk FILE BESAR
php_value upload_max_filesize 200M
php_value post_max_size 210M
php_value max_execution_time 600
php_value max_input_time 600
php_value memory_limit 512M

# Enable error logging
php_flag display_errors off
php_flag log_errors on

# Security: Prevent directory listing
Options -Indexes

# Protect config files
<Files "config.php">
    Require all denied
</Files>

<Files "enhanced_config.php">
    Require all denied
</Files>

<Files "secure_config.php">
    Require all denied
</Files>

# Allow PHP files
<FilesMatch "\.php$">
    Require all granted
</FilesMatch>

# Allow image and video files (PENTING!)
<FilesMatch "\.(jpg|jpeg|png|gif|webp|mp4|webm|ogg|avi|mov)$">
    Require all granted
</FilesMatch>

# Allow large file uploads (300MB)
LimitRequestBody 209715200

# Set proper MIME types for videos
AddType video/mp4 .mp4 .MP4
AddType video/webm .webm
AddType video/ogg .ogg .ogv
AddType video/quicktime .mov
AddType image/webp .webp

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>

# Cache control for media files
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType video/mp4 "access plus 1 month"
    ExpiresByType video/webm "access plus 1 month"
</IfModule>

# Error pages
ErrorDocument 404 /digital-signage/404.php
ErrorDocument 403 /digital-signage/403.php
ErrorDocument 500 /digital-signage/500.php

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>
HTACCESS;

    if (file_put_contents($rootHtaccess, $rootContent)) {
        $results[] = "✅ ROOT .htaccess berhasil dibuat: $rootHtaccess";
    } else {
        $errors[] = "❌ Gagal membuat ROOT .htaccess";
    }
    
    // ==========================================
    // 2. CREATE uploads/.htaccess
    // ==========================================
    $uploadsDir = __DIR__ . '/uploads/';
    
    // Buat folder jika belum ada
    if (!file_exists($uploadsDir)) {
        if (mkdir($uploadsDir, 0755, true)) {
            $results[] = "✅ Folder uploads berhasil dibuat";
        } else {
            $errors[] = "❌ Gagal membuat folder uploads";
        }
    }
    
    $uploadsHtaccess = $uploadsDir . '.htaccess';
    
    $uploadsContent = <<<'HTACCESS'
# .htaccess untuk folder uploads/
# SECURITY: Prevent PHP execution dan directory listing

# Prevent directory listing
Options -Indexes

# Prevent PHP execution (CRITICAL SECURITY!)
<FilesMatch "\.php$">
    Require all denied
</FilesMatch>

<FilesMatch "\.phtml$">
    Require all denied
</FilesMatch>

<FilesMatch "\.php3$">
    Require all denied
</FilesMatch>

<FilesMatch "\.php4$">
    Require all denied
</FilesMatch>

<FilesMatch "\.php5$">
    Require all denied
</FilesMatch>

<FilesMatch "\.phps$">
    Require all denied
</FilesMatch>

# Allow only media files
<FilesMatch "\.(jpg|jpeg|png|gif|webp|bmp|svg|mp4|webm|ogg|avi|mov|flv|wmv)$">
    Require all granted
</FilesMatch>

# Deny all other file types
<FilesMatch "\.">
    Require all denied
</FilesMatch>

# Force download untuk video files (optional)
# <FilesMatch "\.(mp4|webm|ogg)$">
#     ForceType application/octet-stream
#     Header set Content-Disposition attachment
# </FilesMatch>

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
</IfModule>

# Hotlink protection (optional - uncomment jika perlu)
# RewriteEngine on
# RewriteCond %{HTTP_REFERER} !^$
# RewriteCond %{HTTP_REFERER} !^http(s)?://(www\.)?localhost [NC]
# RewriteRule \.(jpg|jpeg|png|gif|mp4|webm)$ - [NC,F,L]
HTACCESS;

    if (file_put_contents($uploadsHtaccess, $uploadsContent)) {
        $results[] = "✅ uploads/.htaccess berhasil dibuat: $uploadsHtaccess";
    } else {
        $errors[] = "❌ Gagal membuat uploads/.htaccess";
    }
    
    // ==========================================
    // 3. VERIFY FILES CREATED
    // ==========================================
    if (file_exists($rootHtaccess)) {
        $size = filesize($rootHtaccess);
        $results[] = "✅ ROOT .htaccess verified: $size bytes";
    }
    
    if (file_exists($uploadsHtaccess)) {
        $size = filesize($uploadsHtaccess);
        $results[] = "✅ uploads/.htaccess verified: $size bytes";
    }
    
    // ==========================================
    // 4. TEST APACHE MOD_REWRITE
    // ==========================================
    if (function_exists('apache_get_modules')) {
        $modules = apache_get_modules();
        if (in_array('mod_rewrite', $modules)) {
            $results[] = "✅ Apache mod_rewrite: ACTIVE";
        } else {
            $errors[] = "⚠️ Apache mod_rewrite: NOT ACTIVE";
        }
    }
    
    // ==========================================
    // 5. RESTART APACHE REMINDER
    // ==========================================
    $results[] = "\n⚠️ PENTING: Restart Apache di XAMPP Control Panel agar .htaccess aktif!";
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create .htaccess Files</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .info-box {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #1976d2;
        }
        .info-box h3 {
            color: #1976d2;
            margin-bottom: 15px;
        }
        .info-box ul {
            margin-left: 20px;
            line-height: 2;
        }
        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .warning-box h3 {
            color: #856404;
            margin-bottom: 15px;
        }
        .success-box {
            background: #d4edda;
            border: 2px solid #28a745;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .success-box h3 {
            color: #155724;
            margin-bottom: 15px;
        }
        .error-box {
            background: #f8d7da;
            border: 2px solid #dc3545;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .error-box h3 {
            color: #721c24;
            margin-bottom: 15px;
        }
        .result-list {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 13px;
            line-height: 1.8;
            white-space: pre-wrap;
        }
        .btn {
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .btn-secondary {
            background: #6c757d;
            margin-top: 10px;
        }
        .code {
            background: #2d3748;
            color: #48bb78;
            padding: 3px 8px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 13px;
        }
        .file-preview {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px solid #dee2e6;
        }
        .file-preview h4 {
            color: #495057;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .file-preview pre {
            background: #2d3748;
            color: #a0aec0;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📄 Create .htaccess Files</h1>
        <p class="subtitle">Auto-generate missing .htaccess files</p>
        
        <?php if (empty($results) && empty($errors)): ?>
        
        <div class="warning-box">
            <h3>⚠️ PENTING - Baca Dulu!</h3>
            <ul>
                <li>Script ini akan membuat 2 file <span class="code">.htaccess</span></li>
                <li>File pertama: <span class="code">.htaccess</span> di root folder</li>
                <li>File kedua: <span class="code">uploads/.htaccess</span> untuk keamanan</li>
                <li><strong>Restart Apache</strong> setelah file dibuat!</li>
            </ul>
        </div>
        
        <div class="info-box">
            <h3>📋 Apa yang akan dibuat?</h3>
            <ul>
                <li><strong>ROOT .htaccess:</strong> Setting upload 300MB, security headers, MIME types</li>
                <li><strong>uploads/.htaccess:</strong> Prevent PHP execution, allow only media files</li>
            </ul>
        </div>
        
        <div class="file-preview">
            <h4>Preview: ROOT .htaccess (50 baris)</h4>
            <pre># Increase PHP upload limits untuk FILE BESAR
php_value upload_max_filesize 200M
php_value post_max_size 210M
php_value max_execution_time 600
php_value max_input_time 600
php_value memory_limit 512M

# Security: Prevent directory listing
Options -Indexes

# Protect config files
&lt;Files "config.php"&gt;
    Require all denied
&lt;/Files&gt;

# Allow image and video files
&lt;FilesMatch "\.(jpg|jpeg|png|gif|webp|mp4|webm|ogg)$"&gt;
    Require all granted
&lt;/FilesMatch&gt;

# ... dan 35 baris lainnya</pre>
        </div>
        
        <div class="file-preview">
            <h4>Preview: uploads/.htaccess (Security Protection)</h4>
            <pre># Prevent PHP execution (CRITICAL SECURITY!)
&lt;FilesMatch "\.php$"&gt;
    Require all denied
&lt;/FilesMatch&gt;

# Allow only media files
&lt;FilesMatch "\.(jpg|jpeg|png|gif|webp|mp4|webm|ogg)$"&gt;
    Require all granted
&lt;/FilesMatch&gt;

# ... dan lainnya</pre>
        </div>
        
        <form method="POST">
            <button type="submit" name="create" class="btn">
                🚀 Create .htaccess Files Now
            </button>
        </form>
        
        <?php else: ?>
        
        <?php if (!empty($results)): ?>
        <div class="success-box">
            <h3>✅ Success!</h3>
            <div class="result-list"><?= implode("\n", $results) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
        <div class="error-box">
            <h3>❌ Errors:</h3>
            <div class="result-list"><?= implode("\n", $errors) ?></div>
        </div>
        <?php endif; ?>
        
        <div class="warning-box">
            <h3>⚡ NEXT STEPS:</h3>
            <ol style="margin-left: 20px; line-height: 2;">
                <li><strong>Restart Apache</strong> di XAMPP Control Panel</li>
                <li>Klik <strong>Stop</strong> lalu <strong>Start</strong> pada Apache</li>
                <li>Tunggu sampai status jadi hijau</li>
                <li>Jalankan <span class="code">check_upload.php</span> lagi untuk verifikasi</li>
                <li>Test upload file di <strong>manage_external.php</strong></li>
            </ol>
        </div>
        
        <a href="check_upload.php" class="btn">
            🔍 Verify with Diagnostic Tool
        </a>
        
        <a href="manage_display/manage_external.php" class="btn btn-secondary">
            📺 Test Upload Now
        </a>
        
        <?php endif; ?>
    </div>
</body>
</html>