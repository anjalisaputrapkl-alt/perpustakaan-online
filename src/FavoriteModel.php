<?php
/**
 * Favorite Model
 * Menangani operasi buku favorit siswa
 */

class FavoriteModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Ambil daftar kategori unik dari tabel buku
     * 
     * @return array - Daftar kategori
     */
    public function getCategories() {
        try {
            $query = "
                SELECT DISTINCT kategori
                FROM buku
                WHERE kategori IS NOT NULL AND kategori != ''
                ORDER BY kategori ASC
            ";

            $stmt = $this->pdo->prepare($query);
            if (!$stmt->execute()) {
                throw new Exception('Gagal mengambil kategori');
            }

            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Ambil daftar buku berdasarkan kategori
     * 
     * @param string $category - Kategori buku (optional)
     * @return array - Daftar buku
     */
    public function getBooksByCategory($category = null) {
        try {
            if ($category) {
                $query = "
                    SELECT 
                        id_buku,
                        judul,
                        penulis,
                        kategori,
                        cover
                    FROM buku
                    WHERE kategori = ?
                    ORDER BY judul ASC
                ";
                $stmt = $this->pdo->prepare($query);
                $stmt->execute([$category]);
            } else {
                $query = "
                    SELECT 
                        id_buku,
                        judul,
                        penulis,
                        kategori,
                        cover
                    FROM buku
                    ORDER BY judul ASC
                ";
                $stmt = $this->pdo->prepare($query);
                $stmt->execute();
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Cek apakah buku sudah ada di favorit siswa
     * 
     * @param int $studentId - ID siswa
     * @param int $bookId - ID buku
     * @return bool - true jika sudah favorit
     */
    public function checkDuplicate($studentId, $bookId) {
        try {
            $query = "
                SELECT COUNT(*) as total
                FROM favorit_siswa
                WHERE id_siswa = ? AND id_buku = ?
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$studentId, $bookId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['total'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Tambah buku ke favorit siswa
     * 
     * @param int $studentId - ID siswa
     * @param int $bookId - ID buku
     * @param string $category - Kategori buku (optional)
     * @return bool - Berhasil atau tidak
     */
    public function addFavorite($studentId, $bookId, $category = null) {
        try {
            // Cek duplikasi
            if ($this->checkDuplicate($studentId, $bookId)) {
                throw new Exception('Buku sudah ada di favorit Anda');
            }

            // Ambil kategori dari buku jika tidak diberikan
            if (!$category) {
                $bookQuery = "SELECT kategori FROM buku WHERE id_buku = ?";
                $bookStmt = $this->pdo->prepare($bookQuery);
                $bookStmt->execute([$bookId]);
                $book = $bookStmt->fetch(PDO::FETCH_ASSOC);
                $category = $book['kategori'] ?? null;
            }

            // Insert ke tabel favorit
            $query = "
                INSERT INTO favorit_siswa (id_siswa, id_buku, kategori)
                VALUES (?, ?, ?)
            ";

            $stmt = $this->pdo->prepare($query);
            if (!$stmt->execute([$studentId, $bookId, $category])) {
                throw new Exception('Gagal menambah buku ke favorit');
            }

            return true;
        } catch (Exception $e) {
            throw new Exception('Error: ' . $e->getMessage());
        }
    }

    /**
     * Ambil daftar buku favorit siswa
     * 
     * @param int $studentId - ID siswa
     * @param string $category - Filter kategori (optional)
     * @return array - Daftar favorit
     */
    public function getFavorites($studentId, $category = null) {
        try {
            if ($category) {
                $query = "
                    SELECT 
                        f.id_favorit,
                        f.id_siswa,
                        f.id_buku,
                        f.kategori,
                        f.tanggal_ditambahkan,
                        b.judul,
                        b.penulis,
                        b.kategori as buku_kategori,
                        b.cover
                    FROM favorit_siswa f
                    JOIN buku b ON f.id_buku = b.id_buku
                    WHERE f.id_siswa = ? AND f.kategori = ?
                    ORDER BY f.tanggal_ditambahkan DESC
                ";
                $stmt = $this->pdo->prepare($query);
                $stmt->execute([$studentId, $category]);
            } else {
                $query = "
                    SELECT 
                        f.id_favorit,
                        f.id_siswa,
                        f.id_buku,
                        f.kategori,
                        f.tanggal_ditambahkan,
                        b.judul,
                        b.penulis,
                        b.kategori as buku_kategori,
                        b.cover
                    FROM favorit_siswa f
                    JOIN buku b ON f.id_buku = b.id_buku
                    WHERE f.id_siswa = ?
                    ORDER BY f.tanggal_ditambahkan DESC
                ";
                $stmt = $this->pdo->prepare($query);
                $stmt->execute([$studentId]);
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Hapus buku dari favorit siswa
     * 
     * @param int $studentId - ID siswa
     * @param int $favoriteId - ID favorit
     * @return bool - Berhasil atau tidak
     */
    public function removeFavorite($studentId, $favoriteId) {
        try {
            $query = "
                DELETE FROM favorit_siswa
                WHERE id_favorit = ? AND id_siswa = ?
            ";

            $stmt = $this->pdo->prepare($query);
            if (!$stmt->execute([$favoriteId, $studentId])) {
                throw new Exception('Gagal menghapus dari favorit');
            }

            return true;
        } catch (Exception $e) {
            throw new Exception('Error: ' . $e->getMessage());
        }
    }

    /**
     * Hitung total favorit siswa
     * 
     * @param int $studentId - ID siswa
     * @return int - Total favorit
     */
    public function countFavorites($studentId) {
        try {
            $query = "
                SELECT COUNT(*) as total
                FROM favorit_siswa
                WHERE id_siswa = ?
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$studentId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) $result['total'];
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Helper: Format tanggal
     */
    public static function formatDate($date) {
        if (empty($date)) {
            return '-';
        }
        $timestamp = strtotime($date);
        $now = time();
        $diff = $now - $timestamp;

        if ($diff < 60) {
            return 'Baru saja';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . ' menit lalu';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . ' jam lalu';
        } elseif ($diff < 604800) {
            return floor($diff / 86400) . ' hari lalu';
        }

        return date('d M Y', $timestamp);
    }
}
?>
