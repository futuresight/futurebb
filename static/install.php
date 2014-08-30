<?php
define('FORUM_ROOT', realpath(dirname(__FILE__) . '/..'));
if (file_exists(FORUM_ROOT . '/config.xml')) {
	die;
}
if (isset($_COOKIE['install_cookie'])) {
	$cookie = base64_decode($_COOKIE['install_cookie']);
	$rows = explode(chr(0), $cookie);
	foreach ($rows as $val) {
		$cols = explode(chr(1), $val);
		if ($cols[0] != '') {
			$config[$cols[0]] = $cols[1];
		}
	}
} else {
	$cookie = null;
}
if (isset($_GET['downloadcfg'])) {
	header('Content-disposition: attachment; filename=config.xml');
	echo $config['config.xml'];
	die;
}
function update_config() {
	global $config;
	$str = '';
	foreach ($config as $key => $val) {
		$str .= chr(0) . $key . chr(1) . $val;
	}
	$str = base64_encode($str);
	setcookie('install_cookie', $str);
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>FutureBB Installation</title>
</head>

<body>
	<h1>FutureBB Installation</h1>
	<?php
	if (!function_exists('mysqli_connect')) {
		echo '<p>It appears your server does not have any of the database drivers we support.</p></body></html>';
	}
	if (isset($config['ready'])) {
		echo '<p>Your forum is pretty much ready! You just need to download your <a href="?downloadcfg">config.xml</a> file and place it in the forum root.</p>';
	} else if (isset($_POST['form_sent_cfg'])) {
		include FORUM_ROOT . '/app_resources/database/' . $config['db_type'] . '.php';
		include FORUM_ROOT . '/app_resources/includes/functions.php';
		$db_info = array('host' => $config['db_host'], 'username' => $config['db_user'], 'password' => $config['db_pass'], 'name' => $config['db_name'], 'prefix' => $config['db_prefix']);
		echo '<p>Testing database ';
		$db = new db_mysqli($db_info);
		if (!$db->link) {
			echo '<b style="color:#F00">[Failed]</b></p></body></html>'; die;
		}
		echo '<b style="color:#0A0">[Success]</b></p>';
		echo '<p>Updating .htaccess file ';
		if (!file_exists(FORUM_ROOT . '/htaccess.tpl')) {
			echo '<b style="color:#F00">[Failed]</b></p></body></html>'; die;
		}
		$data = file_get_contents(FORUM_ROOT . '/htaccess.tpl');
		$basepath = str_replace('static/install.php', '', $_SERVER['REQUEST_URI']);
		$data = str_replace('<baseurl>', $basepath, $data);
		@file_put_contents(FORUM_ROOT . '/.htaccess', $data);
		if (!strstr(file_get_contents(FORUM_ROOT . '/.htaccess'), $basepath)) {
			echo '<b style="color:#F00">[Failed]</b></p></body></html>'; die;
		}
		echo '<b style="color:#0A0">[Success]</b></p>';
		$db->query('TRUNCATE TABLE `#^config`') or error('Failed to wipe config', __FILE__, __LINE__, $db->error());
		set_config('board_title', $_POST['config']['board_title']);
		set_config('admin_email', $_POST['config']['admin_email']);
		set_config('announcement_text', '');
		set_config('announcement_enable', 0);
		set_config('default_user_group', 3);
		set_config('censoring', '');
		set_config('footer_text', '');
		
		$db->query('TRUNCATE TABLE `#^users`') or error('Failed to wipe users table', __FILE__, __LINE__, $db->error());
		$db->query('INSERT INTO `#^users`(id,username,group_id,timezone) VALUES(0,\'Guest\',2,0)') or error('Failed to create guest user', __FILE__, __LINE__, $db->error());
		$db->query('UPDATE `#^users` SET id=0 WHERE username=\'Guest\'') or error('Failed to zero ID of guest user', __FILE__, __LINE__, $db->error());
		$db->query('INSERT INTO `#^users`(username,password,email,registered,registration_ip,group_id,last_visit,timezone) VALUES(\'' . $db->escape($_POST['username']) . '\',\'' . futurebb_hash($_POST['pwd1']) . '\',\'' . $db->escape($_POST['email']) . '\',' . time() . ',\'' . $db->escape($_SERVER['REMOTE_ADDR']) . '\',1,' . time() . ',0)') or error('Failed to create admin user', __FILE__, __LINE__, $db->error());
		
		$db->query('TRUNCATE TABLE `#^user_groups') or error('Failed to wipe user groups', __FILE__, __LINE__, $db->error());
		$db->query('INSERT INTO `#^user_groups`(g_permanent,g_guest_group,g_name,g_title,g_admin_privs,g_mod_privs,g_edit_posts,g_delete_posts,g_signature) VALUES(1,0,\'Administrators\',\'Administrator\',1,1,1,1,1)') or error('Failed to create admin user group', __FILE__, __LINE__, $db->error());
		$db->query('INSERT INTO `#^user_groups`(g_permanent,g_guest_group,g_name,g_title,g_admin_privs,g_mod_privs,g_edit_posts,g_delete_posts,g_signature) VALUES(1,1,\'Guests\',\'Guest\',0,0,0,0,0)') or error('Failed to create guest user group', __FILE__, __LINE__, $db->error());
		$db->query('INSERT INTO `#^user_groups`(g_permanent,g_guest_group,g_name,g_title,g_admin_privs,g_mod_privs,g_edit_posts,g_delete_posts,g_signature) VALUES(1,0,\'Members\',\'Member\',0,0,1,1,1)') or error('Failed to create member user group', __FILE__, __LINE__, $db->error());
		
		$config['ready'] = 1;
		update_config();
		
		header('Refresh: 0');
	} else if (isset($config['config.xml'])) {
		?>
		<form action="install.php" method="post" enctype="multipart/form-data">
			<h2>Board settings</h2>
			<table border="0">
				<tr>
					<td>Board title</td>
					<td><input type="text" name="config[board_title]" value="My FutureBB Forum" /></td>
				</tr>
				<tr>
					<td>Admin email</td>
					<td><input type="text" name="config[admin_email]" value="webmaster@<?php echo $_SERVER['HTTP_HOST']; ?>" /></td>
				</tr>
			</table>
			<h2>Administrator user</h2>
			<table border="0">
				<tr>
					<td>Username</td>
					<td><input type="text" name="username" /></td>
				</tr>
				<tr>
					<td>Password</td>
					<td><input type="password" name="pwd1" /></td>
				</tr>
				<tr>
					<td>Confirm password</td>
					<td><input type="password" name="pwd2" /></td>
				</tr>
				<tr>
					<td>Email address</td>
					<td><input type="text" name="email" /></td>
				</tr>
				<tr>
					<td>Time zone</td>
					<td><select name="timezone"><?php for ($i = -12; $i <= 12; $i++) echo '<option value="' . $i . '">GMT' . ($i >= 0 ? '+': '') . $i . '</option>'; ?></select></td>
				</tr>
			</table>
			<p><input type="submit" name="form_sent_cfg" value="Continue" /></p>
		</form>
		<?php
	} else if (isset($_POST['form_sent_db'])) {
		if ($_POST['pwd1'] != $_POST['pwd2']) {
			echo '<p>Passwords do not match. Hit the back button to try again.</p></body></html>'; die;
		}
		$xml = new SimpleXMLElement('<?xml version="1.0" ?><config></config>');
		$db_xml = $xml->addChild('cfgset');
		$db_xml->addAttribute('type', 'database');
		$db_xml->addChild('type', $_POST['db_type']);
		$db_xml->addChild('host', $_POST['host']);
		$db_xml->addChild('username', $_POST['username']);
		$db_xml->addChild('password', $_POST['pwd1']);
		$db_xml->addChild('name', $_POST['name']);
		$db_xml->addChild('prefix', $_POST['prefix']);
		
		$srv_xml = $xml->addChild('cfgset');
		$srv_xml->addAttribute('type', 'server');
		if (!empty($_SERVER['https'])) {
			$baseurl = 'https://';
		} else {
			$baseurl = 'http://';
		}
		$baseurl .= $_SERVER['HTTP_HOST'];
		$basepath = str_replace('/static/install.php', '', $_SERVER['REQUEST_URI']);
		$baseurl .= $basepath;
		$srv_xml->addChild('baseurl', $baseurl);
		$srv_xml->addChild('basepath', $basepath);
		$srv_xml->addChild('cookie_name', 'futurebb_cookie_' . substr(md5(time()), 0, 5));
		
		$config['config.xml'] = $xml->asXML();
		$config['db_host'] = $_POST['host'];
		$config['db_type'] = $_POST['db_type'];
		$config['db_user'] = $_POST['username'];
		$config['db_pass'] = $_POST['pwd1'];
		$config['db_name'] = $_POST['name'];
		$config['db_prefix'] = $_POST['prefix'];
		update_config();
		header('Refresh: 0');
	} else {
	?>
	<p>No config.xml file was detected, so you have been sent to this page to configure your forum.</p>
	<p>First, if you have not already, please upload the provided SQL file to your database to set it up.</p>
	<form action="install.php" method="post" enctype="multipart/form-data">
		<h2>Database setup</h2>
		<table border="0">
			<tr>
				<td>Type</td>
				<td><select name="db_type"><option value="mysqli">MySQL Improved</option><option value="mysql" disabled="disabled">MySQL Standard (not implemented)</option></select></td>
			</tr>
			<tr>
				<td>Host</td>
				<td><input type="text" name="host" /></td>
			</tr>
			<tr>
				<td>Username</td>
				<td><input type="text" name="username" /></td>
			</tr>
			<tr>
				<td>Password</td>
				<td><input type="password" name="pwd1" /></td>
			</tr>
			<tr>
				<td>Confirm password</td>
				<td><input type="password" name="pwd2" /></td>
			</tr>
			<tr>
				<td>Name</td>
				<td><input type="text" name="name" /></td>
			</tr>
			<tr>
				<td>Prefix</td>
				<td><input type="text" name="prefix" /></td>
			</tr>
		</table>
		<p><input type="submit" name="form_sent_db" value="Continue" /></p>
	</form>
	<?php
	} 
	?>
</body>
</html>