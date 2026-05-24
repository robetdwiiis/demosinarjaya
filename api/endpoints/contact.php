<?php
/**
 * API Endpoint: Contact Messages
 * SINAR JAYA KONVEKSI
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $sql = "SELECT * FROM kontak WHERE id = ?";
                $result = dbQueryOne($sql, [(int)$_GET['id']]);
                if ($result) {
                    // Mark as read
                    dbExecute("UPDATE kontak SET status = 'read' WHERE id = ? AND status = 'unread'", [$result['id']]);
                    successResponse($result);
                } else {
                    errorResponse('Pesan tidak ditemukan', 404);
                }
            } else {
                $sql = "SELECT * FROM kontak WHERE 1=1";
                $params = [];
                
                if (isset($_GET['status'])) {
                    $sql .= " AND status = ?";
                    $params[] = $_GET['status'];
                }
                
                $sql .= " ORDER BY created_at DESC";
                
                if (isset($_GET['limit'])) {
                    $sql .= " LIMIT " . (int)$_GET['limit'];
                }
                
                $result = dbQuery($sql, $params);
                
                // Count unread
                $unread = dbQueryOne("SELECT COUNT(*) as total FROM kontak WHERE status = 'unread'");
                
                successResponse([
                    'messages' => $result,
                    'unread_count' => $unread['total']
                ]);
            }
            break;
            
        case 'POST':
            $data = getInput();
            
            $errors = validateRequired($data, ['nama', 'email', 'pesan']);
            if (!empty($errors)) {
                errorResponse('Validasi gagal', 422, $errors);
            }
            
            if (!validateEmail($data['email'])) {
                errorResponse('Format email tidak valid', 422);
            }
            
            $sql = "INSERT INTO kontak (nama, email, telepon, perusahaan, subjek, pesan) VALUES (?, ?, ?, ?, ?, ?)";
            
            $result = dbExecute($sql, [
                sanitize($data['nama']),
                sanitize($data['email']),
                sanitize($data['telepon'] ?? null),
                sanitize($data['perusahaan'] ?? null),
                $data['subjek'] ?? 'inquiry',
                sanitize($data['pesan'])
            ]);
            
            if ($result) {
                successResponse(null, 'Pesan berhasil dikirim', 201);
            } else {
                errorResponse('Gagal mengirim pesan', 500);
            }
            break;
            
        case 'PUT':
            if (!isset($_GET['id'])) {
                errorResponse('ID diperlukan', 400);
            }
            
            $data = getInput();
            $id = (int)$_GET['id'];
            
            if (isset($data['status'])) {
                $sql = "UPDATE kontak SET status = ?, updated_at = NOW() WHERE id = ?";
                dbExecute($sql, [$data['status'], $id]);
            }
            
            if (isset($data['catatan_admin'])) {
                $sql = "UPDATE kontak SET catatan_admin = ?, updated_at = NOW() WHERE id = ?";
                dbExecute($sql, [$data['catatan_admin'], $id]);
            }
            
            successResponse(null, 'Pesan berhasil diperbarui');
            break;
            
        case 'DELETE':
            if (!isset($_GET['id'])) {
                errorResponse('ID diperlukan', 400);
            }
            
            $sql = "DELETE FROM kontak WHERE id = ?";
            if (dbExecute($sql, [(int)$_GET['id']])) {
                successResponse(null, 'Pesan berhasil dihapus');
            } else {
                errorResponse('Gagal menghapus pesan', 500);
            }
            break;
            
        default:
            errorResponse('Method tidak diizinkan', 405);
    }
} catch (Exception $e) {
    errorResponse($e->getMessage(), 500);
}
