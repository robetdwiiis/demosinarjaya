<?php
/**
 * API Endpoint: Authentication
 * SINAR JAYA KONVEKSI
 */

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../models/User.php';

$user = new User();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'login';

try {
    switch ($action) {
        case 'login':
            if ($method !== 'POST') {
                errorResponse('Method tidak diizinkan', 405);
            }
            
            $data = getInput();
            $errors = validateRequired($data, ['username', 'password']);
            if (!empty($errors)) {
                errorResponse('Validasi gagal', 422, $errors);
            }
            
            $result = $user->authenticate($data['username'], $data['password']);
            
            if (isset($result['error'])) {
                errorResponse($result['error'], 401);
            }
            
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['username'] = $result['user']['username'];
            $_SESSION['role'] = $result['user']['role'];
            $_SESSION['logged_in'] = true;
            
            logActivity($result['user']['id'], 'login', 'users', $result['user']['id']);
            
            successResponse(['user' => $result['user']], 'Login berhasil');
            break;
            
        case 'logout':
            if (isset($_SESSION['user_id'])) {
                logActivity($_SESSION['user_id'], 'logout', 'users', $_SESSION['user_id']);
            }
            session_destroy();
            successResponse(null, 'Logout berhasil');
            break;
            
        case 'check':
            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
                $userData = $user->getById($_SESSION['user_id']);
                successResponse(['logged_in' => true, 'user' => $userData]);
            } else {
                successResponse(['logged_in' => false]);
            }
            break;
            
        default:
            errorResponse('Action tidak valid', 400);
    }
} catch (Exception $e) {
    errorResponse($e->getMessage(), 500);
}
