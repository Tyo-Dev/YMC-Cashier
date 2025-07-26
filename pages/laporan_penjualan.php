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
    WHERE DATE(p.tanggal) BETWEEN ? AND ?
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .report-container {
            max-width: 1280px;
            margin: 0 auto;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .print-only {
                display: block !important;
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php include '../includes/sidebar.php'; ?>

    <main class="p-4 sm:ml-64 mt-14">
        <div class="report-container">
            <!-- Header Section -->
            <div class="mb-8 text-center">
                <h1 class="text-2xl font-bold text-gray-800 mb-2 flex items-center justify-center">
                    <span class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-3">
                        <i class="fas fa-shopping-cart"></i>
                    </span>
                    Laporan Penjualan
                </h1>
                <p class="text-gray-500">Periode: <?= formatTanggal($tanggal_awal) ?> s/d <?= formatTanggal($tanggal_akhir) ?></p>
            </div>

            <!-- Filter Section -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-6">
                <h2 class="text-lg font-semibold mb-4 text-gray-800 flex items-center">
                    <i class="fas fa-filter mr-2 text-blue-500"></i>
                    Filter Data
                </h2>
                <form id="filter-form" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="tanggal_awal" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Awal</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar-alt text-gray-400"></i>
                            </div>
                            <input type="date" id="tanggal_awal" name="tanggal_awal" value="<?= $tanggal_awal ?>"
                                class="form-input pl-10 pr-4 py-2 w-full rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                        </div>
                    </div>
                    <div>
                        <label for="tanggal_akhir" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar-alt text-gray-400"></i>
                            </div>
                            <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="<?= $tanggal_akhir ?>"
                                class="form-input pl-10 pr-4 py-2 w-full rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                        </div>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2">
                            <i class="fas fa-search"></i>
                            <span>Filter</span>
                        </button>
                        <a href="cetak/cetak_laporan_penjualan.php?tanggal_awal=<?= $tanggal_awal ?>&tanggal_akhir=<?= $tanggal_akhir ?>"
                            target="_blank"
                            class="flex-1 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2">
                            <i class="fas fa-print"></i>
                            <span>Cetak</span>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Report Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-table mr-2 text-blue-500"></i>
                        Data Penjualan
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Menampilkan data penjualan dalam periode yang dipilih</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full report-table">
                        <thead>
                            <tr class="bg-gray-50 text-left border-b border-gray-100">
                                <th class="px-6 py-3 text-xs font-medium text-gray-600 uppercase tracking-wider">Tanggal</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-600 uppercase tracking-wider">Kode</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-600 uppercase tracking-wider">Nama Barang</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-600 uppercase tracking-wider text-center">Jumlah</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-600 uppercase tracking-wider text-right">Harga Satuan</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-600 uppercase tracking-wider text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (count($penjualans) === 0): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-search text-gray-300 text-5xl mb-4"></i>
                                            <p class="font-medium">Tidak ada data penjualan untuk periode yang dipilih</p>
                                            <p class="text-sm mt-1">Silakan ubah filter tanggal untuk melihat data lainnya</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php
                                $current_date = '';
                                $subtotal_per_date = 0;

                                foreach ($penjualans as $index => $penjualan):
                                    // Check if we're starting a new date
                                    if ($current_date !== $penjualan['tanggal'] && $current_date !== '') {
                                        // Print subtotal for previous date
                                        echo "<tr>";
                                        echo "<td colspan='4' class='px-6 py-3 text-sm font-medium text-right bg-gray-50'>Subtotal untuk " . formatTanggal($current_date) . "</td>";
                                        echo "<td colspan='2' class='px-6 py-3 text-sm font-medium text-right bg-gray-50 text-blue-700'>" . formatRupiah($subtotal_per_date) . "</td>";
                                        echo "</tr>";
                                        $subtotal_per_date = 0;
                                    }

                                    $current_date = $penjualan['tanggal'];
                                    $subtotal_per_date += $penjualan['subtotal_barang'];

                                    // Add date header if it's a new date
                                    if ($index === 0 || $penjualan['tanggal'] !== $penjualans[$index - 1]['tanggal']) {
                                        echo "<tr>";
                                        echo "<td colspan='6' class='px-6 py-3 text-sm font-semibold bg-blue-50 text-blue-800'>";
                                        echo "<i class='fas fa-calendar-day mr-2'></i>";
                                        echo "Tanggal: " . formatTanggal($penjualan['tanggal']);
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-3 text-sm text-gray-500"><?= formatTanggal($penjualan['tanggal']) ?></td>
                                        <td class="px-6 py-3 text-sm font-mono text-gray-700"><?= $penjualan['kode_barang'] ?></td>
                                        <td class="px-6 py-3 text-sm text-gray-700"><?= htmlspecialchars($penjualan['nama_barang']) ?></td>
                                        <td class="px-6 py-3 text-sm text-gray-700 text-center"><?= $penjualan['jumlah'] ?></td>
                                        <td class="px-6 py-3 text-sm text-gray-700 text-right"><?= formatRupiah($penjualan['harga_satuan']) ?></td>
                                        <td class="px-6 py-3 text-sm font-medium text-gray-800 text-right"><?= formatRupiah($penjualan['subtotal_barang']) ?></td>
                                    </tr>
                                <?php
                                    // If this is the last item, print the final subtotal
                                    if ($index === count($penjualans) - 1) {
                                        echo "<tr>";
                                        echo "<td colspan='4' class='px-6 py-3 text-sm font-medium text-right bg-gray-50'>Subtotal untuk " . formatTanggal($current_date) . "</td>";
                                        echo "<td colspan='2' class='px-6 py-3 text-sm font-medium text-right bg-gray-50 text-blue-700'>" . formatRupiah($subtotal_per_date) . "</td>";
                                        echo "</tr>";
                                    }
                                endforeach;
                                ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="px-6 py-4 bg-blue-50 text-right">
                                    <span class="text-sm font-semibold text-blue-800">TOTAL PENJUALAN</span>
                                </td>
                                <td colspan="2" class="px-6 py-4 bg-blue-50 text-right">
                                    <span class="text-lg font-bold text-blue-700"><?= formatRupiah($total_penjualan) ?></span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Summary Cards -->
                <div class="p-6 border-t border-gray-100 bg-gray-50">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                            <div class="text-sm text-gray-500 mb-1">Total Penjualan</div>
                            <div class="text-xl font-semibold text-gray-800"><?= formatRupiah($total_penjualan) ?></div>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                            <div class="text-sm text-gray-500 mb-1">Jumlah Item</div>
                            <div class="text-xl font-semibold text-gray-800"><?= count($penjualans) ?></div>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                            <div class="text-sm text-gray-500 mb-1">Periode Laporan</div>
                            <div class="text-xl font-semibold text-gray-800"><?= formatTanggal($tanggal_awal) ?> - <?= formatTanggal($tanggal_akhir) ?></div>
                        </div>
                    </div>
                </div>
            </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const tanggalAwalInput = document.getElementById('tanggal_awal');
            const tanggalAkhirInput = document.getElementById('tanggal_akhir');
            const filterForm = document.getElementById('filter-form');
            const printLink = document.querySelector('a[href*="cetak_laporan_penjualan.php"]');

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

            // Form validation
            filterForm.addEventListener('submit', function(e) {
                if (!tanggalAwalInput.value || !tanggalAkhirInput.value) {
                    e.preventDefault();
                    alert('Silakan pilih periode tanggal terlebih dahulu');
                    return;
                }

                const startDate = new Date(tanggalAwalInput.value);
                const endDate = new Date(tanggalAkhirInput.value);
                const diffTime = Math.abs(endDate - startDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                if (diffDays > 31) {
                    const proceed = confirm('Periode yang dipilih lebih dari 31 hari. Lanjutkan?');
                    if (!proceed) {
                        e.preventDefault();
                    }
                }
            });

            // Function to update the print link with current dates
            function updatePrintLink() {
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

    <style>
        /* Custom styles for this report */
        @media print {
            body {
                background-color: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .container {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .report-header {
                margin-bottom: 20px !important;
            }

            .filter-section,
            button:not(.print-button),
            .no-print {
                display: none !important;
            }

            .report-table {
                border-collapse: collapse;
                width: 100%;
            }

            .report-table th,
            .report-table td {
                border: 1px solid #ddd !important;
            }

            .shadow-sm,
            .shadow,
            .shadow-md {
                box-shadow: none !important;
            }

            .border {
                border: 1px solid #ddd !important;
            }

            .rounded-lg,
            .rounded-xl {
                border-radius: 0 !important;
            }

            @page {
                size: A4 landscape;
                margin: 1cm;
            }
        }

        /* Additional responsive styles for smaller screens */
        @media (max-width: 768px) {
            .filter-form {
                flex-direction: column;
            }

            .filter-form>div {
                width: 100%;
                margin-bottom: 1rem;
            }

            .grid {
                grid-template-columns: 1fr !important;
            }
        }

        /* Animation for hover states */
        .hover\:bg-gray-50 {
            transition: background-color 0.2s ease-in-out;
        }

        /* Custom scrollbar for table container */
        .overflow-x-auto::-webkit-scrollbar {
            height: 8px;
        }

        .overflow-x-auto::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .overflow-x-auto::-webkit-scrollbar-thumb {
            background: #ddd;
            border-radius: 4px;
        }

        .overflow-x-auto::-webkit-scrollbar-thumb:hover {
            background: #cdcdcd;
        }
    </style>
</body>

</html>