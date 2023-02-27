<?php
$directory = dirname(__FILE__);

require $directory."/app.php";

$pad = getPads()[$_GET['file']];

?>
<div class="modal-header">
    <h4 class="modal-title"><?php echo $pad->title; ?> <small class="text-muted"><?php echo $pad->date->format('d/m/Y'); ?></small></h4>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<div class="modal-body">
    <p><a href="<?php echo $pad->uri.'.txt'; ?>">Texte</a> | <a href="<?php echo $pad->uri.'.md'; ?>">Markdown</a> | <a href="<?php echo $pad->uri.'.html'; ?>">HTML</a> | <a href="<?php echo $pad->uri.'.etherpad'; ?>">Etherpad</a> | <a href="<?php echo $pad->url; ?>">Lien</a></span></p>
    <?php echo $pad->getContent(); ?>
</div>
