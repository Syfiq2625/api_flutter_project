<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

// Saat ini kita fokus pada GET (Mengambil semua data)
if ($method === 'GET') {
    try {
        $search = $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? '';

        $sql = "
            SELECT 
                t.id, t.name, t.inventory_code, t.category, t.total_qty, t.condition,
                COALESCE(SUM(l.qty), 0) AS borrowed_qty
            FROM tools t
            LEFT JOIN loans l ON t.id = l.tool_id AND l.return_date IS NULL
        ";

        $whereClauses = [];
        $params = [];

        // Pencarian (berdasarkan nama atau kategori)
        if ($search !== '') {
            $whereClauses[] = "(t.name LIKE :search OR t.category LIKE :search)";
            $params['search'] = "%$search%";
        }

        if (count($whereClauses) > 0) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }
        
        $sql .= " GROUP BY t.id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tools = $stmt->fetchAll();

        $result = [];

        foreach ($tools as $tool) {
            $available_qty = $tool['total_qty'] - $tool['borrowed_qty'];
            $status = 'tersedia';

            if ($tool['condition'] === 'rusak') {
                $status = 'rusak';
            } elseif ($available_qty <= 0) {
                $status = 'dipinjam';
            }

            // Eksekusi filter dinamis di PHP sesuai arsitektur (ADR-002)
            if ($filter === 'available' && $status !== 'tersedia') continue;
            if ($filter === 'repair' && $tool['condition'] !== 'perlu_perbaikan') continue;

            $result[] = [
                'id' => $tool['id'],
                'name' => $tool['name'],
                'inventory_code' => $tool['inventory_code'],
                'category' => $tool['category'],
                'total_qty' => $tool['total_qty'],
                'available_qty' => $available_qty,
                'condition' => $tool['condition'],
                'status' => $status
            ];
        }

        sendResponse(true, $result, 'Data alat berhasil diambil');
    } catch (PDOException $e) {
        sendResponse(false, null, "Terjadi kesalahan sistem: " . $e->getMessage());
    }
}