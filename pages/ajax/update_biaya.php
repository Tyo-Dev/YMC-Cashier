<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/koneksi.php';

// Validasi akses
if (!isset($_SESSION['pengguna']) || $_SESSION['pengguna']['level'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Anda tidak memiliki akses untuk memperbarui data biaya'
    ]);
    exit;
}

// Ambil data dari request
$data = json_decode(file_get_contents('php://input'), true);

// Validasi data
if (empty($data['id_biaya']) || empty($data['nama_biaya']) || empty($data['tanggal']) || empty($data['keterangan']) || !isset($data['jumlah_biaya'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Data tidak lengkap'
    ]);
    exit;
}

$id_biaya = (int)$data['id_biaya'];
$nama_biaya = $data['nama_biaya'];
$tanggal = $data['tanggal'];
$jumlah_biaya = (float)$data['jumlah_biaya'];
$keterangan = $data['keterangan'];

try {
    // Update data biaya operasional
    $stmt = $pdo->prepare("
        UPDATE biaya_operasional 
        SET nama_biaya = ?, tanggal = ?, jumlah_biaya = ?, keterangan = ? 
        WHERE id_biaya = ?
    ");

    $stmt->execute([$nama_biaya, $tanggal, $jumlah_biaya, $keterangan, $id_biaya]);

    echo json_encode([
        'success' => true,
        'message' => 'Data biaya operasional berhasil diperbarui'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
