<?php
/**
 * Debug page untuk troubleshoot approve/reject buttons
 */

require __DIR__ . '/../src/auth.php';
requireAuth();

// Check if user is admin
if ($_SESSION['user']['role'] !== 'admin') {
    die('Admin only');
}

// Get recent logs
$logFile = ini_get('error_log') ?: 'php_errors.log';
$logs = [];
if (file_exists($logFile)) {
    $lines = file($logFile);
    // Get last 50 lines
    $lines = array_slice($lines, -50);
    // Filter for our debug logs
    foreach ($lines as $line) {
        if (strpos($line, '[APPROVE') !== false || strpos($line, '[REJECT') !== false) {
            $logs[] = trim($line);
        }
    }
}

// Get pending confirmation borrows
$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];

$stmt = $pdo->prepare('
    SELECT 
        b.id, b.member_id, b.borrow_id,
        b.borrowed_at, b.due_at,
        m.name as member_name, m.nisn,
        bk.title as book_title,
        b.status
    FROM borrows b
    LEFT JOIN members m ON b.member_id = m.id
    LEFT JOIN books bk ON b.book_id = bk.id
    WHERE b.school_id = ? AND b.status = "pending_confirmation"
    ORDER BY b.borrowed_at DESC
    LIMIT 20
');
$stmt->execute([$user['school_id']]);
$pendings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Borrows</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            margin: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1,
        h2 {
            color: #333;
            border-bottom: 2px solid #5BA3F5;
            padding-bottom: 10px;
        }

        .debug-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #5BA3F5;
            border-radius: 4px;
        }

        .log-entry {
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 12px;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #5BA3F5;
            color: white;
            font-weight: 600;
        }

        .test-button {
            background: #5BA3F5;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
            font-weight: 600;
        }

        .test-button:hover {
            background: #4a8fd8;
        }

        .test-reject {
            background: #FF6B6B;
        }

        .test-reject:hover {
            background: #ff5252;
        }

        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending {
            background: #FFF3E0;
            color: #F57C00;
        }

        .test-output {
            background: #1e1e1e;
            color: #0f0;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            margin: 10px 0;
            max-height: 300px;
            overflow-y: auto;
            display: none;
        }

        .error {
            color: #ff6b6b;
        }

        .success {
            color: #51cf66;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔧 Debug Borrows System</h1>

        <!-- System Status -->
        <div class="debug-section">
            <h2>📊 System Status</h2>
            <p><strong>Current User:</strong> <?= htmlspecialchars($_SESSION['user']['name']) ?> (Admin)</p>
            <p><strong>School ID:</strong> <?= htmlspecialchars($user['school_id']) ?></p>
            <p><strong>Pending Confirmations:</strong> <?= count($pendings) ?> records</p>
            <p><strong>Log File:</strong> <?= $logFile ?></p>
        </div>

        <!-- Recent Logs -->
        <div class="debug-section">
            <h2>📝 Recent Debug Logs</h2>
            <?php if (empty($logs)): ?>
                <p style="color: #999;">No debug logs found yet. Click buttons to generate logs.</p>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <div class="log-entry"><?= htmlspecialchars($log) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            <button class="test-button" onclick="location.reload()">🔄 Refresh Logs</button>
        </div>

        <!-- Pending Confirmations -->
        <div class="debug-section">
            <h2>📋 Pending Confirmations</h2>
            <?php if (empty($pendings)): ?>
                <p style="color: #999;">No pending confirmations found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Siswa (NISN)</th>
                            <th>Buku</th>
                            <th>Tgl Pinjam</th>
                            <th>Tenggat</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendings as $record): ?>
                            <tr>
                                <td><?= htmlspecialchars($record['id']) ?></td>
                                <td><?= htmlspecialchars($record['member_name'] . ' (' . $record['nisn'] . ')') ?></td>
                                <td><?= htmlspecialchars($record['book_title'] ?? 'Unknown') ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($record['borrowed_at'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($record['due_at'])) ?></td>
                                <td><span class="status status-pending">Menunggu</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- API Test -->
        <div class="debug-section">
            <h2>🧪 API Test</h2>
            <p>Test single borrow approval/rejection:</p>
            <?php if (!empty($pendings)): ?>
                <button class="test-button" onclick="testApprove(<?= htmlspecialchars(json_encode($pendings[0]['id'])) ?>)">
                    ✓ Test Approve ID: <?= htmlspecialchars($pendings[0]['id']) ?>
                </button>
                <button class="test-button test-reject"
                    onclick="testReject(<?= htmlspecialchars(json_encode($pendings[0]['id'])) ?>)">
                    ✗ Test Reject ID: <?= htmlspecialchars($pendings[0]['id']) ?>
                </button>
                <button class="test-button"
                    onclick="testApproveDue(<?= htmlspecialchars(json_encode($pendings[0]['id'])) ?>, 14)">
                    ✓ Test Approve (14 days) ID: <?= htmlspecialchars($pendings[0]['id']) ?>
                </button>
            <?php else: ?>
                <p style="color: #999;">No pending records to test.</p>
            <?php endif; ?>
            <div id="testOutput" class="test-output"></div>
        </div>

        <!-- Browser Console Test -->
        <div class="debug-section">
            <h2>💻 Browser Console Test</h2>
            <p>Open browser console (F12 → Console) and run:</p>
            <div class="log-entry">
                // Test approve function
                approveAllBorrowWithDue('[1, 2, 3]', 'dueDays_test');

                // Test reject function
                rejectAllBorrow('[1, 2, 3]');

                // Manually test API fetch
                fetch('api/approve-borrow.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'borrow_id=1&due_at=' + encodeURIComponent('2024-01-20 00:00:00')
                }).then(r => r.json()).then(d => console.log('Response:', d));
            </div>
        </div>

        <!-- Quick Links -->
        <div class="debug-section">
            <h2>🔗 Quick Links</h2>
            <a href="borrows.php"
                style="display: inline-block; margin: 5px; padding: 10px 20px; background: #5BA3F5; color: white; text-decoration: none; border-radius: 4px; font-weight: 600;">↩️
                Back to Borrows</a>
            <a href="index.php"
                style="display: inline-block; margin: 5px; padding: 10px 20px; background: #51cf66; color: white; text-decoration: none; border-radius: 4px; font-weight: 600;">🏠
                Dashboard</a>
        </div>
    </div>

    <script>
        function showOutput(message, isError = false) {
            const output = document.getElementById('testOutput');
            output.style.display = 'block';
            const className = isError ? 'error' : 'success';
            output.innerHTML += `<div class="${className}">${escapeHtml(message)}\n</div>`;
        }

        function clearOutput() {
            document.getElementById('testOutput').innerHTML = '';
            document.getElementById('testOutput').style.display = 'none';
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function testApprove(borrowId) {
            clearOutput();
            showOutput(`Testing approve for ID: ${borrowId}...`);

            fetch('api/approve-borrow.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'borrow_id=' + borrowId
            })
                .then(r => {
                    showOutput(`Response status: ${r.status}`);
                    return r.json();
                })
                .then(data => {
                    if (data.success) {
                        showOutput(`✓ Success: ${data.message}`, false);
                    } else {
                        showOutput(`✗ Failed: ${data.message}`, true);
                    }
                })
                .catch(err => {
                    showOutput(`✗ Error: ${err.message}`, true);
                    console.error('Full error:', err);
                });
        }

        function testReject(borrowId) {
            clearOutput();
            showOutput(`Testing reject for ID: ${borrowId}...`);

            fetch('api/reject-borrow.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'borrow_id=' + borrowId
            })
                .then(r => {
                    showOutput(`Response status: ${r.status}`);
                    return r.json();
                })
                .then(data => {
                    if (data.success) {
                        showOutput(`✓ Success: ${data.message}`, false);
                    } else {
                        showOutput(`✗ Failed: ${data.message}`, true);
                    }
                })
                .catch(err => {
                    showOutput(`✗ Error: ${err.message}`, true);
                    console.error('Full error:', err);
                });
        }

        function testApproveDue(borrowId, days) {
            clearOutput();
            showOutput(`Testing approve with ${days} days for ID: ${borrowId}...`);

            const dueDate = new Date();
            dueDate.setDate(dueDate.getDate() + days);
            const dueString = dueDate.toISOString().slice(0, 10) + ' ' + dueDate.toTimeString().slice(0, 8);

            showOutput(`Due date: ${dueString}`);

            fetch('api/approve-borrow.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'borrow_id=' + borrowId + '&due_at=' + encodeURIComponent(dueString)
            })
                .then(r => {
                    showOutput(`Response status: ${r.status}`);
                    return r.json();
                })
                .then(data => {
                    if (data.success) {
                        showOutput(`✓ Success: ${data.message}`, false);
                    } else {
                        showOutput(`✗ Failed: ${data.message}`, true);
                    }
                })
                .catch(err => {
                    showOutput(`✗ Error: ${err.message}`, true);
                    console.error('Full error:', err);
                });
        }
    </script>
</body>

</html>