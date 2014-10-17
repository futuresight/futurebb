<?php
if ($futurebb_user['id'] != 0) {
	redirect($base_config['baseurl']);
	return;
}
$page_title = translate('login');
if (isset($_POST['form_sent'])) {
	$result = $db->query('SELECT password,id,activate_key,g_admin_privs FROM `#^users` AS u LEFT JOIN `#^user_groups` AS g ON g.g_id=u.group_id WHERE username=\'' . $db->escape($_POST['username']) . '\' AND deleted=0') or error('Failed to check user', __FILE__, __LINE__, $db->error());
	list($password, $id, $key, $admin_privs) = $db->fetch_row($result);
	if ($futurebb_config['maintenance'] && !$admin_privs) {
		echo '<p>' . translate('badmaintlogin') . '</p>';
		return;
	}
	if ($key != null) {
		if (!isset($_POST['activation_code'])) {
			echo '<p>' . translate('notactivated') . '</p>';
			return;
		}
		if (isset($_POST['activation_code']) && $_POST['activation_code'] != $key) {
			echo '<p>' . translate('badactivation') . '</p>';
			return;
		}
		if (isset($_POST['activation_code']) && $_POST['activation_code'] == $key) {
			$db->query('UPDATE `#^users` SET activate_key=NULL WHERE id=' . $id) or error('Failed to activate account', __FILE__, __LINE__, $db->error());
		}
	}
	if ($password == futurebb_hash($_POST['password'])) {
		LoginController::LogInUser($id, $password, $_SERVER['HTTP_USER_AGENT'], isset($_POST['remember']));
		redirect($base_config['baseurl']); return;
	} else {
		echo '<p>' . translate('badlogin') . '</p>';
		return;
	}
}
if (isset($_GET['forgot'])) {
	if (isset($_POST['forgot_form_sent'])) {
		$result = $db->query('SELECT id,username,email FROM `#^users` WHERE email=\'' . $db->escape($_POST['email']) . '\'') or error('Failed to find email', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result)) {
			echo '<p>' . translate('emailnotfound') . '<br /><a href="' . $base_config['baseurl'] . '/login?forgot">' . translate('tryagain') . '</a></p>';
			return;
		}
		$user = $db->fetch_assoc($result);
		$key = md5(time() . rand() . $user['username']);
		$db->query('UPDATE `#^users` SET recover_key=\'' . $key . '\' WHERE id=' . $user['id']) or error('Failed to update user', __FILE__, __LINE__, $db->error());
		echo '<p>' . translate('recoveremailsent') . '</p>';
		mail($user['email'], $futurebb_config['board_title'] . ' password recovery', translate('recoveremailcontent') . $base_config['baseurl'] . '/login?reset_pass&username=' . $user['username'] . '&key=' . $key, 'From: FutureBB Account Services <' . $futurebb_config['admin_email'] . '>' . "\r\n" . 'Content-type: text/plain');
		return;
	} else {
		?>
		<h2><?php echo translate('forgotpwd'); ?></h2>
		<form action="<?php echo $base_config['baseurl']; ?>/login?forgot" method="post" enctype="multipart/form-data">
			<p><?php echo translate('emailaddr'); ?> <input type="text" name="email" /></p>
			<p><input type="submit" name="forgot_form_sent" value="<?php echo translate('continue'); ?>" /></p>
		</form>
		<?php
	}
	return;
}
if (isset($_GET['reset_pass'])) {
	if (!isset($_GET['username'])) {
		httperror(404);
	}
	$result = $db->query('SELECT id FROM `#^users` WHERE username=\'' . $db->escape($_GET['username']) . '\' AND recover_key=\'' . $db->escape($_GET['key']) . '\'') or error('Failed to find user', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result)) {
		httperror(404);
	}
	list($id) = $db->fetch_row($result);
	if (isset($_POST['reset_form_sent'])) {
		if ($_POST['pass1'] != $_POST['pass2']) {
			echo '<p>' . translate('passnomatch') . '</p>';
			return;
		}
		$db->query('UPDATE `#^users` SET password=\'' . $db->escape(futurebb_hash($_POST['pass1'])) . '\',recover_key=NULL WHERE id=' . $id) or error('Failed to update password', __FILE__, __LINE__, $db->error());
		LoginController::LogInUser($id, futurebb_hash($_POST['pass1']), $_SERVER['HTTP_USER_AGENT'], true);
		echo '<p>' . translate('pwdresetsuccess') . '<br /><a href="' . $base_config['baseurl'] . '">' . translate('login') . '</a></p>';
		return;
	} else {
		?>
		<form action="<?php echo $base_config['baseurl']; ?>/login?reset_pass&amp;username=<?php echo htmlspecialchars($_GET['username']); ?>&amp;key=<?php echo htmlspecialchars($_GET['key']); ?>" method="post" enctype="multipart/form-data">
			<h2><?php echo translate('resetpass'); ?></h2>
			<table border="0">
				<tr>
					<td><?php echo translate('user'); ?></td>
					<td><?php echo htmlspecialchars($_GET['username']); ?></td>
				</tr>
				<tr>
					<td><?php echo translate('password'); ?></td>
					<td><input type="password" name="pass1" /></td>
				</tr>
				<tr>
					<td><?php echo translate('confirmpwd'); ?></td>
					<td><input type="password" name="pass2" /></td>
				</tr>
			</table>
			<p><input type="submit" name="reset_form_sent" value="<?php echo translate('continue'); ?>" /></p>
		</form>
		<?php
	}
	return;
}
?>
<h2><?php echo translate('login'); ?></h2>
<form action="<?php echo $base_config['baseurl']; ?>/login" method="post" enctype="multipart/form-data">
	<table border="0" class="in_form">
		<tr>
			<th><?php echo translate('username'); ?></th>
			<td><input type="text" name="username" /></td>
		</tr>
		<tr>
			<th><?php echo translate('password'); ?></th>
			<td><input type="password" name="password" /></td>
		</tr>
		<?php if (isset($_GET['activate'])) { ?>
		<tr>
			<th><?php echo translate('activationcode'); ?></th>
			<td><input type="text" name="activation_code" /></td>
		</tr>
		<?php } ?>
	</table>
	<p><input type="checkbox" name="remember" id="remember" value="1" /><label for="remember"><?php echo translate('rememberme'); ?></label></p>
	<p><input type="submit" name="form_sent" value="<?php echo translate('login'); ?>" /></p>
	<?php if ($futurebb_config['verify_registrations'] && !isset($_GET['activate'])) { ?>
	<p><a href="?activate"><?php echo translate('activateacct'); ?></a></p>
	<?php } ?>
	<p><a href="?forgot"><?php echo translate('forgotpwd'); ?></a></p>
</form>