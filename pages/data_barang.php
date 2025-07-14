<?php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Cek apakah user sudah login dan memiliki akses admin
checkUserLevel('admin');

$user = $_SESSION['pengguna'];
$success = null;
$error = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    $stmt = $pdo->prepare("INSERT INTO barang (id_kategori, nama_barang, harga_beli, margin, harga_jual, stok, satuan_barang) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['id_kategori'],
                        $_POST['nama_barang'],
                        $_POST['harga_beli'],
                        $_POST['margin'],
                        $_POST['harga_jual'],
                        $_POST['stok'],
                        $_POST['satuan_barang']
                    ]);
                    $success = "Barang berhasil ditambahkan!";
                    break;
                    
                case 'edit':
                    $stmt = $pdo->prepare("UPDATE barang SET id_kategori = ?, nama_barang = ?, harga_beli = ?, margin = ?, harga_jual = ?, stok = ?, satuan_barang = ? WHERE id_barang = ?");
                    $stmt->execute([
                        $_POST['id_kategori'],
                        $_POST['nama_barang'],
                        $_POST['harga_beli'],
                        $_POST['margin'],
                        $_POST['harga_jual'],
                        $_POST['stok'],
                        $_POST['satuan_barang'],
                        $_POST['id_barang']
                    ]);
                    $success = "Barang berhasil diperbarui!";
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM barang WHERE id_barang = ?");
                    $stmt->execute([$_POST['id_barang']]);
                    $success = "Barang berhasil dihapus!";
                    break;
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Get data
$barang = getBarang($pdo);
$kategori = getKategori($pdo);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang - Sistem Kasir YMC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <nav class="bg-indigo-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="hover:text-indigo-200">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
                <h1 class="text-xl font-bold">Data Barang</h1>
            </div>
            <div class="flex items-center space-x-4">
                <span><?= htmlspecialchars($user['nama_user']) ?></span>
                <a href="../auth/logout.php" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        <?php if ($success): ?>
            <div class="bg-green-100 text-green-700 p-4 rounded mb-4">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="mb-4">
            <button onclick="openModal('addModal')" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                <i class="fas fa-plus"></i> Tambah Barang
            </button>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Beli</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Margin</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Jual</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Satuan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($barang as $index => $item): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $index + 1 ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($item['nama_barang']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($item['kategori_barang']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= formatRupiah($item['harga_beli']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $item['margin'] ?>%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= formatRupiah($item['harga_jual']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="<?= $item['stok'] <= 10 ? 'text-red-600 font-bold' : 'text-gray-900' ?>">
                                        <?= $item['stok'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($item['satuan_barang']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editItem(<?= htmlspecialchars(json_encode($item)) ?>)" class="text-indigo-600 hover:text-indigo-900 mr-2">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteItem(<?= $item['id_barang'] ?>, '<?= htmlspecialchars($item['nama_barang']) ?>')" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="addModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Tambah Barang</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Kategori</label>
                    <select name="id_kategori" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        <option value="">Pilih Kategori</option>
                        <?php foreach ($kategori as $kat): ?>
                            <option value="<?= $kat['id_kategori'] ?>"><?= htmlspecialchars($kat['kategori_barang']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nama Barang</label>
                    <input type="text" name="nama_barang" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Harga Beli</label>
                    <input type="number" name="harga_beli" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Margin (%)</label>
                    <input type="number" name="margin" step="0.01" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Harga Jual</label>
                    <input type="number" name="harga_jual" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Stok</label>
                    <input type="number" name="stok" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Satuan</label>
                    <input type="text" name="satuan_barang" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('addModal')" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Edit Barang</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id_barang" id="edit_id_barang">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Kategori</label>
                    <select name="id_kategori" id="edit_id_kategori" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        <option value="">Pilih Kategori</option>
                        <?php foreach ($kategori as $kat): ?>
                            <option value="<?= $kat['id_kategori'] ?>"><?= htmlspecialchars($kat['kategori_barang']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nama Barang</label>
                    <input type="text" name="nama_barang" id="edit_nama_barang" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Harga Beli</label>
                    <input type="number" name="harga_beli" id="edit_harga_beli" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Margin (%)</label>
                    <input type="number" name="margin" id="edit_margin" step="0.01" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Harga Jual</label>
                    <input type="number" name="harga_jual" id="edit_harga_jual" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Stok</label>
                    <input type="number" name="stok" id="edit_stok" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Satuan</label>
                    <input type="text" name="satuan_barang" id="edit_satuan_barang" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Update</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Hapus Barang</h3>
            <p class="mb-4">Apakah Anda yakin ingin menghapus barang <strong id="deleteItemName"></strong>?</p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id_barang" id="delete_id_barang">
                
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('deleteModal')" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Hapus</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        function editItem(item) {
            document.getElementById('edit_id_barang').value = item.id_barang;
            document.getElementById('edit_id_kategori').value = item.id_kategori;
            document.getElementById('edit_nama_barang').value = item.nama_barang;
            document.getElementById('edit_harga_beli').value = item.harga_beli;
            document.getElementById('edit_margin').value = item.margin;
            document.getElementById('edit_harga_jual').value = item.harga_jual;
            document.getElementById('edit_stok').value = item.stok;
            document.getElementById('edit_satuan_barang').value = item.satuan_barang;
            
            openModal('editModal');
        }

        function deleteItem(id, name) {
            document.getElementById('delete_id_barang').value = id;
            document.getElementById('deleteItemName').textContent = name;
            openModal('deleteModal');
        }

        // Auto calculate harga jual based on harga beli and margin
        document.addEventListener('DOMContentLoaded', function() {
            const hargaBeliInputs = document.querySelectorAll('input[name="harga_beli"]');
            const marginInputs = document.querySelectorAll('input[name="margin"]');
            
            function calculateHargaJual(hargaBeli, margin, hargaJualInput) {
                if (!hargaBeli || !margin) return;
                const hargaJual = parseFloat(hargaBeli) + (parseFloat(hargaBeli) * parseFloat(margin) / 100);
                hargaJualInput.value = Math.round(hargaJual);
            }
            
            function setupEventListeners(form) {
                const hargaBeliInput = form.querySelector('input[name="harga_beli"]');
                const marginInput = form.querySelector('input[name="margin"]');
                const hargaJualInput = form.querySelector('input[name="harga_jual"]');

                hargaBeliInput.addEventListener('input', () => {
                    calculateHargaJual(hargaBeliInput.value, marginInput.value, hargaJualInput);
                });

                marginInput.addEventListener('input', () => {
                    calculateHargaJual(hargaBeliInput.value, marginInput.value, hargaJualInput);
                });
            }

            // Setup for Add form
            const addForm = document.querySelector('#addModal form');
            if(addForm) setupEventListeners(addForm);

            // Setup for Edit form
            const editForm = document.getElementById('editForm');
            if(editForm) setupEventListeners(editForm);
        });
    </script>
</body>
</html>