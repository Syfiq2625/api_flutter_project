<?php
require_once 'db.php';

// Sinkronisasi zona waktu agar fungsi CURDATE() akurat dengan waktu lokal (WITA)
date_default_timezone_set('Asia/Makassar');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Mengambil daftar peminjaman dan mengecek keterlambatan
    try {
        $sql = "
            SELECT 
                l.id, l.tool_id, t.name as tool_name, 
                l.borrower_name, l.qty, 
                l.loan_date, l.due_date, l.return_date,
                (l.return_date IS NULL AND l.due_date < CURDATE()) as is_late
            FROM loans l
            JOIN tools t ON l.tool_id = t.id
            ORDER BY l.return_date IS NULL DESC, l.loan_date DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        $data = $stmt->fetchAll();
        
        // Konversi nilai 1/0 dari MySQL menjadi boolean sejati untuk Flutter
        foreach($data as &$row) {
            $row['is_late'] = (bool)$row['is_late'];
        }
        
        sendResponse(true, $data, 'Data peminjaman berhasil diambil');
    } catch (PDOException $e) {
        sendResponse(false, null, 'Error: ' . $e->getMessage());
    }
// ... (kode GET dan PUT sebelumnya) ...

} elseif ($method === 'PUT') {
    // ... (kode PUT sebelumnya biarkan utuh)
    try {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            sendResponse(false, null, 'ID peminjaman diperlukan');
        }
        
        $sql = "UPDATE loans SET return_date = CURDATE() WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        sendResponse(true, null, 'Alat berhasil dikembalikan');
    } catch (PDOException $e) {
        sendResponse(false, null, 'Error: ' . $e->getMessage());
    }

// === TAMBAHKAN BLOK POST INI ===
} elseif ($method === 'POST') {
    // Menerima data JSON dari Flutter
    $input = json_decode(file_get_contents('php://input'), true);
    $tool_id = $input['tool_id'] ?? null;
    $borrower_name = $input['borrower_name'] ?? '';
    $qty = $input['qty'] ?? 0;
    $due_date = $input['due_date'] ?? null;
    $loan_date = date('Y-m-d'); // Hari ini

    if (!$tool_id || empty($borrower_name) || $qty <= 0 || !$due_date) {
        sendResponse(false, null, 'Data peminjaman tidak lengkap');
    }

    try {
        // 1. VALIDASI STOK (Wajib sesuai arsitektur)
        // Cek total alat
        $stmt = $pdo->prepare("SELECT total_qty FROM tools WHERE id = :id");
        $stmt->execute(['id' => $tool_id]);
        $tool = $stmt->fetch();
        
        if (!$tool) {
            sendResponse(false, null, 'Alat tidak ditemukan di database');
        }
        
        // Cek jumlah yang sedang dipinjam
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(qty), 0) as borrowed FROM loans WHERE tool_id = :id AND return_date IS NULL");
        $stmt->execute(['id' => $tool_id]);
        $borrowed = $stmt->fetch()['borrowed'];
        
        $available = $tool['total_qty'] - $borrowed;
        
        // 2. Tolak jika melebihi ketersediaan
        if ($qty > $available) {
            sendResponse(false, null, "Peminjaman ditolak! Stok tersedia saat ini hanya $available unit.");
        }

        // 3. Simpan data jika lolos validasi
        $sql = "INSERT INTO loans (tool_id, borrower_name, qty, loan_date, due_date) 
                VALUES (:tool_id, :borrower_name, :qty, :loan_date, :due_date)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'tool_id' => $tool_id,
            'borrower_name' => $borrower_name,
            'qty' => $qty,
            'loan_date' => $loan_date,
            'due_date' => $due_date
        ]);

        sendResponse(true, null, 'Peminjaman berhasil dicatat');
    } catch (PDOException $e) {
        sendResponse(false, null, 'Error: ' . $e->getMessage());
    }
// ===============================

} else {
    http_response_code(405);
    sendResponse(false, null, 'Method not allowed');
}
?>