<?php

$config=[];
$config['pads_folder'] = 'pads';

require dirname(__FILE__)."/autoload.php";

function getPadFromFile($file, $loadContent) {
    $parser = new Mni\FrontYAML\Parser();
    $pad = new stdClass();
    $pad->path = $file;
    $pad->uri_markdown = $file;
    $pad->uri_txt = str_replace(".md", ".txt", $file);
    $pad->uri = str_replace(".md", "", $file);
    try {
        $document = $parser->parse(file_get_contents($pad->path), $loadContent);
    $parameters = $document->getYAML();
    } catch(Exception $e) {
    $parameters = array("title" => "", "url" => "");
    }

    if($loadContent) {
        $pad->content = $document->getContent();
    }

    $pad->title = isset($parameters['title']) ? $parameters['title'] : null;
    $pad->url = isset($parameters['url']) ? $parameters['url'] : null;

    return $pad;
}
