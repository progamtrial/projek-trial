<?php
/**
 * scheduler.php
 * Auto-scheduler untuk activate/deactivate konten berdasarkan jadwal
 * Jalankan via CRON setiap 1 menit atau manual
 */

require_once 'config.php';

// Disable timeout untuk cron
set_time_limit(0);

class ContentScheduler {
    private $conn;
    private $logFile = 'logs/scheduler.log';
    
    public function __construct() {
        $this->conn = getConnection();
        
        // Buat folder logs jika belum ada
        if (!file_exists('logs')) {
            mkdir('logs', 0755, true);
        }
    }
    
    /**
     * Jalankan scheduler
     */
    public function run() {
        $this->log("========== SCHEDULER START ==========");
        $this->log("Time: " . date('Y-m-d H:i:s'));
        
        $this->processSchedule('konten_layar');
        
        $this->log("========== SCHEDULER END ==========\n");
    }
    
    /**
     * Process scheduling untuk tabel tertentu
     */
    private function processSchedule($table) {
        $this->log("Processing table: $table");
        
        $now = date('Y-m-d H:i:s');
        $currentDate = date('Y-m-d');
        $currentTime = date('H:i:s');
        $currentDay = date('N'); // 1=Senin, 7=Minggu
        
        // Get semua konten yang scheduled
        $query = "SELECT * FROM $table WHERE is_scheduled = 1";
        $result = $this->conn->query($query);
        
        $activated = 0;
        $deactivated = 0;
        
        while ($row = $result->fetch_assoc()) {
            $shouldBeActive = $this->shouldBeActive($row, $currentDate, $currentTime, $currentDay);
            $currentStatus = $row['status'];
            $id = $row['id'];
            
            // Jika harus aktif tapi sekarang nonaktif -> ACTIVATE
            if ($shouldBeActive && $currentStatus === 'nonaktif') {
                $this->updateStatus($table, $id, 'aktif', 'auto_activate');
                $activated++;
                $this->log("✅ Activated: {$row['judul']} (ID: $id)");
            }
            
            // Jika harus nonaktif tapi sekarang aktif -> DEACTIVATE
            if (!$shouldBeActive && $currentStatus === 'aktif') {
                $this->updateStatus($table, $id, 'nonaktif', 'auto_deactivate');
                $deactivated++;
                $this->log("❌ Deactivated: {$row['judul']} (ID: $id)");
            }
        }
        
        $this->log("Summary: $activated activated, $deactivated deactivated");
    }
    
    /**
     * Cek apakah konten harus aktif sekarang
     */
    private function shouldBeActive($content, $currentDate, $currentTime, $currentDay) {
        // 1. Cek tanggal range
        if ($content['start_date'] && $currentDate < $content['start_date']) {
            return false; // Belum waktunya
        }
        
        if ($content['end_date'] && $currentDate > $content['end_date']) {
            return false; // Sudah expired
        }
        
        // 2. Cek jam operasional
        if ($content['start_time'] && $currentTime < $content['start_time']) {
            return false; // Belum jam mulai
        }
        
        if ($content['end_time'] && $currentTime > $content['end_time']) {
            return false; // Sudah lewat jam berakhir
        }
        
        // 3. Cek hari aktif
        if ($content['active_days']) {
            $activeDays = explode(',', $content['active_days']);
            if (!in_array($currentDay, $activeDays)) {
                return false; // Bukan hari aktif
            }
        }
        
        return true; // Semua kondisi terpenuhi
    }
    
    /**
     * Update status konten
     */
    private function updateStatus($table, $id, $newStatus, $action) {
        $stmt = $this->conn->prepare("UPDATE $table SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $id);
        $stmt->execute();
        $stmt->close();
        
        // Log ke database
        $this->logToDatabase($table, $id, $action);
    }
    
    /**
     * Log ke database
     */
    private function logToDatabase($table, $kontenId, $action) {
        $reason = "Scheduled: " . date('Y-m-d H:i:s');
        $stmt = $this->conn->prepare("INSERT INTO schedule_log (table_name, konten_id, action, reason) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $table, $kontenId, $action, $reason);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Log ke file
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        echo $logMessage; // Untuk debugging
    }
    
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// JALANKAN SCHEDULER
$scheduler = new ContentScheduler();
$scheduler->run();

echo "Scheduler completed successfully!";
?>