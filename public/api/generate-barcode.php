<?php
/**
 * Generate Barcode (Code 128)
 * Using external API (bwip-js)
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400'); // Cache for 1 day

$text = $_GET['text'] ?? '';
$scale = $_GET['scale'] ?? 2;
$height = $_GET['height'] ?? 10;

if (empty($text)) {
    // Return a 1x1 transparent pixel if no text
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    exit;
}

// Ensure alphanumeric only for safety if needed, though Code 128 supports full ASCII
// $text = preg_replace('/[^a-zA-Z0-9]/', '', $text);

// Use bwip-js online API or similar
// bcid=code128, text=$text, scale=$scale, height=$height (mm)
$apiUrl = "https://bwipjs-api.metafloor.org/?bcid=code128&text=" . urlencode($text) . "&scale=" . intval($scale) . "&height=" . intval($height) . "&includetext&backgroundcolor=ffffff";

// Use curl to fetch
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$image = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $image) {
    echo $image;
} else {
    // Fallback or error image
    // Generate a simple error text image if possible, or just exit
    http_response_code(502);
}
