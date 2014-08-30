<?php
$page_title = translate('403title');
?>
<h2 style="font-style: italic; color: #600;"><?php echo translate('403head'); ?></h2>
<p><?php echo translate('403body1', $_SERVER['REQUEST_URI']); ?></p>
<p><?php echo translate('403body2'); ?></p>