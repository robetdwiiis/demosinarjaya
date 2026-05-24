<?php
/**
 * Model User
 * SINAR JAYA KONVEKSI
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

class User {
    private $table = 'users';
    
    /**
     * Get all users
     */
    public function getAll($includeInactive = false) {
        $sql = "SELECT id, username, email, nama_lengkap, telepon, foto, role, status, last_login, created_at 
                FROM {$this->table}";
        
        if (!$includeInactive) {
            $sql .= " WHERE status = 'active'";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        return dbQuery($sql);
    }
    
    /**
     * Get user by ID
     */
    public function getById($id) {
        $sql = "SELECT id, username, email, nama_lengkap, telepon, foto, role, status, last_login, created_at 
                FROM {$this->table} WHERE id = ?";
        return dbQueryOne($sql, [$id]);
    }
    
    /**
     * Get user by username
     */
    public function getByUsername($username) {
        $sql = "SELECT * FROM {$this->table} WHERE username = ?";
        return dbQueryOne($sql, [$username]);
    }
    
    /**
     * Get user by email
     */
    public function getByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        return dbQueryOne($sql, [$email]);
    }
    
    /**
     * Authenticate user
     */
    public function authenticate($username, $password) {
        $user = $this->getByUsername($username);
        
        if (!$user) {
            // Try email
            $user = $this->getByEmail($username);
        }
        
        if (!$user) {
            return ['error' => 'User tidak ditemukan'];
        }
        
        if ($user['status'] !== 'active') {
            return ['error' => 'Akun tidak aktif'];
        }
        
        if (!verifyPassword($password, $user['password'])) {
            return ['error' => 'Password salah'];
        }
        
        // Update last login
        $this->updateLastLogin($user['id']);
        
        // Remove password from response
        unset($user['password']);
        
        return ['success' => true, 'user' => $user];
    }
    
    /**
     * Update last login
     */
    public function updateLastLogin($id) {
        $sql = "UPDATE {$this->table} SET last_login = NOW(), login_count = login_count + 1 WHERE id = ?";
        return dbExecute($sql, [$id]);
    }
    
    /**
     * Create new user
     */
    public function create($data) {
        // Check if username exists
        if ($this->getByUsername($data['username'])) {
            return ['error' => 'Username sudah digunakan'];
        }
        
        // Check if email exists
        if ($this->getByEmail($data['email'])) {
            return ['error' => 'Email sudah digunakan'];
        }
        
        $sql = "INSERT INTO {$this->table} (username, email, password, nama_lengkap, telepon, foto, role, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $result = dbExecute($sql, [
            $data['username'],
            $data['email'],
            hashPassword($data['password']),
            $data['nama_lengkap'] ?? null,
            $data['telepon'] ?? null,
            $data['foto'] ?? null,
            $data['role'] ?? 'operator',
            $data['status'] ?? 'active'
        ]);
        
        if ($result) {
            return $this->getById(dbLastInsertId());
        }
        
        return false;
    }
    
    /**
     * Update user
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        $allowedFields = ['email', 'nama_lengkap', 'telepon', 'foto', 'role', 'status'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
        
        if (dbExecute($sql, $params)) {
            return $this->getById($id);
        }
        
        return false;
    }
    
    /**
     * Update password
     */
    public function updatePassword($id, $currentPassword, $newPassword) {
        $sql = "SELECT password FROM {$this->table} WHERE id = ?";
        $user = dbQueryOne($sql, [$id]);
        
        if (!$user) {
            return ['error' => 'User tidak ditemukan'];
        }
        
        if (!verifyPassword($currentPassword, $user['password'])) {
            return ['error' => 'Password lama salah'];
        }
        
        $sql = "UPDATE {$this->table} SET password = ?, updated_at = NOW() WHERE id = ?";
        if (dbExecute($sql, [hashPassword($newPassword), $id])) {
            return ['success' => true];
        }
        
        return ['error' => 'Gagal mengubah password'];
    }
    
    /**
     * Reset password (admin)
     */
    public function resetPassword($id, $newPassword) {
        $sql = "UPDATE {$this->table} SET password = ?, updated_at = NOW() WHERE id = ?";
        return dbExecute($sql, [hashPassword($newPassword), $id]);
    }
    
    /**
     * Delete user
     */
    public function delete($id) {
        // Check if it's the last super_admin
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE role = 'super_admin' AND status = 'active'";
        $result = dbQueryOne($sql);
        
        $user = $this->getById($id);
        if ($user['role'] === 'super_admin' && $result['total'] <= 1) {
            return ['error' => 'Tidak dapat menghapus super admin terakhir'];
        }
        
        $sql = "UPDATE {$this->table} SET status = 'inactive' WHERE id = ?";
        return dbExecute($sql, [$id]);
    }
}
