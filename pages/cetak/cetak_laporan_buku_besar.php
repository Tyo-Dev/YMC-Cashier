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

// Query untuk mengambil data transaksi buku besar
$query = "
    SELECT 
        tanggal,
        id_transaksi,
        jenis_transaksi as rekening,
        debit,
        kredit
    FROM (
        -- Penjualan
        SELECT 
            p.tanggal,
            p.id_penjualan as id_transaksi,
            'Penjualan' as jenis_transaksi,
            p.total_harga as debit,
            0 as kredit
        FROM penjualan p
        
        UNION ALL
        
        -- Pembelian
        SELECT 
            pb.tanggal,
            pb.id_pembelian as id_transaksi,
            'Pembelian' as jenis_transaksi,
            0 as debit,
            pb.total_harga_beli as kredit
        FROM pembelian pb
    ) AS transaksi
    WHERE tanggal BETWEEN ? AND ?
    ORDER BY tanggal ASC, id_transaksi ASC
";

$stmt = $pdo->prepare($query);
$stmt->execute([$tanggal_awal, $tanggal_akhir]);
$transaksis = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung saldo berjalan
$saldo_debit = 0;
$saldo_kredit = 0;

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Buku Besar - YMC</title>
    <style>
        @page {
            size: landscape;
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
        }

        th {
            background-color: #f0f0f0;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body onload="window.print()">
    <div class="header">
        <h1>Laporan Buku Besar</h1>
        <h2>Toko Yumna Moslem Collection</h2>
        <p>Periode : <?= date('d/m/Y', strtotime($tanggal_awal)) ?> s.d <?= date('d/m/Y', strtotime($tanggal_akhir)) ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Id Transaksi</th>
                <th>Rekening</th>
                <th>Debit</th>
                <th>Kredit</th>
                <th colspan="2">Saldo</th>
            </tr>
            <tr>
                <th colspan="5"></th>
                <th>Debit</th>
                <th>Kredit</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transaksis as $transaksi):
                // Update running balance
                $saldo_debit += $transaksi['debit'];
                $saldo_kredit += $transaksi['kredit'];
            ?>
                <tr>
                    <td class="text-center"><?= date('d/m/Y', strtotime($transaksi['tanggal'])) ?></td>
                    <td><?= htmlspecialchars($transaksi['id_transaksi']) ?></td>
                    <td><?= htmlspecialchars($transaksi['rekening']) ?></td>
                    <td class="text-right"><?= $transaksi['debit'] > 0 ? formatRupiah($transaksi['debit']) : '-' ?></td>
                    <td class="text-right"><?= $transaksi['kredit'] > 0 ? formatRupiah($transaksi['kredit']) : '-' ?></td>
                    <td class="text-right"><?= formatRupiah($saldo_debit) ?></td>
                    <td class="text-right"><?= formatRupiah($saldo_kredit) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>