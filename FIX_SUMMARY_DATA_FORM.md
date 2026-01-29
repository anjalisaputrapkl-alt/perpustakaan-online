## FIX SUMMARY - Data di Form yang Salah

### MASALAH YANG DIIDENTIFIKASI

User melaporkan: "MASIH SALAH FORM YAAMPUNNNN MASIH KE FORM Daftar Peminjaman Aktif"

Data yang seharusnya muncul di **"Form Peminjaman Menunggu Konfirmasi"** masih muncul di **"Daftar Peminjaman Aktif"**.

### ROOT CAUSE

**File: public/borrows.php, Line 480**

Kondisi yang mengecek apakah table "Daftar Peminjaman Aktif" kosong TIDAK mengexclude status `pending_confirmation`:

```php
// BEFORE (BUG):
<?php if (empty(array_filter($borrows, fn($b) => $b['status'] !== 'returned' && $b['status'] !== 'pending_return'))): ?>

// AFTER (FIXED):
<?php if (empty(array_filter($borrows, fn($b) => $b['status'] !== 'returned' && $b['status'] !== 'pending_return' && $b['status'] !== 'pending_confirmation'))): ?>
```

**Penjelasan:**

- Ketika ada record dengan status `pending_confirmation`, kondisi lama akan FALSE (not empty)
- Ini menyebabkan table header dan struktur ditampilkan, walaupun loop di bawah correctly skip pending_confirmation
- Dengan kondisi yang sudah diperbaiki, table hanya ditampilkan jika benar-benar ada borrowed/overdue items

### FILES YANG DIUBAH

1. **public/borrows.php (Line 480)**
   - Added `&& $b['status'] !== 'pending_confirmation'` ke kondisi array_filter

### VERIFICATION

Workflow yang benar:

1. Data disubmit via barcode-scan-simple.php
2. submit-borrow.php insert dengan status="pending_confirmation"
3. borrows.php menampilkan di "Form Peminjaman Menunggu Konfirmasi"
4. Admin click "Terima" → approve-borrow.php update status ke "borrowed"
5. Data pindah ke "Daftar Peminjaman Aktif"

### TEST DATA

Setup test data dengan:

```php
php setup-test-data.php
```

Ini akan create:

- ID 21-22: pending_confirmation (Form Peminjaman Menunggu Konfirmasi)
- ID 23-24: borrowed (Daftar Peminjaman Aktif)

### STATUS

✅ FIXED - Workflow sekarang correctly menampilkan data di form yang sesuai dengan statusnya.
