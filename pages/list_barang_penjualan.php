<?php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Keamanan: Semua level bisa akses
checkUserLevel(['admin', 'pemilik', 'kasir']);

$user_level = $_SESSION['pengguna']['level'];

// Fungsi untuk format rupiah
function formatRupiah($angka)
{
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Ambil data barang dengan informasi kategori
$stmt = $pdo->query("SELECT b.*, k.kategori_barang 
                    FROM barang b 
                    JOIN kategori k ON b.id_kategori = k.id_kategori 
                    ORDER BY b.nama_barang");
$barangs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Barang Penjualan - YMC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body class="bg-gray-100">

    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>

        <main class="flex-1 p-8">
            <div class="max-w-7xl mx-auto">
                <header class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">Daftar Barang Penjualan</h1>
                </header>

                <!-- Search Bar -->
                <div class="mb-6">
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <input type="text"
                                id="searchInput"
                                placeholder="Cari nama barang atau kategori..."
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Kode</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Nama Barang</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Kategori</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Harga Jual</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Stok</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Satuan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100" id="tableBody">
                                <?php if (empty($barangs)): ?>
                                    <tr>
                                        <td colspan="6" class="px-4 py-3 text-center text-gray-500">
                                            Tidak ada data barang
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($barangs as $barang): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-600"><?= str_pad($barang['id_barang'], 6, '0', STR_PAD_LEFT) ?></td>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-800"><?= htmlspecialchars($barang['nama_barang']) ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($barang['kategori_barang']) ?></td>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-800"><?= formatRupiah($barang['harga_jual']) ?></td>
                                            <td class="px-4 py-3 text-sm">
                                                <span class="px-2 py-1 text-sm rounded-full <?= $barang['stok'] > 10 ? 'bg-green-100 text-green-800' : ($barang['stok'] > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                                    <?= $barang['stok'] ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($barang['satuan_barang']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Script untuk fungsi pencarian -->
    <script>
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.getElementById('tableBody');

        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = tableBody.getElementsByTagName('tr');

            for (let row of rows) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            }
        });
    </script>

</body>

</html>