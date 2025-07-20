<?php
// Pastikan session sudah dimulai di file utama Anda
// session_start();

// Untuk keperluan demo, set session sementara. Hapus atau sesuaikan ini.
if (!isset($_SESSION['pengguna'])) {
    $_SESSION['pengguna'] = ['nama_user' => 'Kasir Cekatan', 'level' => 'kasir'];
}

// Ambil halaman saat ini dan level pengguna
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$user_level = $_SESSION['pengguna']['level'];

// Data Menu (tidak ada perubahan di sini, strukturnya sudah bagus)
$menuItems = [
    [
        'label' => 'Dashboard',
        'icon' => 'fas fa-home',
        'url' => 'dashboard.php',
        'levels' => ['admin', 'kasir', 'pemilik'],
    ],
    [
        'label' => 'Manajemen',
        'icon' => 'fa-solid fa-sliders',
        'levels' => ['admin'],
        'submenu' => [
            ['label' => 'Kategori', 'icon' => 'fas fa-tags', 'url' => './manage_kategori.php'],
            ['label' => 'Pemasok', 'icon' => 'fas fa-truck', 'url' => './manage_pemasok.php'],
            ['label' => 'Data Barang', 'icon' => 'fas fa-box', 'url' => './manage_barang.php'],
        ]
    ],
    [
        'label' => 'Transaksi',
        'icon' => 'fa-solid fa-hand-holding-dollar',
        'levels' => ['admin'],
        'submenu' => [
            ['label' => 'Pembelian', 'icon' => 'fas fa-dolly', 'url' => './transaksi_pembelian.php', 'levels' => ['admin']],
            ['label' => 'Biaya Operasional', 'icon' => 'fa-regular fa-money-bill-1', 'url' => './transaksi_biaya.php', 'levels' => ['admin']],
        ]
    ],
    [
        'label' => 'Daftar',
        'icon' => 'fa-solid fa-table-list',
        'levels' => ['admin', 'pemilik', 'kasir'],
        'submenu' => [
            ['label' => 'Daftar Kategori', 'icon' => 'fas fa-tags', 'url' => './manage_kategori.php', 'levels' => ['admin', 'pemilik']],
            ['label' => 'Daftar Barang', 'icon' => 'fas fa-boxes', 'url' => './manage_barang.php', 'levels' => ['admin', 'pemilik']],
            ['label' => 'Daftar Pemasok', 'icon' => 'fas fa-truck-loading', 'url' => './manage_pemasok.php', 'levels' => ['admin', 'pemilik']],
            ['label' => 'Daftar Pengguna', 'icon' => 'fas fa-users', 'url' => './data_pengguna.php', 'levels' => ['admin', 'pemilik']],
            ['label' => 'Daftar Pembelian', 'icon' => 'fas fa-dolly-flatbed', 'url' => './transaksi_pembelian.php', 'levels' => ['admin', 'pemilik']],
            ['label' => 'Daftar Barang Penjualan', 'icon' => 'fas fa-receipt', 'url' => './list_barang_penjualan.php', 'levels' => [ 'kasir', 'pemilik']],
            ['label' => 'Daftar Penjualan Barang', 'icon' => 'fas fa-receipt', 'url' => './list_transaksi_penjualan.php', 'levels' => [ 'admin']],
            ['label' => 'Daftar Biaya Operasional', 'icon' => 'fas fa-file-invoice-dollar', 'url' => './transaksi_biaya.php', 'levels' => ['admin', 'pemilik']],
            ['label' => 'Daftar Transaksi Tunai', 'icon' => 'fas fa-hand-holding-usd', 'url' => './list_transaksi_penjualan.php', 'levels' => [ 'pemilik', 'kasir']],
        ]
    ],
    [
        'label' => 'Laporan',
        'icon' => 'fa-regular fa-file-lines',
        'levels' => ['admin', 'pemilik'],
        'submenu' => [
            ['label' => 'Lap. Penjualan', 'icon' => 'fas fa-chart-line', 'url' => './laporan_penjualan.php'],
            ['label' => 'Lap. Pembelian', 'icon' => 'fas fa-chart-area', 'url' => './laporan_pembelian.php'],
            ['label' => 'Lap. Buku Besar', 'icon' => 'fas fa-book', 'url' => './laporan_buku_besar.php'],
        ]
    ],
    [
        'label' => 'Pengaturan',
        'icon' => 'fa-solid fa-users-gear',
        'url' => './data_pengguna.php',
        'levels' => ['admin'],
    ],
];
?>

<aside class="fixed left-0 top-0 z-50 flex h-screen w-20 flex-col bg-green-800 text-white shadow-lg">
    <div class="flex h-20 flex-shrink-0 items-center justify-center border-b border-green-900/50">
        <i class="fas fa-leaf text-3xl text-lime-300"></i>
    </div>

    <nav class="flex h-full flex-col p-2">
        <ul class="space-y-2">
            <?php
            $bottom_items_labels = ['Pengaturan', 'Logout'];
            foreach ($menuItems as $item):
                // ==========================================================
                // ## BUG FIX 1: TAMBAHKAN VALIDASI LEVEL UNTUK MENU UTAMA ##
                // ==========================================================
                // Jika level user tidak ada di dalam array 'levels' menu ini, lewati (jangan tampilkan)
                if (!in_array($user_level, $item['levels'])) {
                    continue;
                }
                
                // Jangan proses item menu bawah di loop ini
                if (in_array($item['label'], $bottom_items_labels)) continue;

                // Logika render item menu utama (tidak berubah)
                $isActive = false;
                if (isset($item['url']) && str_contains($_SERVER['REQUEST_URI'], $item['url'])) {
                    $isActive = true;
                } elseif (isset($item['submenu'])) {
                    foreach ($item['submenu'] as $sub) {
                        if (str_contains($_SERVER['REQUEST_URI'], $sub['url'])) {
                            $isActive = true;
                            break;
                        }
                    }
                }
                $active_class = $isActive ? 'bg-green-700 border-l-4 border-lime-400' : 'border-l-4 border-transparent';
                $hasSubmenu = isset($item['submenu']);
            ?>
                <li class="relative">
                    <a href="<?= $hasSubmenu ? '#' : $item['url'] ?>" class="group flex items-center justify-center rounded p-4 transition-all hover:bg-green-700 <?= $active_class ?>" <?= $hasSubmenu ? 'onclick="toggleSubmenu(event, \'submenu-' . strtolower(str_replace(' ', '', $item['label'])) . '\')"' : '' ?>>
                        <i class="<?= $item['icon'] ?> w-6 text-center text-xl"></i>
                        <span class="absolute left-full ml-4 hidden whitespace-nowrap rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white group-hover:block"><?= $item['label'] ?></span>
                    </a>
                    <?php if ($hasSubmenu): ?>
                        <ul id="submenu-<?= strtolower(str_replace(' ', '', $item['label'])) ?>" class="submenu absolute left-full top-0 ml-4 hidden w-max rounded-md bg-gray-800 p-2 shadow-lg">
                            <?php foreach ($item['submenu'] as $sub): 
                                // Validasi untuk submenu sudah benar
                                if (isset($sub['levels']) && !in_array($user_level, $sub['levels'])) continue; 
                            ?>
                                <li>
                                    <a href="<?= $sub['url'] ?>" class="flex items-center gap-3 rounded p-2 text-sm text-gray-300 hover:bg-green-700 hover:text-white">
                                        <i class="<?= $sub['icon'] ?> w-4 text-center"></i>
                                        <span><?= $sub['label'] ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <ul class="mt-auto space-y-2">
            <?php
            $pengaturanData = null;
            foreach ($menuItems as $item) {
                if ($item['label'] === 'Pengaturan') {
                    $pengaturanData = $item;
                    break;
                }
            }

            if ($pengaturanData && in_array($user_level, $pengaturanData['levels'])):
                $isActive = (isset($pengaturanData['url']) && str_contains($_SERVER['REQUEST_URI'], $pengaturanData['url']));
            ?>
                <li class="relative">
                    <a href="<?= $pengaturanData['url'] ?>" class="group flex items-center justify-center rounded p-4 transition-all hover:bg-green-700 <?= $isActive ? 'bg-green-700 border-l-4 border-lime-400' : 'border-l-4 border-transparent' ?>">
                        <i class="<?= $pengaturanData['icon'] ?> w-6 text-center text-xl"></i>
                        <span class="absolute left-full ml-4 hidden whitespace-nowrap rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white group-hover:block"><?= $pengaturanData['label'] ?></span>
                    </a>
                </li>
            <?php endif; ?>
            
            <li class="relative">
                <a href="../auth/logout.php" class="group flex items-center justify-center rounded bg-red-600/80 p-4 transition-all hover:bg-red-600">
                    <i class="fas fa-sign-out-alt w-6 text-center text-xl"></i>
                    <span class="absolute left-full ml-4 hidden whitespace-nowrap rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white group-hover:block">Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

<script>
    function toggleSubmenu(event, submenuId) {
        event.preventDefault();
        const targetSubmenu = document.getElementById(submenuId);
        document.querySelectorAll('.submenu').forEach(submenu => {
            if (submenu.id !== submenuId) {
                submenu.classList.add('hidden');
            }
        });
        targetSubmenu.classList.toggle('hidden');
    }
    document.addEventListener('click', function(event) {
        const sidebar = document.querySelector('aside');
        // Pastikan sidebar ada sebelum memanggil .contains
        if (sidebar && !sidebar.contains(event.target)) {
            document.querySelectorAll('.submenu').forEach(submenu => {
                submenu.classList.add('hidden');
            });
        }
    });
</script>