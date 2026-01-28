# QUICK START GUIDE - Interactive Statistics Cards

Panduan cepat untuk implementasi Interactive Statistics Cards.

---

## ⚡ 5 MENIT SETUP

### Step 1: Verifikasi File Dibuat (1 min)
```
✅ /public/api/get-stats-books.php
✅ /public/api/get-stats-members.php
✅ /public/api/get-stats-borrowed.php
✅ /public/api/get-stats-overdue.php
✅ /assets/js/stats-modal.js
✅ /assets/css/index.css (sudah updated)
✅ /public/index.php (sudah updated)
```

### Step 2: Verifikasi Database (1 min)
```sql
-- Run in PHPMyAdmin atau MySQL CLI
-- Check tabel sudah ada dengan data:

SELECT COUNT(*) FROM books;      -- Min 1 row
SELECT COUNT(*) FROM members;    -- Min 1 row
SELECT COUNT(*) FROM borrows;    -- Min 1 row
```

### Step 3: Clear Browser Cache (1 min)
```
Chrome: Ctrl+Shift+Delete → Clear browsing data
Firefox: Ctrl+Shift+Delete → Select all, Clear Now
Safari: Develop → Empty Web Caches
```

### Step 4: Test Hover Effect (1 min)
```
1. Open http://localhost/perpustakaan-online/public/index.php
2. Hover mouse ke card "Total Buku"
3. Expected: Tooltip appears + shadow effect
```

### Step 5: Test Modal Click (1 min)
```
1. Click card "Total Buku"
2. Expected: Modal popup muncul dengan tabel data
3. Click X untuk tutup
```

**SELESAI!** Semua feature sudah jalan! 🎉

---

## 🧪 QUICK TEST CHECKLIST

```
Hover "Total Buku" → ✅ Tooltip + Shadow
Click "Total Buku" → ✅ Modal + Table
Close Modal → ✅ Works (X or overlay)

Hover "Total Anggota" → ✅ Tooltip + Shadow
Click "Total Anggota" → ✅ Modal + Table

Hover "Dipinjam" → ✅ Tooltip + Shadow
Click "Dipinjam" → ✅ Modal + Table

Hover "Terlambat" → ✅ Tooltip + Shadow
Click "Terlambat" → ✅ Modal + Table

Dark Mode → ✅ Styling correct
Mobile View (480px) → ✅ Responsive
Console → ✅ No errors
```

---

## 🔧 IF SOMETHING NOT WORKING

### Modal Tidak Terbuka?
```
1. Open DevTools (F12)
2. Check Console tab untuk error
3. Verify path: /perpustakaan-online/public/api/get-stats-books.php
4. Ensure user sudah login
5. Check MySQL connection di /src/db.php
```

### Tooltip Tidak Muncul?
```
1. Check apakah CSS loaded: CTRL+SHIFT+I → Elements → .stat
2. Look for ::after pseudo element
3. Verify data-tooltip attribute ada di HTML
4. Check browser zoom (jika zoom > 150%, mungkin di luar viewport)
```

### Data Tidak Muncul di Table?
```
1. Check Network tab: apakah request 200 OK?
2. Klik response, lihat JSON valid?
3. Verify school_id filter di query
4. Check database punya data untuk school_id user
```

### Button X Tidak Bekerja?
```
1. Verify modal-close element ada di HTML
2. Check stats-modal.js loaded (F12 → Sources)
3. Verify event listener attached
4. Check console untuk JS errors
```

---

## 📁 FILE STRUCTURE REFERENCE

```
perpustakaan-online/
├── public/
│   ├── index.php (UPDATED - dengan modal HTML & script tag)
│   └── api/
│       ├── get-stats-books.php (NEW)
│       ├── get-stats-members.php (NEW)
│       ├── get-stats-borrowed.php (NEW)
│       └── get-stats-overdue.php (NEW)
├── assets/
│   ├── css/
│   │   └── index.css (UPDATED - dengan hover & modal CSS)
│   └── js/
│       └── stats-modal.js (NEW)
└── src/
    ├── auth.php (used in endpoints)
    └── db.php (used in endpoints)
```

---

## 🎯 WHAT WAS ADDED

### HTML Changes:
- ✅ `data-stat-type` attribute di 4 cards
- ✅ `data-tooltip` attribute di 4 cards
- ✅ Modal overlay HTML element

### CSS Changes:
- ✅ `.stat:hover` effects
- ✅ `.stat::after` tooltip styling
- ✅ `.modal-*` class styles
- ✅ `.status-badge` classes
- ✅ Responsive design @media queries
- ✅ Dark mode support

### JavaScript:
- ✅ New file: `stats-modal.js`
- ✅ modalManager object dengan methods
- ✅ Event listeners untuk card clicks
- ✅ AJAX fetch implementation
- ✅ Dynamic table rendering

### PHP Endpoints:
- ✅ 4 endpoint files dengan AJAX response
- ✅ Database queries dengan prepared statements
- ✅ School-based filtering (multi-tenant)
- ✅ JSON response formatting

---

## 🚀 DEPLOYMENT CHECKLIST

Sebelum deploy ke production:

```
- [ ] Semua file sudah di-backup
- [ ] Testing lakukan di development environment
- [ ] Database backup done
- [ ] CSS minification (optional)
- [ ] JS minification (optional)
- [ ] Browser compatibility tested
- [ ] Mobile responsiveness tested
- [ ] Dark mode tested
- [ ] No console errors
- [ ] AJAX endpoints responding correctly
- [ ] Load testing done (multiple concurrent clicks)
- [ ] Accessibility check (WCAG 2.1 AA)
```

---

## 📞 SUPPORT

Jika ada pertanyaan atau issue:

1. **Check Console**: F12 → Console untuk error messages
2. **Check Network**: F12 → Network untuk AJAX response
3. **Check Database**: Pastikan data ada di MySQL
4. **Check File Path**: Pastikan semua file di folder yang benar
5. **Check Permissions**: Pastikan file readable oleh web server

---

## 📚 DOKUMENTASI LENGKAP

Untuk detail lebih lanjut, baca:
- `DOCUMENTATION_INTERACTIVE_STATS.md` - Feature overview
- `IMPLEMENTATION_SUMMARY.md` - Implementation details
- `KODE_LENGKAP_REFERENCE.md` - Full code reference
- `TESTING_CHECKLIST.md` - Comprehensive testing guide

---

**Siap untuk production! 🎉**

Hubungi developer jika ada pertanyaan atau membutuhkan customization lebih lanjut.
