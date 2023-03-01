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

    public static function getQueueDir() {

        return 'queue';
    }

    public static function getQueueLockFile() {

        return self::getQueueDir().'/.lock';
    }

    public static function getRegexpPad() {

        return "https://[^/]*[/pad]*/p/[éàèêùûâ;a-zA-Z0-9_,\"-]+";
    }
}

class Archive
{
    public static function add($url) {
        $fileName = preg_replace('#^.+\/#', '', $url);
        file_put_contents(Config::getQueueDir().'/'.$fileName.'.url' ,$url);
    }

    public static function commit($queueFile) {
        $url = file_get_contents($queueFile);
        $fileName = preg_replace('#^.+\/#', '', $url);

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

        file_put_contents(Config::$config['pads_folder'].'/'.$fileName.'.txt' ,$txtContent);
        file_put_contents(Config::$config['pads_folder'].'/'.$fileName.'.url' ,$url);

        $mdContent = @file_get_contents($url.'/export/markdown');
        if(strpos($http_response_header[0], '200 OK') !== false) {
            file_put_contents(Config::$config['pads_folder'].'/'.$fileName.'.md' ,$mdContent);
        }

        $htmlContent = @file_get_contents($url.'/export/html');
        if(strpos($http_response_header[0], '200 OK') !== false) {
            file_put_contents(Config::$config['pads_folder'].'/'.$fileName.'.html' ,$htmlContent);
        }

        $etherpadContent = @file_get_contents($url.'/export/etherpad');
        if(strpos($http_response_header[0], '200 OK') !== false) {
            file_put_contents(Config::$config['pads_folder'].'/'.$fileName.'.etherpad' ,$etherpadContent);
        }

        echo shell_exec('cd '.Config::$config['pads_folder'].' && git add '.escapeshellarg($fileName).'.*');
        echo shell_exec('cd '.Config::$config['pads_folder'].' && git commit -m "Archivage du pad : '.escapeshellarg($url).'"');

        unlink($queueFile);
    }

    public static function run() {
        if(file_exists(Config::getQueueLockFile()) && (time() - filemtime(Config::getQueueLockFile())) < 300) {
            echo "Run is lock. Delete \"".Config::getQueueLockFile()."\" file to unlock it.\n";
            return;
        }

        touch(Config::getQueueLockFile());

        if(!is_dir(Config::$config['pads_folder'].'/.git')) {
            shell_exec('cd '.Config::$config['pads_folder'].' && git init 2> /dev/null');
        }

        shell_exec('cd '.Config::$config['pads_folder'].' && git pull -r 2> /dev/null');

        foreach(glob(Config::getQueueDir().'/*.url') as $queueFile) {
            Archive::commit($queueFile);
            touch(Config::getQueueLockFile());
        }

        shell_exec('cd '.Config::$config['pads_folder'].' && git push 2> /dev/null');

        unlink(Config::getQueueLockFile());
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

    public static function getAll() {
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
