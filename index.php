<?php
$directory = dirname(__FILE__);

require $directory."/autoload.php";

$files = scandir($directory);

$parser = new Mni\FrontYAML\Parser();

$pads = array();

$gitDates = explode("\n", shell_exec('git log --pretty="%ai" --name-only *.md'));
$fileDates = array();
$date = null;
foreach($gitDates as $ligne) {
    if(preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}/', $ligne)) {
        $date = new \DateTime($ligne);
        continue;
    }

    if(!preg_match('/\.md/', $ligne)) {
        continue;
    }

    if(isset($fileDates[$ligne])) {
        continue;
    }

    $fileDates[$ligne] = $date;
}

foreach($files as $file) {
    if(!preg_match('/\.md$/', $file)) {
        continue;
    }

    $pad = new stdClass();
    $pad->path = $directory."/".$file;
    $pad->uri_markdown = $file;
    $pad->uri_txt = str_replace(".md", ".txt", $file);
    $pad->uri = str_replace(".md", "", $file);

    try {
    	$document = $parser->parse(file_get_contents($pad->path), false);
	$parameters = $document->getYAML();
    } catch(Exception $e) {
	$parameters = array("title" => "", "url" => "");
    }

    $pad->title = isset($parameters['title']) ? $parameters['title'] : null;
    $pad->url = isset($parameters['url']) ? $parameters['url'] : null;
    $pad->date = $fileDates[$file];


    $pads[$pad->date->format('Y-m-d').$pad->path] = $pad;
}

krsort($pads);

$openPad = null;
if(isset($_GET['pad'])) {
    $openPad = $_GET['pad'];
}

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
        <h2>Historique des pads <small style="font-size:14px;" class="text-muted">Dépôt GIT : <a href="ssh://git@tinc.24eme.fr:pads.git">git@tinc.24eme.fr:pads.git</a></small></h2>
        <table style="margin-top: 20px;" class="table table-bordered table-striped table-sm">
            <thead>
                <tr>
                    <th>Date de modif.</th>
                    <th>Titre</th>
                    <th colspan="4">Contenu</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($pads as $pad): ?>
                    <tr>
                        <td><?php echo $pad->date->format('d/m/Y'); ?></td>
                        <td><?php echo substr($pad->title, 0, 58); ?><?php if(strlen($pad->title) > 58): ?>...<?php endif; ?></td>
                        <td><a class="openModalViewer" data-identifiant="<?php echo $pad->uri; ?>" href="viewer.php?file=<?php echo $pad->uri; ?>">HTML</a></td>
                        <td><a href="<?php echo $pad->uri_markdown; ?>">Markdown</a></td>
                        <td><a href="<?php echo $pad->uri_txt; ?>">Text</a></td>
                        <td><a href="<?php echo $pad->url; ?>"><?php echo $pad->url; ?></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="modalViewer" class="modal">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <div class="ajaxContenu">

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js" integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1" crossorigin="anonymous"></script>
    <script type="text/javascript">
        $(document).ready(function() {

            <?php if($openPad): ?>
                $('#modalViewer .ajaxContenu').load("viewer.php?file=<?php echo $openPad ?>", function() {
                    $('#modalViewer').modal();
                });
            <?php endif; ?>

            $('.openModalViewer').on('click', function(e) {
                history.pushState(null, null, window.location.pathname+"?pad="+$(this).attr('data-identifiant'));
                $('#modalViewer .ajaxContenu').load($(this).attr('href'), function() {
                    $('#modalViewer').modal();
                });
                e.preventDefault();
            });

            $('#modalViewer').on('hide.bs.modal', function (e) {
                history.pushState(null, null, window.location.pathname);
            })
        });
    </script>
</body>
</html>
