<?php
/**
 * Jikan v4 → zen-api shim.
 *
 * Public entry point: zpi_get($endpoint) returns a JSON STRING that mimics
 * the response shape that the existing AniPaca metadata templates expect
 * from zen-api. $endpoint is the path that used to follow $zpi, e.g.
 *   "/info?id=12345"
 *   "/episodes/12345"
 *   "/search?keyword=naruto&page=1"
 *   "/top-ten"
 *   "/recently-updated"
 *   "/random"
 *   "/genre/action?page=2"
 *   "/character/12"
 *   "/actors/34"
 *   "/filter?type=2&sort=default&page=1"
 *   ""                       (root composite for home.php)
 *
 * The shim is read-only and side-effect free besides a JSON file cache in
 * cache/jikan/. Streaming endpoints are NOT handled here; streaming is
 * handled separately by MegaPlay embeds in src/player/*.php.
 */

if (!defined('JIKAN_BASE')) {
    define('JIKAN_BASE', 'https://api.jikan.moe/v4');
}

// ---------------------------------------------------------------------------
// HTTP + cache helpers
// ---------------------------------------------------------------------------

function jikan_cache_dir() {
    $dir = $_SERVER['DOCUMENT_ROOT'] . '/cache/jikan';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir;
}

function jikan_fetch($path, $ttl = 3600) {
    $url = JIKAN_BASE . $path;
    $cacheKey = md5($url);
    $cacheFile = jikan_cache_dir() . '/' . $cacheKey . '.json';

    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
        $cached = @file_get_contents($cacheFile);
        if ($cached !== false) {
            return json_decode($cached, true);
        }
    }

    $attempt = 0;
    $maxAttempts = 3;
    while ($attempt < $maxAttempts) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_USERAGENT      => 'AniPaca-Jikan-Shim/1.0',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($body !== false && $code >= 200 && $code < 300) {
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                @file_put_contents($cacheFile, $body);
                return $decoded;
            }
        }
        if ($code === 429) {
            usleep(500000 * ($attempt + 1));
        }
        $attempt++;
    }

    // Stale cache fallback.
    if (is_file($cacheFile)) {
        $stale = @file_get_contents($cacheFile);
        if ($stale !== false) {
            return json_decode($stale, true);
        }
    }
    return null;
}

// ---------------------------------------------------------------------------
// Mappers: Jikan anime object → zen-api card / info shapes
// ---------------------------------------------------------------------------

function jikan_card($anime) {
    if (!is_array($anime)) return null;
    $malId = $anime['mal_id'] ?? null;
    if ($malId === null) return null;

    $title  = $anime['title_english'] ?? $anime['title'] ?? '';
    $jname  = $anime['title_japanese'] ?? $anime['title'] ?? $title;
    $poster = $anime['images']['jpg']['large_image_url']
           ?? $anime['images']['jpg']['image_url']
           ?? $anime['images']['webp']['large_image_url']
           ?? '';
    $rating = $anime['rating'] ?? '';
    $isAdult = (stripos($rating, 'Rx') !== false) || !empty($anime['explicit_genres']);
    $eps = $anime['episodes'] ?? null;
    $type = $anime['type'] ?? '';
    $duration = $anime['duration'] ?? '';
    $year = $anime['year'] ?? ($anime['aired']['prop']['from']['year'] ?? null);
    $aired = $anime['aired']['string'] ?? ($year ? (string)$year : '');

    return [
        'id'           => (string)$malId,
        'data_id'      => (string)$malId,
        'title'        => $title !== '' ? $title : $jname,
        'jname'        => $jname,
        'poster'       => $poster,
        'description'  => $anime['synopsis'] ?? '',
        'adultContent' => $isAdult,
        'releaseDate'  => $aired,
        'duration'     => $duration,
        'tvInfo' => [
            'sub'         => $eps,
            'dub'         => null,
            'showType'    => $type,
            'rating'      => $isAdult ? '18+' : null,
            'eps'         => $eps,
            'quality'     => 'HD',
            'duration'    => $duration,
            'releaseDate' => $aired,
            'episodeInfo' => [
                'sub' => $eps,
                'dub' => null,
            ],
        ],
    ];
}

function jikan_cards($list) {
    $out = [];
    if (!is_array($list)) return $out;
    foreach ($list as $a) {
        $card = jikan_card($a);
        if ($card) $out[] = $card;
    }
    return $out;
}

// ---------------------------------------------------------------------------
// Endpoint handlers
// ---------------------------------------------------------------------------

function jikan_handle_info($id) {
    $id = (int)$id;
    if ($id <= 0) {
        // Try to extract trailing integer from a slug like "naruto-12345".
        return ['success' => false];
    }
    $full = jikan_fetch("/anime/{$id}/full", 86400);
    if (!isset($full['data'])) return ['success' => false];
    $a = $full['data'];

    $chars = jikan_fetch("/anime/{$id}/characters", 86400);
    $charactersVoiceActors = [];
    if (isset($chars['data']) && is_array($chars['data'])) {
        $sliced = array_slice($chars['data'], 0, 12);
        foreach ($sliced as $row) {
            $char = $row['character'] ?? [];
            $voices = [];
            foreach (($row['voice_actors'] ?? []) as $va) {
                if (($va['language'] ?? '') !== 'Japanese') continue;
                $person = $va['person'] ?? [];
                $voices[] = [
                    'id'     => (string)($person['mal_id'] ?? ''),
                    'name'   => $person['name'] ?? '',
                    'poster' => $person['images']['jpg']['image_url'] ?? '',
                ];
            }
            $charactersVoiceActors[] = [
                'character' => [
                    'id'     => (string)($char['mal_id'] ?? ''),
                    'name'   => $char['name'] ?? '',
                    'poster' => $char['images']['jpg']['image_url'] ?? '',
                    'cast'   => $row['role'] ?? '',
                ],
                'voiceActors' => $voices,
            ];
        }
    }

    $recs = jikan_fetch("/anime/{$id}/recommendations", 86400);
    $recommended = [];
    if (isset($recs['data']) && is_array($recs['data'])) {
        $sliced = array_slice($recs['data'], 0, 12);
        foreach ($sliced as $r) {
            $card = jikan_card($r['entry'] ?? []);
            if ($card) $recommended[] = $card;
        }
    }

    $genres = [];
    foreach (($a['genres'] ?? []) as $g) {
        if (!empty($g['name'])) $genres[] = $g['name'];
    }
    foreach (($a['themes'] ?? []) as $g) {
        if (!empty($g['name'])) $genres[] = $g['name'];
    }
    foreach (($a['demographics'] ?? []) as $g) {
        if (!empty($g['name'])) $genres[] = $g['name'];
    }

    $studios = [];
    foreach (($a['studios'] ?? []) as $s) {
        if (!empty($s['name'])) $studios[] = $s['name'];
    }
    $producers = [];
    foreach (($a['producers'] ?? []) as $p) {
        if (!empty($p['name'])) $producers[] = $p['name'];
    }

    $rating  = $a['rating'] ?? '';
    $isAdult = (stripos($rating, 'Rx') !== false) || !empty($a['explicit_genres']);
    $eps     = $a['episodes'] ?? null;
    $type    = $a['type'] ?? '';
    $duration = $a['duration'] ?? '';
    $title   = $a['title_english'] ?? $a['title'] ?? '';
    $jname   = $a['title_japanese'] ?? $a['title'] ?? $title;
    $poster  = $a['images']['jpg']['large_image_url']
            ?? $a['images']['jpg']['image_url'] ?? '';

    $relations = [];
    foreach (($a['relations'] ?? []) as $rel) {
        foreach (($rel['entry'] ?? []) as $entry) {
            if (($entry['type'] ?? '') !== 'anime') continue;
            $relations[] = [
                'id'     => (string)($entry['mal_id'] ?? ''),
                'data_id'=> (string)($entry['mal_id'] ?? ''),
                'title'  => $entry['name'] ?? '',
                'jname'  => $entry['name'] ?? '',
                'poster' => '',
                'adultContent' => false,
                'tvInfo' => ['sub' => null, 'dub' => null, 'showType' => $rel['relation'] ?? ''],
            ];
        }
    }

    $trailers = [];
    if (!empty($a['trailer']['embed_url'])) {
        $trailers[] = [
            'thumbnail' => $a['trailer']['images']['large_image_url'] ?? $a['trailer']['images']['image_url'] ?? '',
            'title'     => 'Trailer',
            'source'    => $a['trailer']['embed_url'],
        ];
    }

    $synonyms = [];
    foreach (($a['titles'] ?? []) as $t) {
        if (($t['type'] ?? '') === 'Synonym' && !empty($t['title'])) {
            $synonyms[] = $t['title'];
        }
    }

    $data = [
        'id'           => (string)$id,
        'malId'        => $id,
        'anilistId'    => null,
        'data_id'      => (string)$id,
        'title'        => $title !== '' ? $title : $jname,
        'jname'        => $jname,
        'poster'       => $poster,
        'synonyms'     => implode(', ', $synonyms),
        'adultContent' => $isAdult,
        'animeInfo' => [
            'Overview'  => $a['synopsis'] ?? '',
            'Aired'     => $a['aired']['string'] ?? '',
            'Premiered' => isset($a['season'], $a['year']) ? ucfirst((string)$a['season']) . ' ' . $a['year'] : '',
            'MAL Score' => $a['score'] ?? '',
            'Status'    => $a['status'] ?? '',
            'Genres'    => $genres,
            'Studios'   => implode(', ', $studios),
            'Producers' => $producers,
            'Duration'  => $duration,
            'tvInfo' => [
                'showType' => $type,
                'rating'   => $isAdult ? '18+' : null,
                'sub'      => $eps,
                'dub'      => null,
                'eps'      => $eps,
                'quality'  => 'HD',
                'duration' => $duration,
            ],
        ],
        'charactersVoiceActors' => $charactersVoiceActors,
        'recommended_data'      => $recommended,
        'related_data'          => $relations,
        'trailers'              => $trailers,
    ];

    return [
        'success' => true,
        'results' => [
            'data'    => $data,
            'seasons' => [],
        ],
    ];
}

function jikan_handle_episodes($id) {
    $id = (int)$id;
    if ($id <= 0) return ['success' => false];
    $resp = jikan_fetch("/anime/{$id}/episodes", 21600);
    $episodes = [];
    if (isset($resp['data']) && is_array($resp['data'])) {
        foreach ($resp['data'] as $ep) {
            $episodes[] = [
                'episode_no'     => $ep['mal_id'] ?? 0,
                'id'             => (string)($id . '?ep=' . ($ep['mal_id'] ?? 0)),
                'title'          => $ep['title'] ?? ('Episode ' . ($ep['mal_id'] ?? '')),
                'japanese_title' => $ep['title_japanese'] ?? '',
                'filler'         => !empty($ep['filler']),
            ];
        }
    }
    return [
        'success' => true,
        'results' => ['episodes' => $episodes, 'totalEpisodes' => count($episodes)],
    ];
}

function jikan_handle_search($query) {
    $keyword = isset($query['keyword']) ? trim((string)$query['keyword']) : '';
    $page    = max(1, (int)($query['page'] ?? 1));
    if ($keyword === '') {
        return ['success' => true, 'results' => ['data' => [], 'totalPage' => 0, 'currentPage' => $page, 'hasNextPage' => false]];
    }
    $params = http_build_query([
        'q'     => $keyword,
        'page'  => $page,
        'limit' => 24,
        'sfw'   => 'true',
    ]);
    $resp = jikan_fetch("/anime?{$params}", 1800);
    $data = jikan_cards($resp['data'] ?? []);
    return [
        'success' => true,
        'results' => [
            'data'        => $data,
            'totalPage'   => $resp['pagination']['last_visible_page'] ?? 1,
            'totalPages'  => $resp['pagination']['last_visible_page'] ?? 1,
            'currentPage' => $page,
            'hasNextPage' => !empty($resp['pagination']['has_next_page']),
            'total'       => $resp['pagination']['items']['total'] ?? null,
        ],
    ];
}

function jikan_handle_filter($query) {
    $page = max(1, (int)($query['page'] ?? 1));
    $params = ['page' => $page, 'limit' => 24, 'sfw' => 'true'];

    if (!empty($query['keyword'])) $params['q'] = trim((string)$query['keyword']);

    $typeMap = [1=>'movie',2=>'tv',3=>'ova',4=>'ona',5=>'special',6=>'music'];
    if (!empty($query['type']) && isset($typeMap[(int)$query['type']])) {
        $params['type'] = $typeMap[(int)$query['type']];
    }

    $statusMap = [1=>'complete',2=>'airing',3=>'upcoming'];
    if (!empty($query['status']) && isset($statusMap[(int)$query['status']])) {
        $params['status'] = $statusMap[(int)$query['status']];
    }

    $ratedMap = [1=>'g',2=>'pg',3=>'pg13',4=>'r17',5=>'r',6=>'rx'];
    if (!empty($query['rated']) && isset($ratedMap[(int)$query['rated']])) {
        $params['rating'] = $ratedMap[(int)$query['rated']];
    }

    if (isset($query['score']) && $query['score'] !== '') {
        $params['min_score'] = (int)$query['score'];
    }

    if (!empty($query['season_year'])) {
        $params['start_date'] = ((int)$query['season_year']) . '-01-01';
        $params['end_date']   = ((int)$query['season_year']) . '-12-31';
    }

    $sortMap = [
        'default' => ['order_by' => 'popularity', 'sort' => 'asc'],
        'recently_added' => ['order_by' => 'start_date', 'sort' => 'desc'],
        'recently_updated' => ['order_by' => 'start_date', 'sort' => 'desc'],
        'score' => ['order_by' => 'score', 'sort' => 'desc'],
        'name_az' => ['order_by' => 'title', 'sort' => 'asc'],
        'released_date' => ['order_by' => 'start_date', 'sort' => 'desc'],
        'most_watched' => ['order_by' => 'popularity', 'sort' => 'asc'],
    ];
    $sort = $query['sort'] ?? 'default';
    if (isset($sortMap[$sort])) {
        $params += $sortMap[$sort];
    }

    if (!empty($query['genres'])) {
        $genreIds = jikan_resolve_genre_ids($query['genres']);
        if ($genreIds) $params['genres'] = implode(',', $genreIds);
    }

    $resp = jikan_fetch('/anime?' . http_build_query($params), 1800);
    return [
        'success' => true,
        'results' => [
            'data'        => jikan_cards($resp['data'] ?? []),
            'totalPage'   => $resp['pagination']['last_visible_page'] ?? 1,
            'totalPages'  => $resp['pagination']['last_visible_page'] ?? 1,
            'currentPage' => $page,
            'hasNextPage' => !empty($resp['pagination']['has_next_page']),
            'total'       => $resp['pagination']['items']['total'] ?? null,
        ],
    ];
}

function jikan_resolve_genre_ids($csv) {
    static $map = null;
    if ($map === null) {
        $map = [];
        $resp = jikan_fetch('/genres/anime', 604800);
        foreach (($resp['data'] ?? []) as $g) {
            $slug = strtolower(str_replace([' ', '/'], '-', $g['name']));
            $map[$slug] = $g['mal_id'];
        }
    }
    $ids = [];
    foreach (explode(',', (string)$csv) as $g) {
        $slug = strtolower(trim($g));
        if (isset($map[$slug])) $ids[] = $map[$slug];
    }
    return $ids;
}

function jikan_handle_random() {
    $resp = jikan_fetch('/random/anime', 0);
    $id = $resp['data']['mal_id'] ?? null;
    if (!$id) return ['success' => false];
    return ['success' => true, 'results' => ['id' => (string)$id]];
}

function jikan_handle_category($category, $page) {
    $page = max(1, (int)$page);
    $params = ['page' => $page, 'limit' => 24];
    $url = null;

    switch ($category) {
        case 'top-airing':
            $url = '/top/anime?' . http_build_query($params + ['filter' => 'airing']);
            break;
        case 'most-popular':
            $url = '/top/anime?' . http_build_query($params + ['filter' => 'bypopularity']);
            break;
        case 'most-favorite':
            $url = '/top/anime?' . http_build_query($params + ['filter' => 'favorite']);
            break;
        case 'top-upcoming':
        case 'upcoming':
            $url = '/seasons/upcoming?' . http_build_query($params);
            break;
        case 'completed':
        case 'latest-completed':
            $url = '/anime?' . http_build_query($params + ['status' => 'complete', 'order_by' => 'end_date', 'sort' => 'desc']);
            break;
        case 'recently-updated':
        case 'recently-added':
        case 'latest-episode':
        case 'new-on':
        case 'tv':
            $url = $category === 'tv'
                ? '/anime?' . http_build_query($params + ['type' => 'tv', 'order_by' => 'start_date', 'sort' => 'desc'])
                : '/seasons/now?' . http_build_query($params);
            break;
        case 'movie':
            $url = '/anime?' . http_build_query($params + ['type' => 'movie', 'order_by' => 'start_date', 'sort' => 'desc']);
            break;
        case 'special':
            $url = '/anime?' . http_build_query($params + ['type' => 'special', 'order_by' => 'start_date', 'sort' => 'desc']);
            break;
        case 'ova':
            $url = '/anime?' . http_build_query($params + ['type' => 'ova', 'order_by' => 'start_date', 'sort' => 'desc']);
            break;
        case 'ona':
            $url = '/anime?' . http_build_query($params + ['type' => 'ona', 'order_by' => 'start_date', 'sort' => 'desc']);
            break;
        case 'subbed-anime':
        case 'subbed':
        case 'dubbed-anime':
        case 'dubbed':
            $url = '/anime?' . http_build_query($params + ['order_by' => 'popularity']);
            break;
        default:
            $url = '/anime?' . http_build_query($params + ['q' => str_replace('-', ' ', $category)]);
    }

    $resp = jikan_fetch($url, 1800);
    return [
        'success' => true,
        'results' => [
            'data'        => jikan_cards($resp['data'] ?? []),
            'totalPage'   => $resp['pagination']['last_visible_page'] ?? 1,
            'totalPages'  => $resp['pagination']['last_visible_page'] ?? 1,
            'currentPage' => $page,
            'hasNextPage' => !empty($resp['pagination']['has_next_page']),
        ],
    ];
}

function jikan_handle_az($letter, $page) {
    $page = max(1, (int)$page);
    $params = ['page' => $page, 'limit' => 24, 'order_by' => 'title', 'sort' => 'asc'];
    $letter = trim((string)$letter);
    if ($letter !== '' && $letter !== 'all' && $letter !== 'az-list') {
        if ($letter === '0-9') {
            // Jikan letter filter doesn't support digits; fall back to title sort.
        } else {
            $params['letter'] = strtoupper(substr($letter, 0, 1));
        }
    }
    $resp = jikan_fetch('/anime?' . http_build_query($params), 1800);
    return [
        'success' => true,
        'results' => [
            'data'        => jikan_cards($resp['data'] ?? []),
            'totalPage'   => $resp['pagination']['last_visible_page'] ?? 1,
            'totalPages'  => $resp['pagination']['last_visible_page'] ?? 1,
            'currentPage' => $page,
            'hasNextPage' => !empty($resp['pagination']['has_next_page']),
        ],
    ];
}

function jikan_handle_genre($name, $page) {
    $page = max(1, (int)$page);
    $ids  = jikan_resolve_genre_ids($name);
    $params = ['page' => $page, 'limit' => 24, 'order_by' => 'popularity', 'sort' => 'asc'];
    if ($ids) $params['genres'] = implode(',', $ids);
    $resp = jikan_fetch('/anime?' . http_build_query($params), 1800);
    return [
        'success' => true,
        'results' => [
            'data'        => jikan_cards($resp['data'] ?? []),
            'totalPage'   => $resp['pagination']['last_visible_page'] ?? 1,
            'totalPages'  => $resp['pagination']['last_visible_page'] ?? 1,
            'currentPage' => $page,
            'hasNextPage' => !empty($resp['pagination']['has_next_page']),
        ],
    ];
}

function jikan_resolve_producer_id($slug) {
    static $cache = [];
    $slug = strtolower(trim((string)$slug));
    if (isset($cache[$slug])) return $cache[$slug];
    $q = str_replace('-', ' ', $slug);
    $resp = jikan_fetch('/producers?' . http_build_query(['q' => $q, 'limit' => 5]), 604800);
    $best = null;
    foreach (($resp['data'] ?? []) as $p) {
        foreach (($p['titles'] ?? []) as $t) {
            $candidate = strtolower(str_replace([' ', '.'], ['-', ''], $t['title'] ?? ''));
            if ($candidate === $slug) { $best = $p['mal_id']; break 2; }
        }
        if ($best === null) $best = $p['mal_id'];
    }
    return $cache[$slug] = $best;
}

function jikan_handle_producer($slug, $page) {
    $page = max(1, (int)$page);
    $id   = jikan_resolve_producer_id($slug);
    $params = ['page' => $page, 'limit' => 24, 'order_by' => 'popularity', 'sort' => 'asc'];
    if ($id) $params['producers'] = $id;
    $resp = jikan_fetch('/anime?' . http_build_query($params), 1800);
    return [
        'success' => true,
        'results' => [
            'data'        => jikan_cards($resp['data'] ?? []),
            'totalPage'   => $resp['pagination']['last_visible_page'] ?? 1,
            'totalPages'  => $resp['pagination']['last_visible_page'] ?? 1,
            'currentPage' => $page,
            'hasNextPage' => !empty($resp['pagination']['has_next_page']),
        ],
    ];
}

function jikan_handle_top_ten() {
    $resp = jikan_fetch('/top/anime?' . http_build_query(['limit' => 25]), 3600);
    $list = $resp['data'] ?? [];

    $build = function ($slice) {
        $out = []; $i = 1;
        foreach ($slice as $a) {
            $card = jikan_card($a);
            if (!$card) continue;
            $card['number'] = $i++;
            $out[] = $card;
            if (count($out) >= 10) break;
        }
        return $out;
    };

    return [
        'success' => true,
        'results' => [
            'today' => $build(array_slice($list, 0, 10)),
            'week'  => $build(array_slice($list, 5, 10)),
            'month' => $build(array_slice($list, 10, 10)),
        ],
    ];
}

function jikan_handle_schedule_day($date) {
    $ts = strtotime((string)$date) ?: time();
    $day = strtolower(date('l', $ts));
    $resp = jikan_fetch('/schedules?' . http_build_query(['filter' => $day, 'limit' => 25]), 1800);
    $items = [];
    foreach (($resp['data'] ?? []) as $a) {
        $time = '';
        if (!empty($a['broadcast']['time'])) {
            $time = $a['broadcast']['time'];
        }
        $items[] = [
            'id'         => (string)($a['mal_id'] ?? ''),
            'title'      => $a['title_english'] ?? $a['title'] ?? '',
            'jname'      => $a['title_japanese'] ?? $a['title'] ?? '',
            'time'       => $time,
            'episode_no' => $a['episodes'] ?? '',
        ];
    }
    return ['success' => true, 'results' => $items];
}

function jikan_handle_home() {
    $now  = jikan_fetch('/seasons/now?' . http_build_query(['limit' => 25]), 3600);
    $top  = jikan_fetch('/top/anime?' . http_build_query(['limit' => 25]), 3600);
    $upc  = jikan_fetch('/seasons/upcoming?' . http_build_query(['limit' => 10]), 3600);

    $nowList = $now['data'] ?? [];
    $topList = $top['data'] ?? [];

    $spotlights = [];
    $i = 0;
    foreach (array_slice($topList, 0, 10) as $a) {
        $card = jikan_card($a);
        if (!$card) continue;
        $card['number'] = ++$i;
        $spotlights[] = $card;
    }

    $trending = [];
    $i = 0;
    foreach (array_slice($nowList, 0, 16) as $a) {
        $card = jikan_card($a);
        if (!$card) continue;
        $card['number'] = ++$i;
        $trending[] = $card;
    }

    $latestEpisode = jikan_cards(array_slice($nowList, 0, 12));
    $newOn         = jikan_cards(array_slice($nowList, 12, 12));

    $airing = jikan_fetch('/top/anime?' . http_build_query(['filter' => 'airing', 'limit' => 10]), 3600);
    $popular = jikan_fetch('/top/anime?' . http_build_query(['filter' => 'bypopularity', 'limit' => 10]), 3600);
    $favorite = jikan_fetch('/top/anime?' . http_build_query(['filter' => 'favorite', 'limit' => 10]), 3600);
    $completed = jikan_fetch('/anime?' . http_build_query(['status' => 'complete', 'order_by' => 'end_date', 'sort' => 'desc', 'limit' => 10]), 3600);

    return [
        'success' => true,
        'results' => [
            'spotlights'       => $spotlights,
            'trending'         => $trending,
            'latestEpisode'    => $latestEpisode,
            'newOn'            => $newOn,
            'topUpcoming'      => jikan_cards($upc['data'] ?? []),
            'topAiring'        => jikan_cards($airing['data'] ?? []),
            'mostPopular'      => jikan_cards($popular['data'] ?? []),
            'mostFavorite'     => jikan_cards($favorite['data'] ?? []),
            'latestCompleted'  => jikan_cards($completed['data'] ?? []),
        ],
    ];
}

function jikan_handle_character($id) {
    $id = (int)$id;
    if ($id <= 0) return ['success' => false];
    $resp = jikan_fetch("/characters/{$id}/full", 86400);
    $c = $resp['data'] ?? null;
    if (!$c) return ['success' => false];

    $voiceActors = [];
    foreach (($c['voices'] ?? []) as $v) {
        $p = $v['person'] ?? [];
        $voiceActors[] = [
            'id'       => (string)($p['mal_id'] ?? ''),
            'name'     => $p['name'] ?? '',
            'profile'  => $p['images']['jpg']['image_url'] ?? '',
            'language' => $v['language'] ?? '',
        ];
    }
    $animeography = [];
    foreach (($c['anime'] ?? []) as $row) {
        $a = $row['anime'] ?? [];
        $animeography[] = [
            'id'     => (string)($a['mal_id'] ?? ''),
            'title'  => $a['title'] ?? '',
            'poster' => $a['images']['jpg']['image_url'] ?? '',
            'role'   => $row['role'] ?? '',
            'type'   => 'Anime',
        ];
    }

    $character = [
        'name'         => $c['name'] ?? '',
        'profile'      => $c['images']['jpg']['image_url'] ?? '',
        'japaneseName' => $c['name_kanji'] ?? '',
        'about' => [
            'style'       => nl2br(htmlspecialchars((string)($c['about'] ?? ''), ENT_QUOTES, 'UTF-8')),
            'description' => $c['about'] ?? '',
        ],
        'voiceActors'  => $voiceActors,
        'animeography' => $animeography,
    ];

    return ['success' => true, 'results' => ['data' => [$character]]];
}

function jikan_handle_actor($id) {
    $id = (int)$id;
    if ($id <= 0) return ['success' => false];
    $resp = jikan_fetch("/people/{$id}/full", 86400);
    $p = $resp['data'] ?? null;
    if (!$p) return ['success' => false];

    $roles = [];
    foreach (($p['voices'] ?? []) as $row) {
        $a = $row['anime'] ?? [];
        $c = $row['character'] ?? [];
        $roles[] = [
            'anime' => [
                'id'     => (string)($a['mal_id'] ?? ''),
                'title'  => $a['title'] ?? '',
                'poster' => $a['images']['jpg']['image_url'] ?? '',
                'type'   => 'Anime',
                'year'   => '',
            ],
            'character' => [
                'id'      => (string)($c['mal_id'] ?? ''),
                'name'    => $c['name'] ?? '',
                'profile' => $c['images']['jpg']['image_url'] ?? '',
                'role'    => $row['role'] ?? '',
            ],
        ];
    }

    $actor = [
        'name'         => $p['name'] ?? '',
        'japaneseName' => $p['family_name'] ?? '' . ' ' . ($p['given_name'] ?? ''),
        'profile'      => $p['images']['jpg']['image_url'] ?? '',
        'about'        => [
            'style' => nl2br(htmlspecialchars((string)($p['about'] ?? ''), ENT_QUOTES, 'UTF-8')),
        ],
        'roles' => $roles,
    ];

    return ['success' => true, 'results' => ['data' => [$actor]]];
}

// ---------------------------------------------------------------------------
// Dispatcher
// ---------------------------------------------------------------------------

function zpi_get($endpoint) {
    $endpoint = (string)$endpoint;
    $parts = explode('?', $endpoint, 2);
    $path  = trim($parts[0], '/');
    $query = [];
    if (isset($parts[1])) parse_str($parts[1], $query);

    $segments = $path === '' ? [] : explode('/', $path);
    $first = $segments[0] ?? '';

    $result = null;
    switch ($first) {
        case '':
            $result = jikan_handle_home();
            break;
        case 'info':
            $result = jikan_handle_info($query['id'] ?? '');
            break;
        case 'episodes':
            $result = jikan_handle_episodes($segments[1] ?? '');
            break;
        case 'search':
            $result = jikan_handle_search($query);
            break;
        case 'filter':
            $result = jikan_handle_filter($query);
            break;
        case 'random':
            $result = jikan_handle_random();
            break;
        case 'top-ten':
            $result = jikan_handle_top_ten();
            break;
        case 'schedule':
            // /schedule?date=YYYY-MM-DD or /schedule/{animeId}
            if (isset($segments[1])) {
                $result = ['success' => false]; // per-anime next-episode not available
            } else {
                $result = jikan_handle_schedule_day($query['date'] ?? date('Y-m-d'));
            }
            break;
        case 'az-list':
            $result = jikan_handle_az($segments[1] ?? '', $query['page'] ?? 1);
            break;
        case 'genre':
            $result = jikan_handle_genre($segments[1] ?? '', $query['page'] ?? 1);
            break;
        case 'producer':
            $result = jikan_handle_producer($segments[1] ?? '', $query['page'] ?? 1);
            break;
        case 'character':
            // /character/list/{id} (used in details.php) or /character/{id}
            if (($segments[1] ?? '') === 'list') {
                $result = ['success' => true, 'results' => ['data' => []]];
            } else {
                $result = jikan_handle_character($segments[1] ?? '');
            }
            break;
        case 'actors':
            $result = jikan_handle_actor($segments[1] ?? '');
            break;
        default:
            // Treat as category (top-airing, most-popular, recently-updated, etc.)
            $result = jikan_handle_category($first, $query['page'] ?? 1);
    }

    if ($result === null) $result = ['success' => false];
    return json_encode($result);
}
