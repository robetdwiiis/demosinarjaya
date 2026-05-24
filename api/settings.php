<?php
/**
 * Settings API
 * SINAR JAYA KONVEKSI
 * 
 * Endpoints:
 * GET  /api/settings.php              - Get all settings
 * GET  /api/settings.php?group=social - Get settings by group
 * POST /api/settings.php              - Update settings
 */

require_once 'config.php';
setJsonHeaders();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getSettings();
        break;
    case 'POST':
        updateSettings();
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

/**
 * Get settings from database
 */
function getSettings() {
    $pdo = getConnection();
    
    // Check if group filter is requested
    $group = isset($_GET['group']) ? trim($_GET['group']) : null;
    $key = isset($_GET['key']) ? trim($_GET['key']) : null;
    
    try {
        if ($key) {
            // Get single setting by key
            $stmt = $pdo->prepare("SELECT setting_key, setting_value, setting_type, setting_label FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $setting = $stmt->fetch();
            
            if ($setting) {
                echo json_encode([
                    'success' => true,
                    'data' => $setting
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Setting not found']);
            }
        } elseif ($group) {
            // Get settings by group
            $stmt = $pdo->prepare("SELECT setting_key, setting_value, setting_type, setting_label FROM settings WHERE setting_group = ? ORDER BY id");
            $stmt->execute([$group]);
            $settings = $stmt->fetchAll();
            
            // Convert to key-value object
            $data = [];
            foreach ($settings as $setting) {
                $data[$setting['setting_key']] = $setting['setting_value'];
            }
            
            echo json_encode([
                'success' => true,
                'group' => $group,
                'data' => $data
            ]);
        } else {
            // Get all settings grouped
            $stmt = $pdo->query("SELECT setting_group, setting_key, setting_value, setting_type, setting_label FROM settings ORDER BY setting_group, id");
            $settings = $stmt->fetchAll();
            
            // Group settings
            $grouped = [];
            foreach ($settings as $setting) {
                $group = $setting['setting_group'];
                if (!isset($grouped[$group])) {
                    $grouped[$group] = [];
                }
                $grouped[$group][$setting['setting_key']] = $setting['setting_value'];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $grouped
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Update settings in database
 */
function updateSettings() {
    $pdo = getConnection();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['settings'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input. Expected: {"settings": {"key": "value", ...}}']);
        return;
    }
    
    $settings = $input['settings'];
    $group = isset($input['group']) ? $input['group'] : null;
    $updated = 0;
    $errors = [];
    
    try {
        $pdo->beginTransaction();
        
        foreach ($settings as $key => $value) {
            // Check if setting exists
            $stmt = $pdo->prepare("SELECT id FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            
            if ($stmt->fetch()) {
                // Update existing setting
                $updateStmt = $pdo->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
                $updateStmt->execute([$value, $key]);
                $updated++;
            } else {
                // Insert new setting if group is provided
                if ($group) {
                    $insertStmt = $pdo->prepare("INSERT INTO settings (setting_group, setting_key, setting_value, setting_label) VALUES (?, ?, ?, ?)");
                    $label = ucwords(str_replace('_', ' ', $key));
                    $insertStmt->execute([$group, $key, $value, $label]);
                    $updated++;
                } else {
                    $errors[] = "Setting key '$key' not found and no group specified for insert";
                }
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "$updated setting(s) updated successfully",
            'updated_count' => $updated,
            'errors' => $errors
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
