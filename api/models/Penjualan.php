<?php
/**
 * Model Penjualan
 * SINAR JAYA KONVEKSI
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

class Penjualan {
    private $table = 'penjualan';
    
    /**
     * Get all sales with filters
     */
    public function getAll($filters = []) {
        $sql = "SELECT p.*, 
                (SELECT COUNT(*) FROM detail_penjualan dp WHERE dp.penjualan_id = p.id) as jumlah_item
                FROM {$this->table} p 
                WHERE 1=1";
        $params = [];
        
        // Filter by status
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }
        
        // Filter by date range
        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(p.tanggal_order) >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(p.tanggal_order) <= ?";
            $params[] = $filters['end_date'];
        }
        
        // Search
        if (!empty($filters['search'])) {
            $sql .= " AND (p.no_invoice LIKE ? OR p.nama_pelanggan LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
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
     * Get sale by ID with details
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $penjualan = dbQueryOne($sql, [$id]);
        
        if ($penjualan) {
            $penjualan['items'] = $this->getDetails($id);
        }
        
        return $penjualan;
    }
    
    /**
     * Get sale by invoice number
     */
    public function getByInvoice($invoice) {
        $sql = "SELECT * FROM {$this->table} WHERE no_invoice = ?";
        $penjualan = dbQueryOne($sql, [$invoice]);
        
        if ($penjualan) {
            $penjualan['items'] = $this->getDetails($penjualan['id']);
        }
        
        return $penjualan;
    }
    
    /**
     * Get sale details
     */
    public function getDetails($penjualanId) {
        $sql = "SELECT dp.*, p.gambar_utama, p.slug 
                FROM detail_penjualan dp 
                LEFT JOIN produk p ON dp.produk_id = p.id 
                WHERE dp.penjualan_id = ?";
        return dbQuery($sql, [$penjualanId]);
    }
    
    /**
     * Create new sale
     */
    public function create($data) {
        try {
            dbBeginTransaction();
            
            // Generate invoice number
            $noInvoice = generateInvoiceNumber();
            
            // Calculate totals
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $subtotal += $item['harga_satuan'] * $item['jumlah'];
            }
            
            $diskon = $data['diskon'] ?? 0;
            $ongkir = $data['ongkir'] ?? 0;
            $total = $subtotal - $diskon + $ongkir;
            
            // Insert penjualan
            $sql = "INSERT INTO {$this->table} 
                    (no_invoice, pelanggan_id, nama_pelanggan, email, telepon, whatsapp, alamat, 
                     subtotal, diskon, ongkir, total_harga, metode_pembayaran, jumlah_dp, 
                     status_pembayaran, status, tanggal_deadline, catatan) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            dbExecute($sql, [
                $noInvoice,
                $data['pelanggan_id'] ?? null,
                $data['nama_pelanggan'],
                $data['email'] ?? null,
                $data['telepon'] ?? null,
                $data['whatsapp'] ?? null,
                $data['alamat'] ?? null,
                $subtotal,
                $diskon,
                $ongkir,
                $total,
                $data['metode_pembayaran'] ?? 'cash',
                $data['jumlah_dp'] ?? 0,
                $data['status_pembayaran'] ?? 'unpaid',
                $data['status'] ?? 'pending',
                $data['tanggal_deadline'] ?? null,
                $data['catatan'] ?? null
            ]);
            
            $penjualanId = dbLastInsertId();
            
            // Insert details
            foreach ($data['items'] as $item) {
                $subtotalItem = $item['harga_satuan'] * $item['jumlah'];
                
                $sqlDetail = "INSERT INTO detail_penjualan 
                              (penjualan_id, produk_id, nama_produk, jumlah, ukuran, warna, catatan_item, harga_satuan, subtotal) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                dbExecute($sqlDetail, [
                    $penjualanId,
                    $item['produk_id'],
                    $item['nama_produk'],
                    $item['jumlah'],
                    $item['ukuran'] ?? null,
                    $item['warna'] ?? null,
                    $item['catatan_item'] ?? null,
                    $item['harga_satuan'],
                    $subtotalItem
                ]);
                
                // Update stock
                $sqlStock = "UPDATE produk SET stok = stok - ? WHERE id = ?";
                dbExecute($sqlStock, [$item['jumlah'], $item['produk_id']]);
            }
            
            dbCommit();
            
            return $this->getById($penjualanId);
            
        } catch (Exception $e) {
            dbRollback();
            throw $e;
        }
    }
    
    /**
     * Update sale status
     */
    public function updateStatus($id, $status) {
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?";
        if (dbExecute($sql, [$status, $id])) {
            return $this->getById($id);
        }
        return false;
    }
    
    /**
     * Update payment status
     */
    public function updatePaymentStatus($id, $status, $dpAmount = null) {
        $sql = "UPDATE {$this->table} SET status_pembayaran = ?";
        $params = [$status];
        
        if ($dpAmount !== null) {
            $sql .= ", jumlah_dp = ?";
            $params[] = $dpAmount;
        }
        
        $sql .= ", updated_at = NOW() WHERE id = ?";
        $params[] = $id;
        
        if (dbExecute($sql, $params)) {
            return $this->getById($id);
        }
        return false;
    }
    
    /**
     * Cancel sale and restore stock
     */
    public function cancel($id) {
        try {
            dbBeginTransaction();
            
            // Get details to restore stock
            $details = $this->getDetails($id);
            
            foreach ($details as $item) {
                $sql = "UPDATE produk SET stok = stok + ? WHERE id = ?";
                dbExecute($sql, [$item['jumlah'], $item['produk_id']]);
            }
            
            // Update status
            $sql = "UPDATE {$this->table} SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
            dbExecute($sql, [$id]);
            
            dbCommit();
            
            return $this->getById($id);
            
        } catch (Exception $e) {
            dbRollback();
            throw $e;
        }
    }
    
    /**
     * Get sales statistics
     */
    public function getStats($year = null, $month = null) {
        $year = $year ?? date('Y');
        
        $stats = [];
        
        // Total sales
        $sql = "SELECT COUNT(*) as total, SUM(total_harga) as pendapatan 
                FROM {$this->table} 
                WHERE status IN ('completed', 'shipped') 
                AND YEAR(tanggal_order) = ?";
        $params = [$year];
        
        if ($month) {
            $sql .= " AND MONTH(tanggal_order) = ?";
            $params[] = $month;
        }
        
        $stats['summary'] = dbQueryOne($sql, $params);
        
        // Monthly breakdown
        $sql = "SELECT MONTH(tanggal_order) as bulan, COUNT(*) as total, SUM(total_harga) as pendapatan 
                FROM {$this->table} 
                WHERE status IN ('completed', 'shipped') 
                AND YEAR(tanggal_order) = ? 
                GROUP BY MONTH(tanggal_order) 
                ORDER BY bulan";
        $stats['monthly'] = dbQuery($sql, [$year]);
        
        // Status breakdown
        $sql = "SELECT status, COUNT(*) as total 
                FROM {$this->table} 
                WHERE YEAR(tanggal_order) = ? 
                GROUP BY status";
        $stats['by_status'] = dbQuery($sql, [$year]);
        
        return $stats;
    }
    
    /**
     * Get recent sales
     */
    public function getRecent($limit = 5) {
        return $this->getAll(['limit' => $limit]);
    }
}
