-- ============================================
-- DATABASE ENHANCEMENT: TARGET KINERJA
-- Digital Signage SMFR Kelas II Manado
-- ============================================

USE digital_signage;

-- ============================================
-- 1. TABEL TARGET KINERJA
-- ============================================
CREATE TABLE IF NOT EXISTS `target_kinerja` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `kategori` VARCHAR(100) NOT NULL COMMENT 'Monitoring, Inspeksi, Perizinan, dll',
  `target` INT NOT NULL COMMENT 'Target yang harus dicapai',
  `realisasi` INT DEFAULT 0 COMMENT 'Realisasi saat ini',
  `satuan` VARCHAR(50) NOT NULL COMMENT 'stasiun, izin, inspeksi, dll',
  `keterangan` TEXT NULL COMMENT 'Keterangan tambahan',
  `bulan` INT NOT NULL COMMENT '1-12 untuk Jan-Des',
  `tahun` INT NOT NULL COMMENT 'Tahun target',
  
  -- Display Settings
  `display_on_internal` BOOLEAN DEFAULT TRUE COMMENT 'Tampilkan di layar internal',
  `display_priority` INT DEFAULT 5 COMMENT '0-10, semakin tinggi semakin prioritas',
  `warna` VARCHAR(7) DEFAULT '#667eea' COMMENT 'Hex color untuk chart',
  
  -- Status
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_bulan_tahun` (`bulan`, `tahun`),
  INDEX `idx_display` (`display_on_internal`, `display_priority`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Target kinerja bulanan';

-- ============================================
-- 2. DATA CONTOH
-- ============================================
INSERT INTO `target_kinerja` (`kategori`, `target`, `realisasi`, `satuan`, `keterangan`, `bulan`, `tahun`, `display_priority`, `warna`) VALUES
('Monitoring Stasiun Radio', 150, 127, 'stasiun', 'Monitoring spektrum frekuensi radio 24/7', 12, 2024, 10, '#667eea'),
('Inspeksi Lapangan', 45, 38, 'inspeksi', 'Inspeksi teknik stasiun radio', 12, 2024, 9, '#48bb78'),
('Perizinan Frekuensi', 80, 72, 'izin', 'Penerbitan izin frekuensi baru', 12, 2024, 8, '#f56565'),
('Penanganan Gangguan', 25, 21, 'kasus', 'Penyelesaian harmful interference', 12, 2024, 7, '#ed8936'),
('Pengukuran Parameter', 30, 28, 'pengukuran', 'Pengukuran parameter teknik radio', 12, 2024, 6, '#9f7aea');

-- ============================================
-- 3. TABEL DISPLAY SETTINGS (Enhancement)
-- ============================================
-- Tambah kolom untuk konfigurasi display kinerja
ALTER TABLE `display_zones` 
ADD COLUMN IF NOT EXISTS `show_kinerja` BOOLEAN DEFAULT FALSE AFTER `show_rss`,
ADD COLUMN IF NOT EXISTS `kinerja_layout` ENUM('grid', 'list', 'summary') DEFAULT 'grid' AFTER `show_kinerja`;

-- Update untuk layar internal
UPDATE `display_zones` 
SET `show_kinerja` = TRUE, `kinerja_layout` = 'grid' 
WHERE `zone_type` = 'internal';

-- ============================================
-- 4. VIEW: KINERJA SUMMARY
-- ============================================
DROP VIEW IF EXISTS `v_kinerja_summary`;

CREATE VIEW `v_kinerja_summary` AS
SELECT 
    tk.bulan,
    tk.tahun,
    COUNT(*) as total_kategori,
    SUM(tk.target) as total_target,
    SUM(tk.realisasi) as total_realisasi,
    ROUND(AVG((tk.realisasi / NULLIF(tk.target, 0)) * 100), 2) as rata_rata_pencapaian,
    SUM(CASE WHEN tk.realisasi >= tk.target THEN 1 ELSE 0 END) as kategori_tercapai
FROM target_kinerja tk
WHERE tk.is_active = TRUE
GROUP BY tk.bulan, tk.tahun;

-- ============================================
-- 5. VIEW: KINERJA DETAIL BY MONTH
-- ============================================
DROP VIEW IF EXISTS `v_kinerja_detail`;

CREATE VIEW `v_kinerja_detail` AS
SELECT 
    tk.id,
    tk.kategori,
    tk.target,
    tk.realisasi,
    tk.satuan,
    tk.keterangan,
    tk.bulan,
    tk.tahun,
    tk.display_on_internal,
    tk.display_priority,
    tk.warna,
    ROUND((tk.realisasi / NULLIF(tk.target, 0)) * 100, 2) as persentase,
    CASE 
        WHEN tk.realisasi >= tk.target THEN 'tercapai'
        WHEN tk.realisasi >= (tk.target * 0.8) THEN 'mendekati'
        ELSE 'belum'
    END as status_pencapaian,
    (tk.target - tk.realisasi) as sisa_target
FROM target_kinerja tk
WHERE tk.is_active = TRUE;

-- ============================================
-- 6. STORED PROCEDURE: UPDATE REALISASI
-- ============================================
DELIMITER $$

DROP PROCEDURE IF EXISTS `sp_update_realisasi` $$

CREATE PROCEDURE `sp_update_realisasi`(
    IN p_id INT,
    IN p_realisasi INT,
    IN p_user_id INT
)
BEGIN
    DECLARE v_old_realisasi INT;
    
    -- Get old value
    SELECT realisasi INTO v_old_realisasi 
    FROM target_kinerja 
    WHERE id = p_id;
    
    -- Update
    UPDATE target_kinerja 
    SET realisasi = p_realisasi,
        updated_at = NOW()
    WHERE id = p_id;
    
    -- Log activity
    IF EXISTS(SELECT 1 FROM information_schema.tables WHERE table_name = 'activity_log') THEN
        INSERT INTO activity_log (user_id, action, module, description, old_value, new_value)
        VALUES (
            p_user_id,
            'update',
            'target_kinerja',
            CONCAT('Update realisasi dari ', v_old_realisasi, ' menjadi ', p_realisasi),
            v_old_realisasi,
            p_realisasi
        );
    END IF;
END $$

DELIMITER ;

-- ============================================
-- 7. TRIGGER: AUTO UPDATE TIMESTAMP
-- ============================================
DROP TRIGGER IF EXISTS `before_update_target_kinerja`;

DELIMITER $$

CREATE TRIGGER `before_update_target_kinerja`
BEFORE UPDATE ON `target_kinerja`
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END $$

DELIMITER ;

-- ============================================
-- 8. INDEX OPTIMIZATION
-- ============================================
-- Optimize untuk query filtering dan sorting
ALTER TABLE `target_kinerja`
    ADD INDEX IF NOT EXISTS `idx_kategori` (`kategori`),
    ADD INDEX IF NOT EXISTS `idx_status` (`is_active`, `display_on_internal`);

-- ============================================
-- VERIFICATION QUERIES
-- ============================================
-- Cek data kinerja
SELECT * FROM v_kinerja_detail 
WHERE bulan = MONTH(NOW()) AND tahun = YEAR(NOW())
ORDER BY display_priority DESC;

-- Cek summary
SELECT * FROM v_kinerja_summary
WHERE bulan = MONTH(NOW()) AND tahun = YEAR(NOW());

-- ============================================
-- END OF SCRIPT
-- ============================================