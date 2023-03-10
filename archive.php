<?php

require dirname(__FILE__)."/app.php";

$urls = array();

$stdin = fopen("php://stdin", "r");
stream_set_blocking($stdin, false);
while($contentStdin = fgets($stdin)) {
    $urls = array_merge($urls, PadClient::extractUrls($contentStdin));
}
fclose($stdin);

$bodyRequest = file_get_contents('php://input');

if($bodyRequest) {
    $urls = array_merge($urls, PadClient::extractUrls($bodyRequest));
}

if(isset($argv)) {
    $urls = array_merge($urls, PadClient::extractUrls(implode(" ", $argv)));
}

if(isset($_GET['url'])) {
    $urls = array_merge($urls, PadClient::extractUrls($_GET['url']));
}

if(isset($_POST['url'])) {
    $urls = array_merge($urls, PadClient::extractUrls($_GET['url']));
}

$urls = array_values(array_unique($urls));

foreach($urls as $url) {
    Archive::add($url);
    echo "$url in queue to be archived\n";
}

if(isset($_GET['run']) && $_GET['run']) {
    Archive::run();
}

if(isset($_GET['url'])) {

    header('Location: '.Config::getBaseUrl());
}
