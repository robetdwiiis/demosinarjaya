<?php
/**
 * Categories API - SINAR JAYA KONVEKSI
 * Get product categories from MySQL database
 */

require_once 'config.php';
setJsonHeaders();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getCategories();
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function getCategories() {
    try {
        $pdo = getConnection();
        
        $sql = "SELECT k.*, 
                       COUNT(p.id) as product_count,
                       COALESCE(SUM(p.stok), 0) as total_stock
                FROM kategori k 
                LEFT JOIN produk p ON k.id = p.kategori_id AND p.status = 'active'
                WHERE k.status = 'active'
                GROUP BY k.id
                ORDER BY k.urutan";
        
        $stmt = $pdo->query($sql);
        $categories = $stmt->fetchAll();
        
        // Transform for frontend compatibility
        $data = array_map(function($c) {
            return [
                'id' => $c['slug'],
                'db_id' => (int)$c['id'],
                'name' => $c['nama_kategori'],
                'slug' => $c['slug'],
                'icon' => $c['icon'],
                'description' => $c['deskripsi'],
                'product_count' => (int)$c['product_count'],
                'total_stock' => (int)$c['total_stock']
            ];
        }, $categories);
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'count' => count($data)
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
