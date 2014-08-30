<?php
$page_title = translate('usersonline');
?>
<p class="aligncenter"><?php
$result = $db->query('SELECT id,username FROM `#^users` WHERE last_page_load>' . (time() - $futurebb_config['online_timeout']) . ' AND username<>\'Guest\' ORDER BY RAND() LIMIT 10') or error('Failed to get online list', __FILE__, __LINE__, $db->error());
while (list($id,$username) = $db->fetch_row($result)) {
	echo '<div style="display: block;" class="user-online"><p style="display: inline;">
	<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($username) . '">';
	if (file_exists(FORUM_ROOT . '/static/avatars/' . $id . '.png')) {
		echo '<img src="' . $base_config['baseurl'] . '/static/avatars/' . $id . '.png" class="avatar" alt="user avatar" /><br />';
	}
	echo htmlspecialchars($username) . '</a></p></div>';
}
?></p>