<?php
/**
 * API Endpoint: Products
 * SINAR JAYA KONVEKSI
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../models/Produk.php';

$produk = new Produk();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get product by ID or slug
            if (isset($_GET['id'])) {
                $result = $produk->getById((int)$_GET['id']);
                if ($result) {
                    $produk->incrementViews((int)$_GET['id']);
                    successResponse($result);
                } else {
                    errorResponse('Produk tidak ditemukan', 404);
                }
            } 
            elseif (isset($_GET['slug'])) {
                $result = $produk->getBySlug($_GET['slug']);
                if ($result) {
                    $produk->incrementViews($result['id']);
                    successResponse($result);
                } else {
                    errorResponse('Produk tidak ditemukan', 404);
                }
            }
            // Get featured products
            elseif (isset($_GET['featured'])) {
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 4;
                $result = $produk->getFeatured($limit);
                successResponse($result);
            }
            // Get products by category
            elseif (isset($_GET['kategori'])) {
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
                $result = $produk->getByCategory($_GET['kategori'], $limit);
                successResponse($result);
            }
            // Get low stock products
            elseif (isset($_GET['low_stock'])) {
                $threshold = isset($_GET['threshold']) ? (int)$_GET['threshold'] : 50;
                $result = $produk->getLowStock($threshold);
                successResponse($result);
            }
            // Get all products with filters
            else {
                $filters = [
                    'search' => $_GET['search'] ?? null,
                    'kategori' => $_GET['cat'] ?? null,
                    'order_by' => $_GET['order_by'] ?? 'created_at',
                    'order_dir' => $_GET['order_dir'] ?? 'DESC',
                    'limit' => $_GET['limit'] ?? null,
                    'offset' => $_GET['offset'] ?? null
                ];
                
                $result = $produk->getAll(array_filter($filters));
                successResponse([
                    'products' => $result,
                    'total' => $produk->count()
                ]);
            }
            break;
            
        case 'POST':
            $data = getInput();
            
            // Validate required fields
            $errors = validateRequired($data, ['nama_produk', 'kategori_id', 'harga']);
            if (!empty($errors)) {
                errorResponse('Validasi gagal', 422, $errors);
            }
            
            $result = $produk->create($data);
            if ($result) {
                successResponse($result, 'Produk berhasil ditambahkan', 201);
            } else {
                errorResponse('Gagal menambahkan produk', 500);
            }
            break;
            
        case 'PUT':
            if (!isset($_GET['id'])) {
                errorResponse('ID produk diperlukan', 400);
            }
            
            $data = getInput();
            $result = $produk->update((int)$_GET['id'], $data);
            
            if ($result) {
                successResponse($result, 'Produk berhasil diperbarui');
            } else {
                errorResponse('Gagal memperbarui produk', 500);
            }
            break;
            
        case 'DELETE':
            if (!isset($_GET['id'])) {
                errorResponse('ID produk diperlukan', 400);
            }
            
            $result = $produk->delete((int)$_GET['id']);
            if ($result) {
                successResponse(null, 'Produk berhasil dihapus');
            } else {
                errorResponse('Gagal menghapus produk', 500);
            }
            break;
            
        default:
            errorResponse('Method tidak diizinkan', 405);
    }
} catch (Exception $e) {
    errorResponse($e->getMessage(), 500);
}
