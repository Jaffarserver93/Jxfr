<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/_config.php');

// Allow CORS for your domain
$origin = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
$originHost = parse_url($origin, PHP_URL_HOST);
$currentHost = $_SERVER['HTTP_HOST'];
$websiteUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$currentHost";

if ($originHost !== $currentHost && $originHost !== 'localhost') {
    http_response_code(403);
    echo json_encode(["error" => "Access denied, domain not allowed."]);
    exit;
}

header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: *");

if (!isset($_GET['url']) || empty($_GET['url'])) {
    http_response_code(400);
    echo json_encode(["error" => "URL parameter is required"]);
    exit;
}

$url = trim($_GET['url']);
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid URL"]);
    exit;
}

// Fetch original content
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => [
        "Referer: https://megacloud.club/",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36",
        "Accept: */*"
    ]
]);
$response = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $httpCode !== 200) {
    http_response_code($httpCode !== 200 ? $httpCode : 500);
    echo json_encode(["error" => "Failed to fetch resource", "status" => $httpCode]);
    exit;
}

// Handle playlist rewriting
$ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
$isPlaylist = in_array($ext, ['m3u8', 'mpd', 'txt']);

if ($isPlaylist) {
    $baseUrl = preg_replace('/[^\/]+$/', '', $url);
    $lines = explode("\n", $response);
    $processedLines = [];
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        // Keep comments and empty lines as-is
        if (empty($trimmed) || strpos($trimmed, '#') === 0) {
            $processedLines[] = $line;
            continue;
        }
        
        // Process all lines as potential URLs
        if (!empty($trimmed)) {
            $fullUrl = (strpos($trimmed, 'http') === 0) ? $trimmed : rtrim($baseUrl, '/') . '/' . ltrim($trimmed, '/');
            $processedLines[] = $websiteUrl . "/src/ajax/proxy.php?url=" . urlencode($fullUrl);
        } else {
            $processedLines[] = $line;
        }
    }
    
    $response = implode("\n", $processedLines);
    
    // Additional DASH handling
    if ($ext === 'mpd') {
        $response = preg_replace_callback('/<BaseURL>(.*?)<\/BaseURL>/i', function ($matches) use ($baseUrl, $websiteUrl) {
            $full = (strpos($matches[1], 'http') === 0) ? $matches[1] : rtrim($baseUrl, '/') . '/' . ltrim($matches[1], '/');
            return "<BaseURL>" . htmlspecialchars($websiteUrl . "/src/ajax/proxy.php?url=" . urlencode($full)) . "</BaseURL>";
        }, $response);
    }
}

// Serve the response
header("Content-Type: $contentType");
header("Cache-Control: max-age=3600, public");
echo $response;
