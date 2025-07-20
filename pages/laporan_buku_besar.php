<?php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Validasi akses
if (!isset($_SESSION['pengguna'])) {
    header('Location: ../../auth/login.php');
    exit();
}

if (!function_exists('formatRupiah')) {
    function formatRupiah($angka) {
        return "Rp " . number_format($angka, 0, ',', '.');
    }
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
    <title>Laporan Buku Besar - YMC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        .date-filter {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php include '../includes/sidebar.php'; ?>

    <div class="p-4 sm:ml-64">
        <div class="p-4 border-2 border-gray-200 rounded-lg">
            <div class="mb-4">
                <h1 class="text-2xl font-semibold mb-4">Laporan Buku Besar</h1>

                <!-- Filter Form -->
                <form class="flex gap-4 mb-4" id="filterForm">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700">Tanggal Awal</label>
                        <input type="date" name="tanggal_awal" value="<?= $tanggal_awal ?>"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700">Tanggal Akhir</label>
                        <input type="date" name="tanggal_akhir" value="<?= $tanggal_akhir ?>"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>
                    <div class="flex-1 flex items-end">
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                            Filter
                        </button>
                        <a href="javascript:void(0)" onclick="printReport()" class="ml-2 bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
                            Cetak
                        </a>
                    </div>
                </form>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Id Transaksi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Rekening</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Debit</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Kredit</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-b" colspan="2">Saldo</th>
                            </tr>
                            <tr>
                                <th class="px-6 py-3 border-b" colspan="5"></th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Debit</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Kredit</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($transaksis as $transaksi):
                                // Update running balance
                                $saldo_debit += $transaksi['debit'];
                                $saldo_kredit += $transaksi['kredit'];
                            ?>
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?= date('d/m/Y', strtotime($transaksi['tanggal'])) ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars($transaksi['id_transaksi']) ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars($transaksi['rekening']) ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500 text-right"><?= $transaksi['debit'] > 0 ? formatRupiah($transaksi['debit']) : '-' ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500 text-right"><?= $transaksi['kredit'] > 0 ? formatRupiah($transaksi['kredit']) : '-' ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500 text-right"><?= formatRupiah($saldo_debit) ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500 text-right"><?= formatRupiah($saldo_kredit) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const tanggalAwal = this.tanggal_awal.value;
            const tanggalAkhir = this.tanggal_akhir.value;
            window.location.href = `?tanggal_awal=${tanggalAwal}&tanggal_akhir=${tanggalAkhir}`;
        });

        function printReport() {
            const tanggalAwal = document.getElementsByName('tanggal_awal')[0].value;
            const tanggalAkhir = document.getElementsByName('tanggal_akhir')[0].value;
            window.open(`cetak/cetak_laporan_buku_besar.php?tanggal_awal=${tanggalAwal}&tanggal_akhir=${tanggalAkhir}`, '_blank');
        }
    </script>
</body>

</html>