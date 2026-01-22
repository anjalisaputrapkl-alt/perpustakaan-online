<?php
$pdo = require __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/NotificationsService.php';

echo "=== TEST NOTIFIKASI ===\n\n";

// Test 1: Query table notifications
echo "1. Checking if notifications table exists...\n";
$result = $pdo->query('SHOW TABLES LIKE "notifications"');
if ($result->rowCount() > 0) {
    echo "✓ Tabel notifications ada\n\n";
} else {
    echo "✗ Tabel notifications TIDAK ada!\n\n";
    exit;
}

// Test 2: Count total notifications
echo "2. Counting total notifications...\n";
$count = $pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn();
echo "Total notifications di database: $count\n\n";

// Test 3: Get notifications untuk student ID 1
echo "3. Fetching notifications for student ID 1...\n";
$stmt = $pdo->prepare('SELECT id, title, message, type, created_at FROM notifications WHERE student_id = :sid LIMIT 10');
$stmt->execute([':sid' => 1]);
$notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($notifs) > 0) {
    echo "✓ Found " . count($notifs) . " notifications:\n";
    foreach ($notifs as $n) {
        echo "  - [{$n['type']}] {$n['title']}\n";
        echo "    {$n['message']}\n";
        echo "    Created: {$n['created_at']}\n\n";
    }
} else {
    echo "✗ No notifications found for student 1\n\n";
}

// Test 4: Test NotificationsService
echo "4. Testing NotificationsService class...\n";
try {
    $service = new NotificationsService($pdo);
    $all = $service->getAllNotifications(1);
    echo "✓ Service fetched " . count($all) . " notifications\n";
    
    if (count($all) > 0) {
        echo "\nFirst notification:\n";
        $first = $all[0];
        echo "  Judul: {$first['judul']}\n";
        echo "  Pesan: {$first['pesan']}\n";
        echo "  Tipe: {$first['jenis_notifikasi']}\n";
        echo "  Tanggal: {$first['tanggal']}\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test 5: Check if NotificationsHelper works
echo "\n5. Testing NotificationsHelper manual creation...\n";
require_once __DIR__ . '/../src/NotificationsHelper.php';
try {
    $helper = new NotificationsHelper($pdo);
    // Create test notification
    $result = $helper->createNotification(
        1, // school_id
        1, // student_id
        'borrow',
        'Test Notification',
        'This is a test notification to verify the system works.'
    );
    
    if ($result) {
        echo "✓ Test notification created successfully!\n\n";
        
        // Fetch it back
        $stmt = $pdo->prepare('SELECT * FROM notifications WHERE student_id = :sid ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([':sid' => 1]);
        $test = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Fetched back:\n";
        echo "  ID: {$test['id']}\n";
        echo "  Title: {$test['title']}\n";
        echo "  Type: {$test['type']}\n";
        echo "  Created: {$test['created_at']}\n";
    } else {
        echo "✗ Failed to create test notification\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== TEST SELESAI ===\n";
?>
