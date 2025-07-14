<?php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = $_SESSION['pengguna'];
$stats = getDashboardStats($pdo);

// Mendapatkan data untuk chart (penjualan 7 hari terakhir)
$stmt = $pdo->prepare("
    SELECT DATE(tanggal) as tanggal, SUM(total_harga_jual) as total 
    FROM penjualan 
    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
    GROUP BY DATE(tanggal) 
    ORDER BY tanggal
");
$stmt->execute();
$chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Produk dengan stok menipis
$stmt = $pdo->prepare("
    SELECT b.*, k.kategori_barang 
    FROM barang b 
    JOIN kategori k ON b.id_kategori = k.id_kategori 
    WHERE b.stok <= 10 
    ORDER BY b.stok ASC 
    LIMIT 5
");
$stmt->execute();
$stok_menipis = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Kasir YMC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-indigo-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Sistem Kasir YMC</h1>
            <div class="flex items-center space-x-4">
                <span>Selamat datang, <?= htmlspecialchars($user['nama_user']) ?></span>
                <span class="bg-indigo-800 px-2 py-1 rounded text-sm"><?= ucfirst($user['level']) ?></span>
                <a href="../auth/logout.php" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        <!-- Menu Navigation -->
        <div class="mb-8">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <a href="transaksi_penjualan.php" class="bg-green-500 hover:bg-green-600 text-white p-4 rounded-lg text-center">
                    <i class="fas fa-shopping-cart text-2xl mb-2"></i>
                    <p>Transaksi Penjualan</p>
                </a>
                
                <?php if ($user['level'] === 'admin'): ?>
                <a href="data_barang.php" class="bg-blue-500 hover:bg-blue-600 text-white p-4 rounded-lg text-center">
                    <i class="fas fa-box text-2xl mb-2"></i>
                    <p>Data Barang</p>
                </a>
                
                <a href="data_kategori.php" class="bg-purple-500 hover:bg-purple-600 text-white p-4 rounded-lg text-center">
                    <i class="fas fa-tags text-2xl mb-2"></i>
                    <p>Data Kategori</p>
                </a>
                
                <a href="data_pemasok.php" class="bg-orange-500 hover:bg-orange-600 text-white p-4 rounded-lg text-center">
                    <i class="fas fa-truck text-2xl mb-2"></i>
                    <p>Data Pemasok</p>
                </a>
                
                <a href="data_pengguna.php" class="bg-pink-500 hover:bg-pink-600 text-white p-4 rounded-lg text-center">
                    <i class="fas fa-users text-2xl mb-2"></i>
                    <p>Data Pengguna</p>
                </a>
                
                <a href="transaksi_pembelian.php" class="bg-indigo-500 hover:bg-indigo-600 text-white p-4 rounded-lg text-center">
                    <i class="fas fa-shopping-bag text-2xl mb-2"></i>
                    <p>Pembelian Barang</p>
                </a>
                
                <a href="biaya_operasional.php" class="bg-red-500 hover:bg-red-600 text-white p-4 rounded-lg text-center">
                    <i class="fas fa-money-bill text-2xl mb-2"></i>
                    <p>Biaya Operasional</p>
                </a>
                <?php endif; ?>
                
                <?php if ($user['level'] === 'pemilik' || $user['level'] === 'admin'): ?>
                <a href="laporan_penjualan.php" class="bg-teal-500 hover:bg-teal-600 text-white p-4 rounded-lg text-center">
                    <i class="fas fa-chart-line text-2xl mb-2"></i>
                    <p>Laporan Penjualan</p>
                </a>
                
                <a href="laporan_pembelian.php" class="bg-yellow-500 hover:bg-yellow-600 text-white p-4 rounded-lg text-center">
                    <i class="fas fa-file-invoice text-2xl mb-2"></i>
                    <p>Laporan Pembelian</p>
                </a>
                
                <a href="laporan_laba_rugi.php" class="bg-gray-500 hover:bg-gray-600 text-white p-4 rounded-lg text-center">
                    <i class="fas fa-calculator text-2xl mb-2"></i>
                    <p>Laporan Laba Rugi</p>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="bg-green-500 text-white p-3 rounded-full">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-gray-500 text-sm">Penjualan Hari Ini</h3>
                        <p class="text-2xl font-bold"><?= formatRupiah($stats['penjualan_hari_ini']) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="bg-blue-500 text-white p-3 rounded-full">
                        <i class="fas fa-calendar-month"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-gray-500 text-sm">Penjualan Bulan Ini</h3>
                        <p class="text-2xl font-bold"><?= formatRupiah($stats['penjualan_bulan_ini']) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="bg-purple-500 text-white p-3 rounded-full">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-gray-500 text-sm">Total Barang</h3>
                        <p class="text-2xl font-bold"><?= $stats['total_barang'] ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="bg-orange-500 text-white p-3 rounded-full">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-gray-500 text-sm">Total Stok</h3>
                        <p class="text-2xl font-bold"><?= $stats['total_stok'] ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Chart Penjualan -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold mb-4">Penjualan 7 Hari Terakhir</h3>
                <canvas id="salesChart" width="400" height="200"></canvas>
            </div>
            
            <!-- Stok Menipis -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold mb-4">Stok Menipis</h3>
                <div class="space-y-3">
                    <?php if (empty($stok_menipis)): ?>
                        <p class="text-gray-500">Semua stok barang aman</p>
                    <?php else: ?>
                        <?php foreach ($stok_menipis as $item): ?>
                            <div class="flex justify-between items-center p-3 bg-red-50 rounded">
                                <div>
                                    <p class="font-medium"><?= htmlspecialchars($item['nama_barang']) ?></p>
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars($item['kategori_barang']) ?></p>
                                </div>
                                <span class="bg-red-500 text-white px-2 py-1 rounded text-sm">
                                    <?= $item['stok'] ?> <?= $item['satuan_barang'] ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Chart configuration
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($chart_data, 'tanggal')) ?>,
                datasets: [{
                    label: 'Penjualan',
                    data: <?= json_encode(array_column($chart_data, 'total')) ?>,
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Penjualan: Rp ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>