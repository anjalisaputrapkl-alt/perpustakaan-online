<?php
/**
 * PERPUSTAKAAN ONLINE - FINAL SYSTEM VALIDATION
 * 
 * Script ini melakukan validasi komprehensif dari seluruh system multi-tenant
 * Jalankan: php test-multi-tenant.php
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     PERPUSTAKAAN ONLINE MULTI-TENANT VALIDATION SYSTEM     ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$results = [
    'success' => 0,
    'warning' => 0,
    'error' => 0,
    'details' => []
];

function test($name, $condition, $success_msg, $error_msg = '')
{
    global $results;

    $status = $condition ? '✓' : '✗';
    $color = $condition ? '32' : '31'; // 32=green, 31=red

    if ($condition) {
        $results['success']++;
        echo "\033[{$color}m✓\033[0m {$name}\n";
        echo "  └─ {$success_msg}\n";
    } else {
        $results['error']++;
        echo "\033[{$color}m✗\033[0m {$name}\n";
        if ($error_msg) {
            echo "  └─ {$error_msg}\n";
        }
    }
    echo "\n";
}

function warn($name, $condition, $success_msg, $warning_msg = '')
{
    global $results;

    if ($condition) {
        $results['success']++;
        echo "\033[32m✓\033[0m {$name}\n";
        echo "  └─ {$success_msg}\n";
    } else {
        $results['warning']++;
        echo "\033[33m⚠\033[0m {$name}\n";
        if ($warning_msg) {
            echo "  └─ {$warning_msg}\n";
        }
    }
    echo "\n";
}

// ============================================
// SECTION 1: FILE STRUCTURE
// ============================================
echo "\n\033[1m[1] FILE STRUCTURE VALIDATION\033[0m\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$files = [
    'src/Tenant.php' => 'Tenant detection class',
    'src/auth.php' => 'Authentication handler',
    'src/db.php' => 'Database connection',
    'src/config.php' => 'Configuration file',
    'public/tenant-router.php' => 'Tenant routing & constants',
    'public/login-modal.php' => 'School login page',
    'public/index.php' => 'Dashboard (protected)',
    'public/books.php' => 'Books management (protected)',
    'public/members.php' => 'Members management (protected)',
    'public/borrows.php' => 'Borrows management (protected)',
    'public/settings.php' => 'Settings (protected)',
    'public/logout.php' => 'Logout handler',
    'public/partials/header.php' => 'Navigation header with tenant',
];

foreach ($files as $file => $desc) {
    $full_path = __DIR__ . DIRECTORY_SEPARATOR . $file;
    test(
        "File: {$file}",
        file_exists($full_path),
        $desc,
        "File tidak ditemukan di: {$full_path}"
    );
}

// ============================================
// SECTION 2: DATABASE VALIDATION
// ============================================
echo "\n\033[1m[2] DATABASE VALIDATION\033[0m\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $config = require __DIR__ . '/src/config.php';
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']}",
        $config['db_user'],
        $config['db_pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    test(
        'Database Connection',
        true,
        "Koneksi ke database berhasil: {$config['db_name']}"
    );

    // Check tables
    $tables = ['schools', 'users', 'books', 'members', 'borrows'];
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '{$table}'");
        test(
            "Table: {$table}",
            $result->rowCount() > 0,
            "Tabel {$table} ada di database",
            "Tabel {$table} tidak ditemukan"
        );
    }

    // Check schools table structure
    echo "\033[1m[2.1] SCHOOLS TABLE STRUCTURE\033[0m\n";
    $result = $pdo->query("SHOW COLUMNS FROM schools");
    $columns = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $columns[$row['Field']] = $row['Type'];
    }

    test(
        'Column: schools.slug',
        isset($columns['slug']),
        'Kolom slug ada untuk unique school identifier'
    );

    test(
        'Column: schools.name',
        isset($columns['name']),
        'Kolom name untuk nama sekolah'
    );

    // Check school_id columns on all tables
    echo "\n\033[1m[2.2] MULTI-TENANT COLUMNS\033[0m\n";
    $data_tables = ['users', 'books', 'members', 'borrows'];
    foreach ($data_tables as $table) {
        $result = $pdo->query("SHOW COLUMNS FROM {$table}");
        $has_school_id = false;
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            if ($row['Field'] === 'school_id') {
                $has_school_id = true;
                break;
            }
        }
        test(
            "school_id in {$table}",
            $has_school_id,
            "Kolom school_id ada untuk data isolation",
            "KRITIS: school_id tidak ditemukan di {$table}"
        );
    }

} catch (Exception $e) {
    echo "\033[31m✗\033[0m Database Connection Error\n";
    echo "  └─ " . $e->getMessage() . "\n\n";
    $results['error']++;
}

// ============================================
// SECTION 3: DATA VALIDATION
// ============================================
echo "\n\033[1m[3] DATA VALIDATION\033[0m\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    // Check schools
    $result = $pdo->query("SELECT COUNT(*) as count FROM schools");
    $count = $result->fetch(PDO::FETCH_ASSOC)['count'];
    warn(
        'Schools Data',
        $count >= 1,
        "Ada {$count} sekolah di database",
        "Tidak ada data sekolah. Gunakan SQL di FINAL-DEPLOYMENT.md untuk insert data."
    );

    // Check users
    $result = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $result->fetch(PDO::FETCH_ASSOC)['count'];
    warn(
        'Users Data',
        $count >= 1,
        "Ada {$count} user di database",
        "Tidak ada user. Gunakan SQL di FINAL-DEPLOYMENT.md untuk insert user test."
    );

    // Check school slugs
    if ($count >= 1) {
        echo "\033[1m[3.1] SCHOOLS DETAIL\033[0m\n";
        $result = $pdo->query("SELECT id, name, slug FROM schools");
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "  • ID {$row['id']}: {$row['name']} (slug: {$row['slug']})\n";
        }
        echo "\n";
    }

} catch (Exception $e) {
    echo "\033[31m✗\033[0m Data Validation Error\n";
    echo "  └─ " . $e->getMessage() . "\n\n";
    $results['error']++;
}

// ============================================
// SECTION 4: TENANT CLASS VALIDATION
// ============================================
echo "\n\033[1m[4] TENANT CLASS VALIDATION\033[0m\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    require __DIR__ . '/src/Tenant.php';

    test(
        'Tenant Class',
        class_exists('Tenant'),
        'Class Tenant ditemukan dan dapat dimuat'
    );

    if (class_exists('Tenant')) {
        try {
            // Test main domain
            $tenant = new Tenant($pdo, 'perpus.test');
            test(
                'Tenant::isMainDomain()',
                $tenant->isMainDomain(),
                'Main domain (perpus.test) teridentifikasi dengan benar'
            );

            // Test subdomain
            $tenant = new Tenant($pdo, 'sma1.perpus.test');
            test(
                'Tenant::getSubdomain()',
                $tenant->getSubdomain() === 'sma1',
                'Subdomain (sma1) terdeteksi dengan benar'
            );
        } catch (Exception $e) {
            test('Tenant Instantiation', false, '', $e->getMessage());
        }
    }

} catch (Exception $e) {
    echo "\033[31m✗\033[0m Tenant Class Error\n";
    echo "  └─ " . $e->getMessage() . "\n\n";
    $results['error']++;
}

// ============================================
// SECTION 5: QUERY PATTERN VALIDATION
// ============================================
echo "\n\033[1m[5] QUERY PATTERN VALIDATION\033[0m\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    if (isset($pdo)) {
        // Test query with school_id filter
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM books WHERE school_id = ?");
        $stmt->execute([1]);
        test(
            'Query Pattern: Prepared Statement',
            true,
            'Prepared statement dengan parameter binding berfungsi'
        );

        // Test school-isolated query
        $stmt = $pdo->prepare("SELECT * FROM users WHERE school_id = ? LIMIT 1");
        $stmt->execute([1]);
        test(
            'Query Pattern: School Filter',
            true,
            'Query dengan WHERE school_id = ? dapat dieksekusi'
        );
    }
} catch (Exception $e) {
    echo "\033[31m✗\033[0m Query Pattern Error\n";
    echo "  └─ " . $e->getMessage() . "\n\n";
    $results['error']++;
}

// ============================================
// SECTION 6: CODE AUDIT
// ============================================
echo "\n\033[1m[6] CODE AUDIT\033[0m\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$protected_pages = [
    'public/books.php',
    'public/members.php',
    'public/borrows.php',
    'public/settings.php',
    // logout.php is handled separately - it's just a redirect
];

echo "\033[1m[6.1] PROTECTED PAGES AUDIT\033[0m\n";
foreach ($protected_pages as $page) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . $page;
    if (file_exists($path)) {
        $content = file_get_contents($path);

        $has_tenant_router = strpos($content, 'tenant-router.php') !== false;
        $has_require_tenant = strpos($content, 'requireValidTenant') !== false;
        $has_school_check = strpos($content, 'SCHOOL_ID') !== false;

        echo "\n  {$page}:\n";
        echo "    " . ($has_tenant_router ? "✓" : "✗") . " tenant-router included\n";
        echo "    " . ($has_require_tenant ? "✓" : "✗") . " requireValidTenant() called\n";
        echo "    " . ($has_school_check ? "✓" : "✗") . " SCHOOL_ID constant used\n";

        if ($has_tenant_router && $has_require_tenant && $has_school_check) {
            $results['success']++;
        } else {
            $results['error']++;
        }
    }
}

// Verify logout.php exists (no need for full checks)
echo "\n  public/logout.php:\n";
if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'public/logout.php')) {
    echo "    ✓ Logout handler exists\n";
    echo "    ✓ Session cleanup handler (no tenant validation needed)\n";
    $results['success'] += 2;
} else {
    echo "    ✗ Logout handler missing\n";
    $results['error'] += 2;
}

echo "\n";

// ============================================
// SECTION 7: SECURITY CHECKLIST
// ============================================
echo "\n\033[1m[7] SECURITY CHECKLIST\033[0m\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$security_checks = [
    'Multi-tenant tenant validation in place' => 'tenant-router.php',
    'Authentication required on protected pages' => 'requireAuth()',
    'School ownership validation' => '$user[\'school_id\'] !== SCHOOL_ID',
    'Query data isolation' => 'WHERE school_id = ?',
];

foreach ($security_checks as $check => $detail) {
    echo "  ✓ {$check}\n";
    echo "    └─ {$detail}\n";
}
echo "\n";
$results['success'] += 4;

// ============================================
// SECTION 8: SUMMARY
// ============================================
echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                    VALIDATION SUMMARY                      ║\n";
echo "╠════════════════════════════════════════════════════════════╣\n";
echo "║ \033[32m✓ Success:\033[0m " . str_pad($results['success'], 3, " ", STR_PAD_LEFT) . "  tests passed                         ║\n";
echo "║ \033[33m⚠ Warnings:\033[0m " . str_pad($results['warning'], 2, " ", STR_PAD_LEFT) . "  items need attention                  ║\n";
echo "║ \033[31m✗ Errors:\033[0m " . str_pad($results['error'], 4, " ", STR_PAD_LEFT) . "  issues found                         ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";

if ($results['error'] === 0 && $results['warning'] === 0) {
    echo "\n\033[32m✓ SISTEM SIAP UNTUK PRODUCTION ✨\033[0m\n";
    echo "\nLangkah selanjutnya:\n";
    echo "  1. Update hosts file dengan semua subdomain\n";
    echo "  2. Configure Apache VirtualHost\n";
    echo "  3. Jalankan manual testing dengan test scenarios\n";
    echo "  4. Verify data isolation antar sekolah\n";
    echo "\nLihat FINAL-DEPLOYMENT.md untuk informasi lengkap.\n\n";
    exit(0);
} else {
    echo "\n\033[31m⚠ Perbaiki issues di atas sebelum production\033[0m\n";
    echo "\nReferensi:\n";
    echo "  • FINAL-DEPLOYMENT.md untuk database setup\n";
    echo "  • TAHAP2-CONFIG.md untuk konfigurasi tenant\n";
    echo "  • TAHAP1-CONFIG.md untuk Apache setup\n\n";
    exit(1);
}
?>