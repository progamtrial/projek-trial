<?php
/**
 * UPLOAD HANDLER - Digital Signage BMFR
 * Upload TANPA judul dan deskripsi wajib
 * Path: upload_handler.php
 */

header('Content-Type: application/json');

require_once 'config.php';
requireLogin();

// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Function untuk mengirim response JSON
function sendResponse($success, $message, $data = null, $errors = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'errors' => $errors
    ]);
    exit;
}

// Validasi request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Method tidak diizinkan');
}

// UPDATED: Hanya tipe_layar, nomor_layar, dan durasi yang wajib
$requiredFields = ['tipe_layar', 'nomor_layar', 'durasi'];
$errors = [];

foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || $_POST[$field] === '') {
        $errors[] = "Field '$field' wajib diisi";
    }
}

// Validasi file upload
if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
    $errors[] = "File wajib diupload";
}

if (!empty($errors)) {
    sendResponse(false, 'Validasi gagal', null, $errors);
}

// Ambil data dari POST
$tipe_layar = $_POST['tipe_layar'];
$nomor_layar = (int)$_POST['nomor_layar'];
$durasi = (int)$_POST['durasi'];
$urutan = (int)($_POST['urutan'] ?? 0);

// UPDATED: Judul dan deskripsi OPSIONAL - gunakan default dari filename jika kosong
$file = $_FILES['file'];
$originalFilename = pathinfo($file['name'], PATHINFO_FILENAME);

$judul = trim($_POST['judul'] ?? '') ?: $originalFilename; // Default: nama file
$deskripsi = trim($_POST['deskripsi'] ?? ''); // Default: kosong

// Validasi tipe layar
if (!in_array($tipe_layar, ['external', 'internal'])) {
    sendResponse(false, 'Tipe layar tidak valid');
}

// Validasi nomor layar
$maxLayar = $tipe_layar === 'external' ? 4 : 3;
if ($nomor_layar < 1 || $nomor_layar > $maxLayar) {
    sendResponse(false, "Nomor layar harus antara 1-$maxLayar");
}

// Validasi durasi
if ($durasi < 1 || $durasi > 60) {
    sendResponse(false, 'Durasi harus antara 1-60 detik');
}

// Cek error upload
$uploadErrors = [
    UPLOAD_ERR_INI_SIZE => 'File terlalu besar (melebihi upload_max_filesize di php.ini)',
    UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (melebihi MAX_FILE_SIZE di form)',
    UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
    UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload',
    UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
    UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
    UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh extension PHP'
];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $errorMsg = $uploadErrors[$file['error']] ?? 'Error upload tidak diketahui';
    sendResponse(false, $errorMsg);
}

// Validasi ukuran file (200MB)
$maxSize = 200 * 1024 * 1024; // 200MB
if ($file['size'] > $maxSize) {
    sendResponse(false, 'File terlalu besar. Maksimal 200MB');
}

// Deteksi tipe file
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

// Allowed MIME types
$allowedImages = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
    'image/jpg'
];

$allowedVideos = [
    'video/mp4',
    'video/webm',
    'video/ogg',
    'video/quicktime',
    'video/x-msvideo'
];

$isImage = in_array($mimeType, $allowedImages);
$isVideo = in_array($mimeType, $allowedVideos);

if (!$isImage && !$isVideo) {
    sendResponse(false, "Tipe file tidak didukung: $mimeType. Hanya gambar (JPG, PNG, GIF, WebP) dan video (MP4, WebM, OGG) yang diperbolehkan");
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '_' . time() . '.' . strtolower($extension);

// Tentukan folder upload
$uploadDir = __DIR__ . '/uploads/';

// Buat folder jika belum ada
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        sendResponse(false, 'Gagal membuat folder uploads');
    }
}

// Cek permission folder
if (!is_writable($uploadDir)) {
    sendResponse(false, 'Folder uploads tidak memiliki permission untuk menulis');
}

// Upload file
$uploadPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    sendResponse(false, 'Gagal memindahkan file ke folder uploads');
}

// Insert ke database
try {
    $conn = getConnection();
    
    // Cek apakah kolom 'video' ada
    $videoColCheck = $conn->query("SHOW COLUMNS FROM konten_layar LIKE 'video'");
    $hasVideoCol = $videoColCheck && $videoColCheck->num_rows > 0;
    
    if ($isImage) {
        // Upload Gambar
        $stmt = $conn->prepare(
            "INSERT INTO konten_layar 
            (tipe_layar, nomor_layar, judul, deskripsi, gambar, durasi, urutan, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'aktif', NOW())"
        );
        
        $stmt->bind_param(
            "sisssii",
            $tipe_layar,
            $nomor_layar,
            $judul,
            $deskripsi,
            $filename,
            $durasi,
            $urutan
        );
    } else {
        // Upload Video
        if ($hasVideoCol) {
            // Tabel sudah punya kolom video
            $stmt = $conn->prepare(
                "INSERT INTO konten_layar 
                (tipe_layar, nomor_layar, judul, deskripsi, video, durasi, urutan, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'aktif', NOW())"
            );
            
            $stmt->bind_param(
                "sisssii",
                $tipe_layar,
                $nomor_layar,
                $judul,
                $deskripsi,
                $filename,
                $durasi,
                $urutan
            );
        } else {
            // Fallback: gunakan kolom gambar untuk video
            $stmt = $conn->prepare(
                "INSERT INTO konten_layar 
                (tipe_layar, nomor_layar, judul, deskripsi, gambar, durasi, urutan, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'aktif', NOW())"
            );
            
            $stmt->bind_param(
                "sisssii",
                $tipe_layar,
                $nomor_layar,
                $judul,
                $deskripsi,
                $filename,
                $durasi,
                $urutan
            );
        }
    }
    
    if ($stmt->execute()) {
        $insertId = $stmt->insert_id;
        $stmt->close();
        $conn->close();
        
        sendResponse(true, 'Konten berhasil diupload', [
            'id' => $insertId,
            'filename' => $filename,
            'judul' => $judul,
            'type' => $isImage ? 'image' : 'video'
        ]);
    } else {
        // Hapus file jika insert gagal
        unlink($uploadPath);
        sendResponse(false, 'Gagal menyimpan ke database: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    // Hapus file jika terjadi error
    if (file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    sendResponse(false, 'Terjadi kesalahan: ' . $e->getMessage());
}