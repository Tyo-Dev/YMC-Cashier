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

// Get transaction ID from request
$id_penjualan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id_penjualan) {
    echo json_encode([
        'success' => false,
        'message' => 'ID transaksi tidak ditemukan'
    ]);
    exit;
}

try {
    // Get transaction header
    $stmtTrx = $pdo->prepare("
        SELECT 
            p.*,
            u.nama_user
        FROM penjualan p
        JOIN pengguna u ON p.id_user = u.id_user
        WHERE p.id_penjualan = ?
    ");
    $stmtTrx->execute([$id_penjualan]);
    $transaction = $stmtTrx->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        echo json_encode([
            'success' => false,
            'message' => 'Transaksi tidak ditemukan'
        ]);
        exit;
    }

    // Examine the structure of detail_penjualan table
    try {
        $stmtColumns = $pdo->prepare("SHOW COLUMNS FROM detail_penjualan");
        $stmtColumns->execute();
        $columns = $stmtColumns->fetchAll(PDO::FETCH_COLUMN);

        // Use appropriate column name for ID - we need to identify the primary key column
        $idColumnName = 'id'; // Default assumption

        // Find id column name - usually primary key has 'id' in it
        foreach ($columns as $column) {
            if (strpos(strtolower($column), 'id_') === 0) {
                $idColumnName = $column;
                break;
            }
        }

        // Get transaction items with correct column names
        $stmtItems = $pdo->prepare("
            SELECT 
                d.*,
                b.nama_barang,
                b.harga_jual
            FROM detail_penjualan d
            JOIN barang b ON d.id_barang = b.id_barang
            WHERE d.id_penjualan = ?
        ");
        $stmtItems->execute([$id_penjualan]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // Add a custom id_detail field for frontend if needed
        foreach ($items as &$item) {
            // If id_detail doesn't exist, create it based on the primary key
            if (!isset($item['id_detail'])) {
                $item['id_detail'] = $item[$idColumnName];
            }
        }

        echo json_encode([
            'success' => true,
            'transaction' => $transaction,
            'items' => $items,
            'debug_info' => [
                'table_columns' => $columns,
                'id_column_used' => $idColumnName
            ]
        ], JSON_NUMERIC_CHECK);
    } catch (PDOException $e) {
        // This is an internal error for examining the table structure
        throw new PDOException("Error examining table structure: " . $e->getMessage());
    }
} catch (PDOException $e) {
    error_log("Database error in get_transaction_details: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
