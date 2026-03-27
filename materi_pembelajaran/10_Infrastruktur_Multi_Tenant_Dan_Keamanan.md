# Modul 10: Arsitektur Multi-Tenant & Keamanan Tingkat Tinggi

## 1. Apa itu Multi-Tenant?
Satu software digunakan oleh banyak sekolah secara bersamaan tanpa data yang saling bercampur.
- **Data Isolation**: Menggunakan kolom `school_id` sebagai filter di **SETIAP** query SQL.
- **Shared Infrastructure**: Menggunakan 1 database yang sama untuk menghemat biaya server.

## 2. Trust Score & Anti-Abuse
**File: `src/MultiTenantManager.php`**

Untuk mencegah penyalahgunaan (spam pendaftaran sekolah palsu), sistem memiliki **Trust Score (Skor Kepercayaan)**.
- **Input Poin**: 
  - Pakai email `.sch.id` (+15 poin).
  - Ada aktifitas transaksi (+10 poin).
  - Verifikasi OTP sukses (+20 poin).
- **Auto-Activation**: Jika skor mencapai 70, status sekolah otomatis berubah dari `trial` menjadi `active`.

## 3. Keamanan SQL (SQL Injection Prevention)
Seluruh query menggunakan **PDO Prepared Statements**.
```php
// CARA AMAN (Kita pakai ini)
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$user_input]); // SQL Injection mustahil tembus
```

## 4. Keamanan Gambar (Security)
Saat user upload foto/cover, sistem mengecek *MIME Type* dan ekstensi file untuk mencegah user jahat mengupload file `.php` (virus) ke folder sistem.

---
*Penutup Kurikulum: Dengan pemahaman 10 modul ini, kamu bukan lagi sekadar pembuat web, tapi sudah menjadi Arsitek Sistem Digital yang tangguh. Selamat atas kelulusan materi teknismu!*
