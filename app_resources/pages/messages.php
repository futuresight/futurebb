<?php
if (trim($dirs[2]) == '') {
	httperror(404);
}
$q = new DBSelect('users', array('id'), 'rss_token=\'' . $db->escape($dirs[2]) . '\'', 'Failed to find user');
$result = $q->commit();
if (!$db->num_rows($result)) {
	httperror(404);
}
list($uid) = $db->fetch_row($result);
$futurebb_user['id'] = $uid;
LoginController::LoadNotifications();
?>

<table<?php if (!isset($_GET['nopage'])) echo ' width="100%"'; ?> border="0">
<?php
if (sizeof($futurebb_user['notifications']) == 0) {
	echo '<tr><td>' . translate('nonotifs') . '</td></tr>';
}

foreach($futurebb_user['notifications'] as $entry) {
	echo '<tr><td>';
	switch ($entry['type']) {
		case 'warning':
			if($entry['read_time'] == 0) {
				echo '<img src="' . $base_config['baseurl'] . '/static/img/msgu_warning.png" alt="warning" width="22" />';
			} else {
				echo '<img src="' . $base_config['baseurl'] . '/static/img/msg_warning.png" alt="warning" width="22" />';
			}
			break;
		case 'msg':
			if($entry['read_time'] == 0) {
				echo '<img src="' . $base_config['baseurl'] . '/static/img/msgu_msg.png" alt="message" width="22" />';
			} else {
				echo '<img src="' . $base_config['baseurl'] . '/static/img/msg_msg.png" alt="message" width="22" />';
			}
			break;
		case 'notification':
			if($entry['read_time'] == 0) {
				echo '<img src="' . $base_config['baseurl'] . '/static/img/msgu_notif.png" alt="notification" width="22" />';
			} else {
				echo '<img src="' . $base_config['baseurl'] . '/static/img/msg_notif.png" alt="notification" width="22" />';
			}
			break;
		default:
			echo '<img src="' . $base_config['baseurl'] . '/static/img/msg_msg.png" alt="message" width="22" />';
	}
	echo '</td><td>' . $entry['contents'] . '</td>';
	echo '<td style="width: 140px;">' . user_date($entry['send_time']) . '</td>';
	echo '<td style="width: 120px;"><a href="' . $base_config['baseurl'] . '/report/message/' . $entry['id'] . '">';
	if($entry['type'] == 'warning') {
		echo translate('send_appeal');
	} else {
		echo translate('report_abuse');
	}
	echo '</a></td>';
	echo '</tr>';
}

// Set all notifications to read
$db->query('UPDATE `#^notifications` SET read_time = ' . time() . ', read_ip = \'' . $db->escape($_SERVER['REMOTE_ADDR']) . '\' WHERE user = ' . $futurebb_user['id'] . ' AND read_time = 0');
?>
</table>
<?php
if (isset($_GET['nopage'])) {
	$db->close();
	die;
}
?>