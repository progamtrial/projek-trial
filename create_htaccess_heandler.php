<?php
/**
 * HANDLER: Create uploads/.htaccess
 * Path: create_htaccess_handler.php
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'create_htaccess') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

try {
    // Get htaccess content
    $content = $_POST['content'] ?? '';
    
    if (empty($content)) {
        throw new Exception('Content is empty');
    }
    
    // Create uploads directory if not exists
    $uploadsDir = __DIR__ . '/uploads/';
    if (!file_exists($uploadsDir)) {
        if (!mkdir($uploadsDir, 0755, true)) {
            throw new Exception('Failed to create uploads directory');
        }
    }
    
    // Create .htaccess file
    $htaccessPath = $uploadsDir . '.htaccess';
    
    if (file_put_contents($htaccessPath, $content) === false) {
        throw new Exception('Failed to write .htaccess file');
    }
    
    // Verify file created
    if (!file_exists($htaccessPath)) {
        throw new Exception('.htaccess file not found after creation');
    }
    
    $fileSize = filesize($htaccessPath);
    
    echo json_encode([
        'success' => true,
        'message' => '.htaccess file created successfully!',
        'file' => 'uploads/.htaccess',
        'size' => $fileSize
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>