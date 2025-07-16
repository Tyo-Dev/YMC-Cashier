<?php
session_start();

// Redirect jika belum login
if (!isset($_SESSION['pengguna'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Ambil data pengguna dari session
$pengguna = $_SESSION['pengguna'];
$user_level = $pengguna['level'];
$nama_user = htmlspecialchars($pengguna['nama_user']);

// Data dummy untuk demonstrasi - Ganti dengan query database Anda
function get_dummy_data($level) {
    if ($level == 'admin') {
        return [
            'penjualan_hari_ini' => 'Rp 1.250.000',
            'transaksi_hari_ini' => 32,
            'stok_kritis' => 8,
            'aktivitas_terbaru' => [
                ['jenis' => 'Penjualan', 'deskripsi' => 'Transaksi #INV-001 oleh Kasir A', 'waktu' => '5 menit lalu'],
                ['jenis' => 'Stok', 'deskripsi' => 'Barang "Kopi Robusta" ditambahkan', 'waktu' => '30 menit lalu'],
                ['jenis' => 'Pengguna', 'deskripsi' => 'User "Kasir B" berhasil dibuat', 'waktu' => '1 jam lalu'],
            ]
        ];
    } elseif ($level == 'kasir') {
        return [
            'penjualan_anda_hari_ini' => 'Rp 450.000',
            'transaksi_anda_hari_ini' => 12,
            'aktivitas_terbaru' => [
                ['jenis' => 'Penjualan', 'deskripsi' => 'Transaksi #INV-008 sebesar Rp 75.000', 'waktu' => '2 menit lalu'],
                ['jenis' => 'Penjualan', 'deskripsi' => 'Transaksi #INV-007 sebesar Rp 30.000', 'waktu' => '15 menit lalu'],
            ]
        ];
    } elseif ($level == 'pemilik') {
        return [
            'pendapatan_bulan_ini' => 'Rp 25.800.000',
            'laba_kotor' => 'Rp 8.200.000',
            'barang_terlaris' => 'Espresso Single',
            'sales_chart_data' => [65, 59, 80, 81, 56, 55, 40] // Data untuk 7 hari terakhir
        ];
    }
    return [];
}
$data = get_dummy_data($user_level);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Anda bisa menambahkan custom style di sini jika perlu */
        /* .card-hover-effect dihapus karena @apply tidak didukung di CSS biasa.
           Gunakan langsung kelas utilitas Tailwind di elemen HTML Anda:
           transition-all duration-300 hover:shadow-xl hover:-translate-y-1
        */
    </style>
</head>
<body class="bg-slate-50">

    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>

        <main class="ml-20 w-full min-h-screen p-8">
            <header class="mb-8">
                <h1 class="text-4xl font-bold text-slate-800">Selamat Datang, <?= $nama_user ?>!</h1>
                <p class="text-slate-500 mt-1">Berikut adalah ringkasan aktivitas untuk Anda hari ini.</p>
            </header>

            <?php if ($user_level == 'admin'): ?>
                <div class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="bg-white p-6 rounded-xl shadow-md flex items-center gap-6">
                            <div class="bg-teal-100 text-teal-600 p-4 rounded-full"><i class="fas fa-dollar-sign fa-2x"></i></div>
                            <div>
                                <p class="text-sm text-slate-500">Penjualan Hari Ini</p>
                                <p class="text-2xl font-bold text-slate-800"><?= $data['penjualan_hari_ini'] ?></p>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-xl shadow-md flex items-center gap-6">
                            <div class="bg-lime-100 text-lime-600 p-4 rounded-full"><i class="fas fa-exchange-alt fa-2x"></i></div>
                            <div>
                                <p class="text-sm text-slate-500">Transaksi Hari Ini</p>
                                <p class="text-2xl font-bold text-slate-800"><?= $data['transaksi_hari_ini'] ?></p>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-xl shadow-md flex items-center gap-6">
                            <div class="bg-red-100 text-red-600 p-4 rounded-full"><i class="fas fa-exclamation-triangle fa-2x"></i></div>
                            <div>
                                <p class="text-sm text-slate-500">Stok Barang Kritis</p>
                                <p class="text-2xl font-bold text-slate-800"><?= $data['stok_kritis'] ?> Item</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-2xl font-semibold text-slate-800 mb-4">Akses Cepat</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <a href="barang.php" class="bg-white p-6 rounded-xl shadow-md text-center card-hover-effect">
                                <i class="fas fa-box-open fa-3x text-green-600 mb-4"></i>
                                <p class="font-semibold text-slate-700">Kelola Barang</p>
                            </a>
                            <a href="laporan_penjualan.php" class="bg-white p-6 rounded-xl shadow-md text-center card-hover-effect">
                                <i class="fas fa-chart-line fa-3x text-green-600 mb-4"></i>
                                <p class="font-semibold text-slate-700">Lihat Laporan</p>
                            </a>
                             <a href="penjualan.php" class="bg-white p-6 rounded-xl shadow-md text-center card-hover-effect">
                                <i class="fas fa-cash-register fa-3x text-green-600 mb-4"></i>
                                <p class="font-semibold text-slate-700">Halaman Kasir</p>
                            </a>
                            <a href="pengaturan.php" class="bg-white p-6 rounded-xl shadow-md text-center card-hover-effect">
                                <i class="fas fa-users-cog fa-3x text-green-600 mb-4"></i>
                                <p class="font-semibold text-slate-700">Kelola Pengguna</p>
                            </a>
                        </div>
                    </div>
                </div>

            <?php elseif ($user_level == 'kasir'): ?>
                <div class="space-y-8">
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white p-6 rounded-xl shadow-md flex items-center gap-6">
                            <div class="bg-teal-100 text-teal-600 p-4 rounded-full"><i class="fas fa-wallet fa-2x"></i></div>
                            <div>
                                <p class="text-sm text-slate-500">Penjualan Anda Hari Ini</p>
                                <p class="text-2xl font-bold text-slate-800"><?= $data['penjualan_anda_hari_ini'] ?></p>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-xl shadow-md flex items-center gap-6">
                            <div class="bg-lime-100 text-lime-600 p-4 rounded-full"><i class="fas fa-receipt fa-2x"></i></div>
                            <div>
                                <p class="text-sm text-slate-500">Transaksi Anda Hari Ini</p>
                                <p class="text-2xl font-bold text-slate-800"><?= $data['transaksi_anda_hari_ini'] ?></p>
                            </div>
                        </div>
                    </div>
                    <a href="penjualan.php" class="bg-gradient-to-br from-green-500 to-teal-600 text-white flex items-center justify-center gap-4 p-10 rounded-xl shadow-lg card-hover-effect">
                        <i class="fas fa-cash-register fa-4x"></i>
                        <div>
                            <p class="text-3xl font-bold">Mulai Transaksi Baru</p>
                            <p class="opacity-80">Klik di sini untuk membuka halaman kasir</p>
                        </div>
                    </a>
                </div>

            <?php elseif ($user_level == 'pemilik'): ?>
                <div class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="bg-white p-6 rounded-xl shadow-md flex items-center gap-6">
                            <div class="bg-green-100 text-green-600 p-4 rounded-full"><i class="fas fa-calendar-alt fa-2x"></i></div>
                            <div>
                                <p class="text-sm text-slate-500">Pendapatan Bulan Ini</p>
                                <p class="text-2xl font-bold text-slate-800"><?= $data['pendapatan_bulan_ini'] ?></p>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-xl shadow-md flex items-center gap-6">
                            <div class="bg-teal-100 text-teal-600 p-4 rounded-full"><i class="fas fa-chart-pie fa-2x"></i></div>
                            <div>
                                <p class="text-sm text-slate-500">Perkiraan Laba Kotor</p>
                                <p class="text-2xl font-bold text-slate-800"><?= $data['laba_kotor'] ?></p>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-xl shadow-md flex items-center gap-6">
                            <div class="bg-lime-100 text-lime-600 p-4 rounded-full"><i class="fas fa-star fa-2x"></i></div>
                            <div>
                                <p class="text-sm text-slate-500">Barang Terlaris</p>
                                <p class="text-2xl font-bold text-slate-800"><?= $data['barang_terlaris'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-md">
                        <h2 class="text-2xl font-semibold text-slate-800 mb-4">Analisis Penjualan (7 Hari Terakhir)</h2>
                        <p class="text-sm text-slate-500 mb-6">Grafik ini menunjukkan tren penjualan selama seminggu terakhir. Integrasikan dengan Chart.js untuk data dinamis.</p>
                        <div class="h-64 flex items-end justify-between gap-2">
                           <?php foreach($data['sales_chart_data'] as $value): ?>
                                <div class="bg-green-400 w-full rounded-t-lg transition-all hover:bg-green-500" style="height: <?= $value ?>%;"></div>
                           <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>

</body>
</html>