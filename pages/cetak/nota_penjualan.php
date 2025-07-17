<?php
session_start();
require_once '../../config/koneksi.php';
require_once '../../includes/functions.php';

// Keamanan: Hanya user yang login yang bisa akses
if (!isset($_SESSION['pengguna'])) {
    die('Anda harus login untuk mengakses halaman ini.');
}

// Ambil ID penjualan dari URL
$id_penjualan = $_GET['id'] ?? null;
if (!$id_penjualan || !is_numeric($id_penjualan)) {
    die('ID Penjualan tidak valid atau tidak ditemukan.');
}

// Ambil data transaksi utama
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        u.nama_user AS nama_kasir
    FROM penjualan p
    JOIN pengguna u ON p.id_user = u.id_user
    WHERE p.id_penjualan = ?
");
$stmt->execute([$id_penjualan]);
$penjualan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$penjualan) {
    die('Data penjualan tidak ditemukan untuk ID tersebut.');
}

// Ambil detail item transaksi
$stmtDetail = $pdo->prepare("
    SELECT 
        d.*,
        b.nama_barang,
        b.id_barang as kode_barang
    FROM detail_penjualan d
    JOIN barang b ON d.id_barang = b.id_barang
    WHERE d.id_penjualan = ?
");
$stmtDetail->execute([$id_penjualan]);
$items = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

// Hitung total kuantitas barang
$total_qty = 0;
foreach ($items as $item) {
    $total_qty += $item['jumlah'];
}

function formatRupiah($angka)
{
    return number_format($angka, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota #<?= htmlspecialchars($penjualan['no_transaksi']) ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            width: 280px;
            margin: 0 auto;
            color: #000;
        }
        .container {
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .logo {
            width: 60px;
            height: 60px;
            border: 1px solid #000;
            border-radius: 50%;
            margin: 0 auto 5px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        hr {
            border: none;
            border-top: 1px solid #000;
            margin: 5px 0;
        }
        .info {
            margin-bottom: 10px;
        }
        .info-line {
            display: flex;
            justify-content: space-between;
        }
        .item-list .item {
            margin-bottom: 5px;
        }
        .item-line, .total-line {
            display: flex;
            justify-content: space-between;
        }
        .totals {
            margin-top: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
        }
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Logo</div>
            <div>Alamat</div>
            <div>No Telp</div>
        </div>

        <hr>
        <div class="info">
            <div class="info-line">
                <span>Tgl: <?= htmlspecialchars(date('d/m/Y', strtotime($penjualan['tanggal']))) ?></span>
            </div>
            <div class="info-line">
                <span>Waktu: <?= htmlspecialchars(date('H:i', strtotime($penjualan['tanggal']))) ?></span>
                <span>Kasir: <?= htmlspecialchars($penjualan['nama_kasir']) ?></span>
            </div>
        </div>
        <hr>
        
        <div class="item-list">
            <?php foreach ($items as $item): ?>
            <div class="item">
                <div class="item-line">
                    <span>Kode Brg: <?= htmlspecialchars($item['kode_barang']) ?></span>
                    <span><?= htmlspecialchars($item['nama_barang']) ?></span>
                </div>
                <div class="item-line">
                    <span><?= $item['jumlah'] ?> x <?= formatRupiah($item['harga_satuan']) ?></span>
                    <span><?= formatRupiah($item['subtotal_barang']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <hr>
        <div class="totals">
            <div class="total-line">
                <span>Total QTY:</span>
                <span><?= $total_qty ?></span>
            </div>
            <hr>
            <div class="total-line">
                <span>Total Harga</span>
                <span><?= formatRupiah($penjualan['total_harga']) ?></span>
            </div>
            <div class="total-line">
                <span>Bayar</span>
                <span><?= formatRupiah($penjualan['bayar']) ?></span>
            </div>
            <div class="total-line">
                <span>Kembali</span>
                <span><?= formatRupiah($penjualan['kembalian']) ?></span>
            </div>
        </div>

        <div class="footer">
            <p>Terimakasih telah berbelanja</p>
        </div>
    </div>

    <script>
        // Jalankan semua aksi saat halaman nota selesai dimuat
        window.onload = function() {
            // 1. Refresh halaman penjualan di latar belakang
            if (window.opener) {
                window.opener.location.reload();
            }

            // 2. Tampilkan dialog cetak
            window.print();
        }

        // Tambahkan event listener yang akan berjalan SETELAH dialog cetak ditutup
        window.addEventListener('afterprint', function(event) {
            // 3. Tutup tab nota ini secara otomatis
            window.close();
        });
    </script>
</body>
</html>