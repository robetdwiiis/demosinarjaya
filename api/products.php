<?php
/**
 * Products API - SINAR JAYA KONVEKSI
 * CRUD operations for products using MySQL database
 */

require_once 'config.php';
setJsonHeaders();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getProductById($_GET['id']);
        } else {
            getProducts();
        }
        break;
    case 'POST':
        createProduct();
        break;
    case 'PUT':
        updateProduct();
        break;
    case 'DELETE':
        deleteProduct();
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

/**
 * Get all products with optional filters
 */
function getProducts() {
    try {
        $pdo = getConnection();
        
        // Build query with filters
        $sql = "SELECT p.*, k.nama_kategori, k.slug as kategori_slug 
                FROM produk p 
                LEFT JOIN kategori k ON p.kategori_id = k.id 
                WHERE 1=1";
        $params = [];
        
        // Filter by category
        if (!empty($_GET['category'])) {
            $sql .= " AND (k.slug = ? OR k.id = ?)";
            $params[] = $_GET['category'];
            $params[] = $_GET['category'];
        }
        
        // Filter by status
        if (!empty($_GET['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $_GET['status'];
        } else {
            $sql .= " AND p.status = 'active'";
        }
        
        // Filter featured
        if (isset($_GET['featured']) && $_GET['featured'] == '1') {
            $sql .= " AND p.featured = 1";
        }
        
        // Search
        if (!empty($_GET['search'])) {
            $sql .= " AND (p.nama_produk LIKE ? OR p.deskripsi LIKE ?)";
            $search = '%' . $_GET['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }
        
        // Sorting
        $orderBy = $_GET['order'] ?? 'created_at';
        $orderDir = strtoupper($_GET['dir'] ?? 'DESC');
        $orderDir = in_array($orderDir, ['ASC', 'DESC']) ? $orderDir : 'DESC';
        
        $validOrders = ['created_at', 'nama_produk', 'harga', 'stok', 'views'];
        if (!in_array($orderBy, $validOrders)) {
            $orderBy = 'created_at';
        }
        
        $sql .= " ORDER BY p.$orderBy $orderDir";
        
        // Limit
        $limit = (int)($_GET['limit'] ?? 100);
        $sql .= " LIMIT $limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        
        // Transform data for frontend compatibility
        $products = array_map(function($p) {
            // Normalisasi path gambar: hapus prefix '../' agar selalu root-relative
            $gambar = $p['gambar_utama'];
            if ($gambar && !preg_match('/^https?:\/\//', $gambar)) {
                $gambar = ltrim(str_replace('../', '', $gambar), '/');
                if (!empty($gambar) && strpos($gambar, 'uploads/') === false) {
                    $gambar = 'uploads/' . $gambar;
                }
            }
            return [
                'id'             => (int)$p['id'],
                'name'           => $p['nama_produk'],
                'slug'           => $p['slug'],
                'category'       => $p['kategori_slug'],
                'category_name'  => $p['nama_kategori'],
                'price'          => (float)$p['harga'],
                'discount_price' => $p['harga_diskon'] ? (float)$p['harga_diskon'] : null,
                'stock'          => (int)$p['stok'],
                'min_order'      => (int)$p['min_order'],
                'description'    => $p['deskripsi'],
                'image'          => $gambar,
                'featured'       => (bool)$p['featured'],
                'best_seller'    => (bool)$p['best_seller'],
                'new_arrival'    => (bool)$p['new_arrival'],
                'status'         => $p['status'],
                'views'          => (int)$p['views'],
                'material'       => $p['bahan'],
                'sizes'          => $p['ukuran_tersedia'],
                'colors'         => $p['warna_tersedia']
            ];
        }, $products);
        
        echo json_encode([
            'success' => true,
            'data' => $products,
            'count' => count($products)
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get single product by ID
 */
function getProductById($id) {
    try {
        $pdo = getConnection();
        
        $sql = "SELECT p.*, k.nama_kategori, k.slug as kategori_slug 
                FROM produk p 
                LEFT JOIN kategori k ON p.kategori_id = k.id 
                WHERE p.id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([(int)$id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            return;
        }
        
        // Increment views
        $pdo->prepare("UPDATE produk SET views = views + 1 WHERE id = ?")->execute([(int)$id]);
        
        // Get additional images
        $stmt = $pdo->prepare("SELECT * FROM gambar_produk WHERE produk_id = ? ORDER BY urutan");
        $stmt->execute([(int)$id]);
        $images = $stmt->fetchAll();
        
        $data = [
            'id' => (int)$product['id'],
            'name' => $product['nama_produk'],
            'slug' => $product['slug'],
            'category' => $product['kategori_slug'],
            'category_name' => $product['nama_kategori'],
            'price' => (float)$product['harga'],
            'discount_price' => $product['harga_diskon'] ? (float)$product['harga_diskon'] : null,
            'stock' => (int)$product['stok'],
            'min_order' => (int)$product['min_order'],
            'description' => $product['deskripsi'],
            'image' => $product['gambar_utama'],
            'images' => $images,
            'featured' => (bool)$product['featured'],
            'status' => $product['status'],
            'material' => $product['bahan'],
            'sizes' => $product['ukuran_tersedia'],
            'colors' => $product['warna_tersedia'],
            'views' => (int)$product['views'] + 1
        ];
        
        echo json_encode(['success' => true, 'data' => $data]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Create new product
 */
function createProduct() {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (empty($data['name']) || empty($data['category'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Name and category are required']);
            return;
        }
        
        $pdo = getConnection();
        
        // Get category ID
        $stmt = $pdo->prepare("SELECT id FROM kategori WHERE slug = ? OR id = ?");
        $stmt->execute([$data['category'], $data['category']]);
        $category = $stmt->fetch();
        
        if (!$category) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid category']);
            return;
        }
        
        // Generate slug
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $data['name']));
        $slug = trim($slug, '-');
        
        // Check if slug exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM produk WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetchColumn() > 0) {
            $slug .= '-' . time();
        }
        
        $sql = "INSERT INTO produk (kategori_id, nama_produk, slug, deskripsi, harga, stok, min_order, bahan, gambar_utama, featured, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $category['id'],
            htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8'),
            $slug,
            htmlspecialchars($data['description'] ?? '', ENT_QUOTES, 'UTF-8'),
            (float)($data['price'] ?? 0),
            (int)($data['stock'] ?? 0),
            (int)($data['min_order'] ?? 12),
            htmlspecialchars($data['material'] ?? '', ENT_QUOTES, 'UTF-8'),
            $data['image'] ?? null,
            (int)($data['featured'] ?? 0),
            $data['status'] ?? 'active'
        ]);
        
        $id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Product created successfully',
            'id' => $id,
            'slug' => $slug
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Update product
 */
function updateProduct() {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Product ID is required']);
            return;
        }
        
        $pdo = getConnection();
        
        // Get category ID if provided
        $categoryId = null;
        if (!empty($data['category'])) {
            $stmt = $pdo->prepare("SELECT id FROM kategori WHERE slug = ? OR id = ?");
            $stmt->execute([$data['category'], $data['category']]);
            $category = $stmt->fetch();
            $categoryId = $category ? $category['id'] : null;
        }
        
        // Build update query dynamically
        $updates = [];
        $params = [];
        
        if (isset($data['name'])) {
            $updates[] = "nama_produk = ?";
            $params[] = htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8');
        }
        if ($categoryId) {
            $updates[] = "kategori_id = ?";
            $params[] = $categoryId;
        }
        if (isset($data['description'])) {
            $updates[] = "deskripsi = ?";
            $params[] = htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8');
        }
        if (isset($data['price'])) {
            $updates[] = "harga = ?";
            $params[] = (float)$data['price'];
        }
        if (isset($data['stock'])) {
            $updates[] = "stok = ?";
            $params[] = (int)$data['stock'];
        }
        if (isset($data['image'])) {
            $updates[] = "gambar_utama = ?";
            $params[] = $data['image'];
        }
        if (isset($data['featured'])) {
            $updates[] = "featured = ?";
            $params[] = (int)$data['featured'];
        }
        if (isset($data['status'])) {
            $updates[] = "status = ?";
            $params[] = $data['status'];
        }
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            return;
        }
        
        $sql = "UPDATE produk SET " . implode(', ', $updates) . " WHERE id = ?";
        $params[] = (int)$data['id'];
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Delete product
 */
function deleteProduct() {
    try {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Product ID is required']);
            return;
        }
        
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("DELETE FROM produk WHERE id = ?");
        $stmt->execute([(int)$id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Product not found']);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get categories
 */
function getCategories() {
    try {
        $pdo = getConnection();
        
        $sql = "SELECT k.*, COUNT(p.id) as product_count 
                FROM kategori k 
                LEFT JOIN produk p ON k.id = p.kategori_id AND p.status = 'active'
                WHERE k.status = 'active'
                GROUP BY k.id
                ORDER BY k.urutan";
        
        $stmt = $pdo->query($sql);
        $categories = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $categories]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
