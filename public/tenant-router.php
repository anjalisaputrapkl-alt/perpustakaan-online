<?php
/**
 * tenant-router.php - Tenant Detection dan Routing
 * 
 * File ini harus di-include di awal setiap entry point (index.php, login.php, register.php, dll)
 * 
 * Fungsi:
 * 1. Inisialisasi session
 * 2. Load database connection
 * 3. Deteksi tenant dari subdomain
 * 4. Simpan tenant ke session
 * 5. Tentukan apakah main domain atau school domain
 */

// Start session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load database connection
$pdo = require __DIR__ . '/../src/db.php';

// Load Tenant class
require __DIR__ . '/../src/Tenant.php';

// Inisialisasi tenant
$tenant = new Tenant($pdo);

// Simpan tenant ke session untuk digunakan di seluruh aplikasi
$tenant->setToSession();

/**
 * Constants yang bisa digunakan di seluruh aplikasi
 */
define('IS_MAIN_DOMAIN', $tenant->isMainDomain());
define('IS_VALID_TENANT', $tenant->isValidTenant());
define('SCHOOL_ID', $tenant->getSchoolId());
define('SCHOOL_NAME', $tenant->getSchoolName());
define('SUBDOMAIN', $tenant->getSubdomain());
define('CURRENT_HOST', $tenant->getHost());

/**
 * Helper function: Get current school_id (dari session atau tenant)
 * Digunakan untuk query yang membutuhkan school_id
 */
function getCurrentSchoolId()
{
    return SCHOOL_ID;
}

/**
 * Helper function: Enforce valid tenant
 * Gunakan ini di halaman yang hanya bisa diakses via subdomain sekolah
 */
function requireValidTenant($redirect_to = '/')
{
    if (!IS_VALID_TENANT) {
        header("Location: $redirect_to");
        exit;
    }
}
