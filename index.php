<?php

require dirname(__FILE__)."/app.php";

$pads = array();

$limit = 100;

if(isset($_GET['limit']) && $_GET['limit'] == -1) {
     $limit = false;
} elseif(isset($_GET['limit']) && preg_match('/^[0-9]+$/', $_GET['limit'])) {
     $limit = $_GET['limit'];
}

$q = (isset($_GET['q']) && trim($_GET['q'])) ? $_GET['q'] : null;

$gitDates = explode("\n", shell_exec('git log --pretty="%ai" --name-only'));
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

arsort($fileDates);

$i = 0;

foreach($fileDates as $file => $date) {
    if(!preg_match('/\.md$/', $file)) {
        continue;
    }

    $pad = getPadFromFile($file, false);
    $pad->date = $date;

    if($q && strpos(strtolower($pad->title), strtolower($q)) === false) {
        continue;
    }

    $pads[$pad->date->format('Y-m-d').$pad->path] = $pad;
    $i++;
    if($limit !== false && $i >= $limit) {
	break;
    }
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
</head>
<body>
    <div class="container pt-3">
        <h2>Historique des pads <small style="font-size:14px;" class="text-muted">Dépôt GIT : <a href="ssh://git@tinc.24eme.fr:pads.git">git@tinc.24eme.fr:pads.git</a></small></h2>

        <form method="GET" class="mt-3">
            <div class="input-group">
                <input type="search" autofocus="autofocus" name="q" placeholder="Recherche sur le titre" class="form-control" value="<?php echo $q ?>" autocomplete="off" />
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button">Rechercher</button>
                </div>
            </div>
        </form>
        <table class="table table-bordered table-striped table-sm mt-3">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Titre</th>
                    <th>Pad</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($pads as $pad): ?>
                    <tr>
                        <td><?php echo $pad->date->format('d/m/Y'); ?></td>
                        <td><?php echo substr($pad->title, 0, 58); ?><?php if(strlen($pad->title) > 58): ?>...<?php endif; ?></td>
                        <td><a href="<?php echo $pad->url; ?>"><?php echo $pad->url; ?></a></td>
                        <td class="text-center"><a class="openModalViewer" data-identifiant="<?php echo $pad->uri; ?>" href="viewer.php?file=<?php echo $pad->uri; ?>">Voir</></td>
                    </tr>
                <?php endforeach; ?>
		<?php if($limit !== false): ?>
			<tr><td colspan="6"><center><a href="?limit=<?php echo $limit + 100 ?>">Voir plus de résultats</a></center></td></tr>
		<?php endif; ?>
	     </tbody>
        </table>
    </div>

    <div id="modalViewer" class="modal">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">

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
                $('#modalViewer .modal-content').load("viewer.php?file=<?php echo $openPad ?>", function() {
                    $('#modalViewer').modal();
                });
            <?php endif; ?>

            $('.openModalViewer').on('click', function(e) {
                history.pushState(null, null, window.location.pathname+"?pad="+$(this).attr('data-identifiant'));
                $('#modalViewer .modal-content').load($(this).attr('href'), function() {
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
