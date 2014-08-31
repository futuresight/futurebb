		<?php if (!isset($page_info['nocontentbox'])) { ?>
		</div>
		<?php } ?>
		<?php if ($page_info['file'] != 'viewtopic.php') { ?>
		<$breadcrumbs/>
		<?php } ?>
		<div class="forum_footer">
			<p><?php echo translate('poweredby'); ?></p>
			<?php
			if ($futurebb_config['footer_text'] != '') {
				echo '<p>' . $futurebb_config['footer_text'] . '</p>';
			}
			?>
		</div>
		<$debug_info/>
	</div>
</body>
</html>