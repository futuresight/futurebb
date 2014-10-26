<?php
if (!isset($dirs[2]) || $dirs[2] == '') {
	include FORUM_ROOT . '/app_resources/pages/userlist.php';
	return;
}
if (!isset($dirs[3])) {
	$dirs[3] = '';
}
function PMBox() {
	global $futurebb_config, $futurebb_user, $cur_user, $base_config, $dirs;
	// Private messaging
	if(($futurebb_config['allow_privatemsg'] == 1 && $futurebb_user['id'] != 0 && $futurebb_user['id'] != $cur_user['id'] && $cur_user['block_pm'] == 0) || $futurebb_user['g_mod_privs']) {
		echo '<h3>' . translate('sendPM') . '</h3>';
		echo '<form action="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($dirs[2]) . '" method="post" enctype="multipart/form-data" name="sendpm">
		<textarea name="pm_text" style="width: 290; height: 50;"></textarea><br />
		<input name="pm_sent" type="submit" value="' . translate('send') . '" />';
		if($futurebb_user['g_mod_privs']) echo '<input type="checkbox" name="send_warning" id="send_warning" /> <label for="send_warning">' . translate('sendas_admin') . '</label>';
		echo '</form>';
	}
}
$user = $dirs[2];
$edit = false;
if ($user == $futurebb_user['username'] || $futurebb_user['g_admin_privs']) {
	$edit = true;
	if ($user == $futurebb_user['username']) {
		$edit = true;
	}
}
$result = $db->query('SELECT u.*,g.* FROM `#^users` AS u LEFT JOIN `#^user_groups` AS g ON g.g_id=u.group_id WHERE LOWER(username)=\'' . $db->escape(strtolower($user)) . '\'') or error('Failed to get user', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result)) {
	httperror(404);
}
$cur_user = $db->fetch_assoc($result);
if ($cur_user['deleted'] == 1 && !$futurebb_user['g_admin_privs']) {
	httperror(404);
}

if ($cur_user['username'] != $user) {
	unset($dirs[0], $dirs[1], $dirs[2]);
	header('Location: ' . $base_config['baseurl'] . '/users/' . $cur_user['username'] . '/' . implode('/', $dirs));
	return;
}
$page_title = $user . ' - Users';

if(isset($_POST['pm_sent'])) {
	// Send PM / Warning to user
	$send_type = 'msg';
	if(isset($_POST['send_warning'])) $send_type = 'warning';
	$db->query('INSERT INTO `#^notifications` (type, user, send_time, contents, arguments) VALUES (\'' . $send_type . '\', ' . $cur_user['id'] . ', ' . time() . ', \'' . $db->escape(htmlspecialchars($_POST['pm_text'])) . '\', \'' . $db->escape($futurebb_user['username']) .'\')') or enhanced_error('Failed to send PM', true);
}
?>
<div class="container">
	<?php if ($edit) { ?>
	<div class="forum_content leftmenu">
		<h2 class="boxtitle"><?php echo htmlspecialchars($user); ?></h2>
		<ul class="leftnavlist">
			<li <?php if ($dirs[3] == '') echo ' class="active"'; ?>><a href="<?php echo $base_config['baseurl']; ?>/users/<?php echo htmlspecialchars($dirs[2]); ?>"><?php echo translate('basics'); ?></a></li>
			<li <?php if ($dirs[3] == 'security') echo ' class="active"'; ?>><a href="<?php echo $base_config['baseurl']; ?>/users/<?php echo htmlspecialchars($dirs[2]); ?>/security"><?php echo translate('security'); ?></a></li>
			<?php if ($futurebb_config['avatars']) { ?>
			<li <?php if ($dirs[3] == 'avatar') echo ' class="active"'; ?>><a href="<?php echo $base_config['baseurl']; ?>/users/<?php echo htmlspecialchars($dirs[2]); ?>/avatar"><?php echo translate('avatar'); ?></a></li>
			<?php } ?>
			<?php if ($cur_user['g_signature']) { ?><li <?php if ($dirs[3] == 'sig') echo ' class="active"'; ?>><a href="<?php echo $base_config['baseurl']; ?>/users/<?php echo htmlspecialchars($dirs[2]); ?>/sig"><?php echo translate('postsig'); ?></a></li><?php } ?>
			<li <?php if ($dirs[3] == 'reports') echo ' class="active"'; ?>><a href="<?php echo $base_config['baseurl']; ?>/users/<?php echo htmlspecialchars($dirs[2]); ?>/reports"><?php echo translate('reports'); ?></a></li>
			<?php if ($user != $futurebb_user['username'] && $futurebb_user['g_admin_privs']) { ?>
			<li <?php if ($dirs[3] == 'admin') echo ' class="active"'; ?>><a href="<?php echo $base_config['baseurl']; ?>/users/<?php echo htmlspecialchars($dirs[2]); ?>/admin"><?php echo translate('administration'); ?></a></li>
			<?php } ?>
            <?php if ($futurebb_user['g_admin_privs']) { ?>
			<li <?php if ($dirs[3] == 'warnings') echo ' class="active"'; ?>><a href="<?php echo $base_config['baseurl']; ?>/users/<?php echo htmlspecialchars($dirs[2]); ?>/warnings"><?php echo translate('warnings'); ?></a></li>
			<?php } ?>
            <li <?php if ($dirs[3] == 'msgs') echo ' class="active"'; ?>><a href="<?php echo $base_config['baseurl']; ?>/users/<?php echo htmlspecialchars($dirs[2]); ?>/msgs"><?php echo translate('messages'); ?></a></li>
		</ul>
	</div>
    <?php } ?>
	<div class="forum_content<?php if ($edit) echo ' rightbox'; ?>">
	<?php
	if ($edit) {
		switch ($dirs[3]) {
			case '':
				if (isset($_POST['form_sent'])) {
					// Update basic config
					$cfg_list = array(
						//format: 'name'		=> 'type'
						'email'				=> 'string',
						'timezone'			=> 'int',
						'style'				=> 'string',
						'language'			=> 'string',
						'block_pm'			=> 'bool',
						'block_notif'		=> 'bool',
					);
					if ($_POST['rss_token'] != $cur_user['rss_token']) {
						$token_exists = true;
						while ($token_exists) {
							$token = md5(rand(1,10000000000));
							$result = $db->query('SELECT 1 FROM `#^users` WHERE rss_token=\'' . $db->escape($token) . '\'') or error('Failed to check if token exists', __FILE__, __LINE__, $db->error());
							$token_exists = $db->num_rows($result);
						}
						$cfg_list['rss_token'] = 'string';
						$_POST['rss_token'] = $token;
					}
					$sql = '';
					foreach ($cfg_list as $name => $type) {
						switch ($type) {
							case 'bool':
								$val = (isset($_POST[$name]) ? '1' : '0');
								$sql .= ',' . $name . '=' . $val;
								break;
							case 'string':
								$val = $_POST[$name];
								$sql .= ',' . $name . '=\'' . $db->escape($val) . '\'';
								break;
							case 'int':
								$val = intval($_POST[$name]);
								$sql .= ',' . $name . '=' . $val;
								break;
						}
						$cur_user[$name] = $val;
					}
					$sql = substr($sql, 1);
					$db->query('UPDATE `#^users` SET ' . $sql . ' WHERE id=' . $cur_user['id']) or error('Failed to update user info', __FILE__, __LINE__, $db->error());
				}
				
				echo '<h3>' . translate('profile') . ' - ' . translate('basics') . '</h3>';
				if ($cur_user['deleted']) {
					echo '<p class="redem">' . translate('userdeleted') . '</p>';
				}
				echo '<p>' . translate('profiledesc') . '</p>';
				
				// User details and settings
				echo '<h4>' . translate('userdetails') . '</h4>';
				echo '<p><strong>' . translate('numposts') . '</strong> ' . $cur_user['num_posts'] . '</p>';
				echo '<p><strong>' . translate('dateregistered') . '</strong> ' . user_date($cur_user['registered']) . '</p>';
				echo '<form action="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($dirs[2]) . '" method="post" enctype="multipart/form-data">';
				?>
				<table border="0">
					<tr>
						<td><?php echo translate('emailaddrnocolon'); ?></td>
						<td><input type="text" name="email" value="<?php echo htmlspecialchars($cur_user['email']); ?>" /></td>
					</tr>
					<tr>
						<td><?php echo translate('timezone'); ?></td>
						<td><select name="timezone"><?php for ($i = -12; $i <= 12; $i++) echo '<option value="' . $i . '"' . ($i == $cur_user['timezone'] ? ' selected="selected"' : '') . '>GMT' . ($i >= 0 ? '+': '') . $i . '</option>'; ?></select></td>
					</tr>
					<tr>
						<td><?php echo translate('styleset'); ?></td>
						<td><select name="style"><?php
						$handle = opendir(FORUM_ROOT . '/app_resources/pages/css');
						while ($f = readdir($handle)) {
							if (pathinfo($f, PATHINFO_EXTENSION) == 'css') {
								$f = htmlspecialchars(basename($f, '.css'));
								echo '<option value="' . $f . '"';
								if ($f == $cur_user['style']) {
									echo ' selected="selected"';
								}
								echo '>' . $f . '</option>';
							}
						}
						?></select></td>
					</tr>
					<tr>
						<td><?php echo translate('language'); ?></td>
						<td><select name="language"><?php
						$handle = opendir(FORUM_ROOT . '/app_config/langs');
						while ($f = readdir($handle)) {
							if ($f != '.' && $f != '..') {
								$f = htmlspecialchars($f);
								echo '<option value="' . $f . '"';
								if ($f == $cur_user['language']) {
									echo ' selected="selected"';
								}
								echo '>' . $f . '</option>';
							}
						}
						?></select></td>
					</tr>
                    <tr>
						<td><?php echo translate('rsstoken'); ?></td>
						<td><input type="text" name="rss_token" value="<?php echo htmlspecialchars($cur_user['rss_token']); ?>" size="50" /></td>
					</tr>
					<tr>
						<td><?php echo translate('blockPM'); ?></td>
						<td><input type="checkbox" name="block_pm"<?php if ($cur_user['block_pm']) echo ' checked="checked"'; ?> /></td>
					</tr>
					<tr>
						<td><?php echo translate('blocknotifs'); ?></td>
						<td><input type="checkbox" name="block_notif"<?php if ($cur_user['block_notif']) echo ' checked="checked"'; ?> /></td>
					</tr>
				</table>
				<p><?php echo translate('dateregistered'); ?>: <?php echo user_date($cur_user['registered']); ?> (IP: <?php echo $cur_user['registration_ip']; ?>)</p>
				<p><input type="submit" name="form_sent" value="<?php echo translate('save'); ?>" /></p>
				<?php
				PMBox();
				echo '</form>';
				break;
			case 'security':
				if (isset($_POST['form_sent'])) {
					if ($_POST['pwd1'] != $_POST['pwd2']) {
						echo '<p><b>' . translate('passnomatch') . '</b></p>';
					} else {
						$db->query('UPDATE `#^users` SET password=\'' . futurebb_hash($_POST['pwd1']) . '\' WHERE username=\'' . $db->escape($user) . '\'') or error('Failed to update password', __FILE__, __LINE__, $db->error());
						if ($me) {
							LoginController::LogInUser($futurebb_user['id'], futurebb_hash($_POST['pwd1']), $_SERVER['HTTP_USER_AGENT']);
						}
						redirect($base_config['baseurk'] . '/users/' . rawurlencode($dirs[2]));
					}
				}
				echo '<form action="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($dirs[2]) . '/security" method="post" enctype="multipart/form-data">';
				?>
				<h2><?php echo translate('changepass'); ?></h2>
				<table border="0">
					<tr>
						<td><?php echo translate('newpass'); ?></td>
						<td><input type="password" name="pwd1" /></td>
					</tr>
					<tr>
						<td><?php echo translate('confirmpwd'); ?></td>
						<td><input type="password" name="pwd2" /></td>
					</tr>
				</table>
				<p><input type="submit" name="form_sent" value="<?php echo translate('save'); ?>" /></p>
				<?php
				echo '</form>';
				break;
			case 'avatar':
				if (!$futurebb_config['avatars']) {
					httperror(404);
				}
				if (isset($dirs[4]) && $dirs[4] == 'remove') {
					$q = new DBUpdate('users', array('avatar_extension' => null), 'username=\'' . $db->escape($user) . '\'', 'Failed to remove avatar extension');
					$q->commit();
				}
				if (isset($_POST['form_sent'])) {
					// Make sure the upload worked right
					if (isset($_FILES['avatar']['error'])) {
						switch ($_FILES['avatar']['error']) {
							case 1: // UPLOAD_ERR_INI_SIZE
							case 2: // UPLOAD_ERR_FORM_SIZE
								echo '<p>' . translate('toobigphpini', (ini_get('upload_max_filesize') / 1024)) . '</p></div>'; return;
								break;
							case 3: // UPLOAD_ERR_PARTIAL
								echo '<p>' . translate('partialupload') . '</p></div>'; return;
								break;
			
							case 4: // UPLOAD_ERR_NO_FILE
								echo '<p>' . translate('uploadfailed') . '</p></div>'; return;
								break;
			
							case 6: // UPLOAD_ERR_NO_TMP_DIR
								echo '<p>' . translate('notmpdir') . '</p></div>'; return;
								break;
			
							default:
								if ($_FILES['avatar']['size'] == 0) {
									echo '<p>' . translate('uploadfailed') . '</p></div>'; return;
								}
								break;
						}
					}
					if (!is_uploaded_file($_FILES['avatar']['tmp_name'])) {
						echo '<p>' . translate('uploadfailed') . '</p></div>'; return;
					}
					if (!in_array($_FILES['avatar']['type'], array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png'))) {
						echo '<p>' . translate('badavatarfiletype') . '</p></div>'; return;
					}
					if ($_FILES['avatar']['size'] > $futurebb_config['avatar_max_filesize'] * 1024) {
						echo '<p>' . translate('filetoobig', $futurebb_config['avatar_max_filesize']) . '</p></div>'; return;
					}
					list($width, $height, $type,) = @getimagesize($_FILES['avatar']['tmp_name']);
					switch ($type) {
						case IMAGETYPE_GIF:
							$ext = 'gif'; break;
						case IMAGETYPE_JPEG:
							$ext = 'jpg'; break;
						case IMAGETYPE_PNG;
							$ext = 'png'; break;
						default:
							echo '<p>' . translate('badavatarfiletype') . '</p></div>';
					}
					if (empty($width) || empty($height) || $width > $futurebb_config['avatar_max_width'] || $height > $futurebb_config['avatar_max_height']) {
						echo '<p>' . translate('imagetoobig', $futurebb_config['avatar_max_width'], $futurebb_config['avatar_max_width']) . '</p></div>'; return;
					}
					if (file_exists(FORUM_ROOT . '/static/avatars/' . $cur_user['id'] . '.' . $cur_user['avatar_extension'])) {
						unlink(FORUM_ROOT . '/static/avatars/' . $cur_user['id'] . '.' . $cur_user['avatar_extension']);
					}
					$q = new DBUpdate('users', array('avatar_extension' => $ext), 'username=\'' . $db->escape($cur_user['username']) . '\'', 'Failed to update avatar extension');
					$q->commit();
					move_uploaded_file($_FILES['avatar']['tmp_name'], FORUM_ROOT . '/static/avatars/' . $cur_user['id'] . '.' . $ext);
					header('Refresh: 0'); echo '</p></div>'; return;
				}
				echo '<form action="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($dirs[2]) . '/avatar" method="post" enctype="multipart/form-data">';
				?>
				<p><?php echo translate('currentavatar'); ?><br /><?php if (file_exists(FORUM_ROOT . '/static/avatars/' . $cur_user['id'] . '.' . $cur_user['avatar_extension'])) {
					echo '<img src="' . $base_config['baseurl'] . '/static/avatars/' . $cur_user['id'] . '.' . $cur_user['avatar_extension'] . '" alt="avatar" />';
					?>
                    <br />
                    <a href="<?php echo $base_config['baseurl']; ?>/users/<?php echo htmlspecialchars($dirs[2]); ?>/avatar/remove">Delete avatar</a>
                    <?php
				} else {
					echo translate('noavatar');
				}
				?></p>
				<h3><?php echo translate('newavatar'); ?></h3>
				<p><?php echo translate('avataruploaddesc', $futurebb_config['avatar_max_filesize'], $futurebb_config['avatar_max_width'], $futurebb_config['avatar_max_height']); ?></p>
				<p><input type="file" name="avatar" accept="image/png" /></p>
				<p><input type="submit" name="form_sent" value="<?php echo translate('save'); ?>" /></p>
				<?php
				echo '</form>'; break;
			case 'sig':
				if (!$cur_user['g_signature']) {
					httperror(404);
				}
				if (isset($_POST['form_sent'])) {
					$errors = array();
					include FORUM_ROOT . '/app_resources/includes/parser.php';
					BBCodeController::error_check($_POST['signature'], $errors);
					if ($futurebb_config['sig_max_length'] && strlen($_POST['signature']) > $futurebb_config['sig_max_length']) {
						$errors[] = translate('sigtoolong', $futurebb_config['sig_max_length'], strlen($_POST['signature']));
					}
					if ($futurebb_config['sig_max_lines'] && sizeof(explode("\n", $_POST['signature'])) > $futurebb_config['sig_max_length']) {
						$errors[] = translate('toomanysiglines', $futurebb_config['sig_max_lines'], sizeof(explode("\n", $_POST['signature'])));
					}
					if (empty($errors)) {
						$cur_user['signature'] = $_POST['signature'];
						$db->query('UPDATE `#^users` SET signature=\'' . $db->escape($_POST['signature']) . '\',parsed_signature=\'' . $db->escape(BBCodeController::parse_msg($_POST['signature'], $futurebb_config['enable_smilies'], false, $futurebb_config['enable_bbcode'])) . '\' WHERE id=' . $cur_user['id'], $futurebb_config['enable_bbcode']) or error('Failed to update sig', __FILE__, __LINE__, $db->error());
						echo '</div></div>';
						header('Refresh: 0');
						return;
					}
				}
				echo '<h3>' . translate('changesig') . '</h3>';
				if (!empty($errors)) {
					echo '<p>' . translate('errordesc') . '<ul><li>' . implode('</li><li>', $errors) . '</li></ul></p>';
				}
				echo '<form action="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($dirs[2]) . '/sig" method="post" enctype="multipart/form-data">';
				if ($cur_user['signature'] != '') {
					echo '<h4>' . translate('currentsig') . '</h4><p class="quotebox"';
					if ($futurebb_config['sig_max_height']) {
						echo ' style="max-height:' . $futurebb_config['sig_max_height'] . 'px; overflow:hidden"';
					}
					echo '>' . $cur_user['parsed_signature'] . '</p>';
				}
				?>
				<h4><?php echo translate('newsig'); ?></h4>
                <p><?php
				$restrictions = array();
                if ($futurebb_config['sig_max_length'] > 0) {
					$restrictions[] = translate('maxchars', $futurebb_config['sig_max_length']);
				}
				if ($futurebb_config['sig_max_lines'] > 0) {
					$restrictions[] = translate('maxlines', $futurebb_config['sig_max_lines']);
				}
				if ($futurebb_config['sig_max_height'] > 0) {
					$restrictions[] = translate('heightwarning', $futurebb_config['sig_max_height']);
				}
				echo implode('<br />', $restrictions);
				?>
                </p>
				<p><textarea name="signature" rows="6" cols="60"><?php
				if (isset($_POST['signature'])) {
					echo htmlspecialchars($_POST['signature']);
				} else {
					echo htmlspecialchars($cur_user['signature']);
				}
				?></textarea></p>
                <p><a href="<?php echo $base_config['baseurl']; ?>/bbcodehelp"><?php echo translate('bbcode'); ?></a>: <?php if ($futurebb_config['enable_bbcode']) echo translate('on'); else echo translate('off'); ?>, <a href="<?php echo $base_config['baseurl']; ?>/bbcodehelp#smilies"><?php echo translate('smilies'); ?></a>: <?php if ($futurebb_config['enable_smilies']) echo translate('on'); else echo translate('off'); ?>, <a href="<?php echo $base_config['baseurl']; ?>/bbcodehelp#linksimages"><?php echo translate('imgtag'); ?></a>: <?php if ($futurebb_user['g_post_links']) echo translate('on'); else echo translate('off'); ?>, <a href="<?php echo $base_config['baseurl']; ?>/bbcodehelp#linksimages"><?php echo translate('urltag'); ?></a>: <?php if ($futurebb_user['g_post_images']) echo translate('on'); else echo translate('off'); ?></p>
				<p><input type="submit" name="form_sent" value="Update" /></p>
				<?php
				echo '</form>';
				break;
			case 'admin':
				if (!$futurebb_user['g_admin_privs']) {
					httperror(403);
				}
				if (isset($_POST['form_sent'])) {
					$revoked_privs = array();
					if (isset($_POST['revoke_edit_privs'])) $revoked_privs[] = 'edit';
					if (isset($_POST['revoke_delete_privs'])) $revoked_privs[] = 'delete';
					
					$db->query('UPDATE `#^users` SET group_id=' . intval($_POST['group']) . ',restricted_privs=\'' . implode(',', $revoked_privs) . '\' WHERE username=\'' . $db->escape($user) . '\'') or error('Failed to update user group', __FILE__, __LINE__, $db->error());
					redirect($base_config['baseurl'] . '/users/' . $user . '/admin');
				}
				?>
				<form action="<?php echo $base_config['baseurl']; ?>/users/<?php echo htmlspecialchars($dirs[2]); ?>/admin" method="post" enctype="multipart/form-data">
					<h3><?php echo translate('changegroup'); ?></h3>
					<select name="group">
					<?php
					$result = $db->query('SELECT g_id,g_name FROM `#^user_groups` WHERE g_guest_group=0') or error('Failed to find groups', __FILE__, __LINE__, $db->error());
					while (list($id,$name) = $db->fetch_row($result)) {
						echo '<option value="' . $id . '"';
						if ($id == $cur_user['group_id']) {
							echo ' selected="selected"';
						}
						echo '>' . htmlspecialchars($name) . '</option>';
					}
					?>
					</select>
					<h3><?php echo translate('revokeprivs'); ?></h3>
					<p><input type="checkbox" name="revoke_edit_privs" id="revoke_edit_privs"<?php if (strstr($cur_user['restricted_privs'], 'edit')) echo ' checked="checked"'; ?> /><label for="revoke_edit_privs"><?php echo translate('editposts'); ?></label><br /><input type="checkbox" name="revoke_delete_privs" id="revoke_delete_privs"<?php if (strstr($cur_user['restricted_privs'], 'delete')) echo ' checked="checked"'; ?> /><label for="revoke_delete_privs"><?php echo translate('deleteposts'); ?></label></p>
					<?php if ($cur_user['deleted']) { ?>
					<p><a href="<?php echo $base_config['baseurl']; ?>/users/<?php echo htmlspecialchars($dirs[2]); ?>/restore"><?php echo translate('restoreuser'); ?></a></p>
					<?php } else { ?>
					<p><a href="<?php echo $base_config['baseurl']; ?>/users/<?php echo htmlspecialchars($dirs[2]); ?>/delete" onclick="return confirm(\'<?php echo translate('deletewarning'); ?>\');"><?php echo translate('deleteuser'); ?></a></p>
					<?php } ?>
					<p><input type="submit" name="form_sent" value="<?php echo translate('save'); ?>" /></p>
				</form>
				<?php
				break;
			case 'delete':
				if (!$futurebb_user['g_admin_privs']) {
					httperror(403);
				}
				$db->query('UPDATE `#^users` SET deleted=1 WHERE id=' . $cur_user['id']) or error('Failed to delete user', __FILE__, __LINE__, $db->error());
				header('Location: ' . $base_config['baseurl'] . '/users/' . $dirs[2]);
				return;
			case 'restore':
				if (!$futurebb_user['g_admin_privs']) {
					httperror(403);
				}
				$db->query('UPDATE `#^users` SET deleted=0 WHERE id=' . $cur_user['id']) or error('Failed to delete user', __FILE__, __LINE__, $db->error());
				header('Location: ' . $base_config['baseurl'] . '/users/' . $dirs[2]);
				return;
			case 'reports':
				echo '<h2>' . translate('reports') . '</h2>';
				if (isset($_GET['withdraw'])) {
					$db->query('UPDATE `#^reports` SET status=\'withdrawn\',zapped=' . time() . ',zapped_by=' . $cur_user['id'] . ' WHERE id=' . intval($_GET['withdraw']) . ' AND reported_by=' . $cur_user['id'] . ' AND status=\'unread\'') or error('Failed to withdraw report', __FILE__, __LINE__, $db->error());
					redirect($base_config['baseurl'] . '/users/' . $dirs[2] . '/reports');
				}
				$result = $db->query('SELECT
		r.status,r.id,r.post_id,r.post_type,r.reason,r.time_reported,r.zapped,
		t.subject,n.type,n.contents,n.arguments,n.send_time,
		t.url AS turl,
		f.name AS fname,
		f.url AS furl,
		u.username AS reported_by,
		z.username AS zapped_by FROM `#^reports` AS r
		LEFT JOIN `#^posts` AS p ON p.id=r.post_id
		LEFT JOIN `#^topics` AS t ON t.id=p.topic_id
		LEFT JOIN `#^forums` AS f ON f.id=t.forum_id
		LEFT JOIN `#^users` AS u ON u.id=r.reported_by
		LEFT JOIN `#^users` AS z ON z.id=r.zapped_by
		LEFT JOIN `#^notifications` AS n ON n.id=r.post_id
		WHERE r.reported_by=' . $cur_user['id'] . ' ORDER BY r.time_reported DESC LIMIT 20') or error('Failed to get new reports', __FILE__, __LINE__, $db->error());
				if ($db->num_rows($result)) {
					while ($cur_report = $db->fetch_assoc($result)) {
						echo '<div class="reportbox">
						<p>';
						if($cur_report['post_type'] == 'post') {
							echo '<a href="' . $base_config['baseurl'] . '/' . $cur_report['furl'] . '">' . htmlspecialchars($cur_report['fname']) . '</a> &raquo; <a href="' . $base_config['baseurl'] . '/' . $cur_report['furl'] . '/' . $cur_report['turl'] . '">' . htmlspecialchars($cur_report['subject']) . '</a> &raquo; <a href="' . $base_config['baseurl'] . '/posts/' . $cur_report['post_id'] . '">Post #' . $cur_report['post_id'] . '</a><br />';
						} elseif($cur_report['post_type'] == 'msg') {
								echo '</p><p class="whitebox">';
								switch ($cur_report['type']) {
									case 'warning':
										echo '<img src="' . $base_config['baseurl'] . '/static/img/msg_warning.png" alt="warning" width="22" />';
										echo '<span class="notifications_count">#' . $cur_report['post_id'] . '</span>';
										echo translate('user_sent_warning', '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($cur_report['arguments']) . '">' . htmlspecialchars($cur_report['arguments']) . '</a>') . '<br />' . $cur_report['contents'];
										break;
									case 'msg':
										echo '<img src="' . $base_config['baseurl'] . '/static/img/msg_msg.png" alt="message" width="22" />';
										echo '<span class="notifications_count">#' . $cur_report['post_id'] . '</span>';
										echo translate('user_sent_msg', '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($cur_report['arguments']) . '">' . htmlspecialchars($cur_report['arguments']) . '</a>') . '<br />' . $cur_report['contents'];
										break;
									case 'notification':
										$parts = explode(',', $cur_report['arguments'], 2);
										echo '<img src="' . $base_config['baseurl'] . '/static/img/msg_notif.png" alt="notification" width="22" />';
										echo '<span class="notifications_count">#' . $cur_report['post_id'] . '</span> ';
										echo translate('user_mentioned_you', '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($parts[0]) . '">' . htmlspecialchars($parts[0]) . '</a>') .
						'<a href="' . $base_config['baseurl'] . '/posts/' . $cur_report['contents'] . '">' . htmlspecialchars($parts[1]) . '</a>';
										break;
									default:
										echo '<span class="notifications_count" style="font-size: 12px;>#' . $cur_report['post_id'] . '</span>';
										echo translate('couldnot_display_notif');
								}
							if ($cur_report['send_time'] != 0) echo '<br /><em>' . translate('sent') . ' ' . user_date($cur_report['send_time']) . '</em>';
							echo '</p><p>';
						}
						echo 'Reported on ' . user_date($cur_report['time_reported']) . '</p><p>Reason<br /><b>' . htmlspecialchars($cur_report['reason']) . '</b></p><p>Status: <strong>';
						switch ($cur_report['status']) {
							case 'unread':
								echo '<span style="color: #555;">' . translate('pending') . '</span>'; break;
							case 'review':
								echo translate('underreview'); break;
							case 'reject':
								echo '<span style="color: #A00;">' . translate('rejected') . '</span>'; break;
							case 'accept':
								echo '<span style="color: #0A0;">' . translate('accepted') . '</span>'; break;
							case 'noresp':
								echo translate('noresp'); break;
							case 'withdrawn':
								echo translate('withdrawnbyreporter'); break;
							default:
								echo 'Unknown'; break;
						}
						echo '</strong>';
						if ($cur_report['status'] == 'unread') {
							echo '<br /><a href="?withdraw=' . $cur_report['id'] . '">' . translate('withdrawreport') . '</a>';
						}
						echo '
						</p>
						
					</div>';
					}
				}
				break;
			case 'warnings':
				if (!$futurebb_user['g_admin_privs']) {
					httperror(403);
				}
				$result = $db->query('SELECT send_time,contents,arguments FROM `#^notifications` WHERE type=\'warning\' AND user=' . $cur_user['id'] . ' ORDER BY send_time DESC LIMIT 20') or error('Failed to get warnings', __FILE__, __LINE__, $db->error());
				if (!$db->num_rows($result)) {
					echo '<p>' . translate('none') . '</p>';
				} else {
					echo '<table border="0">
						<tr>
							<th>' . translate('time') . '</th>
							<th>' . translate('sentby') . '</th>
							<th>' . translate('message') . '</th>
						</tr>';
					while ($msg = $db->fetch_assoc($result)) {
						echo '<tr><td>' . user_date($msg['send_time']) . '</td><td>' . htmlspecialchars($msg['arguments']) . '</td><td>' . $msg['contents'] . '</td></tr>';
					}
					echo '</table>';
				}
				break;
			case 'msgs':
				$result = $db->query('SELECT n.send_time,n.contents,n.arguments,u.username AS recipient FROM `#^notifications` AS n LEFT JOIN `#^users` AS u ON u.id=n.user WHERE type=\'msg\' AND (arguments=\'' . $db->escape($cur_user['username']) . '\' OR user=' . $cur_user['id'] . ') ORDER BY send_time DESC LIMIT 20') or error('Failed to get message', __FILE__, __LINE__, $db->error());
				if (!$db->num_rows($result)) {
					echo '<p>' . translate('none') . '</p>';
				} else {
					echo '<table border="0">
						<tr>
							<th>' . translate('time') . '</th>
							<th>' . translate('sentby') . '</th>
							<th>' . translate('sentto') . '</th>
							<th>' . translate('message') . '</th>
						</tr>';
					while ($msg = $db->fetch_assoc($result)) {
						echo '<tr><td>' . user_date($msg['send_time']) . '</td><td>' . htmlspecialchars($msg['arguments']) . '</td><td>' . htmlspecialchars($msg['recipient']) . '</td><td>' . $msg['contents'] . '</td></tr>';
					}
					echo '</table>';
				}
				break;
			default:
				httperror(404);
		}
	} else {
		//view-only
		?>
        <div style="padding-left:5px">
            <h2><?php echo htmlspecialchars($user); ?></h2>
            <table border="0" class="optionstable">
                <tr>
                    <th><?php echo translate('numposts'); ?></th>
                    <td><?php echo $cur_user['num_posts']; ?></td>
                </tr>
                <tr>
                    <th><?php echo translate('timezone'); ?></th>
                    <td><?php echo 'GMT' . ($cur_user['timezone'] >= 0 ? '+': '') . $cur_user['timezone']; ?></td>
                </tr>
                <tr>
                    <th><?php echo translate('dateregistered'); ?></th>
                    <td><?php echo user_date($cur_user['registered']); ?></td>
                </tr>
                <tr>
                	<th><?php echo translate('signature'); ?></th>
                    <td><?php echo $cur_user['parsed_signature']; ?></td>
                </tr>
                <?php
				if (file_exists(FORUM_ROOT . '/static/avatars/' . $cur_user['id'] . '.' . $cur_user['avatar_extension'])) {
					?>
                    <tr>
                    	<th><?php echo translate('avatar'); ?></th>
                        <td><?php echo '<img src="' . $base_config['baseurl'] . '/static/avatars/' . $cur_user['id'] . '.' . $cur_user['avatar_extension'] . '" alt="avatar" />'; ?></td>
                    </tr>
                   	<?php
				}
				?>
            </table>
        <?php
		PMBox();
		?>
        </div>
        <?php
	}
	?>
	</div>
</div>