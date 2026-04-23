<?php 
require_once($_SERVER['DOCUMENT_ROOT'] . '/_config.php');
$getAnime = json_decode(zpi_get('/random'), true);
if (isset($getAnime['success']) && $getAnime['success'] === true && isset($getAnime['results'])) {
    $animeId = $getAnime['results']['id'];
    $newURL = "$websiteUrl/details/$animeId";
    header('Location: '.$newURL);
    exit;
} else {  
    header('Location: '.$websiteUrl.'/404');
    exit;
}
?>