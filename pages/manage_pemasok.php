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

            // Validasi nomor telepon
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

            // Validasi nomor telepon
            if (!preg_match("/^[0-9\-]+$/", $no_telepon)) {
                throw new Exception("Format nomor telepon tidak valid!");
            }

            $stmt = $pdo->prepare("UPDATE pemasok SET nama_pemasok = ?, alamat = ?, no_telepon = ? WHERE id_pemasok = ?");
            $stmt->execute([$nama_pemasok, $alamat, $no_telepon, $id_pemasok]);
            $notification = ['type' => 'success', 'message' => 'Data pemasok berhasil diperbarui.'];
        } elseif ($_POST['action'] === 'delete') {
            $id_pemasok = filter_var($_POST['id_pemasok'], FILTER_VALIDATE_INT);

            // Cek apakah pemasok masih memiliki transaksi pembelian
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pembelian WHERE id_pemasok = ?");
            $stmt->execute([$id_pemasok]);
            if ($stmt->fetchColumn() > 0) {
                $notification = ['type' => 'error', 'message' => 'Pemasok tidak dapat dihapus karena masih memiliki transaksi pembelian.'];
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
foreach ($pemasoks as $pemasok) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pembelian WHERE id_pemasok = ?");
    $stmt->execute([$pemasok['id_pemasok']]);
    $transaksi_pemasok[$pemasok['id_pemasok']] = $stmt->fetchColumn();
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

<body class="bg-gray-100">

    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>

        <main class="flex-1 p-8">
            <div class="max-w-7xl mx-auto">
                <header class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">Data Pemasok</h1>
                    <?php if ($user_level === 'admin'): ?>
                        <button onclick="openModal('add')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                            <i class="fas fa-plus"></i>
                            <span>Tambah Pemasok</span>
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
                                placeholder="Cari Pemasok..."
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <?php if ($user_level === 'pemilik'): ?>
                            <div>
                                <a href="cetak/cetak_pemasok.php"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                                    <i class="fas fa-file-excel mr-2"></i>
                                    <span>Unduh File Excel</span>
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
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">No</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Nama Pemasok</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Alamat</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">No. Telepon</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Jumlah Transaksi</th>
                                    <?php if ($user_level === 'admin'): ?>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100" id="tableBody">
                                <?php if (empty($pemasoks)): ?>
                                    <tr>
                                        <td colspan="<?= $user_level === 'admin' ? '6' : '5' ?>" class="px-4 py-3 text-center text-gray-500">
                                            Tidak ada data pemasok
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1;
                                    foreach ($pemasoks as $pemasok): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-600"><?= $no++ ?></td>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-800"><?= htmlspecialchars($pemasok['nama_pemasok']) ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($pemasok['alamat']) ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($pemasok['no_telepon']) ?></td>
                                            <td class="px-4 py-3 text-sm">
                                                <span class="px-2 py-1 text-sm rounded-full <?= $transaksi_pemasok[$pemasok['id_pemasok']] > 0 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600' ?>">
                                                    <?= $transaksi_pemasok[$pemasok['id_pemasok']] ?> transaksi
                                                </span>
                                            </td>
                                            <?php if ($user_level === 'admin'): ?>
                                                <td class="px-4 py-3">
                                                    <div class="flex gap-2">
                                                        <button onclick='openModal("edit", <?= json_encode($pemasok) ?>)'
                                                            class="text-blue-600 hover:text-blue-800">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form method="POST" class="inline" onsubmit="return confirmDelete()">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id_pemasok" value="<?= $pemasok['id_pemasok'] ?>">
                                                            <button type="submit" class="text-red-600 hover:text-red-800">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
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

    <!-- Script untuk fungsi pencarian - tersedia untuk semua level -->
    <script>
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.getElementById('tableBody');

        // Filter table
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
        <div id="pemasokModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-xl p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 id="modalTitle" class="text-2xl font-bold text-gray-800"></h2>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="pemasokForm" method="POST" class="space-y-4">
                    <input type="hidden" name="action" id="formAction">
                    <input type="hidden" name="id_pemasok" id="formIdPemasok">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Pemasok</label>
                        <input type="text" id="formNamaPemasok" name="nama_pemasok" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                        <textarea id="formAlamat" name="alamat" required rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">No. Telepon</label>
                        <input type="text" id="formNoTelepon" name="no_telepon" required
                            pattern="[0-9\-]+" title="Gunakan angka dan tanda strip (-)"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>

                    <div class="flex justify-end gap-3">
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
                if (mode === 'add') {
                    modalTitle.textContent = 'Tambah Pemasok Baru';
                    formAction.value = 'add';
                    form.reset();
                } else if (mode === 'edit' && data) {
                    modalTitle.textContent = 'Edit Data Pemasok';
                    formAction.value = 'edit';
                    formIdPemasok.value = data.id_pemasok;
                    formNamaPemasok.value = data.nama_pemasok;
                    formAlamat.value = data.alamat;
                    formNoTelepon.value = data.no_telepon;
                }
                modal.classList.remove('hidden');
            }

            function closeModal() {
                modal.classList.add('hidden');
                form.reset();
            }

            function confirmDelete() {
                return confirm('Apakah Anda yakin ingin menghapus pemasok ini?');
            }

            // Menutup modal saat klik di luar
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
        </script>
    <?php endif; ?>

</body>

</html>