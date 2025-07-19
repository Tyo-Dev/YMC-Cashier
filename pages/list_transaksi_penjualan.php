<?php
session_start();
require_once '../includes/functions.php';
checkUserLevel(['admin', 'pemilik', 'kasir']); // Admin, Pemilik, dan Kasir bisa akses

// Ambil level pengguna dari session
$user_level = $_SESSION['pengguna']['level'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Penjualan - YMC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            display: flex;
        }

        main {
            flex-grow: 1;
        }

        .table-container {
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            overflow: hidden;
        }

        /* Custom select styling */
        .custom-select {
            position: relative;
            width: 100%;
        }

        .custom-select select {
            appearance: none;
            -webkit-appearance: none;
            width: 100%;
            padding: 0.5rem 2.5rem 0.5rem 1rem;
            font-size: 0.875rem;
            line-height: 1.5;
            background-color: #fff;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            color: #1f2937;
            cursor: pointer;
            outline: none;
            transition: all 0.15s ease-in-out;
        }

        .custom-select select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }

        .custom-select::after {
            content: '\f0d7';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            pointer-events: none;
        }

        /* Modal styles */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
        }

        .modal {
            background-color: white;
            border-radius: 0.5rem;
            width: 100%;
            max-width: 32rem;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
    </style>
</head>

<body class="bg-gray-100">
    <?php include '../includes/sidebar.php'; ?>

    <main class="w-full p-4 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <header class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <h1 class="text-3xl font-bold text-gray-800">Daftar Penjualan</h1>
                <div class="flex items-center gap-4 w-full md:w-auto">
                    <div class="custom-select w-full md:w-60">
                        <select id="kasirFilter" class="focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Kasir</option>
                        </select>
                    </div>
                    <?php if ($user_level === 'kasir'): ?>
                        <a href="transaksi_penjualan.php" class="w-full md:w-auto flex-shrink-0 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition text-center">
                            <i class="fas fa-plus mr-2"></i>Tambah Penjualan
                        </a>
                    <?php endif; ?>
                </div>
            </header>

            <div class="table-container">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="p-4 text-left font-semibold text-gray-600">No. Transaksi</th>
                                <th class="p-4 text-left font-semibold text-gray-600">Tanggal</th>
                                <th class="p-4 text-left font-semibold text-gray-600">Kasir</th>
                                <th class="p-4 text-center font-semibold text-gray-600">Total Item</th>
                                <th class="p-4 text-right font-semibold text-gray-600">Total Harga</th>
                                <th class="p-4 text-center font-semibold text-gray-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="salesTableBody">
                        </tbody>
                    </table>
                    <div id="loadingIndicator" class="text-center p-8">
                        <p class="text-gray-500">Memuat data...</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Transaction Modal -->
    <div id="editTransactionModal" class="modal-backdrop hidden">
        <div class="modal">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Edit Transaksi</h3>
                    <button type="button" id="closeModal" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-content">
                    <!-- Modal content will be loaded dynamically -->
                    <div id="modalLoadingIndicator" class="py-10 text-center">
                        <i class="fas fa-spinner fa-spin text-blue-500 text-2xl mb-2"></i>
                        <p>Memuat detail transaksi...</p>
                    </div>
                    <div id="transactionDetails" class="hidden">
                        <!-- Transaction details will be inserted here -->
                    </div>
                </div>
                <div class="flex justify-end mt-6 gap-2">
                    <button id="saveChangesBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Simpan Perubahan
                    </button>
                    <button id="cancelEditBtn" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const salesTableBody = document.getElementById('salesTableBody');
            const kasirFilter = document.getElementById('kasirFilter');
            const loadingIndicator = document.getElementById('loadingIndicator');
            const editTransactionModal = document.getElementById('editTransactionModal');
            const closeModal = document.getElementById('closeModal');
            const cancelEditBtn = document.getElementById('cancelEditBtn');
            const modalLoadingIndicator = document.getElementById('modalLoadingIndicator');
            const transactionDetails = document.getElementById('transactionDetails');
            const saveChangesBtn = document.getElementById('saveChangesBtn');

            // Store user level in JS for access control in client-side
            const userLevel = "<?php echo $user_level; ?>";
            let currentTransactionId = null;

            const formatRupiah = (angka) => new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(angka);

            const fetchSales = async (kasirId = '') => {
                loadingIndicator.style.display = 'block';
                salesTableBody.innerHTML = '';

                try {
                    const response = await fetch(`ajax/get_list_penjualan.php?kasir=${kasirId}`);
                    const sales = await response.json();

                    if (sales.length === 0) {
                        salesTableBody.innerHTML = `<tr><td colspan="6" class="text-center p-8 text-gray-500">Tidak ada data penjualan.</td></tr>`;
                    } else {
                        sales.forEach(sale => {
                            // Build actions based on user level
                            let actionButtons = `
                                <a href="cetak/nota_penjualan.php?id=${sale.id_penjualan}" target="_blank" class="text-green-500 hover:text-green-700" title="Lihat Nota">
                                    <i class="fas fa-eye"></i>
                                </a>
                            `;

                            // Only kasir can edit transactions (formerly delete)
                            if (userLevel === 'kasir') {
                                actionButtons += `
                                    <button onclick="editTransaction(${sale.id_penjualan})" class="text-blue-500 hover:text-blue-700 ml-3" title="Edit Transaksi">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                `;
                            }

                            const row = `
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-4 font-mono text-blue-600">${sale.no_transaksi}</td>
                                    <td class="p-4 text-gray-700">${new Date(sale.tanggal).toLocaleString('id-ID')}</td>
                                    <td class="p-4 text-gray-700">${sale.nama_kasir}</td>
                                    <td class="p-4 text-center text-gray-700">${sale.total_item}</td>
                                    <td class="p-4 text-right font-semibold text-gray-800">${formatRupiah(sale.total_harga)}</td>
                                    <td class="p-4 text-center">
                                        <div class="flex justify-center gap-2">
                                            ${actionButtons}
                                        </div>
                                    </td>
                                </tr>
                            `;
                            salesTableBody.innerHTML += row;
                        });
                    }
                } catch (error) {
                    console.error('Error fetching sales:', error);
                    salesTableBody.innerHTML = `<tr><td colspan="6" class="text-center p-8 text-red-500">Gagal memuat data.</td></tr>`;
                } finally {
                    loadingIndicator.style.display = 'none';
                }
            };

            const fetchKasirList = async () => {
                try {
                    const response = await fetch('ajax/get_kasir_list.php');
                    const kasirList = await response.json();

                    if (Array.isArray(kasirList)) {
                        kasirList.forEach(kasir => {
                            const option = document.createElement('option');
                            if (typeof kasir === 'object' && kasir !== null) {
                                option.value = kasir.id_user || kasir.id_pengguna || '';
                                option.textContent = kasir.nama_user || kasir.nama || '';
                            } else {
                                option.value = '';
                                option.textContent = kasir;
                            }
                            kasirFilter.appendChild(option);
                        });
                    } else {
                        console.error('Expected array of kasir but got:', kasirList);
                    }
                } catch (error) {
                    console.error('Error fetching kasir list:', error);
                }
            };

            // Function to fetch transaction details for editing
            window.editTransaction = async (id) => {
                // Double check user level for security
                if (userLevel !== 'kasir') {
                    alert('Anda tidak memiliki izin untuk mengedit transaksi.');
                    return;
                }

                currentTransactionId = id;
                editTransactionModal.classList.remove('hidden');
                modalLoadingIndicator.classList.remove('hidden');
                transactionDetails.classList.add('hidden');

                try {
                    const response = await fetch(`ajax/get_transaction_details.php?id=${id}`);
                    const data = await response.json();

                    if (data.success) {
                        console.log("Transaction data:", data); // Debug info
                        const transaction = data.transaction;
                        const items = data.items;

                        // Generate transaction details HTML
                        let itemsHtml = '';
                        items.forEach(item => {
                            // Get the ID field from the returned item, using debug info if needed
                            const itemId = item.id_detail || item[data.debug_info.id_column_used];

                            itemsHtml += `
                                <div class="flex justify-between items-center border-b py-2" data-item-id="${itemId}">
                                    <div class="flex-1">
                                        <p class="font-medium">${item.nama_barang}</p>
                                        <div class="flex items-center mt-1">
                                            <input type="number" class="item-qty w-16 px-2 py-1 border rounded mr-2" 
                                                value="${item.jumlah}" min="1" max="99">
                                            <span class="text-gray-600">x ${formatRupiah(item.harga_jual)}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-medium item-subtotal">${formatRupiah(item.jumlah * item.harga_jual)}</p>
                                        <button class="remove-item text-red-500 hover:text-red-700 text-sm mt-1">
                                            <i class="fas fa-trash-alt mr-1"></i>Hapus
                                        </button>
                                    </div>
                                </div>
                            `;
                        });

                        transactionDetails.innerHTML = `
                            <div class="mb-4 pb-3 border-b">
                                <div class="flex justify-between">
                                    <p class="text-gray-600">No. Transaksi:</p>
                                    <p class="font-medium">${transaction.no_transaksi}</p>
                                </div>
                                <div class="flex justify-between mt-1">
                                    <p class="text-gray-600">Tanggal:</p>
                                    <p class="font-medium">${new Date(transaction.tanggal).toLocaleString('id-ID')}</p>
                                </div>
                            </div>
                            
                            <h4 class="font-bold text-gray-800 mb-2">Detail Item</h4>
                            <div class="max-h-64 overflow-y-auto">
                                ${itemsHtml}
                            </div>
                            
                            <div class="mt-4 pt-3 border-t">
                                <div class="flex justify-between">
                                    <p class="text-gray-600">Total:</p>
                                    <p class="font-bold text-lg" id="totalAmount">${formatRupiah(transaction.total_harga)}</p>
                                </div>
                                ${data.debug_info ? `
                                <div class="mt-2 text-xs text-gray-500">
                                    <p>ID Column: ${data.debug_info.id_column_used}</p>
                                    <p>Columns: ${data.debug_info.table_columns.join(', ')}</p>
                                </div>` : ''}
                            </div>
                        `;

                        // Show transaction details
                        modalLoadingIndicator.classList.add('hidden');
                        transactionDetails.classList.remove('hidden');

                        // Add event listeners for quantity changes
                        document.querySelectorAll('.item-qty').forEach(input => {
                            input.addEventListener('change', updateItemSubtotal);
                        });

                        // Add event listeners for item removal
                        document.querySelectorAll('.remove-item').forEach(button => {
                            button.addEventListener('click', removeItem);
                        });

                    } else {
                        throw new Error(data.message || 'Gagal memuat detail transaksi');
                    }
                } catch (error) {
                    console.error('Error loading transaction details:', error);
                    transactionDetails.innerHTML = `
                        <div class="text-center py-6 text-red-500">
                            <p>Gagal memuat detail transaksi.</p>
                            <p class="text-sm">${error.message}</p>
                        </div>
                    `;
                    modalLoadingIndicator.classList.add('hidden');
                    transactionDetails.classList.remove('hidden');
                }
            };

            // Function to update item subtotal when quantity changes
            function updateItemSubtotal() {
                const itemContainer = this.closest('[data-item-id]');
                const quantity = parseInt(this.value) || 0;
                const priceText = this.nextElementSibling.textContent;
                const price = parseInt(priceText.replace(/[^\d]/g, ''));

                const subtotal = quantity * price;
                itemContainer.querySelector('.item-subtotal').textContent = formatRupiah(subtotal);

                updateTotalAmount();
            }

            // Function to remove item from the transaction
            function removeItem() {
                const itemContainer = this.closest('[data-item-id]');
                itemContainer.style.display = 'none';
                itemContainer.dataset.removed = 'true';

                updateTotalAmount();
            }

            // Function to update the total amount
            function updateTotalAmount() {
                let total = 0;

                document.querySelectorAll('[data-item-id]').forEach(item => {
                    if (item.dataset.removed !== 'true') {
                        const subtotalElement = item.querySelector('.item-subtotal');
                        const subtotalText = subtotalElement.textContent;
                        const subtotal = parseInt(subtotalText.replace(/[^\d]/g, ''));
                        total += subtotal;
                    }
                });

                document.getElementById('totalAmount').textContent = formatRupiah(total);
            }

            // Save changes to the transaction
            saveChangesBtn.addEventListener('click', async () => {
                if (!currentTransactionId) return;

                const updatedItems = [];
                const removedItems = [];

                document.querySelectorAll('[data-item-id]').forEach(item => {
                    const itemId = item.dataset.itemId;

                    if (item.dataset.removed === 'true') {
                        removedItems.push(itemId);
                    } else {
                        const quantity = parseInt(item.querySelector('.item-qty').value) || 0;
                        updatedItems.push({
                            id_detail: itemId,
                            jumlah: quantity
                        });
                    }
                });

                try {
                    const response = await fetch('ajax/update_transaction.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id_penjualan: currentTransactionId,
                            updated_items: updatedItems,
                            removed_items: removedItems
                        })
                    });

                    const result = await response.json();
                    console.log("Update result:", result); // Debug info

                    if (result.success) {
                        alert('Transaksi berhasil diperbarui.');
                        closeEditModal();
                        fetchSales(kasirFilter.value); // Refresh table
                    } else {
                        throw new Error(result.message || 'Gagal memperbarui transaksi');
                    }
                } catch (error) {
                    alert(`Gagal memperbarui transaksi: ${error.message}`);
                    console.error('Error updating transaction:', error);
                }
            });

            // Close modal functions
            function closeEditModal() {
                editTransactionModal.classList.add('hidden');
                currentTransactionId = null;
            }

            closeModal.addEventListener('click', closeEditModal);
            cancelEditBtn.addEventListener('click', closeEditModal);

            // Close modal when clicking outside
            editTransactionModal.addEventListener('click', (e) => {
                if (e.target === editTransactionModal) {
                    closeEditModal();
                }
            });

            kasirFilter.addEventListener('change', () => fetchSales(kasirFilter.value));

            fetchKasirList();
            fetchSales();
        });
    </script>
</body>

</html>