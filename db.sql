-- Database Schema untuk Sistem Kasir YMC (Yumna Moslem Collection)
-- Berdasarkan hasil export dan optimalisasi dari phpMyAdmin

CREATE DATABASE IF NOT EXISTS db_ymc;
USE db_ymc;

-- 1. Tabel Kategori
CREATE TABLE kategori (
    id_kategori INT(10) AUTO_INCREMENT PRIMARY KEY,
    kategori_barang VARCHAR(100) NOT NULL
);

-- 2. Tabel Pengguna (User)
CREATE TABLE pengguna (
    id_user INT(10) AUTO_INCREMENT PRIMARY KEY,
    nama_user VARCHAR(50) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    level ENUM('admin', 'kasir', 'pemilik') NOT NULL
);

-- 3. Tabel Pemasok (Supplier)
CREATE TABLE pemasok (
    id_pemasok INT(10) AUTO_INCREMENT PRIMARY KEY,
    nama_pemasok VARCHAR(50) NOT NULL,
    alamat VARCHAR(100) NOT NULL,
    no_telepon VARCHAR(15) NOT NULL
);

-- 4. Tabel Barang
CREATE TABLE barang (
    id_barang INT(10) AUTO_INCREMENT PRIMARY KEY,
    id_kategori INT(10) NOT NULL,
    nama_barang VARCHAR(50) NOT NULL,
    harga_beli INT(10) NOT NULL,
    margin DECIMAL(10,2) NOT NULL,
    harga_jual INT(10) NOT NULL,
    stok INT(5) NOT NULL DEFAULT 0,
    satuan_barang VARCHAR(20) NOT NULL,
    FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori) ON DELETE CASCADE
);

-- 5. Tabel Penjualan
CREATE TABLE penjualan (
    id_penjualan INT(10) AUTO_INCREMENT PRIMARY KEY,
    no_transaksi VARCHAR(50) NOT NULL UNIQUE,
    tanggal DATETIME NOT NULL,
    id_user INT(10) NOT NULL,
    total_harga INT(11) NOT NULL,
    bayar INT(11) NOT NULL,
    kembalian INT(11) NOT NULL,
    FOREIGN KEY (id_user) REFERENCES pengguna(id_user) ON DELETE CASCADE
);

-- 6. Tabel Detail Penjualan
CREATE TABLE detail_penjualan (
    id_detail_penjualan INT(10) AUTO_INCREMENT PRIMARY KEY,
    id_penjualan INT(10) NOT NULL,
    id_barang INT(10) NOT NULL,
    jumlah INT NOT NULL,
    harga_satuan INT(11) NOT NULL,
    subtotal_barang INT(11) NOT NULL,
    FOREIGN KEY (id_penjualan) REFERENCES penjualan(id_penjualan) ON DELETE CASCADE,
    FOREIGN KEY (id_barang) REFERENCES barang(id_barang) ON DELETE CASCADE
);

-- 7. Tabel Pembelian
CREATE TABLE pembelian (
    id_pembelian INT(10) AUTO_INCREMENT PRIMARY KEY,
    id_pemasok INT(10) NOT NULL,
    tanggal DATE NOT NULL,
    total_harga_beli DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_pemasok) REFERENCES pemasok(id_pemasok) ON DELETE CASCADE
);

-- 8. Tabel Detail Pembelian
CREATE TABLE detail_pembelian (
    id_detail_pembelian INT(10) AUTO_INCREMENT PRIMARY KEY,
    id_pembelian INT(10) NOT NULL,
    id_barang INT(10) NOT NULL,
    jumlah INT NOT NULL,
    harga_beli DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_pembelian) REFERENCES pembelian(id_pembelian) ON DELETE CASCADE,
    FOREIGN KEY (id_barang) REFERENCES barang(id_barang) ON DELETE CASCADE
);

-- Insert Data Default

-- Kategori Default
INSERT INTO kategori (kategori_barang) VALUES 
('Pakaian Pria'),
('Pakaian Wanita'),
('Aksesoris'),
('Perlengkapan Ibadah'),
('Tas dan Dompet'),
('Pakaian Bayi');

-- User Default (gunakan bcrypt di aplikasi saat input/update password)
INSERT INTO pengguna (nama_user, username, password, level) VALUES
('Admin Utama', 'admin','$2y$10$k0cfiEyQewg3d1O48chePOVw27BT1S.Rvpwss.D1AgjFaWjkCRDv.', 'admin'),
('Kasir Toko', 'kasir', '$2y$10$DNm36EuDqlzVHNbuHILwbunmUYetreAPMoPau52A0iRsPxh3n1mZy', 'kasir'),
('Pemilik Usaha', 'pemilik', '$2y$10$mE3951byHIY2X7TFjX7vt.U9JqxeXhZ2AdklXq5y62nhErxmy8Is.', 'pemilik');

-- Pengguna Default
-- user : admin,  pw:admin
-- user : kasir,  pw:kasir
-- user : pemilik,pw:pemilik 

-- Pemasok Default
INSERT INTO pemasok (nama_pemasok, alamat, no_telepon) VALUES 
('PT. Busana Muslim Indonesia', 'Jl. Raya Jakarta No. 123', '02112345678'),
('CV. Aksesoris Islami', 'Jl. Sudirman No. 456', '02187654321'),
('Toko Grosir Hijab', 'Jl. Malioboro No. 789', '0274123456'),
('PT Muslim Busana Indonesia', 'JL Bantul KM 129', '0987654323456');

-- Barang Contoh
INSERT INTO barang (id_kategori, nama_barang, harga_beli, margin, harga_jual, stok, satuan_barang) VALUES 
(1, 'Kemeja Koko Putih', 75000, 25.00, 100000, 20, 'pcs'),
(2, 'Hijab Segi Empat', 25000, 40.00, 35000, 50, 'pcs'),
(3, 'Tasbih Kayu', 15000, 33.33, 20000, 30, 'pcs'),
(4, 'Sajadah Turki', 45000, 33.33, 60000, 15, 'pcs'),
(5, 'Tas Selempang Pria', 85000, 29.41, 110000, 10, 'pcs');
