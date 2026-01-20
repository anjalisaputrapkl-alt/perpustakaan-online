-- ===================================================
-- Tabel Favorit Siswa
-- File: sql/migrations/favorites.sql
-- ===================================================

-- Buat tabel favorit_siswa jika belum ada
CREATE TABLE IF NOT EXISTS favorit_siswa (
    id_favorit INT AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT NOT NULL,
    id_buku INT NOT NULL,
    kategori VARCHAR(100),
    tanggal_ditambahkan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY idx_siswa (id_siswa),
    KEY idx_buku (id_buku),
    KEY idx_kategori (kategori),
    UNIQUE KEY unique_favorit (id_siswa, id_buku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Koleksi Favorit Siswa';

-- Insert sample data (optional, bisa dihapus)
-- INSERT INTO favorit_siswa (id_siswa, id_buku, kategori) VALUES
-- (1, 5, 'Programming'),
-- (1, 8, 'Database'),
-- (2, 3, 'Web Development');
