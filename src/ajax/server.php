<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
require_once($_SERVER['DOCUMENT_ROOT'] . '/_config.php');

$episodeParam = $_GET['episodeId'] ?? null;
$api_url = "$zpi/servers/" . $episodeParam;

$response = @file_get_contents($api_url);

if ($response === false) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid API response',
        'debug' => [
            'raw_response' => $response,
            'url_called' => $api_url
        ]
    ]);
    exit;
}

$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid API response',
        'debug' => [
            'raw_response' => $response,
            'url_called' => $api_url
        ]
    ]);
    exit;
}
if (empty($data['success'])) {
    echo json_encode([
        'success' => false,
        'error' => 'API reported no servers',
        'api_response' => $data
    ]);
    exit;
}
if (empty($data['results'])) {
    echo json_encode([
        'success' => false,
        'error' => 'No servers available',
        'debug' => [
            'api_url' => $api_url,
            'note' => 'API returned success but empty results'
        ]
    ]);
    exit;
}
$response = [
    'success' => true,
    'sub' => [],
    'dub' => []
];

// Process servers into their respective types
$serversByType = ['sub' => [], 'dub' => []];

foreach ($data['results'] as $server) {
    $type = strtolower($server['type'] ?? 'sub');
    $serverName = $server['serverName'] ?? 'Unknown';
    $serverId = $server['server_id'] ?? '0';
    
    if (in_array($type, ['sub', 'dub'])) {
        $serversByType[$type][$serverId] = [
            'serverName' => $serverName,
            'serverId' => $serverId
        ];
    } elseif ($type === 'raw' && empty($serversByType['sub'])) {
        // Only add raw type as sub if no other sub servers exist
        $serversByType['sub'][$serverId] = [
            'serverName' => $serverName,
            'serverId' => $serverId
        ];
    }
}

// Add Fast server as first in both sub and dub if available
$fastServer = [
    'serverName' => 'Fast',
    'serverId' => 'fast',
    'isFast' => true
];

// Add Fast server to both sub and dub if either has servers
if (!empty($serversByType['sub']) || !empty($serversByType['dub'])) {
    if (!empty($serversByType['sub'])) {
        $response['sub'][] = $fastServer;
    }
    if (!empty($serversByType['dub'])) {
        $response['dub'][] = $fastServer;
    }
}

// Add remaining servers to their respective categories
foreach (['sub', 'dub'] as $type) {
    foreach ($serversByType[$type] as $server) {
        // Skip if this is a Fast server that we already added
        if (($server['serverName'] === 'Fast' && $server['serverId'] === 'fast') || 
            isset($server['isFast'])) {
            continue;
        }
        $response[$type][] = $server;
    }
}

// Function to sort servers by serverName (excluding Fast server)
$sortServers = function(&$servers) {
    if (empty($servers)) return;
    
    // Separate Fast server (if exists) from the rest
    $fastServer = null;
    foreach ($servers as $key => $server) {
        if (($server['serverName'] === 'Fast' && $server['serverId'] === 'fast') || 
            isset($server['isFast'])) {
            $fastServer = $server;
            unset($servers[$key]);
            break;
        }
    }
    
    // Sort remaining servers
    usort($servers, function($a, $b) {
        return strnatcasecmp($a['serverName'], $b['serverName']);
    });
    
    // Re-add Fast server at the beginning if it exists
    if ($fastServer) {
        array_unshift($servers, $fastServer);
    }
};

// Sort both sub and dub arrays
$sortServers($response['sub']);
$sortServers($response['dub']);

echo json_encode($response);