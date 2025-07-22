<?php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Keamanan: hanya admin dan pemilik yang bisa akses
checkUserLevel(['admin', 'pemilik']);
$user_level = $_SESSION['pengguna']['level'];

// Fungsi untuk format rupiah
function formatRupiah($angka)
{
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Query untuk mengambil data pembelian
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = "SELECT p.id_pembelian, p.tanggal, s.nama_pemasok, p.total_harga_beli, s.id_pemasok,
              (SELECT COUNT(*) FROM detail_pembelian dp WHERE dp.id_pembelian = p.id_pembelian) as jumlah_item
              FROM pembelian p 
              JOIN pemasok s ON p.id_pemasok = s.id_pemasok";

    $conditions = [];
    $params = [];

    if (!empty($_GET['pemasok'])) {
        $conditions[] = "p.id_pemasok = ?";
        $params[] = $_GET['pemasok'];
    }

    if (!empty($_GET['tanggal_awal'])) {
        $conditions[] = "p.tanggal >= ?";
        $params[] = $_GET['tanggal_awal'];
    }

    if (!empty($_GET['tanggal_akhir'])) {
        $conditions[] = "p.tanggal <= ?";
        $params[] = $_GET['tanggal_akhir'];
    }

    if ($conditions) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY p.tanggal DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $pembelians = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Ambil data barang untuk modal tambah
$stmtBarang = $pdo->query("SELECT id_barang, nama_barang, harga_beli, stok FROM barang ORDER BY nama_barang");
$barangs = $stmtBarang->fetchAll(PDO::FETCH_ASSOC);

// Ambil data pemasok untuk modal tambah
$stmtPemasok = $pdo->query("SELECT id_pemasok, nama_pemasok FROM pemasok ORDER BY nama_pemasok");
$pemasoks = $stmtPemasok->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Pembelian - YMC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/pembelian.css">
</head>

<body class="bg-gray-100">
    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-grow p-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Transaksi Pembelian</h1>

                <div class="flex items-center space-x-3">
                    <div class="filter-dropdown relative">
                        <button id="filterButton" class="flex items-center gap-2 px-6 py-3 bg-white text-gray-700 rounded-xl border border-gray-200 hover:bg-gray-50 transition-all duration-200 shadow-sm font-medium">
                            <i class="fas fa-filter text-gray-400"></i>
                            <span>Filter</span>
                            <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
                        </button>
                        <div id="filterContent" class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-lg border border-gray-100 p-6 z-50 hidden">
                            <form action="" method="get" class="space-y-5">
                                <div>
                                    <label for="pemasok" class="block text-sm font-semibold text-gray-700 mb-2">Pemasok</label>
                                    <div class="relative">
                                        <select id="pemasok" name="pemasok"
                                            class="block w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl text-gray-700 focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-150 appearance-none bg-white">
                                            <option value="">Semua Pemasok</option>
                                            <?php foreach ($pemasoks as $pemasok): ?>
                                                <option value="<?= $pemasok['id_pemasok'] ?>" <?= isset($_GET['pemasok']) && $_GET['pemasok'] == $pemasok['id_pemasok'] ? 'selected' : '' ?>><?= htmlspecialchars($pemasok['nama_pemasok']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <i class="fas fa-truck absolute left-3.5 top-3.5 text-gray-400"></i>
                                    </div>
                                </div>
                                <div>
                                    <label for="tanggal_awal" class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Awal</label>
                                    <div class="relative">
                                        <input type="date" id="tanggal_awal" name="tanggal_awal" value="<?= isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : '' ?>"
                                            class="block w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl text-gray-700 focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-150">
                                        <i class="fas fa-calendar-alt absolute left-3.5 top-3.5 text-gray-400"></i>
                                    </div>
                                </div>
                                <div>
                                    <label for="tanggal_akhir" class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Akhir</label>
                                    <div class="relative">
                                        <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="<?= isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : '' ?>"
                                            class="block w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl text-gray-700 focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-150">
                                        <i class="fas fa-calendar-alt absolute left-3.5 top-3.5 text-gray-400"></i>
                                    </div>
                                </div>
                                <div class="flex space-x-3">
                                    <button type="submit"
                                        class="w-full py-3 px-4 border border-transparent rounded-xl shadow-md text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        Terapkan
                                    </button>
                                    <button type="reset"
                                        class="w-full py-3 px-4 border border-gray-300 rounded-xl shadow-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                        Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <button id="printTableBtn" class="flex items-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl transition-all duration-200 shadow-sm font-medium">
                        <i class="fas fa-print"></i>
                        <span>Cetak Tabel</span>
                    </button>

                    <?php if ($user_level === 'admin'): ?>
                        <button id="openAddModal" class="flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-all duration-200 shadow-sm font-medium">
                            <i class="fas fa-plus"></i>
                            <span>Tambah Pembelian</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="min-w-full" id="pembelianTable">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Kode Barang</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Nama Barang</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Tanggal</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Jumlah</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Harga Satuan</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Total Harga</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Pemasok</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (count($pembelians) > 0): ?>
                                <?php foreach ($pembelians as $pembelian): ?>
                                    <?php
                                    // Fetch detail pembelian untuk setiap pembelian
                                    $stmtDetail = $pdo->prepare("
                                        SELECT dp.*, b.nama_barang, b.id_barang
                                        FROM detail_pembelian dp
                                        JOIN barang b ON dp.id_barang = b.id_barang
                                        WHERE dp.id_pembelian = ?
                                    ");
                                    $stmtDetail->execute([$pembelian['id_pembelian']]);
                                    $details = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($details as $detail):
                                    ?>
                                        <tr class="hover:bg-gray-50/50 transition-colors duration-150" data-pembelian-id="<?= $pembelian['id_pembelian'] ?>" data-pemasok-id="<?= $pembelian['id_pemasok'] ?>">
                                            <td class="px-6 py-4 text-sm text-gray-600">
                                                <span class="font-medium bg-gray-100 px-3 py-1 rounded-lg">
                                                    <?= $detail['id_barang'] ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-800 font-medium"><?= htmlspecialchars($detail['nama_barang']) ?></td>
                                            <td class="px-6 py-4 text-sm text-gray-600"><?= date('d/m/Y', strtotime($pembelian['tanggal'])) ?></td>
                                            <td class="px-6 py-4 text-sm text-gray-800"><?= $detail['jumlah'] ?></td>
                                            <td class="px-6 py-4 text-sm font-medium text-gray-800"><?= formatRupiah($detail['harga_beli']) ?></td>
                                            <td class="px-6 py-4 text-sm font-medium text-green-600"><?= formatRupiah($detail['subtotal']) ?></td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-blue-50 text-blue-700">
                                                    <?= htmlspecialchars($pembelian['nama_pemasok']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <?php if ($user_level === 'admin'): ?>
                                                        <button class="bg-blue-50 text-blue-600 hover:bg-blue-100 p-2 rounded-lg transition-colors" title="Edit" data-id="<?= $pembelian['id_pembelian'] ?>">
                                                            <i class="fas fa-edit edit-pembelian"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="bg-amber-50 text-amber-600 hover:bg-amber-100 p-2 rounded-lg transition-colors" title="Lihat Detail" data-id="<?= $pembelian['id_pembelian'] ?>">
                                                        <i class="fas fa-eye view-pembelian"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-8 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <i class="fas fa-box text-gray-400 text-4xl"></i>
                                            <p class="text-gray-500 text-lg">Tidak ada data pembelian yang tersedia</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Tambah Pembelian -->
    <div id="addPembelianModal" class="modal">
        <div class="modal-content bg-white rounded-2xl shadow-2xl border border-gray-100 max-w-3xl">
            <div class="modal-header px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h2 class="modal-title text-xl font-bold text-gray-800">Tambah Pembelian Baru</h2>
                <span class="close text-2xl cursor-pointer hover:text-gray-500 transition-colors">&times;</span>
            </div>
            <div class="modal-body p-6">
                <form id="addPembelianForm" class="space-y-5">
                    <div class="flex space-x-4">
                        <div class="w-1/2">
                            <label for="tanggal" class="block text-sm font-medium text-gray-700">Tanggal</label>
                            <input type="date" id="tanggal" name="tanggal" value="<?= date('Y-m-d') ?>" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div class="w-1/2">
                            <label for="id_pemasok" class="block text-sm font-medium text-gray-700">Pemasok</label>
                            <select id="id_pemasok" name="id_pemasok" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Pilih Pemasok</option>
                                <?php foreach ($pemasoks as $pemasok): ?>
                                    <option value="<?= $pemasok['id_pemasok'] ?>"><?= htmlspecialchars($pemasok['nama_pemasok']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Item Barang</label>
                        <div class="mt-2 p-4 border border-gray-300 rounded-md">
                            <div class="flex space-x-4 mb-4">
                                <div class="w-1/3">
                                    <label for="id_barang" class="block text-xs font-medium text-gray-700">Barang</label>
                                    <select id="id_barang" name="id_barang"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                        <option value="">Pilih Barang</option>
                                        <?php foreach ($barangs as $barang): ?>
                                            <option value="<?= $barang['id_barang'] ?>"
                                                data-harga="<?= $barang['harga_beli'] ?>"
                                                data-stok="<?= $barang['stok'] ?>"
                                                data-nama="<?= htmlspecialchars($barang['nama_barang']) ?>">
                                                <?= htmlspecialchars($barang['nama_barang']) ?> - Stok: <?= $barang['stok'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="w-1/4">
                                    <label for="jumlah" class="block text-xs font-medium text-gray-700">Jumlah</label>
                                    <input type="number" id="jumlah" name="jumlah" min="1" value="1"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                </div>
                                <div class="w-1/4">
                                    <label for="harga_beli" class="block text-xs font-medium text-gray-700">Harga Satuan</label>
                                    <input type="number" id="harga_beli" name="harga_beli" min="0"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                </div>
                                <div class="w-1/6 flex items-end">
                                    <button type="button" id="addItemBtn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 w-full text-sm">
                                        <i class="fas fa-plus"></i> Tambah
                                    </button>
                                </div>
                            </div>

                            <div id="listItems" class="space-y-2">
                                <div class="text-sm text-gray-500 italic text-center p-4">
                                    Belum ada item yang ditambahkan
                                </div>
                            </div>

                            <div class="mt-4 pt-4 border-t flex justify-between items-center">
                                <span class="font-semibold text-gray-700">Total:</span>
                                <span id="totalHarga" class="font-bold text-lg">Rp 0</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button type="button" class="closeModal px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Batal
                        </button>
                        <button type="submit" id="savePembelianBtn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Simpan Pembelian
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Pembelian -->
    <div id="editPembelianModal" class="modal">
        <div class="modal-content bg-white rounded-2xl shadow-2xl border border-gray-100 max-w-3xl">
            <div class="modal-header px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h2 class="modal-title text-xl font-bold text-gray-800">Edit Pembelian</h2>
                <span class="close text-2xl cursor-pointer hover:text-gray-500 transition-colors">&times;</span>
            </div>
            <div class="modal-body p-6">
                <form id="editPembelianForm" class="space-y-5">
                    <input type="hidden" id="edit_id_pembelian" name="id_pembelian">

                    <div class="flex space-x-4">
                        <div class="w-1/2">
                            <label for="edit_tanggal" class="block text-sm font-medium text-gray-700">Tanggal</label>
                            <input type="date" id="edit_tanggal" name="tanggal" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div class="w-1/2">
                            <label for="edit_id_pemasok" class="block text-sm font-medium text-gray-700">Pemasok</label>
                            <select id="edit_id_pemasok" name="id_pemasok" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Pilih Pemasok</option>
                                <?php foreach ($pemasoks as $pemasok): ?>
                                    <option value="<?= $pemasok['id_pemasok'] ?>"><?= htmlspecialchars($pemasok['nama_pemasok']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Item Barang</label>
                        <div id="editItemsList" class="mt-2 p-4 border border-gray-300 rounded-md">
                            <!-- Items will be loaded here -->
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button type="button" class="closeModal px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Batal
                        </button>
                        <button type="submit" id="updatePembelianBtn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Lihat Invoice -->
    <div id="invoiceModal" class="modal">
        <div class="modal-content bg-white rounded-2xl shadow-2xl border border-gray-100 max-w-4xl">
            <div class="modal-header px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h2 class="modal-title text-xl font-bold text-gray-800">Invoice Pembelian</h2>
                <span class="close text-2xl cursor-pointer hover:text-gray-500 transition-colors">&times;</span>
            </div>
            <div class="modal-body p-6">
                <div id="invoice-container" class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                    <!-- Invoice content will be generated here -->
                </div>
                <div class="flex justify-end space-x-3 pt-5 border-t mt-6 no-print">
                    <button id="printInvoiceBtn" class="flex items-center gap-2 px-5 py-2.5 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-all shadow-sm font-medium">
                        <i class="fas fa-print"></i>
                        <span>Cetak Invoice</span>
                    </button>
                    <button class="closeModal flex items-center gap-2 px-5 py-2.5 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all shadow-sm font-medium">
                        <span>Tutup</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content bg-white rounded-2xl shadow-2xl border border-gray-100" style="max-width: 400px;">
            <div class="modal-header px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h2 class="modal-title text-xl font-bold text-gray-800">Konfirmasi Hapus</h2>
                <span class="close text-2xl cursor-pointer hover:text-gray-500 transition-colors">&times;</span>
            </div>
            <div class="modal-body p-6">
                <div class="flex items-center gap-4 mb-6">
                    <div class="bg-red-50 p-3 rounded-full">
                        <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
                    </div>
                    <p class="text-gray-700">Apakah Anda yakin ingin menghapus transaksi pembelian ini?</p>
                </div>
                <div class="flex justify-end gap-3">
                    <button class="closeModal flex items-center gap-2 px-5 py-2.5 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all shadow-sm font-medium">
                        Batal
                    </button>
                    <button id="confirmDeleteBtn" data-id="" class="flex items-center gap-2 px-5 py-2.5 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-all shadow-sm font-medium">
                        <i class="fas fa-trash-alt"></i>
                        <span>Ya, Hapus</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Data yang akan diteruskan ke JavaScript -->
    <script>
        const config = {
            userLevel: '<?= $user_level ?>',
            baseUrl: '../',
            currentDate: '<?= date('Y-m-d') ?>'
        };

        // Inline JavaScript untuk filter button
        document.addEventListener('DOMContentLoaded', function() {
            // Filter button functionality
            const filterButton = document.getElementById('filterButton');
            const filterContent = document.getElementById('filterContent');

            if (filterButton && filterContent) {
                filterButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    filterContent.classList.toggle('hidden');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!filterButton.contains(e.target) && !filterContent.contains(e.target)) {
                        filterContent.classList.add('hidden');
                    }
                });
            }
        });
    </script>

    <!-- External JavaScript -->
    <script src="../assets/js/pembelian.js"></script>
</body>

</html>