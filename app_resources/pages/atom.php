<?php
header('Content-type: application/atom+xml');
$type = $dirs[2];
translate('<addfile>', 'rss');
$output = '<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
	<title><$title></title>
	<subtitle><$description></subtitle>	
	<link href="<$link>" />
	<link rel="stylesheet" href="' . $base_config['baseurl'] . '/styles/default.css" />
	<generator>FutureBB</generator>';
if (isset($dirs[3]) && $dirs[3] != '') {
	//topic is given, use it
	$result = $db->query('SELECT t.id,t.url,t.subject,t.closed,t.sticky,t.last_post,t.last_post_id,t.first_post_id,t.redirect_id,f.name AS forum_name,f.id AS forum_id,f.url AS forum_url,f.view_groups,f.reply_groups FROM `#^topics` AS t LEFT JOIN `#^forums` AS f ON f.url=\'' . $db->escape($dirs[2]) . '\' WHERE f.id IS NOT NULL AND f.id=t.forum_id AND t.url=\'' . $db->escape($dirs[3]) . '\' AND t.deleted IS NULL') or error('Failed to get topic info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result)) {
		httperror(404);
	}
	$cur_topic = $db->fetch_assoc($result);
	if (!strstr($cur_topic['view_groups'], '-' . $futurebb_user['group_id'] . '-')) {
		httperror(403);
	}
	$title = $cur_topic['subject'] . ' - ' . $cur_topic['forum_name'] . ' - ' . $futurebb_config['board_title'];
	$description = translate('latestpostsin', $cur_topic['subject']);
	$link = $base_config['baseurl'] . '/' . htmlspecialchars($dirs[2]) . '/' . htmlspecialchars($dirs[3]);
	
	$q = new DBSelect('posts', array('p.id','p.parsed_content','u.username AS poster','p.posted'), 'p.topic_id=' . $cur_topic['id'] . ' AND p.deleted IS NULL', 'Failed to get posts');
	$q->add_join(new DBJoin('users', 'u', 'u.id=p.poster', 'LEFT'));
	$q->table_as('p');
	$q->set_order('p.posted DESC');
	$q->set_limit('20');
	$result = $q->commit();
	if (!$db->num_rows($result)) {
		httperror(404);
	}
	while ($post = $db->fetch_assoc($result)) {
		$output .= "\n\t" . '<entry>' . "\n\t\t" . '<title>' . htmlspecialchars($cur_topic['forum_name']) . ' / ' . htmlspecialchars($cur_topic['subject']) . '</title>';
		$output .= "\n\t\t" . '<published>' . gmdate('D, d M Y H:i:s', $post['posted']) . ' +0000</published>';
		$output .= "\n\t\t" . '<link href="' . htmlspecialchars($base_config['baseurl'] . '/posts/' . $post['id']) . '" />';
		$output .= "\n\t\t" . '<guid>' . $base_config['baseurl'] . '/posts/' . $post['id'] . '</guid>';
		$output .= "\n\t\t" . '<author>';
		$output .= "\n\t\t\t" . '<name>' . htmlspecialchars($post['poster']) . '</name>';
		$output .= "\n\t\t\t" . '<uri>' . htmlspecialchars($base_config['baseurl'] . '/users/' . rawurlencode($post['poster'])) . '/</uri>';
		$output .= "\n\t\t" . '</author>';
		$output .= "\n\t\t" . '<content type="xhtml"><p>' . $post['parsed_content'] . '</p></content>';
		$output .= "\n\t" . '</entry>';
	}
} else {
	//no topic is given, so use the forum
	$q = new DBSelect('forums', array('f.id','rf.url AS redirect_url', 'f.name', 'f.view_groups'), 'f.url=\'' . $db->escape($dirs[2]) . '\'', 'Failed to get forum info');
	$q->add_join(new DBJoin('forums', 'rf', 'rf.id=f.redirect_id', 'LEFT'));
	$q->table_as('f');
	$result = $q->commit();
	if (!$db->num_rows($result)) {
		httperror(404);
	}
	$forum_info = $db->fetch_assoc($result);
	if (!strstr($forum_info['view_groups'], '-' . $futurebb_user['group_id'] . '-')) {
		//don't try to get smart and view forums without permission
		httperror(403);
	}
	if ($forum_info['redirect_url'] != null) {
		redirect($base_config['baseurl'] . '/rss/forum/' . $forum_info['redirect_url']);
	}
	$title = $forum_info['name'] . ' - ' . $futurebb_config['board_title'];
	$q = new DBSelect('posts', array('p.id','p.parsed_content','u.username AS poster','t.subject','p.posted'), 't.forum_id=' . $forum_info['id'], 'Failed to get posts');
	$q->add_join(new DBJoin('topics', 't', 't.id=p.topic_id', 'LEFT'));
	$q->add_join(new DBJoin('users', 'u', 'u.id=p.poster', 'LEFT'));
	$q->table_as('p');
	$q->set_order('p.posted DESC');
	$q->set_limit('20');
	$result = $q->commit();
	if (!$db->num_rows($result)) {
		httperror(404);
	}
	while ($post = $db->fetch_assoc($result)) {
		$output .= "\n\t" . '<entry>' . "\n\t\t" . '<title><![CDATA[' . htmlspecialchars($forum_info['name']) . ' / ' . htmlspecialchars($post['subject']) . ']]></title>';
		$output .= "\n\t\t" . '<published>' . gmdate('D, d M Y H:i:s', $post['posted']) . ' +0000</published>';
		$output .= "\n\t\t" . '<link href="' . htmlspecialchars($base_config['baseurl'] . '/posts/' . $post['id']) . '" />';
		$output .= "\n\t\t" . '<guid>' . $base_config['baseurl'] . '/posts/' . $post['id'] . '</guid>';
		$output .= "\n\t\t" . '<author>';
		$output .= "\n\t\t\t" . '<name>' . htmlspecialchars($post['poster']) . '</name>';
		$output .= "\n\t\t\t" . '<uri>' . htmlspecialchars($base_config['baseurl'] . '/users/' . rawurlencode($post['poster'])) . '/</uri>';
		$output .= "\n\t\t" . '</author>';
		$output .= "\n\t\t" . '<content type="xhtml"><p>' . $post['parsed_content'] . '</p></content>';
		$output .= "\n\t" . '</entry>';
	}
	$link = $base_config['baseurl'] . '/' . htmlspecialchars($dirs[2]);
	$description = translate('latestpostsin', $forum_info['name']);
}
$output .= "\n" . '</feed>';
$output = str_replace('<$title>', $title, $output);
$output = str_replace('<$description>', $description, $output);
$output = str_replace('<$link>', $link, $output);
echo $output;