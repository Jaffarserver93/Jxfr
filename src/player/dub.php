<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/_config.php');

header('X-Frame-Options: SAMEORIGIN');
header("Content-Security-Policy: frame-ancestors 'self';");

$rawId = $_GET['id'] ?? '';
$parsed = explode('?', urldecode($rawId));
$animeId = $parsed[0] ?? '';
parse_str($parsed[1] ?? '', $extraParams);
$epNum = $extraParams['ep'] ?? ($_GET['ep'] ?? null);

function buildMegaplayUrl($animeId, $epNum, $lang) {
    $animeId = trim((string)$animeId);
    $epNum = trim((string)$epNum);

    if (ctype_digit($animeId) && ctype_digit($epNum)) {
        return "https://megaplay.buzz/stream/mal/{$animeId}/{$epNum}/{$lang}";
    }

    // Fallback for legacy episode IDs if present.
    if ($epNum !== '') {
        return "https://megaplay.buzz/stream/s-2/{$epNum}/{$lang}";
    }

    return '';
}

$embedUrl = buildMegaplayUrl($animeId, $epNum, 'dub');

if ($embedUrl === '') {
    http_response_code(400);
    echo 'Invalid stream parameters.';
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        html, body { margin: 0; padding: 0; width: 100%; height: 100%; background: #000; overflow: hidden; }
        iframe { width: 100%; height: 100%; border: 0; }
    </style>
</head>
<body>
    <iframe
        id="megaplay-frame"
        src="<?= htmlspecialchars($embedUrl, ENT_QUOTES, 'UTF-8') ?>"
        allow="autoplay; fullscreen; picture-in-picture"
        allowfullscreen
        referrerpolicy="strict-origin-when-cross-origin"
    ></iframe>

    <script>
        // Relay MegaPlay player events to the parent watch page.
        window.addEventListener('message', function (event) {
            if (event.origin !== 'https://megaplay.buzz') return;
            if (!window.parent || window.parent === window) return;

            const payload = {
                source: 'megaplay',
                language: 'dub',
                animeId: <?= json_encode($animeId) ?>,
                episode: <?= json_encode((string)$epNum) ?>,
                data: event.data
            };

            window.parent.postMessage(payload, window.location.origin);
        });
    </script>
</body>
</html>
