<?php
session_start([
    'cookie_lifetime' => 0, // Session ends when browser closes
    'cookie_httponly' => true, // Prevents XSS access to session cookie
    'cookie_secure' => false, // Set to true if using HTTPS
    'cookie_samesite' => 'Lax'
]);

header('Content-Type: application/json');

// Database connection
require_once 'config.php';

// Action router
$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

if ($input && isset($input['action'])) {
    $action = $input['action'];
}

switch ($action) {
    case 'login':
        handleLogin($input);
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check':
        checkLoginStatus();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// function getConnection removed (using config.php's version)

function handleLogin($input) {
    if (!isset($input['username']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Username dan Password wajib diisi']);
        return;
    }

    $pdo = getConnection();
    if (!$pdo) {
        // Fallback hardcoded admin if DB fail (NOT RECOMMENDED FOR PROD but useful for rescue)
        if ($input['username'] === 'admin' && $input['password'] === 'admin123') {
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = 'admin';
            $_SESSION['role'] = 'super_admin';
            echo json_encode(['success' => true, 'message' => 'Login berhasil (Fallback)']);
            return;
        }
        
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        return;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$input['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($input['password'], $user['password'])) {
        // Password benar
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Log activity
        try {
            $logStmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details) VALUES (?, 'LOGIN', 'User logged in')");
            $logStmt->execute([$user['id']]);
        } catch (Exception $e) {}

        echo json_encode([
            'success' => true, 
            'message' => 'Login berhasil',
            'user' => [
                'username' => $user['username'],
                'role' => $user['role'],
                'full_name' => $user['nama_lengkap'] ?? 'Administrator'
            ]
        ]);
    } else {
        // Login gagal
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Username atau Password salah!']);
    }
}

function handleLogout() {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logout berhasil']);
}

function checkLoginStatus() {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'is_logged_in' => true,
            'username' => $_SESSION['username'] ?? 'Admin',
            'role' => $_SESSION['role'] ?? 'admin'
        ]);
    } else {
        echo json_encode([
            'is_logged_in' => false
        ]);
    }
}
?>
