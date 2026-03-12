<?php
/**
 * FineHelper - Centralized utility for fine calculations
 */
class FineHelper
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get total unpaid fine for a member
     * Includes:
     * 1. Late return fines from 'borrows' table (fine_status = 'unpaid')
     * 2. Damage fines from 'book_damage_fines' table (status = 'pending')
     */
    public function getTotalUnpaidFine($memberId)
    {
        $total = 0;

        try {
            // 1. Calculate late return fines
            $stmt1 = $this->pdo->prepare(
                "SELECT SUM(fine_amount) as late_fines 
                 FROM borrows 
                 WHERE member_id = :member_id AND fine_status = 'unpaid'"
            );
            $stmt1->execute(['member_id' => $memberId]);
            $res1 = $stmt1->fetch();
            $total += (float) ($res1['late_fines'] ?? 0);

            // 2. Calculate damage fines
            $stmt2 = $this->pdo->prepare(
                "SELECT SUM(fine_amount) as damage_fines 
                 FROM book_damage_fines 
                 WHERE member_id = :member_id AND status = 'pending'"
            );
            $stmt2->execute(['member_id' => $memberId]);
            $res2 = $stmt2->fetch();
            $total += (float) ($res2['damage_fines'] ?? 0);

            return $total;
        } catch (Exception $e) {
            error_log("FineHelper::getTotalUnpaidFine Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get detailed list of unpaid fines
     */
    public function getUnpaidFineDetails($memberId)
    {
        $fines = [];

        try {
            // 1. Get late return fines
            $stmt1 = $this->pdo->prepare(
                "SELECT b.id, 'Keterlambatan' as type, bk.title as book_title, 
                        b.fine_amount, b.returned_at as date
                 FROM borrows b
                 JOIN books bk ON b.book_id = bk.id
                 WHERE b.member_id = :member_id AND b.fine_status = 'unpaid' AND b.fine_amount > 0"
            );
            $stmt1->execute(['member_id' => $memberId]);
            $lateFines = $stmt1->fetchAll(PDO::FETCH_ASSOC);
            foreach ($lateFines as $f) {
                $fines[] = $f;
            }

            // 2. Get damage fines
            $stmt2 = $this->pdo->prepare(
                "SELECT d.id, 'Kerusakan' as type, bk.title as book_title, 
                        d.fine_amount, d.created_at as date, d.damage_description
                 FROM book_damage_fines d
                 JOIN books bk ON d.book_id = bk.id
                 WHERE d.member_id = :member_id AND d.status = 'pending'"
            );
            $stmt2->execute(['member_id' => $memberId]);
            $damageFines = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            foreach ($damageFines as $f) {
                $fines[] = $f;
            }

            // Sort by date descending
            usort($fines, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

            return $fines;
        } catch (Exception $e) {
            error_log("FineHelper::getUnpaidFineDetails Error: " . $e->getMessage());
            return [];
        }
    }
}
