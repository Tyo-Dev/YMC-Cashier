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
            $nama_barang = validateInput($_POST['nama_barang']);
            $id_kategori = filter_var($_POST['id_kategori'], FILTER_VALIDATE_INT);
            $harga_beli = filter_var($_POST['harga_beli'], FILTER_VALIDATE_INT);
            $margin = filter_var($_POST['margin'], FILTER_VALIDATE_FLOAT);
            $harga_jual = filter_var($_POST['harga_jual'], FILTER_VALIDATE_INT);
            $stok = filter_var($_POST['stok'], FILTER_VALIDATE_INT);
            $satuan = validateInput($_POST['satuan']);

            $stmt = $pdo->prepare("INSERT INTO barang (nama_barang, id_kategori, harga_beli, margin, harga_jual, stok, satuan_barang) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nama_barang, $id_kategori, $harga_beli, $margin, $harga_jual, $stok, $satuan]);
            $notification = ['type' => 'success', 'message' => 'Barang berhasil ditambahkan.'];
        } elseif ($_POST['action'] === 'edit') {
            $id_barang = filter_var($_POST['id_barang'], FILTER_VALIDATE_INT);
            $nama_barang = validateInput($_POST['nama_barang']);
            $id_kategori = filter_var($_POST['id_kategori'], FILTER_VALIDATE_INT);
            $harga_beli = filter_var($_POST['harga_beli'], FILTER_VALIDATE_INT);
            $margin = filter_var($_POST['margin'], FILTER_VALIDATE_FLOAT);
            $harga_jual = filter_var($_POST['harga_jual'], FILTER_VALIDATE_INT);
            $stok = filter_var($_POST['stok'], FILTER_VALIDATE_INT);
            $satuan = validateInput($_POST['satuan']);

            $stmt = $pdo->prepare("UPDATE barang SET nama_barang = ?, id_kategori = ?, harga_beli = ?, margin = ?, harga_jual = ?, stok = ?, satuan_barang = ? WHERE id_barang = ?");
            $stmt->execute([$nama_barang, $id_kategori, $harga_beli, $margin, $harga_jual, $stok, $satuan, $id_barang]);
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
    <title>Data Barang - YMC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body class="bg-gray-100">

    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>

        <main class="flex-1 p-8">
            <div class="max-w-7xl mx-auto">
                <header class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">
                        Data Barang
                    </h1>
                    <?php if ($user_level === 'admin'): ?>
                        <button onclick="openModal('add')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                            <i class="fas fa-plus"></i>
                            <span>Tambah Barang</span>
                        </button>
                    <?php endif; ?>
                </header>

                <?php if ($notification): ?>
                    <div class="mb-6 p-4 rounded-lg border <?= $notification['type'] === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?>">
                        <?= $notification['message'] ?>
                    </div>
                <?php endif; ?>

                <!-- Search Bar -->
                <div class="mb-6">
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <input type="text"
                                id="searchInput"
                                placeholder="Cari Barang..."
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <?php if ($user_level === 'pemilik'): ?>
                            <div>
                                <a href="cetak/cetak_barang.php"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                                    <i class="fas fa-file-excel mr-2"></i>
                                    <span>Unduh Laporan Excel</span>
                                </a>
                            </div>
                        <?php endif; ?>
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
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Harga Beli</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Margin</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Harga Jual</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Stok</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Satuan</th>
                                    <?php if ($user_level === 'admin'): ?>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100" id="tableBody">
                                <?php foreach ($barangs as $barang): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-600"><?= str_pad($barang['id_barang'], 6, '0', STR_PAD_LEFT) ?></td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-800"><?= htmlspecialchars($barang['nama_barang']) ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($barang['kategori_barang']) ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-600"><?= formatRupiah($barang['harga_beli']) ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-600"><?= number_format($barang['margin'], 2) ?>%</td>
                                        <td class="px-4 py-3 text-sm text-gray-600"><?= formatRupiah($barang['harga_jual']) ?></td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="px-2 py-1 text-sm rounded-full <?= $barang['stok'] > 10 ? 'bg-green-100 text-green-800' : ($barang['stok'] > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                                <?= $barang['stok'] ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($barang['satuan_barang']) ?></td>
                                        <?php if ($user_level === 'admin'): ?>
                                            <td class="px-4 py-3">
                                                <div class="flex gap-2">
                                                    <button onclick='openModal("edit", <?= json_encode($barang) ?>)'
                                                        class="text-blue-600 hover:text-blue-800">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" class="inline" onsubmit="return confirmDelete()">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id_barang" value="<?= $barang['id_barang'] ?>">
                                                        <button type="submit" class="text-red-600 hover:text-red-800">
                                                            <i class="fas fa-trash"></i>
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

    <!-- Script untuk fungsi pencarian - tersedia untuk semua level -->
    <script>
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.getElementById('tableBody');

        // Filter table - fungsi search
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = tableBody.getElementsByTagName('tr');

            for (let row of rows) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            }
        });
    </script>

    <?php if ($user_level === 'admin'): ?>
        <!-- Modal Form -->
        <div id="barangModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-xl p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 id="modalTitle" class="text-2xl font-bold text-gray-800"></h2>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="barangForm" method="POST" class="space-y-4">
                    <input type="hidden" name="action" id="formAction">
                    <input type="hidden" name="id_barang" id="formIdBarang">

                    <div class="grid grid-cols-2 gap-4">
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

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" onclick="closeModal()"
                            class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">
                            Simpan
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
            const formIdBarang = document.getElementById('formIdBarang');
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
                } else if (mode === 'edit' && data) {
                    modalTitle.textContent = 'Edit Data Barang';
                    formAction.value = 'edit';
                    formIdBarang.value = data.id_barang;
                    formNamaBarang.value = data.nama_barang;
                    document.getElementById('formKategori').value = data.id_kategori;
                    formHargaBeli.value = data.harga_beli;
                    formMargin.value = data.margin;
                    formHargaJual.value = data.harga_jual;
                    formStok.value = data.stok;
                    document.getElementById('formSatuan').value = data.satuan_barang;
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
                if (e.target === modal) closeModal();
            });
        </script>
    <?php endif; ?>

</body>

</html>