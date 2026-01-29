# Troubleshooting: Buttons Terima/Tolak Tidak Berfungsi

## Problem Summary

- Custom tenggat (due date) input tidak bekerja
- Button "Terima" (Approve) tidak berfungsi
- Button "Tolak" (Reject) tidak berfungsi
- Halaman tidak reload setelah click button

## Diagnosis Steps

### Step 1: Buka Browser Console

1. Klik tombol "Terima" atau "Tolak" di form
2. Buka Browser Developer Tools dengan menekan **F12**
3. Pilih tab **Console**
4. Lihat apakah ada:
   - Error messages (warna merah)
   - Log entries dengan prefix `[APPROVE]` atau `[REJECT]`

### Step 2: Check Console Logs

Anda seharusnya melihat logs seperti:

```
[APPROVE] IDs: [1, 2, 3] Due Days: 7 Due String: 2024-01-20 12:34:56
[APPROVE] Response status: 200 for ID: 1
[APPROVE] Response data: {success: true, message: "..."}
```

**Jika tidak ada logs:**

- Function tidak ter-trigger
- Gunakan Step 3

**Jika ada error:**

- Ada JavaScript error
- Lihat exact error message
- Gunakan Step 4

### Step 3: Test Input ID Manual

Di Browser Console, copy-paste ini:

```javascript
// Check if function exists
console.log("approveAllBorrowWithDue exists:", typeof approveAllBorrowWithDue);
console.log("rejectAllBorrow exists:", typeof rejectAllBorrow);

// Try to find input element
const inputs = document.querySelectorAll('input[id^="dueDays_"]');
console.log("Found input elements:", inputs.length);
inputs.forEach((input) => console.log("ID:", input.id, "Value:", input.value));
```

### Step 4: Manual API Test

Di Browser Console:

```javascript
// Test 1: Simple approve without custom due date
fetch("api/approve-borrow.php", {
  method: "POST",
  headers: { "Content-Type": "application/x-www-form-urlencoded" },
  body: "borrow_id=1", // Replace 1 with actual borrow ID
})
  .then((r) => {
    console.log("Status:", r.status);
    return r.json();
  })
  .then((d) => console.log("Response:", d))
  .catch((e) => console.error("Error:", e));

// Test 2: Approve with custom due date
fetch("api/approve-borrow.php", {
  method: "POST",
  headers: { "Content-Type": "application/x-www-form-urlencoded" },
  body: "borrow_id=1&due_at=" + encodeURIComponent("2024-01-20 12:00:00"),
})
  .then((r) => r.json())
  .then((d) => console.log("Response:", d))
  .catch((e) => console.error("Error:", e));
```

Expected response:

```json
{ "success": true, "message": "Peminjaman telah diterima" }
```

### Step 5: Check Server Error Logs

Buka file error log XAMPP:

- **Windows**: `C:\xampp\apache\logs\error.log` atau `C:\xampp\php\logs\php_errors.log`
- **Linux**: `/var/log/apache2/error.log` atau `/var/log/php_errors.log`

Cari entries dengan `[APPROVE]` atau `[REJECT]`:

```
[2024-01-20 12:30:45] [APPROVE-BORROW] borrow_id=1, due_at=2024-01-20 12:34:56, school_id=123
[2024-01-20 12:30:45] [APPROVE-BORROW] Update with due_at executed, rows affected: 1
[2024-01-20 12:30:45] [APPROVE-BORROW] Success!
```

### Step 6: Use Debug Page

Kunjungi: `http://localhost/perpustakaan-online/public/debug-borrows.php`

Halaman ini menampilkan:

- ✓ Status system
- ✓ Recent debug logs
- ✓ List pending confirmations
- ✓ Test buttons untuk API

## Common Issues & Solutions

### Issue 1: "Input tenggat tidak ditemukan" Alert

**Cause**: Input element ID salah atau tidak match
**Solution**:

```javascript
// Di console, debug:
const inputId = "dueDays_123"; // Replace 123 dengan student ID
const elem = document.getElementById(inputId);
console.log("Element found:", !!elem);
if (elem) console.log("Value:", elem.value);
```

### Issue 2: "SyntaxError: Unexpected token in JSON"

**Cause**: JSON array dari borrow IDs tidak properly encoded
**Check**:

- Apakah button onclick memiliki valid JSON?
- Test di console: `JSON.parse('[1,2,3]')`

### Issue 3: API returns 404 or "Peminjaman tidak ditemukan"

**Cause**: Borrow record sudah diprocess atau school_id tidak match
**Check**:

- Pastikan borrow ID valid dan masih status "pending_confirmation"
- Pastikan logged in dengan user yang sama school_id

### Issue 4: Page tidak reload setelah success

**Cause**: `location.reload()` tidak ter-eksekusi
**Check**:

- Apakah promise .then() di eksekusi?
- Check console untuk error

## Quick Fix Checklist

- [ ] Browser console F12 tidak ada errors
- [ ] Logs `[APPROVE]` atau `[REJECT]` muncul di console
- [ ] API response status 200
- [ ] API response body `{"success": true, ...}`
- [ ] Button onclick attribute memiliki valid JSON
- [ ] Input element ID match dengan button parameter
- [ ] Logged in sebagai admin
- [ ] Minimal 1 pending_confirmation record ada

## Server-Side Debugging

Jika semuanya di client side OK tapi API error, check server:

### Check PHP Syntax

```bash
php -l public/api/approve-borrow.php
php -l public/api/reject-borrow.php
```

### Check Database Connection

```php
// Di borrows.php atau debug page, check:
$pdo = require __DIR__ . '/../src/db.php';
echo "✓ Database connected";
```

### Enable Query Logging

Add ke approve-borrow.php:

```php
error_log("QUERY: UPDATE borrows SET status=borrowed, due_at=$due_at WHERE id=$id AND school_id=$sid");
```

### Test Database Record

```sql
-- Check if pending_confirmation record exists
SELECT id, member_id, book_id, status FROM borrows WHERE status='pending_confirmation' LIMIT 5;

-- Check if update works
UPDATE borrows SET status='borrowed' WHERE id=1 AND school_id=123;
```

## Complete Test Scenario

1. **Create test data**:

   ```sql
   INSERT INTO borrows (member_id, book_id, borrowed_at, due_at, status, school_id)
   VALUES (1, 1, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 'pending_confirmation', 1);
   ```

2. **Open borrows.php** in browser
3. Look for pending confirmation card with test data
4. **Open F12 Console**
5. **Click Terima button**
6. Check for `[APPROVE]` logs
7. Verify response in console
8. Page should reload

## Files Modified with Debug Logging

- `public/api/approve-borrow.php` - Added error_log with detailed debugging
- `public/api/reject-borrow.php` - Added error_log with detailed debugging
- `public/borrows.php` - Added console.log in JavaScript functions
- `public/debug-borrows.php` - NEW: Debug page with API tester

## Next Steps if Still Not Working

1. Post console log output (F12 → Console → Right-click → Save as)
2. Post server error_log excerpt (5-10 relevant lines)
3. Post specific error message/screenshot
4. Check file permissions on api/approve-borrow.php (should be 644 or readable)

---

**Last Updated**: 2024
**Log Prefix for Debugging**: `[APPROVE]`, `[REJECT]`, `[APPROVE-BORROW]`, `[REJECT-BORROW]`
