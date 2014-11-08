<?php
$page_title = translate('404title');
?>
<h2 style="font-style: italic; color: #600;"><?php echo translate('404head'); ?></h2>
<p><?php echo translate('404body1', htmlspecialchars($_SERVER['REQUEST_URI'])); ?></p>
<p><?php echo translate('403body2'); ?></p>