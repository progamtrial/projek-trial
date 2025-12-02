<?php
/**
 * AUTO SETUP SCRIPT
 * Jalankan file ini sekali untuk setup otomatis
 * Akses: http://localhost/digital-signage/setup.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Setup Digital Signage</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #667eea; }
        .success { color: green; padding: 10px; background: #d4edda; margin: 10px 0; border-radius: 5px; }
        .error { color: red; padding: 10px; background: #f8d7da; margin: 10px 0; border-radius: 5px; }
        .info { color: #856404; padding: 10px; background: #fff3cd; margin: 10px 0; border-radius: 5px; }
        .step { padding: 15px; margin: 15px 0; border-left: 4px solid #667eea; background: #f9f9f9; }
        .btn { padding: 15px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0; }
    </style>
</head>
<body>
<div class='container'>
<h1>🚀 Setup Digital Signage - BMFR Kelas II Manado</h1>";

$errors = [];
$success = [];

// STEP 1: Buat Folder Uploads
echo "<div class='step'><h3>Step 1: Membuat Folder Uploads</h3>";
if (!file_exists('uploads')) {
    if (mkdir('uploads', 0755, true)) {
        echo "<div class='success'>✅ Folder 'uploads' berhasil dibuat!</div>";
        $success[] = "Folder uploads dibuat";
    } else {
        echo "<div class='error'>❌ Gagal membuat folder 'uploads'. Buat manual!</div>";
        $errors[] = "Folder uploads gagal dibuat";
    }
} else {
    echo "<div class='info'>ℹ️ Folder 'uploads' sudah ada.</div>";
    $success[] = "Folder uploads ada";
}

// Cek writable
if (is_writable('uploads')) {
    echo "<div class='success'>✅ Folder 'uploads' dapat ditulis (writable).</div>";
} else {
    echo "<div class='error'>❌ Folder 'uploads' tidak dapat ditulis. Ubah permission!</div>";
    $errors[] = "Folder uploads tidak writable";
}
echo "</div>";

// STEP 2: Test Koneksi Database
echo "<div class='step'><h3>Step 2: Test Koneksi Database</h3>";
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'digital_signage';

$conn = @new mysqli($db_host, $db_user, $db_pass);

if ($conn->connect_error) {
    echo "<div class='error'>❌ Koneksi ke MySQL gagal: " . $conn->connect_error . "</div>";
    $errors[] = "Koneksi MySQL gagal";
} else {
    echo "<div class='success'>✅ Koneksi ke MySQL berhasil!</div>";
    $success[] = "Koneksi MySQL berhasil";
    
    // Cek apakah database sudah ada
    $result = $conn->query("SHOW DATABASES LIKE '$db_name'");
    if ($result->num_rows > 0) {
        echo "<div class='info'>ℹ️ Database '$db_name' sudah ada.</div>";
        $conn->select_db($db_name);
        
        // Cek tabel
        $tables = ['admin', 'konten_layar'];
        $table_exists = 0;
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows > 0) {
                $table_exists++;
            }
        }
        
        if ($table_exists == 2) {
            echo "<div class='success'>✅ Semua tabel sudah ada dan siap digunakan!</div>";
            $success[] = "Semua tabel ada";
        } else {
            echo "<div class='error'>❌ Database ada tapi tabel tidak lengkap. Import ulang database.sql!</div>";
            $errors[] = "Tabel tidak lengkap";
        }
    } else {
        echo "<div class='error'>❌ Database '$db_name' belum dibuat!</div>";
        echo "<div class='info'>📋 Silakan:</div>";
        echo "<ol>
            <li>Buka <a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a></li>
            <li>Klik 'New' atau 'Databases'</li>
            <li>Buat database dengan nama: <strong>digital_signage</strong></li>
            <li>Pilih database tersebut</li>
            <li>Klik tab 'Import'</li>
            <li>Upload file <strong>database.sql</strong></li>
            <li>Klik 'Go'</li>
            <li>Refresh halaman ini</li>
        </ol>";
        $errors[] = "Database belum dibuat";
    }
    
    $conn->close();
}
echo "</div>";

// STEP 3: Cek Extension PHP
echo "<div class='step'><h3>Step 3: Cek Extension PHP</h3>";
$required_extensions = [
    'mysqli' => 'Database connection',
    'gd' => 'Image processing (upload gambar)',
    'json' => 'JSON processing'
];

foreach ($required_extensions as $ext => $desc) {
    if (extension_loaded($ext)) {
        echo "<div class='success'>✅ Extension '$ext' aktif - $desc</div>";
        $success[] = "Extension $ext aktif";
    } else {
        echo "<div class='error'>❌ Extension '$ext' tidak aktif - $desc</div>";
        if ($ext == 'gd') {
            echo "<div class='info'>📋 Cara aktifkan GD:
            <ol>
                <li>Buka file: <strong>C:\\xampp\\php\\php.ini</strong></li>
                <li>Cari baris: <code>;extension=gd</code></li>
                <li>Hapus tanda ; sehingga jadi: <code>extension=gd</code></li>
                <li>Save file</li>
                <li>Restart Apache di XAMPP</li>
            </ol></div>";
        }
        $errors[] = "Extension $ext tidak aktif";
    }
}
echo "</div>";

// STEP 4: Cek Struktur File
echo "<div class='step'><h3>Step 4: Cek Struktur File</h3>";
$required_files = [
    'index.php' => 'Landing page',
    'config.php' => 'Database configuration',
    'auth/login.php' => 'Login page',
    'dashboard.php' => 'Admin dashboard',
    'manage_external.php' => 'Manage external displays',
    'manage_internal.php' => 'Manage internal displays',
    'display/display_external.php' => 'External TV display',
    'display/display_internal.php' => 'Internal TV display',
    'auth/logout.php' => 'Logout script',
    'database.sql' => 'Database schema'
];

$missing_files = [];
foreach ($required_files as $file => $desc) {
    if (file_exists($file)) {
        echo "<div class='success'>✅ $file - $desc</div>";
    } else {
        echo "<div class='error'>❌ $file TIDAK ADA - $desc</div>";
        $missing_files[] = $file;
        $errors[] = "File $file tidak ada";
    }
}

if (!empty($missing_files)) {
    echo "<div class='error'><strong>File yang hilang:</strong> " . implode(', ', $missing_files) . "</div>";
}
echo "</div>";

// RINGKASAN
echo "<div class='step'>";
echo "<h2>📊 Ringkasan Setup</h2>";
echo "<p><strong>Berhasil:</strong> " . count($success) . " item</p>";
echo "<p><strong>Error:</strong> " . count($errors) . " item</p>";

if (count($errors) == 0) {
    echo "<div class='success'><h3>🎉 SETUP BERHASIL!</h3>";
    echo "<p>Sistem siap digunakan!</p>";
    echo "<a href='auth/login.php' class='btn'>🚀 Login ke Dashboard</a>";
    echo "<a href='display/display_external.php?nomor=1' class='btn' target='_blank'>📺 Preview External Display</a>";
    echo "</div>";
} else {
    echo "<div class='error'><h3>⚠️ SETUP BELUM LENGKAP</h3>";
    echo "<p>Silakan perbaiki error di atas terlebih dahulu.</p>";
    echo "<p><a href='setup.php' class='btn'>🔄 Refresh Setup</a></p>";
    echo "</div>";
}
echo "</div>";

// Tampilkan info login
echo "<div class='step'>";
echo "<h3>🔑 Info Login Default</h3>";
echo "<p><strong>Username:</strong> admin</p>";
echo "<p><strong>Password:</strong> admin123</p>";
echo "<p><em>⚠️ Ganti password setelah login pertama kali!</em></p>";
echo "</div>";

echo "</div></body></html>";
?>