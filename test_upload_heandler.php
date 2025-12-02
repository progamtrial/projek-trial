<?php
/**
 * MINIMAL UPLOAD HANDLER - For Testing Only
 * Path: manage_display/test_upload_handler.php
 */

// CRITICAL: Matikan semua output
error_reporting(0);
ini_set('display_errors', 0);

// Hapus semua output buffer
while (ob_get_level()) {
    ob_end_clean();
}

// Start fresh output buffer
ob_start();

// Set JSON header ASAP
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Simple response function
function sendResponse($success, $message, $data = null) {
    // Clear any buffered output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json; charset=utf-8');
    
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Start session
session_start();

try {
    // Check method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Method harus POST');
    }
    
    // Check if user logged in (simulasi)
    if (!isset($_SESSION['admin_id'])) {
        $_SESSION['admin_id'] = 1; // Auto login untuk test
    }
    
    // Get POST data
    $tipeLayar = isset($_POST['tipe_layar']) ? trim($_POST['tipe_layar']) : '';
    $nomorLayar = isset($_POST['nomor_layar']) ? (int)$_POST['nomor_layar'] : 0;
    $judul = isset($_POST['judul']) ? trim($_POST['judul']) : '';
    $deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';
    $durasi = isset($_POST['durasi']) ? (int)$_POST['durasi'] : 5;
    $urutan = isset($_POST['urutan']) ? (int)$_POST['urutan'] : 0;
    
    // Validate
    if (empty($judul)) {
        sendResponse(false, 'Judul tidak boleh kosong');
    }
    
    if (!in_array($tipeLayar, ['external', 'internal'])) {
        sendResponse(false, 'Tipe layar tidak valid');
    }
    
    // Check file upload
    if (!isset($_FILES['file'])) {
        sendResponse(false, 'Tidak ada file yang diupload (cek enctype form)');
    }
    
    $file = $_FILES['file'];
    
    // Check upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = 'Upload error: ';
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $errorMsg .= 'File terlalu besar (php.ini limit: ' . ini_get('upload_max_filesize') . ')';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $errorMsg .= 'File terlalu besar (form limit)';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMsg .= 'File hanya ter-upload sebagian';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMsg .= 'Tidak ada file yang dipilih';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errorMsg .= 'Folder temporary tidak ada';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errorMsg .= 'Tidak bisa write ke disk';
                break;
            default:
                $errorMsg .= 'Code ' . $file['error'];
        }
        sendResponse(false, $errorMsg);
    }
    
    // Get file info
    $originalName = basename($file['name']);
    $tmpPath = $file['tmp_name'];
    $fileSize = $file['size'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    
    // Check if file exists
    if (!file_exists($tmpPath)) {
        sendResponse(false, 'File temporary tidak ditemukan: ' . $tmpPath);
    }
    
    // Check file size
    if ($fileSize == 0) {
        sendResponse(false, 'File kosong (0 bytes)');
    }
    
    // Check extension
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'ogg'];
    if (!in_array($ext, $allowedExt)) {
        sendResponse(false, 'Extension tidak didukung: .' . $ext);
    }
    
    // Check MIME type
    if (!function_exists('finfo_open')) {
        sendResponse(false, 'Extension fileinfo tidak aktif. Enable di php.ini');
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tmpPath);
    finfo_close($finfo);
    
    // Determine type
    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    $isVideo = in_array($ext, ['mp4', 'webm', 'ogg']);
    
    if (!$isImage && !$isVideo) {
        sendResponse(false, 'File bukan gambar atau video');
    }
    
    // Create upload directory
    $uploadDir = '../uploads/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            sendResponse(false, 'Gagal membuat folder uploads');
        }
    }
    
    if (!is_writable($uploadDir)) {
        sendResponse(false, 'Folder uploads tidak writable. Ubah permission ke 755');
    }
    
    // Generate filename
    $newFilename = uniqid('test_', true) . '_' . time() . '.' . $ext;
    $uploadPath = $uploadDir . $newFilename;
    
    // Move file
    if (!move_uploaded_file($tmpPath, $uploadPath)) {
        sendResponse(false, 'Gagal move file dari temp ke uploads');
    }
    
    // Check if file moved successfully
    if (!file_exists($uploadPath)) {
        sendResponse(false, 'File tidak ditemukan setelah upload');
    }
    
    // Format file size
    if ($fileSize >= 1048576) {
        $fileSizeFormatted = number_format($fileSize / 1048576, 2) . ' MB';
    } elseif ($fileSize >= 1024) {
        $fileSizeFormatted = number_format($fileSize / 1024, 2) . ' KB';
    } else {
        $fileSizeFormatted = $fileSize . ' bytes';
    }
    
    // Try to save to database (optional - skip error jika gagal)
    $dbSaved = false;
    $insertId = 0;
    
    if (file_exists('../config.php')) {
        try {
            require_once '../config.php';
            $conn = getConnection();
            
            if ($conn) {
                $createdBy = $_SESSION['admin_id'];
                
                if ($isImage) {
                    $stmt = $conn->prepare(
                        "INSERT INTO konten_layar 
                        (tipe_layar, nomor_layar, judul, deskripsi, gambar, durasi, urutan, status, created_by, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'aktif', ?, NOW())"
                    );
                    $stmt->bind_param("sissiii", $tipeLayar, $nomorLayar, $judul, $deskripsi, $newFilename, $durasi, $urutan, $createdBy);
                } else {
                    // Check if video column exists
                    $checkCol = $conn->query("SHOW COLUMNS FROM konten_layar LIKE 'video'");
                    if ($checkCol && $checkCol->num_rows > 0) {
                        $stmt = $conn->prepare(
                            "INSERT INTO konten_layar 
                            (tipe_layar, nomor_layar, judul, deskripsi, video, durasi, urutan, status, created_by, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'aktif', ?, NOW())"
                        );
                        $stmt->bind_param("sissiiii", $tipeLayar, $nomorLayar, $judul, $deskripsi, $newFilename, $durasi, $urutan, $createdBy);
                    } else {
                        // Fallback: use gambar column
                        $stmt = $conn->prepare(
                            "INSERT INTO konten_layar 
                            (tipe_layar, nomor_layar, judul, deskripsi, gambar, durasi, urutan, status, created_by, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'aktif', ?, NOW())"
                        );
                        $stmt->bind_param("sissiii", $tipeLayar, $nomorLayar, $judul, $deskripsi, $newFilename, $durasi, $urutan, $createdBy);
                    }
                }
                
                if ($stmt->execute()) {
                    $insertId = $stmt->insert_id;
                    $dbSaved = true;
                }
                
                $stmt->close();
                $conn->close();
            }
        } catch (Exception $e) {
            // Ignore DB error, file already saved
        }
    }
    
    // Success response
    sendResponse(true, 
        $dbSaved ? 'Upload berhasil dan tersimpan ke database!' : 'Upload berhasil! (DB save skipped)',
        [
            'id' => $insertId,
            'filename' => $newFilename,
            'type' => $isImage ? 'image' : 'video',
            'size' => $fileSizeFormatted,
            'mime' => $mimeType,
            'db_saved' => $dbSaved
        ]
    );
    
} catch (Exception $e) {
    sendResponse(false, 'Exception: ' . $e->getMessage());
}
?>