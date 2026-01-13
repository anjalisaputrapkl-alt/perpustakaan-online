#!/usr/bin/env php
<?php
/**
 * test-multi-tenant.php - Multi-Tenant System Validation Script
 * 
 * Jalankan: php test-multi-tenant.php
 * 
 * Script ini memvalidasi bahwa sistem multi-tenant sudah ter-setup dengan benar
 */

// Color codes untuk terminal output
define('GREEN', "\033[32m");
define('RED', "\033[31m");
define('YELLOW', "\033[33m");
define('BLUE', "\033[34m");
define('RESET', "\033[0m");

class MultiTenantValidator
{
    private $pdo;
    private $errors = [];
    private $warnings = [];
    private $successes = [];

    public function __construct()
    {
        try {
            $this->pdo = require __DIR__ . '/src/db.php';
            $this->success("Database connection established");
        } catch (Exception $e) {
            $this->error("Database connection failed: " . $e->getMessage());
            exit(1);
        }
    }

    // ==================== VALIDATORS ====================

    public function validateFiles()
    {
        echo "\n" . BLUE . "=== FILE VALIDATION ===" . RESET . "\n";

        $files = [
            'src/Tenant.php' => 'Tenant detection class',
            'public/tenant-router.php' => 'Tenant router & constants',
            'public/login-modal.php' => 'School-specific login',
            'public/books.php' => 'Books management (protected)',
            'public/members.php' => 'Members management (protected)',
            'public/borrows.php' => 'Borrows management (protected)',
            'public/settings.php' => 'Settings page (protected)',
            'public/logout.php' => 'Logout handler',
        ];

        foreach ($files as $file => $desc) {
            $path = __DIR__ . '/' . $file;
            if (file_exists($path)) {
                $this->success("✓ $file - $desc");
            } else {
                $this->error("✗ $file - NOT FOUND");
            }
        }
    }

    public function validateDatabase()
    {
        echo "\n" . BLUE . "=== DATABASE VALIDATION ===" . RESET . "\n";

        // Check tables
        $tables = ['schools', 'users', 'books', 'members', 'borrows'];
        foreach ($tables as $table) {
            try {
                $stmt = $this->pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    $this->success("✓ Table '$table' exists");
                } else {
                    $this->error("✗ Table '$table' not found");
                }
            } catch (Exception $e) {
                $this->error("✗ Error checking table '$table': " . $e->getMessage());
            }
        }

        // Check school_id columns
        $tables_with_school_id = ['users', 'books', 'members', 'borrows'];
        foreach ($tables_with_school_id as $table) {
            try {
                $stmt = $this->pdo->query("DESCRIBE $table");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
                if (in_array('school_id', $columns)) {
                    $this->success("✓ Table '$table' has school_id column");
                } else {
                    $this->error("✗ Table '$table' missing school_id column");
                }
            } catch (Exception $e) {
                $this->warning("⚠ Error checking columns in '$table'");
            }
        }

        // Check schools slug column
        try {
            $stmt = $this->pdo->query("DESCRIBE schools");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            if (in_array('slug', $columns)) {
                $this->success("✓ Table 'schools' has slug column");
            } else {
                $this->error("✗ Table 'schools' missing slug column");
            }
        } catch (Exception $e) {
            $this->warning("⚠ Error checking schools table");
        }
    }

    public function validateSchools()
    {
        echo "\n" . BLUE . "=== SCHOOLS DATA VALIDATION ===" . RESET . "\n";

        try {
            $stmt = $this->pdo->query("SELECT id, name, slug FROM schools");
            $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($schools) === 0) {
                $this->warning("⚠ No schools in database (add sample data for testing)");
                $this->printSchoolInsertSQL();
            } else {
                $this->success("✓ Found " . count($schools) . " schools:");
                foreach ($schools as $school) {
                    echo "  - {$school['name']} (slug: {$school['slug']})\n";
                }
            }
        } catch (Exception $e) {
            $this->error("✗ Error querying schools: " . $e->getMessage());
        }
    }

    public function validateUsers()
    {
        echo "\n" . BLUE . "=== USERS DATA VALIDATION ===" . RESET . "\n";

        try {
            $stmt = $this->pdo->query("SELECT id, school_id, name, email FROM users LIMIT 10");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($users) === 0) {
                $this->warning("⚠ No users in database (add sample data for testing)");
                $this->printUserInsertSQL();
            } else {
                $this->success("✓ Found " . count($users) . " users:");
                foreach ($users as $user) {
                    echo "  - {$user['name']} ({$user['email']}) - school_id: {$user['school_id']}\n";
                }
            }
        } catch (Exception $e) {
            $this->error("✗ Error querying users: " . $e->getMessage());
        }
    }

    public function validateTenantClass()
    {
        echo "\n" . BLUE . "=== TENANT CLASS VALIDATION ===" . RESET . "\n";

        try {
            require __DIR__ . '/src/Tenant.php';
            $this->success("✓ Tenant class can be loaded");

            // Test main domain
            $tenant = new Tenant($this->pdo, 'perpus.test');
            if ($tenant->isMainDomain()) {
                $this->success("✓ Main domain detection works");
            } else {
                $this->error("✗ Main domain detection failed");
            }

            // Test subdomain (if exists in DB)
            $stmt = $this->pdo->query("SELECT slug FROM schools LIMIT 1");
            $school = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($school) {
                $slug = $school['slug'];
                $tenant2 = new Tenant($this->pdo, "{$slug}.perpus.test");
                if ($tenant2->isValidTenant()) {
                    $this->success("✓ Subdomain detection works");
                } else {
                    $this->error("✗ Subdomain detection failed for: $slug");
                }
            }
        } catch (Exception $e) {
            $this->error("✗ Tenant class error: " . $e->getMessage());
        }
    }

    public function validateQueries()
    {
        echo "\n" . BLUE . "=== QUERY VALIDATION ===" . RESET . "\n";

        try {
            // Get first school
            $stmt = $this->pdo->query("SELECT id FROM schools LIMIT 1");
            $school = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$school) {
                $this->warning("⚠ No schools to test queries");
                return;
            }

            $school_id = $school['id'];

            // Test books query
            try {
                $stmt = $this->pdo->prepare("SELECT * FROM books WHERE school_id = ? LIMIT 1");
                $stmt->execute([$school_id]);
                $this->success("✓ Books query with school_id filter works");
            } catch (Exception $e) {
                $this->error("✗ Books query failed: " . $e->getMessage());
            }

            // Test members query
            try {
                $stmt = $this->pdo->prepare("SELECT * FROM members WHERE school_id = ? LIMIT 1");
                $stmt->execute([$school_id]);
                $this->success("✓ Members query with school_id filter works");
            } catch (Exception $e) {
                $this->error("✗ Members query failed: " . $e->getMessage());
            }

            // Test borrows query with joins
            try {
                $stmt = $this->pdo->prepare("
                    SELECT b.* FROM borrows b
                    WHERE b.school_id = ?
                    LIMIT 1
                ");
                $stmt->execute([$school_id]);
                $this->success("✓ Borrows query with school_id filter works");
            } catch (Exception $e) {
                $this->error("✗ Borrows query failed: " . $e->getMessage());
            }
        } catch (Exception $e) {
            $this->error("✗ Query validation error: " . $e->getMessage());
        }
    }

    // ==================== HELPER METHODS ====================

    private function success($msg)
    {
        echo GREEN . $msg . RESET . "\n";
        $this->successes[] = $msg;
    }

    private function error($msg)
    {
        echo RED . $msg . RESET . "\n";
        $this->errors[] = $msg;
    }

    private function warning($msg)
    {
        echo YELLOW . $msg . RESET . "\n";
        $this->warnings[] = $msg;
    }

    private function printSchoolInsertSQL()
    {
        echo YELLOW . "\nRecommended SQL to insert sample schools:\n" . RESET;
        echo <<<SQL
INSERT INTO schools (name, slug) VALUES
('SMA 1 Jakarta', 'sma1'),
('SMP 5 Bandung', 'smp5'),
('SMA Negeri 3 Surabaya', 'sma3');
SQL . "\n\n";
    }

    private function printUserInsertSQL()
    {
        echo YELLOW . "\nRecommended SQL to insert sample users:\n" . RESET;
        echo <<<SQL
-- Password hash for 'password' is:
-- \$2y\$10\$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36CHqKPm

INSERT INTO users (school_id, name, email, password, role) VALUES
(1, 'Admin SMA 1', 'admin@sma1.com', '\$2y\$10\$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36CHqKPm', 'admin'),
(2, 'Admin SMP 5', 'admin@smp5.com', '\$2y\$10\$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36CHqKPm', 'admin'),
(3, 'Admin SMA 3', 'admin@sma3.com', '\$2y\$10\$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36CHqKPm', 'admin');
SQL . "\n\n";
    }

    public function printSummary()
    {
        echo "\n" . BLUE . "=== SUMMARY ===" . RESET . "\n";
        echo GREEN . "Successes: " . count($this->successes) . RESET . "\n";
        echo RED . "Errors: " . count($this->errors) . RESET . "\n";
        echo YELLOW . "Warnings: " . count($this->warnings) . RESET . "\n\n";

        if (count($this->errors) === 0) {
            echo GREEN . "✓ All validations passed!" . RESET . "\n";
            echo "System is ready for testing.\n";
            return true;
        } else {
            echo RED . "✗ Some validations failed. Please fix the errors above." . RESET . "\n";
            return false;
        }
    }

    public function run()
    {
        echo BLUE . "\n╔════════════════════════════════════════════════════════╗\n";
        echo "║   MULTI-TENANT SYSTEM VALIDATOR                        ║\n";
        echo "║   Perpustakaan Online - Tahap 3                        ║\n";
        echo "╚════════════════════════════════════════════════════════╝\n" . RESET;

        $this->validateFiles();
        $this->validateDatabase();
        $this->validateSchools();
        $this->validateUsers();
        $this->validateTenantClass();
        $this->validateQueries();

        return $this->printSummary();
    }
}

// Run validator
$validator = new MultiTenantValidator();
$success = $validator->run();
exit($success ? 0 : 1);
