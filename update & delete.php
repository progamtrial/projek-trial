<?php
/**
 * UPDATE STATUS HANDLER
 * Path: update_status.php
 */

header('Content-Type: application/json');
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method tidak diizinkan']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'error' => 'Data tidak lengkap']);
    exit;
}

$id = (int)$input['id'];
$status = $input['status'];

if (!in_array($status, ['aktif', 'nonaktif'])) {
    echo json_encode(['success' => false, 'error' => 'Status tidak valid']);
    exit;
}

try {
    $conn = getConnection();
    $stmt = $conn->prepare("UPDATE konten_layar SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Status berhasil diubah']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Gagal mengubah status']);
    }
    
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

<?php
/**
 * DELETE CONTENT HANDLER
 * Path: delete_content.php
 */

header('Content-Type: application/json');
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method tidak diizinkan']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id'])) {
    echo json_encode(['success' => false, 'error' => 'ID tidak ditemukan']);
    exit;
}

$id = (int)$input['id'];

try {
    $conn = getConnection();
    
    // Get file info sebelum delete
    $stmt = $conn->prepare("SELECT gambar, video FROM konten_layar WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Konten tidak ditemukan']);
        exit;
    }
    
    // Delete dari database
    $stmt = $conn->prepare("DELETE FROM konten_layar WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Hapus file fisik
        $uploadDir = __DIR__ . '/uploads/';
        
        if (!empty($result['gambar']) && file_exists($uploadDir . $result['gambar'])) {
            unlink($uploadDir . $result['gambar']);
        }
        
        if (!empty($result['video']) && file_exists($uploadDir . $result['video'])) {
            unlink($uploadDir . $result['video']);
        }
        
        echo json_encode(['success' => true, 'message' => 'Konten berhasil dihapus']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Gagal menghapus konten']);
    }
    
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>