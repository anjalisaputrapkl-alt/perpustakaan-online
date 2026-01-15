<?php
// Theme API - Per-school theme management
session_start();

// Check authentication
if (empty($_SESSION['user']) || empty($_SESSION['user']['school_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require __DIR__ . '/../../src/db.php';

$school_id = $_SESSION['user']['school_id'];
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get school's theme
        $stmt = $pdo->prepare('SELECT * FROM school_themes WHERE school_id = ?');
        $stmt->execute([$school_id]);
        $result = $stmt->fetch();

        if ($result) {
            echo json_encode([
                'success' => true,
                'theme_name' => $result['theme_name'],
                'custom_colors' => json_decode($result['custom_colors'] ?? '{}', true),
                'typography' => json_decode($result['typography'] ?? '{}', true)
            ]);
        } else {
            // Return default if not set
            echo json_encode([
                'success' => true,
                'theme_name' => 'light',
                'custom_colors' => [],
                'typography' => []
            ]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Save school's theme
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['theme_name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'theme_name is required']);
            exit;
        }

        $theme_name = $data['theme_name'];
        $custom_colors = isset($data['custom_colors']) ? json_encode($data['custom_colors']) : null;
        $typography = isset($data['typography']) ? json_encode($data['typography']) : null;

        // Check if exists
        $stmt = $pdo->prepare('SELECT id FROM school_themes WHERE school_id = ?');
        $stmt->execute([$school_id]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $stmt = $pdo->prepare('UPDATE school_themes SET theme_name = ?, custom_colors = ?, typography = ? WHERE school_id = ?');
            $stmt->execute([$theme_name, $custom_colors, $typography, $school_id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO school_themes (school_id, theme_name, custom_colors, typography) VALUES (?, ?, ?, ?)');
            $stmt->execute([$school_id, $theme_name, $custom_colors, $typography]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Theme saved successfully'
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
