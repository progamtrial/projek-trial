<?php
// ========================================
// FILE 2: check_php_settings.php
// ========================================
?>
<?php
header('Content-Type: application/json');

function parseSize($size) {
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
    $size = preg_replace('/[^0-9\.]/', '', $size);
    if ($unit) {
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    }
    return round($size);
}

$uploadMax = ini_get('upload_max_filesize');
$postMax = ini_get('post_max_size');

$uploadBytes = parseSize($uploadMax);
$postBytes = parseSize($postMax);
$targetSize = 200 * 1024 * 1024; // 300MB

echo json_encode([
    'upload_max' => $uploadMax,
    'post_max' => $postMax,
    'upload_max_ok' => $uploadBytes >= $targetSize,
    'post_max_ok' => $postBytes >= $targetSize,
    'target_size' => '200M',
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time')
]);
?>
