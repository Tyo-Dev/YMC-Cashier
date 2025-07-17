<?php
session_start();
require_once '../../config/koneksi.php';
require_once '../../includes/functions.php';

// Keamanan: Hanya kasir yang bisa akses
checkUserLevel(['kasir']);

$search = $_GET['search'] ?? '';
$kategori = $_GET['kategori'] ?? '';

try {
    $params = [];
    $where = ["stok > 0"];

    if ($search) {
        $where[] = "(nama_barang LIKE ? OR id_barang LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($kategori) {
        $where[] = "k.kategori_barang = ?";
        $params[] = $kategori;
    }

    // Always ensure we have products in stock
    $where[] = "b.stok > 0";

    $whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

    $sql = "
        SELECT b.id_barang, b.nama_barang, b.harga_jual, b.stok, b.satuan_barang, k.kategori_barang
        FROM barang b
        LEFT JOIN kategori k ON b.id_kategori = k.id_kategori
        $whereClause
        ORDER BY b.nama_barang 
        LIMIT 100
    ";

    $stmt = $pdo->prepare($sql);

    $searchTerm = "%$search%";
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($products);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
