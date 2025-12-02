<?php
/**
 * DELETE CONTENT HANDLER - FIXED & ENHANCED
 * Handle penghapusan konten (gambar & video) dari database dan file system
 * Path: delete_content.php (root folder)
 */

// Force JSON response
header('Content-Type: application/json');

// Disable HTML error output
ini_set('display_errors', 0);
error_reporting(0);

// Start output buffering to catch any accidental output
ob_start();

try {
    require_once 'config.php';
    
    // Clear any output before JSON
    ob_clean();
    
    requireLogin();
    
    // Cek method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }
    
    if (!$data || !isset($data['id'])) {
        throw new Exception('ID konten tidak ditemukan');
    }
    
    $id = (int)$data['id'];
    
    if ($id <= 0) {
        throw new Exception('ID tidak valid');
    }
    
    $conn = getConnection();
    
    // Get content data before deletion
    $stmt = $conn->prepare("SELECT * FROM konten_layar WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $content = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$content) {
        throw new Exception('Konten tidak ditemukan');
    }
    
    // Delete from database first
    $deleteStmt = $conn->prepare("DELETE FROM konten_layar WHERE id = ?");
    $deleteStmt->bind_param("i", $id);
    
    if (!$deleteStmt->execute()) {
        throw new Exception('Gagal menghapus dari database: ' . $deleteStmt->error);
    }
    $deleteStmt->close();
    
    // Delete physical files
    $filesDeleted = [];
    $uploadDir = __DIR__ . '/uploads/';
    
    // Delete image file
    if (!empty($content['gambar'])) {
        $imagePath = $uploadDir . $content['gambar'];
        if (file_exists($imagePath)) {
            if (@unlink($imagePath)) {
                $filesDeleted[] = $content['gambar'];
            }
        }
    }
    
    // Delete video file and thumbnail
    if (!empty($content['video'])) {
        $videoPath = $uploadDir . $content['video'];
        if (file_exists($videoPath)) {
            if (@unlink($videoPath)) {
                $filesDeleted[] = $content['video'];
            }
        }
        
        // Delete video thumbnail (multiple possible formats)
        $thumbnailNames = [
            'thumb_' . pathinfo($content['video'], PATHINFO_FILENAME) . '.jpg',
            'thumb_' . pathinfo($content['video'], PATHINFO_FILENAME) . '.png',
            pathinfo($content['video'], PATHINFO_FILENAME) . '_thumb.jpg'
        ];
        
        foreach ($thumbnailNames as $thumbnailName) {
            $thumbnailPath = $uploadDir . $thumbnailName;
            if (file_exists($thumbnailPath)) {
                if (@unlink($thumbnailPath)) {
                    $filesDeleted[] = $thumbnailName;
                }
            }
        }
    }
    
    // Log activity
    if (function_exists('logActivity')) {
        $contentType = !empty($content['video']) ? 'video' : 'gambar';
        logActivity(
            'delete',
            'konten_layar',
            "Hapus konten {$contentType}: {$content['judul']} dari {$content['tipe_layar']} layar {$content['nomor_layar']}",
            $content,
            null
        );
    }
    
    $conn->close();
    
    // Clear buffer and send JSON
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Konten berhasil dihapus',
        'files_deleted' => count($filesDeleted),
        'details' => [
            'id' => $id,
            'judul' => $content['judul'],
            'tipe' => !empty($content['video']) ? 'video' : 'gambar',
            'files' => $filesDeleted
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Clear buffer and send error JSON
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// End output buffering
ob_end_flush();
?>