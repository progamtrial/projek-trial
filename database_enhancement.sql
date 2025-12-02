-- ============================================
-- ENHANCED DATABASE SCHEMA
-- Digital Signage BMFR Kelas II Manado
-- ============================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS digital_signage;
USE digital_signage;

-- ============================================
-- 1. ENHANCED ADMIN TABLE (Multi-Role Support)
-- ============================================
DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` ENUM('superadmin', 'admin', 'editor', 'viewer') DEFAULT 'editor',
  `is_active` BOOLEAN DEFAULT TRUE,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  INDEX `idx_role` (`role`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default users (password: admin123)
INSERT INTO `admin` (`username`, `password`, `nama`, `email`, `role`) VALUES
('superadmin', '$2y$10$/8tWQt3O3tsqwJtLgtUGXeEZ9VW03.YjxqComjFDRBX7o3O//8gpW', 'Super Admin', 'super@bmfr.go.id', 'superadmin'),
('admin', '$2y$10$/8tWQt3O3tsqwJtLgtUGXeEZ9VW03.YjxqComjFDRBX7o3O//8gpW', 'Admin Utama', 'admin@bmfr.go.id', 'admin'),
('editor', '$2y$10$/8tWQt3O3tsqwJtLgtUGXeEZ9VW03.YjxqComjFDRBX7o3O//8gpW', 'Editor Konten', 'editor@bmfr.go.id', 'editor'),
('viewer', '$2y$10$/8tWQt3O3tsqwJtLgtUGXeEZ9VW03.YjxqComjFDRBX7o3O//8gpW', 'View Only', 'viewer@bmfr.go.id', 'viewer');

-- ============================================
-- 2. ACTIVITY LOG TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `description` TEXT,
  `ip_address` VARCHAR(45),
  `user_agent` VARCHAR(255),
  `old_value` TEXT,
  `new_value` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_module` (`module`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 3. CONTENT ANALYTICS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `content_analytics` (
  `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
  `konten_id` INT NOT NULL,
  `display_type` ENUM('external', 'internal') NOT NULL,
  `nomor_layar` INT NOT NULL,
  `display_count` INT DEFAULT 1,
  `display_date` DATE NOT NULL,
  `display_hour` INT NOT NULL,
  `total_duration` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_analytics` (`konten_id`, `display_date`, `display_hour`, `nomor_layar`),
  INDEX `idx_konten` (`konten_id`),
  INDEX `idx_date` (`display_date`),
  INDEX `idx_display` (`display_type`, `nomor_layar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 4. SCHEDULE LOG TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `schedule_log` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `table_name` VARCHAR(50) NOT NULL,
  `konten_id` INT NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `reason` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_konten` (`konten_id`),
  INDEX `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 5. RSS FEEDS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `rss_feeds` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `category` VARCHAR(50) DEFAULT 'news',
  `is_active` BOOLEAN DEFAULT TRUE,
  `refresh_interval` INT DEFAULT 3600,
  `last_fetch` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default RSS feeds
INSERT INTO `rss_feeds` (`name`, `url`, `category`) VALUES
('Kominfo News', 'https://www.kominfo.go.id/rss', 'news'),
('Antara News', 'https://www.antaranews.com/rss/terkini.xml', 'news');

-- ============================================
-- 6. RSS ITEMS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `rss_items` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `feed_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `link` VARCHAR(255),
  `pub_date` DATETIME,
  `guid` VARCHAR(255) UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_feed` (`feed_id`),
  INDEX `idx_date` (`pub_date`),
  FOREIGN KEY (`feed_id`) REFERENCES `rss_feeds`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 7. BACKUP LOG TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `backup_log` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `filename` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500),
  `file_size` BIGINT,
  `backup_type` ENUM('auto', 'manual') DEFAULT 'manual',
  `status` ENUM('success', 'failed') DEFAULT 'success',
  `error_message` TEXT,
  `created_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_type` (`backup_type`),
  INDEX `idx_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 8. SETTINGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `key_name` VARCHAR(100) UNIQUE NOT NULL,
  `key_value` TEXT,
  `description` VARCHAR(255),
  `updated_by` INT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO `settings` (`key_name`, `key_value`, `description`) VALUES
('auto_backup_enabled', '1', 'Enable automatic backup'),
('auto_backup_interval', '86400', 'Backup interval in seconds (24 hours)'),
('backup_retention_days', '30', 'How many days to keep backups'),
('analytics_enabled', '1', 'Enable content analytics'),
('rss_refresh_enabled', '1', 'Enable automatic RSS refresh'),
('api_enabled', '0', 'Enable API access'),
('api_key', '', 'API Key for external access');

-- ============================================
-- 9. API TOKENS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `api_tokens` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `token` VARCHAR(64) UNIQUE NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `permissions` TEXT,
  `is_active` BOOLEAN DEFAULT TRUE,
  `expires_at` DATETIME,
  `last_used` DATETIME,
  `created_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_token` (`token`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 10. DISPLAY ZONES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `display_zones` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `zone_name` VARCHAR(100) NOT NULL,
  `zone_type` ENUM('external', 'internal') NOT NULL,
  `nomor_layar` INT NOT NULL,
  `layout` ENUM('fullscreen', 'split-horizontal', 'split-vertical', 'grid-4') DEFAULT 'fullscreen',
  `show_rss` BOOLEAN DEFAULT FALSE,
  `rss_position` ENUM('top', 'bottom', 'left', 'right') DEFAULT 'bottom',
  `show_clock` BOOLEAN DEFAULT TRUE,
  `show_weather` BOOLEAN DEFAULT FALSE,
  `is_active` BOOLEAN DEFAULT TRUE,
  `settings` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_zone` (`zone_type`, `nomor_layar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default zones
INSERT INTO `display_zones` (`zone_name`, `zone_type`, `nomor_layar`, `show_rss`) VALUES
('External Display 1 - Lobby', 'external', 1, TRUE),
('External Display 2 - Entrance', 'external', 2, TRUE),
('External Display 3 - Waiting Area', 'external', 3, TRUE),
('External Display 4 - Info Desk', 'external', 4, TRUE),
('Internal Display 1 - Office', 'internal', 1, FALSE),
('Internal Display 2 - Meeting Room', 'internal', 2, FALSE),
('Internal Display 3 - Staff Area', 'internal', 3, FALSE);

-- ============================================
-- 11. CHECK AND ALTER konten_layar TABLE
-- ============================================
-- First check if table exists
CREATE TABLE IF NOT EXISTS `konten_layar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `konten` text,
  `gambar` varchar(255) DEFAULT NULL,
  `video` varchar(255) DEFAULT NULL,
  `tipe_layar` ENUM('external', 'internal') NOT NULL,
  `nomor_layar` int(11) NOT NULL,
  `status` ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add new columns if they don't exist
ALTER TABLE `konten_layar` 
ADD COLUMN IF NOT EXISTS `video_duration` INT DEFAULT 0 AFTER `video`;

ALTER TABLE `konten_layar` 
ADD COLUMN IF NOT EXISTS `view_count` INT DEFAULT 0 AFTER `video_duration`;

ALTER TABLE `konten_layar` 
ADD COLUMN IF NOT EXISTS `last_displayed` DATETIME AFTER `view_count`;

-- ============================================
-- 12. CREATE VIEWS
-- ============================================

-- Drop views if exist
DROP VIEW IF EXISTS v_active_content_summary;
DROP VIEW IF EXISTS v_today_analytics;
DROP VIEW IF EXISTS v_user_activity;

-- View: Active Content Summary
CREATE VIEW v_active_content_summary AS
SELECT 
    tipe_layar,
    nomor_layar,
    COUNT(*) as total_konten,
    SUM(CASE WHEN status='aktif' THEN 1 ELSE 0 END) as aktif_konten,
    SUM(view_count) as total_views
FROM konten_layar
GROUP BY tipe_layar, nomor_layar;

-- View: Today's Analytics
CREATE VIEW v_today_analytics AS
SELECT 
    k.id,
    k.judul,
    k.tipe_layar,
    k.nomor_layar,
    COALESCE(SUM(ca.display_count), 0) as displays_today,
    COALESCE(SUM(ca.total_duration), 0) as duration_today
FROM konten_layar k
LEFT JOIN content_analytics ca ON k.id = ca.konten_id 
    AND ca.display_date = CURDATE()
GROUP BY k.id, k.judul, k.tipe_layar, k.nomor_layar;

-- View: User Activity Summary
CREATE VIEW v_user_activity AS
SELECT 
    a.id,
    a.username,
    a.nama,
    a.role,
    COUNT(al.id) as total_actions,
    MAX(al.created_at) as last_activity
FROM admin a
LEFT JOIN activity_log al ON a.id = al.user_id
GROUP BY a.id, a.username, a.nama, a.role;

-- ============================================
-- END OF SCRIPT
-- ============================================