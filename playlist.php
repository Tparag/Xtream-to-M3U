<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$requestUri = $_SERVER['REQUEST_URI'];
$m3u8Url = $protocol . $host . dirname($requestUri) . '/play.php?id=';

$jsonFile = "data/data.json";
$jsonContent = @file_get_contents($jsonFile);
if ($jsonContent === false) {
    http_response_code(500);
    die("Error: Unable to load JSON file at $jsonFile.");
}

$jsonData = json_decode($jsonContent, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    die("Error: Invalid JSON format in $jsonFile: " . json_last_error_msg());
}

$baseUrl = rtrim($jsonData['url'] ?? '', '/');
$user = $jsonData['user'] ?? '';
$password = $jsonData['password'] ?? '';
if (empty($baseUrl) || empty($user) || empty($password)) {
    http_response_code(500);
    die("Error: Missing url, user, or password in $jsonFile.");
}

$parsedUrl = parse_url($baseUrl);
$hostname = $parsedUrl['host'] ?? '';
if (empty($hostname)) {
    http_response_code(500);
    die("Error: Invalid URL in $jsonFile: Unable to extract hostname.");
}
$hostname = str_replace('.', '', $hostname);

$m3uFilePath = "data/playlist/{$hostname}.m3u";

$playlistDir = dirname($m3uFilePath);
if (!is_dir($playlistDir) && !mkdir($playlistDir, 0755, true)) {
    http_response_code(500);
    die("Error: Unable to create directory $playlistDir.");
}

$m3uContent = @file_get_contents($m3uFilePath);
if ($m3uContent === false) {
    $apiUrl = "$baseUrl/player_api.php?username=$user&password=$password&action=get_live_streams";
    
    $apiResponse = @file_get_contents($apiUrl);
    if ($apiResponse === false) {
        http_response_code(500);
        die("Error: Unable to fetch streams from API at $apiUrl.");
    }

    $streams = json_decode($apiResponse, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(500);
        die("Error: Invalid API response format from $apiUrl: " . json_last_error_msg());
    }

    if (!is_array($streams)) {
        http_response_code(500);
        die("Error: API response is not an array of streams.");
    }

    $categoryApiUrl = "$baseUrl/player_api.php?username=$user&password=$password&action=get_live_categories";
    $categoryResponse = @file_get_contents($categoryApiUrl);
    $categories = [];
    if ($categoryResponse !== false) {
        $categories = json_decode($categoryResponse, true) ?: [];
        if (!is_array($categories)) {
            $categories = [];
        }
    }

    $categoryMap = [];
    foreach ($categories as $cat) {
        $categoryMap[$cat['category_id']] = $cat['category_name'] ?? 'Unknown';
    }

    $m3uContent = "#EXTM3U\n";
    $streamCount = 0;

    foreach ($streams as $stream) {
        $streamId = $stream['stream_id'] ?? '';
        $streamName = $stream['name'] ?? 'Unknown Stream';
        $categoryId = $stream['category_id'] ?? '';
        $streamIcon = $stream['stream_icon'] ?? '';
        if (empty($streamId)) {
            continue;
        }

        $streamUrl = "$baseUrl/$user/$password/$streamId";
        $newStreamUrl = $m3u8Url . $streamId;

        $categoryName = $categoryMap[$categoryId] ?? 'Unknown';

        $m3uContent .= "#EXTINF:-1 tvg-id=\"$streamId\" tvg-name=\"$streamName\" tvg-logo=\"$streamIcon\" group-title=\"$categoryName\",$streamName\n$newStreamUrl\n";
        $streamCount++;
    }

    if ($streamCount === 0) {
        http_response_code(500);
        die("Error: No valid streams found in API response.");
    }

    if (@file_put_contents($m3uFilePath, $m3uContent) === false) {
        http_response_code(500);
        die("Error: Unable to save M3U file at $m3uFilePath.");
    }
} else {
    $oldUrl = "$baseUrl/$user/$password/";
    $m3uContent = str_replace($oldUrl, $m3u8Url, $m3uContent);
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

header('Content-Type: audio/x-mpegurl');
header('Content-Disposition: inline; filename="playlist.m3u"');
echo $m3uContent;
exit();
?>
