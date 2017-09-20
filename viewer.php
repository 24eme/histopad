<?php
$directory = dirname(__FILE__);

require $directory."/autoload.php";

$parser = new Mni\FrontYAML\Parser();

$file = $_GET['file'].".md";

$document = $parser->parse(file_get_contents($file));


?>
<?php echo $document->getContent(); ?>
