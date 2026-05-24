<?php
/**
 * Main API Router
 * SINAR JAYA KONVEKSI
 * 
 * URL: /api/index.php?endpoint=products&action=get
 */

// Start session
session_start();

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include config
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

// Get endpoint from URL
$endpoint = $_GET['endpoint'] ?? '';

// Available endpoints
$endpoints = [
    'products' => 'endpoints/products.php',
    'categories' => 'endpoints/categories.php',
    'auth' => 'endpoints/auth.php',
    'settings' => 'endpoints/settings.php',
    'contact' => 'endpoints/contact.php',
];

// Check if endpoint exists
if (empty($endpoint)) {
    successResponse([
        'name' => 'SINAR JAYA KONVEKSI API',
        'version' => '1.0.0',
        'endpoints' => array_keys($endpoints),
        'documentation' => 'https://sinarjaya-konveksi.com/api/docs'
    ]);
}

if (!isset($endpoints[$endpoint])) {
    errorResponse('Endpoint tidak ditemukan', 404);
}

// Include endpoint file
$endpointFile = __DIR__ . '/' . $endpoints[$endpoint];

if (file_exists($endpointFile)) {
    require_once $endpointFile;
} else {
    errorResponse('Endpoint file tidak ditemukan', 500);
}
