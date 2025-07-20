<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/koneksi.php';

// Validasi akses
if (!isset($_SESSION['pengguna']) || $_SESSION['pengguna']['level'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Anda tidak memiliki akses untuk menambah data biaya'
    ]);
    exit;
}

// Ambil data dari request
$data = json_decode(file_get_contents('php://input'), true);

// Validasi data
if (empty($data['nama_biaya']) || empty($data['tanggal']) || empty($data['keterangan']) || !isset($data['jumlah_biaya'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Data tidak lengkap'
    ]);
    exit;
}

$nama_biaya = $data['nama_biaya'];
$tanggal = $data['tanggal'];
$jumlah_biaya = (float)$data['jumlah_biaya'];
$keterangan = $data['keterangan'];

try {
    // Insert data biaya operasional
    $stmt = $pdo->prepare("
        INSERT INTO biaya_operasional (nama_biaya, tanggal, jumlah_biaya, keterangan) 
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([$nama_biaya, $tanggal, $jumlah_biaya, $keterangan]);

    echo json_encode([
        'success' => true,
        'message' => 'Data biaya operasional berhasil disimpan'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
