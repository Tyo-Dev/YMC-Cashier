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

// Proses Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_level === 'admin') {
    try {
        if ($_POST['action'] === 'add') {
            $nama_pemasok = validateInput($_POST['nama_pemasok']);
            $alamat = validateInput($_POST['alamat']);
            $no_telepon = validateInput($_POST['no_telepon']);

            if (!preg_match("/^[0-9\-]+$/", $no_telepon)) {
                throw new Exception("Format nomor telepon tidak valid!");
            }

            $stmt = $pdo->prepare("INSERT INTO pemasok (nama_pemasok, alamat, no_telepon) VALUES (?, ?, ?)");
            $stmt->execute([$nama_pemasok, $alamat, $no_telepon]);
            $notification = ['type' => 'success', 'message' => 'Pemasok berhasil ditambahkan.'];
        } elseif ($_POST['action'] === 'edit') {
            $id_pemasok = filter_var($_POST['id_pemasok'], FILTER_VALIDATE_INT);
            $nama_pemasok = validateInput($_POST['nama_pemasok']);
            $alamat = validateInput($_POST['alamat']);
            $no_telepon = validateInput($_POST['no_telepon']);

            if (!preg_match("/^[0-9\-]+$/", $no_telepon)) {
                throw new Exception("Format nomor telepon tidak valid!");
            }

            $stmt = $pdo->prepare("UPDATE pemasok SET nama_pemasok = ?, alamat = ?, no_telepon = ? WHERE id_pemasok = ?");
            $stmt->execute([$nama_pemasok, $alamat, $no_telepon, $id_pemasok]);
            $notification = ['type' => 'success', 'message' => 'Data pemasok berhasil diperbarui.'];
        } elseif ($_POST['action'] === 'delete') {
            $id_pemasok = filter_var($_POST['id_pemasok'], FILTER_VALIDATE_INT);

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pembelian WHERE id_pemasok = ?");
            $stmt->execute([$id_pemasok]);
            if ($stmt->fetchColumn() > 0) {
                $notification = ['type' => 'error', 'message' => 'Pemasok tidak dapat dihapus karena memiliki data transaksi pembelian.'];
            } else {
                $stmt = $pdo->prepare("DELETE FROM pemasok WHERE id_pemasok = ?");
                $stmt->execute([$id_pemasok]);
                $notification = ['type' => 'success', 'message' => 'Pemasok berhasil dihapus.'];
            }
        }
    } catch (Exception $e) {
        $notification = ['type' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
    }
}

// Ambil data pemasok
$stmt = $pdo->query("SELECT * FROM pemasok ORDER BY nama_pemasok");
$pemasoks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung jumlah transaksi per pemasok
$transaksi_pemasok = [];
$stmt_transaksi = $pdo->prepare("SELECT COUNT(*) FROM pembelian WHERE id_pemasok = ?");
foreach ($pemasoks as $pemasok) {
    $stmt_transaksi->execute([$pemasok['id_pemasok']]);
    $transaksi_pemasok[$pemasok['id_pemasok']] = $stmt_transaksi->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pemasok - YMC</title>
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
                            Data Pemasok
                        </h1>
                    </div>
                    <?php if ($user_level === 'admin') : ?>
                        <button onclick="openModal('add')" class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-6 py-3 rounded-xl flex items-center gap-3 transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <i class="fas fa-plus-circle text-lg"></i>
                            <span class="font-medium">Tambah Pemasok</span>
                        </button>
                    <?php endif; ?>
                </header>

                <?php if ($notification) : ?>
                    <div class="mb-8 p-5 rounded-2xl border-2 backdrop-blur-sm shadow-md <?= $notification['type'] === 'success' ? 'bg-green-50/80 border-green-200 text-green-800' : 'bg-red-50/80 border-red-200 text-red-800' ?>">
                        <div class="flex items-center gap-4">
                            <i class="fas <?= $notification['type'] === 'success' ? 'fa-circle-check text-green-500' : 'fa-circle-exclamation text-red-500' ?> text-2xl"></i>
                            <p class="font-medium text-lg"><?= $notification['message'] ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mb-8">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-lg"></i>
                                <input type="text" id="searchInput" placeholder="Cari Pemasok..." class="w-full pl-12 pr-4 py-3 text-lg rounded-xl border-2 border-gray-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all bg-white/50">
                            </div>
                        </div>
                        <?php if ($user_level === 'pemilik') : ?>
                            <div>
                                <a href="cetak/cetak_pemasok.php" class="inline-flex w-full sm:w-auto justify-center items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-xl transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
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
                                    <th class="px-6 py-5 text-left text-base font-semibold text-gray-700">ID</th>
                                    <th class="px-6 py-5 text-left text-base font-semibold text-gray-700">Nama Pemasok</th>
                                    <th class="px-6 py-5 text-left text-base font-semibold text-gray-700">Alamat</th>
                                    <th class="px-6 py-5 text-left text-base font-semibold text-gray-700">No. Telepon</th>
                                    <th class="px-6 py-5 text-left text-base font-semibold text-gray-700">Total Transaksi</th>
                                    <?php if ($user_level === 'admin') : ?>
                                        <th class="px-6 py-5 text-left text-base font-semibold text-gray-700">Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100" id="tableBody">
                                <?php foreach ($pemasoks as $pemasok) : ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors duration-150">
                                        <td class="px-6 py-4 text-base text-gray-600 font-medium"><?= str_pad($pemasok['id_pemasok'], 4, '0', STR_PAD_LEFT) ?></td>
                                        <td class="px-6 py-4 text-gray-800 font-medium text-base"><?= htmlspecialchars($pemasok['nama_pemasok']) ?></td>
                                        <td class="px-6 py-4 text-base text-gray-600"><?= htmlspecialchars($pemasok['alamat']) ?></td>
                                        <td class="px-6 py-4 text-base text-gray-600"><?= htmlspecialchars($pemasok['no_telepon']) ?></td>
                                        <td class="px-6 py-4">
                                            <a href="transaksi_pembelian.php?pemasok=<?= $pemasok['id_pemasok'] ?>"
                                                class="px-3 py-1.5 text-base rounded-xl inline-flex items-center gap-2 font-medium cursor-pointer hover:opacity-80 transition-opacity
                                                <?= $transaksi_pemasok[$pemasok['id_pemasok']] > 0
                                                    ? 'bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100'
                                                    : 'bg-gray-100 text-gray-600 border border-gray-200 hover:bg-gray-200' ?>">
                                                <i class="fas fa-receipt"></i>
                                                <?= $transaksi_pemasok[$pemasok['id_pemasok']] ?>
                                            </a>
                                        </td>
                                        <?php if ($user_level === 'admin') : ?>
                                            <td class="px-6 py-4">
                                                <div class="flex gap-4">
                                                    <button onclick='openModal("edit", <?= json_encode($pemasok) ?>)' class="text-blue-600 hover:text-blue-800 bg-blue-50 p-2.5 rounded-lg hover:bg-blue-100 transition-colors">
                                                        <i class="fas fa-edit text-lg"></i>
                                                    </button>
                                                    <form method="POST" class="inline" onsubmit="return confirmDelete()">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id_pemasok" value="<?= $pemasok['id_pemasok'] ?>">
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

    <script>
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.getElementById('tableBody').getElementsByTagName('tr');
            for (let row of rows) {
                row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
            }
        });
    </script>

    <?php if ($user_level === 'admin') : ?>
        <div id="pemasokModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm hidden flex items-center justify-center z-50">
            <div class="bg-white/90 backdrop-blur-md rounded-2xl shadow-xl w-full max-w-2xl p-10 border border-gray-100 transform transition-all">
                <div class="flex justify-between items-center mb-8">
                    <h2 id="modalTitle" class="text-3xl font-bold text-gray-800 flex items-center gap-4"></h2>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl p-3 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form id="pemasokForm" method="POST" class="space-y-6">
                    <input type="hidden" name="action" id="formAction">
                    <input type="hidden" name="id_pemasok" id="formIdPemasok">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Pemasok</label>
                        <input type="text" id="formNamaPemasok" name="nama_pemasok" required class="w-full px-4 py-2.5 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                        <textarea id="formAlamat" name="alamat" required rows="3" class="w-full px-4 py-2.5 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">No. Telepon</label>
                        <input type="text" id="formNoTelepon" name="no_telepon" required pattern="[0-9\-]+" title="Gunakan angka dan tanda strip (-)" class="w-full px-4 py-2.5 text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>

                    <div class="flex justify-end gap-4 pt-4">
                        <button type="button" onclick="closeModal()" class="px-8 py-3.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all duration-300 font-medium flex items-center gap-3 text-lg">
                            <i class="fas fa-times"></i>
                            <span>Batal</span>
                        </button>
                        <button type="submit" class="px-8 py-3.5 text-white bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 rounded-xl transition-all duration-300 font-medium shadow-md hover:shadow-lg transform hover:-translate-y-0.5 flex items-center gap-3 text-lg">
                            <i class="fas fa-save"></i>
                            <span>Simpan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            const modal = document.getElementById('pemasokModal');
            const modalTitle = document.getElementById('modalTitle');
            const form = document.getElementById('pemasokForm');
            const formAction = document.getElementById('formAction');
            const formIdPemasok = document.getElementById('formIdPemasok');
            const formNamaPemasok = document.getElementById('formNamaPemasok');
            const formAlamat = document.getElementById('formAlamat');
            const formNoTelepon = document.getElementById('formNoTelepon');

            function openModal(mode, data = null) {
                form.reset();
                if (mode === 'add') {
                    modalTitle.innerHTML = '<i class="fas fa-plus-circle text-green-600"></i> <span>Tambah Pemasok</span>';
                    formAction.value = 'add';
                } else if (mode === 'edit' && data) {
                    modalTitle.innerHTML = '<i class="fas fa-edit text-blue-600"></i> <span>Edit Pemasok</span>';
                    formAction.value = 'edit';
                    formIdPemasok.value = data.id_pemasok;
                    formNamaPemasok.value = data.nama_pemasok;
                    formAlamat.value = data.alamat;
                    formNoTelepon.value = data.no_telepon;
                }
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            function confirmDelete() {
                return confirm('Apakah Anda yakin ingin menghapus pemasok ini? Data yang terhubung dengan transaksi tidak akan bisa dihapus.');
            }

            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
        </script>
    <?php endif; ?>

</body>

</html>