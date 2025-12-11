<?php
/**
 * FIX UPLOADS/.HTACCESS
 * Script khusus untuk membuat uploads/.htaccess
 * Save as: fix_uploads_htaccess.php
 * Akses: http://localhost/digital-signage/fix_uploads_htaccess.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$success = false;
$error = null;
$created = false;

if (isset($_POST['create'])) {
    
    $uploadsDir = __DIR__ . '/uploads/';
    
    // 1. Cek folder uploads
    if (!file_exists($uploadsDir)) {
        if (!mkdir($uploadsDir, 0755, true)) {
            $error = "❌ Gagal membuat folder uploads!";
        } else {
            $created = true;
        }
    }
    
    // 2. Create .htaccess
    if (!$error) {
        $htaccessPath = $uploadsDir . '.htaccess';
        
        $content = <<<'HTACCESS'
# ============================================
# SECURITY .htaccess untuk folder uploads/
# Digital Signage BMFR Kelas II Manado
# ============================================

# Prevent directory listing
Options -Indexes

# ============================================
# CRITICAL: Prevent PHP Execution
# Ini WAJIB untuk mencegah upload PHP shell
# ============================================
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

<FilesMatch "\.phar$">
    Require all denied
</FilesMatch>

# ============================================
# Allow ONLY Media Files
# ============================================
<FilesMatch "\.(jpg|jpeg|png|gif|webp|bmp|svg|ico)$">
    Require all granted
</FilesMatch>

<FilesMatch "\.(mp4|webm|ogg|avi|mov|flv|wmv|mkv)$">
    Require all granted
</FilesMatch>

# ============================================
# Deny Everything Else
# ============================================
<FilesMatch "^.*$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Override untuk media files (whitelist)
<FilesMatch "\.(jpg|jpeg|png|gif|webp|bmp|svg|ico|mp4|webm|ogg|avi|mov|flv|wmv|mkv)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>

# ============================================
# Security Headers
# ============================================
<IfModule mod_headers.c>
    # Prevent MIME sniffing
    Header set X-Content-Type-Options "nosniff"
    
    # Prevent clickjacking
    Header set X-Frame-Options "SAMEORIGIN"
    
    # Prevent XSS
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

# ============================================
# Optional: Hotlink Protection
# Uncomment jika ingin mencegah hotlinking
# ============================================
# <IfModule mod_rewrite.c>
#     RewriteEngine On
#     RewriteCond %{HTTP_REFERER} !^$
#     RewriteCond %{HTTP_REFERER} !^https?://(www\.)?localhost [NC]
#     RewriteCond %{HTTP_REFERER} !^https?://(www\.)?yourdomain\.com [NC]
#     RewriteRule \.(jpg|jpeg|png|gif|bmp|mp4|webm)$ - [F,L]
# </IfModule>

# ============================================
# Cache Control for Media Files
# ============================================
<IfModule mod_expires.c>
    ExpiresActive On
    
    # Images
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/webp "access plus 1 month"
    
    # Videos
    ExpiresByType video/mp4 "access plus 1 month"
    ExpiresByType video/webm "access plus 1 month"
    ExpiresByType video/ogg "access plus 1 month"
</IfModule>

# ============================================
# Disable Script Execution (Backup Method)
# ============================================
<IfModule mod_php7.c>
    php_flag engine off
</IfModule>

<IfModule mod_php.c>
    php_flag engine off
</IfModule>

# ============================================
# End of uploads/.htaccess
# ============================================
HTACCESS;

        if (file_put_contents($htaccessPath, $content)) {
            $success = true;
        } else {
            $error = "❌ Gagal menulis file .htaccess!";
        }
    }
}

// Cek status file
$uploadsDir = __DIR__ . '/uploads/';
$htaccessPath = $uploadsDir . '.htaccess';
$fileExists = file_exists($htaccessPath);
$fileSize = $fileExists ? filesize($htaccessPath) : 0;
$folderExists = file_exists($uploadsDir);
$folderWritable = $folderExists && is_writable($uploadsDir);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix uploads/.htaccess</title>
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
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .status-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }
        .status-box h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .status-item {
            padding: 10px;
            margin: 8px 0;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status-item.ok {
            background: #d4edda;
            color: #155724;
        }
        .status-item.error {
            background: #f8d7da;
            color: #721c24;
        }
        .status-item .icon {
            font-size: 20px;
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
        .success-box {
            background: #d4edda;
            border: 2px solid #28a745;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            animation: slideIn 0.5s;
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
            margin-top: 20px;
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
        }
        .btn-success {
            background: #28a745;
        }
        .code-preview {
            background: #2d3748;
            color: #a0aec0;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 12px;
            line-height: 1.8;
            overflow-x: auto;
            max-height: 400px;
        }
        .highlight {
            background: #fef5e7;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
        }
        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <span>🔒</span>
            <span>Fix uploads/.htaccess</span>
        </h1>
        <p class="subtitle">Security protection untuk folder uploads</p>
        
        <!-- Status Check -->
        <div class="status-box">
            <h3>📊 Status Check</h3>
            
            <div class="status-item <?= $folderExists ? 'ok' : 'error' ?>">
                <span class="icon"><?= $folderExists ? '✅' : '❌' ?></span>
                <span>Folder uploads/ <?= $folderExists ? 'EXISTS' : 'NOT FOUND' ?></span>
            </div>
            
            <div class="status-item <?= $folderWritable ? 'ok' : 'error' ?>">
                <span class="icon"><?= $folderWritable ? '✅' : '❌' ?></span>
                <span>Folder uploads/ <?= $folderWritable ? 'WRITABLE' : 'NOT WRITABLE' ?></span>
            </div>
            
            <div class="status-item <?= $fileExists ? 'ok' : 'error' ?>">
                <span class="icon"><?= $fileExists ? '✅' : '❌' ?></span>
                <span>uploads/.htaccess <?= $fileExists ? "EXISTS ($fileSize bytes)" : 'NOT FOUND' ?></span>
            </div>
        </div>
        
        <?php if ($success): ?>
        
        <div class="success-box">
            <h3>✅ Success!</h3>
            <p>File <span class="highlight">uploads/.htaccess</span> berhasil dibuat!</p>
            <?php if ($created): ?>
            <p style="margin-top: 10px;">✅ Folder uploads juga berhasil dibuat.</p>
            <?php endif; ?>
        </div>
        
        <div class="info-box">
            <h3>🛡️ Security Features Aktif:</h3>
            <ul>
                <li>✅ PHP execution <strong>DISABLED</strong> (prevent shell upload)</li>
                <li>✅ Directory listing <strong>DISABLED</strong></li>
                <li>✅ Only media files allowed (jpg, png, mp4, dll)</li>
                <li>✅ Security headers enabled</li>
                <li>✅ Cache control untuk media files</li>
            </ul>
        </div>
        
        <a href="check_upload.php" class="btn btn-success">
            🔍 Verify dengan Diagnostic Tool
        </a>
        
        <a href="manage_display/manage_external.php" class="btn">
            📺 Test Upload Sekarang
        </a>
        
        <?php elseif ($error): ?>
        
        <div class="error-box">
            <h3>❌ Error</h3>
            <p><?= $error ?></p>
        </div>
        
        <form method="POST">
            <button type="submit" name="create" class="btn">
                🔄 Try Again
            </button>
        </form>
        
        <?php else: ?>
        
        <?php if (!$fileExists): ?>
        
        <div class="info-box">
            <h3>🔒 Kenapa perlu uploads/.htaccess?</h3>
            <ul>
                <li><strong>Security:</strong> Mencegah eksekusi file PHP di folder uploads</li>
                <li><strong>Protection:</strong> Block upload file berbahaya (.php, .phtml, dll)</li>
                <li><strong>Whitelist:</strong> Hanya izinkan media files (gambar & video)</li>
                <li><strong>Headers:</strong> Set security headers untuk mencegah XSS & clickjacking</li>
            </ul>
        </div>
        
        <div class="info-box" style="background: #fff3cd; border-color: #ffc107;">
            <h3 style="color: #856404;">⚠️ Yang Akan Dibuat:</h3>
            <ul style="color: #856404;">
                <li>File: <span class="highlight">uploads/.htaccess</span> (sekitar 2.5 KB)</li>
                <li>Konten: 100+ baris konfigurasi security</li>
                <li>Fitur: Prevent PHP execution, whitelist media files, security headers</li>
            </ul>
        </div>
        
        <details style="margin: 20px 0;">
            <summary style="cursor: pointer; padding: 10px; background: #f8f9fa; border-radius: 5px; font-weight: 600;">
                📄 Preview Content (Click to expand)
            </summary>
            <div class="code-preview"># SECURITY .htaccess untuk folder uploads/

# Prevent directory listing
Options -Indexes

# CRITICAL: Prevent PHP Execution
&lt;FilesMatch "\.php$"&gt;
    Require all denied
&lt;/FilesMatch&gt;

# Allow ONLY Media Files
&lt;FilesMatch "\.(jpg|jpeg|png|gif|webp|mp4|webm|ogg)$"&gt;
    Require all granted
&lt;/FilesMatch&gt;

# Security Headers
&lt;IfModule mod_headers.c&gt;
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
&lt;/IfModule&gt;

# ... dan 80+ baris lainnya</div>
        </details>
        
        <form method="POST">
            <button type="submit" name="create" class="btn">
                🚀 Create uploads/.htaccess Now
            </button>
        </form>
        
        <?php else: ?>
        
        <div class="success-box">
            <h3>✅ File Sudah Ada!</h3>
            <p>File <span class="highlight">uploads/.htaccess</span> sudah ada dengan ukuran <strong><?= number_format($fileSize) ?> bytes</strong>.</p>
        </div>
        
        <a href="check_upload.php" class="btn btn-success">
            🔍 Run Full Diagnostic
        </a>
        
        <?php endif; ?>
        
        <?php endif; ?>
        
        <a href="dashboard_modern.php" class="btn btn-secondary">
            ← Kembali ke Dashboard
        </a>
    </div>
</body>
</html>