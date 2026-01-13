<?php
/**
 * Tenant.php - Multi-Tenant Detection System
 * 
 * Mendeteksi tenant (sekolah) dari subdomain dan mengelola school_id
 * Contoh:
 * - perpus.test → Main platform (no tenant)
 * - sma1.perpus.test → SMA 1 tenant
 * - smp5.perpus.test → SMP 5 tenant
 */

class Tenant
{
    private $pdo;
    private $host;
    private $subdomain;
    private $is_main_domain;
    private $school_id;
    private $school_name;
    private $school_data;

    /**
     * Constructor - Inisialisasi dengan PDO dan HTTP_HOST
     * 
     * @param PDO $pdo - Database connection
     * @param string $host - HTTP_HOST dari $_SERVER (default: localhost)
     */
    public function __construct($pdo, $host = null)
    {
        $this->pdo = $pdo;
        $this->host = $host ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $this->parseHost();
        $this->detectSchool();
    }

    /**
     * Parse HTTP_HOST untuk mengekstrak subdomain
     * 
     * Contoh parsing:
     * - perpus.test → main domain (no subdomain)
     * - sma1.perpus.test → subdomain: sma1
     * - sub.domain.perpus.test → subdomain: sub.domain
     */
    private function parseHost()
    {
        // Hapus port jika ada (contoh: perpus.test:80)
        $host = explode(':', $this->host)[0];

        // Split domain by dots
        $parts = explode('.', $host);

        // Deteksi apakah ini domain utama atau subdomain
        // Asumsi: base domain adalah 2 part terakhir (perpus.test)
        if (count($parts) === 2) {
            // Main domain (perpus.test)
            $this->is_main_domain = true;
            $this->subdomain = null;
        } else if (count($parts) >= 3) {
            // Subdomain (sma1.perpus.test)
            $this->is_main_domain = false;
            // Ambil bagian pertama sebagai subdomain (sma1)
            $this->subdomain = $parts[0];
        } else {
            // Localhost atau IP address
            $this->is_main_domain = true;
            $this->subdomain = null;
        }
    }

    /**
     * Deteksi sekolah dari subdomain
     * 
     * - Jika main domain: school_id = null
     * - Jika subdomain: cari sekolah di database berdasarkan slug
     */
    private function detectSchool()
    {
        if ($this->is_main_domain) {
            // Tidak ada tenant untuk main domain
            $this->school_id = null;
            $this->school_name = null;
            $this->school_data = null;
            return;
        }

        // Cari sekolah berdasarkan slug (subdomain)
        try {
            $stmt = $this->pdo->prepare('SELECT id, name, slug FROM schools WHERE slug = :slug LIMIT 1');
            $stmt->execute(['slug' => $this->subdomain]);
            $school = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($school) {
                $this->school_id = $school['id'];
                $this->school_name = $school['name'];
                $this->school_data = $school;
            } else {
                // Sekolah tidak ditemukan
                $this->school_id = null;
                $this->school_name = null;
                $this->school_data = null;
            }
        } catch (Exception $e) {
            error_log("Tenant detection error: " . $e->getMessage());
            $this->school_id = null;
            $this->school_name = null;
            $this->school_data = null;
        }
    }

    /**
     * Cek apakah ini main domain (perpus.test)
     * 
     * @return bool
     */
    public function isMainDomain()
    {
        return $this->is_main_domain;
    }

    /**
     * Cek apakah subdomain ditemukan dan valid
     * 
     * @return bool
     */
    public function isValidTenant()
    {
        return $this->school_id !== null && $this->school_data !== null;
    }

    /**
     * Get School ID dari tenant
     * 
     * @return int|null
     */
    public function getSchoolId()
    {
        return $this->school_id;
    }

    /**
     * Get School Name dari tenant
     * 
     * @return string|null
     */
    public function getSchoolName()
    {
        return $this->school_name;
    }

    /**
     * Get Subdomain (slug)
     * 
     * @return string|null
     */
    public function getSubdomain()
    {
        return $this->subdomain;
    }

    /**
     * Get seluruh data sekolah
     * 
     * @return array|null
     */
    public function getSchoolData()
    {
        return $this->school_data;
    }

    /**
     * Get HTTP Host (domain yang diakses)
     * 
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set tenant ke session
     * Simpan school_id dan informasi lainnya ke $_SESSION
     */
    public function setToSession()
    {
        $_SESSION['tenant'] = [
            'school_id' => $this->school_id,
            'school_name' => $this->school_name,
            'subdomain' => $this->subdomain,
            'host' => $this->host,
            'is_main_domain' => $this->is_main_domain
        ];
    }

    /**
     * Get tenant dari session
     * 
     * @return array|null
     */
    public static function getFromSession()
    {
        return $_SESSION['tenant'] ?? null;
    }

    /**
     * Enforce tenant: redirect jika subdomain tidak valid
     * 
     * @param string $redirect_to - URL untuk redirect jika tenant tidak valid
     * @return void
     */
    public function enforceValidTenant($redirect_to = 'http://perpus.test/')
    {
        if (!$this->isValidTenant()) {
            header("Location: $redirect_to");
            exit;
        }
    }
}
