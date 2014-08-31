<?php
$_GET['auth'] = 1;
$_POST['accesscode'] = 'futurebb-fstech';
define('FORUM_ROOT', realpath(dirname(__FILE__) . '/../'));
include FORUM_ROOT . '/app_resources/includes/startup.php';
$r1 = $db->query('SHOW TABLES') or error('Failed to get table list', __FILE__, __LINE__, $db->error());
while (list($name) = $db->fetch_row($r1)) {
	if (strstr($name, $db->prefix)) {
		if ($name != 'futurebb_config') {
		echo 'DROP TABLE IF EXISTS `' . $name . '`;' . "\n";
			$r2 = $db->query('SHOW CREATE TABLE `' . $name . '`') or error('Failed to get structure', __FILE__, __LINE__, $db->error());
			$info = $db->fetch_assoc($r2);
			echo $info['Create Table'] . ';' . "\n";
		}
		$r2 = $db->query('SELECT * FROM `' . $name . '`') or error('Failed to get data', __FILE__, __LINE__, $db->error());
		$data = array();
		while ($info = $db->fetch_row($r2)) {
			$str = '(';
			foreach ($info as $val) {
				if ($val === null) {
					$str .= 'NULL';
				} else if (is_int($val)) {
					$str .= $val;
				} else {
					$str .= '\'' . $db->escape($val) . '\'';
				}
				$str .= ',';
			}
			$str = substr($str, 0, strlen($str) - 1) . ')';
			$data[] = $str;
		}
		if (!empty($data)) {
			echo 'INSERT IGNORE INTO `' . $name . '` VALUES' . implode(',', $data) . ';' . "\n";
		}
	}
}
echo '; UPDATE `futurebb_users` SET id=0 WHERE username=\'Guest\'';