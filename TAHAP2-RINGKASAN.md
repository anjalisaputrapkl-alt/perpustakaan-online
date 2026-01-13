# TAHAP 2: PENYESUAIAN SISTEM TENANT - RINGKASAN IMPLEMENTASI

## 📁 File-File yang Dibuat dan Dimodifikasi

### 1. **src/Tenant.php** (NEW)

Kelas utama untuk mendeteksi dan mengelola tenant dari subdomain.

**Fitur Utama:**

- Parse HTTP_HOST untuk ekstrak subdomain
- Query database untuk cocokkan slug dengan schools table
- Set/Get school_id, school_name, dan data lainnya
- Constants untuk mudah diakses di seluruh aplikasi

**Method Penting:**

```php
$tenant = new Tenant($pdo);
$tenant->isMainDomain();      // Cek apakah main domain
$tenant->isValidTenant();     // Cek apakah sekolah ditemukan
$tenant->getSchoolId();       // Get school_id
$tenant->getSchoolName();     // Get nama sekolah
$tenant->getSubdomain();      // Get slug dari subdomain
$tenant->setToSession();      // Simpan ke $_SESSION['tenant']
```

---

### 2. **public/tenant-router.php** (NEW)

Router yang menghubungkan Tenant class dengan aplikasi. Harus di-include di awal setiap halaman.

**Define Constants:**

```php
IS_MAIN_DOMAIN    // true = perpus.test, false = subdomain
IS_VALID_TENANT   // true = sekolah ditemukan di DB
SCHOOL_ID         // int atau null
SCHOOL_NAME       // string atau null
SUBDOMAIN         // string atau null
CURRENT_HOST      // domain yang diakses
```

**Helper Functions:**

```php
getCurrentSchoolId()     // Get school_id saat ini
requireValidTenant()     // Enforce: hanya bisa di subdomain valid
```

---

### 3. **public/login-modal.php** (NEW)

Halaman login khusus untuk subdomain sekolah.

**Fitur:**

- Deteksi sekolah dari subdomain
- Login hanya untuk user di sekolah tersebut (WHERE school_id = SCHOOL_ID)
- Validasi: reject user dari sekolah lain
- Tampilkan nama sekolah dan subdomain di header

**Flow:**

```
sma1.perpus.test (tanpa login)
  ↓
tenant-router deteksi SCHOOL_ID = 1
  ↓
Tampil login-modal.php dengan "SMA 1 Jakarta"
  ↓
User masukkan email + password
  ↓
Query: SELECT * FROM users WHERE email = ? AND school_id = 1
  ↓
✓ Valid → Set session, redirect ke dashboard
✗ Invalid → Tampil error message
```

---

### 4. **index.php** (UPDATED)

Landing page yang sekarang aware tentang tenant.

**Perubahan:**

```php
// Baris pertama: include tenant router
require __DIR__ . '/public/tenant-router.php';

// Jika subdomain sekolah valid → redirect ke dashboard
if (!IS_MAIN_DOMAIN && IS_VALID_TENANT) {
    header('Location: /public/index.php');
    exit;
}

// Jika subdomain tidak valid → error 404
if (!IS_MAIN_DOMAIN && !IS_VALID_TENANT) {
    http_response_code(404);
    die('Sekolah tidak ditemukan...');
}

// Jika main domain → tampil landing page (normal flow)
?>
<!doctype html>
...
```

**Behavior:**
| URL | IS_MAIN_DOMAIN | IS_VALID_TENANT | Action |
|-----|---|---|---|
| perpus.test | TRUE | - | Tampil landing page |
| sma1.perpus.test | FALSE | TRUE | Redirect ke /public/index.php |
| invalid.perpus.test | FALSE | FALSE | Error 404 |

---

### 5. **public/index.php** (UPDATED)

Dashboard sekolah yang sekarang memvalidasi tenant.

**Perubahan:**

```php
// Include tenant router SEBELUM auth.php
require __DIR__ . '/tenant-router.php';

// Enforce: hanya bisa diakses dari subdomain valid
requireValidTenant('/');

// Jika user login di sekolah lain, redirect
if ($user['school_id'] !== SCHOOL_ID) {
    header('Location: /public/logout.php');
    exit;
}
```

**Security Layer:**

```
1. Subdomain check → school_id dari subdomain
2. Auth check → user sudah login?
3. School validation → user['school_id'] === SCHOOL_ID (dari subdomain)
   Jika tidak match → logout user
```

---

### 6. **src/auth.php** (UPDATED)

Updated untuk support multi-tenant redirects.

**Perubahan:**

```php
function requireAuth()
{
    if (!isAuthenticated()) {
        // Jika di subdomain sekolah → login-modal.php
        if (count($parts) >= 3) {
            header('Location: /public/login-modal.php');
            exit;
        }

        // Jika main domain → login.php
        header('Location: /public/login.php');
        exit;
    }
}
```

---

## 🔄 Flow Diagram Penggunaan

### Scenario A: User Akses Main Domain

```
perpus.test/
    ↓
index.php include tenant-router.php
    ↓
Tenant::__construct() parse host
    ↓
is_main_domain = true, school_id = null
    ↓
IS_MAIN_DOMAIN = TRUE
    ↓
Landing page ditampilkan
```

---

### Scenario B: User Akses Subdomain Sekolah (Belum Login)

```
sma1.perpus.test/
    ↓
index.php include tenant-router.php
    ↓
Tenant parse host → subdomain = "sma1"
    ↓
Query DB: SELECT id FROM schools WHERE slug = 'sma1'
    ↓
Found: SMA 1 Jakarta (id=1)
    ↓
IS_MAIN_DOMAIN = FALSE
IS_VALID_TENANT = TRUE
SCHOOL_ID = 1
    ↓
Condition: if (!IS_MAIN_DOMAIN && IS_VALID_TENANT)
    ↓
Redirect ke /public/index.php
    ↓
public/index.php:
  - Load tenant-router.php
  - requireValidTenant() → OK (SCHOOL_ID = 1)
  - requireAuth() → NOT OK (no session)
  - Redirect ke /public/login-modal.php
    ↓
login-modal.php displayed dengan:
  - School name: "SMA 1 Jakarta"
  - Subdomain badge: "sma1.perpus.test"
  - Login form (email + password)
```

---

### Scenario C: User Login dari Subdomain Sekolah

```
sma1.perpus.test/public/login-modal.php
    ↓
Form submitted:
  email: admin@sma1.com
  password: password123
    ↓
Query: SELECT * FROM users
       WHERE email = 'admin@sma1.com'
       AND school_id = 1
    ↓
Found + password_verify() OK
    ↓
Set $_SESSION['user'] = [
  'id' => 1,
  'school_id' => 1,
  'name' => 'Admin SMA 1',
  'role' => 'admin'
]
    ↓
Redirect ke /public/index.php
    ↓
public/index.php:
  - tenant-router.php → SCHOOL_ID = 1
  - requireValidTenant() → OK
  - requireAuth() → OK (session user exists)
  - Validation: user['school_id'] (1) === SCHOOL_ID (1) → OK
    ↓
Dashboard SMA 1 ditampilkan
```

---

### Scenario D: User dari SMP 5 Coba Akses SMA 1

```
1. User login di smp5.perpus.test dengan admin@smp5.com
   → $_SESSION['user']['school_id'] = 2

2. User buka sma1.perpus.test/public/index.php

3. sma1.perpus.test parsing:
   → SCHOOL_ID = 1 (dari subdomain SMA 1)

4. public/index.php validation:
   if ($user['school_id'] !== SCHOOL_ID)  // 2 !== 1
   → header('Location: /public/logout.php')

5. Session destroyed, user logout
```

---

## 🔐 Security Architecture

### Multi-Layer Protection:

**Layer 1: Domain/Subdomain Detection**

```php
$tenant = new Tenant($pdo);
// Ekstrak school_id dari subdomain yang diakses
```

**Layer 2: Tenant Validation**

```php
requireValidTenant('/');
// Jika subdomain tidak ada di database → reject
```

**Layer 3: User Authentication**

```php
requireAuth();
// Jika user tidak login → redirect ke login page
```

**Layer 4: School Ownership Validation**

```php
if ($user['school_id'] !== SCHOOL_ID) {
    header('Location: /public/logout.php');
}
// Jika user login di sekolah berbeda → logout
```

---

## 📊 Query Isolation

Semua query harus include WHERE school_id:

```php
// ✓ CORRECT - Filter by school_id
$stmt = $pdo->prepare(
    'SELECT * FROM books WHERE school_id = ? AND id = ?'
);

// ✗ WRONG - Tanpa school_id filter
$stmt = $pdo->prepare('SELECT * FROM books WHERE id = ?');
```

Helper function untuk mendapat school_id:

```php
$school_id = getCurrentSchoolId(); // Dari SCHOOL_ID constant

// Atau dari session user
$user = getAuthUser();
$school_id = $user['school_id'];
```

---

## ✅ Checklist Implementation

- [x] Buat src/Tenant.php dengan parsing dan detection
- [x] Buat public/tenant-router.php untuk set constants
- [x] Update index.php untuk include tenant-router
- [x] Update public/index.php untuk validate tenant
- [x] Buat public/login-modal.php untuk school-specific login
- [x] Update src/auth.php untuk multi-tenant redirects
- [x] Dokumentasi testing scenarios

---

## 🚀 Next: Tahap 3

Siap untuk **Tahap 3: Pemisahan Website Utama dan Website Sekolah**

Yang akan dilakukan:

1. Reorganisasi folder struktur untuk jelas separation
2. Update semua protected pages (books.php, members.php, dll)
3. Add tenant indicator di navbar
4. Test data isolation dengan multiple sekolah di database
