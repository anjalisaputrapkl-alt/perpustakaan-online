# Modul 6: Sistem Notifikasi & Broadcast

## 1. Notifications Service
**File: `src/NotificationsService.php`**

Pusat pengiriman pesan internal sistem. Mendukung beberapa jenis:
- **`overdue`**: Notifikasi otomatis saat buku telat.
- **`new_book`**: Broadcast massal saat ada koleksi baru.
- **`return`**: Konfirmasi saat buku sudah dikembalikan.

## 2. Broadcast Massal (Pengumuman)
Saat admin menambah buku baru di `books.php`, sistem memanggil fungsi broadcast:
```php
$helper->broadcastNotification(
    $sid,       // School ID
    $students,  // Array daftar semua ID siswa
    'new_book', // Jenis
    'Buku Baru Terarsip',
    'Halo! Buku ' . $title . ' sudah bisa dipinjam loh!'
);
```

## 3. Push Notif di Dashboard
Di dashboard siswa, terdapat icon lonceng dengan *badge* angka merah. Data ini ditarik real-time dari tabel `notifications` dimana status `is_read = 0`.

---
*Fakta Teknis: Sistem notifikasi ini memastikan komunikasi dua arah. Admin tidak perlu mengirim WA manual ke siswa yang telat mengembalikan buku.*
