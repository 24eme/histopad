<?php
$directory = dirname(__FILE__);

require $directory."/app.php";

$pad = getPadFromFile($_GET['file'].".md", true);

?>
<div class="modal-header">
    <h4 class="modal-title"><?php echo $pad->title; ?></h4>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<div class="modal-body">
    <p><a href="<?php echo $pad->uri_txt; ?>">Texte</a> | <a href="<?php echo $pad->uri_markdown; ?>">Markdown</a> | <a href="<?php echo $pad->url; ?>">Pad</a></span></p>
    <?php echo $pad->content; ?>
</div>
