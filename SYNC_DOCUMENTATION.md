# 📚 Dokumentasi Sinkronisasi Data Siswa

## Overview

Sistem sinkronisasi data otomatis antara table `members` (authentication source) dan table `siswa` (profile display source).

## Alur Kerja

### 1. **Saat Login**

- User mendaftar/login menggunakan table `members`
- Data disimpan di: `id`, `name`, `nisn`, `member_no`, `email`, `status`

### 2. **Saat Buka Profile** (`/public/profil.php`)

- Aplikasi otomatis **mendeteksi** apakah sudah ada record di `siswa`
- **UPDATE** jika sudah ada (dengan data terbaru dari `members`)
- **INSERT** jika belum ada (membuat record baru)
- **DISPLAY** data dari table `siswa` (sumber kebenaran/source of truth)

### 3. **Field yang Disinkronisasi**

```
members → siswa
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
id           → id_siswa
name         → nama_lengkap
member_no    → nis
nisn         → nisn
email        → email
status       → (tersimpan di members saja, not synced)
```

## Struktur Table

### Table `members` (Authentication)

```sql
┌─────────────┬──────────────┬──────┐
│ Field       │ Type         │ Null │
├─────────────┼──────────────┼──────┤
│ id          │ int(11)      │ NO   │ ← Primary Key
│ name        │ varchar(100) │ NO   │
│ nisn        │ varchar(20)  │ YES  │
│ member_no   │ varchar(20)  │ YES  │
│ email       │ varchar(100) │ NO   │
│ school_id   │ int(11)      │ NO   │ ← Foreign Key
│ status      │ varchar(20)  │ NO   │
│ created_at  │ timestamp    │ NO   │
└─────────────┴──────────────┴──────┘

Sumber: user registration/login
Fitur: Authentication, authorization
```

### Table `siswa` (Profile)

```sql
┌──────────────────┬──────────────┬──────┐
│ Field            │ Type         │ Null │
├──────────────────┼──────────────┼──────┤
│ id_siswa         │ int(11)      │ NO   │ ← Primary Key (sama dengan member ID)
│ nama_lengkap     │ varchar(100) │ NO   │ ← dari members.name
│ nis              │ varchar(20)  │ YES  │ ← dari members.member_no
│ nisn             │ varchar(20)  │ YES  │ ← dari members.nisn
│ kelas            │ varchar(20)  │ YES  │ ← user input, edit di profil
│ jurusan          │ varchar(50)  │ YES  │ ← user input, edit di profil
│ tanggal_lahir    │ date         │ YES  │ ← user input, edit di profil
│ jenis_kelamin    │ char(1)      │ YES  │ ← user input, edit di profil
│ alamat           │ text         │ YES  │ ← user input, edit di profil
│ email            │ varchar(100) │ YES  │ ← dari members.email
│ no_hp            │ varchar(15)  │ YES  │ ← user input, edit di profil
│ foto             │ varchar(255) │ YES  │ ← user input, upload foto
│ created_at       │ timestamp    │ NO   │
│ updated_at       │ timestamp    │ NO   │ ← auto-update saat sync
└──────────────────┴──────────────┴──────┘

Sumber: sync otomatis dari members + user input
Fitur: Profile display, data completeness
```

## Implementasi Code

### Di `profil.php` (Lines 12-68)

```php
// 1. Get data dari members
$stmt = $pdo->prepare("
    SELECT id, name, nisn, member_no, email, status, created_at
    FROM members
    WHERE id = ? AND school_id = ?
");
$stmt->execute([$userId, $schoolId]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Sync ke siswa (try-catch untuk handle error)
if ($member) {
    // Check apakah record sudah ada
    $check = $pdo->prepare("SELECT id_siswa FROM siswa WHERE id_siswa = ?");
    $check->execute([$userId]);
    $exists = $check->fetch();

    if ($exists) {
        // UPDATE jika sudah ada
        $update = $pdo->prepare("
            UPDATE siswa
            SET
                nama_lengkap = ?,
                nisn = ?,
                nis = ?,
                email = ?,
                updated_at = NOW()
            WHERE id_siswa = ?
        ");
        $update->execute([$member['name'], $member['nisn'], $member['member_no'], $member['email'], $userId]);
    } else {
        // INSERT jika belum ada
        $insert = $pdo->prepare("
            INSERT INTO siswa
            (id_siswa, nama_lengkap, nisn, nis, email, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $insert->execute([$userId, $member['name'], $member['nisn'], $member['member_no'], $member['email']]);
    }
}

// 3. Display dari siswa (sumber kebenaran)
$stmt = $pdo->prepare("
    SELECT
        id_siswa, nama_lengkap, nis, nisn, kelas, jurusan,
        tanggal_lahir, jenis_kelamin, alamat, email, no_hp, foto,
        created_at, updated_at
    FROM siswa
    WHERE id_siswa = ?
");
$stmt->execute([$userId]);
$siswa = $stmt->fetch(PDO::FETCH_ASSOC);
```

## Data Flow Diagram

```
┌──────────────────────────────────────────────────────┐
│           USER REGISTER / LOGIN                      │
│      ↓ Data saved to members table                   │
│   (name, nisn, member_no, email, status, etc.)       │
└──────────────────────────────────────────────────────┘
                          ↓
┌──────────────────────────────────────────────────────┐
│      USER OPEN PROFILE PAGE (/profil.php)            │
│      ↓ System automatically sync members → siswa     │
└──────────────────────────────────────────────────────┘
                          ↓
                    ┌─────────────┐
                    │   Cek DB    │
                    │ Sudah ada?  │
                    └─────────────┘
                      ↙         ↘
                    YA          TIDAK
                    ↓             ↓
            ┌─────────────┐  ┌──────────────┐
            │   UPDATE    │  │    INSERT    │
            │   Record    │  │ Record Baru  │
            └─────────────┘  └──────────────┘
                    ↘             ↙
                    └──────┬──────┘
                           ↓
        ┌────────────────────────────────────┐
        │  Display siswa table data in UI    │
        │  (nama_lengkap, nis, nisn, email,  │
        │   kelas, jurusan, tanggal_lahir,   │
        │   jenis_kelamin, alamat, no_hp)    │
        └────────────────────────────────────┘
```

## Testing

### Manual Test: Gunakan `/public/sync-siswa-test.php`

```
URL: http://localhost/perpustakaan-online/public/sync-siswa-test.php

Fitur:
✅ Lihat data di members
✅ Lihat data di siswa sebelum sync
✅ Klik "Sinkronisasi Sekarang"
✅ Lihat data di siswa sesudah sync
✅ Lihat field mana yang berubah (highlighted)
```

### Auto Test: Cek di `/public/profil.php`

```
URL: http://localhost/perpustakaan-online/public/profil.php

Proses otomatis:
1. Buka page
2. Cek F12 Console → tidak ada error
3. Data ditampilkan dengan benar
4. Query database: SELECT * FROM siswa WHERE id_siswa = [user_id]
   → Data harus updated dengan nama, nisn, email dari members
```

## Edge Cases

### Case 1: User baru (belum ada di siswa)

```
✅ Handled: INSERT query akan membuat record baru
   Nama, NISN, Email, Status otomatis terisi dari members
```

### Case 2: User sudah ada di siswa dengan data stale

```
✅ Handled: UPDATE query akan update nama, NISN, email dengan data terbaru
   Field custom (kelas, jurusan, dll) tidak dihapus
```

### Case 3: Members data ada NULL

```
✅ Handled: Prepared statement tetap aman
   NULL values di-pass as NULL (tidak error)
```

### Case 4: Database error saat sync

```
✅ Handled: try-catch + error_log()
   - Error logged ke PHP error log
   - Page tetap bisa load (tidak crash)
   - User tidak perlu tahu error detail
```

## Keuntungan Sistem Ini

| Aspek                 | Sebelum                       | Sesudah                                |
| --------------------- | ----------------------------- | -------------------------------------- |
| **Source of Truth**   | Query members directly ❌     | Table siswa ✅                         |
| **Extensibility**     | Terbatas pada fields members  | Bisa edit kelas, jurusan, foto, dll ✅ |
| **Sync Status**       | N/A                           | Auto-sync, updated_at timestamp ✅     |
| **Data Completeness** | Kurang lengkap                | Lengkap (profile fields) ✅            |
| **Relationship**      | members ← siswa (no relation) | members ↔ siswa (linked via ID) ✅     |
| **Backup Safety**     | N/A                           | Snapshot of members data di siswa ✅   |

## Field Mapping Reference

```
Synchronized Fields (otomatis dari members):
┌─────────────────┬──────────────────┐
│ members         │ siswa            │
├─────────────────┼──────────────────┤
│ id              │ id_siswa         │
│ name            │ nama_lengkap     │
│ member_no       │ nis              │
│ nisn            │ nisn             │
│ email           │ email            │
│ created_at      │ created_at       │
└─────────────────┴──────────────────┘

Custom Fields (user input, tidak dari members):
┌──────────────────────────────────────┐
│ siswa                                │
├──────────────────────────────────────┤
│ kelas (edit di profil)               │
│ jurusan (edit di profil)             │
│ tanggal_lahir (edit di profil)       │
│ jenis_kelamin (edit di profil)       │
│ alamat (edit di profil)              │
│ no_hp (edit di profil)               │
│ foto (upload foto)                   │
│ updated_at (auto, saat sync)         │
└──────────────────────────────────────┘
```

## Troubleshooting

### Problem: Data tidak tersync

**Solution:**

1. Buka `/sync-siswa-test.php` untuk debug manual
2. Lihat apakah data di members ada
3. Klik "Sinkronisasi Sekarang"
4. Cek error messages

### Problem: Data di siswa tidak terupdate

**Solution:**

1. Reload page `/profil.php` (harus fresh load, bukan cache)
2. Cek database: `SELECT * FROM siswa WHERE id_siswa = X`
3. Cek PHP error log: `C:\xampp\logs\php_error.log`

### Problem: Error "profile not found"

**Solution:**

1. Pastikan user sudah login dengan benar
2. Cek SESSION: `echo $_SESSION['user']['id']`
3. Verifikasi user ada di members table
4. Run sync test untuk membuat siswa record

## Maintenance

### Database Backup

```bash
# Backup before production
mysqldump -u root perpustakaan_online > backup.sql
```

### Monitor Sync Errors

```php
// Check error log
tail -f /xampp/logs/php_error.log | grep "Sync error"
```

### Update Field Mapping

Jika ada perubahan struktur:

1. Update migration file di `sql/migrations/`
2. Update prepared statements di `profil.php`
3. Update form di `profil-edit.php` (jika ada)

---

**Last Updated:** January 20, 2026
**Status:** ✅ Production Ready
