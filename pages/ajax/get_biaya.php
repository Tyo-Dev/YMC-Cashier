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

// Ambil ID biaya dari request
$id_biaya = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id_biaya) {
    echo json_encode([
        'success' => false,
        'message' => 'ID biaya tidak valid'
    ]);
    exit;
}

try {
    // Ambil data biaya operasional
    $stmt = $pdo->prepare("
        SELECT * FROM biaya_operasional WHERE id_biaya = ?
    ");
    $stmt->execute([$id_biaya]);
    $biaya = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$biaya) {
        throw new Exception('Data biaya operasional tidak ditemukan');
    }

    echo json_encode([
        'success' => true,
        'biaya' => $biaya
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
