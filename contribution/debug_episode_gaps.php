<?php
/**
 * Temporary debug utility for episode gaps.
 *
 * Usage:
 *   php contribution/debug_episode_gaps.php
 *   php contribution/debug_episode_gaps.php 21 12345
 */

declare(strict_types=1);

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', '0');

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/..');
$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'localhost';
require_once $_SERVER['DOCUMENT_ROOT'] . '/_config.php';

function is_missing_episode_value($value): bool
{
    if (!isset($value) || $value === '' || $value === null) {
        return true;
    }

    if (is_numeric($value)) {
        return (int)$value <= 0;
    }

    return false;
}

function zpi_json(string $endpoint): ?array
{
    $raw = zpi_get($endpoint);
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function debug_info_gap(string $animeId): ?array
{
    $info = zpi_json('/info?id=' . urlencode($animeId));
    if (!$info || empty($info['success'])) {
        return [
            'id' => $animeId,
            'error' => 'info endpoint failed',
        ];
    }

    $data = $info['results']['data'] ?? [];
    $tvInfo = $data['animeInfo']['tvInfo'] ?? [];

    $totalEpisodes = $data['totalEpisodes'] ?? ($tvInfo['totalEpisodes'] ?? ($tvInfo['eps'] ?? ($tvInfo['sub'] ?? null)));
    $currentEpisode = $data['currentEpisode'] ?? ($tvInfo['currentEpisode'] ?? ($tvInfo['sub'] ?? null));

    $episodesResp = zpi_json('/episodes/' . urlencode($animeId));
    $episodesCount = $episodesResp['results']['totalEpisodes'] ?? null;

    $isTotalMissing = is_missing_episode_value($totalEpisodes);
    $isCurrentMissing = is_missing_episode_value($currentEpisode);

    if (!$isTotalMissing && !$isCurrentMissing) {
        return null;
    }

    return [
        'id' => $animeId,
        'title' => $data['title'] ?? ($data['jname'] ?? 'Unknown'),
        'totalEpisodes' => $totalEpisodes,
        'currentEpisode' => $currentEpisode,
        'episodesEndpointTotal' => $episodesCount,
        'tvInfo' => [
            'sub' => $tvInfo['sub'] ?? null,
            'eps' => $tvInfo['eps'] ?? null,
            'dub' => $tvInfo['dub'] ?? null,
            'showType' => $tvInfo['showType'] ?? null,
        ],
    ];
}

function debug_category_gap(string $category, int $page = 1): array
{
    $resp = zpi_json('/' . $category . '?page=' . $page);
    if (!$resp || empty($resp['success'])) {
        return [[
            'category' => $category,
            'error' => 'category endpoint failed',
        ]];
    }

    $rows = $resp['results']['data'] ?? [];
    $gaps = [];

    foreach ($rows as $row) {
        $tvInfo = $row['tvInfo'] ?? [];
        $totalEpisodes = $row['totalEpisodes'] ?? ($tvInfo['eps'] ?? ($tvInfo['sub'] ?? null));
        $currentEpisode = $row['currentEpisode'] ?? ($tvInfo['sub'] ?? null);

        if (is_missing_episode_value($totalEpisodes) || is_missing_episode_value($currentEpisode)) {
            $gaps[] = [
                'category' => $category,
                'id' => $row['id'] ?? null,
                'title' => $row['title'] ?? ($row['jname'] ?? 'Unknown'),
                'totalEpisodes' => $totalEpisodes,
                'currentEpisode' => $currentEpisode,
                'tvInfo' => [
                    'sub' => $tvInfo['sub'] ?? null,
                    'eps' => $tvInfo['eps'] ?? null,
                    'dub' => $tvInfo['dub'] ?? null,
                ],
            ];
        }
    }

    return $gaps;
}

$animeIds = array_slice($argv, 1);
if (empty($animeIds)) {
    // Defaults: One Piece and Chiikawa on MAL/Jikan.
    $animeIds = ['21', '50250'];
}

$report = [
    'generatedAtUtc' => gmdate('c'),
    'checkedAnime' => [],
    'homeCategoryGaps' => [],
    'notes' => [
        'Current details fetch uses /info?id={id}.',
        'This script also compares /episodes/{id} as fallback visibility check.',
    ],
];

foreach ($animeIds as $id) {
    $gap = debug_info_gap((string)$id);
    if ($gap !== null) {
        $report['checkedAnime'][] = $gap;
    }
}

foreach (['top-airing', 'most-favorite', 'most-popular'] as $category) {
    $report['homeCategoryGaps'] = array_merge($report['homeCategoryGaps'], debug_category_gap($category, 1));
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
