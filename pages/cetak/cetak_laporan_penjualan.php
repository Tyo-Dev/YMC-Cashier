<?php
require_once '../../config/koneksi.php';
require_once '../../includes/functions.php';

// Validasi akses
if (!isset($_SESSION['pengguna'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Get parameters
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');

// Query untuk mengambil data penjualan dengan detail barang
$query = "
    SELECT 
        p.tanggal,
        b.id_barang as kode_barang,
        b.nama_barang,
        dp.jumlah,
        dp.harga_satuan,
        dp.subtotal_barang
    FROM penjualan p
    JOIN detail_penjualan dp ON p.id_penjualan = dp.id_penjualan
    JOIN barang b ON dp.id_barang = b.id_barang
    WHERE DATE(p.tanggal) BETWEEN ? AND ?
    ORDER BY p.tanggal ASC, b.id_barang ASC
";

$stmt = $pdo->prepare($query);
$stmt->execute([$tanggal_awal, $tanggal_akhir]);
$penjualans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total penjualan dan subtotal per tanggal
$total_penjualan = 0;
$subtotals_per_date = [];

foreach ($penjualans as $penjualan) {
    $tanggal = $penjualan['tanggal'];
    if (!isset($subtotals_per_date[$tanggal])) {
        $subtotals_per_date[$tanggal] = 0;
    }
    $subtotals_per_date[$tanggal] += $penjualan['subtotal_barang'];
    $total_penjualan += $penjualan['subtotal_barang'];
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
    <title>Print Laporan Penjualan - YMC</title>
    <style>
        @page {
            size: A4;
            margin: 2cm;
        }

        /* Reset table styles */
        table {
            width: 100%;
            border-collapse: collapse !important;
            border: 2px solid black !important;
            margin-top: 20px;
            font-size: 12px;
        }

        /* Strengthen all borders */
        th,
        td {
            border: 1px solid black !important;
            padding: 8px;
            text-align: left;
            position: relative !important;
        }

        /* Header styling */
        th {
            background-color: #f0f0f0 !important;
            font-weight: bold;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Total row styling */
        .total-row td {
            font-weight: bold;
            border-top: 3px double black !important;
        }

        /* Text alignment */
        .text-right {
            text-align: right !important;
        }

        .currency {
            text-align: right !important;
        }

        /* Print-specific styles */
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            table {
                page-break-inside: auto !important;
            }

            tr {
                page-break-inside: avoid !important;
                page-break-after: auto !important;
            }

            thead {
                display: table-header-group !important;
            }

            tfoot {
                display: table-footer-group !important;
            }
        }
    </style>
</head>

<body onload="window.print()">
    <div class="header">
        <h1>Laporan Penjualan</h1>
        <h2>Toko Yumna Moslem Collection</h2>
        <p>Periode : <?= formatTanggal($tanggal_awal) ?> s.d <?= formatTanggal($tanggal_akhir) ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal Penj</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Jumlah Barang</th>
                <th>Harga Satuan</th>
                <th>Subtotal</th>
                <th>Total Harga</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $current_tanggal = '';
            foreach ($penjualans as $penjualan):

                $current_tanggal = $penjualan['tanggal'];
            ?>
                <tr>
                    <td><?= formatTanggal($penjualan['tanggal']) ?></td>
                    <td><?= $penjualan['kode_barang'] ?></td>
                    <td><?= htmlspecialchars($penjualan['nama_barang']) ?></td>
                    <td class="text-right"><?= $penjualan['jumlah'] ?></td>
                    <td class="currency"><?= formatRupiah($penjualan['harga_satuan']) ?></td>
                    <td class="currency"><?= formatRupiah($penjualan['subtotal_barang']) ?></td>
                    <td class="currency"><?= formatRupiah($penjualan['subtotal_barang']) ?></td>
                </tr>
            <?php endforeach;
            // Print last subtotal
            if (!empty($penjualans)): ?>

            <?php endif; ?>
            <tr class="total-row">
                <td colspan="6" class="text-center">Total Penjualan</td>
                <td class="currency"><?= formatRupiah($total_penjualan) ?></td>
            </tr>
        </tbody>
    </table>
</body>

</html>