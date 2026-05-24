<?php
/**
 * Dashboard API - SINAR JAYA KONVEKSI
 * Get dashboard statistics from MySQL database
 */

require_once 'config.php';
setJsonHeaders();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $action = $_GET['action'] ?? 'summary';
        
        switch ($action) {
            case 'summary':
                getDashboardSummary();
                break;
            case 'sales':
                getSalesData();
                break;
            case 'stock':
                getStockData();
                break;
            case 'recent':
                getRecentActivity();
                break;
            default:
                getDashboardSummary();
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

/**
 * Get dashboard summary statistics
 */
function getDashboardSummary() {
    try {
        $pdo = getConnection();
        
        // Get product stats
        $stmt = $pdo->query("SELECT 
            COUNT(*) as total_products,
            COALESCE(SUM(stok), 0) as total_stock,
            COALESCE(SUM(stok * harga), 0) as inventory_value,
            SUM(CASE WHEN stok < 50 THEN 1 ELSE 0 END) as low_stock
            FROM produk WHERE status = 'active'");
        $productStats = $stmt->fetch();
        
        // Get category count
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM kategori WHERE status = 'active'");
        $categoryCount = $stmt->fetchColumn();
        
        // Get unread messages
        $stmt = $pdo->query("SELECT COUNT(*) as unread FROM kontak WHERE status = 'unread'");
        $unreadMessages = $stmt->fetchColumn();
        
        // Get recent orders (if any)
        $stmt = $pdo->query("SELECT COUNT(*) as total, COALESCE(SUM(total_harga), 0) as revenue 
                             FROM penjualan WHERE status IN ('completed', 'shipped')");
        $orderStats = $stmt->fetch();
        
        // Get this month's stock movement
        $currentMonth = date('n');
        $currentYear = date('Y');
        $stmt = $pdo->prepare("SELECT 
            COALESCE(SUM(stok_masuk), 0) as stock_in,
            COALESCE(SUM(stok_keluar), 0) as stock_out
            FROM stok_history WHERE bulan = ? AND tahun = ?");
        $stmt->execute([$currentMonth, $currentYear]);
        $stockMovement = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_products' => (int)$productStats['total_products'],
                'total_stock' => (int)$productStats['total_stock'],
                'inventory_value' => (float)$productStats['inventory_value'],
                'low_stock_count' => (int)$productStats['low_stock'],
                'total_categories' => (int)$categoryCount,
                'unread_messages' => (int)$unreadMessages,
                'total_orders' => (int)$orderStats['total'],
                'total_revenue' => (float)$orderStats['revenue'],
                'stock_in_this_month' => (int)$stockMovement['stock_in'],
                'stock_out_this_month' => (int)$stockMovement['stock_out']
            ]
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get sales data for charts
 */
function getSalesData() {
    try {
        $pdo = getConnection();
        $year = $_GET['year'] ?? date('Y');
        
        $sql = "SELECT bulan, tahun,
                       COALESCE(SUM(stok_keluar), 0) as total_sales
                FROM stok_history 
                WHERE tahun = ?
                GROUP BY bulan, tahun
                ORDER BY bulan";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$year]);
        $data = $stmt->fetchAll();
        
        // Fill missing months with 0
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $salesData = array_fill(0, 12, 0);
        
        foreach ($data as $row) {
            $salesData[$row['bulan'] - 1] = (int)$row['total_sales'];
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'labels' => $months,
                'values' => $salesData,
                'year' => (int)$year
            ]
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get stock data per category
 */
function getStockData() {
    try {
        $pdo = getConnection();
        
        $sql = "SELECT k.nama_kategori, k.slug,
                       COUNT(p.id) as product_count,
                       COALESCE(SUM(p.stok), 0) as total_stock
                FROM kategori k
                LEFT JOIN produk p ON k.id = p.kategori_id AND p.status = 'active'
                WHERE k.status = 'active'
                GROUP BY k.id
                ORDER BY k.urutan";
        
        $stmt = $pdo->query($sql);
        $data = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => array_map(function($row) {
                return [
                    'category' => $row['nama_kategori'],
                    'slug' => $row['slug'],
                    'products' => (int)$row['product_count'],
                    'stock' => (int)$row['total_stock']
                ];
            }, $data)
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get recent activity log
 */
function getRecentActivity() {
    try {
        $pdo = getConnection();
        $limit = $_GET['limit'] ?? 10;
        
        $sql = "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([(int)$limit]);
        $data = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
