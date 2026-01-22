-- ==========================================
-- TABEL FAVORIT_SISWA (BUKU FAVORIT)
-- ==========================================

CREATE TABLE IF NOT EXISTS favorit_siswa (
    id_favorit INT PRIMARY KEY AUTO_INCREMENT,
    id_siswa INT NOT NULL,
    id_buku INT NOT NULL,
    kategori VARCHAR(100),
    tanggal_ditambahkan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_siswa (id_siswa),
    KEY idx_buku (id_buku),
    KEY idx_siswa_buku (id_siswa, id_buku),
    UNIQUE KEY unique_siswa_buku (id_siswa, id_buku),
    FOREIGN KEY (id_siswa) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (id_buku) REFERENCES buku(id_buku) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
