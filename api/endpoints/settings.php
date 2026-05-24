<?php
/**
 * API Endpoint: Settings
 * SINAR JAYA KONVEKSI
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
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
            if (isset($_GET['key'])) {
                $value = getSetting($_GET['key']);
                successResponse(['key' => $_GET['key'], 'value' => $value]);
            } 
            elseif (isset($_GET['group'])) {
                $sql = "SELECT setting_key, setting_value, setting_type FROM settings WHERE setting_group = ?";
                $result = dbQuery($sql, [$_GET['group']]);
                $settings = [];
                foreach ($result as $row) {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
                successResponse($settings);
            }
            else {
                $sql = "SELECT setting_group, setting_key, setting_value, setting_type, setting_label FROM settings WHERE is_public = 1 ORDER BY setting_group, id";
                $result = dbQuery($sql);
                $settings = [];
                foreach ($result as $row) {
                    if (!isset($settings[$row['setting_group']])) {
                        $settings[$row['setting_group']] = [];
                    }
                    $settings[$row['setting_group']][$row['setting_key']] = $row['setting_value'];
                }
                successResponse($settings);
            }
            break;
            
        case 'PUT':
        case 'POST':
            $data = getInput();
            
            if (isset($data['key']) && isset($data['value'])) {
                updateSetting($data['key'], $data['value']);
                successResponse(null, 'Pengaturan berhasil disimpan');
            }
            elseif (is_array($data)) {
                foreach ($data as $key => $value) {
                    if ($key !== 'key' && $key !== 'value') {
                        updateSetting($key, $value);
                    }
                }
                successResponse(null, 'Pengaturan berhasil disimpan');
            }
            else {
                errorResponse('Data tidak valid', 400);
            }
            break;
            
        default:
            errorResponse('Method tidak diizinkan', 405);
    }
} catch (Exception $e) {
    errorResponse($e->getMessage(), 500);
}
