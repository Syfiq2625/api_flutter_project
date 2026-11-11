<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Menghitung ringkasan menggunakan Subquery agar efisien dalam 1 kali request
        $sql = "
            SELECT 
                (SELECT COALESCE(SUM(total_qty), 0) FROM tools) AS total_items,
                (SELECT COALESCE(SUM(qty), 0) FROM loans WHERE return_date IS NULL) AS total_borrowed,
                (SELECT COALESCE(SUM(total_qty), 0) FROM tools WHERE `condition` = 'rusak') AS total_broken
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $summary = $stmt->fetch();

        // Format output menjadi integer
        $result = [
            'total_items' => (int)$summary['total_items'],
            'total_borrowed' => (int)$summary['total_borrowed'],
            'total_broken' => (int)$summary['total_broken'],
        ];

        sendResponse(true, $result, 'Ringkasan berhasil diambil');

    } catch (PDOException $e) {
        sendResponse(false, null, "Terjadi kesalahan sistem: " . $e->getMessage());
    }
} else {
    http_response_code(405);
    sendResponse(false, null, "Metode HTTP tidak diizinkan");
}
?>