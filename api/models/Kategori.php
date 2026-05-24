<?php
/**
 * Model Kategori
 * SINAR JAYA KONVEKSI
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

class Kategori {
    private $table = 'kategori';
    
    /**
     * Get all categories
     */
    public function getAll($activeOnly = true) {
        $sql = "SELECT k.*, 
                (SELECT COUNT(*) FROM produk p WHERE p.kategori_id = k.id AND p.status = 'active') as jumlah_produk
                FROM {$this->table} k";
        
        if ($activeOnly) {
            $sql .= " WHERE k.status = 'active'";
        }
        
        $sql .= " ORDER BY k.urutan ASC, k.nama_kategori ASC";
        
        return dbQuery($sql);
    }
    
    /**
     * Get category by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return dbQueryOne($sql, [$id]);
    }
    
    /**
     * Get category by slug
     */
    public function getBySlug($slug) {
        $sql = "SELECT * FROM {$this->table} WHERE slug = ?";
        return dbQueryOne($sql, [$slug]);
    }
    
    /**
     * Create new category
     */
    public function create($data) {
        $slug = generateSlug($data['nama_kategori']);
        
        $sql = "INSERT INTO {$this->table} (nama_kategori, slug, icon, deskripsi, urutan, status) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $result = dbExecute($sql, [
            $data['nama_kategori'],
            $slug,
            $data['icon'] ?? null,
            $data['deskripsi'] ?? null,
            $data['urutan'] ?? 0,
            $data['status'] ?? 'active'
        ]);
        
        if ($result) {
            return $this->getById(dbLastInsertId());
        }
        
        return false;
    }
    
    /**
     * Update category
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        $allowedFields = ['nama_kategori', 'icon', 'deskripsi', 'urutan', 'status'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (isset($data['nama_kategori'])) {
            $fields[] = "slug = ?";
            $params[] = generateSlug($data['nama_kategori']);
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        
        if (dbExecute($sql, $params)) {
            return $this->getById($id);
        }
        
        return false;
    }
    
    /**
     * Delete category
     */
    public function delete($id) {
        // Check if has products
        $sql = "SELECT COUNT(*) as total FROM produk WHERE kategori_id = ?";
        $result = dbQueryOne($sql, [$id]);
        
        if ($result['total'] > 0) {
            return ['error' => 'Kategori masih memiliki produk'];
        }
        
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return dbExecute($sql, [$id]);
    }
    
    /**
     * Get categories with product summary
     */
    public function getWithSummary() {
        return dbQuery("SELECT * FROM v_produk_per_kategori");
    }
}
