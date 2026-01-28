# TESTING CHECKLIST - Interactive Statistics Cards

Panduan lengkap untuk testing semua fitur yang telah diimplementasikan.

---

## 📋 PRE-TESTING SETUP

- [ ] Pastikan XAMPP running (Apache + MySQL)
- [ ] Database `perpustakaan_online` sudah ada dengan data
- [ ] User sudah login ke dashboard admin
- [ ] Browser opened ke `http://localhost/perpustakaan-online/public/index.php`

---

## 🎯 TESTING SCENARIOS

### TEST 1: HOVER EFFECTS

#### 1.1 Hover pada Card "Total Buku"
- [ ] Move mouse ke card "Total Buku"
- [ ] Expected: Shadow muncul di bawah card
- [ ] Expected: Card naik ke atas (subtle 2px translation)
- [ ] Expected: Border color berubah menjadi blue
- [ ] Expected: Tooltip muncul dengan text "Total seluruh buku yang sudah terdaftar di perpustakaan"

#### 1.2 Hover pada Card "Total Anggota"
- [ ] Move mouse ke card "Total Anggota"
- [ ] Expected: Sama seperti 1.1
- [ ] Expected: Tooltip "Total seluruh anggota perpustakaan yang terdaftar"

#### 1.3 Hover pada Card "Dipinjam"
- [ ] Move mouse ke card "Dipinjam"
- [ ] Expected: Sama seperti 1.1
- [ ] Expected: Tooltip "Total buku yang sedang dipinjam oleh anggota"

#### 1.4 Hover pada Card "Terlambat"
- [ ] Move mouse ke card "Terlambat"
- [ ] Expected: Sama seperti 1.1
- [ ] Expected: Tooltip "Total peminjaman yang sudah melewati batas waktu pengembalian"

#### 1.5 Tooltip Animation
- [ ] Hover dan lepas berkali-kali
- [ ] Expected: Smooth fade in/out animation
- [ ] Expected: Transition time ~0.3 detik

---

### TEST 2: MODAL OPEN - "TOTAL BUKU"

#### 2.1 Click Card "Total Buku"
- [ ] Click pada card "Total Buku"
- [ ] Expected: Modal overlay muncul dengan dark background
- [ ] Expected: Modal container fade in di tengah layar
- [ ] Expected: Loading spinner terlihat
- [ ] Expected: Title modal = "Daftar Semua Buku"

#### 2.2 Loading State
- [ ] Perhatikan loading spinner saat data di-fetch
- [ ] Expected: Max loading time < 2 detik (AJAX request)
- [ ] Expected: Setelah loading selesai, tabel muncul otomatis

#### 2.3 Table Content - Buku
- [ ] Expected: Kolom: Judul Buku | Penulis | Kategori | Stok | Status
- [ ] Expected: Data buku dari database tampil
- [ ] Expected: Contoh row: "Mengunyah Rindu | Budi Maryono | Fiksi | 0/1 | Habis"
- [ ] Expected: Status badge dengan warna (Tersedia = hijau, Habis = merah)

#### 2.4 Table Features
- [ ] [ ] Scroll tabel jika data banyak (max-height constraint)
- [ ] Expected: Scrollbar muncul saat konten lebih panjang
- [ ] Expected: Header tetap sticky saat scroll

#### 2.5 Mobile View - Buku
- [ ] Resize browser ke 480px width
- [ ] Expected: Modal width menjadi 95%
- [ ] Expected: Kolom "Penulis" hilang (hidden pada mobile)
- [ ] Expected: Tabel masih readable

---

### TEST 3: MODAL OPEN - "TOTAL ANGGOTA"

#### 3.1 Click Card "Total Anggota"
- [ ] Click pada card "Total Anggota"
- [ ] Expected: Modal overlay muncul
- [ ] Expected: Title modal = "Daftar Anggota"
- [ ] Expected: Loading state muncul

#### 3.2 Table Content - Anggota
- [ ] Expected: Kolom: Nama | NISN | Email | Status | Peminjaman
- [ ] Expected: Data anggota dari database tampil
- [ ] Expected: Contoh: "Anjali Saputra | 0094234 | anjali@... | Aktif | 2"
- [ ] Expected: Status badge (Aktif = hijau, Nonaktif = merah)

#### 3.3 Status Badge Colors
- [ ] Verify status "Aktif" = green background dengan dark green text
- [ ] Verify status "Nonaktif" = red background dengan dark red text

---

### TEST 4: MODAL OPEN - "DIPINJAM"

#### 4.1 Click Card "Dipinjam"
- [ ] Click pada card "Dipinjam"
- [ ] Expected: Modal overlay muncul
- [ ] Expected: Title modal = "Buku yang Sedang Dipinjam"

#### 4.2 Table Content - Peminjaman Aktif
- [ ] Expected: Kolom: Buku | Peminjam | Tgl Peminjaman | Jatuh Tempo | Status
- [ ] Expected: Data peminjaman yang returned_at IS NULL tampil
- [ ] Expected: Buku yang belum dikembalikan tampil di tabel

#### 4.3 Status Variations
- [ ] Expected: Status "Sedang Dipinjam" = yellow badge untuk normal
- [ ] Expected: Status "TERLAMBAT (X hari)" = red badge untuk overdue
- [ ] Expected: Status "Akan Jatuh Tempo (X hari)" = yellow badge untuk yang akan jatuh tempo
- [ ] Expected: Hitung DATEDIFF(due_at, NOW()) untuk days remaining

---

### TEST 5: MODAL OPEN - "TERLAMBAT"

#### 5.1 Click Card "Terlambat"
- [ ] Click pada card "Terlambat"
- [ ] Expected: Modal overlay muncul
- [ ] Expected: Title modal = "Peminjaman Terlambat"

#### 5.2 Table Content - Overdue
- [ ] Expected: Kolom: Buku | Peminjam | Tgl Peminjaman | Jatuh Tempo | Terlambat
- [ ] Expected: Hanya data dengan status='overdue' dan returned_at IS NULL
- [ ] Expected: Kolom "Terlambat" menampilkan jumlah hari (contoh: "3 hari")
- [ ] Expected: Badge merah untuk setiap row

#### 5.3 Sorting
- [ ] Expected: Data diurutkan by due_at ASC (yang paling terlambat di atas)

---

### TEST 6: MODAL INTERACTIONS

#### 6.1 Close Modal dengan Tombol X
- [ ] Click tombol X di top-right modal
- [ ] Expected: Modal fade out
- [ ] Expected: Background overlay hilang
- [ ] Expected: Bisa kembali ke dashboard

#### 6.2 Close Modal dengan Click Overlay
- [ ] Click di area gelap (overlay) di luar modal
- [ ] Expected: Modal tutup
- [ ] Expected: Background overlay hilang

#### 6.3 Open Multiple Cards Sequentially
- [ ] Click card "Total Buku" → Wait 1 sec → Close
- [ ] Click card "Total Anggota" → Wait 1 sec → Close
- [ ] Click card "Dipinjam" → Wait 1 sec → Close
- [ ] Click card "Terlambat" → Wait 1 sec → Close
- [ ] Expected: Semua modal buka tutup dengan smooth
- [ ] Expected: Tidak ada lag atau error

#### 6.4 Rapid Click
- [ ] Click card dan langsung click X sebelum loading selesai
- [ ] Expected: Loading state dihentikan/diganti dengan content jika siap
- [ ] Expected: Tidak ada error di console

---

### TEST 7: RESPONSIVE DESIGN

#### 7.1 Desktop (1920px)
- [ ] Modal max-width 900px
- [ ] Modal width 90%
- [ ] Semua kolom visible
- [ ] Table readable

#### 7.2 Tablet (768px)
- [ ] Modal width adjust dengan proper
- [ ] Kolom "Penulis" dan "Email" tetap visible (CSS media query = col-hide-mobile)
- [ ] Font size yang reasonable

#### 7.3 Mobile (480px)
- [ ] Modal width 95%
- [ ] Kolom "Penulis", "Email", "Tgl Peminjaman" hilang
- [ ] Hanya kolom penting yang tampil
- [ ] Swipe/scroll tabel masih lancar

#### 7.4 Table Overflow Mobile
- [ ] Di mobile, jika text panjang, harus wrap atau truncate
- [ ] Table harus scrollable horizontal jika perlu

---

### TEST 8: DARK MODE

#### 8.1 Toggle Dark Mode
- [ ] Check apakah ada dark mode toggle di dashboard
- [ ] Click dark mode toggle (biasanya di settings)
- [ ] Expected: Background dashboard berubah ke dark

#### 8.2 Hover Effects Dark Mode
- [ ] Hover card saat dark mode aktif
- [ ] Expected: Tooltip background = dark gray (#374151)
- [ ] Expected: Tooltip text = light color
- [ ] Expected: Shadow still visible
- [ ] Expected: Smooth transition

#### 8.3 Modal Dark Mode
- [ ] Click card saat dark mode aktif
- [ ] Expected: Modal background = dark
- [ ] Expected: Table background = dark
- [ ] Expected: Text color = light
- [ ] Expected: Contrast tetap good
- [ ] Expected: Table hover state = lighter dark gray

#### 8.4 Badge Dark Mode
- [ ] Status badges tetap terlihat jelas di dark mode
- [ ] Expected: Color contrast ratio > 4.5:1

---

### TEST 9: DATA ACCURACY

#### 9.1 Total Books Count
- [ ] Check apakah jumlah di card "Total Buku" sesuai dengan COUNT tabel books
- [ ] Open modal dan count rows
- [ ] Expected: Jumlah match

#### 9.2 Available Stock
- [ ] Di table buku, stok = (total - borrowed count)
- [ ] Verify satu buku: "The Psychology of Money"
  - Total: 14
  - Borrowed: ? (hitung dari tabel borrows)
  - Available: 14 - borrowed

#### 9.3 Members Count
- [ ] Card "Total Anggota" = COUNT dari members table
- [ ] Open modal dan verify

#### 9.4 Borrowed Count
- [ ] Card "Dipinjam" = COUNT dari borrows WHERE returned_at IS NULL
- [ ] Open modal dan verify count

#### 9.5 Overdue Count
- [ ] Card "Terlambat" = COUNT dari borrows WHERE status='overdue' AND returned_at IS NULL
- [ ] Open modal dan verify
- [ ] Check apakah data di query sesuai kondisi

---

### TEST 10: ERROR HANDLING

#### 10.1 Network Error (Simulate)
- [ ] Open DevTools Network tab
- [ ] Set throttling ke "Offline"
- [ ] Click card
- [ ] Expected: Error message muncul "Gagal memuat data. Silakan coba lagi."
- [ ] Expected: Modal tidak hang/freeze
- [ ] Reset network back to online

#### 10.2 Database Error (Simulate)
- [ ] Close database connection (stop MySQL)
- [ ] Click card
- [ ] Expected: Error message muncul
- [ ] Expected: Console log error details
- [ ] Restart database

#### 10.3 Empty Result
- [ ] Jika tidak ada data overdue
- [ ] Click card "Terlambat"
- [ ] Expected: Message "Tidak ada data untuk ditampilkan"
- [ ] Modal tetap buka, tapi empty state terlihat

---

### TEST 11: CONSOLE ERRORS

#### 11.1 Browser Console
- [ ] Open DevTools (F12) → Console tab
- [ ] Perform all tests di section TEST 1-10
- [ ] Expected: NO red error messages
- [ ] Expected: Hanya info/warning yang acceptable (jika ada)

#### 11.2 Network Tab
- [ ] Open DevTools → Network tab
- [ ] Click masing-masing card
- [ ] Expected: 4 XHR requests (to 4 endpoints)
- [ ] Expected: Status 200 OK untuk setiap request
- [ ] Expected: Response JSON valid

#### 11.3 Performance
- [ ] Check Performance tab
- [ ] Click card dan measure load time
- [ ] Expected: AJAX response < 500ms
- [ ] Expected: Rendering < 300ms
- [ ] Expected: No jank/stutter

---

### TEST 12: CROSS-BROWSER

#### 12.1 Chrome
- [ ] Open Chrome
- [ ] Visit dashboard
- [ ] Run semua tests
- [ ] Expected: ✅ Semua pass

#### 12.2 Firefox
- [ ] Open Firefox
- [ ] Visit dashboard
- [ ] Run hover test + modal test
- [ ] Expected: ✅ Semua pass

#### 12.3 Edge
- [ ] Open Microsoft Edge
- [ ] Visit dashboard
- [ ] Run hover test + modal test
- [ ] Expected: ✅ Semua pass

#### 12.4 Safari (jika available)
- [ ] Open Safari
- [ ] Run tests
- [ ] Expected: ✅ Semua pass

---

## 🐛 BUG REPORT TEMPLATE

Jika menemukan issue:

```
Title: [Bug Description]

Environment:
- Browser: [Chrome/Firefox/Edge/Safari]
- OS: [Windows/Mac/Linux]
- Screen Size: [1920x1080/768x1024/480x800]
- Dark Mode: [Yes/No]

Steps to Reproduce:
1. [Step 1]
2. [Step 2]
3. [Step 3]

Expected Result:
[What should happen]

Actual Result:
[What actually happened]

Screenshots:
[If applicable]

Console Error:
[Paste error dari DevTools Console]
```

---

## ✅ FINAL CHECKLIST

- [ ] Semua 4 cards hover effects berfungsi
- [ ] Semua 4 cards modal buka dengan benar
- [ ] Semua 4 tabel menampilkan data yang tepat
- [ ] Modal close functionality berfungsi (X dan overlay click)
- [ ] Responsive design di desktop/tablet/mobile
- [ ] Dark mode styling correct
- [ ] No console errors
- [ ] AJAX requests successful (200 OK)
- [ ] Data accuracy verified
- [ ] Cross-browser compatibility confirmed

---

## 🎉 TESTING COMPLETE!

Jika semua checklist tercentang, maka implementasi Interactive Statistics Cards sudah **SIAP PRODUCTION**! 🚀
