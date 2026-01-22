<?php
// Simple test script untuk verify notification system

session_start();

// Assume user login sebagai student dengan id 1
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id' => 1,
        'name' => 'Test Student',
        'school_id' => 1,
        'role' => 'student'
    ];
}

$pdo = require __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/NotificationsService.php';
require_once __DIR__ . '/src/NotificationsHelper.php';

try {
    $studentId = $_SESSION['user']['id'];
    $schoolId = $_SESSION['user']['school_id'];
    
    // Test 1: Get notifications
    echo "<h3>Test 1: Get Notifications</h3>";
    $service = new NotificationsService($pdo);
    $notifications = $service->getAllNotifications($studentId);
    echo "Found " . count($notifications) . " notifications<br>";
    
    if (count($notifications) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Title</th><th>Type</th><th>Message</th><th>Date</th><th>Read</th></tr>";
        foreach (array_slice($notifications, 0, 5) as $notif) {
            echo "<tr>";
            echo "<td>" . $notif['id'] . "</td>";
            echo "<td>" . htmlspecialchars($notif['judul']) . "</td>";
            echo "<td><span style='background: #e0f2fe; padding: 5px;'>" . $notif['jenis_notifikasi'] . "</span></td>";
            echo "<td>" . substr(htmlspecialchars($notif['pesan']), 0, 50) . "...</td>";
            echo "<td>" . $notif['tanggal'] . "</td>";
            echo "<td>" . ($notif['status_baca'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No notifications found<br>";
    }
    
    // Test 2: Get statistics
    echo "<h3>Test 2: Get Statistics</h3>";
    $stats = $service->getStatistics($studentId);
    echo "<pre>";
    print_r($stats);
    echo "</pre>";
    
    // Test 3: Create a test notification
    echo "<h3>Test 3: Create Test Notification</h3>";
    $helper = new NotificationsHelper($pdo);
    $success = $helper->createNotification(
        $schoolId,
        $studentId,
        'test',
        'Test Notification',
        'This is a test notification from the system.'
    );
    
    if ($success) {
        echo "✓ Test notification created successfully!<br>";
    } else {
        echo "✗ Failed to create test notification<br>";
    }
    
    // Test 4: Verify the notification was created
    echo "<h3>Test 4: Verify Creation</h3>";
    $notificationsAfter = $service->getAllNotifications($studentId);
    echo "Notifications after test: " . count($notificationsAfter) . "<br>";
    
    if (count($notificationsAfter) > count($notifications)) {
        echo "✓ New notification was added successfully!<br>";
        $lastNotif = $notificationsAfter[0];
        echo "Last notification: " . htmlspecialchars($lastNotif['judul']) . "<br>";
    }
    
    echo "<h3>✓ All tests completed!</h3>";
    echo "<p><a href='public/notifications.php'>View Notifications Page</a></p>";
    
} catch (Exception $e) {
    echo "<h3>Error: " . htmlspecialchars($e->getMessage()) . "</h3>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
