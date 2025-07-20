<?php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Cek apakah user sudah login
if (!isset($_SESSION['pengguna'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Mengambil parameter tanggal dari URL jika ada
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
    WHERE p.tanggal BETWEEN ? AND ?
    ORDER BY p.tanggal ASC, b.id_barang ASC
";

$stmt = $pdo->prepare($query);
$stmt->execute([$tanggal_awal, $tanggal_akhir]);
$penjualans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total penjualan
$total_penjualan = 0;
foreach ($penjualans as $penjualan) {
    $total_penjualan += $penjualan['subtotal_barang'];
}

// Format function
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
    <title>Laporan Penjualan - YMC</title>
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

<body class="bg-gray-100">
    <?php include '../includes/sidebar.php'; ?>

    <main class="p-4 sm:ml-64 mt-14">
        <!-- Filter Section -->
        <div class="bg-white p-4 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">Laporan Penjualan</h2>
            <form class="date-filter">
                <div>
                    <label for="tanggal_awal" class="block text-sm font-medium text-gray-700">Tanggal Awal</label>
                    <input type="date" id="tanggal_awal" name="tanggal_awal" value="<?= $tanggal_awal ?>"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="tanggal_akhir" class="block text-sm font-medium text-gray-700">Tanggal Akhir</label>
                    <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="<?= $tanggal_akhir ?>"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                        Filter
                    </button>
                    <a href="cetak/cetak_laporan_penjualan.php?tanggal_awal=<?= $tanggal_awal ?>&tanggal_akhir=<?= $tanggal_akhir ?>"
                        target="_blank"
                        class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 ml-2 inline-block">
                        Cetak
                    </a>
                </div>
            </form>
        </div>

        <!-- Report Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full whitespace-nowrap">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode Barang</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Barang</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Satuan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($penjualans as $penjualan): ?>
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= formatTanggal($penjualan['tanggal']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= $penjualan['kode_barang'] ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($penjualan['nama_barang']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= $penjualan['jumlah'] ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= formatRupiah($penjualan['harga_satuan']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= formatRupiah($penjualan['subtotal_barang']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50">
                        <td colspan="5" class="px-6 py-4 text-sm font-medium text-gray-900 text-center">Total Penjualan</td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900"><?= formatRupiah($total_penjualan) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </main>

    <script>
        // Script for date filter
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const tanggalAwalInput = document.getElementById('tanggal_awal');
            const tanggalAkhirInput = document.getElementById('tanggal_akhir');

            // Set max date to today
            const maxDate = today.toISOString().split('T')[0];
            tanggalAwalInput.max = maxDate;
            tanggalAkhirInput.max = maxDate;

            // Event handlers for date inputs
            tanggalAwalInput.addEventListener('change', function() {
                if (tanggalAkhirInput.value && tanggalAkhirInput.value < this.value) {
                    tanggalAkhirInput.value = this.value;
                }
                tanggalAkhirInput.min = this.value;
                updatePrintLink();
            });

            tanggalAkhirInput.addEventListener('change', function() {
                if (tanggalAwalInput.value && tanggalAwalInput.value > this.value) {
                    tanggalAwalInput.value = this.value;
                }
                tanggalAwalInput.max = this.value;
                updatePrintLink();
            });

            // Function to update the print link with current dates
            function updatePrintLink() {
                const printLink = document.querySelector('a[href*="cetak_laporan_penjualan.php"]');
                if (printLink) {
                    const tanggalAwal = tanggalAwalInput.value;
                    const tanggalAkhir = tanggalAkhirInput.value;
                    printLink.href = `cetak/cetak_laporan_penjualan.php?tanggal_awal=${tanggalAwal}&tanggal_akhir=${tanggalAkhir}`;
                }
            }

            // Initial update of print link
            updatePrintLink();
        });
    </script>
</body>

</html>