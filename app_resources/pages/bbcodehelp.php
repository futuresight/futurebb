<?php
$page_title = 'BBCode Help';
include FORUM_ROOT . '/app_resources/includes/parser.php';
?>
<div class="container">
    <div class="forum_content">
        <h2><?php echo translate('bbcodehelp'); ?></h2>
        <p><?php echo translate('bbcodehelpintro'); ?></p>
    </div>
    <div class="forum_content">
    	<h3><?php echo translate('basicformatting'); ?></h3>
        <p><?php echo translate('tagssupported'); ?></p>
        <ul>
        	<li><code>[b]<?php echo translate('boldtext'); ?>[/b]</code> <?php echo translate('produces'); ?> <strong><?php echo translate('boldtext'); ?></strong></li>
            <li><code>[i]<?php echo translate('italictext'); ?>[/i]</code> <?php echo translate('produces'); ?> <em><?php echo translate('italictext'); ?></em></li>
            <li><code>[u]<?php echo translate('underlinedtext'); ?>[/u]</code> <?php echo translate('produces'); ?> <u><?php echo translate('underlinedtext'); ?></u></li>
            <li><code>[s]<?php echo translate('struckouttext'); ?>[/s]</code> <?php echo translate('produces'); ?> <del><?php echo translate('struckouttext'); ?></del></li>
            <li><code>[color=#00A]<?php echo translate('bluetext'); ?>[/color]</code> <?php echo translate('produces'); ?> <span style="color:#00A"><?php echo translate('bluetext'); ?></span></li>
            <li><code>[color=magenta]<?php echo translate('magentatext'); ?>[/color]</code> <?php echo translate('produces'); ?> <span style="color:magenta"><?php echo translate('magentatext'); ?></span></li>
        </ul>
    </div>
    <div class="forum_content">
    	<h3><?php echo translate('quotes'); ?></h3>
        <p><code>[quote]<?php echo translate('textquoting'); ?>[/quote]</code> <?php echo translate('produces'); ?>:</p>
        <div class="quotebox"><p><?php echo translate('textquoting'); ?></p></div>
        <p><code>[quote=<?php echo translate('johnsmith'); ?>]<?php echo translate('textquoting'); ?>[/quote]</code> <?php echo translate('produces'); ?>:</p>
        <div class="quotebox"><p><b><?php echo translate('johnsmith'); ?> <?php echo translate('wrote'); ?></b><br /><?php echo translate('textquoting'); ?></p></div>
    </div>
    <div class="forum_content" id="smilies">
    	<h3><?php echo translate('smilies'); ?></h3>
        <ul>
        <?php
		foreach (BBCodeController::$smilies as $code => $url) {
			echo '<li><code>' . $code . '</code> ' . translate('produces') . ' <img src="' . $base_config['baseurl'] . '/static/img/smile/' . $url . '" alt="' . $code . '" width="15px" height="15px" /></li>';
		}
		?>
        </ul>
    </div>
    <div class="forum_content" id="linksimages">
    	<h3><?php echo translate('linksandimages'); ?></h3>
        <ul>
        	<li><code>[url]http://futuresight.org[/url]</code> <?php echo translate('produces'); ?> <a href="http://futuresight.org">http://futuresight.org</a></li>
            <li><code>[url=http://futuresight.org]FutureSight Technologies[/url]</code> <?php echo translate('produces'); ?> <a href="http://futuresight.org">FutureSight Technologies</a></li>
            <li><code>[img]https://www.google.com/images/srpr/logo11w.png[/img]</code> <?php echo translate('produces'); ?> <br /> <img src="https://www.google.com/images/srpr/logo11w.png" alt="forum image" /></li>
        </ul>
    </div>
</div>