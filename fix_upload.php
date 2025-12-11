<?php
$uploadDir = __DIR__ . '/uploads/';

echo "<h2>Fix Folder Uploads</h2>";

// Hapus folder lama jika ada
if (file_exists($uploadDir)) {
    echo "Menghapus folder lama...<br>";
    // Hapus semua file di dalamnya dulu
    $files = glob($uploadDir . '*');
    foreach($files as $file) {
        if(is_file($file)) unlink($file);
    }
    rmdir($uploadDir);
    echo "✅ Folder lama dihapus<br>";
}

// Buat folder baru dengan permission
if (mkdir($uploadDir, 0777, true)) {
    echo "✅ Folder uploads berhasil dibuat<br>";
    
    // Force set permission
    chmod($uploadDir, 0777);
    
    // Cek hasil
    $perms = substr(sprintf('%o', fileperms($uploadDir)), -4);
    echo "Permission: " . $perms . "<br>";
    
    if (is_writable($uploadDir)) {
        echo "✅ <strong style='color: green;'>FOLDER WRITABLE! Siap digunakan!</strong><br>";
        
        // Test write
        $testFile = $uploadDir . 'test.txt';
        if (file_put_contents($testFile, 'success')) {
            echo "✅ Test write file: SUKSES<br>";
            unlink($testFile);
        }
    } else {
        echo "❌ Folder masih tidak writable<br>";
        echo "➡️ Coba solusi 2 atau 3 di bawah<br>";
    }
} else {
    echo "❌ Gagal membuat folder<br>";
    echo "Error: " . error_get_last()['message'] . "<br>";
}
?>