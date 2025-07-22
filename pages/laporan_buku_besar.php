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
    function formatRupiah($angka)
    {
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
                    <span class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center mr-3">
                        <i class="fas fa-book"></i>
                    </span>
                    Laporan Buku Besar
                </h1>
                <p class="text-gray-500">Periode: <?= date('d/m/Y', strtotime($tanggal_awal)) ?> s/d <?= date('d/m/Y', strtotime($tanggal_akhir)) ?></p>
            </div>

            <!-- Filter Section -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-6">
                <h2 class="text-lg font-semibold mb-4 text-gray-800 flex items-center">
                    <i class="fas fa-filter mr-2 text-purple-500"></i>
                    Filter Data
                </h2>
                <form id="filterForm" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="tanggal_awal" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Awal</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar-alt text-gray-400"></i>
                            </div>
                            <input type="date" name="tanggal_awal" id="tanggal_awal" value="<?= $tanggal_awal ?>"
                                class="form-input pl-10 pr-4 py-2 w-full rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-transparent">
                        </div>
                    </div>
                    <div>
                        <label for="tanggal_akhir" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar-alt text-gray-400"></i>
                            </div>
                            <input type="date" name="tanggal_akhir" id="tanggal_akhir" value="<?= $tanggal_akhir ?>"
                                class="form-input pl-10 pr-4 py-2 w-full rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-transparent">
                        </div>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2">
                            <i class="fas fa-search"></i>
                            <span>Filter</span>
                        </button>
                        <button type="button" onclick="printReport()" class="flex-1 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2">
                            <i class="fas fa-print"></i>
                            <span>Cetak</span>
                        </button>
                    </div>
                </form>

                <!-- Table Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-table mr-2 text-purple-500"></i>
                            Data Transaksi Buku Besar
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">Menampilkan data transaksi buku besar dalam periode yang dipilih</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full report-table">
                            <thead>
                                <tr class="bg-gray-50 text-left border-b border-gray-100">
                                    <th class="px-6 py-3 text-xs font-medium text-gray-600 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-6 py-3 text-xs font-medium text-gray-600 uppercase tracking-wider">Id Transaksi</th>
                                    <th class="px-6 py-3 text-xs font-medium text-gray-600 uppercase tracking-wider">Rekening</th>
                                    <th class="px-6 py-3 text-xs font-medium text-gray-600 uppercase tracking-wider text-right">Debit</th>
                                    <th class="px-6 py-3 text-xs font-medium text-gray-600 uppercase tracking-wider text-right">Kredit</th>
                                    <th class="px-6 py-3 text-xs font-medium text-gray-600 uppercase tracking-wider text-center" colspan="2">Saldo</th>
                                </tr>
                                <tr class="bg-gray-50 border-b border-gray-100">
                                    <th class="px-6 py-3" colspan="5"></th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">Debit</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">Kredit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (count($transaksis) === 0): ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-search text-gray-300 text-5xl mb-4"></i>
                                                <p class="font-medium">Tidak ada data transaksi untuk periode yang dipilih</p>
                                                <p class="text-sm mt-1">Silakan ubah filter tanggal untuk melihat data lainnya</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($transaksis as $transaksi):
                                        // Update running balance
                                        $saldo_debit += $transaksi['debit'];
                                        $saldo_kredit += $transaksi['kredit'];
                                    ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-3 text-sm text-gray-500"><?= date('d/m/Y', strtotime($transaksi['tanggal'])) ?></td>
                                            <td class="px-6 py-3 text-sm font-mono text-gray-700"><?= htmlspecialchars($transaksi['id_transaksi']) ?></td>
                                            <td class="px-6 py-3 text-sm text-gray-700">
                                                <span class="inline-flex items-center">
                                                    <i class="fas <?= $transaksi['rekening'] === 'Penjualan' ? 'fa-shopping-cart text-green-500' : 'fa-shopping-bag text-blue-500' ?> mr-2"></i>
                                                    <?= htmlspecialchars($transaksi['rekening']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-3 text-sm text-green-600 text-right font-medium"><?= $transaksi['debit'] > 0 ? formatRupiah($transaksi['debit']) : '-' ?></td>
                                            <td class="px-6 py-3 text-sm text-red-600 text-right font-medium"><?= $transaksi['kredit'] > 0 ? formatRupiah($transaksi['kredit']) : '-' ?></td>
                                            <td class="px-6 py-3 text-sm text-gray-900 text-right font-medium"><?= formatRupiah($saldo_debit) ?></td>
                                            <td class="px-6 py-3 text-sm text-gray-900 text-right font-medium"><?= formatRupiah($saldo_kredit) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="bg-purple-50">
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-sm font-semibold text-purple-800">TOTAL SALDO</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-green-600 text-right"><?= formatRupiah($saldo_debit) ?></td>
                                    <td class="px-6 py-4 text-sm font-semibold text-red-600 text-right"><?= formatRupiah($saldo_kredit) ?></td>
                                    <td colspan="2" class="px-6 py-4 text-right">
                                        <span class="text-sm font-bold text-purple-700"><?= formatRupiah(abs($saldo_debit - $saldo_kredit)) ?></span>
                                        <span class="text-xs text-purple-600 ml-1">(<?= $saldo_debit > $saldo_kredit ? 'Debit' : 'Kredit' ?>)</span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Summary Cards -->
                    <div class="p-6 border-t border-gray-100 bg-gray-50">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                                <div class="text-sm text-gray-500 mb-1">Total Debit</div>
                                <div class="text-xl font-semibold text-green-600"><?= formatRupiah($saldo_debit) ?></div>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                                <div class="text-sm text-gray-500 mb-1">Total Kredit</div>
                                <div class="text-xl font-semibold text-red-600"><?= formatRupiah($saldo_kredit) ?></div>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                                <div class="text-sm text-gray-500 mb-1">Saldo Akhir</div>
                                <div class="text-xl font-semibold text-purple-600"><?= formatRupiah(abs($saldo_debit - $saldo_kredit)) ?></div>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                                <div class="text-sm text-gray-500 mb-1">Periode Laporan</div>
                                <div class="text-xl font-semibold text-gray-800"><?= date('d/m/Y', strtotime($tanggal_awal)) ?> - <?= date('d/m/Y', strtotime($tanggal_akhir)) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterForm = document.getElementById('filterForm');
            const tanggalAwalInput = document.getElementById('tanggal_awal');
            const tanggalAkhirInput = document.getElementById('tanggal_akhir');

            // Set max date to today
            const today = new Date();
            const maxDate = today.toISOString().split('T')[0];
            tanggalAwalInput.max = maxDate;
            tanggalAkhirInput.max = maxDate;

            // Event handlers for date inputs
            tanggalAwalInput.addEventListener('change', function() {
                if (tanggalAkhirInput.value && tanggalAkhirInput.value < this.value) {
                    tanggalAkhirInput.value = this.value;
                }
                tanggalAkhirInput.min = this.value;
            });

            tanggalAkhirInput.addEventListener('change', function() {
                if (tanggalAwalInput.value && tanggalAwalInput.value > this.value) {
                    tanggalAwalInput.value = this.value;
                }
                tanggalAwalInput.max = this.value;
            });

            // Form validation
            filterForm.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!tanggalAwalInput.value || !tanggalAkhirInput.value) {
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
                        return;
                    }
                }

                window.location.href = `?tanggal_awal=${tanggalAwalInput.value}&tanggal_akhir=${tanggalAkhirInput.value}`;
            });
        });

        function printReport() {
            const tanggalAwal = document.getElementById('tanggal_awal').value;
            const tanggalAkhir = document.getElementById('tanggal_akhir').value;
            window.open(`cetak/cetak_laporan_buku_besar.php?tanggal_awal=${tanggalAwal}&tanggal_akhir=${tanggalAkhir}`, '_blank');
        }
    </script>

    <style>
        /* Print styles */
        @media print {
            body {
                background-color: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .report-container {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

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
            .shadow {
                box-shadow: none !important;
            }

            .border {
                border: 1px solid #ddd !important;
            }

            .rounded-xl {
                border-radius: 0 !important;
            }

            @page {
                size: A4 landscape;
                margin: 1cm;
            }
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr !important;
            }

            .flex-1 {
                width: 100%;
            }
        }

        /* Custom scrollbar */
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

        /* Transitions */
        .hover\:bg-gray-50 {
            transition: background-color 0.2s ease-in-out;
        }

        /* Table row highlight */
        tr:hover td {
            background-color: rgba(124, 58, 237, 0.05);
        }
    </style>
</body>

</html>