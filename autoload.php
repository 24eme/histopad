<?php
$directory = dirname(__FILE__);

spl_autoload_register(function ($class) use ($directory) {
    $pathClass = $class;
    $pathClass = str_replace('Mni\\FrontYAML\\', $directory."/vendor/FrontYAML/src/", $pathClass);
    $pathClass = str_replace('Symfony\\Component\\Yaml\\', $directory."/vendor/yaml/", $pathClass);
    $pathClass = str_replace('Michelf\\', $directory."/vendor/php-markdown/Michelf/", $pathClass);

    require str_replace('\\', '/', $pathClass).'.php';
});

require $directory."/vendor/Parsedown.php";
