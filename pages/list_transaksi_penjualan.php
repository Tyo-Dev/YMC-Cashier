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
            min-height: 100vh;
            background-color: #f9fafb;
        }

        main {
            flex-grow: 1;
        }

        .table-container {
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid rgba(229, 231, 235, 0.7);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .table-container:hover {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
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
            padding: 0.75rem 2.5rem 0.75rem 1rem;
            font-size: 0.875rem;
            line-height: 1.5;
            background-color: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            color: #1f2937;
            cursor: pointer;
            outline: none;
            transition: all 0.15s ease-in-out;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
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

        /* Table styling */
        table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        
        thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: linear-gradient(to right, #f9fafb, #f3f4f6);
        }
        
        tbody tr:hover {
            background-color: #f8fafc;
        }
        
        tbody td {
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s;
        }

        /* Modal styles */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        
        .modal-backdrop:not(.hidden) {
            opacity: 1;
        }

        .modal {
            background-color: white;
            border-radius: 1rem;
            width: 100%;
            max-width: 40rem;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 10px 20px -5px rgba(0, 0, 0, 0.1);
            transform: translateY(20px) scale(0.98);
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            border: 1px solid rgba(229, 231, 235, 1); /* gray-200 */
        }
        
        .modal-backdrop:not(.hidden) .modal {
            transform: translateY(0) scale(1);
        }
        
        /* Scrollbar styling for modal */
        .modal::-webkit-scrollbar {
            width: 6px;
        }
        
        .modal::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .modal::-webkit-scrollbar-thumb {
            background: #c5c5c5;
            border-radius: 10px;
        }
        
        .modal::-webkit-scrollbar-thumb:hover {
            background: #a0a0a0;
        }
    </style>
</head>

<body class="bg-gray-100">
    <?php include '../includes/sidebar.php'; ?>

    <main class="w-full p-4 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <header class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2 flex items-center">
                        <span class="w-10 h-10 rounded-lg bg-green-100 text-green-600 flex items-center justify-center mr-3">
                            <i class="fas fa-shopping-cart"></i>
                        </span>
                        Daftar Penjualan
                    </h1>
                </div>
                <div class="flex items-center gap-4 w-full md:w-auto">
                    <div class="relative w-full md:w-64">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="fas fa-user-tag text-gray-400"></i>
                        </div>
                        <select id="kasirFilter" class="bg-white border border-gray-200 text-gray-700 rounded-lg block w-full pl-10 pr-10 py-3 appearance-none focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 shadow-sm">
                            <option value="">Semua Kasir</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400"></i>
                        </div>
                    </div>
                    <?php if ($user_level === 'kasir'): ?>
                        <a href="transaksi_penjualan.php" class="w-full md:w-auto flex-shrink-0 bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-6 rounded-xl transition-all duration-200 flex items-center justify-center shadow-sm hover:shadow-md">
                            <i class="fas fa-plus mr-2"></i>
                            <span>Tambah Penjualan</span>
                        </a>
                    <?php endif; ?>
                </div>
            </header>

            <div class="table-container">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100">
                                <th class="px-6 py-4 text-left font-semibold text-gray-600 uppercase tracking-wider text-xs">Info Transaksi</th>
                                <th class="px-6 py-4 text-left font-semibold text-gray-600 uppercase tracking-wider text-xs">Kasir</th>
                                <th class="px-6 py-4 text-center font-semibold text-gray-600 uppercase tracking-wider text-xs">Item</th>
                                <th class="px-6 py-4 text-right font-semibold text-gray-600 uppercase tracking-wider text-xs">Total</th>
                                <th class="px-6 py-4 text-center font-semibold text-gray-600 uppercase tracking-wider text-xs">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="salesTableBody">
                        </tbody>
                    </table>
                    <div id="loadingIndicator" class="flex flex-col items-center justify-center p-16">
                        <div class="animate-spin rounded-full h-14 w-14 border-t-2 border-b-2 border-green-500 mb-4"></div>
                        <p class="text-gray-500 font-medium">Memuat data transaksi...</p>
                        <p class="text-gray-500 font-medium">Memuat data penjualan...</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Transaction Modal -->
    <div id="editTransactionModal" class="modal-backdrop hidden">
        <div class="modal">
            <div class="p-6 md:p-8">
                <div class="flex justify-between items-center mb-6 border-b pb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Edit Transaksi</h3>
                        <p class="text-sm text-gray-500 mt-1">Modifikasi detail transaksi penjualan</p>
                    </div>
                    <button type="button" id="closeModal" class="text-gray-500 hover:text-gray-700 h-10 w-10 rounded-full flex items-center justify-center hover:bg-gray-100 transition-all">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-content">
                    <!-- Modal content will be loaded dynamically -->
                    <div id="modalLoadingIndicator" class="py-16 text-center">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-500 mx-auto mb-4"></div>
                        <p class="text-gray-500 font-medium">Memuat detail transaksi...</p>
                    </div>
                    <div id="transactionDetails" class="hidden">
                        <!-- Transaction details will be inserted here -->
                    </div>
                </div>
                <div class="flex justify-end mt-8 gap-3 pt-4 border-t">
                    <button id="cancelEditBtn" class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all duration-200 font-medium">
                        Batal
                    </button>
                    <button id="saveChangesBtn" class="px-6 py-2.5 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-all duration-200 flex items-center gap-2 font-medium shadow-sm">
                        <i class="fas fa-save"></i>
                        <span>Simpan Perubahan</span>
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
                                <a href="cetak/nota_penjualan.php?id=${sale.id_penjualan}" target="_blank" 
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-green-50 hover:bg-green-100 transition-all text-green-600 hover:text-green-700" 
                                   title="Lihat Nota">
                                    <i class="fas fa-eye"></i>
                                </a>
                            `;

                            // Only kasir can edit transactions (formerly delete)
                            if (userLevel === 'kasir') {
                                actionButtons += `
                                    <button onclick="editTransaction(${sale.id_penjualan})" 
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 hover:bg-blue-100 transition-all text-blue-600 hover:text-blue-700 ml-2" 
                                            title="Edit Transaksi">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                `;
                            }

                            const row = `
                                <tr class="border-b hover:bg-gray-50 transition-all">
                                    <td class="p-4">
                                        <div class="font-mono text-blue-600 font-medium">${sale.no_transaksi}</div>
                                        <div class="text-xs text-gray-500 mt-1">${new Date(sale.tanggal).toLocaleString('id-ID')}</div>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center mr-3">
                                                <i class="fas fa-user-circle"></i>
                                            </div>
                                            <span class="font-medium text-gray-800">${sale.nama_kasir}</span>
                                        </div>
                                    </td>
                                    <td class="p-4 text-center">
                                        <span class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-purple-50 text-purple-700">
                                            <i class="fas fa-box-open mr-1.5"></i>${sale.total_item}
                                        </span>
                                    </td>
                                    <td class="p-4 text-right font-bold text-gray-800">${formatRupiah(sale.total_harga)}</td>
                                    <td class="p-4 text-center">
                                        <div class="flex justify-center">
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
                openModalWithAnimation();
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

            // Close modal functions with animation
            function closeEditModal() {
                // First animate the modal out
                const modal = document.querySelector('.modal');
                modal.style.transform = 'translateY(20px) scale(0.98)';
                modal.style.opacity = '0';
                
                // Then fade out the backdrop and hide it
                setTimeout(() => {
                    editTransactionModal.style.opacity = '0';
                    setTimeout(() => {
                        editTransactionModal.classList.add('hidden');
                        modal.style.transform = '';
                        modal.style.opacity = '';
                        editTransactionModal.style.opacity = '';
                        currentTransactionId = null;
                    }, 300); // Match the CSS transition time
                }, 100);
            }
            
            // Open modal with animation enhancement
            window.openModalWithAnimation = () => {
                editTransactionModal.classList.remove('hidden');
                editTransactionModal.style.opacity = '0';
                
                // Ensure we render before animating
                setTimeout(() => {
                    editTransactionModal.style.opacity = '1';
                }, 10);
            }

            closeModal.addEventListener('click', closeEditModal);
            cancelEditBtn.addEventListener('click', closeEditModal);

            // Close modal when clicking outside with animation
            editTransactionModal.addEventListener('click', (e) => {
                if (e.target === editTransactionModal) {
                    closeEditModal();
                }
            });
            
            // Add keyboard support for ESC key to close modal
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !editTransactionModal.classList.contains('hidden')) {
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