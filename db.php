<?php
// 1. Wajib: Tangani CORS dan Preflight OPTIONS sesuai arsitektur
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200);
    exit; 
}

// 2. Format JSON agar respons konsisten
header('Content-Type: application/json; charset=utf-8');

// Fungsi pembantu untuk mengembalikan JSON secara konsisten
function sendResponse($success, $data = null, $message = '') {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

// 3. Konfigurasi PDO murni ke MySQL Laragon
$host = 'localhost';
$dbname = 'lab_komputer'; // Sesuaikan dengan nama database di Fase 1
$username = 'root'; // Default Laragon
$password = ''; // Default Laragon

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Atur error mode ke exception agar mudah di-debug
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Kembalikan hasil query sebagai array asosiatif
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    sendResponse(false, null, "Koneksi database gagal: " . $e->getMessage());
}
?>