# Modul 3: Manajemen Koleksi Buku (CRUD & Inventory)

## 1. Tambah Buku (Create)
**File: `public/books.php`**

Fitur unik: **Multi-Copy Insertion**. Jika admin menginput "Jumlah Salinan: 5", sistem akan menjalankan `for loop`.
```php
for ($i = 0; $i < $quantity; $i++) {
    $stmt->execute(['title' => $_POST['title'], 'copies' => 1, ...]);
}
```
Setiap eksekusi menghasilkan baris baru dengan id unik. ID ini nantinya menjadi dasar nomor **Barcode**.

## 2. Pengelolaan Sampul (Image Upload)
Sistem menyimpan file di `img/covers/`.
- Nama file diubah menjadi format unik: `book_timestamp_uniqid.ext` untuk menghindari bentrokan nama file antar user.
- Menggunakan `move_uploaded_file()` untuk memindahkan dari memory temporary ke folder permanen.

## 3. Update & Delete (Sinkronisasi Data)
- **Update**: Mengubah informasi buku (judul/pengarang) akan mengupdate SEMUA salinan yang memiliki Judul & ISBN yang sama.
- **Delete Single**: Menghapus hanya 1 fisik buku (jika rusak).
- **Delete All**: Menghapus seluruh koleksi dengan judul tersebut.

```php
// Hapus satu fisik (Single Item)
$pdo->prepare('DELETE FROM books WHERE id = :id')->execute(['id' => $id]);
```

---
*Fakta Teknis: Sistem ini tidak menggunakan kolom "stok" (misal: stok=10), melainkan mencatat setiap buku sebagai 1 baris. Mengapa? Agar kita bisa tahu buku Laskar Pelangi yang mana yang sedang dipinjam oleh siapa.*
