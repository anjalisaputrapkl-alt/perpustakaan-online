# Modul 5: Teknis CRUD Anggota & Akun Otomatis

Berikut adalah logika sinkronisasi antara tabel `members` (Data Perpustakaan) dan tabel `users` (Data Login) pada file `public/members.php`.

## 1. Menambah Anggota & Akun Otomatis (Create)
**Logika:** Ketika Admin menambah anggota baru, sistem melakukan dua kali penyimpanan (Double Insert). Pertama ke tabel `members`, kedua ke tabel `users` agar anggota tersebut langsung bisa login menggunakan NISN-nya.

```php
// 1. Simpan ke data anggota perpustakaan
$stmt = $pdo->prepare('INSERT INTO members (school_id, name, nisn, role) VALUES (...)');
$stmt->execute([...]);

// 2. Buat akun login secara otomatis
$hashed_password = password_hash($_POST['password'], PASSWORD_BCRYPT);
$userStmt = $pdo->prepare('INSERT INTO users (school_id, name, password, role, nisn) VALUES (...)');
$userStmt->execute([
    'password' => $hashed_password,
    'nisn' => $_POST['nisn'],
    // ... data lainnya
]);
```

## 2. Mengedit Anggota (Update)
**Logika:** Jika Admin mengubah NISN atau Nama anggota, sistem harus mengupdate tabel `members` dan tabel `users` secara bersamaan agar kredensial login tetap sinkron.

```php
// Update Tabel Member
$stmt = $pdo->prepare('UPDATE members SET name=:name, nisn=:nisn WHERE id=:id');
$stmt->execute([...]);

// Cari user yang terkait dengan NISN lama, lalu update ke data baru
$updateUserStmt = $pdo->prepare('UPDATE users SET name=:name, nisn=:new_nisn WHERE id=:id');
$updateUserStmt->execute([...]);
```

## 3. Menghapus Anggota (Delete)
**Logika:** Menghapus anggota akan memicu penghapusan akun loginnya juga untuk keamanan.

```php
$getMemberStmt = $pdo->prepare('SELECT nisn FROM members WHERE id=:id');
$getMemberStmt->execute(['id' => $id]);
$member = $getMemberStmt->fetch();

if ($member) {
    // Hapus akun login di tabel users
    $deleteUserStmt = $pdo->prepare('DELETE FROM users WHERE nisn=:nisn AND role="student"');
    $deleteUserStmt->execute(['nisn' => $member['nisn']]);

    // Hapus data fisik anggota di tabel members
    $stmt = $pdo->prepare('DELETE FROM members WHERE id=:id');
    $stmt->execute(['id' => $id]);
}
```

## 4. Pelaporan Data (Reporting)
**Logika Agregasi SQL:** Menggunakan fungsi `COUNT`, `SUM`, dan `GROUP BY` untuk menarik data statistik secara cepat.
```php
// Menghitung total pinjaman aktif per sekolah
$stmt = $pdo->prepare("
    SELECT COUNT(id) as total 
    FROM borrows 
    WHERE school_id = :sid AND returned_at IS NULL
");
```

---
*Kesimpulan Teknis: Sistem ini menggunakan Relasi Data antara NISN di tabel members dan NISN di tabel users. Hal ini memastikan integritas data (data yang sama di dua tempat berbeda) tetap terjaga dengan baik saat terjadi proses CRUD.*
