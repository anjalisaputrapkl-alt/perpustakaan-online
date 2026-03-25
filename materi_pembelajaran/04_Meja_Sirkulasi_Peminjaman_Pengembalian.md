# Modul 4: Transaksi Sirkulasi & Penghitungan Denda

## A. Sirkulasi Pengembalian Buku (Returning)
**File Terkait: `public/borrows.php`**

**1. Logika Menghitung Selisih Keterlambatan**
Saat URL menerima parameter aksi pengembalian buku `&action=return`, server mengecek kolom `due_at`. Jika waktu sekarang (`$now`) lebih besar dari waktu jatuh tempo (`$dueDate`), maka sistem akan mengkalkulasi selisih hari keterlambatan, lalu mengalikannya dengan rasio `$late_fine` (nominal denda).

```php
if ($borrowData['due_at']) {
    $dueDate = new DateTime($borrowData['due_at']);
    $now = new DateTime();
    
    // Mengecek apabila waktu sekarang > waktu jatuh tempo
    if ($now > $dueDate) {
        $diff = $now->diff($dueDate);
        $daysLate = $diff->days;
        $fineAmount = $daysLate * $late_fine; // Mengalikan telat x nominal denda
    }
}
```

**2. Update Status Peminjaman (Database Commit)**
Jika Denda telah terkalkulasi, maka sistem mengubah status kolom "status" ke "returned", serta mendata denda ke "unpaid". 
```php
$stmt = $pdo->prepare(
    'UPDATE borrows SET returned_at=NOW(), status="returned", fine_amount=:fine, fine_status="unpaid"
     WHERE id=:id AND school_id=:sid'
);
$stmt->execute(['id' => (int) $_GET['id'], 'sid' => $sid, 'fine' => $fineAmount]);
```

**3. Memicu Penambahan Stok Kembali**
Jika pengembalian database borrows tereksekusi, tabel master rak ketersediaan di buku akan bertambah `+ 1`.
```php
$stmt = $pdo->prepare('UPDATE books SET copies = copies + 1 WHERE id = :bid');
$stmt->execute(['bid' => $borrowData['book_id']]);
```
