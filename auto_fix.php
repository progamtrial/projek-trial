<?php
/**
 * AUTO FIX SCRIPT - ONE-CLICK SYSTEM REPAIR
 * Digital Signage BMFR Kelas II Manado
 * 
 * File: auto_fix.php
 * Location: C:\xampp\htdocs\digital-signage\auto_fix.php
 * Access: http://localhost/digital-signage/auto_fix.php
 * 
 * CARA PAKAI:
 * 1. Copy file ini ke root folder digital-signage
 * 2. Akses di browser
 * 3. Klik "Run Auto Fix"
 * 4. Tunggu sampai selesai
 * 5. Delete file ini setelah selesai (untuk security)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minutes timeout

// Security: Only allow from localhost
if ($_SERVER['SERVER_NAME'] !== 'localhost' && $_SERVER['SERVER_NAME'] !== '127.0.0.1') {
    die('Access denied. Only accessible from localhost.');
}

$results = [];
$errors = [];

// Run fix if requested
if (isset($_POST['run_fix'])) {
    $results[] = "🚀 Starting Auto Fix...";
    
    // ========================================
    // FIX 1: Check and Create Directories
    // ========================================
    $results[] = "\n📁 Checking directories...";
    
    $dirs = [
        'uploads' => __DIR__ . '/uploads/',
        'backups' => __DIR__ . '/backups/',
        'logs' => __DIR__ . '/logs/'
    ];
    
    foreach ($dirs as $name => $path) {
        if (!file_exists($path)) {
            if (mkdir($path, 0755, true)) {
                $results[] = "✅ Created directory: $name/";
            } else {
                $errors[] = "❌ Failed to create directory: $name/";
            }
        } else {
            if (is_writable($path)) {
                $results[] = "✅ Directory exists and writable: $name/";
            } else {
                $errors[] = "⚠️ Directory exists but not writable: $name/";
            }
        }
    }
    
    // ========================================
    // FIX 2: Create .htaccess in uploads
    // ========================================
    $results[] = "\n🔒 Creating uploads/.htaccess...";
    
    $uploadsHtaccess = __DIR__ . '/uploads/.htaccess';
    $htaccessContent = <<<'HTACCESS'
# Prevent PHP execution
<FilesMatch "\.php$">
    Require all denied
</FilesMatch>

# Prevent directory listing
Options -Indexes

# Allow only media files
<FilesMatch "\.(jpg|jpeg|png|gif|webp|mp4|webm|ogg)$">
    Require all granted
</FilesMatch>
HTACCESS;
    
    if (file_put_contents($uploadsHtaccess, $htaccessContent)) {
        $results[] = "✅ Created uploads/.htaccess";
    } else {
        $errors[] = "❌ Failed to create uploads/.htaccess";
    }
    
    // ========================================
    // FIX 3: Database Connection Check
    // ========================================
    $results[] = "\n🗄️ Checking database...";
    
    try {
        require_once 'config.php';
        $conn = getConnection();
        $results[] = "✅ Database connection successful";
        
        // ========================================
        // FIX 4: Add Missing Columns
        // ========================================
        $results[] = "\n🔧 Fixing database structure...";
        
        // Check and add video column
        $videoCheck = $conn->query("SHOW COLUMNS FROM konten_layar LIKE 'video'");
        if ($videoCheck->num_rows == 0) {
            if ($conn->query("ALTER TABLE konten_layar ADD COLUMN video VARCHAR(255) NULL AFTER gambar")) {
                $results[] = "✅ Added 'video' column to konten_layar";
            } else {
                $errors[] = "❌ Failed to add video column: " . $conn->error;
            }
        } else {
            $results[] = "✓ Column 'video' already exists";
        }
        
        // Check and add view_count column
        $viewCountCheck = $conn->query("SHOW COLUMNS FROM konten_layar LIKE 'view_count'");
        if ($viewCountCheck->num_rows == 0) {
            if ($conn->query("ALTER TABLE konten_layar ADD COLUMN view_count INT DEFAULT 0 AFTER video")) {
                $results[] = "✅ Added 'view_count' column to konten_layar";
            }
        } else {
            $results[] = "✓ Column 'view_count' already exists";
        }
        
        // Check and add role column to admin
        $roleCheck = $conn->query("SHOW COLUMNS FROM admin LIKE 'role'");
        if ($roleCheck->num_rows == 0) {
            if ($conn->query("ALTER TABLE admin ADD COLUMN role ENUM('superadmin', 'admin', 'editor', 'viewer') DEFAULT 'admin' AFTER nama")) {
                $results[] = "✅ Added 'role' column to admin";
                
                // Set first user as superadmin
                $conn->query("UPDATE admin SET role = 'superadmin' WHERE id = 1");
                $results[] = "✅ Set first user as superadmin";
            }
        } else {
            $results[] = "✓ Column 'role' already exists";
        }
        
        // Check and add email column
        $emailCheck = $conn->query("SHOW COLUMNS FROM admin LIKE 'email'");
        if ($emailCheck->num_rows == 0) {
            $conn->query("ALTER TABLE admin ADD COLUMN email VARCHAR(100) NULL AFTER nama");
            $results[] = "✅ Added 'email' column to admin";
        } else {
            $results[] = "✓ Column 'email' already exists";
        }
        
        // Check and add is_active column
        $activeCheck = $conn->query("SHOW COLUMNS FROM admin LIKE 'is_active'");
        if ($activeCheck->num_rows == 0) {
            $conn->query("ALTER TABLE admin ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER role");
            $results[] = "✅ Added 'is_active' column to admin";
        } else {
            $results[] = "✓ Column 'is_active' already exists";
        }
        
        // ========================================
        // FIX 5: Create Missing Tables
        // ========================================
        $results[] = "\n📊 Creating missing tables...";
        
        // Activity Log Table
        $tableCheck = $conn->query("SHOW TABLES LIKE 'activity_log'");
        if ($tableCheck->num_rows == 0) {
            $sql = "CREATE TABLE activity_log (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                module VARCHAR(50) NOT NULL,
                description TEXT,
                ip_address VARCHAR(45),
                user_agent VARCHAR(255),
                old_value TEXT,
                new_value TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_action (action),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            if ($conn->query($sql)) {
                $results[] = "✅ Created table: activity_log";
            }
        } else {
            $results[] = "✓ Table 'activity_log' already exists";
        }
        
        // Content Analytics Table
        $tableCheck = $conn->query("SHOW TABLES LIKE 'content_analytics'");
        if ($tableCheck->num_rows == 0) {
            $sql = "CREATE TABLE content_analytics (
                id BIGINT PRIMARY KEY AUTO_INCREMENT,
                konten_id INT NOT NULL,
                display_type ENUM('external', 'internal') NOT NULL,
                nomor_layar INT NOT NULL,
                display_count INT DEFAULT 1,
                display_date DATE NOT NULL,
                display_hour INT NOT NULL,
                total_duration INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_analytics (konten_id, display_date, display_hour, nomor_layar),
                INDEX idx_konten (konten_id),
                INDEX idx_date (display_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            if ($conn->query($sql)) {
                $results[] = "✅ Created table: content_analytics";
            }
        } else {
            $results[] = "✓ Table 'content_analytics' already exists";
        }
        
        // Settings Table
        $tableCheck = $conn->query("SHOW TABLES LIKE 'settings'");
        if ($tableCheck->num_rows == 0) {
            $sql = "CREATE TABLE settings (
                id INT PRIMARY KEY AUTO_INCREMENT,
                key_name VARCHAR(100) UNIQUE NOT NULL,
                key_value TEXT,
                description VARCHAR(255),
                updated_by INT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            if ($conn->query($sql)) {
                $results[] = "✅ Created table: settings";
                
                // Insert default settings
                $conn->query("INSERT IGNORE INTO settings (key_name, key_value, description) VALUES 
                    ('auto_backup_enabled', '1', 'Enable automatic backup'),
                    ('analytics_enabled', '1', 'Enable content analytics'),
                    ('system_timezone', 'Asia/Makassar', 'System timezone')");
                $results[] = "✅ Inserted default settings";
            }
        } else {
            $results[] = "✓ Table 'settings' already exists";
        }
        
        // ========================================
        // FIX 6: Add Indexes
        // ========================================
        $results[] = "\n🚀 Adding performance indexes...";
        
        // Check if index exists
        $indexCheck = $conn->query("SHOW INDEX FROM konten_layar WHERE Key_name = 'idx_tipe_nomor'");
        if ($indexCheck->num_rows == 0) {
            $conn->query("ALTER TABLE konten_layar ADD INDEX idx_tipe_nomor (tipe_layar, nomor_layar)");
            $results[] = "✅ Added index: idx_tipe_nomor";
        }
        
        $indexCheck = $conn->query("SHOW INDEX FROM konten_layar WHERE Key_name = 'idx_status'");
        if ($indexCheck->num_rows == 0) {
            $conn->query("ALTER TABLE konten_layar ADD INDEX idx_status (status)");
            $results[] = "✅ Added index: idx_status";
        }
        
        // ========================================
        // FIX 7: Optimize Tables
        // ========================================
        $results[] = "\n⚡ Optimizing tables...";
        
        $tables = ['konten_layar', 'admin', 'activity_log', 'content_analytics'];
        foreach ($tables as $table) {
            $tableExists = $conn->query("SHOW TABLES LIKE '$table'");
            if ($tableExists && $tableExists->num_rows > 0) {
                $conn->query("OPTIMIZE TABLE $table");
                $results[] = "✅ Optimized table: $table";
            }
        }
        
        $conn->close();
        
    } catch (Exception $e) {
        $errors[] = "❌ Database error: " . $e->getMessage();
    }
    
    // ========================================
    // FIX 8: Check PHP Extensions
    // ========================================
    $results[] = "\n🔌 Checking PHP extensions...";
    
    $requiredExtensions = [
        'mysqli' => 'Database connectivity',
        'gd' => 'Image processing',
        'json' => 'JSON handling',
        'fileinfo' => 'File type detection'
    ];
    
    foreach ($requiredExtensions as $ext => $desc) {
        if (extension_loaded($ext)) {
            $results[] = "✅ Extension $ext loaded ($desc)";
        } else {
            $errors[] = "⚠️ Extension $ext NOT loaded - $desc";
        }
    }
    
    // ========================================
    // FIX 9: Check File Permissions
    // ========================================
    $results[] = "\n🔐 Checking file permissions...";
    
    $criticalFiles = [
        'config.php' => 'Configuration file',
        'dashboard.php' => 'Main dashboard',
        'upload_handler.php' => 'Upload handler'
    ];
    
    foreach ($criticalFiles as $file => $desc) {
        if (file_exists($file)) {
            $results[] = "✅ File exists: $file ($desc)";
        } else {
            $errors[] = "⚠️ Missing file: $file ($desc)";
        }
    }
    
    // ========================================
    // COMPLETION
    // ========================================
    $results[] = "\n🎉 Auto Fix Completed!";
    $results[] = "Total fixes applied: " . count($results);
    $results[] = "Total errors: " . count($errors);
    
    if (count($errors) == 0) {
        $results[] = "\n✅ ALL FIXES APPLIED SUCCESSFULLY!";
        $results[] = "Your system is now ready to use.";
    } else {
        $results[] = "\n⚠️ SOME ISSUES REMAIN:";
        $results[] = "Please review the errors above and fix manually.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Fix - Digital Signage</title>
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
        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .warning h3 {
            color: #856404;
            margin-bottom: 10px;
        }
        .warning ul {
            margin-left: 20px;
            color: #856404;
        }
        .warning li {
            margin-bottom: 8px;
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
            transition: transform 0.2s;
            width: 100%;
            margin-bottom: 20px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .result-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
            max-height: 600px;
            overflow-y: auto;
        }
        .result-box pre {
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
        }
        .success {
            background: #d4edda;
            border: 2px solid #28a745;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .success h3 {
            color: #155724;
            margin-bottom: 10px;
        }
        .error-box {
            background: #f8d7da;
            border: 2px solid #dc3545;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .error-box h3 {
            color: #721c24;
            margin-bottom: 10px;
        }
        .error-box pre {
            color: #721c24;
            white-space: pre-wrap;
        }
        .checklist {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .checklist h3 {
            color: #1976d2;
            margin-bottom: 15px;
        }
        .checklist ul {
            margin-left: 20px;
        }
        .checklist li {
            margin-bottom: 10px;
            color: #1565c0;
        }
        .next-steps {
            background: #d1ecf1;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
        }
        .next-steps h3 {
            color: #0c5460;
            margin-bottom: 15px;
        }
        .next-steps ol {
            margin-left: 20px;
        }
        .next-steps li {
            margin-bottom: 10px;
            color: #0c5460;
        }
        .links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .link-btn {
            padding: 12px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            transition: all 0.2s;
        }
        .link-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Auto Fix Script</h1>
        <p class="subtitle">One-Click System Repair - Digital Signage BMFR Kelas II Manado</p>
        
        <div class="warning">
            <h3>⚠️ PENTING - Baca Sebelum Menjalankan!</h3>
            <ul>
                <li><strong>Backup dulu!</strong> Backup database dan files sebelum run script ini</li>
                <li>Script ini akan membuat perubahan pada database dan files</li>
                <li>Pastikan XAMPP (Apache & MySQL) sudah running</li>
                <li>Proses mungkin memakan waktu 1-2 menit</li>
                <li><strong>Delete file ini setelah selesai</strong> untuk keamanan</li>
            </ul>
        </div>
        
        <div class="checklist">
            <h3>📋 What This Script Will Fix:</h3>
            <ul>
                <li>✓ Create missing folders (uploads, backups, logs)</li>
                <li>✓ Create security .htaccess in uploads folder</li>
                <li>✓ Add missing database columns (video, role, etc.)</li>
                <li>✓ Create missing tables (activity_log, analytics, settings)</li>
                <li>✓ Add performance indexes to database</li>
                <li>✓ Optimize database tables</li>
                <li>✓ Check PHP extensions</li>
                <li>✓ Verify critical files exist</li>
            </ul>
        </div>
        
        <?php if (!isset($_POST['run_fix'])): ?>
        
        <form method="POST">
            <button type="submit" name="run_fix" class="btn" onclick="this.disabled=true; this.textContent='⏳ Running... Please wait...';">
                🚀 Run Auto Fix Now
            </button>
        </form>
        
        <div class="next-steps">
            <h3>ℹ️ After Running Auto Fix:</h3>
            <ol>
                <li>Review the results below</li>
                <li>Fix any remaining errors manually</li>
                <li>Login to dashboard and test upload</li>
                <li>Delete this file (auto_fix.php) for security</li>
                <li>Run check_system.php to verify everything</li>
            </ol>
        </div>
        
        <?php else: ?>
        
        <?php if (count($errors) == 0): ?>
        <div class="success">
            <h3>✅ Auto Fix Completed Successfully!</h3>
            <p>All fixes have been applied. Your system is now ready to use.</p>
        </div>
        <?php else: ?>
        <div class="error-box">
            <h3>⚠️ Auto Fix Completed with Errors</h3>
            <p>Some issues remain. Please review the logs below and fix manually.</p>
        </div>
        <?php endif; ?>
        
        <div class="result-box">
            <h3>📊 Fix Results:</h3>
            <pre><?php echo implode("\n", $results); ?></pre>
        </div>
        
        <?php if (count($errors) > 0): ?>
        <div class="error-box">
            <h3>❌ Errors Encountered:</h3>
            <pre><?php echo implode("\n", $errors); ?></pre>
        </div>
        <?php endif; ?>
        
        <div class="next-steps">
            <h3>✅ Next Steps:</h3>
            <ol>
                <li><strong>Test Upload:</strong> Try uploading a test image</li>
                <li><strong>Check Display:</strong> Open display preview and verify content shows</li>
                <li><strong>Run System Check:</strong> Access check_system.php for full verification</li>
                <li><strong>Delete This File:</strong> Remove auto_fix.php for security</li>
                <li><strong>Change Password:</strong> Change default admin password</li>
            </ol>
        </div>
        
        <div class="links">
            <a href="auth/login.php" class="link-btn">🔐 Login</a>
            <a href="dashboard.php" class="link-btn">📊 Dashboard</a>
            <a href="check_system.php" class="link-btn">🔍 System Check</a>
            <a href="display/display_external.php" target="_blank" class="link-btn">📺 Preview Display</a>
        </div>
        
        <?php endif; ?>
    </div>
</body>
</html>