-- ============================================
-- DATABASE SINAR JAYA KONVEKSI
-- Untuk diimport ke phpMyAdmin
-- Versi: 2.1 - Fixed for MySQL/MariaDB Compatibility
-- Updated: February 2026
-- ============================================

-- Buat database
CREATE DATABASE IF NOT EXISTS sinar_jaya_konveksi;
USE sinar_jaya_konveksi;

-- Set character encoding
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- HAPUS TABEL YANG ADA (urutan penting karena FK)
-- ============================================
DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS prediksi_stok;
DROP TABLE IF EXISTS detail_penjualan;
DROP TABLE IF EXISTS penjualan;
DROP TABLE IF EXISTS stok_history;
DROP TABLE IF EXISTS gambar_produk;
DROP TABLE IF EXISTS produk;
DROP TABLE IF EXISTS pelanggan;
DROP TABLE IF EXISTS kontak;
DROP TABLE IF EXISTS testimonial;
DROP TABLE IF EXISTS galeri;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS about;
DROP TABLE IF EXISTS kategori;

-- ============================================
-- TABEL KATEGORI PRODUK
-- ============================================
CREATE TABLE kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50) DEFAULT NULL COMMENT 'Font Awesome icon class',
    deskripsi TEXT,
    urutan INT DEFAULT 0 COMMENT 'Urutan tampilan',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert data kategori
INSERT INTO kategori (nama_kategori, slug, icon, deskripsi, urutan) VALUES
('Kemeja', 'kemeja', 'fa-shirt', 'Kemeja formal dan casual dengan berbagai pilihan bahan dan desain', 1),
('Polo Shirt', 'polo', 'fa-tshirt', 'Polo shirt berkualitas untuk seragam kantor, event, atau kegiatan komunitas', 2),
('Jaket', 'jaket', 'fa-vest', 'Jaket custom dengan berbagai model seperti bomber, varsity, dan windbreaker', 3),
('Seragam', 'seragam', 'fa-user-tie', 'Seragam kerja, sekolah, dan organisasi dengan kualitas terbaik', 4),
('Kaos', 'kaos', 'fa-shirt', 'Kaos polos dan sablon berbagai ukuran', 5),
('Celana', 'celana', 'fa-socks', 'Celana formal dan casual untuk seragam kerja', 6);

-- ============================================
-- TABEL PRODUK
-- ============================================
CREATE TABLE produk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori_id INT NOT NULL,
    nama_produk VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    deskripsi TEXT,
    harga DECIMAL(12, 2) NOT NULL DEFAULT 0,
    harga_diskon DECIMAL(12, 2) DEFAULT NULL COMMENT 'Harga setelah diskon',
    stok INT NOT NULL DEFAULT 0,
    min_order INT DEFAULT 12 COMMENT 'Minimum pemesanan',
    ukuran_tersedia VARCHAR(255) DEFAULT 'S,M,L,XL,XXL' COMMENT 'Ukuran yang tersedia',
    warna_tersedia VARCHAR(255) DEFAULT NULL COMMENT 'Warna yang tersedia',
    bahan VARCHAR(100) DEFAULT NULL COMMENT 'Jenis bahan',
    gambar_utama VARCHAR(255),
    featured TINYINT(1) DEFAULT 0 COMMENT 'Produk unggulan (tampil di homepage)',
    best_seller TINYINT(1) DEFAULT 0,
    new_arrival TINYINT(1) DEFAULT 0,
    status ENUM('active', 'inactive', 'out_of_stock') DEFAULT 'active',
    views INT DEFAULT 0 COMMENT 'Jumlah dilihat',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kategori (kategori_id),
    INDEX idx_status (status),
    INDEX idx_featured (featured),
    INDEX idx_slug (slug),
    CONSTRAINT fk_produk_kategori FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert data produk contoh
INSERT INTO produk (kategori_id, nama_produk, slug, deskripsi, harga, stok, min_order, bahan, featured, gambar_utama) VALUES
(1, 'Kemeja Formal Premium', 'kemeja-formal-premium', 'Kemeja formal dengan bahan premium cotton, cocok untuk seragam kantor dan acara formal', 185000, 150, 12, 'Cotton Premium', 1, 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=500'),
(1, 'Kemeja Batik Modern', 'kemeja-batik-modern', 'Kemeja batik dengan motif modern, perpaduan tradisional dan kontemporer', 225000, 80, 12, 'Katun Batik', 0, 'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=500'),
(1, 'Kemeja Lengan Pendek', 'kemeja-lengan-pendek', 'Kemeja lengan pendek casual untuk kegiatan sehari-hari', 145000, 180, 12, 'Cotton Twill', 0, 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=500'),
(2, 'Polo Shirt Corporate', 'polo-shirt-corporate', 'Polo shirt dengan bordir logo perusahaan, bahan lacoste premium', 125000, 200, 24, 'Lacoste Cotton', 1, 'https://images.unsplash.com/photo-1625910513413-5fc45759de53?w=500'),
(2, 'Polo Shirt Casual', 'polo-shirt-casual', 'Polo shirt kasual untuk sehari-hari, tersedia berbagai warna', 95000, 250, 24, 'Lacoste PE', 0, 'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=500'),
(3, 'Jaket Bomber Custom', 'jaket-bomber-custom', 'Jaket bomber dengan desain custom, bahan taslan berkualitas', 350000, 75, 12, 'Taslan', 1, 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=500'),
(3, 'Jaket Varsity', 'jaket-varsity', 'Jaket varsity dengan aksen klasik, cocok untuk komunitas dan sekolah', 285000, 60, 12, 'Fleece', 0, 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=500'),
(3, 'Jaket Windbreaker', 'jaket-windbreaker', 'Jaket windbreaker anti air dan angin, ringan dan nyaman', 265000, 90, 12, 'Parasut', 0, 'https://images.unsplash.com/photo-1544022613-e87ca75a784a?w=500'),
(4, 'Seragam Kerja Set', 'seragam-kerja-set', 'Set seragam kerja lengkap dengan celana, bahan drill premium', 275000, 100, 24, 'Drill', 1, 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=500'),
(4, 'Seragam Sekolah', 'seragam-sekolah', 'Seragam sekolah berkualitas, nyaman dipakai seharian', 175000, 300, 50, 'Teteron', 0, 'https://images.unsplash.com/photo-1604671801908-6f0c6a092c05?w=500'),
(5, 'Kaos Polos Premium', 'kaos-polos-premium', 'Kaos polos bahan cotton combed 30s, lembut dan adem', 75000, 400, 24, 'Cotton Combed 30s', 0, 'https://images.unsplash.com/photo-1576566588028-4147f3842f27?w=500'),
(5, 'Kaos Sablon Custom', 'kaos-sablon-custom', 'Kaos dengan sablon custom desain, bisa request sablon plastisol atau rubber', 85000, 350, 24, 'Cotton Combed 24s', 0, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=500'),
(6, 'Celana Kerja Formal', 'celana-kerja-formal', 'Celana kerja formal bahan premium, cocok untuk kantor', 195000, 120, 12, 'Drill Premium', 0, 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=500'),
(6, 'Celana Chino', 'celana-chino', 'Celana chino casual, nyaman dan stylish', 165000, 100, 12, 'Chino Stretch', 0, 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=500');

-- ============================================
-- TABEL GAMBAR PRODUK (Multiple Images)
-- ============================================
CREATE TABLE gambar_produk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produk_id INT NOT NULL,
    nama_file VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) DEFAULT NULL,
    urutan INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_produk (produk_id),
    CONSTRAINT fk_gambar_produk FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABEL PELANGGAN
-- ============================================
CREATE TABLE pelanggan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    telepon VARCHAR(20),
    whatsapp VARCHAR(20),
    perusahaan VARCHAR(255) DEFAULT NULL,
    alamat TEXT,
    kota VARCHAR(100),
    provinsi VARCHAR(100),
    kode_pos VARCHAR(10),
    catatan TEXT,
    total_transaksi INT DEFAULT 0,
    total_pembelian DECIMAL(15, 2) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_telepon (telepon)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample pelanggan
INSERT INTO pelanggan (nama, email, telepon, whatsapp, perusahaan, alamat, kota, provinsi) VALUES
('Budi Santoso', 'budi@majubersama.com', '08123456789', '08123456789', 'PT. Maju Bersama', 'Jl. Industri No. 10', 'Jakarta', 'DKI Jakarta'),
('Siti Rahayu', 'siti@berkah.com', '08234567890', '08234567890', 'CV. Berkah Textile', 'Jl. Tekstil No. 25', 'Bandung', 'Jawa Barat'),
('Ahmad Hidayat', 'ahmad@harapan.sch.id', '08345678901', '08345678901', 'Sekolah Harapan Bangsa', 'Jl. Pendidikan No. 5', 'Semarang', 'Jawa Tengah');

-- ============================================
-- TABEL PENJUALAN/TRANSAKSI
-- ============================================
CREATE TABLE penjualan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_invoice VARCHAR(50) NOT NULL UNIQUE,
    pelanggan_id INT DEFAULT NULL,
    nama_pelanggan VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    telepon VARCHAR(20),
    whatsapp VARCHAR(20),
    alamat TEXT,
    subtotal DECIMAL(12, 2) NOT NULL DEFAULT 0,
    diskon DECIMAL(12, 2) DEFAULT 0,
    ongkir DECIMAL(12, 2) DEFAULT 0,
    total_harga DECIMAL(12, 2) NOT NULL DEFAULT 0,
    metode_pembayaran ENUM('cash', 'transfer', 'dp') DEFAULT 'cash',
    jumlah_dp DECIMAL(12, 2) DEFAULT 0,
    status_pembayaran ENUM('unpaid', 'dp', 'paid') DEFAULT 'unpaid',
    status ENUM('pending', 'confirmed', 'processing', 'production', 'quality_check', 'completed', 'shipped', 'cancelled') DEFAULT 'pending',
    tanggal_deadline DATE DEFAULT NULL,
    catatan TEXT,
    tanggal_order TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_invoice (no_invoice),
    INDEX idx_status (status),
    INDEX idx_tanggal (tanggal_order),
    INDEX idx_pelanggan (pelanggan_id),
    CONSTRAINT fk_penjualan_pelanggan FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABEL DETAIL PENJUALAN
-- ============================================
CREATE TABLE detail_penjualan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    penjualan_id INT NOT NULL,
    produk_id INT NOT NULL,
    nama_produk VARCHAR(255) NOT NULL COMMENT 'Snapshot nama produk saat transaksi',
    jumlah INT NOT NULL,
    ukuran VARCHAR(10) DEFAULT NULL,
    warna VARCHAR(50) DEFAULT NULL,
    catatan_item TEXT DEFAULT NULL,
    harga_satuan DECIMAL(12, 2) NOT NULL,
    subtotal DECIMAL(12, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_penjualan (penjualan_id),
    INDEX idx_produk (produk_id),
    CONSTRAINT fk_detail_penjualan FOREIGN KEY (penjualan_id) REFERENCES penjualan(id) ON DELETE CASCADE,
    CONSTRAINT fk_detail_produk FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABEL STOK HISTORY (Untuk Prediksi & Laporan)
-- ============================================
CREATE TABLE stok_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produk_id INT DEFAULT NULL,
    kategori_id INT DEFAULT NULL,
    bulan TINYINT NOT NULL,
    tahun SMALLINT NOT NULL,
    stok_awal INT DEFAULT 0,
    stok_masuk INT DEFAULT 0,
    stok_keluar INT DEFAULT 0,
    stok_akhir INT DEFAULT 0,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_periode (bulan, tahun),
    UNIQUE KEY unique_periode (kategori_id, bulan, tahun),
    CONSTRAINT fk_stok_produk FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE SET NULL,
    CONSTRAINT fk_stok_kategori FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert data stok history untuk prediksi (Data 2024-2025)
INSERT INTO stok_history (kategori_id, bulan, tahun, stok_awal, stok_masuk, stok_keluar, stok_akhir) VALUES
-- Kemeja 2024
(1, 1, 2024, 200, 100, 80, 220),
(1, 2, 2024, 220, 80, 90, 210),
(1, 3, 2024, 210, 120, 100, 230),
(1, 4, 2024, 230, 90, 95, 225),
(1, 5, 2024, 225, 130, 110, 245),
(1, 6, 2024, 245, 150, 130, 265),
(1, 7, 2024, 265, 100, 105, 260),
(1, 8, 2024, 260, 140, 120, 280),
(1, 9, 2024, 280, 160, 140, 300),
(1, 10, 2024, 300, 170, 150, 320),
(1, 11, 2024, 320, 190, 170, 340),
(1, 12, 2024, 340, 220, 200, 360),
-- Kemeja 2025
(1, 1, 2025, 360, 110, 95, 375),
-- Polo Shirt 2024
(2, 1, 2024, 300, 150, 120, 330),
(2, 2, 2024, 330, 120, 130, 320),
(2, 3, 2024, 320, 180, 150, 350),
(2, 4, 2024, 350, 140, 145, 345),
(2, 5, 2024, 345, 200, 170, 375),
(2, 6, 2024, 375, 220, 190, 405),
(2, 7, 2024, 405, 160, 165, 400),
(2, 8, 2024, 400, 200, 180, 420),
(2, 9, 2024, 420, 240, 210, 450),
(2, 10, 2024, 450, 260, 230, 480),
(2, 11, 2024, 480, 290, 260, 510),
(2, 12, 2024, 510, 330, 300, 540),
-- Polo Shirt 2025
(2, 1, 2025, 540, 170, 150, 560),
-- Jaket 2024
(3, 1, 2024, 100, 50, 40, 110),
(3, 2, 2024, 110, 40, 45, 105),
(3, 3, 2024, 105, 60, 50, 115),
(3, 4, 2024, 115, 45, 48, 112),
(3, 5, 2024, 112, 55, 52, 115),
(3, 6, 2024, 115, 70, 65, 120),
(3, 7, 2024, 120, 80, 75, 125),
(3, 8, 2024, 125, 85, 80, 130),
(3, 9, 2024, 130, 90, 85, 135),
(3, 10, 2024, 135, 95, 90, 140),
(3, 11, 2024, 140, 100, 95, 145),
(3, 12, 2024, 145, 110, 100, 155),
-- Seragam 2024
(4, 1, 2024, 150, 80, 70, 160),
(4, 2, 2024, 160, 70, 75, 155),
(4, 3, 2024, 155, 90, 85, 160),
(4, 4, 2024, 160, 85, 80, 165),
(4, 5, 2024, 165, 100, 95, 170),
(4, 6, 2024, 170, 120, 110, 180),
(4, 7, 2024, 180, 150, 140, 190),
(4, 8, 2024, 190, 160, 150, 200),
(4, 9, 2024, 200, 140, 130, 210),
(4, 10, 2024, 210, 130, 120, 220),
(4, 11, 2024, 220, 125, 115, 230),
(4, 12, 2024, 230, 135, 125, 240);

-- ============================================
-- TABEL USER/ADMIN
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL COMMENT 'Hashed dengan bcrypt',
    nama_lengkap VARCHAR(100),
    telepon VARCHAR(20),
    foto VARCHAR(255) DEFAULT NULL,
    role ENUM('super_admin', 'admin', 'operator') DEFAULT 'operator',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    login_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert user admin default (password: admin123)
INSERT INTO users (username, email, password, nama_lengkap, telepon, role, status) VALUES
('admin', 'admin@sinarjaya-konveksi.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', '085647352998', 'super_admin', 'active'),
('operator', 'operator@sinarjaya-konveksi.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operator', '085647352998', 'operator', 'active');

-- ============================================
-- TABEL PENGATURAN WEBSITE
-- ============================================
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_group VARCHAR(50) DEFAULT 'general',
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('text', 'textarea', 'number', 'email', 'url', 'image', 'json', 'boolean') DEFAULT 'text',
    setting_label VARCHAR(100) DEFAULT NULL,
    is_public TINYINT(1) DEFAULT 1 COMMENT 'Apakah setting ditampilkan di website',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_group (setting_group),
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert pengaturan default
INSERT INTO settings (setting_group, setting_key, setting_value, setting_type, setting_label) VALUES
-- General Settings
('general', 'nama_perusahaan', 'SINAR JAYA KONVEKSI', 'text', 'Nama Perusahaan'),
('general', 'tagline', 'Produsen Pakaian Berkualitas', 'text', 'Tagline'),
('general', 'deskripsi', 'SINAR JAYA KONVEKSI - Produsen pakaian berkualitas tinggi.', 'textarea', 'Deskripsi Website'),
('general', 'tahun_berdiri', '2017', 'number', 'Tahun Berdiri'),
('general', 'pendiri', 'Muhammad Salim dan Muhammad Salman Alfarisi', 'text', 'Pendiri'),
('general', 'jumlah_karyawan', '32', 'number', 'Jumlah Karyawan'),
('general', 'logo', 'logo.png', 'image', 'Logo'),
('general', 'favicon', 'favicon.ico', 'image', 'Favicon'),
-- Contact Settings
('contact', 'telepon', '+62 856 4735 2998', 'text', 'No. Telepon'),
('contact', 'whatsapp', '6285647352998', 'text', 'No. WhatsApp'),
('contact', 'email', 'info@sinarjaya-konveksi.com', 'email', 'Email'),
('contact', 'alamat', 'Desa Padurenan, Kecamatan Gebog, Kabupaten Kudus, Jawa Tengah', 'textarea', 'Alamat Lengkap'),
('contact', 'kota', 'Kudus', 'text', 'Kota'),
('contact', 'provinsi', 'Jawa Tengah', 'text', 'Provinsi'),
('contact', 'kode_pos', '59354', 'text', 'Kode Pos'),
('contact', 'jam_operasional', 'Senin - Sabtu: 08:00 - 17:00, Minggu: Tutup', 'text', 'Jam Operasional'),
-- Social Media Settings
('social', 'facebook', 'https://www.facebook.com/share/1BrUVgcHFc/', 'url', 'Facebook'),
('social', 'instagram', 'https://www.instagram.com/sinar_jaya_konveksi_kudus', 'url', 'Instagram'),
('social', 'shopee', 'https://s.shopee.co.id/10xHFKtWol', 'url', 'Shopee'),
('social', 'tiktok', '', 'url', 'TikTok'),
('social', 'youtube', '', 'url', 'YouTube'),
-- Business Settings
('business', 'min_order', '12', 'number', 'Minimum Order'),
('business', 'estimasi_produksi', '7-14 hari kerja', 'text', 'Estimasi Produksi'),
('business', 'dp_minimum', '50', 'number', 'DP Minimum (%)'),
-- Stats Settings
('stats', 'pengalaman_tahun', '8', 'number', 'Tahun Pengalaman'),
('stats', 'pelanggan_puas', '1000', 'number', 'Pelanggan Puas'),
('stats', 'proyek_selesai', '3500', 'number', 'Proyek Selesai');

-- ============================================
-- TABEL GALERI
-- ============================================
CREATE TABLE galeri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    deskripsi TEXT,
    gambar VARCHAR(255) NOT NULL,
    kategori ENUM('produksi', 'kegiatan', 'produk', 'workshop') DEFAULT 'produk',
    urutan INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kategori (kategori),
    INDEX idx_status (status),
    INDEX idx_urutan (urutan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert galeri contoh
INSERT INTO galeri (judul, kategori, gambar, deskripsi, urutan) VALUES
('Proses Produksi Kemeja', 'produksi', 'https://images.unsplash.com/photo-1558171813-4c088753af8f?w=600', 'Workshop Utama', 1),
('Quality Control', 'produksi', 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600', 'Pengecekan kualitas', 2),
('Hasil Produksi', 'produksi', 'https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?w=600', 'Siap kirim', 3),
('Tim Produksi', 'kegiatan', 'https://images.unsplash.com/photo-1556761175-5973dc0f32e7?w=600', 'Tim profesional', 4),
('Workshop Utama', 'workshop', 'https://images.unsplash.com/photo-1558618047-f4b511bfa673?w=600', 'Area produksi', 5),
('Koleksi Kemeja', 'produk', 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=600', 'Kemeja premium', 6),
('Koleksi Polo Shirt', 'produk', 'https://images.unsplash.com/photo-1625910513413-5fc45759de53?w=600', 'Polo corporate', 7),
('Koleksi Jaket', 'produk', 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=600', 'Jaket bomber', 8);

-- ============================================
-- TABEL KONTAK/PESAN
-- ============================================
CREATE TABLE kontak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telepon VARCHAR(20),
    perusahaan VARCHAR(100) DEFAULT NULL,
    subjek ENUM('inquiry', 'quotation', 'order', 'custom', 'partnership', 'other') DEFAULT 'inquiry',
    pesan TEXT NOT NULL,
    status ENUM('unread', 'read', 'replied', 'archived') DEFAULT 'unread',
    catatan_admin TEXT DEFAULT NULL,
    replied_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABEL TESTIMONIAL
-- ============================================
CREATE TABLE testimonial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    perusahaan VARCHAR(100) DEFAULT NULL,
    jabatan VARCHAR(100) DEFAULT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    rating TINYINT DEFAULT 5,
    testimoni TEXT NOT NULL,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    urutan INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_urutan (urutan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert testimonial contoh
INSERT INTO testimonial (nama, perusahaan, jabatan, foto, rating, testimoni, status, urutan) VALUES
('Budi Santoso', 'PT. Maju Bersama', 'Manager HRD', 'https://i.pravatar.cc/100?img=1', 5, 'Kualitas produk sangat memuaskan dan pengerjaan tepat waktu.', 'active', 1),
('Siti Rahayu', 'CV. Berkah Textile', 'Owner', 'https://i.pravatar.cc/100?img=5', 5, 'Pelayanan ramah dan harga kompetitif. Sangat recommended!', 'active', 2),
('Ahmad Hidayat', 'Sekolah Harapan Bangsa', 'Kepala Sekolah', 'https://i.pravatar.cc/100?img=3', 4, 'Seragam sekolah berkualitas. Anak-anak nyaman memakainya.', 'active', 3);

-- ============================================
-- TABEL PREDIKSI STOK
-- ============================================
CREATE TABLE prediksi_stok (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori_id INT DEFAULT NULL,
    bulan TINYINT NOT NULL,
    tahun SMALLINT NOT NULL,
    algoritma ENUM('moving_average', 'linear_regression', 'exponential_smoothing', 'trend_analysis') NOT NULL,
    nilai_prediksi INT NOT NULL,
    nilai_aktual INT DEFAULT NULL,
    akurasi DECIMAL(5, 2) DEFAULT NULL COMMENT 'Persentase akurasi prediksi',
    mape DECIMAL(5, 2) DEFAULT NULL COMMENT 'Mean Absolute Percentage Error',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_periode (bulan, tahun),
    INDEX idx_algoritma (algoritma),
    CONSTRAINT fk_prediksi_kategori FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABEL LOG AKTIVITAS
-- ============================================
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    model VARCHAR(100) DEFAULT NULL COMMENT 'Nama tabel yang diakses',
    model_id INT DEFAULT NULL COMMENT 'ID record yang diakses',
    old_values JSON DEFAULT NULL,
    new_values JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at),
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABEL TENTANG PERUSAHAAN
-- ============================================
CREATE TABLE about (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert data about perusahaan
INSERT INTO about (section, title, content, image) VALUES
('sejarah', 'Sejarah Perusahaan', 'SINAR JAYA KONVEKSI didirikan pada tahun 2017 oleh dua bersaudara, Bapak Muhammad Salim dan Bapak Muhammad Salman Alfarisi di Desa Padurenan, Kecamatan Gebog, Kabupaten Kudus.', 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600'),
('visi', 'Visi', 'Menjadi perusahaan konveksi terdepan yang menghasilkan produk berkualitas tinggi dengan pelayanan terbaik.', NULL),
('misi', 'Misi', '1. Menyediakan produk konveksi berkualitas tinggi\n2. Memberikan pelayanan terbaik\n3. Mengembangkan SDM profesional\n4. Menerapkan teknologi dan inovasi\n5. Membangun hubungan jangka panjang', NULL),
('keunggulan', 'Mengapa Memilih Kami', 'Bahan berkualitas premium, proses produksi terstandar, pengerjaan tepat waktu, dan harga bersaing. Didukung oleh 32 karyawan profesional.', 'https://images.unsplash.com/photo-1558171813-4c088753af8f?w=600');

-- ============================================
-- VIEW UNTUK LAPORAN
-- ============================================

-- View Ringkasan Produk per Kategori
DROP VIEW IF EXISTS v_produk_per_kategori;
CREATE VIEW v_produk_per_kategori AS
SELECT 
    k.id as kategori_id,
    k.nama_kategori,
    k.icon,
    COUNT(p.id) as jumlah_produk,
    COALESCE(SUM(p.stok), 0) as total_stok,
    COALESCE(SUM(p.stok * p.harga), 0) as nilai_inventori,
    SUM(CASE WHEN p.stok < 50 THEN 1 ELSE 0 END) as stok_menipis,
    SUM(CASE WHEN p.featured = 1 THEN 1 ELSE 0 END) as produk_unggulan
FROM kategori k
LEFT JOIN produk p ON k.id = p.kategori_id AND p.status = 'active'
GROUP BY k.id, k.nama_kategori, k.icon;

-- View Penjualan Bulanan
DROP VIEW IF EXISTS v_penjualan_bulanan;
CREATE VIEW v_penjualan_bulanan AS
SELECT 
    YEAR(tanggal_order) as tahun,
    MONTH(tanggal_order) as bulan,
    COUNT(*) as jumlah_transaksi,
    SUM(total_harga) as total_penjualan,
    AVG(total_harga) as rata_rata_transaksi
FROM penjualan
WHERE status IN ('completed', 'shipped')
GROUP BY YEAR(tanggal_order), MONTH(tanggal_order)
ORDER BY tahun DESC, bulan DESC;

-- View Dashboard Summary
DROP VIEW IF EXISTS v_dashboard_summary;
CREATE VIEW v_dashboard_summary AS
SELECT 
    (SELECT COUNT(*) FROM produk WHERE status = 'active') as total_produk,
    (SELECT COUNT(*) FROM kategori WHERE status = 'active') as total_kategori,
    (SELECT COALESCE(SUM(stok), 0) FROM produk WHERE status = 'active') as total_stok,
    (SELECT COUNT(*) FROM penjualan WHERE status NOT IN ('cancelled')) as total_pesanan,
    (SELECT COALESCE(SUM(total_harga), 0) FROM penjualan WHERE status IN ('completed', 'shipped')) as total_pendapatan,
    (SELECT COUNT(*) FROM pelanggan WHERE status = 'active') as total_pelanggan,
    (SELECT COUNT(*) FROM kontak WHERE status = 'unread') as pesan_belum_dibaca;

-- ============================================
-- RE-ENABLE FOREIGN KEY CHECKS
-- ============================================
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- SELESAI!
-- ============================================
-- 
-- INFORMASI AKUN DEFAULT:
-- ========================
-- Username: admin
-- Password: admin123
-- Email: admin@sinarjaya-konveksi.com
-- Role: Super Admin
--
-- Username: operator  
-- Password: admin123
-- Email: operator@sinarjaya-konveksi.com
-- Role: Operator
--
-- ============================================
