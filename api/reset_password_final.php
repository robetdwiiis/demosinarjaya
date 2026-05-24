<?php
// Reset Password Script
require_once 'config.php';

try {
    $pdo = getConnection();
    
    // Password baru: admin123
    // Kita gunakan BCRYPT cost 10 manual agar pasti kompatibel
    $newPassword = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 10]);
    
    // Update password untuk user 'admin'
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$newPassword]);
    
    // Check if update worked
    if ($stmt->rowCount() > 0) {
        echo "Password for 'admin' has been RESET successfully.\n";
    } else {
        // Jika row count 0, mungkin user belum ada atau password sama
        // Coba insert user jika belum ada
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        $checkStmt->execute();
        if ($checkStmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, nama_lengkap) VALUES ('admin', ?, 'admin@sinarjaya.com', 'super_admin', 'Administrator')");
            $stmt->execute([$newPassword]);
            echo "User 'admin' created with new password.\n";
        } else {
            echo "Password matched or update failed. Retrying update forced...\n";
            // Force update by dummy logic if needed, but hash change should trigger it
        }
    }
    
    // Verify
    $verifyStmt = $pdo->prepare("SELECT password FROM users WHERE username = 'admin'");
    $verifyStmt->execute();
    $storedHash = $verifyStmt->fetchColumn();
    
    echo "Verification: " . (password_verify('admin123', $storedHash) ? "SUCCESS - Login should work now!" : "FAILED") . "\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
