<?php
$directory = dirname(__FILE__);

require $directory."/app.php";

$pad = PadClient::find($_GET['file']);

?>
<div class="modal-header">
    <h5 class="modal-title">
        <?php echo $pad->title; ?>
    </h5>

    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<div class="modal-body">
    <table class="table table-striped table-bordered table-sm">
        <tr>
            <th class="col-3">Dernière modification</th>
            <td><?php echo $pad->date->format('d/m/Y à H\hi'); ?></td>
        </tr>
        <tr>
            <th>Archivage planifié</th>
            <td><?php if($pad->getDateNextArchivage()): ?><?php echo $pad->getDateNextArchivage()->format('d/m/Y à H\hi'); ?><?php else : ?>Aucun<?php endif; ?></td>
        </tr>
        <tr>
            <th>Export</th>
            <td><a href="<?php echo $pad->uri.'.txt'; ?>">Texte</a> | <a href="<?php echo $pad->uri.'.md'; ?>">Markdown</a> | <a href="<?php echo $pad->uri.'.html'; ?>">HTML</a> | <a href="<?php echo $pad->uri.'.etherpad'; ?>">Etherpad</a> | <a href="<?php echo $pad->url; ?>">Lien</a></td>
        </tr>
    </table>
    <?php echo htmlspecialchars($pad->getContent()); ?>
</div>
