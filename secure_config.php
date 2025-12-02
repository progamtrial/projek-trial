<?php
/**
 * SECURE ENHANCED CONFIG - Digital Signage BMFR Kelas II Manado
 * File ini menggantikan enhanced_config.php dengan security yang lebih baik
 * TESTED & PRODUCTION READY
 */

// Environment Detection
define('IS_DEVELOPMENT', $_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1');

// Error Reporting berdasarkan environment
if (IS_DEVELOPMENT) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/php_errors.log');
}

// Database Configuration (Gunakan environment variables di production)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'digital_signage');

// Directory Configuration
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');
define('BACKUP_DIR', __DIR__ . '/backups/');
define('LOG_DIR', __DIR__ . '/logs/');

// File Configuration
// File Configuration
define('ALLOWED_IMAGE_EXT', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_VIDEO_EXT', ['mp4', 'webm', 'ogg']);
define('MAX_FILE_SIZE', 200 * 1024 * 1024); // 200MB ← SUDAH DIUBAH

// MIME Type Whitelist (SECURITY ENHANCEMENT)
define('ALLOWED_IMAGE_MIMES', [
    'image/jpeg',
    'image/png', 
    'image/gif',
    'image/webp'
]);

define('ALLOWED_VIDEO_MIMES', [
    'video/mp4',
    'video/webm',
    'video/ogg'
]);

// Backup Configuration
define('BACKUP_RETENTION_DAYS', 30);
define('AUTO_BACKUP_ENABLED', true);
define('AUTO_BACKUP_INTERVAL', 86400); // 24 hours

// Security Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('PASSWORD_MIN_LENGTH', 8);

// Session Security Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
if (!IS_DEVELOPMENT) {
    ini_set('session.cookie_secure', 1); // Enable jika pakai HTTPS
}

// Start Session dengan Security
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    
    // Session Timeout Check
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['LAST_ACTIVITY'] = time();
    
    // Session Hijacking Prevention
    if (!isset($_SESSION['USER_AGENT'])) {
        $_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    if ($_SESSION['USER_AGENT'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
        session_unset();
        session_destroy();
        header('Location: auth/login.php?error=session_invalid');
        exit;
    }
}

// Role Definitions
define('ROLES', [
    'superadmin' => 'Super Admin',
    'admin' => 'Administrator',
    'editor' => 'Editor',
    'viewer' => 'Viewer'
]);

// Role Permissions
define('ROLE_PERMISSIONS', [
    'superadmin' => ['all'],
    'admin' => ['manage_users', 'manage_content', 'manage_settings', 'view_analytics', 'manage_backup', 'manage_rss'],
    'editor' => ['manage_content', 'view_analytics'],
    'viewer' => ['view_content', 'view_analytics']
]);

/**
 * Get Database Connection with Error Handling
 */
function getConnection() {
    static $conn = null;
    
    if ($conn !== null) {
        return $conn;
    }
    
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            logToFile('Database connection failed: ' . $conn->connect_error, 'database_errors.log');
            if (IS_DEVELOPMENT) {
                die("Connection failed: " . $conn->connect_error);
            } else {
                die("Database connection error. Please contact administrator.");
            }
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
        
    } catch (Exception $e) {
        logToFile('Database exception: ' . $e->getMessage(), 'database_errors.log');
        die("Database error occurred.");
    }
}

/**
 * Generate CSRF Token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF Token
 */
function validateCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF Token Input HTML
 */
function csrfTokenField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Require CSRF Token (Call at start of POST handlers)
 */
function requireCSRFToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            logToFile('CSRF token validation failed from IP: ' . $_SERVER['REMOTE_ADDR'], 'security.log');
            http_response_code(403);
            die('CSRF token validation failed. Please refresh and try again.');
        }
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_nama']);
}

/**
 * Require login with redirect
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $currentPath = $_SERVER['PHP_SELF'];
        $loginPath = 'auth/login.php';
        
        if (strpos($currentPath, '/management/') !== false || strpos($currentPath, '/manage_display/') !== false) {
            $loginPath = '../auth/login.php';
        }
        
        header('Location: ' . $loginPath);
        exit;
    }
}

/**
 * Get current user data with caching
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
    $stmt = $conn->prepare("SELECT id, username, nama, email, role, is_active FROM admin WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $currentUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $currentUser;
}

/**
 * Check if user has specific role
 */
function hasRole($requiredRole) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = $_SESSION['admin_role'] ?? 'viewer';
    
    if ($userRole === 'superadmin') {
        return true;
    }
    
    $roleHierarchy = ['superadmin', 'admin', 'editor', 'viewer'];
    $userLevel = array_search($userRole, $roleHierarchy);
    $requiredLevel = array_search($requiredRole, $roleHierarchy);
    
    if ($userLevel === false || $requiredLevel === false) {
        return false;
    }
    
    return $userLevel <= $requiredLevel;
}

/**
 * Require specific role
 */
function requireRole($role) {
    requireLogin();
    
    if (!hasRole($role)) {
        logToFile('Unauthorized access attempt by user ID ' . $_SESSION['admin_id'] . ' to role: ' . $role, 'security.log');
        http_response_code(403);
        die("Access Denied: Insufficient permissions");
    }
}

/**
 * Validate and sanitize file upload
 * ENHANCED SECURITY VERSION
 */
function validateFileUpload($file, $type = 'image') {
    $errors = [];
    
    // Check if file was uploaded
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error: ' . $file['error'];
        return ['success' => false, 'errors' => $errors];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        $errors[] = 'File size exceeds maximum allowed (' . formatBytes(MAX_FILE_SIZE) . ')';
        return ['success' => false, 'errors' => $errors];
    }
    
    // Get file extension
    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Validate extension
    $allowedExt = $type === 'image' ? ALLOWED_IMAGE_EXT : ALLOWED_VIDEO_EXT;
    if (!in_array($ext, $allowedExt)) {
        $errors[] = 'Invalid file extension. Allowed: ' . implode(', ', $allowedExt);
        return ['success' => false, 'errors' => $errors];
    }
    
    // CRITICAL: Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedMimes = $type === 'image' ? ALLOWED_IMAGE_MIMES : ALLOWED_VIDEO_MIMES;
    if (!in_array($mimeType, $allowedMimes)) {
        $errors[] = 'Invalid file type detected. Expected ' . $type . ', got: ' . $mimeType;
        logToFile('Suspicious file upload attempt: ' . $mimeType . ' from IP: ' . $_SERVER['REMOTE_ADDR'], 'security.log');
        return ['success' => false, 'errors' => $errors];
    }
    
    // Generate secure random filename
    $secureFilename = bin2hex(random_bytes(16)) . '.' . $ext;
    
    return [
        'success' => true,
        'original_name' => $filename,
        'secure_filename' => $secureFilename,
        'extension' => $ext,
        'mime_type' => $mimeType,
        'size' => $file['size']
    ];
}

/**
 * Log activity to database with prepared statement
 */
function logActivity($action, $module, $description = '', $oldValue = null, $newValue = null) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $conn = getConnection();
    
    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'activity_log'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        return false;
    }
    
    $userId = $_SESSION['admin_id'];
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Sanitize description
    $description = strip_tags($description);
    
    $stmt = $conn->prepare(
        "INSERT INTO activity_log (user_id, action, module, description, ip_address, user_agent, old_value, new_value) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    
    $oldJson = $oldValue ? json_encode($oldValue, JSON_UNESCAPED_UNICODE) : null;
    $newJson = $newValue ? json_encode($newValue, JSON_UNESCAPED_UNICODE) : null;
    
    $stmt->bind_param("isssssss", $userId, $action, $module, $description, $ipAddress, $userAgent, $oldJson, $newJson);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Track content display for analytics
 */
function trackContentDisplay($kontenId, $displayType, $nomorLayar, $duration) {
    $conn = getConnection();
    
    $tableCheck = $conn->query("SHOW TABLES LIKE 'content_analytics'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        return false;
    }
    
    $displayDate = date('Y-m-d');
    $displayHour = (int)date('H');
    
    // Use INSERT ... ON DUPLICATE KEY UPDATE for atomic operation
    $stmt = $conn->prepare(
        "INSERT INTO content_analytics 
         (konten_id, display_type, nomor_layar, display_count, display_date, display_hour, total_duration) 
         VALUES (?, ?, ?, 1, ?, ?, ?)
         ON DUPLICATE KEY UPDATE 
         display_count = display_count + 1, 
         total_duration = total_duration + VALUES(total_duration)"
    );
    $stmt->bind_param("isiiii", $kontenId, $displayType, $nomorLayar, $displayDate, $displayHour, $duration);
    $result = $stmt->execute();
    $stmt->close();
    
    // Update view_count in konten_layar
    $updateStmt = $conn->prepare(
        "UPDATE konten_layar 
         SET view_count = COALESCE(view_count, 0) + 1, 
             last_displayed = NOW() 
         WHERE id = ?"
    );
    $updateStmt->bind_param("i", $kontenId);
    $updateStmt->execute();
    $updateStmt->close();
    
    return $result;
}

/**
 * Check login attempts and implement rate limiting
 */
function checkLoginAttempts($username) {
    $conn = getConnection();
    
    // Create login_attempts table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_ip (ip_address),
        INDEX idx_time (attempt_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $lockoutTime = date('Y-m-d H:i:s', time() - LOGIN_LOCKOUT_TIME);
    
    // Count recent failed attempts
    $stmt = $conn->prepare(
        "SELECT COUNT(*) as attempts FROM login_attempts 
         WHERE (username = ? OR ip_address = ?) 
         AND attempt_time > ?"
    );
    $stmt->bind_param("sss", $username, $ipAddress, $lockoutTime);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result['attempts'];
}

/**
 * Record failed login attempt
 */
function recordLoginAttempt($username) {
    $conn = getConnection();
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $conn->prepare(
        "INSERT INTO login_attempts (username, ip_address) VALUES (?, ?)"
    );
    $stmt->bind_param("ss", $username, $ipAddress);
    $stmt->execute();
    $stmt->close();
    
    logToFile("Failed login attempt for user: $username from IP: $ipAddress", 'security.log');
}

/**
 * Clear login attempts after successful login
 */
function clearLoginAttempts($username) {
    $conn = getConnection();
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $conn->prepare(
        "DELETE FROM login_attempts WHERE username = ? OR ip_address = ?"
    );
    $stmt->bind_param("ss", $username, $ipAddress);
    $stmt->execute();
    $stmt->close();
}

/**
 * Validate input string
 */
function validateInput($input, $type = 'string', $maxLength = 255) {
    $input = trim($input);
    
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL) !== false;
        
        case 'url':
            return filter_var($input, FILTER_VALIDATE_URL) !== false;
        
        case 'int':
            return filter_var($input, FILTER_VALIDATE_INT) !== false;
        
        case 'date':
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $input) && strtotime($input) !== false;
        
        case 'time':
            return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $input);
        
        case 'string':
        default:
            return strlen($input) <= $maxLength;
    }
}

/**
 * Sanitize output (prevent XSS)
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
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
function formatDateTime($datetime, $format = 'd M Y H:i') {
    if (!$datetime) return '-';
    return date($format, strtotime($datetime));
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
 * Get setting value
 */
function getSetting($key, $default = null) {
    static $settings = null;
    
    if ($settings === null) {
        $conn = getConnection();
        $tableCheck = $conn->query("SHOW TABLES LIKE 'settings'");
        
        if (!$tableCheck || $tableCheck->num_rows === 0) {
            return $default;
        }
        
        $result = $conn->query("SELECT key_name, key_value FROM settings");
        $settings = [];
        while ($row = $result->fetch_assoc()) {
            $settings[$row['key_name']] = $row['key_value'];
        }
    }
    
    return $settings[$key] ?? $default;
}

/**
 * Set setting value
 */
function setSetting($key, $value, $description = '') {
    $conn = getConnection();
    $userId = $_SESSION['admin_id'] ?? null;
    
    $tableCheck = $conn->query("SHOW TABLES LIKE 'settings'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        return false;
    }
    
    $stmt = $conn->prepare(
        "INSERT INTO settings (key_name, key_value, description, updated_by) 
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE 
         key_value = VALUES(key_value), 
         description = VALUES(description), 
         updated_by = VALUES(updated_by)"
    );
    $stmt->bind_param("sssi", $key, $value, $description, $userId);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Log to file with rotation
 */
function logToFile($message, $filename = 'system.log') {
    $logFile = LOG_DIR . $filename;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    
    // Create log directory if not exists
    if (!file_exists(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
    
    // Rotate log if > 10MB
    if (file_exists($logFile) && filesize($logFile) > 10 * 1024 * 1024) {
        $rotatedFile = LOG_DIR . date('Y-m-d_His') . '_' . $filename;
        rename($logFile, $rotatedFile);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Create necessary directories with proper permissions
 */
function createDirectories() {
    $dirs = [
        UPLOAD_DIR => 0755,
        BACKUP_DIR => 0755,
        LOG_DIR => 0755,
    ];
    
    foreach ($dirs as $dir => $permission) {
        if (!file_exists($dir)) {
            mkdir($dir, $permission, true);
        }
    }
}

// Auto-create directories
createDirectories();

/**
 * Check if table exists
 */
function tableExists($tableName) {
    static $checkedTables = [];
    
    if (isset($checkedTables[$tableName])) {
        return $checkedTables[$tableName];
    }
    
    $conn = getConnection();
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    $exists = $result && $result->num_rows > 0;
    
    $checkedTables[$tableName] = $exists;
    return $exists;
}

/**
 * Send security headers
 */
function sendSecurityHeaders() {
    if (!headers_sent()) {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        if (!IS_DEVELOPMENT) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}

// Send security headers automatically
sendSecurityHeaders();