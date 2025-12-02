<?php
require_once 'enhanced_config.php';
requireLogin();
requireRole('viewer');

$conn = getConnection();

// Get active content count
$externalCount = $conn->query("SELECT COUNT(*) as c FROM konten_layar WHERE tipe_layar = 'external' AND status = 'aktif'")->fetch_assoc()['c'];
$internalCount = $conn->query("SELECT COUNT(*) as c FROM konten_layar WHERE tipe_layar = 'internal' AND status = 'aktif'")->fetch_assoc()['c'];

// Get recent content
$recentContent = $conn->query("SELECT * FROM konten_layar WHERE status = 'aktif' ORDER BY created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi BMFR - Digital Signage</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .header p {
            font-size: 18px;
            opacity: 0.95;
        }
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .hero-section {
            background: white;
            padding: 50px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 40px;
            text-align: center;
        }
        .hero-section .logo {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .hero-section h2 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 20px;
        }
        .hero-section p {
            color: #666;
            font-size: 16px;
            line-height: 1.8;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .info-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .info-card .icon {
            font-size: 50px;
            margin-bottom: 20px;
        }
        .info-card h3 {
            color: #667eea;
            font-size: 22px;
            margin-bottom: 15px;
        }
        .info-card p, .info-card ul {
            color: #666;
            line-height: 1.8;
            font-size: 15px;
        }
        .info-card ul {
            margin-left: 20px;
            margin-top: 15px;
        }
        .info-card li {
            margin-bottom: 10px;
        }
        
        .stats-section {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }
        .stats-section h2 {
            color: #667eea;
            text-align: center;
            margin-bottom: 30px;
            font-size: 26px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
        }
        .stat-box {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }
        .stat-box .number {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .stat-box .label {
            font-size: 16px;
            opacity: 0.95;
        }
        
        .display-links {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }
        .display-links h2 {
            color: #667eea;
            text-align: center;
            margin-bottom: 30px;
            font-size: 26px;
        }
        .links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .display-link {
            padding: 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            text-align: center;
            font-weight: 600;
            transition: transform 0.3s;
            display: block;
        }
        .display-link:hover {
            transform: translateY(-5px);
        }
        .display-link .icon {
            font-size: 32px;
            display: block;
            margin-bottom: 10px;
        }
        
        .contact-section {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .contact-section h2 {
            color: #667eea;
            margin-bottom: 30px;
            font-size: 26px;
        }
        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        .contact-item {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .contact-item .icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .contact-item h4 {
            color: #333;
            margin-bottom: 10px;
        }
        .contact-item p {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="auth/logout.php" class="logout-btn">Logout</a>
        <div class="header-content">
            <h1>📡 Balai Monitor Frekuensi Radio Kelas II Manado</h1>
            <p>Sistem Informasi Digital Signage</p>
        </div>
    </div>
    
    <div class="container">
        <!-- Hero Section -->
        <div class="hero-section">
            <div class="logo">📡</div>
            <h2>Tentang Balai Monitor Frekuensi Radio Kelas II Manado</h2>
            <p>
                Balai Monitor Frekuensi Radio (BMFR) Kelas II Manado merupakan Unit Pelaksana Teknis (UPT) di bawah 
                Direktorat Jenderal Sumber Daya dan Perangkat Pos dan Informatika, Kementerian Komunikasi dan Informatika 
                Republik Indonesia. BMFR bertugas melakukan pengawasan pemanfaatan spektrum frekuensi radio dan orbit satelit 
                di wilayah Sulawesi Utara, Sulawesi Tengah, Gorontalo, dan Maluku Utara.
            </p>
        </div>
        
        <!-- Stats Section -->
        <div class="stats-section">
            <h2>📊 Statistik Digital Signage</h2>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="number">4</div>
                    <div class="label">Layar Eksternal</div>
                </div>
                <div class="stat-box">
                    <div class="number">3</div>
                    <div class="label">Layar Internal</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?= $externalCount ?></div>
                    <div class="label">Konten Eksternal Aktif</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?= $internalCount ?></div>
                    <div class="label">Konten Internal Aktif</div>
                </div>
            </div>
        </div>
        
        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-card">
                <div class="icon">🎯</div>
                <h3>Visi</h3>
                <p>
                    Mewujudkan tata kelola spektrum frekuensi radio dan orbit satelit yang optimal 
                    dalam mendukung pembangunan ekonomi digital Indonesia.
                </p>
            </div>
            
            <div class="info-card">
                <div class="icon">🚀</div>
                <h3>Misi</h3>
                <ul>
                    <li>Melakukan monitoring dan pengawasan spektrum frekuensi radio</li>
                    <li>Memberikan pelayanan perizinan frekuensi radio yang efisien</li>
                    <li>Menangani gangguan frekuensi radio (harmful interference)</li>
                    <li>Melakukan pengukuran parameter teknik radio</li>
                </ul>
            </div>
            
            <div class="info-card">
                <div class="icon">⚙️</div>
                <h3>Tugas & Fungsi</h3>
                <ul>
                    <li>Monitoring spektrum frekuensi radio 24/7</li>
                    <li>Inspeksi dan pengukuran stasiun radio</li>
                    <li>Penyelesaian gangguan harmful interference</li>
                    <li>Pelayanan administrasi perizinan radio</li>
                    <li>Pembinaan terhadap pengguna spektrum</li>
                </ul>
            </div>
            
            <div class="info-card">
                <div class="icon">📍</div>
                <h3>Wilayah Kerja</h3>
                <ul>
                    <li>Provinsi Sulawesi Utara</li>
                    <li>Provinsi Sulawesi Tengah</li>
                    <li>Provinsi Gorontalo</li>
                    <li>Provinsi Maluku Utara</li>
                </ul>
            </div>
        </div>
        
        <!-- Display Links -->
        <div class="display-links">
            <h2>👁️ Preview Layar Digital Signage</h2>
            <div class="links-grid">
                <a href="display/display_external.php" target="_blank" class="display-link">
                    <span class="icon">📺</span>
                    External Layar 1
                </a>
                <a href="display/display_external.php?nomor=2" target="_blank" class="display-link">
                    <span class="icon">📺</span>
                    External Layar 2
                </a>
                <a href="display/display_external.php?nomor=3" target="_blank" class="display-link">
                    <span class="icon">📺</span>
                    External Layar 3
                </a>
                <a href="display/display_external.php?nomor=4" target="_blank" class="display-link">
                    <span class="icon">📺</span>
                    External Layar 4
                </a>
                <a href="display/display_internal.php?nomor=1" target="_blank" class="display-link">
                    <span class="icon">🖥️</span>
                    Internal Layar 1
                </a>
                <a href="display/display_internal.php?nomor=2" target="_blank" class="display-link">
                    <span class="icon">🖥️</span>
                    Internal Layar 2
                </a>
                <a href="display/display_internal.php?nomor=3" target="_blank" class="display-link">
                    <span class="icon">🖥️</span>
                    Internal Layar 3
                </a>
            </div>
        </div>
        
        <!-- Contact Section -->
        <div class="contact-section">
            <h2>📞 Hubungi Kami</h2>
            <p style="color: #666; margin-bottom: 20px;">
                Untuk informasi lebih lanjut mengenai layanan monitoring frekuensi radio dan perizinan, 
                silakan hubungi kami melalui:
            </p>
            
            <div class="contact-info">
                <div class="contact-item">
                    <div class="icon">📍</div>
                    <h4>Alamat</h4>
                    <p>Jl. A.A. Maramis<br>Manado, Sulawesi Utara<br>Indonesia</p>
                </div>
                
                <div class="contact-item">
                    <div class="icon">📞</div>
                    <h4>Telepon</h4>
                    <p>(0431) 123456<br>Fax: (0431) 123457</p>
                </div>
                
                <div class="contact-item">
                    <div class="icon">✉️</div>
                    <h4>Email</h4>
                    <p>info@bmfr-manado.go.id<br>monitoring@bmfr-manado.go.id</p>
                </div>
                
                <div class="contact-item">
                    <div class="icon">⏰</div>
                    <h4>Jam Operasional</h4>
                    <p>Senin - Jumat<br>08:00 - 16:00 WITA<br>(Monitoring 24/7)</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>