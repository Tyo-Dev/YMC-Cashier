-- Database Schema untuk Sistem Kasir YMC (Yumna Moslem Collection)
-- Berdasarkan analisis dokumen desain sistem

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
    level ENUM('admin', 'kasir','pemilik') NOT NULL
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
    id_user INT(10) NOT NULL,
    tanggal DATE NOT NULL,
    total_harga_jual DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_user) REFERENCES pengguna(id_user) ON DELETE CASCADE
);

-- 6. Tabel Detail Penjualan
CREATE TABLE detail_penjualan (
    id_detail_penjualan INT(10) AUTO_INCREMENT PRIMARY KEY,
    id_penjualan INT(10) NOT NULL,
    id_barang INT(10) NOT NULL,
    jumlah INT NOT NULL,
    harga_satuan DECIMAL(10,2) NOT NULL,
    subtotal_barang DECIMAL(10,2) NOT NULL,
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

-- 9. Tabel Biaya Operasional
CREATE TABLE biaya_operasional (
    id_biaya INT(10) AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    keterangan VARCHAR(100) NOT NULL,
    jumlah_biaya DECIMAL(10,2) NOT NULL
);

-- Insert data default
-- Kategori default
INSERT INTO kategori (kategori_barang) VALUES 
('Pakaian Pria'),
('Pakaian Wanita'),
('Aksesoris'),
('Perlengkapan Ibadah'),
('Tas dan Dompet');

-- User default (password: admin123 dan kasir123 - akan di-hash di aplikasi)
INSERT INTO pengguna (nama_user, username, password, level) VALUES
('Admin Utama', 'admin01', MD5('admin123'), 'admin'),
('Kasir Toko', 'kasir01', MD5('kasir123'), 'kasir'),
('Pemilik Usaha', 'pemilik01', MD5('pemilik123'), 'pemilik');


-- Pemasok default
INSERT INTO pemasok (nama_pemasok, alamat, no_telepon) VALUES 
('PT. Busana Muslim Indonesia', 'Jl. Raya Jakarta No. 123', '021-12345678'),
('CV. Aksesoris Islami', 'Jl. Sudirman No. 456', '021-87654321'),
('Toko Grosir Hijab', 'Jl. Malioboro No. 789', '0274-123456');

-- Barang contoh
INSERT INTO barang (id_kategori, nama_barang, harga_beli, margin, harga_jual, stok, satuan_barang) VALUES 
(1, 'Kemeja Koko Putih', 75000, 25.00, 100000, 20, 'pcs'),
(2, 'Hijab Segi Empat', 25000, 40.00, 35000, 50, 'pcs'),
(3, 'Tasbih Kayu', 15000, 33.33, 20000, 30, 'pcs'),
(4, 'Sajadah Turki', 45000, 33.33, 60000, 15, 'pcs'),
(5, 'Tas Selempang Pria', 85000, 29.41, 110000, 10, 'pcs');

