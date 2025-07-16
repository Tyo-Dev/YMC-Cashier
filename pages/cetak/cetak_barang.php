<?php
session_start();
require_once '../../config/koneksi.php';
require_once '../../includes/functions.php';

// Keamanan: Hanya pemilik yang bisa akses
checkUserLevel(['pemilik']);

// Ambil data barang dengan kategori
$stmt = $pdo->query("SELECT b.*, k.kategori_barang FROM barang b JOIN kategori k ON b.id_kategori = k.id_kategori ORDER BY b.nama_barang");
$barangs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set header for Excel download

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="laporan_barang_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');
?>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
        }

        th {
            background-color: #f0f0f0;
        }

        .title {
            font-size: 16pt;
            font-weight: bold;
            text-align: center;
        }

        .center {
            text-align: center;
        }
    </style>
</head>

<body>
    <table>
        <tr>
            <td colspan="8" class="title">
                Laporan Daftar Barang<br />
                Toko Yumna Moslem Collection<br />
                <?php echo date('d/m/Y'); ?>
            </td>
        </tr>
        <tr></tr>
        <tr>
            <th>Kode Barang</th>
            <th>Nama Barang</th>
            <th>Kategori</th>
            <th>Harga Beli</th>
            <th>Margin</th>
            <th>Harga Jual</th>
            <th>Stok</th>
            <th>Satuan</th>
        </tr>
        <?php foreach ($barangs as $barang): ?>
            <tr>
                <td class="center"><?php echo str_pad($barang['id_barang'], 6, '0', STR_PAD_LEFT); ?></td>
                <td><?php echo htmlspecialchars($barang['nama_barang']); ?></td>
                <td><?php echo htmlspecialchars($barang['kategori_barang']); ?></td>
                <td><?php echo number_format($barang['harga_beli'], 0, ',', '.'); ?></td>
                <td><?php echo number_format($barang['margin'], 2); ?>%</td>
                <td><?php echo number_format($barang['harga_jual'], 0, ',', '.'); ?></td>
                <td class="center"><?php echo $barang['stok']; ?></td>
                <td><?php echo htmlspecialchars($barang['satuan_barang']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>

</html>