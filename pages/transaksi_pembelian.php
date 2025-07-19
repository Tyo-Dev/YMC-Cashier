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
$query = "SELECT p.id_pembelian, p.tanggal, s.nama_pemasok, p.total_harga_beli, s.id_pemasok,
          (SELECT COUNT(*) FROM detail_pembelian dp WHERE dp.id_pembelian = p.id_pembelian) as jumlah_item
          FROM pembelian p 
          JOIN pemasok s ON p.id_pemasok = s.id_pemasok
          ORDER BY p.tanggal DESC";
$stmt = $pdo->prepare($query);
$stmt->execute();
$pembelians = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <div class="filter-dropdown">
                        <button id="filterButton" class="filter-button">
                            Filter
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div id="filterContent" class="filter-content">
                            <form action="" method="get" class="space-y-4">
                                <div>
                                    <label for="search" class="block text-sm font-medium text-gray-700">Cari</label>
                                    <input type="text" id="search" name="search" placeholder="Cari..."
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div>
                                    <label for="pemasok" class="block text-sm font-medium text-gray-700">Pemasok</label>
                                    <select id="pemasok" name="pemasok"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">Semua Pemasok</option>
                                        <?php foreach ($pemasoks as $pemasok): ?>
                                            <option value="<?= $pemasok['id_pemasok'] ?>"><?= htmlspecialchars($pemasok['nama_pemasok']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="tanggal_awal" class="block text-sm font-medium text-gray-700">Tanggal Awal</label>
                                    <input type="date" id="tanggal_awal" name="tanggal_awal"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div>
                                    <label for="tanggal_akhir" class="block text-sm font-medium text-gray-700">Tanggal Akhir</label>
                                    <input type="date" id="tanggal_akhir" name="tanggal_akhir"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div class="flex space-x-3">
                                    <button type="submit"
                                        class="w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Terapkan
                                    </button>
                                    <button type="reset"
                                        class="w-full py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <button id="printTableBtn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                        <i class="fas fa-print mr-2"></i>
                        Cetak Tabel
                    </button>

                    <?php if ($user_level === 'admin'): ?>
                        <button id="openAddModal" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                            <i class="fas fa-plus mr-2"></i>
                            Tambah Pembelian
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full" id="pembelianTable">
                        <thead>
                            <tr>
                                <th class="px-4 py-3">Kode Barang</th>
                                <th class="px-4 py-3">Nama Barang</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Jumlah</th>
                                <th class="px-4 py-3">Harga Satuan</th>
                                <th class="px-4 py-3">Total Harga</th>
                                <th class="px-4 py-3">Pemasok</th>
                                <th class="px-4 py-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
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
                                        <tr data-pembelian-id="<?= $pembelian['id_pembelian'] ?>" data-pemasok-id="<?= $pembelian['id_pemasok'] ?>">
                                            <td class="px-4 py-3"><?= $detail['id_barang'] ?></td>
                                            <td class="px-4 py-3"><?= htmlspecialchars($detail['nama_barang']) ?></td>
                                            <td class="px-4 py-3"><?= date('d/m/Y', strtotime($pembelian['tanggal'])) ?></td>
                                            <td class="px-4 py-3"><?= $detail['jumlah'] ?></td>
                                            <td class="px-4 py-3"><?= formatRupiah($detail['harga_beli']) ?></td>
                                            <td class="px-4 py-3"><?= formatRupiah($detail['subtotal']) ?></td>
                                            <td class="px-4 py-3"><?= htmlspecialchars($pembelian['nama_pemasok']) ?></td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center space-x-2">
                                                    <?php if ($user_level === 'admin'): ?>
                                                        <button class="text-blue-600 hover:text-blue-800" title="Edit" data-id="<?= $pembelian['id_pembelian'] ?>">
                                                            <i class="fas fa-edit edit-pembelian"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="text-yellow-600 hover:text-yellow-800" title="Lihat Detail" data-id="<?= $pembelian['id_pembelian'] ?>">
                                                        <i class="fas fa-eye view-pembelian"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                                        Tidak ada data pembelian yang tersedia.
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
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Tambah Pembelian Baru</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addPembelianForm" class="space-y-4">
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
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Pembelian</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editPembelianForm" class="space-y-4">
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
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Invoice Pembelian</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="invoice-container">
                    <!-- Invoice content will be generated here -->
                </div>
                <div class="flex justify-end space-x-3 pt-4 border-t mt-4 no-print">
                    <button id="printInvoiceBtn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        <i class="fas fa-print mr-2"></i> Cetak Invoice
                    </button>
                    <button class="closeModal px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2 class="modal-title">Konfirmasi Hapus</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p class="mb-4">Apakah Anda yakin ingin menghapus transaksi pembelian ini?</p>
                <div class="flex justify-end space-x-3">
                    <button class="closeModal px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Batal
                    </button>
                    <button id="confirmDeleteBtn" data-id="" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        Ya, Hapus
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
    </script>

    <!-- External JavaScript -->
    <script src="../assets/js/pembelian.js"></script>
</body>
</html>