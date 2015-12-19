<?php
if (!$futurebb_user['g_mod_privs'] && !$futurebb_user['g_admin_privs']) {
	httperror(403);
}
translate('<addfile>', 'admin');
$page_title = translate('iptracker');
include FORUM_ROOT . '/app_resources/includes/admin.php';

if (!isset($dirs[3])) {
	$dirs[3] = '';
}
?>
<div class="container">
	<?php make_admin_menu(); ?>
	<div class="forum_content rightbox admin">
		<?php
		if (isset($_GET['ip']) && $_GET['ip'] != '') {
			echo '<h3>' . translate('searchresultsfor', translate('ipaddr') . ' &quot;' . htmlspecialchars($_GET['ip']) . '&quot;') . '</h3>';
			echo '<p><a href="' . $base_config['baseurl'] . '/admin/bans/new?ip=' . rawurlencode(htmlspecialchars($_GET['ip'])) . '">' . translate('ban') . '</a></p>';
			$regs = array();
			$result = $db->query('SELECT username FROM `#^users` WHERE registration_ip=\'' . $db->escape($_GET['ip']) . '\' ORDER BY registered DESC LIMIT 10') or error('Failed to find users registered from that IP', __FILE__, __LINE__, $db->error());
			while ($u = $db->fetch_assoc($result)) {
				$regs[] = $u;
			}
			$posts = array();
			$result = $db->query('SELECT t.subject,p.id,p.posted,u.username AS poster FROM `#^posts` AS p LEFT JOIN `#^topics` AS t ON t.id=p.topic_id LEFT JOIN `#^users` AS u ON u.id=p.poster WHERE p.poster_ip=\'' . $db->escape($_GET['ip']) . '\' ORDER BY p.posted DESC LIMIT 30') or error('Failed to find posts', __FILE__, __LINE__, $db->error());
			while ($p = $db->fetch_assoc($result)) {
				$posts[] = $p;
			}
			//first, just get users
			echo '<h4>' . translate('recentactivity') . '</h4>';
			if (!empty($regs)) {
				echo '<h5>' . translate('userregs') . '</h5>';
				echo '<ul>';
				foreach ($regs as $val) {
					echo '<li><a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($val['username']) . '">' . htmlspecialchars($val['username']) . '</a></li>';
				}
				echo '</ul>';
			}
			if (!empty($posts)) {
				echo '<h5>' . translate('posts') . '</h5>';
				echo '<table border="0">';
				echo '<tr><th>' . translate('topic') . '</th><th>' . translate('time') . '</th><th>' . translate('user') . '</th></tr>';
				foreach ($posts as $val) {
					echo '<tr><td><a href="' . $base_config['baseurl'] . '/posts/' . $val['id'] . '">' . htmlspecialchars($val['subject']) . '</a></td><td>' . user_date($val['posted']) . '</td><td><a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($val['poster']) . '">' . htmlspecialchars($val['poster']) . '</a></td></tr>';
				}
				echo '</table>';
			}
		} else if (isset($_GET['username']) && $_GET['username'] != '') {
			$ips = array(); //stored in the notation [ip] => last used
			$result = $db->query('SELECT registration_ip,registered FROM `#^users` WHERE username=\'' . $db->escape($_GET['username']) . '\' ORDER BY registered DESC LIMIT 10') or error('Failed to find users registered from that IP', __FILE__, __LINE__, $db->error());
			while (list($ip_addr, $date) = $db->fetch_row($result)) {
				$ips[$ip_addr] = isset($ips[$ip_addr]) ? max($date, $ips[$ip_addr]): $date;
			}
			$result = $db->query('SELECT p.poster_ip,p.posted FROM `#^posts` AS p LEFT JOIN `#^users` AS u ON u.id=p.poster WHERE u.username=\'' . $db->escape($_GET['username']) . '\' ORDER BY p.posted DESC LIMIT 30') or error('Failed to find posts', __FILE__, __LINE__, $db->error());
			while (list($ip_addr, $date) = $db->fetch_row($result)) {
				$ips[$ip_addr] = isset($ips[$ip_addr]) ? max($date, $ips[$ip_addr]): $date;
			}
			echo '<h3>' . translate('searchresultsfor', strtolower(translate('username')) . ' &quot;' . htmlspecialchars($_GET['username']) . '&quot;') . '</h3>';
			if (sizeof($ips) == 0) {
				echo '<p>' . translate('noresults') . '</p>';
			} else {
				arsort($ips);
				?>
				<table border="0">
					<tr>
						<th><?php echo translate('ipaddr'); ?></th>
						<th><?php echo translate('lastused'); ?></th>
					</tr>
					<?php
					foreach ($ips as $ip => $lastused) {
						echo '<tr><td><a href="?ip=' . htmlspecialchars(rawurlencode($ip)) . '">' . htmlspecialchars($ip) . '</a></td><td>' . user_date($lastused) . '</td></tr>';
					}
					?>
				</table>
				<?php
			}
		} else if ($dirs[3] == '') {
			?>
			<form action="<?php echo $base_config['baseurl']; ?>/admin/ip_tracker" method="get" enctype="multipart/form-data">
				<h3><?php echo translate('iptracker'); ?></h3>
				<table border="0">
					<tr>
						<td><?php echo translate('searchip'); ?></td>
						<td><input type="text" name="ip" /></td>
					</tr>
					<tr>
						<td><?php echo translate('searchusername'); ?></td>
						<td><input type="text" name="username" /></td>
					</tr>
				</table>
				<p><input type="submit" value="<?php echo translate('search'); ?>" /></p>
			</form>
			<?php
		} else {
			httperror(404);
		}
		?>
	</div>
</div>