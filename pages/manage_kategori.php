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

<body class="bg-gray-100">

    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>

        <main class="flex-1 p-8">
            <div class="max-w-4xl mx-auto">
                <header class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">
                        <?php echo $user_level === 'admin' ? 'Manajemen Kategori' : 'Data Kategori'; ?>
                    </h1>
                    <?php if ($user_level === 'admin'): ?>
                        <button onclick="openModal('add')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                            <i class="fas fa-plus"></i>
                            <span>Tambah Kategori</span>
                        </button>
                    <?php endif; ?>
                </header>

                <?php if ($notification): ?>
                    <div class="mb-6 p-4 rounded-lg border <?= $notification['type'] === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?>">
                        <?= $notification['message'] ?>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">No</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">Kategori</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">Jumlah Barang</th>
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
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-gray-600"><?= $no++ ?></td>
                                        <td class="px-6 py-4 text-gray-800 font-medium"><?= htmlspecialchars($kategori['kategori_barang']) ?></td>
                                        <td class="px-6 py-4">
                                            <span class="px-3 py-1 text-sm rounded-full <?= $jumlah_barang > 0 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600' ?>">
                                                <?= $jumlah_barang ?> barang
                                            </span>
                                        </td>
                                        <?php if ($user_level === 'admin'): ?>
                                            <td class="px-6 py-4">
                                                <div class="flex gap-3">
                                                    <button onclick='openModal("edit", <?= json_encode($kategori) ?>)'
                                                        class="text-blue-600 hover:text-blue-800">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" class="inline" onsubmit="return confirmDelete()">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id_kategori" value="<?= $kategori['id_kategori'] ?>">
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
        </main>
    </div>

    <?php if ($user_level === 'admin'): ?>
        <!-- Modal Form -->
        <div id="kategoriModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 id="modalTitle" class="text-2xl font-bold text-gray-800"></h2>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="kategoriForm" method="POST" class="space-y-4">
                    <input type="hidden" name="action" id="formAction">
                    <input type="hidden" name="id_kategori" id="formIdKategori">

                    <div>
                        <label for="kategori_barang" class="block text-sm font-medium text-gray-700 mb-1">
                            Nama Kategori
                        </label>
                        <input type="text"
                            id="formKategori"
                            name="kategori_barang"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button"
                            onclick="closeModal()"
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