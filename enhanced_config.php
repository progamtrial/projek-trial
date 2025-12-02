<?php
/**
 * ENHANCED CONFIG - Digital Signage BMFR Kelas II Manado
 * Fixed Permissions & Role Management
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'digital_signage');
define('DB_PORT', 3306);

// Directory Configuration
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');
define('BACKUP_DIR', __DIR__ . '/backups/');
define('LOG_DIR', __DIR__ . '/logs/');

// File Configuration
define('ALLOWED_IMAGE_EXT', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_VIDEO_EXT', ['mp4', 'webm', 'ogg']);
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB

// Backup Configuration
define('BACKUP_RETENTION_DAYS', 30);
define('AUTO_BACKUP_ENABLED', true);
define('AUTO_BACKUP_INTERVAL', 86400); // 24 hours

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Role Definitions
define('ROLES', [
    'superadmin' => 'Super Admin',
    'admin' => 'Administrator',
    'editor' => 'Editor',
    'viewer' => 'Viewer'
]);

// Role Permissions - FIXED VERSION
define('ROLE_PERMISSIONS', [
    'superadmin' => ['all'], // Full access to everything
    'admin' => ['manage_users', 'manage_content', 'manage_settings', 'view_analytics', 'manage_backup', 'manage_rss', 'activity_log'],
    'editor' => ['manage_content', 'view_analytics', 'manage_rss'],
    'viewer' => ['view_content', 'view_analytics']
]);

/**
 * Get Database Connection
 */
function getConnection() {
    static $conn = null;
    
    if ($conn !== null && @$conn->ping()) {
        return $conn;
    }
    
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        
        if ($conn->connect_error) {
            throw new Exception($conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
        
    } catch (Exception $e) {
        die("Database Error: " . $e->getMessage());
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_nama']);
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $currentPath = $_SERVER['PHP_SELF'];
        $loginPath = 'auth/login.php';
        
        if (strpos($currentPath, '/management/') !== false || 
            strpos($currentPath, '/manage_display/') !== false) {
            $loginPath = '../auth/login.php';
        }
        
        header('Location: ' . $loginPath);
        exit;
    }
}

/**
 * Get current user data
 */
function getCurrentUser() {
    static $currentUser = null;
    
    if ($currentUser !== null) {
        return $currentUser;
    }
    
    if (!isLoggedIn()) {
        return null;
    }
    
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM admin WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $currentUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Update session dengan role terbaru jika belum ada
    if ($currentUser && !isset($_SESSION['admin_role'])) {
        $_SESSION['admin_role'] = $currentUser['role'] ?? 'admin';
    }
    
    return $currentUser;
}

/**
 * Check if user has specific role - FIXED VERSION
 */
function hasRole($requiredRole) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Get user role from session or database
    $userRole = $_SESSION['admin_role'] ?? null;
    
    // If no role in session, get from database
    if (!$userRole) {
        $user = getCurrentUser();
        $userRole = $user['role'] ?? 'admin';
        $_SESSION['admin_role'] = $userRole;
    }
    
    // Superadmin has access to everything
    if ($userRole === 'superadmin') {
        return true;
    }
    
    // Role hierarchy check
    $roleHierarchy = ['superadmin', 'admin', 'editor', 'viewer'];
    $userLevel = array_search($userRole, $roleHierarchy);
    $requiredLevel = array_search($requiredRole, $roleHierarchy);
    
    // If role not found in hierarchy, default to allow for backward compatibility
    if ($userLevel === false || $requiredLevel === false) {
        return true; // Fallback: allow access
    }
    
    return $userLevel <= $requiredLevel;
}

/**
 * Check if user has specific permission - NEW
 */
function hasPermission($permission) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = $_SESSION['admin_role'] ?? null;
    
    if (!$userRole) {
        $user = getCurrentUser();
        $userRole = $user['role'] ?? 'admin';
        $_SESSION['admin_role'] = $userRole;
    }
    
    // Check if role has permission
    $rolePermissions = ROLE_PERMISSIONS[$userRole] ?? [];
    
    // 'all' means full access
    if (in_array('all', $rolePermissions)) {
        return true;
    }
    
    return in_array($permission, $rolePermissions);
}

/**
 * Require specific role - FIXED VERSION
 */
function requireRole($role) {
    requireLogin();
    
    // For backward compatibility, allow if user is logged in and no strict role enforcement
    if (!isset($_SESSION['admin_role'])) {
        // Try to get role from database
        $user = getCurrentUser();
        if ($user) {
            $_SESSION['admin_role'] = $user['role'] ?? 'admin';
        } else {
            // Fallback: assume admin role
            $_SESSION['admin_role'] = 'admin';
            return; // Allow access
        }
    }
    
    if (!hasRole($role)) {
        displayAccessDenied($role);
        exit;
    }
}

/**
 * Require specific permission - NEW
 */
function requirePermission($permission) {
    requireLogin();
    
    if (!hasPermission($permission)) {
        displayAccessDenied(null, $permission);
        exit;
    }
}

/**
 * Display Access Denied Page
 */
function displayAccessDenied($requiredRole = null, $requiredPermission = null) {
    $userRole = $_SESSION['admin_role'] ?? 'unknown';
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Segoe UI', sans-serif;
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
                max-width: 500px;
                width: 100%;
                text-align: center;
            }
            .error-icon {
                font-size: 80px;
                margin-bottom: 20px;
            }
            h1 {
                color: #dc3545;
                margin-bottom: 15px;
            }
            .error-message {
                color: #666;
                margin-bottom: 20px;
                line-height: 1.6;
            }
            .info-box {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
                text-align: left;
            }
            .info-box p {
                margin: 5px 0;
                font-size: 14px;
            }
            .info-box strong {
                color: #333;
            }
            .badge {
                display: inline-block;
                padding: 5px 10px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
            }
            .badge-danger { background: #f8d7da; color: #721c24; }
            .badge-info { background: #d1ecf1; color: #0c5460; }
            .btn {
                display: inline-block;
                padding: 12px 30px;
                margin: 10px 5px;
                background: #667eea;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                font-weight: 600;
            }
            .btn:hover {
                background: #5568d3;
            }
            .btn-secondary {
                background: #6c757d;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">🔒</div>
            <h1>Access Denied</h1>
            <p class="error-message">
                Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.
            </p>
            
            <div class="info-box">
                <p><strong>Role Anda:</strong> <span class="badge badge-info"><?= ROLES[$userRole] ?? $userRole ?></span></p>
                <?php if ($requiredRole): ?>
                <p><strong>Role Diperlukan:</strong> <span class="badge badge-danger"><?= ROLES[$requiredRole] ?? $requiredRole ?></span></p>
                <?php endif; ?>
                <?php if ($requiredPermission): ?>
                <p><strong>Permission Diperlukan:</strong> <span class="badge badge-danger"><?= $requiredPermission ?></span></p>
                <?php endif; ?>
            </div>
            
            <p style="color: #999; font-size: 14px; margin-bottom: 20px;">
                Hubungi administrator sistem untuk mengubah role atau permission Anda.
            </p>
            
            <a href="<?= strpos($_SERVER['PHP_SELF'], '/management/') !== false ? '../dashboard.php' : 'dashboard.php' ?>" class="btn">
                ← Kembali ke Dashboard
            </a>
            <a href="<?= strpos($_SERVER['PHP_SELF'], '/management/') !== false ? '../auth/logout.php' : 'auth/logout.php' ?>" class="btn btn-secondary">
                Logout
            </a>
        </div>
    </body>
    </html>
    <?php
}

/**
 * Log activity to database
 */
function logActivity($action, $module, $description = '', $oldValue = null, $newValue = null) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $conn = getConnection();
    
    $tableCheck = $conn->query("SHOW TABLES LIKE 'activity_log'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        return false;
    }
    
    $userId = $_SESSION['admin_id'];
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $conn->prepare(
        "INSERT INTO activity_log (user_id, action, module, description, ip_address, user_agent, old_value, new_value) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    
    $oldJson = $oldValue ? json_encode($oldValue) : null;
    $newJson = $newValue ? json_encode($newValue) : null;
    
    $stmt->bind_param("isssssss", $userId, $action, $module, $description, $ipAddress, $userAgent, $oldJson, $newJson);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Get recent activities
 */
function getRecentActivities($limit = 20) {
    $conn = getConnection();
    
    $tableCheck = $conn->query("SHOW TABLES LIKE 'activity_log'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        return [];
    }
    
    $stmt = $conn->prepare(
        "SELECT al.*, a.nama, a.username 
         FROM activity_log al 
         JOIN admin a ON al.user_id = a.id 
         ORDER BY al.created_at DESC 
         LIMIT ?"
    );
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $activities;
}

/**
 * Track content display
 */
function trackContentDisplay($kontenId, $displayType, $nomorLayar, $duration) {
    $conn = getConnection();
    
    $tableCheck = $conn->query("SHOW TABLES LIKE 'content_analytics'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        return false;
    }
    
    $displayDate = date('Y-m-d');
    $displayHour = (int)date('H');
    
    $stmt = $conn->prepare(
        "SELECT id FROM content_analytics 
         WHERE konten_id = ? AND display_date = ? AND display_hour = ? AND nomor_layar = ?"
    );
    $stmt->bind_param("isii", $kontenId, $displayDate, $displayHour, $nomorLayar);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $updateStmt = $conn->prepare(
            "UPDATE content_analytics 
             SET display_count = display_count + 1, total_duration = total_duration + ? 
             WHERE id = ?"
        );
        $updateStmt->bind_param("ii", $duration, $row['id']);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        $insertStmt = $conn->prepare(
            "INSERT INTO content_analytics (konten_id, display_type, nomor_layar, display_count, display_date, display_hour, total_duration) 
             VALUES (?, ?, ?, 1, ?, ?, ?)"
        );
        $insertStmt->bind_param("isiiii", $kontenId, $displayType, $nomorLayar, $displayDate, $displayHour, $duration);
        $insertStmt->execute();
        $insertStmt->close();
    }
    
    $updateKonten = $conn->prepare(
        "UPDATE konten_layar 
         SET view_count = COALESCE(view_count, 0) + 1, last_displayed = NOW() 
         WHERE id = ?"
    );
    $updateKonten->bind_param("i", $kontenId);
    $updateKonten->execute();
    $updateKonten->close();
    
    $stmt->close();
    return true;
}

/**
 * Format file size
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Format date time
 */
function formatDateTime($datetime) {
    if (!$datetime) return '-';
    return date('d M Y H:i', strtotime($datetime));
}

/**
 * Time ago format
 */
function timeAgo($datetime) {
    if (!$datetime) return 'Never';
    
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return $diff . ' detik lalu';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';
    
    return date('d M Y', $timestamp);
}

/**
 * Sanitize filename
 */
function sanitizeFilename($filename) {
    return preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
}

/**
 * Check if table exists
 */
function tableExists($tableName) {
    $conn = getConnection();
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result && $result->num_rows > 0;
}

/**
 * Get setting value
 */
function getSetting($key, $default = null) {
    if (!tableExists('settings')) {
        return $default;
    }
    
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT key_value FROM settings WHERE key_name = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $value = $result->fetch_assoc()['key_value'];
        $stmt->close();
        return $value;
    }
    
    $stmt->close();
    return $default;
}

/**
 * Set setting value
 */
function setSetting($key, $value, $description = '') {
    if (!tableExists('settings')) {
        return false;
    }
    
    $conn = getConnection();
    $userId = $_SESSION['admin_id'] ?? null;
    
    $checkStmt = $conn->prepare("SELECT id FROM settings WHERE key_name = ?");
    $checkStmt->bind_param("s", $key);
    $checkStmt->execute();
    $exists = $checkStmt->get_result()->num_rows > 0;
    $checkStmt->close();
    
    if ($exists) {
        $stmt = $conn->prepare("UPDATE settings SET key_value = ?, description = ?, updated_by = ? WHERE key_name = ?");
        $stmt->bind_param("ssis", $value, $description, $userId, $key);
    } else {
        $stmt = $conn->prepare("INSERT INTO settings (key_name, key_value, description, updated_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $key, $value, $description, $userId);
    }
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Create necessary directories
 */
function createDirectories() {
    $dirs = [UPLOAD_DIR, BACKUP_DIR, LOG_DIR];
    
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

createDirectories();

/**
 * Log to file
 */
function logToFile($message, $filename = 'system.log') {
    $logFile = LOG_DIR . $filename;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}
?>