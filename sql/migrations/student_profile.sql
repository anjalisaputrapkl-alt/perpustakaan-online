-- =====================================================
-- Database Migration: Student Profile Module
-- =====================================================
-- Migration ini memastikan tabel siswa ada dengan semua kolom
-- yang dibutuhkan untuk profil siswa
-- Safe: Menggunakan IF NOT EXISTS dan ADD COLUMN IF NOT EXISTS
-- =====================================================

-- Pastikan tabel siswa ada
-- Jika ada, akan mengabaikan error
CREATE TABLE IF NOT EXISTS `siswa` (
    `id_siswa` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `nis` VARCHAR(20),
    `nisn` VARCHAR(20),
    `kelas` VARCHAR(20),
    `jurusan` VARCHAR(50),
    `tanggal_lahir` DATE,
    `jenis_kelamin` CHAR(1),
    `alamat` TEXT,
    `email` VARCHAR(100),
    `no_hp` VARCHAR(15),
    `foto` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Index
    KEY `idx_nis` (`nis`),
    KEY `idx_nisn` (`nisn`),
    KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jika tabel sudah ada, pastikan semua kolom ada
-- (Jangan error jika kolom sudah ada)

ALTER TABLE `siswa` 
    ADD COLUMN IF NOT EXISTS `nama_lengkap` VARCHAR(100),
    ADD COLUMN IF NOT EXISTS `nis` VARCHAR(20),
    ADD COLUMN IF NOT EXISTS `nisn` VARCHAR(20),
    ADD COLUMN IF NOT EXISTS `kelas` VARCHAR(20),
    ADD COLUMN IF NOT EXISTS `jurusan` VARCHAR(50),
    ADD COLUMN IF NOT EXISTS `tanggal_lahir` DATE,
    ADD COLUMN IF NOT EXISTS `jenis_kelamin` CHAR(1),
    ADD COLUMN IF NOT EXISTS `alamat` TEXT,
    ADD COLUMN IF NOT EXISTS `email` VARCHAR(100),
    ADD COLUMN IF NOT EXISTS `no_hp` VARCHAR(15),
    ADD COLUMN IF NOT EXISTS `foto` VARCHAR(255),
    ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Create uploads directory folder via filesystem (manual)
-- mkdir -p /uploads/siswa/
-- chmod 755 /uploads/siswa/

-- =====================================================
-- Optional: Jika ingin tambah user account dan password untuk siswa
-- =====================================================
-- Uncomment jika ada kolom username/password di siswa
-- ALTER TABLE `siswa` 
--     ADD COLUMN IF NOT EXISTS `username` VARCHAR(50),
--     ADD COLUMN IF NOT EXISTS `password` VARCHAR(255),
--     ADD UNIQUE KEY `unique_username` (`username`);

-- =====================================================
-- Sample data (opsional - untuk testing)
-- =====================================================
-- INSERT IGNORE INTO `siswa` 
-- (id_siswa, nama_lengkap, nis, nisn, kelas, jurusan, tanggal_lahir, jenis_kelamin, email, no_hp, alamat)
-- VALUES 
-- (1, 'Ahmad Risky Pratama', '001', '1234567890001', 'XI RPL', 'Rekayasa Perangkat Lunak', '2006-05-15', 'L', 'ahmad@example.com', '08123456789', 'Jl. Merdeka No. 10'),
-- (2, 'Siti Nurhaliza', '002', '1234567890002', 'XI RPL', 'Rekayasa Perangkat Lunak', '2006-08-20', 'P', 'siti@example.com', '08234567890', 'Jl. Sudirman No. 25');
