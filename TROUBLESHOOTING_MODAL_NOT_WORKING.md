# 🔧 TROUBLESHOOTING - Modal Tidak Muncul Data

Panduan langkah demi langkah untuk debug masalah "Card ada gerakan tapi data tidak muncul".

---

## 🔍 LANGKAH 1: VERIFIKASI BROWSER (2 menit)

### Buka DevTools
```
Tekan F12
atau Klik kanan → Inspect
```

### Go to Console Tab
```
Lihat ada error merah?
```

### Copy-paste ini ke console:
```javascript
// Test 1: Check modal HTML
console.log('Modal exists?', !!document.getElementById('statsModal'));

// Test 2: Check modal manager
console.log('modalManager exists?', typeof modalManager !== 'undefined');

// Test 3: Manual open modal
modalManager.openModal('books');
```

**Expected result:** Modal should open dengan loading spinner

---

## 🔍 LANGKAH 2: CHECK NETWORK TAB

### Buka Network Tab (F12)
```
Click "Network" tab
Clear existing requests (trash icon)
```

### Klik Card "Total Buku"
```
Lihat ada request muncul?
```

### Check request details
```
Method: GET
Status: 200 ✅ (atau 404 ❌)
URL: api/get-stats-books.php
Response: Lihat JSON response
```

**Possible issues:**

#### Issue 1: Status 404 (Not Found)
```
❌ Berarti file endpoint tidak ditemukan
✅ Solution: Verify file ada di /public/api/get-stats-books.php
```

#### Issue 2: Status 500 (Server Error)
```
❌ Berarti PHP error
✅ Solution: Click response, lihat error message
```

#### Issue 3: Status 200 tapi data error
```
❌ Berarti database connection problem
✅ Solution: Check /src/db.php connection
```

---

## 🔍 LANGKAH 3: CHECK PHP ERROR LOG

### Buka XAMPP Control Panel
```
Location: C:\xampp\apache\logs\error.log
atau
C:\xampp\mysql\data\error.log
```

### Cari error messages terkait:
```
"Undefined variable"
"Database connection"
"Parse error"
```

---

## ⚡ QUICK FIX - Path Issue

Jika Network Tab menunjukkan 404, coba fix ini:

### File: stats-modal.js
```javascript
// Sebelum:
const endpoints = {
    'books': '/perpustakaan-online/public/api/get-stats-books.php',
};

// Sesudah:
const endpoints = {
    'books': 'api/get-stats-books.php',
};
```

Mari saya perbaiki untuk Anda ✅ (sudah dilakukan)

---

## 🧪 MANUAL TESTING DI CONSOLE

### Paste ini satu per satu di console:

```javascript
// Test 1: Manual fetch books
fetch('api/get-stats-books.php')
  .then(r => r.json())
  .then(d => console.log('Books data:', d))
  .catch(e => console.log('Error:', e));
```

Expected output:
```
{
  success: true,
  data: [
    { id: 1, title: "Buku 1", ... },
    ...
  ],
  total: 7
}
```

---

## ✅ CHECKLIST UNTUK VERIFY

```
[ ] Modal HTML ada (F12 → Elements → #statsModal)
[ ] stats-modal.js di-load (F12 → Sources → assets/js/stats-modal.js)
[ ] Cards punya data-stat-type attribute
[ ] Network tab show 200 status untuk AJAX
[ ] JSON response valid
[ ] Database punya data
[ ] No red errors di console
```

---

## 🎯 COMMON ISSUES & SOLUTIONS

### Issue: "Cannot read property 'addEventListener' of null"
```
Cause: Modal HTML not found
Fix: Check index.php sudah updated dengan modal HTML
```

### Issue: "404 Not Found api/get-stats-books.php"
```
Cause: Endpoint file not found
Fix: Check /public/api/ folder punya 4 file endpoint
```

### Issue: "Undefined variable school_id"
```
Cause: User not authenticated
Fix: Make sure sudah login ke dashboard
```

### Issue: "No data appears in table"
```
Cause: Database kosong atau query error
Fix: Check database punya data untuk school_id Anda
```

---

## 📞 JIKA MASIH STUCK

### Informasi yang harus disampaikan:

1. **Console error messages (screenshot)**
2. **Network tab request details (screenshot)**
3. **Response JSON dari AJAX (copy-paste)**
4. **Database status (punya data atau kosong?)**

### Contoh baik:
```
"Saat klik card, di Network tab muncul error 500.
Ini response-nya:
{
  error: "Call to undefined function..."
}"
```

---

## 🔧 ADVANCED DEBUGGING

### Jika ingin deep dive, enable debug mode:

Buka file: `public/api/get-stats-books.php`

Tambahkan di awal:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

Ini akan show detailed error messages.

---

## ✅ SUMMARY

1. Buka F12 DevTools
2. Check Console untuk errors
3. Check Network tab untuk AJAX requests
4. Verify endpoint returns 200 & valid JSON
5. Check database punya data
6. Run manual test di console
7. Verify index.php updated dengan modal HTML

**Jika semua ok, modal harus muncul!** ✅

---

Mari troubleshoot bersama-sama!
