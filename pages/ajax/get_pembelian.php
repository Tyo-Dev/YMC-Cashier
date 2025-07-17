<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/koneksi.php';

// Validasi akses
if (!isset($_SESSION['pengguna']) || !in_array($_SESSION['pengguna']['level'], ['admin', 'pemilik'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Anda tidak memiliki akses'
    ]);
    exit;
}

// Ambil ID pembelian dari request
$id_pembelian = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id_pembelian) {
    echo json_encode([
        'success' => false,
        'message' => 'ID pembelian tidak valid'
    ]);
    exit;
}

try {
    // Ambil data pembelian
    $stmt = $pdo->prepare("
        SELECT p.*, pm.nama_pemasok
        FROM pembelian p
        JOIN pemasok pm ON p.id_pemasok = pm.id_pemasok
        WHERE p.id_pembelian = ?
    ");
    $stmt->execute([$id_pembelian]);
    $pembelian = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pembelian) {
        throw new Exception('Data pembelian tidak ditemukan');
    }

    // Ambil data detail pembelian
    $stmt = $pdo->prepare("
        SELECT dp.*, b.nama_barang, b.id_barang
        FROM detail_pembelian dp
        JOIN barang b ON dp.id_barang = b.id_barang
        WHERE dp.id_pembelian = ?
    ");
    $stmt->execute([$id_pembelian]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'pembelian' => $pembelian,
        'items' => $items
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
