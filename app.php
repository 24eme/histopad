<?php

class Config
{
    public static function getPadsDir() {

        return 'pads';
    }

    public static function getCacheDir() {

        return 'cache';
    }

    public static function getLastCommit() {
        if(file_exists(Config::getPadsDir().'/.git/refs/heads/master')) {

            return str_replace("\n", "", file_get_contents(Config::getPadsDir().'/.git/refs/heads/master'));
        }

        if(file_exists(Config::getPadsDir().'/.git/info/refs')) {

            return explode("\t", file_get_contents(Config::getPadsDir().'/.git/info/refs'))[0];
        }

        throw new Excption("Ref du dernier commit non trouvé");
    }

    public static function getCachePadsFile() {

        return self::getCacheDir().'/'.self::getLastCommit().'.php.serialize';
    }

    public static function getQueueDir() {

        return 'queue';
    }

    public static function getQueueLockFile() {

        return self::getQueueDir().'/.lock';
    }

    public static function getRegexpPad() {

        return "https://[^/]*[/pad]*/p/[éàèêùûâ;a-zA-Z0-9_,\"-]+";
    }

    public static function getBaseUrl() {

        return 'http'.($_SERVER['SERVER_PORT'] == 443 ? 's' : '').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }

    public static function getBaseUrlGit() {

        return self::getBaseUrl().self::getPadsDir().'.git';
    }
}

class Archive
{
    public static function add($url, $time = null) {
        $file = Config::getQueueDir().'/'.preg_replace('#^.+\/#', '', $url).'.url';
        file_put_contents($file, $url);
        if($time) {
            touch($file, $time);
        }
    }

    public static function commit($queueFile) {
        $url = file_get_contents($queueFile);
        $fileName = preg_replace('#^.+\/#', '', $url);
        $file = Config::getPadsDir().'/'.$fileName;

        $txtContent = @file_get_contents($url.'/export/txt');

        if(strpos($http_response_header[0], '200 OK') === false) {
            echo "Le texte du pad $url n'a pu être récupéré : ".$http_response_header[0]."\n";
            unlink($queueFile);
            return;
        }

        if(strpos($txtContent, "Le contenu de ce pad a été effacé") !== false) {
            echo "Le texte du pad a été effacé\n";
            unlink($queueFile);
            return;
        }

        file_put_contents($file.'.txt', $txtContent);
        file_put_contents($file.'.url', $url);

        $mdContent = @file_get_contents($url.'/export/markdown');
        if(strpos($http_response_header[0], '200 OK') !== false) {
            file_put_contents($file.'.md', $mdContent);
        }

        $htmlContent = @file_get_contents($url.'/export/html');
        if(strpos($http_response_header[0], '200 OK') !== false) {
            file_put_contents($file.'.html', $htmlContent);
        }

        $etherpadContent = @file_get_contents($url.'/export/etherpad');
        if(strpos($http_response_header[0], '200 OK') !== false) {
            file_put_contents($file.'.etherpad', $etherpadContent);
        }

        echo shell_exec('cd '.Config::getPadsDir().' && git add '.escapeshellarg($fileName).'.*');
        echo shell_exec('cd '.Config::getPadsDir().' && git commit -m "Archivage du pad : '.escapeshellarg($url).'" && git gc && git pack-refs && git checkout master');

        unlink($queueFile);

        echo "Archivage du pad ".$url."\n";

        $pad = PadClient::find($file);
        $pad->planNextUpdate();

        if($pad->getDateNextArchivage()) {
            echo "Nouvelle archivage du pad ".$pad->url." planifié le ".$pad->getDateNextArchivage()->format('Y-m-d H:i:s')."\n";
        }
    }

    public static function run() {
        if(file_exists(Config::getQueueLockFile()) && (time() - filemtime(Config::getQueueLockFile())) < 300) {
            echo "Run is lock. Delete \"".Config::getQueueLockFile()."\" file to unlock it.\n";
            return;
        }

        touch(Config::getQueueLockFile());

        if(!is_dir(Config::getPadsDir().'/.git')) {
            touch(Config::getPadsDir().'/.gitignore');
            shell_exec('cd '.Config::getPadsDir().' && git init 2> /dev/null && git add . && git commit -m "Initial commit" && git gc && git pack-refs');
        }

        shell_exec('cd '.Config::getPadsDir().' && git pull -r 2> /dev/null');

        foreach(glob(Config::getQueueDir().'/*.url') as $queueFile) {
            if(filemtime($queueFile) > time()) {
                continue;
            }
            Archive::commit($queueFile);
            touch(Config::getQueueLockFile());
        }

        shell_exec('cd '.Config::getPadsDir().' && git push 2> /dev/null');

        unlink(Config::getQueueLockFile());
    }
}

class Pad
{
    public $uri = null;
    public $filename = null;
    public $date = null;
    public $url = null;
    public $title = null;
    public $content = null;

    public function __construct($uri) {
        $this->uri = $uri;
        $this->filename = str_replace(Config::getPadsDir().'/', '', $uri);
        $this->date = PadClient::getDatesCommit()[$this->filename];
        $this->url = file_get_contents($this->uri.'.url');
        $fp = @fopen($this->uri.'.txt', 'r');
        $this->title = fgets($fp);
        fclose($fp);
        $this->content = null;
    }

    public function getContent() {

        return file_get_contents($this->uri.'.txt');
    }

    public function planNextUpdate() {
        $hourDiff = floor((time() - $this->date->format('U')) / 3600) + 1;
        Archive::add($this->url, time() + $hourDiff * 3600);
    }

    public function getDateNextArchivage() {
        if(!file_exists(Config::getQueueDir().'/'.$this->filename.'.url')) {

            return null;
        }

        return new \DateTime(date('Y-m-d H:i:s', filemtime(Config::getQueueDir().'/'.$this->filename.'.url')));
    }
}

class PadClient
{
    public static $datesCommit = null;

    public static function search($q = null) {
        $pads = self::getAll();
        if($q) {
            foreach($pads as $k => $pad) {
                if($q && strpos(strtolower($pad->title), strtolower($q)) === false) {
                    unset($pads[$k]);
                }
            }
        }

        return $pads;
    }

    public static function getDatesCommit() {
        if(!is_null(self::$datesCommit)) {

            return self::$datesCommit;
        }

        $gitDates = explode("\n", shell_exec('cd '.Config::getPadsDir().' && git log --pretty="%ai" --name-only'));
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

            if(!file_exists(Config::getPadsDir()."/".$ligne)) {
                continue;
            }

            $fileDates[str_replace('.txt', '', $ligne)] = $date;
        }

        arsort($fileDates);

        self::$datesCommit = $fileDates;

        return self::$datesCommit;
    }

    public static function getAll() {
        $cachePadsFile = Config::getCachePadsFile();

        if(file_exists($cachePadsFile)) {

            return unserialize(file_get_contents($cachePadsFile));
        }

        self::$datesCommit = null;

        foreach(array_keys(self::getDatesCommit()) as $file) {
            $pad = new Pad(Config::getPadsDir()."/".$file);
            $pads[$pad->uri] = $pad;
        }

        uasort($pads, function($p1, $p2) { return $p1->date < $p2->date; });

        file_put_contents($cachePadsFile, serialize($pads));

        return $pads;
    }

    public static function find($uri) {

        return PadClient::getAll()[$uri];
    }

    public static function extractUrls($content) {
        preg_match_all('#'.Config::getRegexpPad().'#', $content, $urls);

        return array_values(array_unique($urls[0]));
    }
}
