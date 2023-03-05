<?php
$directory = dirname(__FILE__);

require $directory."/app.php";

if(!preg_match('/^[a-zA-Z0-9_-]+$/', $_GET['id'])) {
    exit;
}

$pad = PadClient::find($_GET['id']);

?>
<div class="modal-header">
    <h5 class="modal-title">
        <?php echo $pad->getTitle(); ?>
    </h5>

    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<div class="modal-body">
    <table class="table table-striped table-bordered table-sm">
        <tr>
            <th>Url</th>
            <td><a href="<?php echo $pad->getUrl(); ?>"><?php echo str_replace($pad->getId(), '<strong>'.$pad->getId().'</strong>', $pad->getUrl()); ?></a></td>
        </tr>
        <tr>
            <th class="col-3">Dernière modification</th>
            <td><?php echo $pad->getDate()->format('d/m/Y à H\hi'); ?></td>
        </tr>
        <tr>
            <th>Archivage planifié</th>
            <td><?php if($pad->getDateNextArchivage()): ?><?php echo $pad->getDateNextArchivage()->format('d/m/Y à H\hi'); ?><?php else : ?>Aucun<?php endif; ?></td>
        </tr>
        <tr>
            <th>Export</th>
            <td><a href="<?php echo $pad->getFile('txt'); ?>">Texte</a> | <a href="<?php echo $pad->getFile('md'); ?>">Markdown</a> | <a href="<?php echo $pad->getFile('html'); ?>">HTML</a> | <a href="<?php echo $pad->getFile('etherpad'); ?>">Etherpad</a></td>
        </tr>
    </table>
    <?php echo nl2br(htmlspecialchars($pad->getContent())); ?>
</div>
