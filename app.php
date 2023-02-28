<?php

class Config
{
    public static $config = ['pads_folder' => 'pads'];

    public static function getCacheDir() {

        return 'cache';
    }

    public static function getLastCommit() {

        return str_replace("\n", "", file_get_contents(Config::$config['pads_folder'].'/.git/ORIG_HEAD'));
    }

    public static function getCachePadsFile() {

        return self::getCacheDir().'/'.self::getLastCommit().'.php.serialize';
    }
}

class Pad
{
    public $uri = null;
    public $date = null;
    public $url = null;
    public $title = null;
    public $content = null;

    public function __construct($uri, $date) {
        $this->uri = $uri;
        $this->date = $date;
        $this->url = file_get_contents($this->uri.'.url');
        $fp = @fopen($this->uri.'.txt', 'r');
        $this->title = fgets($fp);
        fclose($fp);
        $this->content = null;
    }

    public function getContent() {

        return nl2br(file_get_contents($this->uri.'.txt'));
    }
}

class PadClient
{
    public static function getAll($q = null) {
        $cachePadsFile = Config::getCachePadsFile();

        if(file_exists($cachePadsFile)) {

            return unserialize(file_get_contents($cachePadsFile));
        }

        $gitDates = explode("\n", shell_exec('cd '.Config::$config['pads_folder'].' && git log --pretty="%ai" --name-only'));
        $fileDates = array();
        $date = null;

        foreach($gitDates as $ligne) {
            if(preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}/', $ligne)) {
                $date = new \DateTime($ligne);
                continue;
            }

            if(!preg_match('/\.txt/', $ligne)) {
                continue;
            }

            if(isset($fileDates[$ligne])) {
                continue;
            }

            if(!file_exists(Config::$config['pads_folder']."/".$ligne)) {
                continue;
            }

            $fileDates[str_replace('.txt', '', $ligne)] = $date;
        }

        arsort($fileDates);

        foreach($fileDates as $file => $date) {
            $pad = new Pad(Config::$config['pads_folder']."/".$file, $date);
            if($q && strpos(strtolower($pad->title), strtolower($q)) === false) {
                continue;
            }
            $pads[$pad->uri] = $pad;
        }

        uasort($pads, function($p1, $p2) { return $p1->date < $p2->date; });

        file_put_contents($cachePadsFile, serialize($pads));

        return $pads;
    }

    public static function find($uri) {

        return PadClient::getAll()[$uri];
    }
}
