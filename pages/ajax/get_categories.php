<?php
session_start();
require_once '../../config/koneksi.php';
require_once '../../includes/functions.php';

// Keamanan: Hanya kasir yang bisa akses
checkUserLevel(['kasir']);

try {
    $stmt = $pdo->query("
        SELECT kategori_barang 
        FROM kategori 
        ORDER BY kategori_barang
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

    header('Content-Type: application/json');
    echo json_encode($categories);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
