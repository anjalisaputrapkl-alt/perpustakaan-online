# DEBUGGING GUIDE: Button Terima/Tolak Tidak Berfungsi

## ✅ PERBAIKAN YANG TELAH DILAKUKAN

### 1. Enhanced Error Logging

**File**: `public/api/approve-borrow.php` dan `public/api/reject-borrow.php`

- ✓ Menambahkan error_log pada setiap step
- ✓ Validasi format due_at sebelum execute
- ✓ Log response rows affected
- ✓ Clear error messages

**Log yang akan muncul**:

```
[APPROVE-BORROW] borrow_id=1, due_at=2024-01-20 12:34:56, school_id=123
[APPROVE-BORROW] Update with due_at executed, rows affected: 1
[APPROVE-BORROW] Success!

[REJECT-BORROW] borrow_id=1, school_id=123
[REJECT-BORROW] Delete executed, rows affected: 1
[REJECT-BORROW] Success!
```

### 2. Improved JavaScript Functions

**File**: `public/borrows.php` (Lines 747-857)

**Enhancements**:

- ✓ JSON parse dengan try-catch block
- ✓ Validasi array dan length
- ✓ Detailed console logging untuk setiap step
- ✓ Better error messages
- ✓ Collect dan tampilkan error details

**Console logs untuk debug**:

```
[APPROVE] Starting with borrowIds: [1,2,3] inputId: dueDays_123
[APPROVE] Parsed IDs successfully: [1,2,3]
[APPROVE] Input element: <input...> Value: 7
[APPROVE] Parsed dueDays: 7
[APPROVE] Due date calculated: 2024-01-20 12:34:56 for 7 days
[APPROVE] Processing ID: 1
[APPROVE] Response status: 200 for ID: 1
[APPROVE] Response data for ID 1 : {success: true, message: "..."}
```

### 3. Button HTML Improvements

**File**: `public/borrows.php` (Lines 490-500)

- ✓ Tambah `type="button"` ke button elements
- ✓ Prevent form submission behavior

### 4. New Debug Tools

#### a) Debug Page: `public/debug-borrows.php`

Buka di browser: `http://localhost/perpustakaan-online/public/debug-borrows.php`

**Features**:

- System status check
- Recent debug logs display
- Pending confirmations list
- API test buttons (Test Approve, Test Reject, Test with custom due date)
- Manual fetch examples

#### b) Troubleshooting Guide: `TROUBLESHOOTING_BUTTONS.md`

- Step-by-step diagnosis
- Console testing commands
- Common issues & solutions
- Server-side debugging

#### c) API Test Page: `public/test-api.php`

Check API endpoints status

---

## 🔍 CARA DEBUG SEKARANG

### Scenario 1: Buttons Not Responding At All

**Step 1: Buka Browser Console (F12)**

```
Press F12 → Console tab
```

**Step 2: Click Terima Button**

**Step 3: Check Console Output**
Harus ada logs:

```
[APPROVE] Starting with borrowIds: ...
[APPROVE] Parsed IDs successfully: ...
[APPROVE] Input element: ...
[APPROVE] Parsed dueDays: 7
```

**If No Logs Appear**:

```javascript
// Copy-paste di console:
console.log("Function exists:", typeof approveAllBorrowWithDue);
console.log(
  "Input elements:",
  document.querySelectorAll('input[id^="dueDays_"]').length,
);
```

**If Still Nothing**:

- Button onclick tidak ter-trigger
- Check apakah JavaScript file ter-load
- Check for syntax errors in console

---

### Scenario 2: JSON Parse Error

**Console akan show**:

```
[APPROVE] JSON parse error: Unexpected token... Input: [1,2,...
```

**Cause**: Borrow ID array tidak proper format JSON

**Fix**: Check generated HTML

```javascript
// Di browser, right-click button → Inspect
// Check onclick attribute apakah valid JSON
onclick = "approveAllBorrowWithDue([1,2,3], 'dueDays_123')"; // OK
onclick = "approveAllBorrowWithDue('[1,2,3]', 'dueDays_123')"; // OK
```

---

### Scenario 3: Input Element Not Found

**Console akan show**:

```
[APPROVE] Error: Input tenggat tidak ditemukan dengan ID dueDays_123
```

**Cause**: Input ID tidak match atau belum ter-render

**Check**:

```javascript
// Di console, test:
const input = document.getElementById("dueDays_123");
console.log("Found:", !!input);
console.log("Value:", input?.value);

// List semua input dengan dueDays prefix:
document.querySelectorAll('input[id^="dueDays_"]').forEach((inp) => {
  console.log("ID:", inp.id, "Value:", inp.value);
});
```

---

### Scenario 4: API Returns Error

**Console akan show**:

```
[APPROVE] Response data for ID 1 : {success: false, message: "Peminjaman tidak ditemukan..."}
```

**Common Errors**:

| Message                                          | Cause                               | Solution                                                                             |
| ------------------------------------------------ | ----------------------------------- | ------------------------------------------------------------------------------------ |
| "Peminjaman tidak ditemukan atau sudah diproses" | Record sudah deleted atau tidak ada | Check database: `SELECT * FROM borrows WHERE id=1 AND status='pending_confirmation'` |
| "Format tenggat tidak valid"                     | due_at format salah                 | Check format: YYYY-MM-DD HH:MM:SS                                                    |
| Error 500                                        | PHP exception                       | Check XAMPP error log                                                                |

**Check Server Log**:

```
C:\xampp\apache\logs\error.log
atau
C:\xampp\php\logs\php_errors.log
```

Look for `[APPROVE-BORROW]` logs

---

### Scenario 5: Page Tidak Reload

**Console akan show success** tapi halaman tidak reload

**Check**:

```javascript
// Di console, test:
console.log(typeof location);
console.log(location.href);

// Manual reload:
location.reload();
```

**If Error**:

- Ada permission issue
- Coba manual reload setelah approval

---

## 📋 QUICK TEST CHECKLIST

Run ini di browser console saat ada pending confirmation data:

```javascript
// 1. Check functions exist
console.assert(
  typeof approveAllBorrowWithDue === "function",
  "Function missing!",
);
console.assert(typeof rejectAllBorrow === "function", "Function missing!");

// 2. Find first dueDays input
const input = document.querySelector('input[id^="dueDays_"]');
console.assert(input, "No input found!");
console.log("Input value:", input?.value);

// 3. Find first button
const button = document.querySelector(
  'button[onclick*="approveAllBorrowWithDue"]',
);
console.assert(button, "No button found!");

// 4. Check onclick attribute
const onclick = button?.getAttribute("onclick");
console.log("Onclick:", onclick);

// 5. Try manual parse
try {
  const match = onclick.match(
    /approveAllBorrowWithDue\((\[.*?\]), '([^']+)'\)/,
  );
  if (match) {
    const ids = JSON.parse(match[1]);
    console.log("Parsed IDs:", ids);
    console.log("Input ID:", match[2]);
  }
} catch (e) {
  console.error("Parse error:", e);
}
```

---

## 🚀 JIKA SEMUANYA OK

Jika semua test pass dan buttons berfungsi:

1. **Check Database**:

   ```sql
   SELECT id, member_id, status, due_at FROM borrows WHERE status='pending_confirmation' LIMIT 5;
   ```

   Records harus berubah status menjadi 'borrowed'

2. **Check Due Date**:

   ```sql
   SELECT id, due_at, borrowed_at FROM borrows WHERE id=1;
   ```

   due_at harus sesuai dengan tanggal + days yang di-input

3. **Page Reload**:
   Halaman harus reload dan pending confirmation form berkurang

---

## 📊 DATABASE TEST COMMANDS

### Create Test Data

```sql
-- Insert 3 test pending confirmations
INSERT INTO borrows (member_id, book_id, borrowed_at, due_at, status, school_id)
VALUES
  (1, 1, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 'pending_confirmation', 1),
  (1, 2, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 'pending_confirmation', 1),
  (2, 3, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 'pending_confirmation', 1);
```

### Verify Approval Worked

```sql
-- Check if status updated to 'borrowed'
SELECT id, member_id, status, due_at FROM borrows
WHERE member_id IN (1, 2) AND status='borrowed'
ORDER BY borrowed_at DESC LIMIT 10;

-- Check if due_at is correct (14 days from now)
SELECT id, due_at,
  DATEDIFF(due_at, NOW()) as days_from_now
FROM borrows WHERE id IN (1, 2, 3);
```

### Verify Rejection Worked

```sql
-- Check if records deleted
SELECT COUNT(*) FROM borrows WHERE id IN (1, 2, 3);
-- Should return 0 jika ditolak, 3 jika approved
```

---

## 🔧 FILES MODIFIED

| File                            | Changes                          | Purpose               |
| ------------------------------- | -------------------------------- | --------------------- |
| `public/api/approve-borrow.php` | Added error_log, validation      | Better debugging      |
| `public/api/reject-borrow.php`  | Added error_log                  | Better debugging      |
| `public/borrows.php`            | Enhanced JS, added type="button" | Better error handling |
| `public/debug-borrows.php`      | NEW                              | Debug interface       |
| `TROUBLESHOOTING_BUTTONS.md`    | NEW                              | Troubleshooting guide |

---

## 📞 SUPPORT

If still having issues:

1. **Gather Debug Info**:
   - Screenshot of F12 console with error
   - XAMPP error log excerpt
   - Database query results
   - Specific steps to reproduce

2. **Common Next Steps**:
   - Clear browser cache (Ctrl+Shift+Delete)
   - Reload page (Ctrl+F5)
   - Test in different browser
   - Check XAMPP Apache/MySQL status

3. **Last Resort**:
   - Check file permissions (chmod 644 public/api/\*.php)
   - Check PHP version compatibility
   - Verify database connection

---

**Status**: ✅ All improvements deployed
**Testing Required**: Yes - Follow debugging steps above
**Expected Result**: Buttons work, data updates in database, page reloads
