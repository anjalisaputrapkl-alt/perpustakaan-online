# 📋 Quick Reference: Data Sync Implementation

## 🎯 Objective

Sinkronisasi otomatis data dari table `members` ke table `siswa`, lalu display dari siswa table.

## 📍 File Changes

| File                          | Status      | Changes                                         |
| ----------------------------- | ----------- | ----------------------------------------------- |
| `/public/profil.php`          | ✅ Modified | Added sync logic (lines 12-90) + display fields |
| `/public/sync-siswa-test.php` | ✅ New      | Manual sync tester tool                         |
| `/verify-setup.php`           | ✅ New      | System verification tool                        |
| `/SYNC_DOCUMENTATION.md`      | ✅ New      | Full technical documentation                    |
| `/IMPLEMENTATION_SUMMARY.md`  | ✅ New      | This implementation summary                     |

## 🔄 Sync Workflow

```
1. User opens /profil.php
   ↓
2. Fetch data from members table
   ↓
3. Check: Does siswa record exist?
   ├─ YES → UPDATE siswa with latest data
   └─ NO → INSERT new siswa record
   ↓
4. Display siswa table data in UI
```

## 📊 Fields Displayed in Profile

| Field         | Source              | Editable            |
| ------------- | ------------------- | ------------------- |
| Nama Lengkap  | members.name        | ❌ No (synced)      |
| NIS           | members.member_no   | ❌ No (synced)      |
| NISN          | members.nisn        | ❌ No (synced)      |
| Email         | members.email       | ❌ No (synced)      |
| Kelas         | siswa.kelas         | ✅ Yes (user input) |
| Jurusan       | siswa.jurusan       | ✅ Yes (user input) |
| Jenis Kelamin | siswa.jenis_kelamin | ✅ Yes (user input) |
| Tanggal Lahir | siswa.tanggal_lahir | ✅ Yes (user input) |
| Alamat        | siswa.alamat        | ✅ Yes (user input) |
| Nomor HP      | siswa.no_hp         | ✅ Yes (user input) |
| Foto          | siswa.foto          | ✅ Yes (upload)     |

## 🧪 Testing URLs

| Purpose              | URL                                                               | Description              |
| -------------------- | ----------------------------------------------------------------- | ------------------------ |
| **Main Profile**     | `http://localhost/perpustakaan-online/public/profil.php`          | Auto sync happens here   |
| **Manual Sync Test** | `http://localhost/perpustakaan-online/public/sync-siswa-test.php` | Manual sync + comparison |
| **System Verify**    | `http://localhost/perpustakaan-online/verify-setup.php`           | Check system setup       |

## 🔗 Database Structure

### Table `members` (Auth Source)

```
id (PK)
name
nisn
member_no
email
school_id (FK)
status
created_at
```

### Table `siswa` (Profile Display)

```
id_siswa (PK) ← linked to members.id
nama_lengkap ← from members.name
nis ← from members.member_no
nisn ← from members.nisn
email ← from members.email
kelas
jurusan
tanggal_lahir
jenis_kelamin
alamat
no_hp
foto
created_at
updated_at (auto-updated on sync)
```

## 📝 Code Snippet: Sync Logic

```php
// Get from members
$stmt = $pdo->prepare("
    SELECT id, name, nisn, member_no, email, status
    FROM members WHERE id = ? AND school_id = ?"
);
$stmt->execute([$userId, $schoolId]);
$member = $stmt->fetch();

// Sync to siswa
if ($member) {
    $check = $pdo->prepare("SELECT id_siswa FROM siswa WHERE id_siswa = ?");
    $check->execute([$userId]);

    if ($check->fetch()) {
        // UPDATE
        $pdo->prepare("
            UPDATE siswa
            SET nama_lengkap=?, nisn=?, nis=?, email=?, updated_at=NOW()
            WHERE id_siswa=?
        ")->execute([$member['name'], $member['nisn'],
                     $member['member_no'], $member['email'], $userId]);
    } else {
        // INSERT
        $pdo->prepare("
            INSERT INTO siswa (id_siswa, nama_lengkap, nisn, nis, email)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$userId, $member['name'], $member['nisn'],
                     $member['member_no'], $member['email']]);
    }
}

// Display from siswa
$stmt = $pdo->prepare("SELECT * FROM siswa WHERE id_siswa = ?");
$stmt->execute([$userId]);
$siswa = $stmt->fetch();
```

## ✅ Verification Checklist

- [ ] Run `/verify-setup.php` - all checks GREEN
- [ ] Login as student
- [ ] Open `/public/profil.php` - no errors, data displays
- [ ] Check browser console (F12) - no errors
- [ ] Database check: `SELECT * FROM siswa WHERE id_siswa = [user_id]`
- [ ] Verify fields match members data (nama_lengkap, nis, nisn, email)
- [ ] Test manual sync: `/public/sync-siswa-test.php`
- [ ] Verify changed fields highlighted in yellow
- [ ] Check updated_at timestamp in database

## 🚨 Troubleshooting

| Problem                     | Solution                                             |
| --------------------------- | ---------------------------------------------------- |
| Profile page shows error    | Check `/verify-setup.php` for issues                 |
| Data not syncing            | Run `/sync-siswa-test.php` for manual sync           |
| Data showing old values     | Clear cache (Ctrl+F5) or refresh page                |
| Database query returns NULL | Check if user exists in members table                |
| Error log has "Sync error"  | Check PHP error log at `C:\xampp\logs\php_error.log` |

## 📞 Key File Locations

```
Project Root: C:\xampp\htdocs\perpustakaan-online\

Core Files:
├── /public/profil.php ......................... Student Profile (WITH SYNC)
├── /public/sync-siswa-test.php ............... Sync Test Tool
├── /verify-setup.php .......................... System Verification
│
Documentation:
├── /SYNC_DOCUMENTATION.md .................... Full Technical Docs
├── /IMPLEMENTATION_SUMMARY.md ................ Implementation Summary
├── /IMPLEMENTATION_GUIDE.md .................. Original Guide (still valid)
│
Database:
├── /sql/migrations/perpustakaan_online (4).sql  Schema with siswa table

Models:
├── /src/db.php ............................... Database Connection
├── /src/auth.php ............................. Authentication
└── /src/config.php ........................... Configuration
```

## 🎯 Success Criteria

✅ **All items should be DONE:**

1. Sync logic added to `/public/profil.php`
2. Data flows: members → siswa (automatic)
3. Display shows all siswa fields
4. No PHP errors in page
5. Database record created/updated on first profile view
6. Manual test tool available for verification
7. System verification tool shows all checks pass
8. Documentation complete and accessible

---

**Created:** January 20, 2026  
**Version:** 1.0  
**Status:** ✅ Ready for Production
