<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/koneksi.php';

// Validasi akses
if (!isset($_SESSION['pengguna']) || $_SESSION['pengguna']['level'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Anda tidak memiliki akses untuk menghapus data biaya'
    ]);
    exit;
}

// Ambil ID biaya dari URL
$id_biaya = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id_biaya) {
    echo json_encode([
        'success' => false,
        'message' => 'ID biaya tidak valid'
    ]);
    exit;
}

try {
    // Hapus data biaya operasional
    $stmt = $pdo->prepare("DELETE FROM biaya_operasional WHERE id_biaya = ?");
    $stmt->execute([$id_biaya]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Data biaya operasional tidak ditemukan');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Data biaya operasional berhasil dihapus'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
