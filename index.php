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

$pads = array_slice(PadClient::search($q), 0, $limit);

$openPad = null;
if(isset($_GET['pad'])) {
    $openPad = $_GET['pad'];
}

$gitUrl = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'pads.git';

?>
<!DOCTYPE html>
<html lang="fr_FR">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="vendor/bootstrap.min.css">
</head>
<body>
    <div class="container pt-3">
        <button type="button" class="btn btn-outline-primary float-right" data-toggle="modal" data-target="#modalArchive">Archiver un pad</button>


        <h2>Historique des pads
            <div class="btn-group">
                <button type="button" class="btn btn-outline-dark btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">Git Clone</button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" onclick="navigator.clipboard.writeText(this.href); alert('Le lien a été copié dans le presse papier !'); return false;" href="<?php echo $gitUrl; ?>"><?php echo $gitUrl; ?></a>
                </div>
            </div>
        </h2>
        <form method="GET" class="mt-3">
            <div class="input-group position-relative">
                <input type="search" autofocus="autofocus" name="q" placeholder="Recherche sur le titre" class="form-control" value="<?php echo $q ?>" autocomplete="off" />
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="submit">Rechercher</button>
                </div>
                <?php if($q): ?>
                <a href="?q=" class="small float-right position-absolute"style="z-index: 3; right: 120px; top: 8px;">Annuler la recherche</a>
                <?php endif; ?>
            </div>
        </form>
        <table class="table table-bordered table-striped table-sm mt-3">
            <thead>
                <tr>
                    <th style="width: 0;">Date</th>
                    <th class="col-6">Titre</th>
                    <th>Pad</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($pads as $pad): ?>
                    <tr>
                        <td><?php echo $pad->date->format('d/m/Y'); ?></td>
                        <td><?php echo htmlspecialchars($pad->title) ?></td>
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

    <div id="modalArchive" class="modal">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Archiver un pad
                    </h5>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="form_archive" method="GET" action="archive.php">
                        <input type="hidden" name="run" value="1" />
                        <div class="form-group">
                          <label for="url">Saisissez l'url du pad :</label>
                          <input type="url" required="required" class="form-control" id="url" name="url" placeholder="https://..." autofocus="autofocus" autocomplete="off" / >
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" form="form_archive" class="btn btn-primary">Valider</button>
                </div>
            </div>
        </div>
    </div>

    <script src="vendor/jquery.min.js"></script>
    <script src="vendor/popper.min.js"></script>
    <script src="vendor/bootstrap.min.js"></script>
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

            $('#modalArchive').on('shown.bs.modal', function (e) {
                $('#modalArchive [autofocus="autofocus"]').focus();
            })
            $('#modalArchive').on('shown.bs.modal', function (e) {
                $('#modalArchive input[name="url"]').val("");
            })
        });
    </script>
</body>
</html>
