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
        // Tambah Kategori
        if ($_POST['action'] === 'add') {
            $kategori = validateInput($_POST['kategori_barang']);

            // Cek apakah kategori sudah ada
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM kategori WHERE kategori_barang = ?");
            $stmt->execute([$kategori]);
            if ($stmt->fetchColumn() > 0) {
                $notification = ['type' => 'error', 'message' => 'Kategori sudah ada!'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO kategori (kategori_barang) VALUES (?)");
                $stmt->execute([$kategori]);
                $notification = ['type' => 'success', 'message' => 'Kategori baru berhasil ditambahkan.'];
            }
        }

        // Edit Kategori
        elseif ($_POST['action'] === 'edit') {
            $id_kategori = filter_var($_POST['id_kategori'], FILTER_VALIDATE_INT);
            $kategori = validateInput($_POST['kategori_barang']);

            // Cek duplikasi kategori
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM kategori WHERE kategori_barang = ? AND id_kategori != ?");
            $stmt->execute([$kategori, $id_kategori]);
            if ($stmt->fetchColumn() > 0) {
                $notification = ['type' => 'error', 'message' => 'Kategori sudah ada!'];
            } else {
                $stmt = $pdo->prepare("UPDATE kategori SET kategori_barang = ? WHERE id_kategori = ?");
                $stmt->execute([$kategori, $id_kategori]);
                $notification = ['type' => 'success', 'message' => 'Kategori berhasil diperbarui.'];
            }
        }

        // Hapus Kategori
        elseif ($_POST['action'] === 'delete') {
            $id_kategori = filter_var($_POST['id_kategori'], FILTER_VALIDATE_INT);

            // Cek apakah kategori masih digunakan di tabel barang
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM barang WHERE id_kategori = ?");
            $stmt->execute([$id_kategori]);
            if ($stmt->fetchColumn() > 0) {
                $notification = ['type' => 'error', 'message' => 'Kategori tidak dapat dihapus karena masih digunakan oleh beberapa barang.'];
            } else {
                $stmt = $pdo->prepare("DELETE FROM kategori WHERE id_kategori = ?");
                $stmt->execute([$id_kategori]);
                $notification = ['type' => 'success', 'message' => 'Kategori berhasil dihapus.'];
            }
        }
    } catch (PDOException $e) {
        $notification = ['type' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
    }
}

// Ambil semua data kategori
$stmt = $pdo->query("SELECT * FROM kategori ORDER BY kategori_barang");
$kategoris = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kategori - YMC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">

    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>

        <main class="flex-1 p-6 sm:p-8 lg:p-10">
            <div class="max-w-6xl mx-auto">
                <header class="flex flex-col sm:flex-row justify-between items-center mb-10 gap-4">
                    <div class="flex items-center gap-4">
                        <h1 class="text-3xl sm:text-4xl font-bold text-gray-800 bg-white py-3 px-6 rounded-2xl shadow-md border border-gray-100">
                            <?php echo $user_level === 'admin' ? 'Manajemen Kategori' : 'Data Kategori'; ?>
                        </h1>
                    </div>
                    <?php if ($user_level === 'admin'): ?>
                        <button onclick="openModal('add')"
                            class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-6 py-3 rounded-xl flex items-center gap-3 transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <i class="fas fa-plus-circle text-lg"></i>
                            <span class="font-medium">Tambah Kategori</span>
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

                <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden border border-gray-100">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                            <tr>
                                <th class="px-8 py-5 text-left text-base font-semibold text-gray-700">No</th>
                                <th class="px-8 py-5 text-left text-base font-semibold text-gray-700">Kategori</th>
                                <th class="px-8 py-5 text-left text-base font-semibold text-gray-700">Jumlah Barang</th>
                                <?php if ($user_level === 'admin'): ?>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($kategoris)): ?>
                                <tr>
                                    <td colspan="<?= $user_level === 'admin' ? '4' : '3' ?>" class="px-6 py-4 text-center text-gray-500">
                                        Tidak ada data kategori
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1;
                                foreach ($kategoris as $kategori):
                                    // Hitung jumlah barang per kategori
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM barang WHERE id_kategori = ?");
                                    $stmt->execute([$kategori['id_kategori']]);
                                    $jumlah_barang = $stmt->fetchColumn();
                                ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors duration-150">
                                        <td class="px-8 py-5 text-gray-600 text-lg"><?= $no++ ?></td>
                                        <td class="px-8 py-5">
                                            <span class="font-medium text-gray-800 bg-gray-50 px-5 py-2 rounded-xl text-lg inline-block">
                                                <?= htmlspecialchars($kategori['kategori_barang']) ?>
                                            </span>
                                        </td>
                                        <td class="px-8 py-5">
                                            <span class="px-5 py-2 text-base rounded-xl inline-flex items-center gap-3 font-medium <?= $jumlah_barang > 0 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-gray-50 text-gray-600 border border-gray-200' ?>">
                                                <i class="fas <?= $jumlah_barang > 0 ? 'fa-box text-blue-500' : 'fa-box text-gray-400' ?> text-lg"></i>
                                                <?= $jumlah_barang ?> barang
                                            </span>
                                        </td>
                                        <?php if ($user_level === 'admin'): ?>
                                            <td class="px-8 py-5">
                                                <div class="flex gap-4">
                                                    <button onclick='openModal("edit", <?= json_encode($kategori) ?>)'
                                                        class="text-blue-600 hover:text-blue-800 bg-blue-50 p-3 rounded-xl hover:bg-blue-100 transition-colors">
                                                        <i class="fas fa-edit text-lg"></i>
                                                    </button>
                                                    <form method="POST" class="inline" onsubmit="return confirmDelete()">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id_kategori" value="<?= $kategori['id_kategori'] ?>">
                                                        <button type="submit" class="text-red-600 hover:text-red-800 bg-red-50 p-3 rounded-xl hover:bg-red-100 transition-colors">
                                                            <i class="fas fa-trash text-lg"></i>
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
        </main>
    </div>

    <?php if ($user_level === 'admin'): ?>
        <!-- Modal Form -->
        <div id="kategoriModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm hidden flex items-center justify-center z-50">
            <div class="bg-white/90 backdrop-blur-md rounded-2xl shadow-xl w-full max-w-lg p-10 border border-gray-100 transform transition-all">
                <div class="flex justify-between items-center mb-8">
                    <h2 id="modalTitle" class="text-3xl font-bold text-gray-800 flex items-center gap-4">
                        <i class="fas fa-tag text-green-600 text-2xl"></i>
                        <span></span>
                    </h2>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl p-3 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form id="kategoriForm" method="POST" class="space-y-6">
                    <input type="hidden" name="action" id="formAction">
                    <input type="hidden" name="id_kategori" id="formIdKategori">

                    <div>
                        <label for="kategori_barang" class="block text-base font-semibold text-gray-700 mb-3">
                            Nama Kategori
                        </label>
                        <div class="relative">
                            <i class="fas fa-tag absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-xl"></i>
                            <input type="text"
                                id="formKategori"
                                name="kategori_barang"
                                required
                                placeholder="Masukkan nama kategori"
                                class="w-full pl-12 pr-4 py-4 text-lg border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all bg-white/50">
                        </div>
                    </div>

                    <div class="flex justify-end gap-4 mt-10">
                        <button type="button"
                            onclick="closeModal()"
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

        <script>
            const modal = document.getElementById('kategoriModal');
            const modalTitle = document.getElementById('modalTitle');
            const form = document.getElementById('kategoriForm');
            const formAction = document.getElementById('formAction');
            const formIdKategori = document.getElementById('formIdKategori');
            const formKategori = document.getElementById('formKategori');

            function openModal(mode, data = null) {
                if (mode === 'add') {
                    modalTitle.textContent = 'Tambah Kategori Baru';
                    formAction.value = 'add';
                    formIdKategori.value = '';
                    formKategori.value = '';
                } else if (mode === 'edit' && data) {
                    modalTitle.textContent = 'Edit Kategori';
                    formAction.value = 'edit';
                    formIdKategori.value = data.id_kategori;
                    formKategori.value = data.kategori_barang;
                }
                modal.classList.remove('hidden');
            }

            function closeModal() {
                modal.classList.add('hidden');
                form.reset();
            }

            function confirmDelete() {
                return confirm('Apakah Anda yakin ingin menghapus kategori ini?');
            }

            // Menutup modal saat klik di luar
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
        </script>
    <?php endif; ?>

</body>

</html>