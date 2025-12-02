<?php
/**
 * UPDATE STATUS HANDLER
 * Toggle aktif/nonaktif konten
 * Path: update_status.php
 */

require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$id = (int)$data['id'];
$newStatus = $data['status'];

if (!in_array($newStatus, ['aktif', 'nonaktif'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

try {
    $conn = getConnection();
    
    // Get current content
    $getStmt = $conn->prepare("SELECT * FROM konten_layar WHERE id = ?");
    $getStmt->bind_param("i", $id);
    $getStmt->execute();
    $content = $getStmt->get_result()->fetch_assoc();
    $getStmt->close();
    
    if (!$content) {
        throw new Exception('Konten tidak ditemukan');
    }
    
    // Update status
    $stmt = $conn->prepare("UPDATE konten_layar SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $id);
    
    if (!$stmt->execute()) {
        throw new Exception('Gagal update status: ' . $stmt->error);
    }
    
    $stmt->close();
    
    // Log activity
    if (function_exists('logActivity')) {
        logActivity(
            'update',
            'konten_layar',
            "Update status konten: {$content['judul']} menjadi {$newStatus}",
            ['status' => $content['status']],
            ['status' => $newStatus]
        );
    }
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Status berhasil diubah'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>