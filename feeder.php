<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if (!isset($_GET['url'])) {
    http_response_code(400);
    echo json_encode(['error' => 'URL parameter is missing']);
    exit;
}

$targetUrl = $_GET['url'];

$parsedUrl = parse_url($targetUrl);
if (!$parsedUrl || !isset($parsedUrl['scheme'], $parsedUrl['host'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid URL']);
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HEADER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    http_response_code(500);
    echo json_encode(['error' => "Failed to fetch data: $error"]);
    exit;
}

if ($httpCode >= 400) {
    http_response_code($httpCode);
    echo json_encode(['error' => "Server returned error: HTTP $httpCode", 'response' => $response]);
    exit;
}

echo $response;
?>