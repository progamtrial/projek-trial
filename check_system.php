<?php
/**
 * SYSTEM DIAGNOSTIC TOOL
 * Untuk mengecek masalah pada sistem Digital Signage
 * Akses: http://localhost/digital-signage/check_system.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Check - Digital Signage</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #667eea; margin-bottom: 20px; }
        h2 { color: #333; margin: 30px 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #667eea; }
        .status { padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid; }
        .status-ok { background: #d4edda; border-color: #28a745; color: #155724; }
        .status-warning { background: #fff3cd; border-color: #ffc107; color: #856404; }
        .status-error { background: #f8d7da; border-color: #dc3545; color: #721c24; }
        .status-info { background: #d1ecf1; border-color: #17a2b8; color: #0c5460; }
        .icon { font-size: 20px; margin-right: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        .btn { display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #5568d3; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .fix-btn { padding: 8px 16px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .fix-btn:hover { background: #218838; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 System Diagnostic Tool</h1>
        <p>Digital Signage BMFR Kelas II Manado - System Health Check</p>

        <?php
        // Database Connection Check
        echo "<h2>1. Database Connection</h2>";
        $dbOk = false;
        try {
            $conn = new mysqli('localhost', 'root', '', 'digital_signage');
            if ($conn->connect_error) {
                echo "<div class='status status-error'>";
                echo "<span class='icon'>❌</span>";
                echo "Database connection failed: " . $conn->connect_error;
                echo "</div>";
            } else {
                echo "<div class='status status-ok'>";
                echo "<span class='icon'>✅</span>";
                echo "Database connection successful";
                echo "</div>";
                $dbOk = true;
            }
        } catch (Exception $e) {
            echo "<div class='status status-error'>";
            echo "<span class='icon'>❌</span>";
            echo "Error: " . $e->getMessage();
            echo "</div>";
        }

        // Check Tables
        if ($dbOk) {
            echo "<h2>2. Database Tables</h2>";
            
            $requiredTables = [
                'admin' => 'User accounts',
                'konten_layar' => 'Content management',
                'activity_log' => 'Activity logging',
                'content_analytics' => 'Analytics tracking',
                'rss_feeds' => 'RSS feed sources',
                'rss_items' => 'RSS feed items',
                'backup_log' => 'Backup history',
                'settings' => 'System settings',
                'display_zones' => 'Display configurations',
                'api_tokens' => 'API access tokens'
            ];
            
            echo "<table>";
            echo "<tr><th>Table Name</th><th>Status</th><th>Description</th></tr>";
            
            $missingTables = [];
            foreach ($requiredTables as $table => $desc) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                $exists = $result->num_rows > 0;
                
                echo "<tr>";
                echo "<td><code>$table</code></td>";
                echo "<td>" . ($exists ? "✅ Exists" : "❌ Missing") . "</td>";
                echo "<td>$desc</td>";
                echo "</tr>";
                
                if (!$exists) {
                    $missingTables[] = $table;
                }
            }
            echo "</table>";
            
            if (!empty($missingTables)) {
                echo "<div class='status status-error'>";
                echo "<span class='icon'>❌</span>";
                echo "Missing tables: " . implode(', ', $missingTables);
                echo "<br><br><strong>Solution:</strong> Run <code>database_enhancement.sql</code> to create missing tables.";
                echo "</div>";
            } else {
                echo "<div class='status status-ok'>";
                echo "<span class='icon'>✅</span>";
                echo "All required tables exist";
                echo "</div>";
            }
        }

        // Check Files
        echo "<h2>3. Required Files</h2>";
        
        $requiredFiles = [
            'config.php' => 'Basic configuration',
            'enhanced_config.php' => 'Enhanced configuration (REQUIRED for new features)',
            'dashboard.php' => 'Basic dashboard',
            'enhanced_dashboard.php' => 'Enhanced dashboard with new features',
            'BackupManager.php' => 'Backup management class',
            'RSSFeedManager.php' => 'RSS feed management class',
            'ImageProcessor.php' => 'Image processing class',
            'auth/login.php' => 'Login page',
            'auth/logout.php' => 'Logout script',
            'management/analytics.php' => 'Analytics page',
            'management/manage_users.php' => 'User management',
            'management/manage_rss.php' => 'RSS management',
            'management/manage_backup.php' => 'Backup management'
        ];
        
        echo "<table>";
        echo "<tr><th>File Path</th><th>Status</th><th>Description</th></tr>";
        
        $missingFiles = [];
        foreach ($requiredFiles as $file => $desc) {
            $exists = file_exists($file);
            
            echo "<tr>";
            echo "<td><code>$file</code></td>";
            echo "<td>" . ($exists ? "✅ Found" : "❌ Missing") . "</td>";
            echo "<td>$desc</td>";
            echo "</tr>";
            
            if (!$exists) {
                $missingFiles[] = $file;
            }
        }
        echo "</table>";
        
        if (!empty($missingFiles)) {
            echo "<div class='status status-error'>";
            echo "<span class='icon'>❌</span>";
            echo "Missing files detected. <strong>This is why your new features don't work!</strong>";
            echo "</div>";
        }

        // Check Directories
        echo "<h2>4. Directory Structure</h2>";
        
        $requiredDirs = [
            'uploads' => 'Uploaded content storage',
            'backups' => 'Backup storage',
            'logs' => 'Log files',
            'auth' => 'Authentication files',
            'display' => 'Display pages',
            'manage_display' => 'Display management',
            'management' => 'System management'
        ];
        
        echo "<table>";
        echo "<tr><th>Directory</th><th>Exists</th><th>Writable</th><th>Purpose</th></tr>";
        
        foreach ($requiredDirs as $dir => $desc) {
            $exists = is_dir($dir);
            $writable = $exists && is_writable($dir);
            
            echo "<tr>";
            echo "<td><code>$dir/</code></td>";
            echo "<td>" . ($exists ? "✅" : "❌") . "</td>";
            echo "<td>" . ($writable ? "✅" : "❌") . "</td>";
            echo "<td>$desc</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Check PHP Extensions
        echo "<h2>5. PHP Extensions</h2>";
        
        $requiredExtensions = [
            'mysqli' => 'Database connectivity',
            'gd' => 'Image processing',
            'json' => 'JSON handling',
            'zip' => 'Backup compression',
            'xml' => 'RSS feed parsing',
            'mbstring' => 'String handling'
        ];
        
        echo "<table>";
        echo "<tr><th>Extension</th><th>Status</th><th>Purpose</th></tr>";
        
        foreach ($requiredExtensions as $ext => $desc) {
            $loaded = extension_loaded($ext);
            
            echo "<tr>";
            echo "<td><code>$ext</code></td>";
            echo "<td>" . ($loaded ? "✅ Loaded" : "❌ Not loaded") . "</td>";
            echo "<td>$desc</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Check Session
        echo "<h2>6. Session Status</h2>";
        if (session_status() === PHP_SESSION_ACTIVE) {
            echo "<div class='status status-ok'>";
            echo "<span class='icon'>✅</span>";
            echo "Session is active";
            echo "</div>";
        } else {
            echo "<div class='status status-warning'>";
            echo "<span class='icon'>⚠️</span>";
            echo "Session is not active";
            echo "</div>";
        }

        // Common Issues
        echo "<h2>7. Common Issues & Solutions</h2>";
        
        echo "<div class='status status-info'>";
        echo "<h3>📋 Why New Features Don't Appear:</h3>";
        echo "<ol>";
        echo "<li><strong>Missing enhanced_config.php</strong> - File ini WAJIB ada untuk fitur baru</li>";
        echo "<li><strong>Using wrong dashboard</strong> - Gunakan enhanced_dashboard.php, bukan dashboard.php</li>";
        echo "<li><strong>Missing database tables</strong> - Run database_enhancement.sql</li>";
        echo "<li><strong>Missing class files</strong> - BackupManager.php, RSSFeedManager.php, ImageProcessor.php</li>";
        echo "<li><strong>Wrong file paths</strong> - Check if management/ folder exists</li>";
        echo "</ol>";
        echo "</div>";

        // Quick Fixes
        echo "<h2>8. Quick Fixes</h2>";
        
        echo "<div class='status status-info'>";
        echo "<h3>🔧 Step-by-Step Fix:</h3>";
        echo "<pre>";
        echo "1. Pastikan file enhanced_config.php ada di root folder\n";
        echo "2. Rename enhanced_dashboard.php menjadi dashboard.php (backup yang lama)\n";
        echo "3. Import database_enhancement.sql ke database\n";
        echo "4. Buat folder 'management' jika belum ada\n";
        echo "5. Copy semua file management (analytics.php, manage_users.php, dll)\n";
        echo "6. Pastikan folder backups, logs ada dan writable (chmod 755)\n";
        echo "7. Clear browser cache dan cookies\n";
        echo "8. Logout dan login kembali\n";
        echo "</pre>";
        echo "</div>";

        // Action Links
        echo "<h2>9. Actions</h2>";
        echo "<a href='auth/login.php' class='btn'>🔐 Go to Login</a>";
        echo "<a href='dashboard.php' class='btn'>📊 Open Dashboard</a>";
        echo "<a href='enhanced_dashboard.php' class='btn'>🎯 Open Enhanced Dashboard</a>";
        echo "<a href='setup.php' class='btn'>⚙️ Run Setup</a>";

        if ($dbOk) {
            $conn->close();
        }
        ?>
    </div>
</body>
</html>