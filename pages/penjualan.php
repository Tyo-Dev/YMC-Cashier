<?php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = $_SESSION['pengguna'];
$success = null;
$error = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'process_sale') {
            $pdo->beginTransaction();
            
            $total_harga = 0;
            $items = json_decode($_POST['items'], true);
            
            // Validasi stok
            foreach ($items as $item) {
                $stmt = $pdo->prepare("SELECT stok FROM barang WHERE id_barang = ?");
                $stmt->execute([$item['id_barang']]);
                $barang = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($barang['stok'] < $item['jumlah']) {
                    throw new Exception("Stok barang tidak mencukupi untuk " . $item['nama_barang']);
                }
                
                $total_harga += $item['subtotal'];
            }
            
            // Insert penjualan
            $stmt = $pdo->prepare("INSERT INTO penjualan (id_user, tanggal, total_harga_jual) VALUES (?, CURDATE(), ?)");
            $stmt->execute([$user['id_user'], $total_harga]);
            $id_penjualan = $pdo->lastInsertId();
            
            // Insert detail penjualan dan update stok
            foreach ($items as $item) {
                $stmt = $pdo->prepare("INSERT INTO detail_penjualan (id_penjualan, id_barang, jumlah, harga_satuan, subtotal_barang) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $id_penjualan,
                    $item['id_barang'],
                    $item['jumlah'],
                    $item['harga_satuan'],
                    $item['subtotal']
                ]);
                
                // Update stok
                updateStokBarang($pdo, $item['id_barang'], $item['jumlah'], 'kurang');
            }
            
            $pdo->commit();
            $success = "Transaksi berhasil disimpan! ID Penjualan: " . $id_penjualan;
            
        } elseif ($_POST['action'] === 'get_barang') {
            $stmt = $pdo->prepare("SELECT * FROM barang WHERE id_barang = ?");
            $stmt->execute([$_POST['id_barang']]);
            $barang = $stmt->fetch(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode($barang);
            exit;
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Get barang untuk dropdown
$barang = getBarang($pdo);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Penjualan - Sistem Kasir YMC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-indigo-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="hover:text-indigo-200">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
                <h1 class="text-xl font-bold">Transaksi Penjualan</h1>
            </div>
            <div class="flex items-center space-x-4">
                <span><?= htmlspecialchars($user['nama_user']) ?></span>
                <a href="../auth/logout.php" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        <!-- Alert Messages -->
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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Form Input Barang -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">Tambah Barang</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Pilih Barang</label>
                        <select id="barang_select" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- Pilih Barang --</option>
                            <?php foreach ($barang as $item): ?>
                                <option value="<?= $item['id_barang'] ?>" data-nama="<?= htmlspecialchars($item['nama_barang']) ?>" data-harga="<?= $item['harga_jual'] ?>" data-stok="<?= $item['stok'] ?>">
                                    <?= htmlspecialchars($item['nama_barang']) ?> - <?= formatRupiah($item['harga_jual']) ?> (Stok: <?= $item['stok'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Harga Satuan</label>
                        <input type="number" id="harga_satuan" class="w-full px-3 py-2 border rounded-lg bg-gray-100" readonly>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Jumlah</label>
                        <input type="number" id="jumlah" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" min="1">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Subtotal</label>
                        <input type="number" id="subtotal" class="w-full px-3 py-2 border rounded-lg bg-gray-100" readonly>
                    </div>
                    
                    <button onclick="addToCart()" class="w-full bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">
                        <i class="fas fa-plus"></i> Tambah ke Keranjang
                    </button>
                </div>
            </div>

            <!-- Keranjang Belanja -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">Keranjang Belanja</h2>
                
                <div id="cart_items" class="space-y-2 mb-4" style="max-height: 300px; overflow-y: auto;">
                    <p class="text-gray-500 text-center">Keranjang kosong</p>
                </div>
                
                <div class="border-t pt-4">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-lg font-bold">Total:</span>
                        <span id="total_harga" class="text-lg font-bold text-green-600">Rp 0</span>
                    </div>
                    
                    <div class="space-y-2">
                        <button onclick="clearCart()" class="w-full bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded">
                            <i class="fas fa-trash"></i> Kosongkan Keranjang
                        </button>
                        
                        <button onclick="processTransaction()" class="w-full bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded">
                            <i class="fas fa-check"></i> Proses Transaksi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let cart = [];
        let total = 0;

        // Event listeners
        document.getElementById('barang_select').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                document.getElementById('harga_satuan').value = selectedOption.dataset.harga;
                document.getElementById('jumlah').value = 1;
                calculateSubtotal();
            } else {
                document.getElementById('harga_satuan').value = '';
                document.getElementById('jumlah').value = '';
                document.getElementById('subtotal').value = '';
            }
        });

        document.getElementById('jumlah').addEventListener('input', calculateSubtotal);

        function calculateSubtotal() {
            const harga = parseFloat(document.getElementById('harga_satuan').value) || 0;
            const jumlah = parseInt(document.getElementById('jumlah').value) || 0;
            const subtotal = harga * jumlah;
            document.getElementById('subtotal').value = subtotal;
        }

        function addToCart() {
            const barangSelect = document.getElementById('barang_select');
            const selectedOption = barangSelect.options[barangSelect.selectedIndex];
            
            if (!selectedOption.value) {
                alert('Pilih barang terlebih dahulu!');
                return;
            }
            
            const jumlah = parseInt(document.getElementById('jumlah').value);
            const stok = parseInt(selectedOption.dataset.stok);
            
            if (!jumlah || jumlah <= 0) {
                alert('Masukkan jumlah yang valid!');
                return;
            }
            
            if (jumlah > stok) {
                alert('Jumlah melebihi stok yang tersedia!');
                return;
            }
            
            const item = {
                id_barang: selectedOption.value,
                nama_barang: selectedOption.dataset.nama,
                harga_satuan: parseFloat(document.getElementById('harga_satuan').value),
                jumlah: jumlah,
                subtotal: parseFloat(document.getElementById('subtotal').value)
            };
            
            // Cek apakah barang sudah ada di keranjang
            const existingIndex = cart.findIndex(cartItem => cartItem.id_barang === item.id_barang);
            
            if (existingIndex !== -1) {
                // Update jumlah dan subtotal
                cart[existingIndex].jumlah += item.jumlah;
                cart[existingIndex].subtotal += item.subtotal;
            } else {
                // Tambah item baru
                cart.push(item);
            }
            
            updateCartDisplay();
            clearForm();
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartDisplay();
        }

        function updateCartDisplay() {
            const cartItemsDiv = document.getElementById('cart_items');
            
            if (cart.length === 0) {
                cartItemsDiv.innerHTML = '<p class="text-gray-500 text-center">Keranjang kosong</p>';
                total = 0;
            } else {
                let html = '';
                total = 0;
                
                cart.forEach((item, index) => {
                    total += item.subtotal;
                    html += `
                        <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                            <div class="flex-1">
                                <p class="font-medium">${item.nama_barang}</p>
                                <p class="text-sm text-gray-600">${item.jumlah} x ${formatRupiah(item.harga_satuan)}</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="font-bold">${formatRupiah(item.subtotal)}</span>
                                <button onclick="removeFromCart(${index})" class="text-red-500 hover:text-red-700">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                });
                
                cartItemsDiv.innerHTML = html;
            }
            
            document.getElementById('total_harga').textContent = formatRupiah(total);
        }

        function clearCart() {
            cart = [];
            updateCartDisplay();
        }

        function clearForm() {
            document.getElementById('barang_select').value = '';
            document.getElementById('harga_satuan').value = '';
            document.getElementById('jumlah').value = '';
            document.getElementById('subtotal').value = '';
        }

        function processTransaction() {
            if (cart.length === 0) {
                alert('Keranjang kosong! Tambahkan barang terlebih dahulu.');
                return;
            }
            
            if (confirm('Proses transaksi ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'process_sale';
                form.appendChild(actionInput);
                
                const itemsInput = document.createElement('input');
                itemsInput.type = 'hidden';
                itemsInput.name = 'items';
                itemsInput.value = JSON.stringify(cart);
                form.appendChild(itemsInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        function formatRupiah(amount) {
            return 'Rp ' + amount.toLocaleString('id-ID');
        }
    </script>
</body>
</html>