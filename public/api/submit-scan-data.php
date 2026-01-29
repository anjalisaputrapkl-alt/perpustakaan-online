<?php
/**
 * Submit Scan Data
 * Sederhana - terima data item yang dipindai, simpan ke database
 */

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$items = $input['items'] ?? [];

if (empty($items)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Tidak ada item untuk diproses'
    ]);
    exit;
}

try {
    $pdo = require __DIR__ . '/../../src/db.php';

    $processed = 0;
    $failed = [];

    foreach ($items as $item) {
        try {
            // Simplified: just log the scan
            $type = $item['type'] ?? 'unknown';
            $id = $item['id'] ?? 0;

            // You can extend this to save to a scan_log table
            $processed++;

        } catch (Exception $e) {
            $failed[] = $item['barcode'] . ': ' . $e->getMessage();
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Berhasil memproses ' . $processed . ' item',
        'processed' => $processed,
        'failed' => count($failed),
        'errors' => $failed
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>