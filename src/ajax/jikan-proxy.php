<?php
// Only the shim is needed here — avoid loading _config.php so that the JSON
// proxy keeps working even if the DB connection in _config.php is unavailable.
require_once($_SERVER['DOCUMENT_ROOT'] . '/src/lib/zpi_shim.php');
header('Content-Type: application/json');
header('Cache-Control: public, max-age=300');

$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$path = preg_replace('#^/api/?#', '/', $uri);
$qs   = $_SERVER['QUERY_STRING'] ?? '';
$endpoint = $qs !== '' ? ($path . '?' . $qs) : $path;

echo zpi_get($endpoint);
