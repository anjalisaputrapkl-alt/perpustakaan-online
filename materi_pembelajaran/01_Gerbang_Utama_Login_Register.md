# Modul 1: Alur Login & Register Terstruktur

## A. Proses Pendaftaran (Register)
**File: `public/api/register.php`**

**1. Validasi Input Kosong**
Jika field input seperti nama sekolah atau email dibiarkan kosong, maka sistem menggagalkan pendaftaran dan mengembalikan status 400.
```php
if (empty($school_name) || empty($admin_name) || empty($admin_email) || empty($admin_password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi']);
    exit;
}
```

**2. Pengecekan Ketersediaan Email**
Jika email *sudah terdaftar* di tabel `users` database, pendaftaran ditolak. Jika *belum terdaftar* (hasil COUNT = 0), maka pendaftaran berhasil dan dilanjutkan.
```php
$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
$stmt->execute(['email' => $admin_email]);
if ($stmt->fetchColumn() > 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email sudah terdaftar']);
    exit;
}
```

**3. Enkripsi Password dan Insert Database**
Jika email tersedia, URL/slug sekolah dibuat, password dienkripsi, dan data di-insert ke tabel `users`.
```php
$password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
$verification_code = generateVerificationCode();

$stmt = $pdo->prepare(
    'INSERT INTO users (school_id, name, email, password, verification_code, code_expires_at, is_verified, role) 
     VALUES (:school_id, :name, :email, :password, :verification_code, :code_expires_at, 0, "admin")'
);
```

---

## B. Proses Masuk (Login)
**File: `public/api/login.php`**

**1. Pemisahan Tipe Pengecekan**
Jika `user_type` adalah 'student', pencarian di database menggunakan NISN. Jika bukan, pencarian menggunakan Email.
```php
if ($user_type === 'student') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE nisn = :nisn AND role IN ('student', 'teacher', 'employee') LIMIT 1");
    $stmt->execute(['nisn' => $nisn]);
} else {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
}
$user = $stmt->fetch(PDO::FETCH_ASSOC);
```

**2. Verifikasi dan Sesi (Session)**
Jika password plain-text dicocokkan dengan hash di database dan BENAR (match), maka data user disimpan ke dalam `$_SESSION`.
```php
if (!password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'NISN/Email atau password salah']);
    exit;
}

$_SESSION['user'] = [
    'id' => $user['id'],
    'school_id' => $user['school_id'],
    'name' => $user['name'],
    'role' => $user['role']
];
```
