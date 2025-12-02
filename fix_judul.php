<?php
/**
 * FIX JUDUL - Ubah BMFR menjadi SMFR
 * Path: fix_judul.php
 * Akses: http://localhost/digital-signage/fix_judul.php
 */

require_once 'config.php';

$results = [];
$errors = [];

if (isset($_POST['run_fix'])) {
    $conn = getConnection();
    
    // 1. Update semua konten yang mengandung BMFR
    $updateQuery = "UPDATE konten_layar 
                    SET judul = REPLACE(judul, 'BMFR', 'SMFR'),
                        deskripsi = REPLACE(deskripsi, 'BMFR', 'SMFR')
                    WHERE judul LIKE '%BMFR%' OR deskripsi LIKE '%BMFR%'";
    
    if ($conn->query($updateQuery)) {
        $affected = $conn->affected_rows;
        $results[] = "✅ Berhasil update $affected konten (BMFR → SMFR)";
    } else {
        $errors[] = "❌ Gagal update konten: " . $conn->error;
    }
    
    // 2. Insert konten contoh baru jika tabel kosong
    $checkQuery = "SELECT COUNT(*) as total FROM konten_layar WHERE tipe_layar = 'external'";
    $result = $conn->query($checkQuery)->fetch_assoc();
    
    if ($result['total'] == 0) {
        $insertQuery = "INSERT INTO konten_layar (tipe_layar, nomor_layar, judul, deskripsi, durasi, urutan, status) VALUES
            ('external', 1, 'Selamat Datang', 'Balai Monitor SMFR Kelas II Manado', 5, 1, 'aktif'),
            ('external', 2, 'Visi Kami', 'Mewujudkan pengawasan spektrum frekuensi radio yang optimal', 5, 1, 'aktif'),
            ('external', 3, 'Layanan Publik', 'Informasi perizinan dan monitoring frekuensi radio', 5, 1, 'aktif'),
            ('external', 4, 'Kontak Kami', 'Jl. A.A. Maramis, Manado - Telp: (0431) 123456', 5, 1, 'aktif')";
        
        if ($conn->query($insertQuery)) {
            $results[] = "✅ Berhasil insert 4 konten contoh baru dengan judul SMFR";
        }
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Judul SMFR</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        h1 { color: #667eea; margin-bottom: 20px; }
        .alert {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .btn {
            padding: 15px 30px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            margin-top: 20px;
        }
        .btn:hover { background: #5568d3; }
        .info-box {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix Judul: BMFR → SMFR</h1>
        
        <?php if (!empty($results)): ?>
            <?php foreach ($results as $msg): ?>
                <div class="alert success"><?= $msg ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $msg): ?>
                <div class="alert error"><?= $msg ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="info-box">
            <h3>📋 Apa yang akan dilakukan:</h3>
            <ol>
                <li>Update semua konten yang mengandung "BMFR" menjadi "SMFR"</li>
                <li>Jika tabel kosong, insert konten contoh baru dengan judul SMFR</li>
            </ol>
        </div>
        
        <form method="POST">
            <button type="submit" name="run_fix" class="btn">
                🚀 Jalankan Fix Sekarang
            </button>
        </form>
        
        <a href="dashboard.php" style="display: block; text-align: center; margin-top: 20px; color: #667eea; text-decoration: none;">
            ← Kembali ke Dashboard
        </a>
    </div>
</body>
</html>