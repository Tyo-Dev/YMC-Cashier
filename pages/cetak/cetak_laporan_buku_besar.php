<?php
session_start();
require_once '../../config/koneksi.php';
// Jika fungsi tidak tersedia di includes/functions.php, tambahkan di sini:
if (!function_exists('formatRupiah')) {
    function formatRupiah($angka)
    {
        return "Rp " . number_format($angka, 0, ',', '.');
    }
}

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
            background: #fff;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 20px;
            font-weight: bold;
            margin: 0 0 5px 0;
        }

        .header h2 {
            font-size: 16px;
            margin: 0 0 5px 0;
        }

        .header p {
            font-size: 13px;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 12px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 7px 5px;
        }

        th {
            background-color: #e0e7ff;
            text-align: center;
            font-weight: 600;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        tbody tr:nth-child(even) {
            background: #f6f8fa;
        }

        .summary {
            margin-top: 20px;
            border: 1px solid #000;
            padding: 15px;
            background: #f8f9fa;
        }

        .summary-item {
            display: inline-block;
            margin-right: 30px;
        }

        .summary-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 3px;
        }

        .summary-value {
            font-size: 14px;
            font-weight: bold;
        }

        .back-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            box-shadow: 0 4px 6px rgba(99, 102, 241, 0.25);
            transition: all 0.3s ease;
            display: none;
            z-index: 1000;
        }

        .back-button i {
            display: inline-block;
            transition: transform 0.3s ease;
        }

        .back-button:hover {
            background: #4f46e5;
            box-shadow: 0 6px 8px rgba(79, 70, 229, 0.3);
            transform: translateY(-2px);
        }

        .back-button:hover i {
            transform: translateX(-3px);
        }

        .back-button:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.2);
        }

        @media print {
            .back-button {
                display: none !important;
            }
        }

        @media screen {
            .back-button {
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }

        .total-row {
            background-color: #e0e7ff;
            font-weight: bold;
        }

        .total-row td {
            border-top: 2px solid #000;
        }

        .print-footer {
            margin-top: 20px;
            font-size: 11px;
            color: #666;
            text-align: center;
        }
    </style>
</head>

<body onload="window.print()">
    <a href="../laporan_buku_besar.php" class="back-button">
        <span>Kembali</span>
    </a>

    <div class="header">
        <h1>Laporan Buku Besar</h1>
        <h2>Toko Yumna Moslem Collection</h2>
        <p>Periode : <?= date('d/m/Y', strtotime($tanggal_awal)) ?> s.d <?= date('d/m/Y', strtotime($tanggal_akhir)) ?></p>
    </div>
    <table>
        <thead>
            <tr>
                <th rowspan="2"">Tanggal</th>
                <th rowspan=" 2">Id Transaksi</th>
                <th rowspan="2">Rekening</th>
                <th rowspan="2">Debit</th>
                <th rowspan="2">Kredit</th>
                <th colspan="2">Saldo</th>
            </tr>
            <tr>

                <th>Debit</th>
                <th>Kredit</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_debit = 0;
            $total_kredit = 0;

            if (count($transaksis) === 0):
            ?>
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px;">
                        Tidak ada data transaksi untuk periode yang dipilih
                    </td>
                </tr>
                <?php else:
                foreach ($transaksis as $transaksi):
                    $saldo_debit += $transaksi['debit'];
                    $saldo_kredit += $transaksi['kredit'];
                    $total_debit += $transaksi['debit'];
                    $total_kredit += $transaksi['kredit'];
                ?>
                    <tr>
                        <td class="text-center"><?= date('d/m/Y', strtotime($transaksi['tanggal'])) ?></td>
                        <td class="text-center"><?= htmlspecialchars($transaksi['id_transaksi']) ?></td>
                        <td class="text-center"><?= htmlspecialchars($transaksi['rekening']) ?></td>
                        <td class="text-right"><?= $transaksi['debit'] > 0 ? formatRupiah($transaksi['debit']) : '-' ?></td>
                        <td class="text-right"><?= $transaksi['kredit'] > 0 ? formatRupiah($transaksi['kredit']) : '-' ?></td>
                        <td class="text-right"><?= formatRupiah($saldo_debit) ?></td>
                        <td class="text-right"><?= formatRupiah($saldo_kredit) ?></td>
                    </tr>
            <?php
                endforeach;
            endif;
            ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" class="text-center">TOTAL</td>
                <td class="text-right"><?= formatRupiah($total_debit) ?></td>
                <td class="text-right"><?= formatRupiah($total_kredit) ?></td>
                <td class="text-right"><?= formatRupiah($saldo_debit) ?></td>
                <td class="text-right"><?= formatRupiah($saldo_kredit) ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- Summary Section -->
    <div class="summary">
        <div class="summary-item">
            <div class="summary-label">Total Debit</div>
            <div class="summary-value"><?= formatRupiah($total_debit) ?></div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Total Kredit</div>
            <div class="summary-value"><?= formatRupiah($total_kredit) ?></div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Saldo Akhir</div>
            <div class="summary-value"><?= formatRupiah(abs($total_debit - $total_kredit)) ?> (<?= $total_debit > $total_kredit ? 'Debit' : 'Kredit' ?>)</div>
        </div>
    </div>

    <div class="print-footer">
        Dicetak pada: <?= date('d/m/Y H:i:s') ?> | Toko Yumna Moslem Collection
    </div>
</body>

</html>