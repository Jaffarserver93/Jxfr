<?php

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

require_once($_SERVER['DOCUMENT_ROOT'] . '/_config.php');
session_start();

$characterId = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

$cacheFile = $_SERVER['DOCUMENT_ROOT'] . "/cache/chInfo/character_{$characterId}.json";
$cacheDuration = 3600; 

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheDuration) {
    $response = file_get_contents($cacheFile);
} else {
    $apiUrl = "/character/{$characterId}"; 

    try {
        $response = zpi_get($apiUrl);
        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data && isset($data['success']) && $data['success'] && isset($data['results']['data'][0])) {
                $character = $data['results']['data'][0];
                $name = $character['name'];
                $profile = $character['profile'];
                $japaneseName = $character['japaneseName'];
                $style = $character['about']['style'];
                $description = $character['about']['description'];
                $voiceActors = $character['voiceActors'];
                $animeography = $character['animeography'];
            } else {
                $errorMessage = 'Failed to fetch character data. Please try again later.';
            }
        } else {
            $errorMessage = 'Could not connect to the API.';
        }
    } catch (Exception $e) {
        $errorMessage = 'An error occurred: ' . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html prefix="og: http://ogp.me/ns#" xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
    <title> <?= htmlspecialchars($name) ?> on <?=$websiteTitle?></title>

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="title" content=" <?= htmlspecialchars($name) ?> on <?=$websiteTitle?>">
    <meta name="description" content="Popular Anime in HD with No Ads. Watch anime online">
    <meta name="keywords"
        content="<?=$websiteTitle?>, watch anime online, free anime, anime stream, anime hd, english sub, kissanime, gogoanime, animeultima, 9anime, 123animes, <?=$websiteTitle?>, vidstreaming, gogo-stream, animekisa, zoro.to, gogoanime.run, animefrenzy, animekisa">
    <meta name="charset" content="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <meta name="robots" content="noindex, follow">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta http-equiv="Content-Language" content="en">
    <meta property="og:title" content=" <?= htmlspecialchars($name) ?> on <?=$websiteTitle?>">
    <meta property="og:description"
        content=" <?= htmlspecialchars($description) ?> on <?=$websiteTitle?> in HD with No Ads. Watch anime online">
    <meta property="og:locale" content="en_US">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?=$websiteTitle?>">
    <meta itemprop="image" content="<?=$profile?>">
    <meta property="og:image" content="<?=$profile?>">
    <meta property="og:image:width" content="650">
    <meta property="og:image:height" content="350">
    <meta property="twitter:card" content="summary">
    <meta name="apple-mobile-web-app-status-bar" content="#202125">
    <meta name="theme-color" content="#202125">
        <link rel="stylesheet" href="<?= $websiteUrl ?>/src/assets/css/styles.min.css?v=<?= $version ?>">
    <link rel="apple-touch-icon" href="<?= $websiteUrl ?>/public/logo/favicon.png?v=<?= $version ?>" />
    <link rel="shortcut icon" href="<?= $websiteUrl ?>/public/logo/favicon.png?v=<?= $version ?>" type="image/x-icon" />
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $websiteUrl ?>/public/logo/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $websiteUrl ?>/public/logo/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= $websiteUrl ?>/public/logo/favicon-16x16.png">
    <link rel="mask-icon" href="<?= $websiteUrl ?>/public/logo/safari-pinned-tab.svg" color="#5bbad5">
    <link rel="icon" sizes="192x192" href="<?= $websiteUrl ?>/public/logo/touch-icon-192x192.png?v=<?= $version ?>">
    <link rel="stylesheet" href="<?= $websiteUrl ?>/src/assets/css/new.css?v=<?= $version ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="<?= $websiteUrl ?>/src/assets/css/search.css">
    <script src="<?= $websiteUrl ?>/src/assets/js/search.js"></script>

    <noscript>
        <link rel=stylesheet href=https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css>
        <link rel=stylesheet href=https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.4.1/css/bootstrap.min.css>
    </noscript>
    <script>const cssFiles = ["https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css", "https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.4.1/css/bootstrap.min.css"], firstLink = document.getElementsByTagName("link")[0]; cssFiles.forEach((s => { const t = document.createElement("link"); t.rel = "stylesheet", t.href = `${s}?v=<?= $version ?>`, t.type = "text/css", firstLink.parentNode.insertBefore(t, firstLink) }))</script>
    <link rel=stylesheet href=https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css>
    <link rel=stylesheet href=https://use.fontawesome.com/releases/v5.3.1/css/all.css>
    <link rel=stylesheet href="https://fonts.googleapis.com/icon?family=Material+Icons">
</head>

<body data-page="page_anime">
    <div id="sidebar_menu_bg"></div>
    <div id="wrapper" data-page="page_home">
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/src/component/header.php'; ?>
        <div class="clearfix"></div>
        <div id="main-wrapper" class="layout-page layout-page-actor">
        <div class="actor-cover">
            <div class="anis-cover-wrap">
                <div class="anis-cover" style="background-image: url(<?= htmlspecialchars($profile) ?>)"></div>
            </div>
        </div>
        <!--actor page-->
        <div class="container">
            <div class="actor-page-wrap">
                <div class="avatar avatar-circle"><img src="<?= htmlspecialchars($profile) ?>" alt="<?= htmlspecialchars($name) ?>"></div>
                <div class="apw-detail">
                    <div class="prebreadcrumb">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="/home">Home</a></li>
                                <li class="breadcrumb-item"><a href="#">Characters</a></li>
                                <li class="breadcrumb-item active"><?= htmlspecialchars($name) ?></li>
                            </ol>
                        </nav>
                    </div>
                    <h4 class="name"><?= htmlspecialchars($name) ?></h4>
                    <div class="sub-name"><?= htmlspecialchars($japaneseName) ?></div>
                    
                    <div class="actor-menu">
                        <ul class="nav nav-tabs pre-tabs">
                            <li class="nav-item"><a data-toggle="tab" href="#bio" class="nav-link active" title="">About</a></li>
                            <li class="nav-item"><a data-toggle="tab" href="#animeography" class="nav-link" title="">Animeography</a>
                            </li>
                            <li class="nav-item"><a data-toggle="tab" href="#voiactor" class="nav-link" title="">Voice
                                    actor</a></li>
                        </ul>
                    </div>
                    <div class="tab-content">
                        <div id="bio" class="tab-pane show active">
                            <div class="bio">
                                <?= htmlspecialchars_decode($style) ?>
                            </div>
                        </div>
                        <div id="animeography" class="tab-pane fade">
                            <div class="sub-box sub-box-film">
                                <div class="sub-box-list">
                                    <div class="cbox cbox-list cbox-realtime">
                                        <div class="cbox-content">
                                            <div class="anif-block-ul">
                                                <ul class="ulclear">
                                                    <?php foreach ($animeography as $anime): ?>
                                                    <li>
                                                        <div class="film-poster">
                                                            <a href="/details/<?= htmlspecialchars($anime['id']) ?>">
                                                                <img src="<?= htmlspecialchars($anime['poster']) ?>" class="film-poster-img" alt="<?= htmlspecialchars($anime['title']) ?>">
                                                            </a>
                                                        </div>
                                                        <div class="film-detail">
                                                            <h3 class="film-name">
                                                                <a class="dynamic-name" data-jname="<?= htmlspecialchars($anime['title']) ?>" href="/details/<?= htmlspecialchars($anime['id']) ?>" title="<?= htmlspecialchars($anime['title']) ?>"><?= htmlspecialchars($anime['title']) ?></a>
                                                            </h3>
                                                            <div class="fd-infor">
                                                                <span class="fdi-item"><?= htmlspecialchars($anime['role']) ?></span>
                                                                <span class="dot"></span>
                                                                <span class="fdi-item"><?= htmlspecialchars($anime['type']) ?></span>
                                                            </div>
                                                        </div>
                                                        <div class="film-fav wl-item" data-movieid="<?= htmlspecialchars($anime['id']) ?>" data-anime-name="<?= htmlspecialchars($anime['title']) ?>" data-poster="<?= htmlspecialchars($anime['poster']) ?>" data-anime-type="<?= htmlspecialchars($anime['type']) ?>">
                                                            <i class="fas fa-plus"></i>
                                                        </div>
                                                        <div class="clearfix"></div>
                                                    </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                                <div class="clearfix"></div>
                                            </div>
                                            <div class="clearfix"></div>
                                        </div>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </div>
                        <div id="voiactor" class="tab-pane fade">
                            <div class="sub-box sub-box-actor">
                                <div class="sub-box-list">
                                    <div class="voice-actors">
                                        <?php foreach ($voiceActors as $actor): ?>
                                            <div class="per-info">
                                                <a href="/actors/<?= htmlspecialchars($actor['id']) ?>" class="pi-avatar">
                                                    <img src="<?= htmlspecialchars($actor['profile']) ?>" alt="<?= htmlspecialchars($actor['name']) ?>">
                                                </a>
                                                <div class="pi-detail">
                                                    <h4 class="pi-name"><a href="/actors/<?= htmlspecialchars($actor['id']) ?>"><?= htmlspecialchars($actor['name']) ?></a></h4>
                                                    <span class="pi-cast"><?= htmlspecialchars($actor['language']) ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--/actor page-->
    </div>
        </div>
        <?php include $_SERVER['DOCUMENT_ROOT'] . '/src/component/footer.php'; ?>
        <div id="mask-overlay"></div>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

        <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/js-cookie@rc/dist/js.cookie.min.js"></script>
        <script type="text/javascript" src="<?= $websiteUrl ?>/src/assets/js/app.js"></script>
        <script type="text/javascript" src="<?= $websiteUrl ?>/src/assets/js/comman.js"></script>
        <script type="text/javascript" src="<?= $websiteUrl ?>/src/assets/js/movie.js"></script>
        <link rel="stylesheet" href="<?= $websiteUrl ?>/src/assets/css/jquery-ui.css">
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
        <script type="text/javascript" src="<?= $websiteUrl ?>/src/assets/js/function.js"></script>

         0
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const watchlistItems = document.querySelectorAll('.film-fav');

        watchlistItems.forEach(item => {
            item.addEventListener('click', function() {
                const movieId = this.getAttribute('data-movieid');
                const animeName = this.getAttribute('data-anime-name');
                const poster = this.getAttribute('data-poster');
                const animeType = this.getAttribute('data-anime-type');

                const data = {
                    type: '3',
                    movieId: movieId,
                    animeName: animeName,
                    poster: poster,
                    animeType: animeType
                };

                fetch('<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']; ?>/src/ajax/wl-up.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showToast('Added to watchlist successfully!');
                    } else {
                        showToast('Error: ' + result.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Failed to add to watchlist.');
                });
            });
        });
    });

    // Function to show toast notification
    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'toast show';
        toast.innerText = message;
        document.getElementById('toast-container').appendChild(toast);

        // Remove the toast after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 500); // Match with the CSS transition duration
        }, 3000);
    }
    </script>

    <div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>

    <style>
    .toast {
        background-color: rgba(0, 128, 0, 0.9); /* Green background */
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        margin-bottom: 10px;
        opacity: 0;
        transition: opacity 0.5s ease;
    }

    .toast.show {
        opacity: 1;
    }
    </style>
</body>

</html>

