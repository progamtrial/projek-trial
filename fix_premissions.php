<?php
/**
 * FIX PERMISSIONS TOOL
 * Untuk memperbaiki masalah "Access Denied: Insufficient permissions"
 * Akses: http://localhost/digital-balmon/fix_permissions.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load config
if (file_exists('enhanced_config.php')) {
    require_once 'enhanced_config.php';
} else {
    require_once 'config.php';
}

$success = [];
$errors = [];
$conn = getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'fix_roles') {
        // Add role column if not exists
        $alterQuery = "ALTER TABLE `admin` 
                       ADD COLUMN IF NOT EXISTS `role` ENUM('superadmin', 'admin', 'editor', 'viewer') DEFAULT 'admin' AFTER `nama`,
                       ADD COLUMN IF NOT EXISTS `email` VARCHAR(100) DEFAULT NULL AFTER `role`,
                       ADD COLUMN IF NOT EXISTS `is_active` BOOLEAN DEFAULT TRUE AFTER `email`,
                       ADD COLUMN IF NOT EXISTS `last_login` DATETIME DEFAULT NULL AFTER `is_active`";
        
        if ($conn->query($alterQuery)) {
            $success[] = "Kolom role berhasil ditambahkan/diupdate";
        } else {
            // Try individual columns
            @$conn->query("ALTER TABLE `admin` ADD COLUMN `role` ENUM('superadmin', 'admin', 'editor', 'viewer') DEFAULT 'admin' AFTER `nama`");
            @$conn->query("ALTER TABLE `admin` ADD COLUMN `email` VARCHAR(100) DEFAULT NULL AFTER `role`");
            @$conn->query("ALTER TABLE `admin` ADD COLUMN `is_active` BOOLEAN DEFAULT TRUE AFTER `email`");
            @$conn->query("ALTER TABLE `admin` ADD COLUMN `last_login` DATETIME DEFAULT NULL AFTER `is_active`");
            $success[] = "Kolom ditambahkan (beberapa mungkin sudah ada)";
        }
        
        // Update existing users
        $conn->query("UPDATE `admin` SET `role` = 'admin' WHERE `role` IS NULL OR `role` = ''");
        $conn->query("UPDATE `admin` SET `is_active` = 1");
        
        // Set first user as superadmin
        $conn->query("UPDATE `admin` SET `role` = 'superadmin' WHERE `id` = 1 OR `username` = 'admin'");
        
        $success[] = "Role user berhasil diupdate";
    }
    
    elseif ($action === 'update_user_role') {
        $userId = (int)$_POST['user_id'];
        $newRole = $_POST['new_role'];
        
        $stmt = $conn->prepare("UPDATE `admin` SET `role` = ? WHERE `id` = ?");
        $stmt->bind_param("si", $newRole, $userId);
        
        if ($stmt->execute()) {
            $success[] = "Role user berhasil diupdate";
        } else {
            $errors[] = "Gagal update role: " . $stmt->error;
        }
        $stmt->close();
    }
    
    elseif ($action === 'reset_session') {
        session_unset();
        session_destroy();
        session_start();
        $success[] = "Session berhasil direset. Silakan login kembali.";
    }
}

// Get all users
$users = $conn->query("SELECT * FROM admin ORDER BY id")->fetch_all(MYSQLI_ASSOC);

// Check if role column exists
$columns = $conn->query("SHOW COLUMNS FROM admin LIKE 'role'")->fetch_all(MYSQLI_ASSOC);
$hasRoleColumn = count($columns) > 0;

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Permissions - Digital Signage</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            border-left: 4px solid;
        }
        .alert-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .alert-warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card h2 {
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-superadmin { background: #e3f2fd; color: #1976d2; }
        .badge-admin { background: #f3e5f5; color: #7b1fa2; }
        .badge-editor { background: #fff3e0; color: #f57c00; }
        .badge-viewer { background: #e8f5e9; color: #388e3c; }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-inactive { background: #f8d7da; color: #721c24; }
        
        select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .step-box {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #1976d2;
            margin: 20px 0;
        }
        .step-box h3 {
            color: #1976d2;
            margin-bottom: 15px;
        }
        .step-box ol {
            margin-left: 20px;
        }
        .step-box li {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Fix Permissions Tool</h1>
            <p>Perbaiki masalah "Access Denied: Insufficient permissions"</p>
        </div>
        
        <?php if (!empty($success)): ?>
            <?php foreach ($success as $msg): ?>
                <div class="alert alert-success">✅ <?= $msg ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $msg): ?>
                <div class="alert alert-error">❌ <?= $msg ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (!$hasRoleColumn): ?>
        <div class="alert alert-warning">
            <strong>⚠️ Warning:</strong> Kolom 'role' belum ada di tabel admin. Klik tombol di bawah untuk memperbaiki.
        </div>
        <?php endif; ?>
        
        <!-- Quick Fix -->
        <div class="card">
            <h2>🚀 Quick Fix</h2>
            <p>Klik tombol di bawah untuk otomatis memperbaiki struktur database dan role user:</p>
            <br>
            <form method="POST">
                <input type="hidden" name="action" value="fix_roles">
                <button type="submit" class="btn btn-primary">
                    🔧 Fix Roles & Permissions Sekarang
                </button>
            </form>
        </div>
        
        <!-- User Management -->
        <div class="card">
            <h2>👥 Daftar User & Role</h2>
            
            <?php if (empty($users)): ?>
                <div class="alert alert-warning">Tidak ada user ditemukan.</div>
            <?php else: ?>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Nama</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                        <td><?= htmlspecialchars($user['nama']) ?></td>
                        <td>
                            <?php if (isset($user['role'])): ?>
                                <span class="badge badge-<?= $user['role'] ?>">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-inactive">No Role</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($user['is_active'])): ?>
                                <span class="badge badge-<?= $user['is_active'] ? 'active' : 'inactive' ?>">
                                    <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-active">Active</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($hasRoleColumn): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="update_user_role">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <select name="new_role" onchange="this.form.submit()">
                                    <option value="">-- Ubah Role --</option>
                                    <option value="superadmin">Superadmin</option>
                                    <option value="admin">Admin</option>
                                    <option value="editor">Editor</option>
                                    <option value="viewer">Viewer</option>
                                </select>
                            </form>
                            <?php else: ?>
                            <span style="color: #999;">Fix roles first</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php endif; ?>
        </div>
        
        <!-- Additional Actions -->
        <div class="card">
            <h2>🛠️ Additional Actions</h2>
            
            <form method="POST" style="display: inline-block; margin-right: 10px;">
                <input type="hidden" name="action" value="reset_session">
                <button type="submit" class="btn btn-warning">
                    🔄 Reset Session (Logout Semua)
                </button>
            </form>
            
            <a href="dashboard.php" class="btn btn-success">
                ✅ Selesai - Kembali ke Dashboard
            </a>
        </div>
        
        <!-- Instructions -->
        <div class="step-box">
            <h3>📋 Langkah-langkah Perbaikan Manual:</h3>
            <ol>
                <li>Klik tombol <strong>"Fix Roles & Permissions Sekarang"</strong> di atas</li>
                <li>Tunggu sampai muncul pesan sukses</li>
                <li>Klik <strong>"Reset Session"</strong> untuk logout semua user</li>
                <li>Login kembali dengan username dan password Anda</li>
                <li>Coba akses fitur yang sebelumnya "Access Denied"</li>
                <li>Jika masih error, ubah role user menjadi <strong>"Superadmin"</strong> dari tabel di atas</li>
            </ol>
        </div>
        
        <div class="card">
            <h2>ℹ️ Role Permissions</h2>
            <table>
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Permissions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="badge badge-superadmin">Superadmin</span></td>
                        <td>✅ Full Access - Semua fitur</td>
                    </tr>
                    <tr>
                        <td><span class="badge badge-admin">Admin</span></td>
                        <td>✅ User Management, Content, Settings, Analytics, Backup, RSS</td>
                    </tr>
                    <tr>
                        <td><span class="badge badge-editor">Editor</span></td>
                        <td>✅ Content Management, Analytics, RSS</td>
                    </tr>
                    <tr>
                        <td><span class="badge badge-viewer">Viewer</span></td>
                        <td>✅ View Content, Analytics (Read-only)</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>