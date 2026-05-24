<?php
/**
 * Helper Functions
 * SINAR JAYA KONVEKSI
 */

// Include database if not already included
if (!function_exists('db')) {
    require_once __DIR__ . '/database.php';
}

/**
 * Send JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Send success response
 */
function successResponse($data = null, $message = 'Success', $statusCode = 200) {
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ], $statusCode);
}

/**
 * Send error response
 */
function errorResponse($message = 'Error', $statusCode = 400, $errors = null) {
    $response = [
        'success' => false,
        'message' => $message
    ];
    
    if ($errors !== null) {
        $response['errors'] = $errors;
    }
    
    jsonResponse($response, $statusCode);
}

/**
 * Get request input (JSON or POST)
 */
function getInput($key = null, $default = null) {
    static $input = null;
    
    if ($input === null) {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $input = array_merge($_GET, $_POST);
        }
    }
    
    if ($key === null) {
        return $input;
    }
    
    return $input[$key] ?? $default;
}

/**
 * Validate required fields
 */
function validateRequired($data, $fields) {
    $errors = [];
    
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' wajib diisi';
        }
    }
    
    return $errors;
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize input
 */
function sanitize($value) {
    if (is_array($value)) {
        return array_map('sanitize', $value);
    }
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate slug from string
 */
function generateSlug($string) {
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

/**
 * Generate invoice number
 */
function generateInvoiceNumber() {
    $prefix = 'INV';
    $date = date('Ymd');
    
    $sql = "SELECT MAX(CAST(SUBSTRING(no_invoice, 12) AS UNSIGNED)) as last_num 
            FROM penjualan 
            WHERE no_invoice LIKE ?";
    $result = dbQueryOne($sql, [$prefix . $date . '%']);
    
    $nextNum = ($result['last_num'] ?? 0) + 1;
    
    return $prefix . $date . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
}

/**
 * Format currency (Rupiah)
 */
function formatRupiah($number, $prefix = 'Rp ') {
    return $prefix . number_format($number, 0, ',', '.');
}

/**
 * Format date to Indonesian format
 */
function formatTanggal($date, $format = 'd F Y') {
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
        4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
        10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    
    $timestamp = strtotime($date);
    $result = date($format, $timestamp);
    
    // Replace month names
    foreach ($bulan as $num => $nama) {
        $result = str_replace(date('F', mktime(0, 0, 0, $num, 1)), $nama, $result);
    }
    
    return $result;
}

/**
 * Upload file
 */
function uploadFile($file, $directory = 'uploads', $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File tidak valid'];
    }
    
    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowedTypes)) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan'];
    }
    
    $maxSize = 2 * 1024 * 1024; // 2MB
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar (max 2MB)'];
    }
    
    $uploadDir = BASEPATH . '/images/' . $directory;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $newFilename = uniqid() . '_' . time() . '.' . $ext;
    $destination = $uploadDir . '/' . $newFilename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => true,
            'filename' => $newFilename,
            'path' => '/images/' . $directory . '/' . $newFilename
        ];
    }
    
    return ['success' => false, 'message' => 'Gagal mengupload file'];
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Log activity
 */
function logActivity($userId, $action, $model = null, $modelId = null, $oldValues = null, $newValues = null) {
    $sql = "INSERT INTO activity_log (user_id, action, model, model_id, old_values, new_values, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    return dbExecute($sql, [
        $userId,
        $action,
        $model,
        $modelId,
        $oldValues ? json_encode($oldValues) : null,
        $newValues ? json_encode($newValues) : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

/**
 * Get setting value
 */
function getSetting($key, $default = null) {
    $sql = "SELECT setting_value FROM settings WHERE setting_key = ?";
    $result = dbQueryOne($sql, [$key]);
    return $result ? $result['setting_value'] : $default;
}

/**
 * Update setting value
 */
function updateSetting($key, $value) {
    $sql = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
    return dbExecute($sql, [$value, $key]);
}

/**
 * Pagination helper
 */
function paginate($table, $page = 1, $perPage = 10, $conditions = '', $params = [], $orderBy = 'id DESC') {
    $offset = ($page - 1) * $perPage;
    
    // Count total
    $countSql = "SELECT COUNT(*) as total FROM $table" . ($conditions ? " WHERE $conditions" : "");
    $countResult = dbQueryOne($countSql, $params);
    $total = $countResult['total'] ?? 0;
    
    // Get data
    $sql = "SELECT * FROM $table" . ($conditions ? " WHERE $conditions" : "") . " ORDER BY $orderBy LIMIT $perPage OFFSET $offset";
    $data = dbQuery($sql, $params);
    
    return [
        'data' => $data,
        'pagination' => [
            'current_page' => (int)$page,
            'per_page' => (int)$perPage,
            'total' => (int)$total,
            'total_pages' => ceil($total / $perPage),
            'has_next' => ($page * $perPage) < $total,
            'has_prev' => $page > 1
        ]
    ];
}
