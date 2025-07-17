<?php
session_start();
require_once '../../config/koneksi.php';
require_once '../../includes/functions.php';

// Set header respons sebagai JSON untuk komunikasi dengan JavaScript
header('Content-Type: application/json');

// Keamanan: Pastikan hanya kasir yang login yang bisa mengakses
if (!isset($_SESSION['pengguna']) || $_SESSION['pengguna']['level'] !== 'kasir') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

// Terima data JSON yang dikirim dari halaman penjualan
$input = json_decode(file_get_contents('php://input'), true);

// Validasi dasar data yang masuk
if (empty($input['no_transaksi']) || !isset($input['total']) || !isset($input['bayar']) || empty($input['items'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Data transaksi tidak lengkap.']);
    exit;
}

try {
    // Mulai transaksi database untuk memastikan semua query berhasil atau tidak sama sekali
    $pdo->beginTransaction();

    // 1. Simpan data utama transaksi ke tabel 'penjualan'
    $stmtPenjualan = $pdo->prepare("
        INSERT INTO penjualan (no_transaksi, tanggal, id_user, total_harga, bayar, kembalian) 
        VALUES (?, NOW(), ?, ?, ?, ?)
    ");

    $stmtPenjualan->execute([
        $input['no_transaksi'],
        $_SESSION['pengguna']['id_user'], // Mengambil id_user dari session
        $input['total'],
        $input['bayar'],
        $input['kembalian']
    ]);

    // Ambil ID dari penjualan yang baru saja disimpan untuk digunakan di detail
    $id_penjualan = $pdo->lastInsertId();

    // 2. Siapkan query untuk menyimpan setiap item ke 'detail_penjualan'
    $stmtDetail = $pdo->prepare("
        INSERT INTO detail_penjualan (id_penjualan, id_barang, jumlah, harga_satuan, subtotal_barang) 
        VALUES (?, ?, ?, ?, ?)
    ");

    // 3. Siapkan query untuk mengurangi stok barang di tabel 'barang'
    $stmtUpdateStok = $pdo->prepare(
        "UPDATE barang SET stok = stok - ? WHERE id_barang = ?"
    );

    // 4. Loop melalui setiap item dari keranjang belanja dan simpan ke database
    foreach ($input['items'] as $item) {
        // Simpan ke detail_penjualan
        $stmtDetail->execute([
            $id_penjualan,
            $item['id_barang'],
            $item['qty'],
            $item['harga_jual'],
            $item['subtotal']
        ]);

        // Kurangi stok barang
        $stmtUpdateStok->execute([
            $item['qty'],
            $item['id_barang']
        ]);
    }

    // Jika semua query berhasil, konfirmasi transaksi
    $pdo->commit();

    // Kirim respons sukses kembali ke JavaScript
    echo json_encode([
        'success' => true,
        'message' => 'Transaksi berhasil disimpan!',
        'id_penjualan' => $id_penjualan // Kirim ID untuk cetak nota
    ]);

} catch (Exception $e) {
    // Jika terjadi error, batalkan semua perubahan di database
    $pdo->rollBack();
    http_response_code(500); // Internal Server Error

    // Kirim respons error yang informatif
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()
    ]);
}