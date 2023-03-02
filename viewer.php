<?php
$directory = dirname(__FILE__);

require $directory."/app.php";

$pad = PadClient::find($_GET['file']);

?>
<div class="modal-header">
    <h4 class="modal-title">
        <?php echo $pad->title; ?>
    </h4>

    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<table class="table table-striped table-bordered table-sm mb-0">
    <tr>
        <th class="col-3">Dernière modification</th>
        <td><?php echo $pad->date->format('d/m/Y H:i:s'); ?></td>
    </tr>
    <tr>
        <th>Archivage planifié</th>
        <td><?php if($pad->getDateNextArchivage()): ?><?php echo $pad->getDateNextArchivage()->format('d/m/Y H:i:s'); ?><?php else : ?>Aucune<?php endif; ?></td>
    </tr>
    <tr>
        <th>Export</th>
        <td><a href="<?php echo $pad->uri.'.txt'; ?>">Texte</a> | <a href="<?php echo $pad->uri.'.md'; ?>">Markdown</a> | <a href="<?php echo $pad->uri.'.html'; ?>">HTML</a> | <a href="<?php echo $pad->uri.'.etherpad'; ?>">Etherpad</a> | <a href="<?php echo $pad->url; ?>">Lien</a></td>
    </tr>
</table>
<div class="modal-body">
    <?php echo $pad->getContent(); ?>
</div>
