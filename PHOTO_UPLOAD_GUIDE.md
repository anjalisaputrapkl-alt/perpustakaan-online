# 📸 Fitur Upload Foto Profil

## ✨ Fitur Baru

### Sebelumnya:

- Avatar di header hanya menampilkan inisial nama (huruf pertama)
- Tidak bisa upload foto profil

### Sekarang:

- ✅ Bisa upload foto profil dari halaman profil
- ✅ Foto langsung ditampilkan di header top-bar
- ✅ Foto tersimpan di database siswa table
- ✅ Validasi format & ukuran file

---

## 🎯 Cara Kerja

### Upload Foto:

```
1. Login sebagai student
2. Buka: /public/profil.php
3. Lihat section "Ubah Foto Profil"
4. Klik tombol "Pilih File" atau drag-drop foto
5. Klik "📤 Upload"
6. ✅ Foto berhasil diupload
7. Foto otomatis muncul di header (top-bar avatar)
```

### Alur Data:

```
User pilih file foto
        ↓
Validasi (format, size)
        ↓
Simpan ke: /public/uploads/siswa/
        ↓
Update siswa table → foto column
        ↓
Header otomatis load foto dari database
        ↓
Foto muncul di avatar (top-right)
```

---

## 📋 Detail Teknis

### Format File yang Diterima:

- ✅ JPG/JPEG
- ✅ PNG
- ✅ WEBP
- ❌ GIF, BMP, dll (tidak support)

### Ukuran File:

- Maximum: **5 MB**
- Jika > 5MB → error "Ukuran file terlalu besar"

### Lokasi Penyimpanan:

```
/public/uploads/siswa/
├── siswa_2_1705856400_abc123.jpg
├── siswa_4_1705856500_def456.png
└── siswa_7_1705856600_ghi789.webp
```

Format filename: `siswa_[user_id]_[timestamp]_[uniqid].[ext]`

### Database:

```sql
UPDATE siswa
SET foto = 'uploads/siswa/siswa_2_1705856400_abc123.jpg'
WHERE id_siswa = 2
```

---

## 🔄 Photo Handling

### Ketika di profil.php:

```php
// Photo display
$photoUrl = $siswa['foto'] ? '/perpustakaan-online/public/' . $siswa['foto']
                             : '/perpustakaan-online/assets/img/default-avatar.png';

// Jika ada foto → tampil foto
// Jika null → tampil default placeholder
```

### Ketika di header:

```php
// student-header.php
if ($studentPhoto && file_exists(...)) {
    // Tampil foto dari database
    <img src="/perpustakaan-online/public/{$studentPhoto}">
} else {
    // Tampil inisial nama (fallback)
    echo strtoupper(substr($user['name'], 0, 1));
}
```

---

## ✅ Upload Form

```html
<form method="POST" enctype="multipart/form-data">
  <input
    type="file"
    name="foto"
    accept="image/jpeg,image/png,image/webp"
    required
  />
  <button type="submit" name="upload_photo" value="1">📤 Upload</button>
</form>
```

### Features:

- ✅ Menerima multiple format
- ✅ File browser atau drag-drop
- ✅ Validasi MIME type dengan finfo_file()
- ✅ Error handling yang user-friendly
- ✅ Success/error messages

---

## 🧪 Testing Checklist

### Test 1: Upload Foto Baru

```
✅ Buka /public/profil.php
✅ Upload foto baru (JPG/PNG/WEBP)
✅ Muncul pesan sukses
✅ Foto tampil di profile header
✅ Foto tampil di header top-bar
✅ Cek phpmyadmin → siswa.foto field terupdate
```

### Test 2: Upload File Terlalu Besar

```
✅ Upload file > 5MB
✅ Muncul error "Ukuran file terlalu besar"
✅ Foto tidak berubah
```

### Test 3: Upload Format Salah

```
✅ Upload file .gif / .bmp / .txt
✅ Muncul error "Format file harus JPG, PNG, atau WEBP"
✅ Foto tidak berubah
```

### Test 4: Header Display

```
✅ Upload foto
✅ Reload page
✅ Header avatar menampilkan foto (bukan inisial)
✅ Ke page lain (books, borrows, dll)
✅ Avatar tetap menampilkan foto di semua halaman
```

### Test 5: Multiple Users

```
✅ User 1 upload foto
✅ User 2 login → avatar tetap default/photo sendiri
✅ User 1 login kembali → foto User 1 tampil
✅ Foto tidak tertukar antar user
```

---

## 🔐 Security Features

- ✅ **MIME Type Validation**: Gunakan finfo_file() (tidak hanya extension)
- ✅ **File Size Check**: Max 5MB
- ✅ **Unique Filename**: Included timestamp + uniqid (tidak bisa overwrite)
- ✅ **Directory Isolation**: Foto siswa di folder terpisah (`/uploads/siswa/`)
- ✅ **Path Sanitization**: htmlspecialchars() saat display
- ✅ **Input Validation**: $\_FILES validation

---

## 📊 Avatar Behavior

### Avatar Display:

```
State 1: Foto Ada
┌─────────┐
│ [Foto]  │ ← Display actual photo
└─────────┘

State 2: Foto Tidak Ada
┌─────────┐
│    B    │ ← Display initial (Budi)
└─────────┘

State 3: File Tidak Ditemukan (missing)
┌─────────┐
│    B    │ ← Fallback ke initial
└─────────┘
```

---

## 📝 Code Changes Summary

### profil.php:

```
+ Photo upload form handling (POST)
+ File validation (size, MIME type)
+ File save to /uploads/siswa/
+ Database update (siswa.foto)
+ Photo URL logic (DB photo or default)
```

### student-header.php:

```
+ Query siswa table untuk ambil foto
+ Display foto jika ada, otherwise initial
+ File existence check
```

---

## 🚀 Performance

- ✅ Query database hanya 1x per page load
- ✅ Foto disimpan local (tidak ke cloud)
- ✅ No resize/compression (browser handle)
- ✅ Fallback ke text jika foto tidak ada

---

**Status**: ✅ READY TO USE  
**Date**: January 21, 2026  
**Testing**: ✅ Complete
