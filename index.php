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

$pads = array_slice(PadClient::getAll($q), 0, $limit);

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
        <h2>Historique des pads</h2>

        <form method="GET" class="mt-3">
            <div class="input-group position-relative">
                <input type="search" autofocus="autofocus" name="q" placeholder="Recherche sur le titre" class="form-control" value="<?php echo $q ?>" autocomplete="off" />
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button">Rechercher</button>
                </div>
                <?php if($q): ?>
                <a href="?q=" class="small float-right position-absolute"style="z-index: 3; right: 120px; top: 8px;">Annuler la recherche</a>
                <?php endif; ?>
            </div>
        </form>
        <table class="table table-bordered table-striped table-sm mt-3">
            <thead>
                <tr>
                    <th class="col-1">Date</th>
                    <th class="col-6">Titre</th>
                    <th>Pad</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($pads as $pad): ?>
                    <tr>
                        <td><?php echo $pad->date->format('d/m/Y'); ?></td>
                        <td><?php echo $pad->title ?></td>
                        <td style=""><a href="<?php echo $pad->url; ?>"><?php echo $pad->url; ?></a></td>
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
