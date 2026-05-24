-- ============================================
-- RESET DATA SINAR JAYA KONVEKSI
-- ============================================
-- Jalankan script ini untuk mengosongkan data sample
-- dan memulai dengan data kosong
-- ============================================

-- Pastikan menggunakan database yang benar
USE sinar_jaya_konveksi;

-- ============================================
-- 1. RESET TABEL PRODUK DAN TRANSAKSI
-- ============================================

-- Kosongkan tabel gambar produk
TRUNCATE TABLE gambar_produk;

-- Kosongkan tabel produk
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE produk;
SET FOREIGN_KEY_CHECKS = 1;

-- Kosongkan tabel penjualan
TRUNCATE TABLE detail_penjualan;
TRUNCATE TABLE penjualan;

-- Kosongkan tabel stok history
TRUNCATE TABLE stok_history;

-- Kosongkan tabel activity log
TRUNCATE TABLE activity_log;

-- ============================================
-- 2. RESET TABEL KONTEN (OPSIONAL)
-- ============================================
-- Uncomment jika ingin mengosongkan juga

-- Kosongkan galeri
-- TRUNCATE TABLE galeri;

-- Kosongkan testimonial
-- TRUNCATE TABLE testimonial;

-- Kosongkan pesan kontak
-- TRUNCATE TABLE kontak;

-- ============================================
-- 3. RESET AUTO INCREMENT
-- ============================================

ALTER TABLE produk AUTO_INCREMENT = 1;
ALTER TABLE gambar_produk AUTO_INCREMENT = 1;
ALTER TABLE penjualan AUTO_INCREMENT = 1;
ALTER TABLE detail_penjualan AUTO_INCREMENT = 1;
ALTER TABLE stok_history AUTO_INCREMENT = 1;
ALTER TABLE activity_log AUTO_INCREMENT = 1;

-- ============================================
-- SELESAI!
-- ============================================
-- Data produk sudah kosong
-- Kategori tetap ada
-- Anda siap menambahkan produk asli
-- ============================================

SELECT 'Data berhasil direset!' AS Status;
SELECT 'Silakan tambahkan produk melalui Admin Panel' AS Info;
