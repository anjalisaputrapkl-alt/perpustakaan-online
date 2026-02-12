<?php
/**
 * ThemeModel
 * Mengelola tema sekolah untuk multi-tenant system
 * Fetch tema berdasarkan school_id
 */

class ThemeModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Ambil tema sekolah berdasarkan school_id
     * @param int $school_id
     * @return array|null
     */
    public function getSchoolTheme($school_id)
    {
        $stmt = $this->pdo->prepare('
            SELECT theme_name, custom_colors, typography
            FROM school_themes
            WHERE school_id = :school_id
            LIMIT 1
        ');
        $stmt->execute(['school_id' => $school_id]);
        $result = $stmt->fetch();

        // Default theme jika belum ada
        if (!$result) {
            return [
                'theme_name' => 'light',
                'custom_colors' => null,
                'typography' => null
            ];
        }

        return $result;
    }

    /**
     * Ambil tema by school_id dengan format siap pakai
     * @param int $school_id
     * @return array
     */
    public function getThemeData($school_id)
    {
        $theme = $this->getSchoolTheme($school_id);

        return [
            'theme_name' => $theme['theme_name'] ?? 'light',
            'custom_colors' => json_decode($theme['custom_colors'] ?? '{}', true),
            'typography' => json_decode($theme['typography'] ?? '{}', true)
        ];
    }

    /**
     * Simpan tema untuk sekolah
     * @param int $school_id
     * @param string $theme_name
     * @param array|null $custom_colors
     * @param array|null $typography
     * @return bool
     */
    public function saveSchoolTheme($school_id, $theme_name, $custom_colors = null, $typography = null)
    {
        // Check if exists
        $stmt = $this->pdo->prepare('SELECT id FROM school_themes WHERE school_id = :school_id');
        $stmt->execute(['school_id' => $school_id]);
        $exists = $stmt->fetchColumn();

        $colors_json = $custom_colors ? json_encode($custom_colors) : null;
        $typo_json = $typography ? json_encode($typography) : null;

        if ($exists) {
            // Update
            $stmt = $this->pdo->prepare('
                UPDATE school_themes 
                SET theme_name = :theme_name, 
                    custom_colors = :colors, 
                    typography = :typography,
                    updated_at = NOW()
                WHERE school_id = :school_id
            ');
            return $stmt->execute([
                'theme_name' => $theme_name,
                'colors' => $colors_json,
                'typography' => $typo_json,
                'school_id' => $school_id
            ]);
        } else {
            // Insert
            $stmt = $this->pdo->prepare('
                INSERT INTO school_themes 
                (school_id, theme_name, custom_colors, typography)
                VALUES (:school_id, :theme_name, :colors, :typography)
            ');
            return $stmt->execute([
                'school_id' => $school_id,
                'theme_name' => $theme_name,
                'colors' => $colors_json,
                'typography' => $typo_json
            ]);
        }
    }
    /**
     * Cek apakah ada tema khusus yang aktif untuk hari ini
     * @param int $school_id
     * @return string|null theme_key atau null
     */
    public function checkSpecialTheme($school_id)
    {
        $stmt = $this->pdo->prepare('
            SELECT theme_key 
            FROM special_themes 
            WHERE school_id = :school_id 
            AND is_active = 1 
            AND date = CURRENT_DATE()
            LIMIT 1
        ');
        $stmt->execute(['school_id' => $school_id]);
        return $stmt->fetchColumn();
    }

    /**
     * Ambil semua daftar hari penting untuk sekolah tertentu
     * @param int $school_id
     * @return array
     */
    public function getSpecialThemes($school_id)
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM special_themes 
            WHERE school_id = :school_id 
            ORDER BY date ASC
        ');
        $stmt->execute(['school_id' => $school_id]);
        return $stmt->fetchAll();
    }

    /**
     * Tambah hari penting baru
     */
    public function addSpecialTheme($data)
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO special_themes (school_id, name, date, theme_key, description, is_active)
            VALUES (:school_id, :name, :date, :theme_key, :description, :is_active)
        ');
        return $stmt->execute($data);
    }

    /**
     * Hapus hari penting
     */
    public function deleteSpecialTheme($id, $school_id)
    {
        $stmt = $this->pdo->prepare('DELETE FROM special_themes WHERE id = :id AND school_id = :school_id');
        return $stmt->execute(['id' => $id, 'school_id' => $school_id]);
    }

    /**
     * Toggle status aktif hari penting
     */
    public function toggleSpecialTheme($id, $school_id, $status)
    {
        $stmt = $this->pdo->prepare('
            UPDATE special_themes 
            SET is_active = :status 
            WHERE id = :id AND school_id = :school_id
        ');
        return $stmt->execute(['status' => $status, 'id' => $id, 'school_id' => $school_id]);
    }
}
?>