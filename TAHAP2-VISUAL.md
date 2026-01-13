# 📊 TAHAP 2: VISUAL ARCHITECTURE GUIDE

## 🏗️ Folder Structure Setelah Tahap 2

```
perpustakaan-online/
│
├── 📄 index.php                    ← Landing Page (Main Domain)
│   └── Detect: Jika subdomain → redirect ke /public/index.php
│       Jika main domain → tampil landing page
│
├── 📁 public/
│   ├── 📄 index.php                ← Dashboard Sekolah (Protected)
│   │   └── Require: Valid tenant + Authenticated
│   │
│   ├── 📄 login-modal.php          ← School-Specific Login (NEW)
│   │   └── Deteksi sekolah dari subdomain
│   │
│   ├── 📄 tenant-router.php        ← Tenant Router (NEW)
│   │   └── Set IS_MAIN_DOMAIN, SCHOOL_ID, constants
│   │
│   ├── 📄 login.php                ← Main Domain Login
│   ├── 📄 register.php             ← Main Domain Register
│   └── ... (file lainnya)
│
├── 📁 src/
│   ├── 📄 Tenant.php              ← Tenant Detection Class (NEW)
│   │   └── Parse subdomain → Query schools → Set school_id
│   │
│   ├── 📄 auth.php                ← Authentication Helper (UPDATED)
│   │   └── Multi-tenant aware redirects
│   │
│   ├── 📄 db.php                  ← Database Connection
│   └── 📄 config.php              ← Config
│
└── 📄 TAHAP2-*.md                 ← Documentation files
```

---

## 🔄 Request Flow Diagram

### Flow 1: Akses Main Domain (perpus.test)

```
User → Browser
  ↓ (GET perpus.test/)
  ↓
index.php (root)
  │
  ├─→ require '/public/tenant-router.php'
  │     ├─→ require '/src/db.php'
  │     ├─→ new Tenant($pdo)
  │     │   ├─→ Parse HTTP_HOST: 'perpus.test'
  │     │   ├─→ Detect: main domain, no subdomain
  │     │   └─→ Set: IS_MAIN_DOMAIN = true, SCHOOL_ID = null
  │     └─→ Define constants
  │
  ├─→ Check: !IS_MAIN_DOMAIN && IS_VALID_TENANT?
  │   └─→ NO (IS_MAIN_DOMAIN = true) → Skip redirect
  │
  ├─→ Landing Page HTML Rendered
  │   ├─→ "Masuk Perpustakaan" button
  │   ├─→ "Daftarkan Sekolah" button
  │   └─→ Multiple sections content
  │
  └─→ Response 200 OK ✓
```

---

### Flow 2: Akses Subdomain Sekolah - Belum Login (sma1.perpus.test)

```
User → Browser
  ↓ (GET sma1.perpus.test/)
  ↓
index.php (root)
  │
  ├─→ require '/public/tenant-router.php'
  │     ├─→ new Tenant($pdo)
  │     │   ├─→ Parse HTTP_HOST: 'sma1.perpus.test'
  │     │   ├─→ Extract: subdomain = 'sma1'
  │     │   ├─→ Query: SELECT * FROM schools WHERE slug = 'sma1'
  │     │   ├─→ Found: id=1, name='SMA 1 Jakarta'
  │     │   └─→ Set: IS_MAIN_DOMAIN=false, IS_VALID_TENANT=true, SCHOOL_ID=1
  │     └─→ $_SESSION['tenant'] = [...school data...]
  │
  ├─→ Check: !IS_MAIN_DOMAIN && IS_VALID_TENANT?
  │   └─→ YES → Redirect
  │
  └─→ header('Location: /public/index.php')
        ↓
        public/index.php
          │
          ├─→ require '/public/tenant-router.php'
          │   └─→ Re-detect tenant (SCHOOL_ID = 1 still valid)
          │
          ├─→ requireValidTenant('/')
          │   └─→ IS_VALID_TENANT = true ✓
          │
          ├─→ require '/src/auth.php'
          ├─→ requireAuth()
          │   └─→ !isAuthenticated() = true → Redirect
          │
          └─→ header('Location: /public/login-modal.php')
                ↓
                login-modal.php
                  │
                  ├─→ require '/public/tenant-router.php'
                  │   └─→ SCHOOL_ID = 1 (still valid)
                  │
                  ├─→ Check: !IS_VALID_TENANT?
                  │   └─→ NO → Continue
                  │
                  └─→ Render Login Form
                        ├─→ School Name: "SMA 1 Jakarta"
                        ├─→ Subdomain: "sma1"
                        ├─→ Email field
                        └─→ Password field
                             ↓
                             Ready for user input
```

---

### Flow 3: Login from Subdomain (sma1.perpus.test/public/login-modal.php)

```
User submits form
  ↓ (POST email='admin@sma1.com', password='password')
  ↓
login-modal.php
  │
  ├─→ $_SERVER['REQUEST_METHOD'] === 'POST'?
  │   └─→ YES
  │
  ├─→ require '/src/db.php'
  │
  ├─→ Query:
  │   SELECT * FROM users
  │   WHERE email = 'admin@sma1.com'
  │   AND school_id = 1  ← IMPORTANT: school_id filter
  │
  ├─→ Found user? YES
  │   ├─→ password_verify('password', stored_hash)?
  │   │   └─→ YES ✓
  │   │
  │   ├─→ $_SESSION['user'] = [
  │   │     'id' => 1,
  │   │     'school_id' => 1,
  │   │     'name' => 'Admin SMA 1',
  │   │     'role' => 'admin'
  │   │   ]
  │   │
  │   └─→ header('Location: /public/index.php')
  │         ↓
  │         public/index.php
  │           │
  │           ├─→ requireValidTenant() ✓
  │           ├─→ requireAuth() ✓ (session['user'] exists)
  │           ├─→ Validate: user['school_id'](1) === SCHOOL_ID(1)?
  │           │   └─→ YES ✓
  │           │
  │           └─→ Dashboard rendered ✓
  │
  └─→ Found user? NO
      └─→ $error = 'Email atau password salah...'
          └─→ Re-render login form dengan error message
```

---

### Flow 4: Cross-Tenant Attack Prevention (User SMP 5 access SMA 1)

```
Scenario: User login di smp5.perpus.test, then access sma1.perpus.test

Step 1: User login di SMP 5
  ↓
smp5.perpus.test/public/login-modal.php
  ├─→ Query: SELECT * FROM users
            WHERE email = 'admin@smp5.com'
            AND school_id = 2
  ├─→ Success! Set:
  │   $_SESSION['user']['school_id'] = 2
  │   SCHOOL_ID = 2
  └─→ Dashboard SMP 5 displayed ✓

Step 2: User manually access sma1.perpus.test/public/index.php
  ↓
public/index.php (on sma1.perpus.test domain)
  │
  ├─→ require '/public/tenant-router.php'
  │   └─→ SCHOOL_ID = 1 (from sma1 subdomain)
  │
  ├─→ requireValidTenant() ✓
  ├─→ require '/src/auth.php'
  ├─→ requireAuth() ✓ (session['user'] exists)
  │
  ├─→ $user = $_SESSION['user']
  │   └─→ user['school_id'] = 2
  │
  ├─→ Validation:
  │   if ($user['school_id'] !== SCHOOL_ID)
  │      // 2 !== 1 → TRUE
  │      header('Location: /public/logout.php')
  │
  └─→ Session destroyed, user logged out ✗
      User cannot access SMA 1 ✓ (Security layer working!)
```

---

## 📊 Tenant Detection Logic

```
┌─ Get HTTP_HOST
│
├─ Parse domain (explode by '.')
│
├─ Count parts:
│  ├─ 2 parts (perpus.test)
│  │  └─→ IS_MAIN_DOMAIN = true
│  │      SCHOOL_ID = null
│  │      SUBDOMAIN = null
│  │
│  └─ 3+ parts (sma1.perpus.test)
│     └─→ IS_MAIN_DOMAIN = false
│        SUBDOMAIN = 'sma1' (first part)
│        ├─ Query: schools WHERE slug = 'sma1'
│        ├─ Found?
│        │  ├─ YES → IS_VALID_TENANT = true, SCHOOL_ID = 1
│        │  └─ NO → IS_VALID_TENANT = false, SCHOOL_ID = null
│        └─ Set to $_SESSION['tenant']
```

---

## 🔐 Security Layers

```
┌──────────────────────────────────────┐
│  Request to Protected Resource       │
├──────────────────────────────────────┤
│                                      │
│  Layer 1: Tenant Validation          │
│  ├─ Parse subdomain                  │
│  ├─ Query schools table              │
│  └─ Enforce: School MUST exist       │
│     └─ NO → Error 404                │
│         YES → Continue ↓             │
│                                      │
│  Layer 2: Authentication             │
│  ├─ Check: $_SESSION['user'] exists? │
│  └─ NO → Redirect to login           │
│      YES → Continue ↓                │
│                                      │
│  Layer 3: Authorization              │
│  ├─ Check: user['school_id'] ===     │
│            SCHOOL_ID?                │
│  └─ NO → Logout & reject             │
│      YES → Continue ↓                │
│                                      │
│  Layer 4: Data Isolation             │
│  ├─ All queries: WHERE school_id = ? │
│  └─ NO filter → BUG ALERT ⚠️         │
│      Filtered → Safe ✓               │
│                                      │
│  Access Granted ✓                    │
└──────────────────────────────────────┘
```

---

## 📈 Database Schema (Multi-Tenant Structure)

```sql
schools
┌─────────────┬──────────────────┐
│ id (PK)     │ name             │
│ slug        │ created_at       │
└─────────────┴──────────────────┘
     ↑
     │ (1 school has many users, books, etc.)
     │
users
┌────────────────────────────────────┐
│ id (PK)                            │
│ school_id (FK) ──→ schools.id      │ ← IMPORTANT
│ name, email, password              │
│ role (admin, librarian, etc)       │
└────────────────────────────────────┘

books
┌────────────────────────────────────┐
│ id (PK)                            │
│ school_id (FK) ──→ schools.id      │ ← IMPORTANT
│ title, author, isbn                │
└────────────────────────────────────┘

members
┌────────────────────────────────────┐
│ id (PK)                            │
│ school_id (FK) ──→ schools.id      │ ← IMPORTANT
│ name, email, student_id            │
└────────────────────────────────────┘

borrows
┌────────────────────────────────────┐
│ id (PK)                            │
│ school_id (FK) ──→ schools.id      │ ← IMPORTANT
│ book_id, member_id, borrowed_date  │
│ returned_at, status                │
└────────────────────────────────────┘

KEY RULE: EVERY TABLE MUST HAVE school_id COLUMN
```

---

## 🎯 Constants Available After Including tenant-router.php

```php
<?php
require __DIR__ . '/tenant-router.php';

// Available constants throughout the application:

IS_MAIN_DOMAIN       // bool - true jika perpus.test
IS_VALID_TENANT      // bool - true jika subdomain valid
SCHOOL_ID            // int|null - ID dari sekolah
SCHOOL_NAME          // string|null - Nama dari sekolah
SUBDOMAIN            // string|null - Slug dari sekolah
CURRENT_HOST         // string - Domain yang diakses

// Example usage:
if (IS_MAIN_DOMAIN) {
    // Show main platform
} else if (IS_VALID_TENANT) {
    // Show school dashboard
} else {
    // Invalid subdomain - error
}

// Query with school_id:
$sql = 'SELECT * FROM books WHERE school_id = ?';
$stmt = $pdo->prepare($sql);
$stmt->execute([SCHOOL_ID]);
```

---

## 🔗 Helper Functions Available

```php
<?php
require __DIR__ . '/tenant-router.php';

// Get school_id safely:
$school_id = getCurrentSchoolId();

// Enforce: Must be valid tenant
requireValidTenant('/');  // Redirect to / if invalid

// Get tenant info from session:
$tenant = Tenant::getFromSession();
echo $tenant['school_id'];    // 1
echo $tenant['school_name'];  // 'SMA 1 Jakarta'
```

---

## ✅ Implementation Checklist

```
[✓] Tenant.php created
[✓] tenant-router.php created
[✓] login-modal.php created
[✓] index.php updated (landing page)
[✓] public/index.php updated (dashboard)
[✓] auth.php updated (multi-tenant redirects)
[✓] Database: schools table with slug
[✓] Database: users table with school_id
[✓] Hosts file: subdomains added
[✓] Apache: VirtualHost configured
[✓] Documentation: TAHAP2-*.md files created

→ Ready for Testing! See TAHAP2-TESTING.md
```

---

## 🚀 Architecture Summary

```
┌─────────────────────────────────────────────────────┐
│          MULTI-TENANT PERPUSTAKAAN SYSTEM           │
├─────────────────────────────────────────────────────┤
│                                                     │
│  Main Domain: perpus.test                          │
│  ├─→ Landing Page (index.php)                      │
│  ├─→ Register (public/register.php)                │
│  └─→ Login (public/login.php)                      │
│                                                     │
│  School Subdomain: *.perpus.test                   │
│  ├─→ sma1.perpus.test                             │
│  │   └─→ Login (public/login-modal.php)            │
│  │   └─→ Dashboard (public/index.php)              │
│  │   └─→ Data: Books, Members, Borrows for SMA1   │
│  │                                                  │
│  ├─→ smp5.perpus.test                             │
│  │   └─→ Login (public/login-modal.php)            │
│  │   └─→ Dashboard (public/index.php)              │
│  │   └─→ Data: Books, Members, Borrows for SMP5   │
│  │                                                  │
│  └─→ sma3.perpus.test                             │
│      └─→ Similar structure...                      │
│                                                     │
│  Core Tenant System:                               │
│  ├─→ src/Tenant.php (Detection + Validation)       │
│  ├─→ public/tenant-router.php (Set Constants)      │
│  ├─→ src/auth.php (Multi-tenant auth)              │
│  └─→ Database: schools, users, books, members...   │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

Next: **TAHAP 3: Pemisahan Website Utama dan Website Sekolah**
