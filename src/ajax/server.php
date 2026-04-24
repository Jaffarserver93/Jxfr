<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$episodeParam = $_GET['episodeId'] ?? null;

if (empty($episodeParam)) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing episodeId',
        'sub' => [],
        'dub' => [],
    ]);
    exit;
}

$fastServer = [
    'serverName' => 'Fast',
    'serverId' => 'fast',
    'provider' => 'megaplay.buzz',
];

echo json_encode([
    'success' => true,
    'episodeId' => (string)$episodeParam,
    'sub' => [$fastServer],
    'dub' => [$fastServer],
]);
