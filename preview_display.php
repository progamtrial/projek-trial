<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Layar Digital Signage</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .preview-container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        
        .header-icon {
            font-size: 36px;
        }
        
        .header h1 {
            color: #667eea;
            font-size: 28px;
        }
        
        .section {
            margin-bottom: 40px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-external {
            background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%);
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .section-internal {
            background: linear-gradient(135deg, #0f346020 0%, #16213e20 100%);
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #0f3460;
        }
        
        .preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .preview-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
        
        .preview-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .preview-card-external:hover {
            border: 2px solid #667eea;
        }
        
        .preview-card-internal:hover {
            border: 2px solid #0f3460;
        }
        
        .card-icon {
            font-size: 48px;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 700;
            text-align: center;
        }
        
        .card-subtitle {
            font-size: 14px;
            color: #666;
            text-align: center;
        }
        
        .card-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-external {
            background: #667eea;
            color: white;
        }
        
        .badge-internal {
            background: #0f3460;
            color: white;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102,126,234,0.4);
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <a href="dashboard.php" class="back-btn">← Kembali ke Dashboard</a>
        
        <div class="header">
            <div class="header-icon">👁️</div>
            <div>
                <h1>Preview Layar Digital Signage</h1>
                <p>Balai Monitor Frekuensi Radio Kelas II Manado</p>
            </div>
        </div>
        
        <!-- External Displays -->
        <div class="section section-external">
            <div class="section-title">
                <span>📺</span>
                <span>External Display (Layar Publik)</span>
            </div>
            
            <div class="preview-grid">
                <a href="display/display_external.php?nomor=1" target="_blank" class="preview-card preview-card-external">
                    <div class="card-icon">📺</div>
                    <div class="card-title">External Layar 1</div>
                    <div class="card-subtitle">Lobby / Entrance</div>
                    <div class="card-badge badge-external">PUBLIK</div>
                </a>
                
                <a href="display/display_external.php?nomor=2" target="_blank" class="preview-card preview-card-external">
                    <div class="card-icon">📺</div>
                    <div class="card-title">External Layar 2</div>
                    <div class="card-subtitle">Waiting Area</div>
                    <div class="card-badge badge-external">PUBLIK</div>
                </a>
                
                <a href="display/display_external.php?nomor=3" target="_blank" class="preview-card preview-card-external">
                    <div class="card-icon">📺</div>
                    <div class="card-title">External Layar 3</div>
                    <div class="card-subtitle">Information Desk</div>
                    <div class="card-badge badge-external">PUBLIK</div>
                </a>
                
                <a href="display/display_external.php?nomor=4" target="_blank" class="preview-card preview-card-external">
                    <div class="card-icon">📺</div>
                    <div class="card-title">External Layar 4</div>
                    <div class="card-subtitle">Public Area</div>
                    <div class="card-badge badge-external">PUBLIK</div>
                </a>
            </div>
        </div>
        
        <!-- Internal Displays -->
        <div class="section section-internal">
            <div class="section-title">
                <span>🖥️</span>
                <span>Internal Display (Layar Pegawai)</span>
            </div>
            
            <div class="preview-grid">
                <a href="display/display_internal.php?nomor=1" target="_blank" class="preview-card preview-card-internal">
                    <div class="card-icon">🖥️</div>
                    <div class="card-title">Internal Layar 1</div>
                    <div class="card-subtitle">Office Area</div>
                    <div class="card-badge badge-internal">🔒 PEGAWAI</div>
                </a>
                
                <a href="display/display_internal.php?nomor=2" target="_blank" class="preview-card preview-card-internal">
                    <div class="card-icon">🖥️</div>
                    <div class="card-title">Internal Layar 2</div>
                    <div class="card-subtitle">Meeting Room</div>
                    <div class="card-badge badge-internal">🔒 PEGAWAI</div>
                </a>
                
                <a href="display/display_internal.php?nomor=3" target="_blank" class="preview-card preview-card-internal">
                    <div class="card-icon">🖥️</div>
                    <div class="card-title">Internal Layar 3</div>
                    <div class="card-subtitle">Staff Area</div>
                    <div class="card-badge badge-internal">🔒 PEGAWAI</div>
                </a>
            </div>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px; text-align: center;">
            <p style="color: #666; font-size: 14px;">
                💡 <strong>Tips:</strong> Klik pada layar untuk membuka preview dalam tab baru. 
                Gunakan mode fullscreen (F11) untuk tampilan optimal.
            </p>
        </div>
    </div>
    
    <script>
        console.log('Preview Display Widget loaded successfully');
    </script>
</body>
</html>