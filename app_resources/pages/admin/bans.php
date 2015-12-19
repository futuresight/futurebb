<?php
if (!$futurebb_user['g_mod_privs'] && !$futurebb_user['g_admin_privs']) {
	httperror(403);
}
translate('<addfile>', 'admin');
$page_title = translate('bans');
include FORUM_ROOT . '/app_resources/includes/admin.php';
?>
<div class="container">
	<?php make_admin_menu(); ?>
	<div class="forum_content rightbox admin">
		<?php
		if (!isset($dirs[3])) {
			$dirs[3] = '';
		}
		switch ($dirs[3]) {
			case '':
			?>
			<h2><?php echo translate('bans'); ?></h2>
			<p><a href="<?php echo $base_config['baseurl']; ?>/admin/bans/new"><?php echo translate('newban'); ?></a></p>
			<table border="0">
				<tr>
					<th><?php echo translate('username'); ?></th>
					<th style="max-width:100px; overflow:hidden"><?php echo translate('ipaddr'); ?></th>
					<th><?php echo translate('message'); ?></th>
					<th><?php echo translate('expires'); ?></th>
					<th><?php echo translate('actions'); ?></th>
				</tr>
				<?php
				$result = $db->query('SELECT id,username,ip,message,expires FROM `#^bans` WHERE expires>' . time() . ' OR expires IS NULL ORDER BY expires ASC') or error('Failed to get bans', __FILE__, __LINE__, $db->error());
				while ($cur_ban = $db->fetch_assoc($result)) {
					echo '<tr><td>' . htmlspecialchars($cur_ban['username']) . '</td><td style="max-width:200px; overflow:hidden">' . $cur_ban['ip'] . '</td><td>' . htmlspecialchars($cur_ban['message']) . '</td><td>' . ($cur_ban['expires'] == null ? translate('never') : user_date($cur_ban['expires'])) . '</td><td><a href="' . $base_config['baseurl'] . '/admin/bans/edit/' . $cur_ban['id'] . '">' . translate('edit') . '</a> / <a href="' . $base_config['baseurl'] . '/admin/bans/delete/' . $cur_ban['id'] . '">' . translate('delete') . '</a></td></tr>';
				}
				$db->query('DELETE FROM `#^bans` WHERE expires<=' . time()) or enhanced_error('Failed to delete old bans', true); //delete any bans that have already expired
				?>
			</table>
		<?php
				break;
			case 'new':
				if (isset($_POST['form_sent'])) {
					$db->query('INSERT INTO `#^bans`(username,ip,message,expires) VALUES(\'' . $db->escape($_POST['username']) . '\',\'' . $db->escape($_POST['ip']) . '\',\'' . $db->escape($_POST['message']) . '\',' . ($_POST['expires'] == '' ? 'NULL' : strtotime($_POST['expires'])) . ')') or error('Failed to insert ban', __FILE__, __LINE__, $db->error());
					CacheEngine::CacheBans();
					redirect($base_config['baseurl'] . '/admin/bans');
					return;
				} else {
					if (isset($_GET['user'])) {
						//get all recent IPs used by a user
						$ips = array(); //stored in the notation [ip] => last used
						
						$user_id = intval($_GET['user']);
						$username = '';
						$result = $db->query('SELECT username,registration_ip,registered FROM `#^users` WHERE id=' . $user_id . ' ORDER BY registered DESC LIMIT 10') or error('Failed to find users registered from that IP', __FILE__, __LINE__, $db->error());
						while (list($cur_username, $ip_addr, $date) = $db->fetch_row($result)) {
							$ips[] = $ip_addr;
							$username = $cur_username;
						}
						$result = $db->query('SELECT poster_ip,posted FROM `#^posts` WHERE poster=' . $user_id . ' ORDER BY posted DESC LIMIT 100') or error('Failed to find posts', __FILE__, __LINE__, $db->error());
						while (list($ip_addr, $date) = $db->fetch_row($result)) {
							$ips[] = $ip_addr;
						}
						array_unique($ips);
						arsort($ips);
					} else if (isset($_GET['ip'])) {
						$ips = array($_GET['ip']);
					}
					?>
					<h2><?php echo translate('newban'); ?></h2>
					<form action="<?php echo $base_config['baseurl']; ?>/admin/bans/new" method="post" enctype="multipart/form-data">
						<table border="0">
							<tr>
								<td><?php echo translate('username'); ?></td>
								<td><input type="text" name="username"<?php if (isset($username)) echo ' value="' . htmlspecialchars($username) . '"'; ?> /></td>
							</tr>
							<tr>
								<td><?php echo translate('ipaddr'); ?></td>
								<td><input type="text" name="ip"<?php if (isset($ips)) echo ' value="' . htmlspecialchars(implode(',', $ips)) . '"'; ?> /></td>
							</tr>
							<tr>
								<td><?php echo translate('message'); ?></td>
								<td><input type="text" name="message" size="50" /></td>
							</tr>
							<tr>
								<td><?php echo translate('expires'); ?></td>
								<td><input type="text" name="expires" /></td>
							</tr>
						</table>
						<p><input type="submit" name="form_sent" value="<?php echo translate('add'); ?>" /></p>
					</form>
					<?php
				}
				break;
			case 'edit':
				$result = $db->query('SELECT id,username,ip,message,expires FROM `#^bans` WHERE (expires>' . time() . ' OR expires IS NULL) AND id=' . intval($dirs[4])) or error('Failed to get ban info', __FILE__, __LINE__, $db->error());
				if (!$db->num_rows($result)) {
					httperror(404);
				}
				$cur_ban = $db->fetch_assoc($result);
				if (isset($_POST['form_sent'])) {
					$db->query('UPDATE `#^bans` SET username=\'' . $db->escape($_POST['username']) . '\',ip=\'' . $db->escape($_POST['ip']) . '\',message=\'' . $db->escape($_POST['message']) . '\',expires=' . ($_POST['expires'] == '' ? 'NULL' : strtotime($_POST['expires'])) . ' WHERE id=' . intval($dirs[4])) or error('Failed to update ban', __FILE__, __LINE__, $db->error());
					CacheEngine::CacheBans();
					redirect($base_config['baseurl'] . '/admin/bans');
					return;
				} else {
					?>
					<h2><?php echo translate('editban'); ?></h2>
					<form action="<?php echo $base_config['baseurl']; ?>/admin/bans/edit/<?php echo intval($dirs[4]); ?>" method="post" enctype="multipart/form-data">
						<table border="0">
							<tr>
								<td><?php echo translate('username'); ?></td>
								<td><input type="text" name="username" value="<?php echo htmlspecialchars($cur_ban['username']); ?>" /></td>
							</tr>
							<tr>
								<td><?php echo translate('ipaddr'); ?></td>
								<td><input type="text" name="ip" value="<?php echo htmlspecialchars($cur_ban['ip']); ?>" /></td>
							</tr>
							<tr>
								<td><?php echo translate('message'); ?></td>
								<td><input type="text" name="message" size="50" value="<?php echo htmlspecialchars($cur_ban['message']); ?>" /></td>
							</tr>
							<tr>
								<td><?php echo translate('expires'); ?></td>
								<td><input type="text" name="expires" value="<?php echo $cur_ban['expires'] == null ? '' : user_date($cur_ban['expires']); ?>" /></td>
							</tr>
						</table>
						<p><input type="submit" name="form_sent" value="<?php echo translate('submit'); ?>" /></p>
					</form>
					<?php
				}
				break;
			case 'delete':
				$result = $db->query('SELECT id,username,ip,message,expires FROM `#^bans` WHERE (expires>' . time() . ' OR expires IS NULL) AND id=' . intval($dirs[4])) or error('Failed to get ban info', __FILE__, __LINE__, $db->error());
				if (!$db->num_rows($result)) {
					httperror(404);
				}
				if (isset($_POST['form_sent'])) {
					$db->query('DELETE FROM `#^bans` WHERE id=' . intval($dirs[4])) or error('Failed to delete ban', __FILE__, __LINE__, $db->error());
					CacheEngine::CacheBans();
					redirect($base_config['baseurl'] . '/admin/bans');
				}
				$cur_ban = $db->fetch_assoc($result);
				?>
				<h2><?php echo translate('deleteban'); ?></h2>
				<p><?php echo translate('deletebanconfirm'); ?></p>
				<form action="<?php echo $base_config['baseurl']; ?>/admin/bans/delete/<?php echo intval($dirs[4]); ?>" method="post" enctype="multipart/form-data">
					<table border="0">
						<tr>
							<td><?php echo translate('username'); ?></td>
							<td><?php echo htmlspecialchars($cur_ban['username']); ?></td>
						</tr>
						<tr>
							<td><?php echo translate('ipaddr'); ?></td>
							<td><?php echo htmlspecialchars($cur_ban['ip']); ?></td>
						</tr>
						<tr>
							<td><?php echo translate('message'); ?></td>
							<td><?php echo htmlspecialchars($cur_ban['message']); ?></td>
						</tr>
						<tr>
							<td><?php echo translate('expires'); ?></td>
							<td><?php echo $cur_ban['expires'] == null ? translate('never') : user_date($cur_ban['expires']); ?></td>
						</tr>
					</table>
					<p><input type="submit" name="form_sent" value="<?php echo translate('delete'); ?>" /> &bull; <a href="<?php echo $base_config['baseurl']; ?>/admin/bans"><?php echo translate('jk'); ?></a></p>
				</form>
				<?php
				break;
			default:
				httperror(404);
		}
		?>
	</div>
</div>