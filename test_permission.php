<?php
$uploadDir = __DIR__ . '/uploads/';

echo "<h2>Test Permission Folder Uploads</h2>";

// Cek apakah folder ada
if (file_exists($uploadDir)) {
    echo "✅ Folder uploads ADA<br>";
    
    // Cek permission
    $perms = substr(sprintf('%o', fileperms($uploadDir)), -4);
    echo "Permission sekarang: <strong>" . $perms . "</strong><br>";
    
    // Cek writable
    if (is_writable($uploadDir)) {
        echo "✅ Folder uploads WRITABLE (bisa ditulis)<br>";
        
        // Test write file
        $testFile = $uploadDir . 'test_' . time() . '.txt';
        if (file_put_contents($testFile, 'test')) {
            echo "✅ Berhasil menulis file test<br>";
            unlink($testFile); // Hapus file test
        } else {
            echo "❌ Gagal menulis file test<br>";
        }
    } else {
        echo "❌ Folder uploads TIDAK WRITABLE<br>";
        echo "➡️ Set permission menjadi 777<br>";
    }
} else {
    echo "❌ Folder uploads TIDAK ADA<br>";
    echo "➡️ Buat folder uploads terlebih dahulu<br>";
}
?>