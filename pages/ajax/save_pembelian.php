<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/koneksi.php';

// Validasi akses
if (!isset($_SESSION['pengguna']) || !in_array($_SESSION['pengguna']['level'], ['admin', 'pemilik'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Anda tidak memiliki akses untuk menambah data pembelian'
    ]);
    exit;
}

// Ambil data dari request
$data = json_decode(file_get_contents('php://input'), true);

// Validasi data
if (empty($data['tanggal']) || empty($data['id_pemasok']) || empty($data['items'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Data tidak lengkap'
    ]);
    exit;
}

$tanggal = $data['tanggal'];
$id_pemasok = (int)$data['id_pemasok'];
$items = $data['items'];
$total_harga_beli = (float)$data['total_harga_beli'];

try {
    // Periksa struktur tabel pembelian
    $stmtPembelianColumns = $pdo->query("SHOW COLUMNS FROM pembelian");
    $pembelianColumns = [];
    while ($row = $stmtPembelianColumns->fetch(PDO::FETCH_ASSOC)) {
        $pembelianColumns[] = $row['Field'];
    }

    // Periksa struktur tabel detail_pembelian
    $stmtDetailColumns = $pdo->query("SHOW COLUMNS FROM detail_pembelian");
    $detailColumns = [];
    while ($row = $stmtDetailColumns->fetch(PDO::FETCH_ASSOC)) {
        $detailColumns[] = $row['Field'];
    }

    // Log kolom-kolom yang ditemukan
    error_log("Columns in pembelian table: " . implode(", ", $pembelianColumns));
    error_log("Columns in detail_pembelian table: " . implode(", ", $detailColumns));

    // Mulai transaksi
    $pdo->beginTransaction();

    // 1. Insert header pembelian - Dinamis berdasarkan struktur tabel
    $hasIdUserColumn = in_array('id_user', $pembelianColumns);

    if ($hasIdUserColumn) {
        $stmt = $pdo->prepare("
            INSERT INTO pembelian (tanggal, id_pemasok, total_harga_beli, id_user) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$tanggal, $id_pemasok, $total_harga_beli, $_SESSION['pengguna']['id_user']]);
    } else {
        // Coba alternatif seperti id_kasir atau id_pengguna
        $userColumn = null;
        foreach ($pembelianColumns as $column) {
            if (
                strpos($column, 'id_kas') !== false ||
                strpos($column, 'id_peng') !== false ||
                strpos($column, 'id_user') !== false
            ) {
                $userColumn = $column;
                break;
            }
        }

        if ($userColumn) {
            $stmt = $pdo->prepare("
                INSERT INTO pembelian (tanggal, id_pemasok, total_harga_beli, $userColumn) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$tanggal, $id_pemasok, $total_harga_beli, $_SESSION['pengguna']['id_user']]);
        } else {
            // Jika tidak ada kolom user, insert tanpa kolom user
            $stmt = $pdo->prepare("
                INSERT INTO pembelian (tanggal, id_pemasok, total_harga_beli) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$tanggal, $id_pemasok, $total_harga_beli]);
        }
    }

    $id_pembelian = $pdo->lastInsertId();

    // 2. Insert detail pembelian
    foreach ($items as $item) {
        // Validasi item data
        if (empty($item['id_barang']) || empty($item['jumlah']) || empty($item['harga_beli'])) {
            throw new Exception('Data item tidak lengkap');
        }

        $id_barang = (int)$item['id_barang'];
        $jumlah = (int)$item['jumlah'];
        $harga_beli = (float)$item['harga_beli'];
        $subtotal = $jumlah * $harga_beli;

        // Deteksi nama kolom harga
        $priceColumn = in_array('harga_beli', $detailColumns) ? 'harga_beli' : 'harga_satuan';

        // Deteksi nama kolom subtotal
        $hasSubtotalColumn = in_array('subtotal', $detailColumns);
        $hasSubtotalBarangColumn = in_array('subtotal_barang', $detailColumns);

        if ($hasSubtotalColumn) {
            $stmt = $pdo->prepare("
                INSERT INTO detail_pembelian (id_pembelian, id_barang, jumlah, $priceColumn, subtotal) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$id_pembelian, $id_barang, $jumlah, $harga_beli, $subtotal]);
        } else if ($hasSubtotalBarangColumn) {
            $stmt = $pdo->prepare("
                INSERT INTO detail_pembelian (id_pembelian, id_barang, jumlah, $priceColumn, subtotal_barang) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$id_pembelian, $id_barang, $jumlah, $harga_beli, $subtotal]);
        } else {
            // Jika tidak ada kolom subtotal, insert tanpa subtotal
            $stmt = $pdo->prepare("
                INSERT INTO detail_pembelian (id_pembelian, id_barang, jumlah, $priceColumn) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$id_pembelian, $id_barang, $jumlah, $harga_beli]);
        }

        // 3. Update stok barang
        $stmt = $pdo->prepare("UPDATE barang SET stok = stok + ? WHERE id_barang = ?");
        $stmt->execute([$jumlah, $id_barang]);
    }

    // Commit transaksi
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Data pembelian berhasil disimpan',
        'id_pembelian' => $id_pembelian
    ]);
} catch (Exception $e) {
    // Rollback jika terjadi error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Error saving purchase: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
