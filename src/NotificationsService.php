<?php
/**
 * Notifications Service
 * Generate notifications dinamis dari tabel peminjaman dan buku
 * Tanpa perlu tabel notifikasi baru
 */

class NotificationsService {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Ambil semua notifikasi dari berbagai sumber
     */
    public function getAllNotifications($studentId) {
        $notifications = [];

        try {
            // 1. Buku hampir jatuh tempo (3 hari ke depan)
            $notifications = array_merge($notifications, $this->getUpcomingNotifications($studentId));

            // 2. Buku sudah jatuh tempo
            $notifications = array_merge($notifications, $this->getOverdueNotifications($studentId));

            // 3. Buku berhasil dikembalikan (3 hari terakhir)
            $notifications = array_merge($notifications, $this->getReturnedNotifications($studentId));

            // 4. Buku baru ditambahkan (7 hari terakhir)
            $notifications = array_merge($notifications, $this->getNewBooksNotifications($studentId));

            // Urutkan berdasarkan tanggal terbaru
            usort($notifications, function($a, $b) {
                return strtotime($b['tanggal']) - strtotime($a['tanggal']);
            });

            return $notifications;
        } catch (Exception $e) {
            throw new Exception('Error: ' . $e->getMessage());
        }
    }

    /**
     * NOTIFIKASI 1: Buku hampir jatuh tempo (< 3 hari)
     */
    public function getUpcomingNotifications($studentId) {
        try {
            $query = "
                SELECT 
                    CONCAT('upcoming_', p.id_peminjaman) as id_notifikasi,
                    p.id_siswa,
                    CONCAT('📚 ', b.judul, ' akan jatuh tempo') as judul,
                    CONCAT('Buku \"', b.judul, '\" harus dikembalikan pada ', DATE_FORMAT(p.tanggal_kembali, '%d %b %Y'), '.') as pesan,
                    'pengembalian' as jenis_notifikasi,
                    p.tanggal_kembali as tanggal,
                    0 as status_baca
                FROM peminjaman p
                JOIN buku b ON p.id_buku = b.id_buku
                WHERE p.id_siswa = ?
                    AND p.status = 'dipinjam'
                    AND DATE(p.tanggal_kembali) <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                    AND DATE(p.tanggal_kembali) > CURDATE()
                ORDER BY p.tanggal_kembali ASC
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$studentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * NOTIFIKASI 2: Buku sudah jatuh tempo
     */
    public function getOverdueNotifications($studentId) {
        try {
            $query = "
                SELECT 
                    CONCAT('overdue_', p.id_peminjaman) as id_notifikasi,
                    p.id_siswa,
                    CONCAT('⚠️ ', b.judul, ' sudah jatuh tempo!') as judul,
                    CONCAT('Buku \"', b.judul, '\" seharusnya dikembalikan pada ', DATE_FORMAT(p.tanggal_kembali, '%d %b %Y'), '. Silakan kembalikan segera untuk menghindari denda.') as pesan,
                    'telat' as jenis_notifikasi,
                    p.tanggal_kembali as tanggal,
                    0 as status_baca
                FROM peminjaman p
                JOIN buku b ON p.id_buku = b.id_buku
                WHERE p.id_siswa = ?
                    AND p.status = 'dipinjam'
                    AND DATE(p.tanggal_kembali) < CURDATE()
                ORDER BY p.tanggal_kembali ASC
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$studentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * NOTIFIKASI 3: Buku berhasil dikembalikan (3 hari terakhir)
     */
    public function getReturnedNotifications($studentId) {
        try {
            $query = "
                SELECT 
                    CONCAT('returned_', p.id_peminjaman) as id_notifikasi,
                    p.id_siswa,
                    CONCAT('✅ ', b.judul, ' telah dikembalikan') as judul,
                    CONCAT('Terima kasih telah mengembalikan \"', b.judul, '\". Pengembalian dicatat pada ', DATE_FORMAT(p.tanggal_dikembalikan, '%d %b %Y %H:%i'), '.') as pesan,
                    'sukses' as jenis_notifikasi,
                    p.tanggal_dikembalikan as tanggal,
                    0 as status_baca
                FROM peminjaman p
                JOIN buku b ON p.id_buku = b.id_buku
                WHERE p.id_siswa = ?
                    AND p.status = 'dikembalikan'
                    AND p.tanggal_dikembalikan IS NOT NULL
                    AND DATE(p.tanggal_dikembalikan) >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
                ORDER BY p.tanggal_dikembalikan DESC
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$studentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * NOTIFIKASI 4: Buku baru ditambahkan (7 hari terakhir)
     * Fallback: jika tidak ada kolom waktu_input/created_at
     */
    public function getNewBooksNotifications($studentId) {
        try {
            // Cek apakah ada kolom created_at atau waktu_input
            $hasCreatedAtColumn = $this->columnExists('buku', 'created_at');
            $hasWaktuInputColumn = $this->columnExists('buku', 'waktu_input');
            
            if (!$hasCreatedAtColumn && !$hasWaktuInputColumn) {
                // Fallback: tampilkan buku tanpa filter waktu (max 5 buku)
                return $this->getNewBooksWithoutTimestamp($studentId);
            }

            $dateColumn = $hasCreatedAtColumn ? 'created_at' : 'waktu_input';

            $query = "
                SELECT 
                    CONCAT('newbook_', b.id_buku) as id_notifikasi,
                    {$studentId} as id_siswa,
                    CONCAT('🆕 ', b.judul, ' - Buku Baru!') as judul,
                    CONCAT('Buku \"', b.judul, '\" karya ', COALESCE(b.penulis, 'Penulis Terkenal'), ' telah ditambahkan ke perpustakaan. Kategori: ', COALESCE(b.kategori, 'Umum'), '.') as pesan,
                    'buku' as jenis_notifikasi,
                    b.{$dateColumn} as tanggal,
                    0 as status_baca
                FROM buku b
                WHERE DATE(b.{$dateColumn}) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                ORDER BY b.{$dateColumn} DESC
                LIMIT 10
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Fallback jika query error
            return $this->getNewBooksWithoutTimestamp($studentId);
        }
    }

    /**
     * Fallback: Ambil buku terbaru tanpa timestamp
     * Gunakan ID sebagai proxy untuk buku baru
     */
    private function getNewBooksWithoutTimestamp($studentId) {
        try {
            $query = "
                SELECT 
                    CONCAT('newbook_', b.id_buku) as id_notifikasi,
                    {$studentId} as id_siswa,
                    CONCAT('🆕 ', b.judul, ' - Buku Baru!') as judul,
                    CONCAT('Buku \"', b.judul, '\" karya ', COALESCE(b.penulis, 'Penulis Terkenal'), ' telah ditambahkan ke perpustakaan. Kategori: ', COALESCE(b.kategori, 'Umum'), '.') as pesan,
                    'buku' as jenis_notifikasi,
                    CURDATE() as tanggal,
                    0 as status_baca
                FROM buku b
                ORDER BY b.id_buku DESC
                LIMIT 5
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Cek apakah kolom ada di tabel
     */
    private function columnExists($table, $column) {
        try {
            $query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = ? AND TABLE_SCHEMA = DATABASE()";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$table, $column]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Helper: Format tanggal relatif
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

        return date('d M Y H:i', $timestamp);
    }

    /**
     * Helper: Get icon untuk jenis notifikasi
     */
    public static function getIcon($type) {
        $icons = [
            'telat' => 'mdi:alert-circle',
            'peringatan' => 'mdi:alert-triangle',
            'pengembalian' => 'mdi:package-variant-closed',
            'info' => 'mdi:information',
            'sukses' => 'mdi:check-circle',
            'buku' => 'mdi:book-open-page-variant',
            'default' => 'mdi:bell'
        ];
        return $icons[$type] ?? $icons['default'];
    }

    /**
     * Helper: Get label untuk jenis notifikasi
     */
    public static function getLabel($type) {
        $labels = [
            'telat' => 'Keterlambatan',
            'peringatan' => 'Peringatan',
            'pengembalian' => 'Pengembalian Buku',
            'info' => 'Informasi',
            'sukses' => 'Sukses',
            'buku' => 'Buku Baru',
            'default' => 'Notifikasi'
        ];
        return $labels[$type] ?? $labels['default'];
    }

    /**
     * Helper: Get CSS class untuk badge
     */
    public static function getBadgeClass($type) {
        $classes = [
            'telat' => 'notification-badge-overdue',
            'peringatan' => 'notification-badge-warning',
            'pengembalian' => 'notification-badge-return',
            'info' => 'notification-badge-info',
            'sukses' => 'notification-badge-success',
            'buku' => 'notification-badge-book',
            'default' => 'notification-badge-default'
        ];
        return $classes[$type] ?? $classes['default'];
    }

    /**
     * Get statistik notifikasi
     */
    public function getStatistics($studentId) {
        $notifications = $this->getAllNotifications($studentId);
        
        $stats = [
            'total' => count($notifications),
            'unread' => count(array_filter($notifications, fn($n) => !$n['status_baca'])),
            'overdue' => count(array_filter($notifications, fn($n) => $n['jenis_notifikasi'] === 'telat')),
            'warning' => count(array_filter($notifications, fn($n) => $n['jenis_notifikasi'] === 'peringatan')),
            'return' => count(array_filter($notifications, fn($n) => $n['jenis_notifikasi'] === 'pengembalian')),
            'info' => count(array_filter($notifications, fn($n) => $n['jenis_notifikasi'] === 'info')),
            'success' => count(array_filter($notifications, fn($n) => $n['jenis_notifikasi'] === 'sukses')),
            'newbooks' => count(array_filter($notifications, fn($n) => $n['jenis_notifikasi'] === 'buku'))
        ];

        return $stats;
    }
}
?>
