# Modul 3: Manajemen Katalog & Data Item

## A. Fitur Pemisahan Fisik Buku Tunggal via Barcode
**File: `public/books.php` dan logika aplikasi umum**

**1. Skema Barcode Per Copy**
Berbeda dengan sistem perpustakaan konvensional yang hanya mencatat judul buku (berbasis ISBN), sistem ini mencatat fisik buku secara terpisah (Item Tracking).

Jika 1 buku ditambahkan sejumlah *N* copies, maka `N` akan digenerate sebagai Barcode Identifier pada sistem (dicatat atau diproses di `books.php` secara logis), maka database `books` akan mengkalkulasi total kopi dan merender barcode untuk masing-masing id fisik.

Di dalam view:
```php
// Query mengambil data master buku untuk dirender ke tabel frontend
$stmt = $pdo->prepare('SELECT * FROM books WHERE school_id = :sid ORDER BY created_at DESC');
$stmt->execute(['sid' => $sid]);
$books = $stmt->fetchAll();
```

## B. Fitur Favorit (Wishlist)
**File: `public/favorites.php`**

**1. Logic Insert Bookmark**
Jika user menekan tombol 'Heart' (Favorit) di `student-dashboard.php`, sistem menyimpan `book_id` yang terikat pada `member_id` ke tabel `favorites`.
```php
$stmt = $pdo->prepare("INSERT INTO favorites (school_id, member_id, book_id) VALUES (:sid, :mid, :bid)");
$stmt->execute([
    'sid' => $school_id,
    'mid' => $member_id,
    'bid' => $book_id
]);
```
