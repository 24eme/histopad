<?php
$directory = dirname(__FILE__);

spl_autoload_register(function ($class) use ($directory) {
    //echo $class."\n";
    /*$nameSpace = explode('\\', $class);
    foreach($nameSpace as $key =>  $value){


    }*/
    //$class = implode('/', $nameSpace);
    //echo str_replace('Mni\\FrontYAML\\', $directory."/vendor/FrontYAML/src/", $class ).'.php';
    $pathClass = $class;
    $pathClass = str_replace('Mni\\FrontYAML\\', $directory."/vendor/FrontYAML/src/", $pathClass);
    $pathClass = str_replace('Symfony\\Component\\Yaml\\', $directory."/vendor/yaml/", $pathClass);
    $pathClass = str_replace('Michelf\\', $directory."/vendor/php-markdown/Michelf/", $pathClass);

    require str_replace('\\', '/', $pathClass).'.php';
});

require $directory."/vendor/Parsedown.php";

$files = scandir($directory);

$parser = new Mni\FrontYAML\Parser();

$pads = array();

foreach($files as $file) {
    if(!preg_match('/\.md$/', $file)) {
        continue;
    }

    $pad = new stdClass();
    $pad->path = $directory."/".$file;
    $pad->uri_markdown = $file;
    $pad->uri_txt = str_replace(".md", ".txt", $file);
    $document = $parser->parse(file_get_contents($pad->path));
    $parameters = $document->getYAML();
    $pad->title = isset($parameters['title']) ? $parameters['title'] : null;
    $pad->url = isset($parameters['url']) ? $parameters['url'] : null;
    $pad->date = new \DateTime(exec('git log -n 1 --pretty="%ai" '.$file));


    $pads[$pad->date->format('Y-m-d').$pad->path] = $pad;
}

krsort($pads);

?>
<!DOCTYPE html>
<html lang="fr_FR">
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
    </head>
    <body>
        <div class="container" style="margin-top: 20px;">
            <h2>Historique des pads</h2>
            <table style="margin-top: 20px;" class="table table-bordered table-striped table-sm">
                <thead>
                    <tr>
                        <th>Date de modification</th>
                        <th>Titre</th>
                        <th colspan="3">Contenu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pads as $pad): ?>
                        <tr>
                            <td><?php echo $pad->date->format('d/m/Y'); ?></td>
                            <td><?php echo $pad->title; ?></td>
                            <td><a href="<?php echo $pad->uri_markdown; ?>">Markdown</a></td>
                            <td><a href="<?php echo $pad->uri_txt; ?>">Text</a></td>
                            <td><a href="<?php echo $pad->url; ?>"><?php echo $pad->url; ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Optional JavaScript -->
        <!-- jQuery first, then Popper.js, then Bootstrap JS -->
        <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js" integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1" crossorigin="anonymous"></script>
  </body>
</html>
