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

        <main class="flex-1 p-8">
            <div class="max-w-7xl mx-auto">
                <header class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">Transaksi Penjualan</h1>
                </header>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Form Transaksi -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">No. Transaksi</label>
                                    <input type="text" id="noTransaksi" value="<?= generateNoTransaksi() ?>" readonly
                                        class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-600">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                                    <input type="text" id="tanggalRealtime" readonly
                                        class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-600">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kasir</label>
                                <input type="text" value="<?= htmlspecialchars($nama_kasir) ?>" readonly
                                    class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-600">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                                    <div class="relative">
                                        <select id="kategoriFilter" class="appearance-none w-full px-3 py-2 pl-3 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                            <option value="">Semua Kategori</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                            <i class="fas fa-chevron-down text-gray-400"></i>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Barang</label>
                                    <div class="relative">
                                        <select id="barangDropdown" class="appearance-none w-full px-3 py-2 pl-3 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                            <option value="">Pilih Barang</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                            <i class="fas fa-chevron-down text-gray-400"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4 relative">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cari Barang</label>
                                <div class="relative">
                                    <input type="text" id="searchBarang" placeholder="Ketik kode atau nama barang..."
                                        class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                </div>
                                <div id="searchResults" class="absolute z-50 w-full mt-1 bg-white rounded-lg shadow-xl max-h-60 overflow-y-auto hidden">
                                    <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                                        <!-- Search results will be added here -->
                                    </div>
                                </div>
                            </div>

                            <!-- Tabel Barang -->
                            <div class="overflow-x-auto">
                                <table class="w-full mb-4">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Kode</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Nama Barang</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Harga</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Qty</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Subtotal</th>
                                            <th class="px-4 py-2 text-xs font-medium text-gray-500">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="keranjangItems">
                                        <!-- Items will be added here dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Panel Total dan Pembayaran -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <div class="text-2xl font-bold text-gray-800 mb-6">Total Pembayaran</div>

                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Total Items:</span>
                                    <span id="totalItems" class="font-medium">0</span>
                                </div>
                                <div class="flex justify-between items-center text-xl font-bold">
                                    <span class="text-gray-800">Total:</span>
                                    <span id="totalAmount" class="text-blue-600">Rp 0</span>
                                </div>
                                <div class="pt-4 border-t">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Bayar</label>
                                    <input type="number" id="inputBayar"
                                        class="w-full px-3 py-2 text-xl border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        onkeyup="hitungKembalian()">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Kembalian</label>
                                    <div id="kembalian" class="text-xl font-bold text-green-600">Rp 0</div>
                                </div>
                                <button onclick="prosesTransaksi()" id="btnBayar"
                                    class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    disabled>
                                    Proses Pembayaran
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
                            div.className = 'px-4 py-2 hover:bg-blue-50 cursor-pointer flex justify-between items-center group';
                            div.innerHTML = `
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900">${item.nama_barang}</div>
                                    <div class="text-sm text-gray-500">Stok: ${item.stok} ${item.satuan_barang}</div>
                                </div>
                                <div class="text-sm font-semibold text-blue-600 group-hover:text-blue-800">
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
            // Check if item already exists
            const existingItem = keranjang.find(i => i.id_barang === item.id_barang);
            if (existingItem) {
                existingItem.qty += 1;
                existingItem.subtotal = existingItem.qty * existingItem.harga_jual;
            } else {
                keranjang.push({
                    id_barang: item.id_barang,
                    nama_barang: item.nama_barang,
                    harga_jual: item.harga_jual,
                    qty: 1,
                    subtotal: item.harga_jual
                });
            }

            updateKeranjangDisplay();
            document.getElementById('searchBarang').value = '';
            document.getElementById('searchResults').classList.add('hidden');
        }

        function updateKeranjangDisplay() {
            const tbody = document.getElementById('keranjangItems');
            tbody.innerHTML = '';
            totalTransaksi = 0;

            keranjang.forEach((item, index) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-4 py-2 text-sm">${item.id_barang}</td>
                    <td class="px-4 py-2 text-sm">${item.nama_barang}</td>
                    <td class="px-4 py-2 text-sm">${formatRupiah(item.harga_jual)}</td>
                    <td class="px-4 py-2 text-sm">
                        <input type="number" value="${item.qty}" min="1" 
                            class="w-20 px-2 py-1 border rounded"
                            onchange="updateQty(${index}, this.value)">
                    </td>
                    <td class="px-4 py-2 text-sm">${formatRupiah(item.subtotal)}</td>
                    <td class="px-4 py-2 text-sm">
                        <button onclick="removeItem(${index})" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
                totalTransaksi += item.subtotal;
            });

            document.getElementById('totalItems').textContent = keranjang.reduce((sum, item) => sum + item.qty, 0);
            document.getElementById('totalAmount').textContent = formatRupiah(totalTransaksi);
            document.getElementById('btnBayar').disabled = keranjang.length === 0;
            hitungKembalian();
        }

        function updateQty(index, newQty) {
            newQty = parseInt(newQty);
            if (newQty < 1) newQty = 1;

            keranjang[index].qty = newQty;
            keranjang[index].subtotal = keranjang[index].qty * keranjang[index].harga_jual;
            updateKeranjangDisplay();
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
                alert('Nomor transaksi tidak boleh kosong');
                return;
            }
            if (isNaN(bayar) || bayar <= 0) {
                alert('Jumlah bayar tidak valid');
                return;
            }
            if (bayar < totalTransaksi) {
                alert('Jumlah bayar kurang dari total transaksi');
                return;
            }
            if (keranjang.length === 0) {
                alert('Keranjang belanja masih kosong');
                return;
            }

            // Tampilkan loading
            const btnSimpan = document.querySelector('button[onclick="prosesTransaksi()"]');
            const originalText = btnSimpan.innerHTML;
            btnSimpan.disabled = true;
            btnSimpan.innerHTML = 'Menyimpan...';

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

                        // Tampilkan pesan sukses
                        alert('Transaksi berhasil disimpan!');
                    } else {
                        throw new Error(result.message || 'Terjadi kesalahan yang tidak diketahui');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert(error.message || 'Terjadi kesalahan saat memproses transaksi. Silakan coba lagi.');
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
    </script>
</body>

</html>