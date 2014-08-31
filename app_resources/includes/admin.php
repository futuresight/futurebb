<?php
function make_admin_menu() {
	global $dirs, $futurebb_user, $base_config;
	include FORUM_ROOT . '/app_config/admin_pages.php';
	?>
	<div class="forum_content leftmenu">
		<h2 class="boxtitle">Administration</h2>
		<ul class="leftnavlist">
		<?php
		if ($futurebb_user['g_admin_privs']) {
			$p = $admin_pages;
		} else {
			$p = $mod_pages;
		}
		foreach ($p as $key => $val) {
			echo '<li';
			if ($dirs[2] == $key) {
				echo ' class="active"';
			}
			echo '><a href="' . $base_config['baseurl'] . '/admin/' . $key . '">' . htmlspecialchars(translate($val)) . '</a></li>';
		}
		?>
		</ul>
	</div>
	<?php
}