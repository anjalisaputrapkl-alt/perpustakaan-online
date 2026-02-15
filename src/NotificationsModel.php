<?php
/**
 * Notifications Model
 * Menangani semua operasi notifikasi anggota
 */

class NotificationsModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Ambil semua notifikasi anggota
     * 
     * @param int $studentId - ID anggota
     * @param string $sort - Opsi sorting (latest, oldest, unread)
     * @return array - Daftar notifikasi
     */
    public function getNotifications($studentId, $sort = 'latest') {
        try {
            $orderBy = 'tanggal DESC';
            
            switch ($sort) {
                case 'oldest':
                    $orderBy = 'tanggal ASC';
                    break;
                case 'unread':
                    $orderBy = 'status_baca ASC, tanggal DESC';
                    break;
                case 'latest':
                default:
                    $orderBy = 'tanggal DESC';
            }

            $query = "
                SELECT 
                    id_notifikasi,
                    id_siswa,
                    judul,
                    pesan,
                    jenis_notifikasi,
                    tanggal,
                    status_baca
                FROM notifikasi
                WHERE id_siswa = ?
                ORDER BY {$orderBy}
            ";

            $stmt = $this->pdo->prepare($query);
            if (!$stmt->execute([$studentId])) {
                throw new Exception('Gagal mengambil notifikasi');
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception('Error: ' . $e->getMessage());
        }
    }

    /**
     * Ambil notifikasi detail berdasarkan ID
     * 
     * @param int $notificationId - ID notifikasi
     * @param int $studentId - ID anggota (untuk verifikasi kepemilikan)
     * @return array - Detail notifikasi
     */
    public function getNotificationDetail($notificationId, $studentId) {
        try {
            $query = "
                SELECT 
                    id_notifikasi,
                    id_siswa,
                    judul,
                    pesan,
                    jenis_notifikasi,
                    tanggal,
                    status_baca
                FROM notifikasi
                WHERE id_notifikasi = ? AND id_siswa = ?
            ";

            $stmt = $this->pdo->prepare($query);
            if (!$stmt->execute([$notificationId, $studentId])) {
                throw new Exception('Gagal mengambil detail notifikasi');
            }

            $notification = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$notification) {
                throw new Exception('Notifikasi tidak ditemukan');
            }

            return $notification;
        } catch (Exception $e) {
            throw new Exception('Error: ' . $e->getMessage());
        }
    }

    /**
     * Update status baca notifikasi
     * 
     * @param int $notificationId - ID notifikasi
     * @param int $studentId - ID anggota (untuk verifikasi)
     * @param int $status - Status baca (0 atau 1)
     * @return bool - Berhasil atau tidak
     */
    public function updateNotificationStatus($notificationId, $studentId, $status = 1) {
        try {
            $query = "
                UPDATE notifikasi
                SET status_baca = ?
                WHERE id_notifikasi = ? AND id_siswa = ?
            ";

            $stmt = $this->pdo->prepare($query);
            if (!$stmt->execute([$status, $notificationId, $studentId])) {
                throw new Exception('Gagal update status notifikasi');
            }

            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            throw new Exception('Error: ' . $e->getMessage());
        }
    }

    /**
     * Tandai semua notifikasi sebagai dibaca
     * 
     * @param int $studentId - ID anggota
     * @return bool - Berhasil atau tidak
     */
    public function markAllAsRead($studentId) {
        try {
            $query = "
                UPDATE notifikasi
                SET status_baca = 1
                WHERE id_siswa = ? AND status_baca = 0
            ";

            $stmt = $this->pdo->prepare($query);
            if (!$stmt->execute([$studentId])) {
                throw new Exception('Gagal tandai semua sebagai dibaca');
            }

            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            throw new Exception('Error: ' . $e->getMessage());
        }
    }

    /**
     * Hitung notifikasi belum dibaca
     * 
     * @param int $studentId - ID anggota
     * @return int - Jumlah notifikasi belum dibaca
     */
    public function countUnread($studentId) {
        try {
            $query = "
                SELECT COUNT(*) as total
                FROM notifikasi
                WHERE id_siswa = ? AND status_baca = 0
            ";

            $stmt = $this->pdo->prepare($query);
            if (!$stmt->execute([$studentId])) {
                throw new Exception('Gagal hitung notifikasi belum dibaca');
            }

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['total'];
        } catch (Exception $e) {
            throw new Exception('Error: ' . $e->getMessage());
        }
    }

    /**
     * Hitung statistik notifikasi
     * 
     * @param int $studentId - ID anggota
     * @return array - Statistik notifikasi
     */
    public function getStatistics($studentId) {
        try {
            $query = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status_baca = 0 THEN 1 ELSE 0 END) as unread,
                    SUM(CASE WHEN jenis_notifikasi = 'telat' THEN 1 ELSE 0 END) as overdue,
                    SUM(CASE WHEN jenis_notifikasi = 'peringatan' THEN 1 ELSE 0 END) as warning,
                    SUM(CASE WHEN jenis_notifikasi = 'pengembalian' THEN 1 ELSE 0 END) as return_notif,
                    SUM(CASE WHEN jenis_notifikasi = 'info' THEN 1 ELSE 0 END) as info
                FROM notifikasi
                WHERE id_siswa = ?
            ";

            $stmt = $this->pdo->prepare($query);
            if (!$stmt->execute([$studentId])) {
                throw new Exception('Gagal hitung statistik');
            }

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            // Rename return_notif back to return untuk kompatibilitas
            if (isset($result['return_notif'])) {
                $result['return'] = $result['return_notif'];
                unset($result['return_notif']);
            }
            return $result;
        } catch (Exception $e) {
            throw new Exception('Error: ' . $e->getMessage());
        }
    }

    /**
     * Hapus notifikasi berdasarkan ID
     * 
     * @param int $notificationId - ID notifikasi
     * @param int $studentId - ID anggota (untuk verifikasi)
     * @return bool - Berhasil atau tidak
     */
    public function deleteNotification($notificationId, $studentId) {
        try {
            $query = "
                DELETE FROM notifikasi
                WHERE id_notifikasi = ? AND id_siswa = ?
            ";

            $stmt = $this->pdo->prepare($query);
            if (!$stmt->execute([$notificationId, $studentId])) {
                throw new Exception('Gagal hapus notifikasi');
            }

            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            throw new Exception('Error: ' . $e->getMessage());
        }
    }

    /**
     * Helper: Format tanggal dengan timezone awareness
     * 
     * @param string $date - Tanggal dari database
     * @return string - Tanggal format yang bagus
     */
    public static function formatDate($date) {
        if (empty($date) || $date === '0000-00-00 00:00:00') {
            return '-';
        }
        return date('d M Y H:i', strtotime($date));
    }

    /**
     * Helper: Get icon untuk jenis notifikasi
     * 
     * @param string $type - Jenis notifikasi
     * @return string - Icon name
     */
    public static function getIcon($type) {
        $icons = [
            'telat' => 'mdi:alert-circle',
            'peringatan' => 'mdi:alert-triangle',
            'pengembalian' => 'mdi:package-variant-closed',
            'info' => 'mdi:information',
            'sukses' => 'mdi:check-circle',
            'buku' => 'mdi:book',
            'default' => 'mdi:bell'
        ];
        return $icons[$type] ?? $icons['default'];
    }

    /**
     * Helper: Get label untuk jenis notifikasi
     * 
     * @param string $type - Jenis notifikasi
     * @return string - Label
     */
    public static function getLabel($type) {
        $labels = [
            'telat' => 'Keterlambatan',
            'peringatan' => 'Peringatan',
            'pengembalian' => 'Pengembalian Buku',
            'info' => 'Informasi',
            'sukses' => 'Sukses',
            'buku' => 'Katalog Buku',
            'default' => 'Notifikasi'
        ];
        return $labels[$type] ?? $labels['default'];
    }

    /**
     * Helper: Get CSS class untuk badge type
     * 
     * @param string $type - Jenis notifikasi
     * @return string - CSS class
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
}
?>
