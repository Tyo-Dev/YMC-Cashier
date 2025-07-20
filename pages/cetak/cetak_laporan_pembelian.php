<?php
session_start();
require_once '../../config/koneksi.php';
require_once '../../includes/functions.php';

// Validasi akses
if (!isset($_SESSION['pengguna'])) {
    header('Location: ../../auth/login.php');
    exit();
}

// Get parameters
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');

// Query untuk mengambil data pembelian
$query = "
    SELECT 
        p.tanggal,
        b.id_barang as kode_barang,
        b.nama_barang,
        dp.jumlah,
        dp.harga_beli as harga_satuan,
        dp.subtotal as total_harga,
        ps.nama_pemasok
    FROM pembelian p
    JOIN detail_pembelian dp ON p.id_pembelian = dp.id_pembelian
    JOIN barang b ON dp.id_barang = b.id_barang
    JOIN pemasok ps ON p.id_pemasok = ps.id_pemasok
    WHERE p.tanggal BETWEEN ? AND ?
    ORDER BY p.tanggal ASC, b.id_barang ASC
";

$stmt = $pdo->prepare($query);
$stmt->execute([$tanggal_awal, $tanggal_akhir]);
$pembelians = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_pembelian = 0;
$subtotals_per_date = [];

foreach ($pembelians as $pembelian) {
    $tanggal = $pembelian['tanggal'];
    if (!isset($subtotals_per_date[$tanggal])) {
        $subtotals_per_date[$tanggal] = 0;
    }
    $subtotals_per_date[$tanggal] += $pembelian['total_harga'];
    $total_pembelian += $pembelian['total_harga'];
}

function formatTanggal($tanggal)
{
    return date('d/m/Y', strtotime($tanggal));
}

function formatRupiah($angka)
{
    return "Rp " . number_format($angka, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Laporan Pembelian - YMC</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1cm;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.4;
            margin: 0;
            padding: 1cm;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 5px 0;
        }

        .header h2 {
            font-size: 16px;
            margin: 0 0 5px 0;
        }

        .header p {
            font-size: 12px;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 11px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .subtotal-row {
            background-color: #f9f9f9;
        }

        .total-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }

        .currency {
            text-align: right;
        }
    </style>
</head>

<body onload="window.print()">
    <div class="header">
        <h1>Laporan Pembelian</h1>
        <h2>Toko Yumna Moslem Collection</h2>
        <p>Periode : <?= formatTanggal($tanggal_awal) ?> s.d <?= formatTanggal($tanggal_akhir) ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal Beli</th>
                <th>Kode Barang</th>
                <th>Pemasok</th>
                <th>Nama Barang</th>
                <th>Jumlah Barang</th>
                <th>Harga Satuan</th>
                <th>Total Harga</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $current_date = '';
            foreach ($pembelians as $index => $pembelian):

                $current_date = $pembelian['tanggal'];
                ?>
                <tr>
                    <td><?= formatTanggal($pembelian['tanggal']) ?></td>
                    <td><?= $pembelian['kode_barang'] ?></td>
                    <td><?= htmlspecialchars($pembelian['nama_pemasok']) ?></td>
                    <td><?= htmlspecialchars($pembelian['nama_barang']) ?></td>
                    <td class="text-right"><?= $pembelian['jumlah'] ?></td>
                    <td class="currency"><?= formatRupiah($pembelian['harga_satuan']) ?></td>
                    <td class="currency"><?= formatRupiah($pembelian['total_harga']) ?></td>
                </tr>
                <?php
                // Print last subtotal if this is the last row
               
            endforeach; ?>
            <tr class="total-row">
                <td colspan="6" class="text-right">Total Pembelian</td>
                <td class="currency"><?= formatRupiah($total_pembelian) ?></td>
            </tr>
        </tbody>
    </table>
</body>

</html>