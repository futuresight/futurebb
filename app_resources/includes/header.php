<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><$page_title/> - <?php echo htmlspecialchars($futurebb_config['board_title']); ?></title>
	<link rel="stylesheet" type="text/css" href="<?php echo $base_config['baseurl']; ?>/styles/<?php echo $futurebb_user['style']; ?>.css" />
</head>

<body>

<div id="futurebb">
	<div class="forum_header">
		<div>
			<?php
				if ($futurebb_user['id'] != 0) {
					// If logged in
					
					//user button
					echo '<a class="userbutton" href="'. $base_config['baseurl'] . '/users/' . $futurebb_user['username'] . '">';
					if (file_exists(FORUM_ROOT . '/static/avatars/' . $futurebb_user['id'] . '.' . $futurebb_user['avatar_extension'])) {
						echo '<img src="' . $base_config['baseurl'] . '/static/avatars/' . $futurebb_user['id'] . '.' . $futurebb_user['avatar_extension'] . '" width="36px" height="36px" alt="avatar" />';
					}
					echo '<strong>' . $futurebb_user['username'] . '</strong>
					</a>';
					
					//notification area
					if ($futurebb_user['g_mod_privs'] || $futurebb_user['g_admin_privs']) {
						$result = $db->query('SELECT 1 FROM `#^reports` WHERE zapped IS NULL AND status<>\'withdrawn\'') or error('Failed to check new reports', __FILE__, __LINE__, $db->error());
						if ($db->num_rows($result)) {
							echo '<a class="userbutton" href="' . $base_config['baseurl'] . '/admin/reports" title="' . translate('newreports') . '"><img src="' . $base_config['baseurl'] . '/static/img/alert16.png" alt="new reports" /><span class="notifications_count">' . $db->num_rows($result) . '</span></a>';
						}
					}
					if ($futurebb_config['maintenance'] && $futurebb_user['g_admin_privs']) {
						echo '<a class="userbutton" href="' . $base_config['baseurl'] . '/admin#maintenance" title="' . translate('maintenabled') . '"><img src="' . $base_config['baseurl'] . '/static/img/lock16.png" alt="maintenance" /></a>';
					}
					if ($futurebb_config['turn_on_maint'] && $futurebb_user['g_admin_privs']) {
						echo '<a class="userbutton" href="' . $base_config['baseurl'] . '/admin#maintenance" title="' . translate('maintsched', user_date($futurebb_config['turn_on_maint'])) . '"><img src="' . $base_config['baseurl'] . '/static/img/clock16.png" alt="scheduled maintenance" /></a>';
					}
					if ($futurebb_user['notifications_count'] > 0) {
						echo '<span id="notifications"><a class="userbutton" href="' . $base_config['baseurl'] . '/messages" title="' . translate('unreadnotifications', $futurebb_user['notifications_count']) . '"><img src="' . $base_config['baseurl'] . '/static/img/message16.png" alt="unread notifications" />
						<span class="notifications_count">' . $futurebb_user['notifications_count']. '</span></a>';
						echo '';
						echo '</span>';
					}
				} else {
					// Not logged in, show login link
					echo '<a class="userbutton" style="font-weight: bold;" href="' . $base_config['baseurl'] . '/login">' . translate('login') . '</a>';
				}
			?>
			<h1><a href="<?php echo $base_config['baseurl']; ?>"><?php echo htmlspecialchars($futurebb_config['board_title']); ?></a></h1>
		</div>
		<div id="navlistwrap">
			<ul id="navlist">
				<?php
				$nav_items = array();
				$nav_items[] = '<a href="' . $base_config['baseurl'] . '">' . translate('index') . '</a>';
				if ($futurebb_user['id'] != 0) {
					$nav_items[] = '<a href="' . $base_config['baseurl'] . '/users/' . $futurebb_user['username'] . '">' . translate('profile') . '</a>';
				} else {
					$nav_items[] = '<a href="' . $base_config['baseurl'] . '/register/' . futurebb_hash(LoginController::GetRandId()) . '">' . translate('register') . '</a>';
				}
				if ($futurebb_user['g_user_list']) {
					$nav_items[] = '<a href="' . $base_config['baseurl']. '/users">' . translate('userlist') . '</a>';
				}
				$nav_items[] = '<a href="' .  $base_config['baseurl'] . '/search">' . translate('search') . '</a>';
				if ($futurebb_user['g_admin_privs']) {
					$nav_items[] = '<a href="' . $base_config['baseurl'] . '/admin">' . translate('administration') . '</a>';
				}
				if ($futurebb_user['g_mod_privs'] && !$futurebb_user['g_admin_privs']) {
					$nav_items[] = '<a href="' . $base_config['baseurl'] . '/admin/bans">' . translate('bans') . '</a>';
					$nav_items[] = '<a href="' . $base_config['baseurl'] . '/admin/trash_bin">' . translate('trashbin') . '</a>';
					$nav_items[] = '<a href="' . $base_config['baseurl'] . '/admin/reports">' . translate('reports') . '</a>';
				}
				if ($futurebb_user['id'] != 0) {
					$nav_items[] = '<a href="' . $base_config['baseurl'] . '/logout">' . translate('logout') . '</a>';
				}
				if ($futurebb_config['addl_header_links']) {
					$addl_links = str_replace("\r\n", "\n", $futurebb_config['addl_header_links']);
					$addl_links = str_replace("\r", chr(1), $addl_links);
					$addl_links = str_replace("\n", chr(1), $addl_links);
					$addl_links = explode(chr(1), $addl_links);
					foreach ($addl_links as $val) {
						$link_parts = explode(':', $val, 2);
						if (sizeof($link_parts) == 2 && $link_parts[0] == intval($link_parts[0])) {
							$nav_items = array_move($nav_items, $link_parts[0], 1);
							$nav_items[$link_parts[0]] = $link_parts[1];
						}
					}
				}
				foreach ($nav_items as $val) {
					echo '<li>' . $val . '</li>';
				}
				?>
			</ul>
		</div>
		<div id="headerinfo">
			<?php
			// Display board announcements if they are enabled
			if($futurebb_config['announcement_enable'] == 1) {
				echo '<p>' . $futurebb_config['announcement_text'] . '</p>';
			}
			?>
		</div>
	</div>
	<$breadcrumbs/>
	<?php if (!isset($page_info['nocontentbox'])) { ?>
	<div class="forum_content">
	<?php } ?>