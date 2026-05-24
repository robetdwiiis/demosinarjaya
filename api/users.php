<?php
/**
 * User Management API - SINAR JAYA KONVEKSI
 * CRUD operations for admin users
 */

require_once 'config.php';
setJsonHeaders();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getUserById($_GET['id']);
        } else {
            getAllUsers();
        }
        break;
    case 'POST':
        createUser();
        break;
    case 'PUT':
        updateUser();
        break;
    case 'DELETE':
        deleteUser();
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

/**
 * Get all users
 */
function getAllUsers() {
    try {
        $pdo = getConnection();
        
        $sql = "SELECT id, username, email, nama_lengkap, telepon, role, status, 
                       last_login, login_count, created_at 
                FROM users 
                ORDER BY FIELD(role, 'super_admin', 'admin', 'operator'), created_at ASC";
        
        $stmt = $pdo->query($sql);
        $users = $stmt->fetchAll();
        
        // Format data
        $formatted = array_map(function($user) {
            return [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'nama_lengkap' => $user['nama_lengkap'],
                'telepon' => $user['telepon'],
                'role' => $user['role'],
                'role_display' => $user['role'] === 'super_admin' ? 'Super Admin' : 
                                  ($user['role'] === 'admin' ? 'Admin' : 'Operator'),
                'status' => $user['status'],
                'last_login' => $user['last_login'],
                'login_count' => (int)$user['login_count'],
                'created_at' => $user['created_at'],
                'initials' => strtoupper(substr($user['nama_lengkap'] ?? $user['username'], 0, 2))
            ];
        }, $users);
        
        echo json_encode([
            'success' => true,
            'data' => $formatted,
            'count' => count($formatted)
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get user by ID
 */
function getUserById($id) {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("SELECT id, username, email, nama_lengkap, telepon, role, status, 
                                      last_login, login_count, created_at 
                               FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'nama_lengkap' => $user['nama_lengkap'],
                'telepon' => $user['telepon'],
                'role' => $user['role'],
                'status' => $user['status'],
                'last_login' => $user['last_login'],
                'login_count' => (int)$user['login_count'],
                'created_at' => $user['created_at']
            ]
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Create new user
 */
function createUser() {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Username, email, dan password wajib diisi']);
            return;
        }
        
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Format email tidak valid']);
            return;
        }
        
        // Validate password length
        if (strlen($data['password']) < 6) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
            return;
        }
        
        $pdo = getConnection();
        
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$data['username'], $data['email']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Username atau email sudah terdaftar']);
            return;
        }
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Insert user
        $sql = "INSERT INTO users (username, email, password, nama_lengkap, telepon, role, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['username'],
            $data['email'],
            $hashedPassword,
            $data['nama_lengkap'] ?? $data['username'],
            $data['telepon'] ?? null,
            $data['role'] ?? 'operator',
            $data['status'] ?? 'active'
        ]);
        
        $userId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'User berhasil ditambahkan',
            'id' => $userId
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Update existing user
 */
function updateUser() {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID user diperlukan']);
            return;
        }
        
        $pdo = getConnection();
        
        // Get current user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$data['id']]);
        $currentUser = $stmt->fetch();
        
        if (!$currentUser) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
            return;
        }
        
        // Check if updating username or email conflicts with existing users
        if (!empty($data['username']) || !empty($data['email'])) {
            $checkSql = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
            $stmt = $pdo->prepare($checkSql);
            $stmt->execute([
                $data['username'] ?? $currentUser['username'],
                $data['email'] ?? $currentUser['email'],
                $data['id']
            ]);
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Username atau email sudah digunakan']);
                return;
            }
        }
        
        // Build update query
        $updates = [];
        $params = [];
        
        if (!empty($data['username'])) {
            $updates[] = "username = ?";
            $params[] = $data['username'];
        }
        if (!empty($data['email'])) {
            $updates[] = "email = ?";
            $params[] = $data['email'];
        }
        if (!empty($data['nama_lengkap'])) {
            $updates[] = "nama_lengkap = ?";
            $params[] = $data['nama_lengkap'];
        }
        if (isset($data['telepon'])) {
            $updates[] = "telepon = ?";
            $params[] = $data['telepon'];
        }
        if (!empty($data['role'])) {
            $updates[] = "role = ?";
            $params[] = $data['role'];
        }
        if (!empty($data['status'])) {
            $updates[] = "status = ?";
            $params[] = $data['status'];
        }
        
        // Update password if provided
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
                return;
            }
            $updates[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Tidak ada data yang diupdate']);
            return;
        }
        
        $params[] = $data['id'];
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode([
            'success' => true,
            'message' => 'User berhasil diupdate'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Delete user
 */
function deleteUser() {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID user diperlukan']);
            return;
        }
        
        $pdo = getConnection();
        
        // Check if user exists and is not super_admin
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$data['id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
            return;
        }
        
        if ($user['role'] === 'super_admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Super Admin tidak dapat dihapus']);
            return;
        }
        
        // Delete user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$data['id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'User berhasil dihapus'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
