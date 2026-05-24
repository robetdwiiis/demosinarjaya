<?php
/**
 * API Endpoint: Categories
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

require_once __DIR__ . '/../models/Kategori.php';

$kategori = new Kategori();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $result = $kategori->getById((int)$_GET['id']);
                if ($result) {
                    successResponse($result);
                } else {
                    errorResponse('Kategori tidak ditemukan', 404);
                }
            } 
            elseif (isset($_GET['slug'])) {
                $result = $kategori->getBySlug($_GET['slug']);
                if ($result) {
                    successResponse($result);
                } else {
                    errorResponse('Kategori tidak ditemukan', 404);
                }
            }
            elseif (isset($_GET['summary'])) {
                $result = $kategori->getWithSummary();
                successResponse($result);
            }
            else {
                $result = $kategori->getAll();
                successResponse($result);
            }
            break;
            
        case 'POST':
            $data = getInput();
            
            $errors = validateRequired($data, ['nama_kategori']);
            if (!empty($errors)) {
                errorResponse('Validasi gagal', 422, $errors);
            }
            
            $result = $kategori->create($data);
            if ($result) {
                successResponse($result, 'Kategori berhasil ditambahkan', 201);
            } else {
                errorResponse('Gagal menambahkan kategori', 500);
            }
            break;
            
        case 'PUT':
            if (!isset($_GET['id'])) {
                errorResponse('ID kategori diperlukan', 400);
            }
            
            $data = getInput();
            $result = $kategori->update((int)$_GET['id'], $data);
            
            if ($result) {
                successResponse($result, 'Kategori berhasil diperbarui');
            } else {
                errorResponse('Gagal memperbarui kategori', 500);
            }
            break;
            
        case 'DELETE':
            if (!isset($_GET['id'])) {
                errorResponse('ID kategori diperlukan', 400);
            }
            
            $result = $kategori->delete((int)$_GET['id']);
            if (isset($result['error'])) {
                errorResponse($result['error'], 400);
            } elseif ($result) {
                successResponse(null, 'Kategori berhasil dihapus');
            } else {
                errorResponse('Gagal menghapus kategori', 500);
            }
            break;
            
        default:
            errorResponse('Method tidak diizinkan', 405);
    }
} catch (Exception $e) {
    errorResponse($e->getMessage(), 500);
}
