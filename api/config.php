<?php
/**
 * Database Connection Configuration
 * SINAR JAYA KONVEKSI
 */

// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'sinar_jaya_konveksi_v2');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Strict Error Handling to prevent HTML output breaking JSON
error_reporting(E_ALL);
ini_set('display_errors', 0); // Do NOT display errors to output (they break JSON)
ini_set('log_errors', 1);     // Log them instead

// Create PDO connection
function getConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            sendJson(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()], 500);
        }
    }
    
    return $pdo;
}

// Set JSON response headers
function setJsonHeaders() {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

// Robust JSON Sender
function sendJson($data, $code = 200) {
    setJsonHeaders();
    http_response_code($code);
    
    // PHP 7.2+ supports JSON_INVALID_UTF8_SUBSTITUTE
    $flags = 0;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
    }
    
    $json = json_encode($data, $flags);
    
    if ($json === false) {
        // Retry without flags if failed, or just error out
        $error = json_last_error_msg();
        http_response_code(500);
        // Try to manually clean data recursively if needed, but for now just return error
        echo json_encode([
            'success' => false, 
            'message' => 'JSON Encoding Error: ' . $error
        ]);
        exit;
    }
    
    echo $json;
    exit;
}

// Handle preflight requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    setJsonHeaders();
    http_response_code(200);
    exit;
}
