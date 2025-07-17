<?php
session_start();
require_once '../config/koneksi.php';

// Redirect jika belum login
if (!isset($_SESSION['pengguna'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Ambil data pengguna dari session
$pengguna = $_SESSION['pengguna'];
$user_level = $pengguna['level'];
$nama_user = htmlspecialchars($pengguna['nama_user']);
$id_user = $pengguna['id_user'];

// Fungsi untuk format rupiah
function formatRupiah($angka)
{
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Ambil data real dari database berdasarkan level user
$data = [];

// Tanggal hari ini
$today = date('Y-m-d');
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');

try {
    // Data untuk Admin
    if ($user_level == 'admin' || $user_level == 'pemilik') {
        // Total penjualan hari ini
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total_harga), 0) as total 
            FROM penjualan 
            WHERE DATE(tanggal) = ?
        ");
        $stmt->execute([$today]);
        $result = $stmt->fetch();
        $data['penjualan_hari_ini'] = formatRupiah($result['total']);

        // Jumlah transaksi hari ini
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM penjualan 
            WHERE DATE(tanggal) = ?
        ");
        $stmt->execute([$today]);
        $result = $stmt->fetch();
        $data['transaksi_hari_ini'] = $result['total'];

        // Stok barang kritis (kurang dari 10)
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM barang WHERE stok < 10");
        $result = $stmt->fetch();
        $data['stok_kritis'] = $result['total'];

        // 5 Aktivitas terbaru
        $stmt = $pdo->prepare("
            SELECT p.no_transaksi, p.total_harga, p.tanggal, u.nama_user 
            FROM penjualan p 
            JOIN pengguna u ON p.id_user = u.id_user
            ORDER BY p.tanggal DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $data['aktivitas_terbaru'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Data khusus Kasir
    if ($user_level == 'kasir') {
        // Total penjualan kasir hari ini
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total_harga), 0) as total 
            FROM penjualan 
            WHERE DATE(tanggal) = ? AND id_user = ?
        ");
        $stmt->execute([$today, $id_user]);
        $result = $stmt->fetch();
        $data['penjualan_anda_hari_ini'] = formatRupiah($result['total']);

        // Jumlah transaksi kasir hari ini
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM penjualan 
            WHERE DATE(tanggal) = ? AND id_user = ?
        ");
        $stmt->execute([$today, $id_user]);
        $result = $stmt->fetch();
        $data['transaksi_anda_hari_ini'] = $result['total'];

        // 5 Transaksi terbaru kasir
        $stmt = $pdo->prepare("
            SELECT no_transaksi, total_harga, tanggal
            FROM penjualan 
            WHERE id_user = ?
            ORDER BY tanggal DESC 
            LIMIT 5
        ");
        $stmt->execute([$id_user]);
        $data['transaksi_terbaru'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Data khusus Pemilik
    if ($user_level == 'pemilik') {
        // Total penjualan bulan ini
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total_harga), 0) as total 
            FROM penjualan 
            WHERE tanggal BETWEEN ? AND ?
        ");
        $stmt->execute([$month_start, $month_end]);
        $result = $stmt->fetch();
        $data['pendapatan_bulan_ini'] = formatRupiah($result['total']);

        // Perkiraan laba kotor (asumsi 30% dari total penjualan)
        $data['laba_kotor'] = formatRupiah($result['total'] * 0.3);

        // Barang terlaris
        $stmt = $pdo->prepare("
            SELECT b.nama_barang, SUM(dp.jumlah) as total_terjual
            FROM detail_penjualan dp
            JOIN barang b ON dp.id_barang = b.id_barang
            JOIN penjualan p ON dp.id_penjualan = p.id_penjualan
            WHERE p.tanggal BETWEEN ? AND ?
            GROUP BY b.id_barang
            ORDER BY total_terjual DESC
            LIMIT 1
        ");
        $stmt->execute([$month_start, $month_end]);
        $result = $stmt->fetch();
        $data['barang_terlaris'] = $result ? $result['nama_barang'] : 'Tidak ada data';

        // Data penjualan 7 hari terakhir untuk grafik
        $stmt = $pdo->query("
            SELECT 
                DATE(tanggal) as tanggal,
                SUM(total_harga) as total
            FROM penjualan
            WHERE tanggal >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
            GROUP BY DATE(tanggal)
            ORDER BY tanggal
        ");
        $sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $chart_labels = [];
        $chart_values = [];
        $chart_percentages = [];

        // Inisialisasi array untuk 7 hari terakhir
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $chart_labels[] = date('d/m', strtotime($date));
            $chart_values[$date] = 0;
        }

        // Isi dengan data yang ada
        foreach ($sales_data as $sale) {
            if (isset($chart_values[$sale['tanggal']])) {
                $chart_values[$sale['tanggal']] = (int)$sale['total'];
            }
        }

        // Hitung persentase untuk chart (max 100%)
        $max_value = max(array_values($chart_values)) ?: 1;
        foreach ($chart_values as $value) {
            $chart_percentages[] = ($value / $max_value) * 100;
        }

        $data['chart_labels'] = $chart_labels;
        $data['chart_values'] = array_values($chart_values);
        $data['chart_percentages'] = $chart_percentages;
    }
} catch (PDOException $e) {
    // Handle error
    error_log("Error pada dashboard: " . $e->getMessage());
    // Set default values if database query fails
    if ($user_level == 'admin') {
        $data = [
            'penjualan_hari_ini' => 'Rp 0',
            'transaksi_hari_ini' => 0,
            'stok_kritis' => 0,
            'aktivitas_terbaru' => []
        ];
    } elseif ($user_level == 'kasir') {
        $data = [
            'penjualan_anda_hari_ini' => 'Rp 0',
            'transaksi_anda_hari_ini' => 0,
            'transaksi_terbaru' => []
        ];
    } elseif ($user_level == 'pemilik') {
        $data = [
            'pendapatan_bulan_ini' => 'Rp 0',
            'laba_kotor' => 'Rp 0',
            'barang_terlaris' => 'Tidak ada data',
            'chart_labels' => [],
            'chart_values' => [],
            'chart_percentages' => [0, 0, 0, 0, 0, 0, 0]
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - YMC Cashier</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }

        /* Smooth transitions */
        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
        }

        /* Pulsing animation for low stock alert */
        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }

        /* Animated bars for chart */
        .animate-bar {
            animation: growUp 1s ease-out forwards;
            transform-origin: bottom;
        }

        @keyframes growUp {
            from {
                transform: scaleY(0);
            }

            to {
                transform: scaleY(1);
            }
        }

        /* Responsive adjustments */
        @media (max-width: 640px) {
            .stats-card {
                padding: 1rem;
            }

            .stats-icon {
                padding: 0.75rem;
            }

            .stats-value {
                font-size: 1.25rem;
            }
        }
    </style>
</head>

<body>
    <div class="flex min-h-screen bg-gray-50">
        <?php include '../includes/sidebar.php'; ?>

        <main class="flex-1 p-4 sm:p-6 md:p-8 max-w-7xl mx-auto">
            <div class="max-w-7xl mx-auto">
                <header class="mb-8 text-center sm:text-left">
                    <h1 class="text-3xl sm:text-4xl font-bold text-gray-800">Selamat Datang, <?= $nama_user ?>!</h1>
                    <p class="text-gray-500 mt-2">Dashboard <?= ucfirst($user_level) ?> - <?= date('d F Y') ?></p>
                </header>

                <?php if ($user_level == 'admin'): ?>
                    <div class="space-y-8">
                        <!-- Admin Stats Cards -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                            <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4 card-hover stats-card">
                                <div class="bg-blue-100 text-blue-600 p-3 rounded-full stats-icon">
                                    <i class="fas fa-dollar-sign fa-lg"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Penjualan Hari Ini</p>
                                    <p class="text-xl sm:text-2xl font-bold text-gray-800 stats-value"><?= $data['penjualan_hari_ini'] ?></p>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4 card-hover stats-card">
                                <div class="bg-green-100 text-green-600 p-3 rounded-full stats-icon">
                                    <i class="fas fa-exchange-alt fa-lg"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Transaksi Hari Ini</p>
                                    <p class="text-xl sm:text-2xl font-bold text-gray-800 stats-value"><?= $data['transaksi_hari_ini'] ?></p>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4 card-hover <?= $data['stok_kritis'] > 0 ? 'pulse' : '' ?> stats-card">
                                <div class="bg-red-100 text-red-600 p-3 rounded-full stats-icon">
                                    <i class="fas fa-exclamation-triangle fa-lg"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Stok Barang Kritis</p>
                                    <p class="text-xl sm:text-2xl font-bold text-gray-800 stats-value"><?= $data['stok_kritis'] ?> Item</p>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Access Buttons -->
                        <div>
                            <h2 class="text-xl sm:text-2xl font-semibold text-gray-800 mb-4 px-1">Akses Cepat</h2>
                            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                                <a href="manage_barang.php" class="bg-white rounded-xl shadow-sm p-5 text-center transition-all duration-300 hover:shadow-md hover:-translate-y-1">
                                    <i class="fas fa-box-open fa-2x text-blue-500 mb-3"></i>
                                    <p class="font-medium text-gray-700">Kelola Barang</p>
                                </a>

                                <a href="list_transaksi_penjualan.php" class="bg-white rounded-xl shadow-sm p-5 text-center transition-all duration-300 hover:shadow-md hover:-translate-y-1">
                                    <i class="fas fa-chart-line fa-2x text-green-500 mb-3"></i>
                                    <p class="font-medium text-gray-700">Daftar Transaksi</p>
                                </a>

                                <a href="transaksi_penjualan.php" class="bg-white rounded-xl shadow-sm p-5 text-center transition-all duration-300 hover:shadow-md hover:-translate-y-1">
                                    <i class="fas fa-cash-register fa-2x text-purple-500 mb-3"></i>
                                    <p class="font-medium text-gray-700">Halaman Kasir</p>
                                </a>

                                <a href="manage_user.php" class="bg-white rounded-xl shadow-sm p-5 text-center transition-all duration-300 hover:shadow-md hover:-translate-y-1">
                                    <i class="fas fa-users-cog fa-2x text-orange-500 mb-3"></i>
                                    <p class="font-medium text-gray-700">Kelola Pengguna</p>
                                </a>
                            </div>
                        </div>

                        <!-- Recent Activities -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h2 class="text-xl sm:text-2xl font-semibold text-gray-800 mb-4">Aktivitas Terbaru</h2>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="border-b">
                                            <th class="pb-3 font-medium text-gray-600">No Transaksi</th>
                                            <th class="pb-3 font-medium text-gray-600">Kasir</th>
                                            <th class="pb-3 font-medium text-gray-600">Waktu</th>
                                            <th class="pb-3 font-medium text-gray-600">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($data['aktivitas_terbaru'])): ?>
                                            <tr>
                                                <td colspan="4" class="py-4 text-center text-gray-500">Belum ada aktivitas terbaru</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($data['aktivitas_terbaru'] as $aktivitas): ?>
                                                <tr class="border-b hover:bg-gray-50">
                                                    <td class="py-3 font-medium text-blue-600"><?= htmlspecialchars($aktivitas['no_transaksi']) ?></td>
                                                    <td class="py-3"><?= htmlspecialchars($aktivitas['nama_user']) ?></td>
                                                    <td class="py-3"><?= date('d/m/Y H:i', strtotime($aktivitas['tanggal'])) ?></td>
                                                    <td class="py-3 font-medium"><?= formatRupiah($aktivitas['total_harga']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4 text-center">
                                <a href="list_transaksi_penjualan.php" class="text-blue-500 hover:text-blue-700 font-medium">Lihat Semua Transaksi →</a>
                            </div>
                        </div>
                    </div>

                <?php elseif ($user_level == 'kasir'): ?>
                    <div class="space-y-8">
                        <!-- Kasir Stats Cards -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4 card-hover stats-card">
                                <div class="bg-green-100 text-green-600 p-3 rounded-full stats-icon">
                                    <i class="fas fa-wallet fa-lg"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Penjualan Anda Hari Ini</p>
                                    <p class="text-xl sm:text-2xl font-bold text-gray-800 stats-value"><?= $data['penjualan_anda_hari_ini'] ?></p>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4 card-hover stats-card">
                                <div class="bg-blue-100 text-blue-600 p-3 rounded-full stats-icon">
                                    <i class="fas fa-receipt fa-lg"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Transaksi Anda Hari Ini</p>
                                    <p class="text-xl sm:text-2xl font-bold text-gray-800 stats-value"><?= $data['transaksi_anda_hari_ini'] ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Big POS Button -->
                        <a href="./transaksi_penjualan.php" class="block bg-gradient-to-br from-blue-600 to-green-500 text-white rounded-xl shadow-lg p-8 text-center hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                                <div class="text-5xl mb-4 sm:mb-0">
                                    <i class="fas fa-cash-register"></i>
                                </div>
                                <div>
                                    <h2 class="text-2xl sm:text-3xl font-bold mb-2">Mulai Transaksi Baru</h2>
                                    <p class="opacity-90 text-sm sm:text-base">Klik di sini untuk membuka halaman kasir</p>
                                </div>
                            </div>
                        </a>

                        <!-- Quick Access Buttons -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <a href="list_barang_penjualan.php" class="bg-white rounded-xl shadow-sm p-5 text-center transition-all duration-300 hover:shadow-md hover:-translate-y-1">
                                <i class="fas fa-boxes fa-2x text-blue-500 mb-3"></i>
                                <p class="font-medium text-gray-700">Daftar Barang</p>
                            </a>

                            <a href="list_transaksi_penjualan.php" class="bg-white rounded-xl shadow-sm p-5 text-center transition-all duration-300 hover:shadow-md hover:-translate-y-1">
                                <i class="fas fa-list-alt fa-2x text-green-500 mb-3"></i>
                                <p class="font-medium text-gray-700">Transaksi Saya</p>
                            </a>
                        </div>

                        <!-- Recent Transactions -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h2 class="text-xl sm:text-2xl font-semibold text-gray-800 mb-4">Transaksi Terbaru Anda</h2>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="border-b">
                                            <th class="pb-3 font-medium text-gray-600">No Transaksi</th>
                                            <th class="pb-3 font-medium text-gray-600">Tanggal</th>
                                            <th class="pb-3 font-medium text-gray-600">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($data['transaksi_terbaru'])): ?>
                                            <tr>
                                                <td colspan="3" class="py-4 text-center text-gray-500">Belum ada transaksi terbaru</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($data['transaksi_terbaru'] as $trx): ?>
                                                <tr class="border-b hover:bg-gray-50">
                                                    <td class="py-3 font-medium text-blue-600"><?= htmlspecialchars($trx['no_transaksi']) ?></td>
                                                    <td class="py-3"><?= date('d/m/Y H:i', strtotime($trx['tanggal'])) ?></td>
                                                    <td class="py-3 font-medium"><?= formatRupiah($trx['total_harga']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4 text-center">
                                <a href="list_transaksi_penjualan.php" class="text-blue-500 hover:text-blue-700 font-medium">Lihat Semua Transaksi →</a>
                            </div>
                        </div>
                    </div>

                <?php elseif ($user_level == 'pemilik'): ?>
                    <div class="space-y-8">
                        <!-- Pemilik Stats Cards -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                            <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4 card-hover stats-card">
                                <div class="bg-green-100 text-green-600 p-3 rounded-full stats-icon">
                                    <i class="fas fa-calendar-alt fa-lg"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Pendapatan Bulan Ini</p>
                                    <p class="text-xl sm:text-2xl font-bold text-gray-800 stats-value"><?= $data['pendapatan_bulan_ini'] ?></p>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4 card-hover stats-card">
                                <div class="bg-blue-100 text-blue-600 p-3 rounded-full stats-icon">
                                    <i class="fas fa-chart-pie fa-lg"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Perkiraan Laba Kotor</p>
                                    <p class="text-xl sm:text-2xl font-bold text-gray-800 stats-value"><?= $data['laba_kotor'] ?></p>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4 card-hover stats-card">
                                <div class="bg-purple-100 text-purple-600 p-3 rounded-full stats-icon">
                                    <i class="fas fa-star fa-lg"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Barang Terlaris</p>
                                    <p class="text-xl sm:text-2xl font-bold text-gray-800 stats-value"><?= $data['barang_terlaris'] ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Sales Chart -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                                <div>
                                    <h2 class="text-xl sm:text-2xl font-semibold text-gray-800">Penjualan 7 Hari Terakhir</h2>
                                    <p class="text-sm text-gray-500 mt-1">Grafik menunjukkan pendapatan harian dalam Rupiah</p>
                                </div>
                                <div class="mt-3 sm:mt-0">
                                    <a href="list_transaksi_penjualan.php" class="text-sm text-blue-500 hover:text-blue-700 font-medium">Laporan Lengkap →</a>
                                </div>
                            </div>

                            <div class="relative h-64 md:h-80">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>

                        <!-- Quick Access Buttons -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <a href="manage_barang.php" class="bg-white rounded-xl shadow-sm p-5 text-center transition-all duration-300 hover:shadow-md hover:-translate-y-1">
                                <i class="fas fa-box-open fa-2x text-blue-500 mb-3"></i>
                                <p class="font-medium text-gray-700">Kelola Barang</p>
                            </a>

                            <a href="list_transaksi_penjualan.php" class="bg-white rounded-xl shadow-sm p-5 text-center transition-all duration-300 hover:shadow-md hover:-translate-y-1">
                                <i class="fas fa-chart-line fa-2x text-green-500 mb-3"></i>
                                <p class="font-medium text-gray-700">Laporan Penjualan</p>
                            </a>

                            <a href="manage_pemasok.php" class="bg-white rounded-xl shadow-sm p-5 text-center transition-all duration-300 hover:shadow-md hover:-translate-y-1">
                                <i class="fas fa-truck fa-2x text-yellow-500 mb-3"></i>
                                <p class="font-medium text-gray-700">Kelola Supplier</p>
                            </a>

                            <a href="manage_user.php" class="bg-white rounded-xl shadow-sm p-5 text-center transition-all duration-300 hover:shadow-md hover:-translate-y-1">
                                <i class="fas fa-users fa-2x text-purple-500 mb-3"></i>
                                <p class="font-medium text-gray-700">Kelola Pengguna</p>
                            </a>
                        </div>

                        <!-- Recent Activities (same as admin) -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h2 class="text-xl sm:text-2xl font-semibold text-gray-800 mb-4">Aktivitas Terbaru</h2>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="border-b">
                                            <th class="pb-3 font-medium text-gray-600">No Transaksi</th>
                                            <th class="pb-3 font-medium text-gray-600">Kasir</th>
                                            <th class="pb-3 font-medium text-gray-600">Waktu</th>
                                            <th class="pb-3 font-medium text-gray-600">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($data['aktivitas_terbaru'])): ?>
                                            <tr>
                                                <td colspan="4" class="py-4 text-center text-gray-500">Belum ada aktivitas terbaru</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($data['aktivitas_terbaru'] as $aktivitas): ?>
                                                <tr class="border-b hover:bg-gray-50">
                                                    <td class="py-3 font-medium text-blue-600"><?= htmlspecialchars($aktivitas['no_transaksi']) ?></td>
                                                    <td class="py-3"><?= htmlspecialchars($aktivitas['nama_user']) ?></td>
                                                    <td class="py-3"><?= date('d/m/Y H:i', strtotime($aktivitas['tanggal'])) ?></td>
                                                    <td class="py-3 font-medium"><?= formatRupiah($aktivitas['total_harga']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4 text-center">
                                <a href="list_transaksi_penjualan.php" class="text-blue-500 hover:text-blue-700 font-medium">Lihat Semua Transaksi →</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php if ($user_level == 'pemilik'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('salesChart').getContext('2d');

                const salesChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($data['chart_labels'] ?? []) ?>,
                        datasets: [{
                            label: 'Penjualan',
                            data: <?= json_encode($data['chart_values'] ?? []) ?>,
                            backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1,
                            borderRadius: 5,
                            barPercentage: 0.7,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let value = context.raw;
                                        return 'Rp ' + value.toLocaleString('id-ID');
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'Rp ' + value.toLocaleString('id-ID');
                                    }
                                }
                            }
                        },
                        animation: {
                            duration: 2000,
                            easing: 'easeOutQuart'
                        }
                    }
                });

                // Add animation effect to the chart bars
                salesChart.data.datasets.forEach((dataset) => {
                    dataset.backgroundColor = dataset.data.map((value, index) => {
                        const opacity = 0.5 + (value / Math.max(...dataset.data)) * 0.5;
                        return `rgba(59, 130, 246, ${opacity})`;
                    });
                });
                salesChart.update();
            });
        </script>
    <?php endif; ?>
</body>

</html>