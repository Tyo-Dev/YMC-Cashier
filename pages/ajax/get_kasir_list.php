<?php
header('Content-Type: application/json');
require_once '../../config/koneksi.php';

try {
    // Ambil daftar pengguna dengan role kasir
    $stmt = $pdo->query("
        SELECT id_user, nama_user 
        FROM pengguna 
        WHERE level = 'kasir'
        ORDER BY nama_user ASC
    ");
    
    $kasirList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($kasirList);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal mengambil daftar kasir: ' . $e->getMessage()]);
}
?>