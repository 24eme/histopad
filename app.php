<?php

class Config
{
    public static function getPadsDir() {

        return 'pads';
    }

    public static function getPadsGitDir() {

        return 'pads.git';
    }

    public static function getCacheDir() {

        return 'cache';
    }

    public static function getLastCommit() {
        if(!is_dir(Config::getPadsDir().'/.git')) {

            return 'nogit';
        }

        if(file_exists(Config::getPadsDir().'/.git/refs/heads/master')) {

            return str_replace("\n", "", file_get_contents(Config::getPadsDir().'/.git/refs/heads/master'));
        }

        if(file_exists(Config::getPadsDir().'/.git/info/refs')) {

            return explode("\t", file_get_contents(Config::getPadsDir().'/.git/info/refs'))[0];
        }

        throw new Exception("Ref du dernier commit non trouvé");
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

        return 'http'.($_SERVER['SERVER_PORT'] == 443 ? 's' : '').'://'.$_SERVER['HTTP_HOST'].(isset($_SERVER['CONTEXT_PREFIX']) ? $_SERVER['CONTEXT_PREFIX'] : "").'/';
    }

    public static function getBaseUrlGit() {

        return self::getBaseUrl().self::getPadsGitDir();
    }

    public static function check() {
        if(!is_dir(Config::getPadsDir().'/.git')) {
            touch(Config::getPadsDir().'/.gitkeep');
            shell_exec('cd '.Config::getPadsDir().' && git init 2> /dev/null && git add .gitkeep && git commit -m "Initial commit" && git repack && git pack-refs');
        }

        $errors = array();
        if(!is_writable(Config::getCacheDir())) {
            $errors[] = "Le dossier \"".Config::getCacheDir()."\" n'a pas les droits en écriture.";
        }
        if(!is_readable(Config::getCacheDir())) {
            $errors[] = "Le dossier \"".Config::getCacheDir()."\" n'a pas les droits en lecture.";
        }
        if(!is_readable(Config::getPadsDir())) {
            $errors[] = "Le dossier \"".Config::getCacheDir()."\" n'a pas les droits en lecture.";
        }
        if(!is_writable(Config::getPadsDir())) {
            $errors[] = "Le dossier \"".Config::getPadsDir()."\" n'a pas les droits en écriture.";
        }
        if(!is_readable(Config::getPadsDir().'/.git')) {
            $errors[] = "Le dossier \"".Config::getPadsDir()."/.git\" n'a pas les droits en lecture.";
        }
        if(!is_writable(Config::getPadsDir().'/.git')) {
            $errors[] = "Le dossier \"".Config::getPadsDir()."/.git\" n'a pas les droits en écriture.";
        }
        if(!is_readable(Config::getPadsDir().'/.git/HEAD')) {
            $errors[] = "Tous les fichiers du dossier \"".Config::getPadsDir()."/.git\" n'ont pas les droits en lecture.";
        }
        if(!is_writable(Config::getPadsDir().'/.git/HEAD')) {
            $errors[] = "Tous les fichiers du dossier \"".Config::getPadsDir()."/.git\" n'ont pas les droits en écriture.";
        }
        if(!is_writable(Config::getQueueDir())) {
            $errors[] = "Le dossier \"".Config::getQueueDir()."\" n'a pas les droits en écriture.";
        }
        if(!is_readable(Config::getQueueDir())) {
            $errors[] = "Le dossier \"".Config::getQueueDir()."\" n'a pas les droits en lecture.";
        }

        return $errors;
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
        $id = preg_replace('#^.+\/#', '', $url);
        $file = Config::getPadsDir().'/'.$id;

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

        if(strpos($txtContent, "this pad has been deleted") !== false) {
            echo "Le texte du pad a été effacé\n";
            unlink($queueFile);
            return;
        }

        if(!trim($txtContent)) {
            echo "Le contenu du pad est vide\n";
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

        echo shell_exec('cd '.Config::getPadsDir().' && git add '.escapeshellarg($id).'.*');
        echo shell_exec('cd '.Config::getPadsDir().' && git commit -m "Archivage du pad : '.escapeshellarg($url).'" && git repack && git pack-refs');

        unlink($queueFile);

        echo "Archivage du pad ".$url."\n";

        $pad = PadClient::find($id);
        $pad->planNextUpdate();

        if($pad->getDateNextArchivage()) {
            echo "Nouvelle archivage du pad ".$pad->getUrl()." planifié le ".$pad->getDateNextArchivage()->format('Y-m-d H:i:s')."\n";
        }
    }

    public static function run() {
        if(file_exists(Config::getQueueLockFile()) && (time() - filemtime(Config::getQueueLockFile())) < 300) {
            echo "Run is lock. Delete \"".Config::getQueueLockFile()."\" file to unlock it.\n";
            return;
        }

        touch(Config::getQueueLockFile());

        foreach(glob(Config::getQueueDir().'/*.url') as $queueFile) {
            if(filemtime($queueFile) > time()) {
                continue;
            }
            Archive::commit($queueFile);
            touch(Config::getQueueLockFile());
        }

        unlink(Config::getQueueLockFile());
    }
}

class Pad
{
    protected $id = null;
    protected $filename = null;
    protected $date = null;
    protected $url = null;
    protected $title = null;

    public function __construct($id) {
        $this->id = $id;
        $this->date = PadClient::getDatesCommit()[$this->id];
        $this->url = str_replace("\n", "", file_get_contents($this->getFile('url')));
        $fp = @fopen($this->getFile('txt'), 'r');
        $this->title = fgets($fp);
        fclose($fp);
    }

    public function getId() {

        return $this->id;
    }

    public function getFile($extension) {

        return Config::getPadsDir().'/'.$this->getId().'.'.$extension;
    }

    public function getQueueFile() {

        return Config::getQueueDir().'/'.$this->getId().'.url';
    }

    public function getDate() {

        return $this->date;
    }

    public function getTitle() {

        return $this->title;
    }

    public function getUrl() {

        return $this->url;
    }

    public function getContent() {

        return file_get_contents($this->getFile('txt'));
    }

    public function planNextUpdate() {
        $hourDiff = floor((time() - $this->getDate()->format('U')) / 3600) + 1;
        Archive::add($this->getUrl(), time() + $hourDiff * 3600);
    }

    public function getDateNextArchivage() {
        if(!file_exists($this->getQueueFile())) {

            return null;
        }

        return new \DateTime(date('Y-m-d H:i:s', filemtime($this->getQueueFile())));
    }
}

class PadClient
{
    public static $datesCommit = null;

    public static function search($q = null) {
        $pads = self::getAll();
        if($q) {
            foreach($pads as $k => $pad) {
                if($q && strpos(strtolower($pad->getTitle()), strtolower($q)) === false) {
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

            if(isset($fileDates[str_replace('.txt', '', $ligne)])) {
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
        $pads = [];

        foreach(array_keys(self::getDatesCommit()) as $id) {
            $pad = new Pad($id);
            $pads[$pad->getId()] = $pad;
        }

        uasort($pads, function($p1, $p2) { return $p1->getDate() < $p2->getDate(); });

        $otherCacheFiles = glob(Config::getCacheDir().'/*.php.serialize');
        file_put_contents($cachePadsFile, serialize($pads));
        array_map('unlink', $otherCacheFiles);

        return $pads;
    }

    public static function find($id) {

        return PadClient::getAll()[$id];
    }

    public static function extractUrls($content) {
        preg_match_all('#'.Config::getRegexpPad().'#', $content, $urls);

        return array_values(array_unique($urls[0]));
    }
}
