<?php
// Error reporting untuk development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Konfigurasi Database untuk Digital Signage BMFR Kelas II Manado
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'digital_signage');
define('DB_PORT', 3306); // Default MySQL port

// Koneksi Database dengan Error Handling
function getConnection() {
    static $conn = null;
    
    // Return existing connection if available
    if ($conn !== null && $conn->ping()) {
        return $conn;
    }
    
    try {
        // Attempt connection with custom port
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        
        if ($conn->connect_error) {
            throw new Exception($conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
        
    } catch (Exception $e) {
        // Display user-friendly error page
        displayDatabaseError($e->getMessage());
        exit;
    }
}

/**
 * Display Database Error Page
 */
function displayDatabaseError($errorMessage) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Connection Error</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 20px;
            }
            .error-container {
                background: white;
                padding: 40px;
                border-radius: 15px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                max-width: 600px;
                width: 100%;
            }
            .error-icon {
                font-size: 60px;
                text-align: center;
                margin-bottom: 20px;
            }
            h1 {
                color: #dc3545;
                text-align: center;
                margin-bottom: 20px;
            }
            .error-message {
                background: #f8d7da;
                color: #721c24;
                padding: 15px;
                border-radius: 8px;
                border-left: 4px solid #dc3545;
                margin: 20px 0;
                word-break: break-word;
            }
            .solution-box {
                background: #d1ecf1;
                padding: 20px;
                border-radius: 8px;
                border-left: 4px solid #17a2b8;
                margin: 20px 0;
            }
            .solution-box h3 {
                color: #0c5460;
                margin-bottom: 15px;
            }
            .solution-box ol {
                margin-left: 20px;
            }
            .solution-box li {
                margin-bottom: 10px;
                color: #0c5460;
            }
            .solution-box code {
                background: #fff;
                padding: 2px 6px;
                border-radius: 3px;
                color: #d63384;
            }
            .button-group {
                text-align: center;
                margin-top: 30px;
            }
            .btn {
                display: inline-block;
                padding: 12px 30px;
                margin: 5px;
                background: #667eea;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                font-weight: 600;
                transition: background 0.3s;
            }
            .btn:hover {
                background: #5568d3;
            }
            .btn-secondary {
                background: #6c757d;
            }
            .btn-secondary:hover {
                background: #5a6268;
            }
            .tech-details {
                margin-top: 20px;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 8px;
                font-size: 12px;
                color: #6c757d;
            }
            .tech-details summary {
                cursor: pointer;
                font-weight: 600;
                color: #495057;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">🔌</div>
            <h1>Koneksi Database Gagal</h1>
            
            <div class="error-message">
                <strong>Error:</strong> <?= htmlspecialchars($errorMessage) ?>
            </div>
            
            <div class="solution-box">
                <h3>🔧 Cara Memperbaiki:</h3>
                <ol>
                    <li>
                        <strong>Start MySQL di XAMPP:</strong>
                        <ul>
                            <li>Buka <code>XAMPP Control Panel</code></li>
                            <li>Klik tombol <strong>Start</strong> di samping <strong>MySQL</strong></li>
                            <li>Tunggu sampai background berubah hijau</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Jika MySQL tidak mau start:</strong>
                        <ul>
                            <li>Port 3306 mungkin sudah dipakai aplikasi lain</li>
                            <li>Cek di <strong>XAMPP → Config → my.ini</strong></li>
                            <li>Ganti <code>port=3306</code> menjadi <code>port=3307</code></li>
                            <li>Update <code>config.php</code>: <code>define('DB_PORT', 3307);</code></li>
                        </ul>
                    </li>
                    <li>
                        <strong>Pastikan database sudah dibuat:</strong>
                        <ul>
                            <li>Buka <code>http://localhost/phpmyadmin</code></li>
                            <li>Buat database dengan nama: <code>digital_signage</code></li>
                            <li>Import file <code>database.sql</code></li>
                        </ul>
                    </li>
                </ol>
            </div>
            
            <div class="button-group">
                <a href="javascript:location.reload()" class="btn">🔄 Coba Lagi</a>
                <a href="http://localhost/phpmyadmin" target="_blank" class="btn btn-secondary">📊 Buka phpMyAdmin</a>
            </div>
            
            <details class="tech-details">
                <summary>📋 Technical Details</summary>
                <ul>
                    <li><strong>Host:</strong> <?= DB_HOST ?></li>
                    <li><strong>Port:</strong> <?= DB_PORT ?></li>
                    <li><strong>User:</strong> <?= DB_USER ?></li>
                    <li><strong>Database:</strong> <?= DB_NAME ?></li>
                    <li><strong>Time:</strong> <?= date('Y-m-d H:i:s') ?></li>
                    <li><strong>PHP Version:</strong> <?= PHP_VERSION ?></li>
                </ul>
            </details>
        </div>
    </body>
    </html>
    <?php
}

// Folder upload
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');
define('BACKUP_DIR', __DIR__ . '/backups/');

// Allowed file types
define('ALLOWED_IMAGE_EXT', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_VIDEO_EXT', ['mp4', 'webm', 'ogg']);
define('MAX_FILE_SIZE', 200 * 1024 * 1024); // 200mb

// Buat folder upload jika belum ada
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fungsi cek login
function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Fungsi redirect jika belum login
function requireLogin() {
    if (!isLoggedIn()) {
        $loginPath = 'auth/login.php';
        $currentPath = $_SERVER['PHP_SELF'];
        
        // Detect if we're in a subdirectory
        if (strpos($currentPath, '/management/') !== false || 
            strpos($currentPath, '/manage_display/') !== false) {
            $loginPath = '../auth/login.php';
        }
        
        header('Location: ' . $loginPath);
        exit;
    }
}

/**
 * Test database connection (untuk diagnostic)
 */
function testDatabaseConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        
        if ($conn->connect_error) {
            return [
                'success' => false,
                'error' => $conn->connect_error,
                'error_code' => $conn->connect_errno
            ];
        }
        
        // Test query
        $result = $conn->query("SELECT 1");
        
        $conn->close();
        
        return [
            'success' => true,
            'message' => 'Connection successful'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
?>