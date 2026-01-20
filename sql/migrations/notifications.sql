-- ===================================================
-- Tabel Notifikasi Siswa
-- File: sql/migrations/notifications.sql
-- ===================================================

-- Buat tabel notifikasi jika belum ada
CREATE TABLE IF NOT EXISTS notifikasi (
    id_notifikasi INT AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT NOT NULL,
    judul VARCHAR(255) NOT NULL,
    pesan TEXT NOT NULL,
    jenis_notifikasi ENUM('telat', 'peringatan', 'pengembalian', 'info', 'sukses', 'buku', 'default') DEFAULT 'default',
    tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
    status_baca TINYINT(1) DEFAULT 0 COMMENT '0 = belum dibaca, 1 = sudah dibaca',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY idx_siswa (id_siswa),
    KEY idx_status (status_baca),
    KEY idx_jenis (jenis_notifikasi),
    KEY idx_tanggal (tanggal),
    FULLTEXT KEY ft_search (judul, pesan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- CONTOH DATA NOTIFIKASI
-- Uncomment untuk menambahkan data sampel
-- ===================================================

-- Notifikasi Keterlambatan
INSERT INTO notifikasi (id_siswa, judul, pesan, jenis_notifikasi, tanggal, status_baca) VALUES
(1, 'Buku Telat Dikembalikan', 'Buku "Clean Code" belum dikembalikan. Tenggat: 2024-01-15. Denda: Rp 5.000/hari', 'telat', DATE_SUB(NOW(), INTERVAL 3 DAY), 0),
(1, 'Peringatan: Denda Diperoleh', 'Anda telah dikenakan denda sebesar Rp 15.000 untuk keterlambatan pengembalian buku', 'peringatan', DATE_SUB(NOW(), INTERVAL 5 DAY), 1),
(1, 'Notifikasi Pengembalian Buku', 'Jangan lupa mengembalikan buku "Design Patterns" sebelum tanggal 2024-01-20', 'pengembalian', DATE_SUB(NOW(), INTERVAL 1 DAY), 0),
(1, 'Informasi Terbaru', 'Perpustakaan akan ditutup pada tanggal 25 Januari untuk pemeliharaan sistem', 'info', NOW(), 0),
(1, 'Peminjaman Berhasil', 'Anda berhasil meminjam buku "Refactoring" pada 2024-01-10', 'sukses', DATE_SUB(NOW(), INTERVAL 7 DAY), 1),
(1, 'Katalog Buku Baru', 'Ada 5 buku baru dalam katalog perpustakaan: "Microservices", "Cloud Native", dan lainnya', 'buku', DATE_SUB(NOW(), INTERVAL 2 DAY), 1);

-- Notifikasi untuk siswa lain
INSERT INTO notifikasi (id_siswa, judul, pesan, jenis_notifikasi, tanggal, status_baca) VALUES
(2, 'Buku Siap Diambil', 'Buku yang Anda pesan "Introduction to Algorithms" sudah tersedia di perpustakaan', 'info', NOW(), 0),
(2, 'Peminjaman Berhasil', 'Anda berhasil meminjam 3 buku pada 2024-01-10', 'sukses', DATE_SUB(NOW(), INTERVAL 2 DAY), 1);

-- ===================================================
-- QUERY BERGUNA
-- ===================================================

-- Cek struktur tabel
-- DESCRIBE notifikasi;

-- Lihat notifikasi berdasarkan ID siswa
-- SELECT * FROM notifikasi WHERE id_siswa = 1 ORDER BY tanggal DESC;

-- Hitung notifikasi belum dibaca
-- SELECT COUNT(*) FROM notifikasi WHERE id_siswa = 1 AND status_baca = 0;

-- Update status baca
-- UPDATE notifikasi SET status_baca = 1 WHERE id_notifikasi = 1;

-- Hapus notifikasi lama (lebih dari 30 hari)
-- DELETE FROM notifikasi WHERE tanggal < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Statistik notifikasi
-- SELECT 
--     jenis_notifikasi,
--     COUNT(*) as total,
--     SUM(CASE WHEN status_baca = 0 THEN 1 ELSE 0 END) as unread
-- FROM notifikasi
-- WHERE id_siswa = 1
-- GROUP BY jenis_notifikasi;
