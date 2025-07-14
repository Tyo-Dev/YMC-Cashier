<?php
require_once '../config/koneksi.php';

// Fungsi untuk mengecek apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['pengguna']);
}

// Fungsi untuk mengecek level user
function checkUserLevel($required_level) {
    if (!isLoggedIn()) {
        header('Location: ../auth/login.php');
        exit;
    }
    
    $user_level = $_SESSION['pengguna']['level'];
    
    if ($required_level === 'admin' && $user_level !== 'admin') {
        header('Location: dashboard.php');
        exit;
    }
    
    if ($required_level === 'pemilik' && $user_level !== 'pemilik') {
        header('Location: dashboard.php');
        exit;
    }
}

// Fungsi untuk format rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Fungsi untuk mendapatkan data barang
function getBarang($pdo) {
    $stmt = $pdo->prepare("
        SELECT b.*, k.kategori_barang 
        FROM barang b 
        JOIN kategori k ON b.id_kategori = k.id_kategori 
        ORDER BY b.nama_barang
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fungsi untuk mendapatkan data kategori
function getKategori($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM kategori ORDER BY kategori_barang");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fungsi untuk mendapatkan data pemasok
function getPemasok($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM pemasok ORDER BY nama_pemasok");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fungsi untuk mendapatkan data pengguna
function getPengguna($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM pengguna ORDER BY nama_user");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fungsi untuk mendapatkan laporan penjualan
function getLaporanPenjualan($pdo, $tanggal_awal = null, $tanggal_akhir = null) {
    $sql = "
        SELECT p.*, u.nama_user 
        FROM penjualan p 
        JOIN pengguna u ON p.id_user = u.id_user
    ";
    
    if ($tanggal_awal && $tanggal_akhir) {
        $sql .= " WHERE p.tanggal BETWEEN ? AND ?";
    }
    
    $sql .= " ORDER BY p.tanggal DESC";
    
    $stmt = $pdo->prepare($sql);
    
    if ($tanggal_awal && $tanggal_akhir) {
        $stmt->execute([$tanggal_awal, $tanggal_akhir]);
    } else {
        $stmt->execute();
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fungsi untuk mendapatkan laporan pembelian
function getLaporanPembelian($pdo, $tanggal_awal = null, $tanggal_akhir = null) {
    $sql = "
        SELECT p.*, ps.nama_pemasok 
        FROM pembelian p 
        JOIN pemasok ps ON p.id_pemasok = ps.id_pemasok
    ";
    
    if ($tanggal_awal && $tanggal_akhir) {
        $sql .= " WHERE p.tanggal BETWEEN ? AND ?";
    }
    
    $sql .= " ORDER BY p.tanggal DESC";
    
    $stmt = $pdo->prepare($sql);
    
    if ($tanggal_awal && $tanggal_akhir) {
        $stmt->execute([$tanggal_awal, $tanggal_akhir]);
    } else {
        $stmt->execute();
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fungsi untuk mendapatkan statistik dashboard
function getDashboardStats($pdo) {
    $stats = [];
    
    // Total penjualan hari ini
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_harga_jual), 0) as total FROM penjualan WHERE tanggal = CURDATE()");
    $stmt->execute();
    $stats['penjualan_hari_ini'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total penjualan bulan ini
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_harga_jual), 0) as total FROM penjualan WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())");
    $stmt->execute();
    $stats['penjualan_bulan_ini'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total barang
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM barang");
    $stmt->execute();
    $stats['total_barang'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total stok
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(stok), 0) as total FROM barang");
    $stmt->execute();
    $stats['total_stok'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    return $stats;
}

// Fungsi untuk update stok barang
function updateStokBarang($pdo, $id_barang, $jumlah, $operasi = 'kurang') {
    if ($operasi === 'kurang') {
        $stmt = $pdo->prepare("UPDATE barang SET stok = stok - ? WHERE id_barang = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE barang SET stok = stok + ? WHERE id_barang = ?");
    }
    
    return $stmt->execute([$jumlah, $id_barang]);
}

// Fungsi untuk validasi input
function validateInput($data, $required_fields = []) {
    $errors = [];
    
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $errors[] = "Field $field harus diisi";
        }
    }
    
    return $errors;
}

// Fungsi untuk generate ID transaksi
function generateTransactionId($prefix = 'TRX') {
    return $prefix . date('Ymd') . rand(1000, 9999);
}
?>