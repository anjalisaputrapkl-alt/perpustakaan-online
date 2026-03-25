# Modul 5: Laporan Agregasi (Reporting) & Pengaturan

## A. Laporan Cerdas Server-Side
**File: `public/reports.php`**

**1. Logika Agregasi Data**
Jika admin ingin melihat metrik penggunaan per bulan, aplikasi tidak melooping data di PHP satu-satu (yang merusak memori performa). Sistem memanggil query Group By SQL tingkat lanjut untuk menarik rekapan.
```php
$stmt = $pdo->prepare("
    SELECT COUNT(id) as total_borrows, SUM(fine_amount) as total_fines 
    FROM borrows 
    WHERE school_id = :sid AND (status = 'returned' OR status = 'overdue')
");
$stmt->execute(['sid' => $sid]);
$reportSummary = $stmt->fetch();
```

## B. Pengaturan Variabel Utama
**File: `public/settings.php`**

**1. Logika Update Setting Konfigurasi**
Jika parameter `$late_fine` (denda/hari) dan profil sekolah disubmit oleh Admin via form POST, maka akan diperbarui dalam tabel master `schools`.
```php
$stmt = $pdo->prepare("
    UPDATE schools SET 
        name = :name, 
        address = :address, 
        late_fine = :fine 
    WHERE id = :id
");
$stmt->execute([
    'name' => $_POST['school_name'],
    'address' => $_POST['school_address'],
    'fine' => $_POST['late_fine'],
    'id' => $school_id
]);
```

*Penutup: Penggunaan PHP murni berpadu PDO menjadikan aplikasi ini minim resource layer ketimbang memakai ORM (Object Relational Mapping) level berat, sehingga waktu komputasi sangat ringan!*
