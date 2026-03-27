# Modul 9: Kustomisasi Tema & Pengaturan Sekolah

## 1. Engine Tema Dinamis
**File: `theme-loader.php` & `theme-config.json`**

Aplikasi mendukung perubahan warna dan tampilan sesuai branding sekolah.
- **CSS Variables**: Warna utama, warna aksen, dan logo disimpan dalam database sekolah.
- **Loader**: Saat halaman dimuat, `theme-loader.php` menyuntikkan kode CSS dinamis ke dalam tag `<style>` di setiap halaman.

```php
:root {
    --primary: <?php echo $school['primary_color']; ?>;
    --accent: <?php echo $school['accent_color']; ?>;
}
```

## 2. Pengaturan Operasional
**File: `public/settings.php`**

Admin dapat mengatur batasan operasional:
- **Denda per Hari**: Mengatur tarif telat.
- **Durasi Pinjam**: Mengatur jatah hari (default 7 hari).
- **Logo & Nama Sekolah**: Mengupdate profil yang tampil di kartu anggota digital.

## 3. Fitur Maintenance (Pemeliharaan)
Status buku bisa diubah menjadi "Rusak" atau "Hilang", sehingga buku tersebut otomatis hilang dari katalog pencarian siswa tapi tetap tercatat di laporan admin.

---
*Fakta Teknis: Setiap sekolah memiliki "Look & Feel" yang berbeda. Sekolah Dasar bisa menggunakan tema ceria, sementara SMK bisa menggunakan tema yang lebih profesional.*
