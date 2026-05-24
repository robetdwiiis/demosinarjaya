<?php
/**
 * Testimonials API - SINAR JAYA KONVEKSI
 */

require_once 'config.php';
setJsonHeaders();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $action = $_GET['action'] ?? 'list';
        if ($action === 'admin_list') {
            getAdminTestimonials();
        } else {
            getTestimonials();
        }
        break;
    case 'POST':
        $action = $_GET['action'] ?? 'submit';
        if ($action === 'update_status') {
            updateTestimonialStatus();
        } elseif ($action === 'delete') {
            deleteTestimonial();
        } else {
            submitTestimonial();
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

/**
 * Get active testimonials
 */
function getTestimonials() {
    try {
        $pdo = getConnection();
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        
        $sql = "SELECT id, nama, perusahaan, jabatan, foto, rating, testimoni, created_at 
                FROM testimonial 
                WHERE status = 'active' 
                ORDER BY urutan ASC, created_at DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $data]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get all testimonials for admin
 */
function getAdminTestimonials() {
    try {
        $pdo = getConnection();
        $sql = "SELECT * FROM testimonial ORDER BY created_at DESC";
        $stmt = $pdo->query($sql);
        $data = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Update testimonial status
 */
function updateTestimonialStatus() {
    try {
        $pdo = getConnection();
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        $status = $input['status'] ?? 'inactive';

        $sql = "UPDATE testimonial SET status = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status, $id]);
        
        echo json_encode(['success' => true, 'message' => 'Status berhasil diupdate']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Delete testimonial
 */
function deleteTestimonial() {
    try {
        $pdo = getConnection();
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;

        $sql = "DELETE FROM testimonial WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Testimonial berhasil dihapus']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Submit a new testimonial (e.g. after purchase)
 */
function submitTestimonial() {
    try {
        $pdo = getConnection();
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $input = $_POST;
        }
        
        $nama = $input['nama'] ?? '';
        $perusahaan = $input['perusahaan'] ?? '';
        $jabatan = $input['jabatan'] ?? '';
        $rating = (int)($input['rating'] ?? 5);
        $testimoni = $input['testimoni'] ?? '';
        $foto = $input['foto'] ?? 'https://i.pravatar.cc/100?u=' . urlencode($nama);

        if (empty($nama) || empty($testimoni)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nama dan testimoni wajib diisi']);
            return;
        }

        $sql = "INSERT INTO testimonial (nama, perusahaan, jabatan, rating, testimoni, foto, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama, $perusahaan, $jabatan, $rating, $testimoni, $foto]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Testimoni berhasil dikirim dan menunggu moderasi admin.'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
