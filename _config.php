<?php 

// Tolerate DB outage so non-DB pages and the /api JSON proxy still work
// when credentials are missing or the host is unreachable. PHP 8.1+ makes
// mysqli throw exceptions by default; suppress that for the constructor.
mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli("sql113.infinityfree.com", "if0_41711685", "ThI3nYB2Kpqr", "if0_41711685_jxfr");

if (!$conn || $conn->connect_error) {
    error_log("Database connection failed: " . ($conn ? $conn->connect_error : 'no connection object'));
}

$websiteTitle = "AniPaca";
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$websiteUrl = "{$protocol}://{$_SERVER['SERVER_NAME']}";
$websiteLogo = $websiteUrl . "/public/logo/logo.png";
$contactEmail = "raisulentertainment@gmail.com";

$version = "1.0.2";

$discord = "https://dcd.gg/anipaca";
$github = "https://github.com/PacaHat";
$telegram = "https://t.me/anipaca";
$instagram = "https://www.instagram.com/pxr15_"; 

// all the api you need
$zpi = "https://your-hosted-api.com/api"; //https://github.com/PacaHat/zen-api
// Metadata endpoints are served by the local Jikan shim (loaded below).
// Player/streaming endpoints (/servers, /stream) still hit $zpi above.
// $metaApi is a same-origin URL used by browser-side JS fetches that
// previously hit $zpi for metadata (search, schedule, filter, etc.).
require_once($_SERVER['DOCUMENT_ROOT'] . '/src/lib/zpi_shim.php');
$metaApi = "{$protocol}://" . ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME']) . "/api";
$proxy = $websiteUrl . "/src/ajax/proxy.php?url=";

//If you want faster loading speed just put // before the first proxy and remove slashes from this one 
//$proxy = "https://your-hosted-proxy.com/proxy?url="; //https://github.com/PacaHat/shrina-proxy


$banner = $websiteUrl . "/public/images/banner.png";

    
