# Modul 4: Sirkulasi Otomatis & Logika Denda

## 1. Peminjaman (Borrow)
**File: `public/borrows.php`**

Menggunakan alur **Transactional SQL**.
1. Cek ketersediaan buku (`copies` > 0).
2. Insert ke tabel `borrows` dengan status `borrowed`.
3. Update tabel `books` (Kurangi 1 copy).
4. Jika berhasil semua, `COMMIT`. Jika gagal salah satu, `ROLLBACK`.

## 2. Pengembalian & Kalkulasi Denda
**Logika Backend:**
Sistem membandingkan `due_at` (tenggat) dengan tanggal server saat ini.
```php
if ($now > $dueDate) {
    $diff = $now->diff($dueDate);
    $daysLate = $diff->days;
    $fineAmount = $daysLate * $late_fine;
}
```
Nominal `$late_fine` diambil dinamis dari pengaturan masing-masing sekolah di tabel `schools`.

## 3. Notifikasi Pengembalian
Saat pengembalian sukses, sistem memicu `NotificationsService` untuk mengirim kabar ke akun Siswa bahwa buku telah diterima kembali.

---
*Fakta Teknis: Perhitungan denda dilakukan secara Real-Time. Artinya, setiap kali halaman dibuka, status 'overdue' (terlambat) akan selalu diperbarui otomatis mengikuti waktu server.*
