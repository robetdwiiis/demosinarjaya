<?php
/**
 * Contact API - SINAR JAYA KONVEKSI
 * Menangani form kontak dari website
 */

require_once 'config.php';
setJsonHeaders();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

switch ($method) {
    case 'GET':
        getContacts();
        break;
    case 'POST':
        createContact();
        break;
    case 'PUT':
        updateContactStatus();
        break;
    case 'DELETE':
        deleteContact();
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

/**
 * Get all contacts (for admin)
 */
function getContacts() {
    try {
        $pdo = getConnection();
        
        $status = $_GET['status'] ?? null;
        $limit = $_GET['limit'] ?? 50;
        
        $sql = "SELECT * FROM kontak";
        $params = [];
        
        if ($status) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }
        
        $limitVal = (int)$limit;
        $sql .= " ORDER BY created_at DESC LIMIT $limitVal";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $contacts = $stmt->fetchAll();
        
        $stmt = $pdo->query("SELECT COUNT(*) as unread FROM kontak WHERE status = 'unread'");
        $unreadResult = $stmt->fetch();
        $unreadCount = $unreadResult ? $unreadResult['unread'] : 0;
        
        sendJson([
            'success' => true,
            'data' => $contacts,
            'unread_count' => (int)$unreadCount
        ]);
        
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
    }
}

/**
 * Create new contact message
 */
function createContact() {
    try {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $data = json_decode(file_get_contents('php://input'), true);
        } else {
            $data = $_POST;
        }
        
        $required = ['nama', 'email', 'pesan'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                sendJson(['success' => false, 'message' => "Field '$field' is required"], 400);
                return;
            }
        }
        
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            sendJson(['success' => false, 'message' => 'Invalid email format'], 400);
            return;
        }
        
        $nama = htmlspecialchars(trim($data['nama']), ENT_QUOTES, 'UTF-8');
        $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
        $telepon = htmlspecialchars(trim($data['telepon'] ?? ''), ENT_QUOTES, 'UTF-8');
        $perusahaan = htmlspecialchars(trim($data['perusahaan'] ?? ''), ENT_QUOTES, 'UTF-8');
        $subjek = $data['subjek'] ?? 'inquiry';
        $pesan = htmlspecialchars(trim($data['pesan']), ENT_QUOTES, 'UTF-8');
        
        $validSubjek = ['inquiry', 'quotation', 'order', 'custom', 'partnership', 'other'];
        if (!in_array($subjek, $validSubjek)) {
            $subjek = 'inquiry';
        }
        
        $pdo = getConnection();
        
        $sql = "INSERT INTO kontak (nama, email, telepon, perusahaan, subjek, pesan, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'unread')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama, $email, $telepon, $perusahaan, $subjek, $pesan]);
        
        $id = $pdo->lastInsertId();
        
        logActivity('create', 'kontak', $id, null, $data);
        
        sendJson([
            'success' => true, 
            'message' => 'Pesan berhasil dikirim! Kami akan menghubungi Anda segera.',
            'id' => $id
        ]);
        
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
    }
}

/**
 * Update contact status (for admin)
 */
function updateContactStatus() {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['action']) && $data['action'] === 'mark_all_read') {
            $pdo = getConnection();
            $stmt = $pdo->prepare("UPDATE kontak SET status = 'read' WHERE status = 'unread'");
            $stmt->execute();
            
            $affectedRows = $stmt->rowCount();
            sendJson([
                'success' => true, 
                'message' => "Marked $affectedRows message(s) as read"
            ]);
            return;
        }
        
        if (empty($data['id'])) {
            sendJson(['success' => false, 'message' => 'Contact ID is required'], 400);
            return;
        }
        
        $id = (int)$data['id'];
        $status = $data['status'] ?? 'read';
        $catatan = $data['catatan_admin'] ?? null;
        
        $validStatus = ['unread', 'read', 'replied', 'archived'];
        if (!in_array($status, $validStatus)) {
            $status = 'read';
        }
        
        $pdo = getConnection();
        
        $sql = "UPDATE kontak SET status = ?, catatan_admin = ?";
        $params = [$status, $catatan];
        
        if ($status === 'replied') {
            $sql .= ", replied_at = NOW()";
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        sendJson(['success' => true, 'message' => 'Status updated successfully']);
        
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
    }
}

/**
 * Delete contact (for admin)
 */
function deleteContact() {
    try {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            sendJson(['success' => false, 'message' => 'Contact ID is required'], 400);
            return;
        }
        
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("DELETE FROM kontak WHERE id = ?");
        $stmt->execute([(int)$id]);
        
        if ($stmt->rowCount() > 0) {
            sendJson(['success' => true, 'message' => 'Contact deleted successfully']);
        } else {
            sendJson(['success' => false, 'message' => 'Contact not found'], 404);
        }
        
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
    }
}

function logActivity($action, $model, $modelId, $oldValues = null, $newValues = null) {
    // Silence errors in logger
    try {
        $pdo = getConnection();
        $sql = "INSERT INTO activity_log (action, model, model_id, old_values, new_values, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $action, $model, $modelId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {}
}
