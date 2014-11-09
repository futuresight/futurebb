<?php
ob_end_clean();
$pid = intval($dirs[2]);
$result = $db->query('SELECT t.id,t.url AS turl, f.url AS furl FROM `#^posts` AS p LEFT JOIN `#^topics` AS t ON t.id=p.topic_id LEFT JOIN `#^forums` AS f ON f.id=t.forum_id WHERE p.id=' . $pid . ($futurebb_user['g_mod_privs'] ? '' : ' AND p.deleted IS NULL AND t.deleted IS NULL')) or error('Failed to get topic location', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result)) {
	httperror(404);
}
list($tid,$turl,$furl) = $db->fetch_row($result);

$result = $db->query('SELECT id FROM `#^posts` WHERE topic_id=' . $tid . ($futurebb_user['g_mod_privs'] ? '' : ' AND deleted IS NULL')) or error('Failed to get post list', __FILE__, __LINE__, $db->error());
$i = 0;
while (list($id) = $db->fetch_row($result)) {
	$i++;
	if ($id == $pid) {
		break;
	}
}
$page = ceil($i / $futurebb_config['posts_per_page']);
redirect($base_config['baseurl'] . '/' . $furl . '/' . $turl . '?page=' . $page . '#post' . $pid);