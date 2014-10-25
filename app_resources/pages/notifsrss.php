<?php
header('Content-type: text/xml');
$type = $dirs[2];
translate('<addfile>', 'rss');
$output = '<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0">

<channel>
	<title><$title></title>
	<description><$description></description>	
	<link>' . $base_config['baseurl'] . '/messages</link>
	<generator>FutureBB</generator>';
	
if (!isset($dirs[2])) {
	httperror(404);
}
$q = new DBSelect('users', array('username', 'id'), 'rss_token=\'' . $db->escape($dirs[2]) . '\'', 'Failed to find users');
$result = $q->commit();
if (!$db->num_rows($result)) {
	httperror(404);
}
list($username, $id) = $db->fetch_row($result);

$q = new DBSelect('notifications', array('type', 'send_time', 'contents', 'arguments'), 'user=' . $id, 'Failed to get notification list');
$q->set_order('send_time DESC');
$q->set_limit('20');
$result = $q->commit();

$title = translate('notifsfor', $username);

while ($notif = $db->fetch_assoc($result)) {
	switch ($notif['type']) {
		case 'warning':
			$type = 'Warning';
			break;
		case 'msg':
			$type = 'Message';
			break;
		case 'notification':
			$type = 'Notification';
			break;
		default:
			echo '<img src="' . $base_config['baseurl'] . '/static/img/msg_msg.png" alt="message" width="22" />';
	}
	if($notif['type'] == 'warning') {
		$contents_raw = translate('user_sent_warning', '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($notif['arguments']) . '">' . htmlspecialchars($notif['arguments']) . '</a>') . '<br />' . $notif['contents'];
		$author = $notif['arguments'];
	} elseif($notif['type'] == 'msg') {
		$contents_raw = translate('user_sent_msg', '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($notif['arguments']) . '">' . htmlspecialchars($notif['arguments']) . '</a>') . '<br />' . $notif['contents'];
		$author = $notif['arguments'];
	} elseif($notif['type'] == 'notification') {
		$parts = explode(',', $notif['arguments'], 2);
		$contents_raw = translate('user_mentioned_you', '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($parts[0]) . '">' . htmlspecialchars($parts[0]) . '</a>') .
			'<a href="' . $base_config['baseurl'] . '/posts/' . $notif['contents'] . '">' . htmlspecialchars($parts[1]) . '</a>';
		$author = $parts[0];
	} else {
		$contents_raw = translate('couldnot_display_notif');
	}
	$output .= "\n\t" . '<item>' . "\n\t\t" . '<title><![CDATA[' . 'stuff' . ']]></title>';
	$output .= "\n\t\t" . '<pubDate>' . gmdate('D, d M Y H:i:s', $notif['send_time']) . ' +0000</pubDate>';
	$output .= "\n\t\t" . '<link>' . $base_config['baseurl'] . '/messages</link>';
	$output .= "\n\t\t" . '<guid>' . $base_config['baseurl'] . '/messages</guid>';
	$output .= "\n\t\t" . '<author><![CDATA[' . htmlspecialchars($author) . ']]></author>';
	$output .= "\n\t\t" . '<description><![CDATA[' . $contents_raw . ']]></description>';
	$output .= "\n\t" . '</item>';
}
$output .= "\n" . '</channel></rss>';
$output = str_replace('<$title>', $title, $output);
$output = str_replace('<$description>', '', $output);
echo $output;