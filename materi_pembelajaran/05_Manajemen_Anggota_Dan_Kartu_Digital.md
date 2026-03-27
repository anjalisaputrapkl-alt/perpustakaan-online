# Modul 5: Manajemen Anggota & Kartu Digital

## 1. Sinkronisasi Akun Anggota
**File: `public/members.php`**

Data anggota disimpan di dua tabel utama:
- **`members`**: Berisi data perpustakaan (nisn, jatah pinjam, role).
- **`users`**: Berisi data untuk login (username/nisn, password hash).

Ketika admin menambah anggota, sistem menggunakan **NISN sebagai Password Default**.
```php
$hashed_password = password_hash($_POST['nisn'], PASSWORD_BCRYPT);
// Insert User baru agar Siswa bisa langsung login pakai NISN-nya
```

## 2. Kartu Anggota Digital (E-Card)
**File: `public/student-card.php`**

Halaman ini menghasilkan kartu ID yang bisa diprint atau disimpan di HP siswa.
- **Barcode Samping**: Menghasilkan barcode dari NISN menggunakan library `JsBarcode`.
- **Foto Profil**: Mengambil data foto dari tabel `siswa` (integrasi data profile).

## 3. Limitasi Jatah Pinjam (Quota)
Setiap role memiliki limit berbeda (misal: Siswa maks 3 buku, Guru maks 10 buku).
Logika pengecekan dilakukan di `public/borrows.php` sebelum transaksi diproses.

---
*Fakta Teknis: Dengan sistem ini, sekolah tidak perlu lagi mencetak kartu fisik mahal. Siswa cukup menunjukkan layar HP mereka ke Scanner di meja perpustakaan.*
