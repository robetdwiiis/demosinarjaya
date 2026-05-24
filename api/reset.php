<?php
/**
 * Reset Data API - SINAR JAYA KONVEKSI
 * Resets database to initial state (empty products/transactions)
 */

session_start();
require_once 'config.php';
setJsonHeaders();

// Keamanan: Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak! Silakan login terlebih dahulu.']);
    exit;
}


$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    resetData();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function resetData() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validasi input
    if (!isset($input['password']) || empty($input['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Password admin wajib diisi']);
        return;
    }

    try {
        $pdo = getConnection();
        
        // 1. Verifikasi Password Admin
        // Ambil password hash dari user super_admin (anggap username 'admin' adalah super admin utama)
        $stmt = $pdo->prepare("SELECT password FROM users WHERE username = 'admin' OR role = 'super_admin' LIMIT 1");
        $stmt->execute();
        $user = $stmt->fetch();
        
        if (!$user) {
            // Jika tidak ada user admin di database, fallback ke password default (hanya untuk keamanan saat DB kosong/awal)
            // Sebaiknya tidak terjadi jika DB diimport dengan benar.
            // Password default: admin123
            $defaultHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
            if (!password_verify($input['password'], $defaultHash)) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Password admin salah!']);
                return;
            }
        } else {
            // Verifikasi dengan hash dari database
            if (!password_verify($input['password'], $user['password'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Password admin salah!']);
                return;
            }
        }

        // 2. Jika Password Benar, Lakukan Reset
        
        // Disable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Truncate tables
        $tables = [
            'gambar_produk',
            'produk',
            'detail_penjualan',
            'penjualan',
            'stok_history',
            'activity_log',
            // Optional: 'kontak', 'galeri', 'testimonial'
        ];
        
        foreach ($tables as $table) {
            $pdo->exec("TRUNCATE TABLE $table");
        }
        
        // Reset Auto Increment (implicitly handled by TRUNCATE, but good to be explicit for some DBs)
        foreach ($tables as $table) {
            $pdo->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
        }
        
        // Re-enable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        // Log the action (if we had a users table/audit log that wasn't just wiped)
        // Since we wiped activity_log, this will be the first entry
        // Kita asumsikan user ID 1 adalah admin
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([1, 'system_reset', 'System data reset to initial state by Admin', $_SERVER['REMOTE_ADDR']]);
        
        echo json_encode(['success' => true, 'message' => 'Data berhasil direset. Halaman akan dimuat ulang.']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
