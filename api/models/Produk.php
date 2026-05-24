<?php
/**
 * Model Produk
 * SINAR JAYA KONVEKSI
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

class Produk {
    private $table = 'produk';
    
    /**
     * Get all products with optional filters
     */
    public function getAll($filters = []) {
        $sql = "SELECT p.*, k.nama_kategori, k.slug as kategori_slug 
                FROM {$this->table} p 
                LEFT JOIN kategori k ON p.kategori_id = k.id 
                WHERE p.status = 'active'";
        $params = [];
        
        // Filter by category
        if (!empty($filters['kategori'])) {
            $sql .= " AND k.slug = ?";
            $params[] = $filters['kategori'];
        }
        
        // Filter by featured
        if (isset($filters['featured'])) {
            $sql .= " AND p.featured = ?";
            $params[] = $filters['featured'];
        }
        
        // Search by name
        if (!empty($filters['search'])) {
            $sql .= " AND (p.nama_produk LIKE ? OR p.deskripsi LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }
        
        // Sorting
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = strtoupper($filters['order_dir'] ?? 'DESC');
        $orderDir = in_array($orderDir, ['ASC', 'DESC']) ? $orderDir : 'DESC';
        $sql .= " ORDER BY p.$orderBy $orderDir";
        
        // Pagination
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET " . (int)$filters['offset'];
            }
        }
        
        return dbQuery($sql, $params);
    }
    
    /**
     * Get product by ID
     */
    public function getById($id) {
        $sql = "SELECT p.*, k.nama_kategori, k.slug as kategori_slug 
                FROM {$this->table} p 
                LEFT JOIN kategori k ON p.kategori_id = k.id 
                WHERE p.id = ?";
        return dbQueryOne($sql, [$id]);
    }
    
    /**
     * Get product by slug
     */
    public function getBySlug($slug) {
        $sql = "SELECT p.*, k.nama_kategori, k.slug as kategori_slug 
                FROM {$this->table} p 
                LEFT JOIN kategori k ON p.kategori_id = k.id 
                WHERE p.slug = ?";
        return dbQueryOne($sql, [$slug]);
    }
    
    /**
     * Get featured products
     */
    public function getFeatured($limit = 4) {
        return $this->getAll(['featured' => 1, 'limit' => $limit]);
    }
    
    /**
     * Get products by category
     */
    public function getByCategory($kategoriSlug, $limit = null) {
        $filters = ['kategori' => $kategoriSlug];
        if ($limit) {
            $filters['limit'] = $limit;
        }
        return $this->getAll($filters);
    }
    
    /**
     * Create new product
     */
    public function create($data) {
        // Generate slug
        $slug = generateSlug($data['nama_produk']);
        
        // Check if slug exists
        $existing = $this->getBySlug($slug);
        if ($existing) {
            $slug .= '-' . time();
        }
        
        $sql = "INSERT INTO {$this->table} 
                (kategori_id, nama_produk, slug, deskripsi, harga, stok, min_order, bahan, gambar_utama, featured, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $result = dbExecute($sql, [
            $data['kategori_id'],
            $data['nama_produk'],
            $slug,
            $data['deskripsi'] ?? null,
            $data['harga'],
            $data['stok'] ?? 0,
            $data['min_order'] ?? 12,
            $data['bahan'] ?? null,
            $data['gambar_utama'] ?? null,
            $data['featured'] ?? 0,
            $data['status'] ?? 'active'
        ]);
        
        if ($result) {
            return $this->getById(dbLastInsertId());
        }
        
        return false;
    }
    
    /**
     * Update product
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        $allowedFields = ['kategori_id', 'nama_produk', 'deskripsi', 'harga', 'stok', 'min_order', 'bahan', 'gambar_utama', 'featured', 'status'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        // Update slug if name changed
        if (isset($data['nama_produk'])) {
            $slug = generateSlug($data['nama_produk']);
            $existing = $this->getBySlug($slug);
            if ($existing && $existing['id'] != $id) {
                $slug .= '-' . time();
            }
            $fields[] = "slug = ?";
            $params[] = $slug;
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $fields[] = "updated_at = NOW()";
        $params[] = $id;
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        
        if (dbExecute($sql, $params)) {
            return $this->getById($id);
        }
        
        return false;
    }
    
    /**
     * Delete product
     */
    public function delete($id) {
        $sql = "UPDATE {$this->table} SET status = 'inactive' WHERE id = ?";
        return dbExecute($sql, [$id]);
    }
    
    /**
     * Hard delete product
     */
    public function forceDelete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return dbExecute($sql, [$id]);
    }
    
    /**
     * Update stock
     */
    public function updateStock($id, $quantity, $operation = 'subtract') {
        $operator = $operation === 'add' ? '+' : '-';
        $sql = "UPDATE {$this->table} SET stok = stok $operator ? WHERE id = ?";
        return dbExecute($sql, [$quantity, $id]);
    }
    
    /**
     * Get total count
     */
    public function count($status = 'active') {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE status = ?";
        $result = dbQueryOne($sql, [$status]);
        return $result['total'] ?? 0;
    }
    
    /**
     * Get low stock products
     */
    public function getLowStock($threshold = 50) {
        $sql = "SELECT p.*, k.nama_kategori 
                FROM {$this->table} p 
                LEFT JOIN kategori k ON p.kategori_id = k.id 
                WHERE p.status = 'active' AND p.stok < ? 
                ORDER BY p.stok ASC";
        return dbQuery($sql, [$threshold]);
    }
    
    /**
     * Increment view count
     */
    public function incrementViews($id) {
        $sql = "UPDATE {$this->table} SET views = views + 1 WHERE id = ?";
        return dbExecute($sql, [$id]);
    }
}
