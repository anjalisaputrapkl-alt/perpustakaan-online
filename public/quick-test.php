<?php
/**
 * Simple test - verify everything is working
 */
require __DIR__ . '/../src/auth.php';
requireAuth();

if ($_SESSION['user']['role'] !== 'admin') {
    die('Admin only');
}

$pdo = require __DIR__ . '/../src/db.php';
$user = $_SESSION['user'];
$sid = $user['school_id'];

// Get one pending confirmation for quick test
$stmt = $pdo->prepare('
    SELECT b.*, bk.title, m.name, m.nisn
    FROM borrows b
    LEFT JOIN books bk ON b.book_id = bk.id
    LEFT JOIN members m ON b.member_id = m.id
    WHERE b.school_id = ? AND b.status = "pending_confirmation"
    LIMIT 1
');
$stmt->execute([$sid]);
$testRecord = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quick Test - Borrows</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #eee;
        }

        .section:last-child {
            border-bottom: none;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-ok {
            background: #d4edda;
            color: #155724;
        }

        .status-warning {
            background: #fff3cd;
            color: #856404;
        }

        .status-error {
            background: #f8d7da;
            color: #721c24;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 20px;
            margin: 10px 0;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
            font-size: 13px;
        }

        .detail-value {
            color: #333;
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 13px;
            word-break: break-all;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            text-align: center;
            margin: 8px 8px 8px 0;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #e9ecef;
            color: #495057;
        }

        .btn-secondary:hover {
            background: #dee2e6;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .code-block {
            background: #f5f5f5;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 4px;
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 12px;
            margin: 15px 0;
            overflow-x: auto;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🧪 Quick Test - Borrows System</h1>
        <p class="subtitle">Verify buttons and API are working</p>

        <!-- System Info -->
        <div class="section">
            <h2 style="font-size: 16px; margin-bottom: 15px; color: #333;">📊 System Info</h2>
            <div class="detail-row">
                <div class="detail-label">User</div>
                <div class="detail-value"><?= htmlspecialchars($_SESSION['user']['name']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Role</div>
                <div class="detail-value">
                    <span class="status-badge status-ok"><?= htmlspecialchars($_SESSION['user']['role']) ?></span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">School ID</div>
                <div class="detail-value"><?= htmlspecialchars($sid) ?></div>
            </div>
        </div>

        <!-- Test Record -->
        <div class="section">
            <h2 style="font-size: 16px; margin-bottom: 15px; color: #333;">📋 Test Data</h2>

            <?php if (!$testRecord): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <p>No pending confirmations found</p>
                    <p style="font-size: 12px; margin-top: 10px;">
                        Create test data using:
                    </p>
                    <div class="code-block">
                        INSERT INTO borrows
                        (member_id, book_id, borrowed_at, due_at, status, school_id)
                        VALUES
                        (1, 1, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 'pending_confirmation', <?= $sid ?>);
                    </div>
                </div>
            <?php else: ?>
                <div class="detail-row">
                    <div class="detail-label">Borrow ID</div>
                    <div class="detail-value"><?= htmlspecialchars($testRecord['id']) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Member</div>
                    <div class="detail-value">
                        <?= htmlspecialchars($testRecord['name'] . ' (' . $testRecord['nisn'] . ')') ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Book</div>
                    <div class="detail-value"><?= htmlspecialchars($testRecord['title'] ?? 'Unknown') ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Borrowed At</div>
                    <div class="detail-value"><?= date('Y-m-d H:i:s', strtotime($testRecord['borrowed_at'])) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Due At</div>
                    <div class="detail-value"><?= date('Y-m-d H:i:s', strtotime($testRecord['due_at'])) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <span class="status-badge status-warning"><?= htmlspecialchars($testRecord['status']) ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Test Actions -->
        <div class="section">
            <h2 style="font-size: 16px; margin-bottom: 15px; color: #333;">🚀 Test Actions</h2>

            <?php if ($testRecord): ?>
                <button class="btn btn-success" onclick="testApprove(<?= $testRecord['id'] ?>)">
                    ✓ Test Approve (7 days)
                </button>
                <button class="btn btn-primary" onclick="testApproveCustom(<?= $testRecord['id'] ?>, 14)">
                    ✓ Test Approve (14 days)
                </button>
                <button class="btn btn-primary" onclick="testReject(<?= $testRecord['id'] ?>)">
                    ✗ Test Reject
                </button>
            <?php endif; ?>

            <a href="borrows.php" class="btn btn-secondary">↩️ Back to Borrows</a>
            <a href="debug-borrows.php" class="btn btn-secondary">🔧 Debug Page</a>
        </div>

        <!-- Console -->
        <div class="section">
            <h2 style="font-size: 16px; margin-bottom: 15px; color: #333;">💻 Browser Console</h2>
            <p style="font-size: 12px; color: #666; margin-bottom: 10px;">
                After clicking test buttons, check browser console (F12) for logs:
            </p>
            <div class="code-block">
                [APPROVE] Starting with borrowIds: ...
                [APPROVE] Response status: 200
                [APPROVE] Response data: {success: true, ...}
            </div>
        </div>

        <!-- Output -->
        <div id="output" style="margin-top: 20px;"></div>
    </div>

    <script>
        function showOutput(msg, type = 'info') {
            const output = document.getElementById('output');
            const time = new Date().toLocaleTimeString();
            const icon = type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ';
            const color = type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#667eea';

            const div = document.createElement('div');
            div.style.cssText = `
                padding: 12px;
                margin: 8px 0;
                background: #f8f9fa;
                border-left: 4px solid ${color};
                border-radius: 4px;
                font-size: 13px;
            `;
            div.innerHTML = `<strong style="color: ${color};">${icon}</strong> ${time} - ${msg}`;
            output.appendChild(div);
            output.scrollTop = output.scrollHeight;
        }

        function testApprove(borrowId) {
            showOutput(`Testing approve ID ${borrowId}...`);
            fetch('api/approve-borrow.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'borrow_id=' + borrowId
            })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        showOutput(`Success: ${d.message}`, 'success');
                    } else {
                        showOutput(`Failed: ${d.message}`, 'error');
                    }
                })
                .catch(e => showOutput(`Error: ${e.message}`, 'error'));
        }

        function testApproveCustom(borrowId, days) {
            showOutput(`Testing approve ID ${borrowId} with ${days} days...`);
            const due = new Date();
            due.setDate(due.getDate() + days);
            const dueStr = due.toISOString().slice(0, 10) + ' ' + due.toTimeString().slice(0, 8);

            fetch('api/approve-borrow.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'borrow_id=' + borrowId + '&due_at=' + encodeURIComponent(dueStr)
            })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        showOutput(`Success: ${d.message} (Due: ${dueStr})`, 'success');
                    } else {
                        showOutput(`Failed: ${d.message}`, 'error');
                    }
                })
                .catch(e => showOutput(`Error: ${e.message}`, 'error'));
        }

        function testReject(borrowId) {
            showOutput(`Testing reject ID ${borrowId}...`);
            fetch('api/reject-borrow.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'borrow_id=' + borrowId
            })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        showOutput(`Success: ${d.message}`, 'success');
                    } else {
                        showOutput(`Failed: ${d.message}`, 'error');
                    }
                })
                .catch(e => showOutput(`Error: ${e.message}`, 'error'));
        }
    </script>
</body>

</html>