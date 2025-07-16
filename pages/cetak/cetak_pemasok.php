<?php
session_start();
require_once '../../config/koneksi.php';
require_once '../../includes/functions.php';

// Keamanan: Hanya pemilik yang bisa akses
checkUserLevel(['pemilik']);

// Ambil data pemasok
$stmt = $pdo->query("SELECT * FROM pemasok ORDER BY nama_pemasok");
$pemasoks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung jumlah transaksi per pemasok
$transaksi_pemasok = [];
foreach ($pemasoks as $pemasok) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pembelian WHERE id_pemasok = ?");
    $stmt->execute([$pemasok['id_pemasok']]);
    $transaksi_pemasok[$pemasok['id_pemasok']] = $stmt->fetchColumn();
}

// Set header for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="laporan_supplier_' . date('Y-m-d') . '.xls"');
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
            <td colspan="5" class="title">
                Laporan Daftar Supplier<br />
                Toko Yumna Moslem Collection<br />
                <?= date('d/m/Y') ?>
            </td>
        </tr>
        <tr></tr>
        <tr>
            <th>No</th>
            <th>Nama Pemasok (Supplier)</th>
            <th>Alamat</th>
            <th>Nomor Telpon</th>
            <th>Jumlah Transaksi</th>
        </tr>
        <?php
        $no = 1;
        foreach ($pemasoks as $pemasok):
        ?>
            <tr>
                <td class="center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($pemasok['nama_pemasok']) ?></td>
                <td><?= htmlspecialchars($pemasok['alamat']) ?></td>
                <td><?= htmlspecialchars($pemasok['no_telepon']) ?></td>
                <td class="center"><?= $transaksi_pemasok[$pemasok['id_pemasok']] ?> transaksi</td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>

</html>

// Ambil data pemasok
$stmt = $pdo->query("SELECT * FROM pemasok ORDER BY nama_pemasok");
$pemasoks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung jumlah transaksi per pemasok
$transaksi_pemasok = [];
foreach ($pemasoks as $pemasok) {
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pembelian WHERE id_pemasok = ?");
$stmt->execute([$pemasok['id_pemasok']]);
$transaksi_pemasok[$pemasok['id_pemasok']] = $stmt->fetchColumn();
}

class PDF extends FPDF {
// Page header
function Header() {
// Logo - you can add your logo here
// $this->Image('logo.png',10,6,30);

// Title
$this->SetFont('Arial','B',16);
$this->Cell(0,10,'Laporan Daftar Supplier',0,1,'C');
$this->Cell(0,10,'Toko Yumna Moslem Collection',0,1,'C');
$this->Ln(10);
}

// Page footer
function Footer() {
$this->SetY(-15);
$this->SetFont('Arial','I',8);
$this->Cell(0,10,'Halaman '.$this->PageNo().'/{nb}',0,0,'C');
}
}

// Create PDF object
$pdf = new PDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();

// Table Header
$pdf->SetFont('Arial','B',10);
$pdf->Cell(10,7,'No',1,0,'C');
$pdf->Cell(50,7,'Nama Pemasok',1,0,'C');
$pdf->Cell(70,7,'Alamat',1,0,'C');
$pdf->Cell(30,7,'No. Telepon',1,0,'C');
$pdf->Cell(30,7,'Jml Transaksi',1,1,'C');

// Table contents
$pdf->SetFont('Arial','',10);
$no = 1;
foreach ($pemasoks as $pemasok) {
$pdf->Cell(10,7,$no++,1,0,'C');
$pdf->Cell(50,7,substr($pemasok['nama_pemasok'], 0, 28),1,0,'L');
$pdf->Cell(70,7,substr($pemasok['alamat'], 0, 40),1,0,'L');
$pdf->Cell(30,7,$pemasok['no_telepon'],1,0,'L');
$pdf->Cell(30,7,$transaksi_pemasok[$pemasok['id_pemasok']] . ' transaksi',1,1,'C');

// Check if we need a new page
if($pdf->GetY() > 250) {
$pdf->AddPage();
}
}

// Output PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="laporan_supplier.pdf"');
$pdf->Output('D', 'laporan_supplier.pdf');