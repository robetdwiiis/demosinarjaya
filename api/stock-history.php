<?php
/**
 * Stock History API - SINAR JAYA KONVEKSI
 * Get stock history data for predictions from MySQL database
 */

require_once 'config.php';
setJsonHeaders();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getStockHistory();
        break;
    case 'POST':
        addStockHistory();
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

/**
 * Get stock history data
 */
function getStockHistory() {
    try {
        $pdo = getConnection();
        
        $category = $_GET['category'] ?? null;
        $year = $_GET['year'] ?? null;
        
        $sql = "SELECT sh.*, k.nama_kategori, k.slug as kategori_slug
                FROM stok_history sh
                LEFT JOIN kategori k ON sh.kategori_id = k.id
                WHERE 1=1";
        $params = [];
        
        if ($category) {
            $sql .= " AND (k.slug = ? OR k.id = ?)";
            $params[] = $category;
            $params[] = $category;
        }
        
        if ($year) {
            $sql .= " AND sh.tahun = ?";
            $params[] = (int)$year;
        }
        
        $sql .= " ORDER BY sh.tahun DESC, sh.bulan DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        // AUTO-SEED: Jika data bener-bener kosong dari DB dan tidak difilter tahun tertentu, buatin data otomatis
        if (empty($data) && !$year && !$category) {
            // Panggil fungsi seed otomatis
            autoSeedStockHistory($pdo);
            // Ambil ulang datanya
            $stmt->execute($params);
            $data = $stmt->fetchAll();
        }
        
        // Transform to format compatible with frontend
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        $formatted = array_map(function($row) use ($months) {
            return [
                'id' => (int)$row['id'],
                'month' => $months[$row['bulan'] - 1] . ' ' . $row['tahun'],
                'month_num' => (int)$row['bulan'],
                'year' => (int)$row['tahun'],
                'category' => $row['kategori_slug'],
                'category_name' => $row['nama_kategori'],
                'stok_awal' => (int)$row['stok_awal'],
                'stok_masuk' => (int)$row['stok_masuk'],
                'stok_keluar' => (int)$row['stok_keluar'],
                'stok_akhir' => (int)$row['stok_akhir']
            ];
        }, $data);
        
        // Also provide aggregated data by month for predictions
        $aggregated = [];
        foreach ($data as $row) {
            $key = $row['tahun'] . '-' . str_pad($row['bulan'], 2, '0', STR_PAD_LEFT);
            if (!isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'month' => $months[$row['bulan'] - 1] . ' ' . $row['tahun'],
                    'kemeja' => 0,
                    'polo' => 0,
                    'jaket' => 0,
                    'seragam' => 0,
                    'kaos' => 0,
                    'celana' => 0
                ];
            }
            if ($row['kategori_slug']) {
                $aggregated[$key][$row['kategori_slug']] = (int)$row['stok_akhir'];
            }
        }
        
        // Sort by key (year-month) and get values
        ksort($aggregated);
        $stockHistory = array_values($aggregated);
        
        echo json_encode([
            'success' => true,
            'data' => $formatted,
            'stockHistory' => $stockHistory,
            'count' => count($formatted)
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Add new stock history record
 */
function addStockHistory() {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (empty($data['kategori_id']) || empty($data['bulan']) || empty($data['tahun'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Category, month and year are required']);
            return;
        }
        
        $pdo = getConnection();
        
        // Check if record exists for this category/month/year
        $stmt = $pdo->prepare("SELECT id FROM stok_history WHERE kategori_id = ? AND bulan = ? AND tahun = ?");
        $stmt->execute([$data['kategori_id'], $data['bulan'], $data['tahun']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing
            $sql = "UPDATE stok_history SET 
                    stok_awal = ?, stok_masuk = ?, stok_keluar = ?, stok_akhir = ?, catatan = ?
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                (int)($data['stok_awal'] ?? 0),
                (int)($data['stok_masuk'] ?? 0),
                (int)($data['stok_keluar'] ?? 0),
                (int)($data['stok_akhir'] ?? 0),
                $data['catatan'] ?? null,
                $existing['id']
            ]);
            $id = $existing['id'];
        } else {
            // Insert new
            $sql = "INSERT INTO stok_history (kategori_id, bulan, tahun, stok_awal, stok_masuk, stok_keluar, stok_akhir, catatan)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                (int)$data['kategori_id'],
                (int)$data['bulan'],
                (int)$data['tahun'],
                (int)($data['stok_awal'] ?? 0),
                (int)($data['stok_masuk'] ?? 0),
                (int)($data['stok_keluar'] ?? 0),
                (int)($data['stok_akhir'] ?? 0),
                $data['catatan'] ?? null
            ]);
            $id = $pdo->lastInsertId();
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Stock history saved successfully',
            'id' => $id
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * AUTO SEED: Automatically Generate 5 Years of Dummy Data to Database if Empty
 * This allows user to immediately see the chart active using "real" MySQL data 
 */
function autoSeedStockHistory($pdo) {
    try {
        // Cek lagi pastikan tabel benar-benar kosong untuk menghindari dobel data
        $stmtCount = $pdo->query("SELECT COUNT(*) FROM stok_history");
        if ($stmtCount->fetchColumn() > 0) return;

        // Ambil ID semua kategori dari tabel 
        $stmtCats = $pdo->query("SELECT id, slug FROM kategori");
        $categories = $stmtCats->fetchAll();
        
        if (empty($categories)) return; // Kategori belum di-seed, tidak bisa lanjut

        $currentYear = (int)date('Y');
        $startYear = $currentYear - 4; // Generate for the last 5 years

        $insertSql = "INSERT INTO stok_history (kategori_id, bulan, tahun, stok_awal, stok_masuk, stok_keluar, stok_akhir, catatan) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $pdo->prepare($insertSql);
        
        // Mulai proses insert untuk tiap kategori
        $pdo->beginTransaction();
        
        foreach ($categories as $cat) {
            $slug = strtolower($cat['slug']);
            // Tentukan base stok (sesuai dummy lama agar chart mirip)
            $baseQty = 1000;
            if (strpos($slug, 'kemeja') !== false) $baseQty = 1200;
            else if (strpos($slug, 'polo') !== false) $baseQty = 2000;
            else if (strpos($slug, 'jaket') !== false) $baseQty = 800;
            else if (strpos($slug, 'seragam') !== false) $baseQty = 3000;
            
            $akumulasiKeluar = 0; // Tren meningkat tiap tahun
            
            for ($year = $startYear; $year <= $currentYear; $year++) {
                // Untuk tiap bulan dalam setahun
                for ($month = 1; $month <= 12; $month++) {
                    
                    // Bikin angka yang masuk akal dan agak acak (meningkat secara trend)
                    $trendBulanan = ($month / 12) * 50; 
                    $trendTahunan = (($year - $startYear) * 200); 
                    
                    $stokAwal = 100; // Contoh statis stok aman
                    $stokMasuk = round($baseQty / 12) + rand(-20, 50) + round($trendTahunan / 12);
                    $stokKeluar = $stokMasuk - rand(5, 15); // Disimulasikan terjual
                    if($stokKeluar < 0) $stokKeluar = 0;
                    
                    $stokAkhir = ($stokAwal + $stokMasuk) - $stokKeluar;
                    
                    $catatan = "Auto-generated Sales Data";
                    
                    $insertStmt->execute([
                        $cat['id'],
                        $month,
                        $year,
                        $stokAwal,
                        $stokMasuk,
                        $stokKeluar,
                        $stokAkhir,
                        $catatan
                    ]);
                }
            }
        }
        
        $pdo->commit();
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Gagal melakukan auto-seed stok: " . $e->getMessage());
    }
}
