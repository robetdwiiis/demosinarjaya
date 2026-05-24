<?php
/**
 * Upload API - SINAR JAYA KONVEKSI
 * Handles file uploads for logo, banner, product images, and gallery
 */

require_once 'config.php';
setJsonHeaders();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Configuration
$uploadDir = '../uploads/';
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

// Create upload directory if not exists
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE   => 'File terlalu besar (melebihi batas server)',
        UPLOAD_ERR_FORM_SIZE  => 'File terlalu besar (melebihi batas form)',
        UPLOAD_ERR_PARTIAL    => 'Upload tidak lengkap',
        UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang diupload',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file',
        UPLOAD_ERR_EXTENSION  => 'Upload dihentikan oleh extension'
    ];

    $error   = isset($_FILES['file']) ? $_FILES['file']['error'] : UPLOAD_ERR_NO_FILE;
    $message = $errorMessages[$error] ?? 'Upload error';

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

$file = $_FILES['file'];

// Validate file type
if (!in_array($file['type'], $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tipe file tidak diizinkan. Gunakan: JPG, PNG, GIF, atau WEBP']);
    exit;
}

// Validate file size
if ($file['size'] > $maxFileSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File terlalu besar. Maksimal 5MB']);
    exit;
}

// Get upload type (logo, banner, product, gallery, etc.)
$type = isset($_POST['type']) ? trim($_POST['type']) : 'image';

// Generate unique filename
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (empty($extension)) {
    // Tentukan ekstensi dari MIME type
    $mimeExtMap = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    $extension = $mimeExtMap[$file['type']] ?? 'jpg';
}

$filename = $type . '_' . time() . '_' . uniqid() . '.' . $extension;
$filepath = $uploadDir . $filename;

// Move uploaded file to server
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file ke server']);
    exit;
}

// Tipe yang TIDAK perlu disimpan ke tabel settings
$nonSettingsTypes = ['product', 'gallery', 'image'];
if (in_array($type, $nonSettingsTypes)) {
    echo json_encode([
        'success'  => true,
        'message'  => 'File berhasil diupload',
        'filename' => $filename,
        'filepath' => 'uploads/' . $filename,
        'url'      => '../uploads/' . $filename,
        'type'     => $type
    ]);
    exit;
}

// Untuk tipe lain (logo, banner), simpan path ke tabel settings
$settingKey = $type === 'logo' ? 'logo' : ($type === 'banner' ? 'banner_hero' : $type);

try {
    $pdo = getConnection();

    // Check if setting exists
    $stmt = $pdo->prepare("SELECT id FROM settings WHERE setting_key = ?");
    $stmt->execute([$settingKey]);

    if ($stmt->fetch()) {
        $updateStmt = $pdo->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
        $updateStmt->execute([$filename, $settingKey]);
    } else {
        $insertStmt = $pdo->prepare("INSERT INTO settings (setting_group, setting_key, setting_value, setting_type, setting_label) VALUES ('general', ?, ?, 'image', ?)");
        $insertStmt->execute([$settingKey, $filename, ucfirst($type)]);
    }

    echo json_encode([
        'success'  => true,
        'message'  => 'File berhasil diupload',
        'filename' => $filename,
        'filepath' => 'uploads/' . $filename,
        'url'      => '../uploads/' . $filename,
        'type'     => $type
    ]);

} catch (PDOException $e) {
    // File sudah terupload, kembalikan sukses meski DB gagal
    echo json_encode([
        'success'  => true,
        'message'  => 'File diupload tapi gagal menyimpan ke database settings',
        'filename' => $filename,
        'filepath' => 'uploads/' . $filename,
        'url'      => '../uploads/' . $filename,
        'error'    => $e->getMessage()
    ]);
}
