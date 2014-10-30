<?php
$tid = intval($_GET['tid']);
$result = $db->query('SELECT t.id,t.url,t.subject,t.closed,t.sticky,t.last_post,t.last_post_id,t.first_post_id,t.redirect_id,f.name AS forum_name,f.id AS forum_id,f.url AS forum_url,f.view_groups,f.reply_groups,rt.id AS tracker_id FROM `#^topics` AS t LEFT JOIN `#^forums` AS f ON f.id=t.forum_id LEFT JOIN `#^read_tracker` AS rt ON rt.topic_id=t.id AND rt.user_id=' . $futurebb_user['id'] . ' AND rt.forum_id IS NULL WHERE f.id IS NOT NULL AND t.id=\'' . $db->escape($tid) . '\' AND t.deleted IS NULL') or error('Failed to get topic info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result)) {
	httperror(404);
}
$cur_topic = $db->fetch_assoc($result);
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
    	<title><?php echo htmlspecialchars($cur_topic['subject'] . ' (Embedded) - ' . $futurebb_config['board_title']); ?></title>
        <link rel="stylesheet" href="<?php echo $base_config['baseurl']; ?>/styles/default.css" />
    </head>
    <body>
    </body>
</html>