# ✅ QUICK TEST GUIDE - Modal Data Muncul Sekarang!

## 🚀 Langkah Testing (3 Menit)

### Step 1: Buka Dashboard
Klik atau ke URL:
```
http://localhost/perpustakaan-online/public/index.php
```

### Step 2: Buka Browser Console
Tekan **F12** → Klik tab **Console**
(Harus melihat messages seperti "Initializing modal manager...")

### Step 3: Klik Salah Satu Card
Coba klik card "Total Buku" atau "Anggota"

**Yang Seharusnya Terjadi:**
1. Overlay gelap muncul (background darkening)
2. Modal popup muncul di tengah layar
3. ✅ **Table dengan data muncul** (ini yang sebelumnya belum muncul)
4. Judul modal sesuai: "Daftar Semua Buku", "Daftar Anggota", dll

### Step 4: Cek Console Output
Di F12 Console, harus melihat:
```
✓ Card clicked: books
✓ Fetching from: /perpustakaan-online/public/api/get-stats-books.php
✓ Response: {success: true, data: Array(5), total: 5}
```

Jika melihat ini → **Feature sekarang working!** ✅

---

## 🔍 Jika Masih Tidak Muncul

### Cek 1: Network Tab
1. F12 → Network tab
2. Refresh halaman (Ctrl+R)
3. Klik card
4. Lihat request ke `get-stats-books.php`
5. Status harus **200** (jika 302 atau 403 = auth error)

### Cek 2: Console Error
1. F12 → Console tab  
2. Lihat ada error merah?
3. Common errors:
   - "Endpoint not found" → path salah
   - "Redirect" → belum login
   - "JSON parse error" → endpoint return error

### Cek 3: Test Endpoint Direct
Buka URL langsung:
```
http://localhost/perpustakaan-online/public/api/get-stats-books.php
```
- Jika muncul JSON → endpoint OK ✅
- Jika redirect ke login → logout dan login ulang
- Jika error → cek database

---

## 📋 Apa yang Difix

| Masalah | Fix |
|---------|-----|
| Session tidak dikirim ke API | Added `credentials: 'include'` ke fetch() |
| Path endpoint tidak konsisten | Changed ke absolute path `/perpustakaan-online/public/api/...` |
| Modal tidak inisialisasi | Added explicit `modalManager.init()` di DOMContentLoaded |
| Sulit debug | Added console.log di berbagai points |

---

## 🎯 Expected Behavior Setiap Card

### 1. Total Buku
- Tampil tabel dengan kolom: Judul, Penulis, Kategori, Stok, Status
- Status: "Tersedia" atau "Habis"
- Rows: Semua buku di sistem

### 2. Anggota  
- Tampil tabel dengan kolom: Nama, NISN, Email, Status, Peminjaman
- Status: "Aktif" atau "Nonaktif"
- Peminjaman: Jumlah buku yang sedang dipinjam

### 3. Sedang Dipinjam
- Tampil tabel dengan kolom: Buku, Peminjam, Tgl Peminjaman, Jatuh Tempo, Status
- Status: "Sedang Dipinjam", "Akan Jatuh Tempo", "TERLAMBAT"
- Row berwarna merah jika terlambat

### 4. Terlambat
- Tampil tabel dengan kolom: Buku, Peminjam, Tgl Peminjaman, Jatuh Tempo, Terlambat
- Terlambat: Jumlah hari telat (merah badge)
- Sorted by due_at (terdekat dulu)

---

## 💾 Files Modified

✅ `/assets/js/stats-modal.js` - Added credentials & updated paths
✅ `/public/index.php` - Added init calls
✅ `/FIX_SESSION_CREDENTIALS.md` - Dokumentasi lengkap

---

## ❓ Pertanyaan FAQ

**Q: Kenapa harus `credentials: 'include'`?**
A: AJAX tidak otomatis kirim cookies. Dengan flag ini, browser akan kirim session cookie yang berisi login info.

**Q: Kenapa absolute path?**
A: Relative path bisa bekerja berbeda tergantung dari halaman mana request dikirim. Absolute path lebih reliable.

**Q: Berapa lama data load?**
A: Tergantung jumlah data. Jika >1000 rows, bisa slow. Di-optimize dengan LIMIT bisa ditambah.

**Q: Bisa export data?**
A: Belum ada fitur export. Bisa ditambahkan nanti jika diperlukan.

---

## 🆘 Butuh Help?

Jika error masih muncul, screenshot console dan report dengan:
1. Error message yang muncul (di console)
2. Browser yang digunakan
3. Apakah sudah login sebelum test?
4. Jumlah data di database (misal: berapa buku?)

