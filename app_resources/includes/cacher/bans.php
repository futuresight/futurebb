<?php
function cache_bans() {
	global $db;
	$result = $db->query('SELECT id,username,ip,expires,message FROM `#^bans`') or enhanced_error('Unable to fetch bans', true);
	$db->query('DELETE FROM `#^bans` WHERE expires<' . time()) or enhanced_error('Failed to delete old bans', true);
	$banned_usernames = array();
	$banned_ips = array();
	$ban_expires = array();
	while ($cur_ban = $db->fetch_assoc($result)) {
		$ban_expires[$cur_ban['id']] = $cur_ban['expires'];
		if ($cur_ban['ip'] != '') {
			$ips = explode(',', $cur_ban['ip']);
			foreach ($ips as $ip) {
				$banned_ips[$ip] = $cur_ban['id'];
			}
		}
		if ($cur_ban['username'] != '') {
			$banned_usernames[$cur_ban['username']] = $cur_ban['id'];
		}
	}
	$output = '<?php' . "\n" . 
			'$banned_usernames = ' . var_export($banned_usernames, true) . ';' . "\n" .
			'$banned_ips = ' . var_export($banned_ips, true) . ';' . "\n" . 
			'$ban_expires = ' . var_export($ban_expires, true) . ';' . "\n";
	file_put_contents(FORUM_ROOT . '/app_config/cache/bans.php', $output);
}