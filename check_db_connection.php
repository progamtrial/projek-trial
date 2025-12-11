<?php
// ========================================
// FILE 3: check_db_connection.php
// ========================================
?>
<?php
header('Content-Type: application/json');

try {
    require_once 'config.php';
    $conn = getConnection();
    
    $connected = $conn->ping();
    
    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'konten_layar'");
    $tableExists = $tableCheck && $tableCheck->num_rows > 0;
    
    // Count records
    $recordCount = 0;
    if ($tableExists) {
        $result = $conn->query("SELECT COUNT(*) as c FROM konten_layar");
        $recordCount = $result->fetch_assoc()['c'];
    }
    
    $conn->close();
    
    echo json_encode([
        'connected' => $connected,
        'table_exists' => $tableExists,
        'record_count' => $recordCount
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'connected' => false,
        'table_exists' => false,
        'record_count' => 0,
        'error' => $e->getMessage()
    ]);
}
?>

<?php
