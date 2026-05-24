<?php
// Simple test script
require_once 'config.php';
header('Content-Type: application/json');

try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM kontak");
    $result = $stmt->fetch();
    echo json_encode(['success' => true, 'message' => 'Connected', 'count' => $result['count']]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
