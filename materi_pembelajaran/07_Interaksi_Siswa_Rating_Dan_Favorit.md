# Modul 7: Interaksi Siswa (Rating, Ulasan, & Favorit)

## 1. Rating & Review Buku
**File: `public/book-rating.php`**

Siswa bisa memberikan bintang (1-5) dan komentar pada buku yang sudah dibaca.
- **Agregasi Bintang**: Sistem menghitung rata-rata (`AVG`) rating untuk ditampilkan di katalog umum.
- **Validasi**: Siswa hanya bisa memberi 1x rating per buku (menghindari spam menggunakan `ON DUPLICATE KEY UPDATE` atau pengecekan `COUNT`).

```php
$summaryStmt = $pdo->prepare('
    SELECT COUNT(*) as total, AVG(rating) as avg 
    FROM rating_buku WHERE id_buku = :id
');
```

## 2. Fitur Buku Favorit (Wislist)
**File: `public/favorites.php`**

Siswa dapat menandai buku yang ingin dibaca nanti.
- Data disimpan di tabel `favorites`.
- Tampilan dashboard siswa akan memprioritaskan buku-buku yang ada di daftar favorit mereka.

## 3. Rekomendasi Pintar (Simple AI)
Buku yang memiliki rating tertinggi (4.5+) akan otomatis muncul di bagian "Buku Terpopuler" pada halaman depan sekolah.

---
*Fakta Teknis: Fitur sosial ini meningkatkan minat baca siswa. Mereka bisa melihat review jujur dari teman sekelasnya sebelum meminjam buku.*
