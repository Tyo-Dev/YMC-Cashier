<?php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Keamanan: Hanya admin dan pemilik yang bisa akses
checkUserLevel(['admin', 'pemilik']);

$user_level = $_SESSION['pengguna']['level'];
$notification = null;

// Fungsi untuk validasi input
function validateInput($data)
{
    return htmlspecialchars(trim($data));
}

// Fungsi untuk format rupiah
function formatRupiah($angka)
{
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Proses Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_level === 'admin') {
    try {
        if ($_POST['action'] === 'add') {
            $id_barang = validateInput($_POST['id_barang']); // Kode barang manual
            $nama_barang = validateInput($_POST['nama_barang']);
            $id_kategori = filter_var($_POST['id_kategori'], FILTER_VALIDATE_INT);
            $harga_beli = filter_var($_POST['harga_beli'], FILTER_VALIDATE_INT);
            $margin = filter_var($_POST['margin'], FILTER_VALIDATE_FLOAT);
            $harga_jual = filter_var($_POST['harga_jual'], FILTER_VALIDATE_INT);
            $stok = filter_var($_POST['stok'], FILTER_VALIDATE_INT);
            $satuan = validateInput($_POST['satuan']);

            // Validasi id_barang tidak boleh kosong dan harus unik
            if (empty($id_barang)) {
                throw new Exception('Kode barang tidak boleh kosong');
            }

            // Cek apakah id_barang sudah ada
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM barang WHERE id_barang = ?");
            $stmtCheck->execute([$id_barang]);
            if ($stmtCheck->fetchColumn() > 0) {
                throw new Exception('Kode barang sudah digunakan, silakan gunakan kode lain');
            }

            // Insert dengan id_barang manual (non auto-increment)
            $stmt = $pdo->prepare("INSERT INTO barang (id_barang, nama_barang, id_kategori, harga_beli, margin, harga_jual, stok, satuan_barang) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id_barang, $nama_barang, $id_kategori, $harga_beli, $margin, $harga_jual, $stok, $satuan]);
            $notification = ['type' => 'success', 'message' => 'Barang berhasil ditambahkan.'];
        } elseif ($_POST['action'] === 'edit') {
            $original_id_barang = validateInput($_POST['original_id_barang']); // ID lama untuk WHERE clause
            $nama_barang = validateInput($_POST['nama_barang']);
            $id_kategori = filter_var($_POST['id_kategori'], FILTER_VALIDATE_INT);
            $harga_beli = filter_var($_POST['harga_beli'], FILTER_VALIDATE_INT);
            $margin = filter_var($_POST['margin'], FILTER_VALIDATE_FLOAT);
            $harga_jual = filter_var($_POST['harga_jual'], FILTER_VALIDATE_INT);
            $stok = filter_var($_POST['stok'], FILTER_VALIDATE_INT);
            $satuan = validateInput($_POST['satuan']);

            // Untuk edit, id_barang (kode barang) tidak dapat diubah
            // Update tanpa mengubah id_barang
            $stmt = $pdo->prepare("UPDATE barang SET nama_barang = ?, id_kategori = ?, harga_beli = ?, margin = ?, harga_jual = ?, stok = ?, satuan_barang = ? WHERE id_barang = ?");
            $stmt->execute([$nama_barang, $id_kategori, $harga_beli, $margin, $harga_jual, $stok, $satuan, $original_id_barang]);
            $notification = ['type' => 'success', 'message' => 'Data barang berhasil diperbarui.'];
        } elseif ($_POST['action'] === 'delete') {
            $id_barang = filter_var($_POST['id_barang'], FILTER_VALIDATE_INT);

            // Cek apakah barang pernah digunakan dalam transaksi
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM detail_penjualan WHERE id_barang = ?");
            $stmt->execute([$id_barang]);
            if ($stmt->fetchColumn() > 0) {
                $notification = ['type' => 'error', 'message' => 'Barang tidak dapat dihapus karena sudah pernah digunakan dalam transaksi.'];
            } else {
                $stmt = $pdo->prepare("DELETE FROM barang WHERE id_barang = ?");
                $stmt->execute([$id_barang]);
                $notification = ['type' => 'success', 'message' => 'Barang berhasil dihapus.'];
            }
        }
    } catch (PDOException $e) {
        $notification = ['type' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
    }
}

// Ambil semua data kategori untuk dropdown
$stmt = $pdo->query("SELECT * FROM kategori ORDER BY kategori_barang");
$kategoris = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil data barang dengan informasi kategori, diurutkan berdasarkan id_barang
$stmt = $pdo->query("SELECT b.*, k.kategori_barang 
                    FROM barang b 
                    JOIN kategori k ON b.id_kategori = k.id_kategori 
                    ORDER BY b.id_barang");
$barangs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang - YMC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">

    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>

        <main class="flex-1 p-6 sm:p-8 lg:p-10">
            <div class="max-w-7xl mx-auto">
                <header class="flex flex-col sm:flex-row justify-between items-center mb-10 gap-4">
                    <div class="flex items-center gap-4">
                        <h1 class="text-3xl sm:text-4xl font-bold text-gray-800 bg-white py-3 px-6 rounded-2xl shadow-md border border-gray-100">
                            Data Barang
                        </h1>
                    </div>
                    <?php if ($user_level === 'admin'): ?>
                        <button onclick="openModal('add')"
                            class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-6 py-3 rounded-xl flex items-center gap-3 transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <i class="fas fa-plus-circle text-lg"></i>
                            <span class="font-medium">Tambah Barang</span>
                        </button>
                    <?php endif; ?>
                </header>

                <?php if ($notification): ?>
                    <div class="mb-8 p-5 rounded-2xl border-2 backdrop-blur-sm shadow-md <?= $notification['type'] === 'success' ? 'bg-green-50/80 border-green-200 text-green-800' : 'bg-red-50/80 border-red-200 text-red-800' ?>">
                        <div class="flex items-center gap-4">
                            <i class="fas <?= $notification['type'] === 'success' ? 'fa-circle-check text-green-500' : 'fa-circle-exclamation text-red-500' ?> text-2xl"></i>
                            <p class="font-medium text-lg"><?= $notification['message'] ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Search Bar -->
                <div class="mb-8">
                    <div class="flex gap-4">
                        <div class="flex-1 flex gap-4">
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-lg"></i>
                                <input type="text"
                                    id="searchInput"
                                    placeholder="Cari Barang..."
                                    class="w-full pl-12 pr-4 py-3 text-lg rounded-xl border-2 border-gray-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all bg-white/50">
                            </div>
                            <div class="w-64">
                                <select id="categoryFilter"
                                    class="w-full px-4 py-3 text-lg rounded-xl border-2 border-gray-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all bg-white/50">
                                    <option value="">Semua Kategori</option>
                                    <?php foreach ($kategoris as $kategori): ?>
                                        <option value="<?= htmlspecialchars($kategori['kategori_barang']) ?>">
                                            <?= htmlspecialchars($kategori['kategori_barang']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <?php if ($user_level === 'pemilik'): ?>
                            <div>
                                <a href="cetak/cetak_barang.php"
                                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-xl transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                    <i class="fas fa-file-excel text-lg mr-3"></i>
                                    <span class="font-medium">Unduh Laporan Excel</span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden border border-gray-100">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-5 text-left text-base font-semibold text-gray-700">Kode</th>
                                    <th class="px-6 py-5 text-left text-base font-semibold text-gray-700">Nama Barang</th>
                                    <th class="px-6 py-5 text-left text-base font-semibold text-gray-700">Kategori</th>
                                    <th class="px-6 py-5 text-left text-base font-semibold text-gray-700">Harga Beli</th>
                                    <th class="px-6 py-5 text-left text-base font-semibold text-gray-700">Margin</th>
                                    <th class="px-6 py-5 text-left text-base font-semibold text-gray-700">Harga Jual</th>
                                    <th class="px-6 py-5 text-left text-base font-semibold text-gray-700">Stok</th>
                                    <th class="px-6 py-5 text-left text-base font-semibold text-gray-700">Satuan</th>
                                    <?php if ($user_level === 'admin'): ?>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100" id="tableBody">
                                <?php foreach ($barangs as $barang): ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors duration-150">
                                        <td class="px-6 py-4 text-base text-gray-600 font-medium">
                                            <span class="bg-blue-50 text-blue-700 px-3 py-1 rounded-lg font-mono">
                                                <?= htmlspecialchars($barang['id_barang']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-gray-800 font-medium text-base bg-gray-50 px-3 py-1 rounded-lg">
                                                <?= htmlspecialchars($barang['nama_barang']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-base text-gray-600"><?= htmlspecialchars($barang['kategori_barang']) ?></td>
                                        <td class="px-6 py-4">
                                            <span class="text-base font-medium text-blue-700"><?= formatRupiah($barang['harga_beli']) ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-base font-medium <?= $barang['margin'] > 20 ? 'text-green-600' : 'text-blue-600' ?>">
                                                <?= number_format($barang['margin'], 2) ?>%
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-base font-medium text-green-700"><?= formatRupiah($barang['harga_jual']) ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-4 py-1.5 text-base rounded-xl inline-flex items-center gap-2 font-medium 
                                                <?= $barang['stok'] > 10 ? 'bg-green-50 text-green-700 border border-green-200' : ($barang['stok'] > 0 ? 'bg-yellow-50 text-yellow-700 border border-yellow-200' :
                                                    'bg-red-50 text-red-700 border border-red-200') ?>">
                                                <i class="fas <?= $barang['stok'] > 10 ? 'fa-check-circle text-green-500' : ($barang['stok'] > 0 ? 'fa-exclamation-circle text-yellow-500' :
                                                                    'fa-times-circle text-red-500') ?>"></i>
                                                <?= $barang['stok'] ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-base font-medium text-gray-700 bg-gray-50 px-3 py-1 rounded-lg">
                                                <?= htmlspecialchars($barang['satuan_barang']) ?>
                                            </span>
                                        </td>
                                        <?php if ($user_level === 'admin'): ?>
                                            <td class="px-6 py-4">
                                                <div class="flex gap-4">
                                                    <button onclick='openModal("edit", <?= json_encode($barang) ?>)'
                                                        class="text-blue-600 hover:text-blue-800 bg-blue-50 p-2.5 rounded-lg hover:bg-blue-100 transition-colors">
                                                        <i class="fas fa-edit text-lg"></i>
                                                    </button>
                                                    <form method="POST" class="inline" onsubmit="return confirmDelete()">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id_barang" value="<?= $barang['id_barang'] ?>">
                                                        <button type="submit" class="text-red-600 hover:text-red-800 bg-red-50 p-2.5 rounded-lg hover:bg-red-100 transition-colors">
                                                            <i class="fas fa-trash text-lg"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Script untuk fungsi pencarian dan filter -->
    <script>
        const searchInput = document.getElementById('searchInput');
        const categoryFilter = document.getElementById('categoryFilter');
        const tableBody = document.getElementById('tableBody');

        // Modifikasi fungsi filterTable yang sudah ada
        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedCategory = categoryFilter.value.toLowerCase();
            const urlParams = new URLSearchParams(window.location.search);
            const isLowStockFilter = urlParams.get('filter') === 'low_stock';
            const rows = tableBody.getElementsByTagName('tr');

            for (let row of rows) {
                const text = row.textContent.toLowerCase();
                const categoryCell = row.getElementsByTagName('td')[2]; // Index 2 adalah kolom kategori
                const stokCell = row.getElementsByTagName('td')[6]; // Index 6 adalah kolom stok
                const category = categoryCell.textContent.toLowerCase();
                const stokValue = parseInt(stokCell.textContent.trim());

                const matchesSearch = text.includes(searchTerm);
                const matchesCategory = selectedCategory === '' || category === selectedCategory;
                const matchesStockFilter = !isLowStockFilter || stokValue < 10;

                row.style.display = matchesSearch && matchesCategory && matchesStockFilter ? '' : 'none';
            }
        }

        // Event listeners for both search and category filter
        searchInput.addEventListener('input', filterTable);
        categoryFilter.addEventListener('change', filterTable);

        // Fungsi untuk mengecek parameter URL saat halaman dimuat
        function checkUrlParams() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('filter') === 'low_stock') {
                // Set filter untuk menampilkan hanya stok menipis
                const rows = tableBody.getElementsByTagName('tr');
                for (let row of rows) {
                    const stokCell = row.getElementsByTagName('td')[6]; // Index 6 adalah kolom stok
                    const stokValue = parseInt(stokCell.textContent.trim());
                    row.style.display = stokValue < 10 ? '' : 'none';
                }
            }
        }

        // Panggil fungsi saat halaman dimuat
        document.addEventListener('DOMContentLoaded', checkUrlParams);
    </script>

    <?php if ($user_level === 'admin'): ?>
        <!-- Modal Form -->
        <div id="barangModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm hidden flex items-center justify-center z-50">
            <div class="bg-white/90 backdrop-blur-md rounded-2xl shadow-xl w-full max-w-5xl p-10 border border-gray-100 transform transition-all">
                <div class="flex justify-between items-center mb-8">
                    <h2 id="modalTitle" class="text-3xl font-bold text-gray-800 flex items-center gap-4">
                        <i class="fas fa-box text-green-600 text-2xl"></i>
                        <span></span>
                    </h2>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl p-3 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form id="barangForm" method="POST" class="space-y-6">
                    <input type="hidden" name="action" id="formAction">
                    <input type="hidden" name="original_id_barang" id="formOriginalIdBarang">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kode Barang</label>
                            <input type="text" id="formIdBarang" name="id_barang" required
                                placeholder="Contoh: hanya bisa angka : 2551"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 font-mono">
                            <p class="text-xs text-gray-500 mt-1">Masukkan kode unik untuk barang ini</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Barang</label>
                            <input type="text" id="formNamaBarang" name="nama_barang" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                            <select id="formKategori" name="id_kategori" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                <?php foreach ($kategoris as $kategori): ?>
                                    <option value="<?= $kategori['id_kategori'] ?>"><?= htmlspecialchars($kategori['kategori_barang']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Harga Beli</label>
                            <input type="number" id="formHargaBeli" name="harga_beli" required min="0"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                onchange="hitungHargaJual()">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Margin (%)</label>
                            <input type="number" id="formMargin" name="margin" required min="0" step="0.01"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                onchange="hitungHargaJual()">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Harga Jual</label>
                            <input type="number" id="formHargaJual" name="harga_jual" required min="0"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stok</label>
                            <input type="number" id="formStok" name="stok" required min="0"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Satuan</label>
                            <select id="formSatuan" name="satuan" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="pcs">PCS</option>
                                <option value="item">ITEM</option>
                                <option value="box">BOX</option>
                                <option value="lusin">LUSIN</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-4 mt-8">
                        <button type="button" onclick="closeModal()"
                            class="px-8 py-3.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all duration-300 font-medium flex items-center gap-3 text-lg">
                            <i class="fas fa-times"></i>
                            <span>Batal</span>
                        </button>
                        <button type="submit"
                            class="px-8 py-3.5 text-white bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 rounded-xl transition-all duration-300 font-medium shadow-md hover:shadow-lg transform hover:-translate-y-0.5 flex items-center gap-3 text-lg">
                            <i class="fas fa-save"></i>
                            <span>Simpan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Script khusus untuk admin -->
        <script>
            const modal = document.getElementById('barangModal');
            const modalTitle = document.getElementById('modalTitle');
            const form = document.getElementById('barangForm');
            const formAction = document.getElementById('formAction');
            const formOriginalIdBarang = document.getElementById('formOriginalIdBarang');
            const formIdBarang = document.getElementById('formIdBarang'); // Input kode barang
            const formNamaBarang = document.getElementById('formNamaBarang');
            const formHargaBeli = document.getElementById('formHargaBeli');
            const formMargin = document.getElementById('formMargin');
            const formHargaJual = document.getElementById('formHargaJual');

            function hitungHargaJual() {
                const hargaBeli = parseFloat(formHargaBeli.value) || 0;
                const margin = parseFloat(formMargin.value) || 0;
                const hargaJual = hargaBeli + (hargaBeli * margin / 100);
                formHargaJual.value = Math.round(hargaJual);
            }

            function openModal(mode, data = null) {
                if (mode === 'add') {
                    modalTitle.textContent = 'Tambah Barang Baru';
                    formAction.value = 'add';
                    form.reset();
                    // Enable kode barang field for new items
                    formIdBarang.readOnly = false;
                    formIdBarang.classList.remove('bg-gray-100');
                    formIdBarang.placeholder = 'Contoh: hanya bisa angka : 2551';
                } else if (mode === 'edit' && data) {
                    modalTitle.textContent = 'Edit Data Barang';
                    formAction.value = 'edit';
                    formOriginalIdBarang.value = data.id_barang; // Simpan ID lama untuk WHERE clause
                    formIdBarang.value = data.id_barang; // Tampilkan kode barang saat ini
                    formNamaBarang.value = data.nama_barang;
                    document.getElementById('formKategori').value = data.id_kategori;
                    formHargaBeli.value = data.harga_beli;
                    formMargin.value = data.margin;
                    formHargaJual.value = data.harga_jual;
                    document.getElementById('formStok').value = data.stok;
                    document.getElementById('formSatuan').value = data.satuan_barang;
                    // Disable kode barang field for editing (prevent changing existing codes)
                    formIdBarang.readOnly = true;
                    formIdBarang.classList.add('bg-gray-100');
                    formIdBarang.placeholder = 'Kode tidak dapat diubah';
                }
                modal.classList.remove('hidden');
            }

            function closeModal() {
                modal.classList.add('hidden');
                form.reset();
            }

            function confirmDelete() {
                return confirm('Apakah Anda yakin ingin menghapus barang ini?');
            }

            // Menutup modal saat klik di luar
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal();
                }
            });
        </script>
    <?php endif; ?>
</body>

</html>