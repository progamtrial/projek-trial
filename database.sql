USE digital_signage;

-- 3. BUAT TABEL BARU - UNIFIED
CREATE TABLE IF NOT EXISTS konten_layar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Identifikasi Layar
    tipe_layar ENUM('external', 'internal') NOT NULL COMMENT 'Tipe layar',
    nomor_layar INT NOT NULL COMMENT 'Nomor layar (1-7)',
    
    -- Konten
    judul VARCHAR(200) NOT NULL,
    deskripsi TEXT NULL,
    gambar VARCHAR(255) NULL,
    video VARCHAR(255) NULL COMMENT 'Support video untuk future',
    
    -- Display Settings
    durasi INT DEFAULT 5 COMMENT 'Durasi tampil (detik)',
    urutan INT DEFAULT 0 COMMENT 'Urutan tampil',
    
    -- Status & Scheduling
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    start_date DATE NULL COMMENT 'Tanggal mulai tayang',
    end_date DATE NULL COMMENT 'Tanggal berakhir tayang',
    start_time TIME NULL COMMENT 'Jam mulai (08:00)',
    end_time TIME NULL COMMENT 'Jam berakhir (17:00)',
    active_days VARCHAR(100) NULL COMMENT 'Hari aktif: 1=Sen,2=Sel,...,7=Min',
    priority ENUM('normal', 'high', 'urgent') DEFAULT 'normal',
    is_scheduled BOOLEAN DEFAULT FALSE,
    
    -- Metadata
    created_by INT NULL COMMENT 'ID admin yang buat',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_layar (tipe_layar, nomor_layar),
    INDEX idx_status (status),
    INDEX idx_schedule (start_date, end_date, is_scheduled),
    INDEX idx_priority (priority),
    INDEX idx_urutan (urutan),
    
    -- Constraint: External 1-4, Internal 1-3
    CONSTRAINT chk_nomor_layar CHECK (
        (tipe_layar = 'external' AND nomor_layar BETWEEN 1 AND 4) OR
        (tipe_layar = 'internal' AND nomor_layar BETWEEN 1 AND 3)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. INSERT data contoh jika tabel kosong
INSERT INTO konten_layar (tipe_layar, nomor_layar, judul, deskripsi, durasi, urutan) VALUES
('external', 1, 'Selamat Datang', 'Balai Monitor Frekuensi Radio Kelas II Manado', 5, 1),
('external', 1, 'Visi Kami', 'Mewujudkan pengawasan spektrum frekuensi radio yang optimal', 5, 2),
('external', 2, 'Layanan Publik', 'Informasi perizinan dan monitoring frekuensi radio', 5, 1),
('external', 3, 'Pengumuman', 'Pendaftaran izin frekuensi radio periode Oktober 2025', 5, 1),
('external', 4, 'Kontak Kami', 'Jl. A.A. Maramis, Manado - Telp: (0431) 123456', 5, 1),
('internal', 1, 'Info Internal', 'Rapat Koordinasi Bulanan - Ruang Rapat Lt.2', 5, 1),
('internal', 2, 'Target Kinerja', 'Monitoring 150 stasiun radio bulan ini', 5, 1),
('internal', 3, 'Pengumuman Internal', 'Evaluasi kinerja triwulan - 15 Oktober 2025', 5, 1);

-- BUAT TABEL ADMIN
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- INSERT ADMIN DEFAULT
INSERT INTO `admin` (`username`, `password`, `nama`) VALUES
('admin', '$2y$10$/8tWQt3O3tsqwJtLgtUGXeEZ9VW03.YjxqComjFDRBX7o3O//8gpW', 'Admin Utama');