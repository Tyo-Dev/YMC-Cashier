<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/koneksi.php';

// Verify user is logged in and is a kasir
if (!isset($_SESSION['pengguna']) || $_SESSION['pengguna']['level'] !== 'kasir') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id_penjualan']) || !isset($data['updated_items'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Data tidak lengkap'
    ]);
    exit;
}

$id_penjualan = (int)$data['id_penjualan'];
$updated_items = $data['updated_items'];
$removed_items = $data['removed_items'] ?? [];

try {
    // First, examine the structure of both detail_penjualan and penjualan tables
    $stmtDetailColumns = $pdo->prepare("SHOW COLUMNS FROM detail_penjualan");
    $stmtDetailColumns->execute();
    $detailColumns = $stmtDetailColumns->fetchAll(PDO::FETCH_COLUMN);

    $stmtPenjualanColumns = $pdo->prepare("SHOW COLUMNS FROM penjualan");
    $stmtPenjualanColumns->execute();
    $penjualanColumns = $stmtPenjualanColumns->fetchAll(PDO::FETCH_COLUMN);

    // Log columns for debugging
    error_log("Detail_penjualan columns: " . implode(", ", $detailColumns));
    error_log("Penjualan columns: " . implode(", ", $penjualanColumns));

    // Find actual primary key column from information schema
    $stmtPK = $pdo->prepare("
        SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'detail_penjualan'
        AND CONSTRAINT_NAME = 'PRIMARY'
    ");
    $stmtPK->execute();
    $pkColumn = $stmtPK->fetchColumn();

    // If primary key not found, try to determine based on column names
    if (!$pkColumn) {
        foreach ($detailColumns as $column) {
            if (strpos(strtolower($column), 'id_detail') !== false) {
                $pkColumn = $column;
                break;
            } else if (strpos(strtolower($column), 'id') === 0 && $column !== 'id_penjualan' && $column !== 'id_barang') {
                $pkColumn = $column;
                break;
            }
        }

        // If still not found, use id_penjualan and id_barang composite
        if (!$pkColumn) {
            $pkColumn = 'id_detail_penjualan';
        }
    }

    error_log("Using PK column: " . $pkColumn);

    // Determine which column is used for the price in detail_penjualan
    $priceColumn = 'harga_satuan'; // Default from schema
    if (in_array('harga', $detailColumns)) {
        $priceColumn = 'harga';
    }

    // Determine which column is used for the subtotal in detail_penjualan
    $subtotalColumn = 'subtotal_barang'; // Default from schema
    if (in_array('subtotal', $detailColumns)) {
        $subtotalColumn = 'subtotal';
    }

    // Determine which column is used for total price in penjualan
    $totalPriceColumn = 'total_harga_jual'; // Default from schema
    if (in_array('total_harga', $penjualanColumns)) {
        $totalPriceColumn = 'total_harga';
    }

    error_log("Using price column: " . $priceColumn);
    error_log("Using subtotal column: " . $subtotalColumn);
    error_log("Using total price column: " . $totalPriceColumn);

    // Begin transaction
    $pdo->beginTransaction();

    // Process removed items first - update stock and remove from detail
    if (!empty($removed_items)) {
        foreach ($removed_items as $item_id) {
            try {
                // Use detected primary key
                $stmt = $pdo->prepare("
                    SELECT id_barang, jumlah 
                    FROM detail_penjualan 
                    WHERE $pkColumn = ? AND id_penjualan = ?
                ");
                $stmt->execute([(int)$item_id, $id_penjualan]);

                $item = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($item) {
                    // Restore stock
                    $stmtUpdateStock = $pdo->prepare("
                        UPDATE barang 
                        SET stok = stok + ? 
                        WHERE id_barang = ?
                    ");
                    $stmtUpdateStock->execute([$item['jumlah'], $item['id_barang']]);

                    // Delete detail
                    $stmtDeleteDetail = $pdo->prepare("
                        DELETE FROM detail_penjualan 
                        WHERE $pkColumn = ? AND id_penjualan = ?
                    ");
                    $stmtDeleteDetail->execute([(int)$item_id, $id_penjualan]);
                }
            } catch (PDOException $e) {
                error_log("Error processing item removal: " . $e->getMessage());
                throw $e;
            }
        }
    }

    // Process updated items
    if (!empty($updated_items)) {
        foreach ($updated_items as $item) {
            try {
                $item_id = (int)$item['id_detail'];
                $new_qty = (int)$item['jumlah'];

                // Get current item details
                $stmt = $pdo->prepare("
                    SELECT id_barang, jumlah, $priceColumn
                    FROM detail_penjualan 
                    WHERE $pkColumn = ? AND id_penjualan = ?
                ");
                $stmt->execute([$item_id, $id_penjualan]);

                $current = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($current) {
                    $qty_diff = $current['jumlah'] - $new_qty; // Positive if reducing quantity

                    // Update stock if quantity changed
                    if ($qty_diff != 0) {
                        $stmtUpdateStock = $pdo->prepare("
                            UPDATE barang 
                            SET stok = stok + ? 
                            WHERE id_barang = ?
                        ");
                        $stmtUpdateStock->execute([$qty_diff, $current['id_barang']]);
                    }

                    // Update detail - use the correct column names for price and subtotal
                    $stmtUpdateDetail = $pdo->prepare("
                        UPDATE detail_penjualan 
                        SET jumlah = ?, $subtotalColumn = $priceColumn * ?
                        WHERE $pkColumn = ? AND id_penjualan = ?
                    ");
                    $stmtUpdateDetail->execute([$new_qty, $new_qty, $item_id, $id_penjualan]);
                }
            } catch (PDOException $e) {
                error_log("Error processing item update: " . $e->getMessage());
                throw $e;
            }
        }
    }

    // Update transaction totals - use the correct column for subtotal and total
    $stmtUpdateTotal = $pdo->prepare("
        UPDATE penjualan p
        SET $totalPriceColumn = (
            SELECT COALESCE(SUM($subtotalColumn), 0)
            FROM detail_penjualan
            WHERE id_penjualan = p.id_penjualan
        )
        WHERE id_penjualan = ?
    ");
    $stmtUpdateTotal->execute([$id_penjualan]);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Transaksi berhasil diperbarui',
        'debug_info' => [
            'pk_column' => $pkColumn,
            'price_column' => $priceColumn,
            'subtotal_column' => $subtotalColumn,
            'total_price_column' => $totalPriceColumn,
            'detail_columns' => $detailColumns,
            'penjualan_columns' => $penjualanColumns
        ]
    ]);
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database error in update_transaction: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
