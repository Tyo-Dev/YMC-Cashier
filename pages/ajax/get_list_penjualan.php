<?php
header('Content-Type: application/json');
require_once '../../config/koneksi.php';

// Ambil parameter kasir dari query string
$kasir_id = $_GET['kasir'] ?? '';

try {
    $sql = "
        SELECT 
            p.id_penjualan,
            p.no_transaksi,
            p.tanggal,
            p.total_harga,
            u.nama_user AS nama_kasir,
            (SELECT SUM(dp.jumlah) FROM detail_penjualan dp WHERE dp.id_penjualan = p.id_penjualan) AS total_item
        FROM 
            penjualan p
        JOIN 
            pengguna u ON p.id_user = u.id_user
    ";

    $params = [];

    // Jika ada filter kasir, tambahkan kondisi WHERE
    if (!empty($kasir_id)) {
        $sql .= " WHERE p.id_user = ?";
        $params[] = $kasir_id;
    }

    $sql .= " ORDER BY p.tanggal DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log untuk debugging
    error_log("Kasir ID: " . $kasir_id);
    error_log("Query: " . $sql);
    error_log("Hasil: " . count($sales) . " records");

    echo json_encode($sales);
} catch (Exception $e) {
    error_log("Error in get_list_penjualan: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
