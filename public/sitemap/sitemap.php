<?php
function fetch_links($url, &$visited) {
    $links = [];
    $html = file_get_contents($url);
    if ($html === false) {
        return $links;
    }

    $dom = new DOMDocument;
    @$dom->loadHTML($html);
    $anchorTags = $dom->getElementsByTagName('a');

    foreach ($anchorTags as $tag) {
        $link = $tag->getAttribute('href');
        $link = resolve_url($url, $link);
        if ($link && !isset($visited[$link])) {
            $visited[$link] = true;
            $links[] = $link;
        }
    }
    return $links;
}

function resolve_url($base, $relative) {
    if (parse_url($relative, PHP_URL_SCHEME) != '') {
        return $relative;
    }

    if (!empty($relative) && $relative[0] == '/') {
        $baseParts = parse_url($base);
        return $baseParts['scheme'] . '://' . $baseParts['host'] . $relative;
    }

    $baseParts = parse_url($base);
    $basePath = isset($baseParts['path']) ? rtrim(dirname($baseParts['path']), '/') . '/' : '/';
    $resolved = $baseParts['scheme'] . '://' . $baseParts['host'] . $basePath . $relative;
    $resolved = preg_replace('#/[^/]+/\.\./#', '/', $resolved);
    $resolved = preg_replace('#(?<!:)//+#', '/', $resolved);

    return $resolved;
}

function generate_sitemap($urls) {
    $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

    foreach ($urls as $url) {
        $cleaned_url = clean_url($url);
        $sitemap .= '  <url>' . PHP_EOL;
        $sitemap .= '    <loc>' . htmlspecialchars($cleaned_url) . '</loc>' . PHP_EOL;
        $sitemap .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . PHP_EOL;
        $sitemap .= '    <changefreq>weekly</changefreq>' . PHP_EOL;
        $sitemap .= '    <priority>0.8</priority>' . PHP_EOL;
        $sitemap .= '  </url>' . PHP_EOL;
    }

    $sitemap .= '</urlset>';
    return $sitemap;
}

function get_base_url($path = '') {
    $host = $_SERVER['HTTP_HOST'];
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . $host . $path;
}

function clean_url($url) {
    $parsed = parse_url($url);
    if (!isset($parsed['host'])) return $url;

    $domain = $parsed['host'];
    $scheme = isset($parsed['scheme']) ? $parsed['scheme'] : 'http';
    $path = isset($parsed['path']) ? $parsed['path'] : '';
    $path = preg_replace([
        '#^/' . preg_quote($domain) . '#i',
        '#/' . preg_quote($domain) . '/#i',
        '#/' . preg_quote($domain) . '$#i'
    ], '/', $path);

    $clean_url = $scheme . '://' . $domain;
    if (!empty($path)) {
        $path = '/' . ltrim($path, '/');
        $clean_url .= $path;
    }
    $clean_url = preg_replace('#(?<!:)//+#', '/', $clean_url);
    
    return $clean_url;
}

$base_url = get_base_url('/');
$visited = [];
$toVisit = [$base_url];
$allLinks = [];

while (!empty($toVisit)) {
    $url = array_shift($toVisit);
    if (!isset($visited[$url])) {
        $visited[$url] = true;
        $links = fetch_links($url, $visited);
        $allLinks = array_merge($allLinks, $links);
        $toVisit = array_merge($toVisit, $links);
    }
}

$sitemap = generate_sitemap(array_unique($allLinks));
file_put_contents('sitemap.xml', $sitemap);

$base_url = get_base_url('/home');
$visited = [];
$toVisit = [$base_url];
$allLinks = [];

while (!empty($toVisit)) {
    $url = array_shift($toVisit);
    if (!isset($visited[$url])) {
        $visited[$url] = true;
        $links = fetch_links($url, $visited);
        $allLinks = array_merge($allLinks, $links);
        $toVisit = array_merge($toVisit, $links);
    }
}

$sitemap = generate_sitemap(array_unique($allLinks));
file_put_contents('sitemaphome.xml', $sitemap);

$paths = [
    '/anime/most-popular' => 'sitemappopular.xml',
    '/anime/movie' => 'sitemapmovie.xml',
    '/anime/top-airing' => 'sitemapairing.xml',
    '/anime/most-favorite' => 'sitemapfav.xml',
    '/anime/completed' => 'sitemapcompleted.xml'
];

foreach ($paths as $path => $filename) {
    $base_url = get_base_url($path);
    $visited = [];
    $toVisit = [$base_url];
    $allLinks = [];

    while (!empty($toVisit)) {
        $url = array_shift($toVisit);
        if (!isset($visited[$url])) {
            $visited[$url] = true;
            $links = fetch_links($url, $visited);
            $allLinks = array_merge($allLinks, $links);
            $toVisit = array_merge($toVisit, $links);
        }
    }

    $sitemap = generate_sitemap(array_unique($allLinks));
    file_put_contents($filename, $sitemap);
}
