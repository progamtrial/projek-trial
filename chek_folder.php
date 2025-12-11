<?php
// ========================================
// FILE 1: check_folder.php
// ========================================
?>
<?php
header('Content-Type: application/json');

$folder = $_GET['folder'] ?? '';

if (empty($folder)) {
    echo json_encode(['error' => 'Folder parameter missing']);
    exit;
}

$path = __DIR__ . '/' . $folder . '/';

echo json_encode([
    'exists' => file_exists($path),
    'writable' => file_exists($path) && is_writable($path),
    'path' => $path,
    'permission' => file_exists($path) ? substr(sprintf('%o', fileperms($path)), -4) : null
]);
?>
