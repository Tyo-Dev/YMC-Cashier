<?php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Keamanan: Hanya kasir yang bisa akses
checkUserLevel(['kasir']);

$user_level = $_SESSION['pengguna']['level'];
$nama_kasir = $_SESSION['pengguna']['nama_user'];

// Format Rupiah
function formatRupiah($angka)
{
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Generate nomor transaksi
function generateNoTransaksi()
{
    return 'TRX-' . date('Ymd') . '-' . substr(uniqid(), -5);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Penjualan - YMC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>

        <main class="flex-1 p-6 md:p-8">
            <div class="max-w-7xl mx-auto">
                <header class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2 flex items-center">
                            <span class="w-10 h-10 rounded-lg bg-green-100 text-green-600 flex items-center justify-center mr-3">
                                <i class="fas fa-cash-register"></i>
                            </span>
                            Transaksi Penjualan
                        </h1>
                        <p class="text-gray-500 text-sm pl-[52px]">Kelola dan proses transaksi penjualan baru</p>
                    </div>
                </header>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Form Transaksi -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
                            <div class="border-b border-gray-100 pb-4 mb-6">
                                <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                                    <i class="fas fa-file-invoice mr-2 text-green-500"></i>
                                    Informasi Transaksi
                                </h2>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">No. Transaksi</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-hashtag text-gray-400"></i>
                                        </div>
                                        <input type="text" id="noTransaksi" value="<?= generateNoTransaksi() ?>" readonly
                                            class="w-full px-3 py-2.5 pl-10 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 font-medium">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-calendar-alt text-gray-400"></i>
                                        </div>
                                        <input type="text" id="tanggalRealtime" readonly
                                            class="w-full px-3 py-2.5 pl-10 bg-gray-50 border border-gray-200 rounded-lg text-gray-700">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Kasir</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-user text-gray-400"></i>
                                    </div>
                                    <input type="text" value="<?= htmlspecialchars($nama_kasir) ?>" readonly
                                        class="w-full px-3 py-2.5 pl-10 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 font-medium">
                                </div>
                            </div>

                            <div class="border-b border-gray-100 pb-4 mb-6">
                                <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                                    <i class="fas fa-boxes mr-2 text-blue-500"></i>
                                    Pilih Produk
                                </h2>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-layer-group text-gray-400"></i>
                                        </div>
                                        <select id="kategoriFilter" class="appearance-none w-full px-3 py-2.5 pl-10 pr-10 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white">
                                            <option value="">Semua Kategori</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <i class="fas fa-chevron-down text-gray-400"></i>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Barang</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-box text-gray-400"></i>
                                        </div>
                                        <select id="barangDropdown" class="appearance-none w-full px-3 py-2.5 pl-10 pr-10 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white">
                                            <option value="">Pilih Barang</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <i class="fas fa-chevron-down text-gray-400"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6 relative">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-search mr-1 text-gray-500"></i>
                                    Cari Barang Cepat
                                </label>
                                <div class="relative">
                                    <input type="text" id="searchBarang" placeholder="Ketik kode atau nama barang..."
                                        class="w-full px-3 py-2.5 pl-10 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                </div>
                                <div id="searchResults" class="absolute z-50 w-full mt-1 bg-white rounded-lg shadow-xl max-h-60 overflow-y-auto hidden border border-gray-100">
                                    <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                                        <!-- Search results will be added here -->
                                    </div>
                                </div>
                            </div>

                            <!-- Tabel Barang -->
                            <div class="border-t border-gray-100 pt-6 mt-2">
                                <div class="flex justify-between items-center mb-4">
                                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                                        <i class="fas fa-shopping-cart mr-2 text-indigo-500"></i>
                                        Keranjang Belanja
                                    </h2>
                                </div>
                                <div class="overflow-x-auto bg-white rounded-lg border border-gray-100 shadow-sm">
                                    <table class="w-full mb-0">
                                        <thead>
                                            <tr class="bg-gray-50 border-b border-gray-100">
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Kode</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Nama Barang</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Harga</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Qty</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Subtotal</th>
                                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="keranjangItems" class="divide-y divide-gray-100">
                                            <!-- Items will be added here dynamically -->
                                        </tbody>
                                    </table>
                                </div>
                                <div id="emptyCart" class="text-center py-8 text-gray-500">
                                    <i class="fas fa-shopping-cart text-gray-300 text-5xl mb-3"></i>
                                    <p>Keranjang belanja masih kosong</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel Total dan Pembayaran -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100 sticky top-6">
                            <div class="flex items-center mb-6 pb-4 border-b border-gray-100">
                                <div class="w-10 h-10 rounded-lg bg-green-100 text-green-600 flex items-center justify-center mr-3">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <h2 class="text-xl font-bold text-gray-800">Pembayaran</h2>
                            </div>

                            <div class="space-y-6">
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-gray-600 flex items-center">
                                            <i class="fas fa-shopping-basket mr-2 text-gray-400"></i>
                                            Total Items:
                                        </span>
                                        <span id="totalItems" class="font-medium text-gray-800 bg-white px-3 py-1 rounded-lg border border-gray-200">0</span>
                                    </div>
                                    <div class="flex justify-between items-center text-xl font-bold">
                                        <span class="text-gray-800 flex items-center">
                                            <i class="fas fa-money-bill-alt mr-2 text-gray-500"></i>
                                            Total:
                                        </span>
                                        <span id="totalAmount" class="text-green-600 bg-green-50 px-3 py-1 rounded-lg border border-green-100">Rp 0</span>
                                    </div>
                                </div>

                                <div class="pt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                        <i class="fas fa-hand-holding-usd mr-2 text-gray-500"></i>
                                        Bayar
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 font-medium">Rp</span>
                                        </div>
                                        <input type="number" id="inputBayar"
                                            class="w-full px-3 py-3 pl-10 text-xl border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 font-medium"
                                            onkeyup="hitungKembalian()">
                                    </div>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                                    <label class="block text-sm font-medium text-gray-700 mb-1 flex items-center">
                                        <i class="fas fa-exchange-alt mr-2 text-gray-500"></i>
                                        Kembalian
                                    </label>
                                    <div id="kembalian" class="text-2xl font-bold text-green-600">Rp 0</div>
                                </div>
                                <button onclick="prosesTransaksi()" id="btnBayar"
                                    class="w-full py-3.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-2 shadow-sm font-medium text-lg"
                                    disabled>
                                    <i class="fas fa-check-circle"></i>
                                    <span>Proses Pembayaran</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        let keranjang = [];
        let totalTransaksi = 0;
        let selectedKategori = '';

        // Load categories when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadCategories();
            loadProducts();
        });

        // Load categories
        function loadCategories() {
            fetch('ajax/get_categories.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    const select = document.getElementById('kategoriFilter');
                    // Clear existing options except the first one
                    select.innerHTML = '<option value="">Semua Kategori</option>';

                    if (Array.isArray(data)) {
                        data.forEach(category => {
                            const option = document.createElement('option');
                            option.value = category;
                            option.textContent = category;
                            select.appendChild(option);
                        });
                    }
                    // Load initial products
                    loadProducts();
                })
                .catch(error => {
                    console.error('Error loading categories:', error);
                    const select = document.getElementById('kategoriFilter');
                    select.innerHTML = '<option value="">Error loading categories</option>';
                    select.disabled = true;
                });
        }

        // Load products based on selected category
        function loadProducts() {
            const kategori = document.getElementById('kategoriFilter').value;
            const url = new URL('ajax/get_products.php', window.location.href);
            if (kategori) {
                url.searchParams.append('kategori', kategori);
            }

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(products => {
                    const select = document.getElementById('barangDropdown');
                    select.innerHTML = '<option value="">Pilih Barang</option>';
                    if (products.length === 0) {
                        const option = document.createElement('option');
                        option.disabled = true;
                        option.textContent = 'Tidak ada barang tersedia';
                        select.appendChild(option);
                    } else {
                        products.forEach(product => {
                            const option = document.createElement('option');
                            option.value = JSON.stringify(product);
                            option.textContent = `${product.nama_barang} - ${formatRupiah(product.harga_jual)}`;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading products:', error);
                    alert('Gagal memuat daftar barang. Silakan coba lagi.');
                });
        }

        // Event listeners for filters and dropdowns
        document.getElementById('kategoriFilter').addEventListener('change', loadProducts);

        document.getElementById('barangDropdown').addEventListener('change', function(e) {
            if (e.target.value) {
                const item = JSON.parse(e.target.value);
                addToKeranjang(item);
                e.target.value = ''; // Reset selection
            }
        });

        // Search product (existing functionality)
        document.getElementById('searchBarang').addEventListener('input', function(e) {
            const searchTerm = e.target.value;
            const resultsDiv = document.getElementById('searchResults');

            if (searchTerm.length < 2) {
                resultsDiv.classList.add('hidden');
                return;
            }

            const kategori = document.getElementById('kategoriFilter').value;
            fetch(`ajax/get_products.php?search=${searchTerm}&kategori=${kategori}`)
                .then(response => response.json())
                .then(data => {
                    const resultsContainer = resultsDiv.querySelector('div');
                    resultsContainer.innerHTML = '';

                    if (data.length === 0) {
                        resultsContainer.innerHTML = `
                            <div class="px-4 py-3 text-sm text-gray-500 italic">
                                Tidak ada barang yang ditemukan
                            </div>`;
                    } else {
                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'px-4 py-3 hover:bg-green-50 cursor-pointer flex justify-between items-center group border-b border-gray-50 last:border-b-0';
                            div.innerHTML = `
                                <div class="flex-1 flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-green-100 text-green-500 flex items-center justify-center mr-3">
                                        <i class="fas fa-box"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">${item.nama_barang}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-xs">
                                                <i class="fas fa-layer-group mr-1"></i> Stok: ${item.stok} ${item.satuan_barang}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-sm font-semibold text-green-600 group-hover:text-green-800 bg-green-50 px-2.5 py-1 rounded-lg">
                                    ${formatRupiah(item.harga_jual)}
                                </div>
                            `;
                            div.onclick = () => {
                                addToKeranjang(item);
                                resultsDiv.classList.add('hidden');
                                e.target.value = '';
                            };
                            resultsContainer.appendChild(div);
                        });
                    }

                    resultsDiv.classList.remove('hidden');
                })
        });

        // Hide search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#searchBarang') && !e.target.closest('#searchResults')) {
                document.getElementById('searchResults').classList.add('hidden');
            }
        });

        function addToKeranjang(item) {
            // Konversi ke float untuk memastikan kompatibilitas
            const harga = parseFloat(item.harga_jual) || 0;

            const existingItem = keranjang.find(i => i.id_barang === item.id_barang);
            if (existingItem) {
                existingItem.qty = parseInt(existingItem.qty) + 1;
                // Gunakan toFixed(2) untuk menghindari masalah floating point
                existingItem.subtotal = (existingItem.qty * harga).toFixed(2);
            } else {
                keranjang.push({
                    id_barang: item.id_barang,
                    nama_barang: item.nama_barang,
                    harga_jual: harga,
                    qty: 1,
                    subtotal: harga.toFixed(2)
                });
            }

            updateKeranjangDisplay();
            document.getElementById('searchBarang').value = '';
            document.getElementById('searchResults').classList.add('hidden');
        }

        function updateKeranjangDisplay() {
            const tbody = document.getElementById('keranjangItems');
            const emptyCart = document.getElementById('emptyCart');
            tbody.innerHTML = '';

            // Reset total dan gunakan parseFloat
            let total = 0;

            if (keranjang.length === 0) {
                emptyCart.classList.remove('hidden');
            } else {
                emptyCart.classList.add('hidden');

                keranjang.forEach((item, index) => {
                    // Konversi nilai ke number untuk kalkulasi yang aman
                    const subtotal = parseFloat(item.subtotal) || 0;
                    total += subtotal;

                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-gray-50 transition-colors';
                    tr.innerHTML = `
                        <td class="px-4 py-3 text-sm font-medium text-gray-700">${item.id_barang}</td>
                        <td class="px-4 py-3 text-sm">${item.nama_barang}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-700">${formatRupiah(item.harga_jual)}</td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex items-center">
                                <button onclick="updateQty(${index}, ${Math.max(1, item.qty - 1)})" class="w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-l-lg flex items-center justify-center text-gray-600">
                                    <i class="fas fa-minus text-xs"></i>
                                </button>
                                <input type="number" value="${item.qty}" min="1" 
                                    class="w-12 h-8 px-0 text-center border-t border-b border-gray-200 focus:outline-none focus:border-green-500"
                                    onchange="updateQty(${index}, this.value)">
                                <button onclick="updateQty(${index}, ${item.qty + 1})" class="w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-r-lg flex items-center justify-center text-gray-600">
                                    <i class="fas fa-plus text-xs"></i>
                                </button>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold text-gray-800">${formatRupiah(item.subtotal)}</td>
                        <td class="px-4 py-3 text-sm text-center">
                            <button onclick="removeItem(${index})" class="w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 flex items-center justify-center transition-colors mx-auto">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            // Set total transaksi dengan pembulatan 2 desimal
            totalTransaksi = parseFloat(total.toFixed(2));

            // Update display dengan format yang aman
            document.getElementById('totalItems').textContent = keranjang.reduce((sum, item) => sum + (parseInt(item.qty) || 0), 0);
            document.getElementById('totalAmount').textContent = formatRupiah(totalTransaksi);
            document.getElementById('btnBayar').disabled = keranjang.length === 0;
            hitungKembalian();
        }

        function updateQty(index, newQty) {
            // Pastikan input adalah number
            newQty = parseInt(newQty) || 1;
            if (newQty < 1) newQty = 1;

            // Validasi index
            if (index >= 0 && index < keranjang.length) {
                const harga = parseFloat(keranjang[index].harga_jual) || 0;
                keranjang[index].qty = newQty;
                keranjang[index].subtotal = (newQty * harga).toFixed(2);
                updateKeranjangDisplay();
            }
        }

        function removeItem(index) {
            keranjang.splice(index, 1);
            updateKeranjangDisplay();
        }

        function hitungKembalian() {
            const bayar = parseFloat(document.getElementById('inputBayar').value) || 0;
            const kembalian = bayar - totalTransaksi;
            document.getElementById('kembalian').textContent = formatRupiah(Math.max(0, kembalian));
            document.getElementById('btnBayar').disabled = bayar < totalTransaksi || keranjang.length === 0;
        }

        function formatRupiah(angka) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka);
        }

        function prosesTransaksi() {
            const noTransaksi = document.getElementById('noTransaksi').value;
            const bayar = parseFloat(document.getElementById('inputBayar').value);

            const data = {
                no_transaksi: noTransaksi,
                total: totalTransaksi,
                bayar: bayar,
                kembalian: bayar - totalTransaksi,
                items: keranjang
            };

            // Debug: Log data yang akan dikirim
            console.log('Data yang akan dikirim:', data);
            console.log('JSON yang akan dikirim:', JSON.stringify(data));

            // Validasi input sebelum mengirim ke server
            if (!noTransaksi) {
                showNotification('error', 'Validasi Gagal', 'Nomor transaksi tidak boleh kosong');
                return;
            }
            if (isNaN(bayar) || bayar <= 0) {
                showNotification('error', 'Validasi Gagal', 'Jumlah bayar tidak valid');
                return;
            }
            if (bayar < totalTransaksi) {
                showNotification('error', 'Validasi Gagal', 'Jumlah bayar kurang dari total transaksi');
                return;
            }
            if (keranjang.length === 0) {
                showNotification('error', 'Validasi Gagal', 'Keranjang belanja masih kosong');
                return;
            }

            // Tampilkan loading
            const btnSimpan = document.querySelector('button[onclick="prosesTransaksi()"]');
            const originalText = btnSimpan.innerHTML;
            btnSimpan.disabled = true;
            btnSimpan.innerHTML = '<div class="inline-flex items-center"><svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Menyimpan Transaksi...</div>';

            fetch('ajax/save_penjualan.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                })
                .then(async response => {
                    const text = await response.text();
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Server response:', text);
                        throw new Error('Server mengembalikan respons yang tidak valid. Silakan cek log error PHP.');
                    }
                })
                .then(result => {
                    if (result.success) {
                        // Buka nota di window baru
                        window.open(`cetak/nota_penjualan.php?id=${result.id_penjualan}`, '_blank');

                        // Reset form
                        keranjang = [];
                        document.getElementById('inputBayar').value = '';
                        document.getElementById('noTransaksi').value = generateNoTransaksi();
                        updateKeranjangDisplay();

                        // Tampilkan pesan sukses dengan notifikasi yang lebih baik
                        showNotification('success', 'Transaksi berhasil disimpan!', 'Nota penjualan telah dibuka di tab baru.');
                    } else {
                        throw new Error(result.message || 'Terjadi kesalahan yang tidak diketahui');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', 'Gagal memproses transaksi', error.message || 'Silakan coba lagi.');
                })
                .finally(() => {
                    // Reset button state
                    const btnSimpan = document.querySelector('button[onclick="prosesTransaksi()"]');
                    btnSimpan.disabled = false;
                    btnSimpan.innerHTML = originalText;
                });
        }
        // Fungsi untuk mengupdate waktu
        function updateWaktuJakarta() {
            const sekarang = new Date();

            // Opsi untuk format tanggal dan waktu sesuai WIB (Waktu Indonesia Barat)
            const options = {
                timeZone: 'Asia/Jakarta',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false // Gunakan format 24 jam
            };

            // Buat formatter untuk lokal Indonesia
            const formatter = new Intl.DateTimeFormat('id-ID', options);
            const [{
                    value: day
                }, ,
                {
                    value: month
                }, ,
                {
                    value: year
                }, ,
                {
                    value: hour
                }, ,
                {
                    value: minute
                }
            ] = formatter.formatToParts(sekarang);

            // Format manual menjadi dd/mm/yyyy HH:ii
            const waktuFormatted = `${day}/${month}/${year} ${hour}:${minute}`;

            // Masukkan ke dalam input field
            document.getElementById('tanggalRealtime').value = waktuFormatted;
        }

        // Panggil fungsi pertama kali agar tidak ada jeda
        updateWaktuJakarta();

        // Set interval untuk mengupdate waktu setiap detik
        setInterval(updateWaktuJakarta, 1000);

        // Function to show styled notifications
        function showNotification(type, title, message) {
            // Create notification container if it doesn't exist
            let notifContainer = document.getElementById('notificationContainer');
            if (!notifContainer) {
                notifContainer = document.createElement('div');
                notifContainer.id = 'notificationContainer';
                notifContainer.className = 'fixed top-4 right-4 z-50 flex flex-col gap-4 w-80';
                document.body.appendChild(notifContainer);
            }

            // Create notification element
            const notif = document.createElement('div');
            notif.className = `p-4 rounded-lg shadow-lg border-l-4 transform translate-x-full transition-transform duration-500 flex items-start ${
                type === 'success' ? 'bg-green-50 border-green-500' : 'bg-red-50 border-red-500'
            }`;

            // Create notification content
            notif.innerHTML = `
                <div class="mr-3 flex-shrink-0">
                    <i class="fas ${type === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'} text-xl"></i>
                </div>
                <div class="flex-1">
                    <h4 class="text-sm font-medium ${type === 'success' ? 'text-green-800' : 'text-red-800'}">${title}</h4>
                    <p class="text-xs mt-1 ${type === 'success' ? 'text-green-600' : 'text-red-600'}">${message}</p>
                </div>
                <button class="ml-4 text-gray-400 hover:text-gray-600" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;

            // Add to DOM
            notifContainer.appendChild(notif);

            // Animate in
            setTimeout(() => {
                notif.style.transform = 'translateX(0)';
            }, 10);

            // Auto remove after 5 seconds
            setTimeout(() => {
                notif.style.transform = 'translateX(full)';
                setTimeout(() => {
                    notif.remove();
                }, 500);
            }, 5000);
        }
    </script>
</body>

</html>