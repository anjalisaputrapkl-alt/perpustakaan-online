# Modul 8: Fitur Mobile Scanner & Barcode Automation

## 1. Mobile Scan (Akses Tanpa Login)
**File: `public/scan-mobile.php`**

Admin bisa menggunakan HP sebagai alat scan tanpa perlu login berulang kali.
- **Scan Access Key**: Setiap sekolah punya kunci rahasia (token). HP yang memasukkan kunci ini diizinkan melakukan transaksi pengembalian/peminjaman.
- **Logika Keamanan**: `loginByScanKey($key)` di `src/auth.php` membuat session khusus dengan role `librarian`.

## 2. Barcode Generation
**File: `public/generate-barcode.php`**

Dapat mencetak barcode secara massal untuk ditempel ke fisik buku.
- **Barcode Buku**: Diawali dengan prefix `B-` (contoh: `B-1024`).
- **Barcode Anggota**: Menggunakan nomor NISN langsung.

## 3. Real-time Scanner Integration
Menggunakan library `Html5-Qrcode`. Kamera HP/Webcam akan mendeteksi gambar barcode, menerjemahkannya menjadi teks ID, lalu otomatis mengirim data ke backend lewat AJAX untuk diproses.

---
*Fakta Teknis: Fitur ini mengubah HP jadul sekalipun menjadi alat scanner perpustakaan canggih. Tidak perlu beli alat Barcode Scanner mahal seharga jutaan rupiah.*
