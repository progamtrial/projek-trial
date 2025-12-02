<?php
/**
 * API untuk tracking display analytics
 * Dipanggil oleh display external/internal untuk mencatat statistik
 */

header('Content-Type: application/json');

// Allow CORS untuk testing
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Load config
$configPath = file_exists('../enhanced_config.php') ? '../enhanced_config.php' : '../config.php';
require_once $configPath;

// Cek method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate input
if (!$data || !isset($data['konten_id']) || !isset($data['display_type']) || !isset($data['nomor_layar'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$kontenId = (int)$data['konten_id'];
$displayType = $data['display_type'];
$nomorLayar = (int)$data['nomor_layar'];
$duration = (int)($data['duration'] ?? 5);

// Validate
if ($kontenId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid content ID']);
    exit;
}

if (!in_array($displayType, ['external', 'internal'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid display type']);
    exit;
}

// Track using function if exists
if (function_exists('trackContentDisplay')) {
    $result = trackContentDisplay($kontenId, $displayType, $nomorLayar, $duration);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Display tracked successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to track display'
        ]);
    }
    exit;
}

// Fallback: Direct database insert
try {
    $conn = getConnection();
    
    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'content_analytics'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Analytics table not found'
        ]);
        exit;
    }
    
    $displayDate = date('Y-m-d');
    $displayHour = (int)date('H');
    
    // Check if record exists
    $checkStmt = $conn->prepare(
        "SELECT id FROM content_analytics 
         WHERE konten_id = ? AND display_date = ? AND display_hour = ? AND nomor_layar = ?"
    );
    $checkStmt->bind_param("isii", $kontenId, $displayDate, $displayHour, $nomorLayar);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing record
        $row = $result->fetch_assoc();
        $updateStmt = $conn->prepare(
            "UPDATE content_analytics 
             SET display_count = display_count + 1, 
                 total_duration = total_duration + ? 
             WHERE id = ?"
        );
        $updateStmt->bind_param("ii", $duration, $row['id']);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        // Insert new record
        $insertStmt = $conn->prepare(
            "INSERT INTO content_analytics 
             (konten_id, display_type, nomor_layar, display_count, display_date, display_hour, total_duration) 
             VALUES (?, ?, ?, 1, ?, ?, ?)"
        );
        $insertStmt->bind_param("isiiii", $kontenId, $displayType, $nomorLayar, $displayDate, $displayHour, $duration);
        $insertStmt->execute();
        $insertStmt->close();
    }
    
    $checkStmt->close();
    
    // Update view_count in konten_layar
    $updateKonten = $conn->prepare(
        "UPDATE konten_layar 
         SET view_count = COALESCE(view_count, 0) + 1, 
             last_displayed = NOW() 
         WHERE id = ?"
    );
    $updateKonten->bind_param("i", $kontenId);
    $updateKonten->execute();
    $updateKonten->close();
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Display tracked successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}