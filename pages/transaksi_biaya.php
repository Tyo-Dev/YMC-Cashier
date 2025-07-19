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

// Query untuk mengambil data biaya operasional
$query = "SELECT * FROM biaya_operasional ORDER BY tanggal DESC";
$stmt = $pdo->prepare($query);
$stmt->execute();
$biayas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Biaya Operasional - YMC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            display: flex;
            min-height: 100vh;
        }

        main {
            flex-grow: 1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background-color: #f9fafb;
            font-weight: 600;
        }

        tr:hover {
            background-color: #f9fafb;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 50;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            width: 80%;
            max-width: 500px;
            animation: modalFadeIn 0.3s;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 15px;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .fa-edit,
        .fa-eye,
        .fa-trash-alt {
            font-size: 1.1rem;
            transition: transform 0.2s ease;
        }

        .fa-edit:hover,
        .fa-eye:hover,
        .fa-trash-alt:hover {
            transform: scale(1.2);
        }
    </style>
</head>

<body class="bg-gray-100">
    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-grow p-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Transaksi Biaya Operasional</h1>

                <div class="flex items-center space-x-3">
                    <button id="printTableBtn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                        <i class="fas fa-print mr-2"></i>
                        Cetak Tabel
                    </button>

                    <?php if ($user_level === 'admin'): ?>
                        <button id="openAddModal" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                            <i class="fas fa-plus mr-2"></i>
                            Tambah Biaya
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full" id="biayaTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Nama Biaya</th>
                                <th>Jumlah Biaya</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="biayaTableBody">
                            <?php if (count($biayas) > 0): ?>
                                <?php $no = 1;
                                foreach ($biayas as $biaya): ?>
                                    <tr>
                                        <td class="px-4 py-3"><?= $no++ ?></td>
                                        <td class="px-4 py-3"><?= date('d/m/Y', strtotime($biaya['tanggal'])) ?></td>
                                        <td class="px-4 py-3"><?= htmlspecialchars($biaya['nama_biaya']) ?></td>
                                        <td class="px-4 py-3"><?= formatRupiah($biaya['jumlah_biaya']) ?></td>
                                        <td class="px-4 py-3"><?= htmlspecialchars($biaya['keterangan']) ?></td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center space-x-2">
                                                <?php if ($user_level === 'admin'): ?>
                                                    <button class="text-blue-600 hover:text-blue-800" title="Edit" data-id="<?= $biaya['id_biaya'] ?>">
                                                        <i class="fas fa-edit edit-biaya"></i>
                                                    </button>
                                                    <button class="text-red-600 hover:text-red-800 delete-biaya" title="Hapus" data-id="<?= $biaya['id_biaya'] ?>">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                        Tidak ada data biaya operasional yang tersedia.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Tambah Biaya -->
    <div id="addBiayaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Tambah Biaya Operasional</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addBiayaForm" class="space-y-4">
                    <div>
                        <label for="nama_biaya" class="block text-sm font-medium text-gray-700">Nama Biaya</label>
                        <input type="text" id="nama_biaya" name="nama_biaya" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label for="tanggal" class="block text-sm font-medium text-gray-700">Tanggal</label>
                        <input type="date" id="tanggal" name="tanggal" value="<?= date('Y-m-d') ?>" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label for="jumlah_biaya" class="block text-sm font-medium text-gray-700">Jumlah Biaya</label>
                        <input type="number" id="jumlah_biaya" name="jumlah_biaya" min="0" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label for="keterangan" class="block text-sm font-medium text-gray-700">Keterangan</label>
                        <textarea id="keterangan" name="keterangan" rows="3" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>


                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button type="button" class="closeModal px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Batal
                        </button>
                        <button type="submit" id="saveBiayaBtn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Biaya -->
    <div id="editBiayaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Biaya Operasional</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editBiayaForm" class="space-y-4">
                    <input type="hidden" id="edit_id_biaya" name="id_biaya">

                    <div>
                        <label for="edit_nama_biaya" class="block text-sm font-medium text-gray-700">Nama Biaya</label>
                        <input type="text" id="edit_nama_biaya" name="nama_biaya" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label for="edit_tanggal" class="block text-sm font-medium text-gray-700">Tanggal</label>
                        <input type="date" id="edit_tanggal" name="tanggal" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label for="edit_jumlah_biaya" class="block text-sm font-medium text-gray-700">Jumlah Biaya</label>
                        <input type="number" id="edit_jumlah_biaya" name="jumlah_biaya" min="0" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label for="edit_keterangan" class="block text-sm font-medium text-gray-700">Keterangan</label>
                        <textarea id="edit_keterangan" name="keterangan" rows="3" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button type="button" class="closeModal px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Batal
                        </button>
                        <button type="submit" id="updateBiayaBtn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
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
                <p class="mb-4">Apakah Anda yakin ingin menghapus biaya operasional ini?</p>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // DOM elements
            const addModal = document.getElementById('addBiayaModal');
            const editModal = document.getElementById('editBiayaModal');
            const deleteModal = document.getElementById('deleteConfirmModal');
            const openAddModalBtn = document.getElementById('openAddModal');
            const closeButtons = document.querySelectorAll('.close, .closeModal');
            const addBiayaForm = document.getElementById('addBiayaForm');
            const editBiayaForm = document.getElementById('editBiayaForm');
            const printTableBtn = document.getElementById('printTableBtn');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

            // Functions for modal handling
            function showModal(modal) {
                modal.style.display = 'block';
            }

            function hideModal(modal) {
                modal.style.display = 'none';
            }

            // Format rupiah
            function formatRupiah(angka) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0,
                }).format(angka);
            }

            // Open add modal
            if (openAddModalBtn) {
                openAddModalBtn.addEventListener('click', function() {
                    showModal(addModal);
                });
            }

            // Close modals
            closeButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    hideModal(addModal);
                    hideModal(editModal);
                    hideModal(deleteModal);
                });
            });

            // When clicking outside modal, close it
            window.addEventListener('click', function(event) {
                if (event.target === addModal) hideModal(addModal);
                if (event.target === editModal) hideModal(editModal);
                if (event.target === deleteModal) hideModal(deleteModal);
            });

            // Add biaya operasional form submit
            addBiayaForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = {
                    nama_biaya: document.getElementById('nama_biaya').value,
                    tanggal: document.getElementById('tanggal').value,
                    jumlah_biaya: document.getElementById('jumlah_biaya').value,
                    keterangan: document.getElementById('keterangan').value
                };
                fetch('ajax/save_biaya.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(formData),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Biaya operasional berhasil disimpan!');
                            hideModal(addModal);
                            location.reload(); // Reload page to show new entry
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat menyimpan data');
                    });
            });

            // Edit biaya operasional
            document.querySelectorAll('.edit-biaya').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.closest('button').dataset.id;

                    fetch(`ajax/get_biaya.php?id=${id}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('edit_id_biaya').value = data.biaya.id_biaya;
                                document.getElementById('edit_nama_biaya').value = data.biaya.nama_biaya;
                                document.getElementById('edit_tanggal').value = data.biaya.tanggal;
                                document.getElementById('edit_jumlah_biaya').value = data.biaya.jumlah_biaya;
                                document.getElementById('edit_keterangan').value = data.biaya.keterangan;

                                showModal(editModal);
                            } else {
                                alert('Error: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Terjadi kesalahan saat mengambil data');
                        });
                });
            });

            // Submit edit form
            editBiayaForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = {
                    id_biaya: document.getElementById('edit_id_biaya').value,
                    nama_biaya: document.getElementById('edit_nama_biaya').value,
                    tanggal: document.getElementById('edit_tanggal').value,
                    jumlah_biaya: document.getElementById('edit_jumlah_biaya').value,
                    keterangan: document.getElementById('edit_keterangan').value
                };
                fetch('ajax/update_biaya.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(formData),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Biaya operasional berhasil diperbarui!');
                            hideModal(editModal);
                            location.reload(); // Reload page to show updated data
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat memperbarui data');
                    });
            });

            // Delete biaya operasional
            document.querySelectorAll('.delete-biaya').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    confirmDeleteBtn.dataset.id = id;
                    showModal(deleteModal);
                });
            });

            // Confirm delete
            confirmDeleteBtn.addEventListener('click', function() {
                const id = this.dataset.id;

                fetch(`ajax/delete_biaya.php?id=${id}`, {
                        method: 'DELETE'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Biaya operasional berhasil dihapus!');
                            hideModal(deleteModal);
                            location.reload(); // Reload page to update list
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat menghapus data');
                    });
            });

            // Print table
            printTableBtn.addEventListener('click', function() {
                const printWindow = window.open('', '', 'height=600,width=800');
                const table = document.getElementById('biayaTable').cloneNode(true);

                // Remove action column
                const actionColumnIndex = [...table.rows[0].cells].findIndex(cell =>
                    cell.textContent.trim().toLowerCase() === 'aksi'
                );

                if (actionColumnIndex !== -1) {
                    [...table.rows].forEach(row => {
                        if (row.cells[actionColumnIndex]) {
                            row.deleteCell(actionColumnIndex);
                        }
                    });
                }

                const html = `
                    <!DOCTYPE html>
                    <html lang="id">
                    <head>
                        <meta charset="UTF-8">
                        <title>Laporan Biaya Operasional</title>
                        <style>
                            body {
                                font-family: Arial, sans-serif;
                                margin: 2cm;
                            }
                            .header {
                                text-align: center;
                                margin-bottom: 20px;
                            }
                            .header h1 {
                                margin: 0;
                                font-size: 18px;
                                font-weight: bold;
                            }
                            .header p {
                                margin: 5px 0;
                                font-size: 14px;
                            }
                            table {
                                width: 100%;
                                border-collapse: collapse;
                                margin-top: 20px;
                            }
                            th, td {
                                border: 1px solid #000;
                                padding: 8px;
                                font-size: 12px;
                                text-align: left;
                            }
                            th {
                                background-color: #f2f2f2;
                                font-weight: bold;
                            }
                            tr:nth-child(even) {
                                background-color: #f9f9f9;
                            }
                            .footer {
                                margin-top: 30px;
                                text-align: right;
                                font-size: 12px;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <h1>LAPORAN BIAYA OPERASIONAL</h1>
                            <p>YMC - Yumna Moslem Collection</p>
                            <p>Tanggal Cetak: ${new Date().toLocaleDateString('id-ID', {
                                day: 'numeric',
                                month: 'long',
                                year: 'numeric'
                            })}</p>
                        </div>
                        ${table.outerHTML}
                        <div class="footer">
                            <p>Dicetak oleh: <?= $user_level ?></p>
                        </div>
                    </body>
                    </html>
                `;

                printWindow.document.write(html);
                printWindow.document.close();

                // Wait for window to load before printing
                printWindow.onload = function() {
                    printWindow.print();
                    printWindow.onafterprint = function() {
                        printWindow.close();
                    };
                };
            });
        });
    </script>
</body>

</html>