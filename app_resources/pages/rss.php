<?php
$type = $dirs[2];
translate('<addfile>', 'rss');
$output = '<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0">

<channel>
  <title><$title></title>
  <description><$description></description>';
switch ($type) {
	case 'topic':
		echo 'Not implemented'; die;
		break;
	case 'forum':
		$q = new DBSelect('forums', array('f.id','rf.url AS redirect_url', 'f.name'), 'f.url=\'' . $db->escape($dirs[3]) . '\'', 'Failed to get forum info');
		$q->add_join(new DBJoin('forums', 'rf', 'rf.id=f.redirect_id', 'LEFT'));
		$q->table_as('f');
		$result = $q->commit();
		if (!$db->num_rows($result)) {
			httperror(404);
		}
		$forum_info = $db->fetch_assoc($result);
		if ($forum_info['redirect_url'] != null) {
			redirect($base_config['baseurl'] . '/rss/forum/' . $forum_info['redirect_url']);
		}
		$title = translate('latestpostsin', $forum_info['name']);
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
			$output .= "\n\t" . '<item>' . "\n\t\t" . '<title>' . htmlspecialchars($forum_info['name']) . ' / ' . htmlspecialchars($post['subject']) . '</title><pubDate>' . gmdate('D, d M Y H:i:s', $post['posted']) . ' +0000</pubDate><link>' . $base_config['baseurl'] . '/posts/' . $post['id'] . '</link><author>' . htmlspecialchars($post['poster']) . '</author><description><![CDATA[' . htmlspecialchars($post['parsed_content']) . ']]></description></item>';
		}
		break;
	default:
		httperror(404);
}
$output .= "\n" . '</channel></rss>';
$output = str_replace('<$title>', $title, $output);
$output = str_replace('<$description>', '', $output);
echo $output;