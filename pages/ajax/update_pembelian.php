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

// Ambil data dari request
$data = json_decode(file_get_contents('php://input'), true);

// Validasi data
if (empty($data['id_pembelian']) || empty($data['tanggal']) || empty($data['id_pemasok'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Data tidak lengkap'
    ]);
    exit;
}

$id_pembelian = (int)$data['id_pembelian'];
$tanggal = $data['tanggal'];
$id_pemasok = (int)$data['id_pemasok'];
$updatedItems = $data['updated_items'] ?? [];
$removedItems = $data['removed_items'] ?? [];

try {
    // Log data untuk debugging
    error_log('Update Data: ' . json_encode($data));
    error_log('Updated Items: ' . json_encode($updatedItems));
    error_log('Removed Items: ' . json_encode($removedItems));

    // Mulai transaksi
    $pdo->beginTransaction();

    // 1. Update header pembelian
    $stmt = $pdo->prepare("UPDATE pembelian SET tanggal = ?, id_pemasok = ? WHERE id_pembelian = ?");
    $stmt->execute([$tanggal, $id_pemasok, $id_pembelian]);

    // 2. Update items yang diubah
    foreach ($updatedItems as $item) {
        $id_detail = (int)$item['id_detail_pembelian'];
        $jumlah = (int)$item['jumlah'];

        // Get current quantity
        $stmt = $pdo->prepare("SELECT jumlah, id_barang FROM detail_pembelian WHERE id_detail_pembelian = ?");
        $stmt->execute([$id_detail]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($current) {
            $qtyDiff = $jumlah - $current['jumlah'];

            // Only update if quantity changed
            if ($qtyDiff != 0) {
                // Update stock
                $stmt = $pdo->prepare("UPDATE barang SET stok = stok + ? WHERE id_barang = ?");
                $stmt->execute([$qtyDiff, $current['id_barang']]);

                // Get harga_beli for the item to calculate new subtotal
                $stmt = $pdo->prepare("SELECT harga_beli FROM detail_pembelian WHERE id_detail_pembelian = ?");
                $stmt->execute([$id_detail]);
                $priceInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                $harga_beli = $priceInfo['harga_beli'];
                $subtotal = $jumlah * $harga_beli;

                // Update detail_pembelian
                $stmt = $pdo->prepare("
                    UPDATE detail_pembelian 
                    SET jumlah = ?, subtotal = ? 
                    WHERE id_detail_pembelian = ?
                ");
                $stmt->execute([$jumlah, $subtotal, $id_detail]);
            }
        }
    }

    // 3. Hapus items yang dihapus
    foreach ($removedItems as $id_detail) {
        // Get item details before removal
        $stmt = $pdo->prepare("SELECT jumlah, id_barang FROM detail_pembelian WHERE id_detail_pembelian = ?");
        $stmt->execute([$id_detail]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($item) {
            // Update stock (remove the quantity)
            $stmt = $pdo->prepare("UPDATE barang SET stok = stok - ? WHERE id_barang = ?");
            $stmt->execute([$item['jumlah'], $item['id_barang']]);

            // Delete detail
            $stmt = $pdo->prepare("DELETE FROM detail_pembelian WHERE id_detail_pembelian = ?");
            $stmt->execute([$id_detail]);
        }
    }

    // 4. Update total_harga_beli
    $stmt = $pdo->prepare("
        UPDATE pembelian p
        SET total_harga_beli = (
            SELECT COALESCE(SUM(subtotal), 0)
            FROM detail_pembelian
            WHERE id_pembelian = p.id_pembelian
        )
        WHERE id_pembelian = ?
    ");
    $stmt->execute([$id_pembelian]);

    // Commit transaksi
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Data pembelian berhasil diperbarui'
    ]);
} catch (Exception $e) {
    // Rollback jika terjadi error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Error updating purchase: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
