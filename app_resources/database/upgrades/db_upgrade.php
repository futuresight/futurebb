<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Database upgrade utility</title>
<style type="text/css">
#loginform {
	border:1px solid #000;
	padding-left:10px;
}
body {
	font-family:Arial, Helvetica, sans-serif;
}
</style>
</head>

<body>
	<h1>FutureBB Database Upgrade</h1>
	<div>
    	<?php
		if (isset($_POST['form_sent'])) {
			$result = $db->query('SELECT 1 FROM `#^users` AS u LEFT JOIN `#^user_groups` AS g ON g.g_id=u.group_id WHERE username=\'' . $db->escape($_POST['username']) . '\' AND password=\'' . futurebb_hash($_POST['password']) . '\' AND g.g_admin_privs=1') or error('Failed to check login');
			if ($db->num_rows($result)) {
				?>
                <ul>
                <?php
				//include all files between old revision and new revision
				for ($i = (isset($futurebb_config['db_version']) ? $futurebb_config['db_version'] : 0) + 1; $i <= DB_VERSION; $i++) {
					include FORUM_ROOT . '/app_resources/database/upgrades/' . $i . '.php';
				}
				?>
                </ul>
                <p>Database upgrade success! You may now <a href="<?php echo $base_config['baseurl']; ?>">visit your forum</a>.</p>
                <?php
			} else {
				?>
            <p>Your login was not valid or you are not an administrator. Please hit your browser's back button and try again.</p>
            	<?php
			}
		} else {
			if ($futurebb_user['language'] != 'English') {
				?>
			<p style="font-weight:bold">This page is only available in English. Unfortunately, no translations are available. Once the database upgrade is complete, all translations will work again.</p>
				<?
			}
			?>
			<p>Your FutureBB database appears to be out of date. If you are an administrator, you can use this utility to upgrade it. If you are not an administrator, you will not be able to access this forum until it is upgraded.</p>
			<form action="<?php echo $base_config['baseurl']; ?>/" method="post" enctype="multipart/form-data" id="loginform">
				<p>Administrator login</p>
				<table border="0">
					<tr>
						<td>Username</td>
						<td><input type="text" name="username" /></td>
					</tr>
					<tr>
						<td>Password</td>
						<td><input type="password" name="password" /></td>
					</tr>
				</table>
				<p><input type="submit" value="Upgrade" name="form_sent" /></p>
			</form>
    	<?php
		}
		?>
    </div>
</body>
</html>
