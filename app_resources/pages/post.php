<?php
if ($futurebb_user['id'] == 0) {
	httperror(403);
}
function check_flood(&$errors) {
	global $db, $futurebb_user;
	if ($futurebb_user['g_post_flood'] > 0) {
		$result = $db->query('SELECT posted FROM `#^posts` WHERE poster=' . $futurebb_user['id'] . ' AND posted>' . (time() - $futurebb_user['g_post_flood'])) or error('Failed to find recent posts', __FILE__, __LINE__, $db->error());
		if ($db->num_rows($result)) {
			list($last_posted) = $db->fetch_row($result);
			$errors[] = translate('flood_wait', $futurebb_user['g_post_flood'], ($futurebb_user['g_post_flood'] - (time() - $last_posted)));
			return;
		}
	}
	if ($futurebb_user['g_posts_per_hour'] > 0) {
		$result = $db->query('SELECT posted FROM `#^posts` WHERE poster=' . $futurebb_user['id'] . ' AND posted>' . (time() - 3600) . ' ORDER BY posted ASC') or error('Failed to find recent posts', __FILE__, __LINE__, $db->error());
		if ($db->num_rows($result) > $futurebb_user['g_posts_per_hour']) {
			list($first_post) = $db->fetch_row($result);
			$errors[] = translate('flood_hour', $futurebb_user['g_posts_per_hour'], (3600 - (time() - $first_post)));
			return;
		}
	}
}
include FORUM_ROOT . '/app_resources/includes/parser.php';
include FORUM_ROOT . '/app_resources/includes/search.php';
if ($dirs[2] == 'forum') {
	$result = $db->query('SELECT name,url,topic_groups FROM `#^forums` WHERE id=' . intval($dirs[3])) or error('Failed to get forum info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result)) {
		httperror(404);
	}
	$forum_info = $db->fetch_assoc($result);
	if (!strstr($forum_info['topic_groups'], '-' . $futurebb_user['group_id'] . '-')) {
		httperror(403);
	}
	$page_title = translate('posttopic') . ' - ' . $forum_info['name'];
	$breadcrumbs = array(translate('index') => '', $forum_info['name'] => $forum_info['url'], translate('posttopic') => '!nourl!');
} else if ($dirs[2] == 'topic') {
	$result = $db->query('SELECT t.subject,t.url,t.closed,f.name AS forum_name,f.url AS forum_url,f.id AS f_id,f.reply_groups FROM `#^topics` AS t LEFT JOIN `#^forums` AS f ON f.id=t.forum_id WHERE t.id=' . intval($dirs[3]) . ' AND t.deleted IS NULL') or error('Failed to get forum info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result)) {
		httperror(404);
	}
	$cur_topic = $db->fetch_assoc($result);
	if (!strstr($cur_topic['reply_groups'], '-' . $futurebb_user['group_id'] . '-') || ($cur_topic['closed'] && !$futurebb_user['g_mod_privs'])) {
		httperror(403);
	}
	$page_title = translate('postreply') . ' - ' . $cur_topic['subject'];
	$breadcrumbs = array(translate('index') => '', $cur_topic['forum_name'] => $cur_topic['forum_url'], $cur_topic['subject'] => $cur_topic['forum_url'] . '/' . $cur_topic['url'], translate('postreply') => '!nourl!');
} else {
	httperror(404);
}
if (isset($_POST['form_sent']) || isset($_POST['preview'])) {
	$errors = array();
	BBCodeController::error_check($_POST['message'], $errors);
	if (strlen($_POST['message']) > 256000) {
		$errors[] = translate('msgtoolong', 256000);
	}
	
	check_flood($errors);
	if ($dirs[2] == 'forum' && $_POST['subject'] == '')
		$errors[] = translate('blanksubject');
	if ($_POST['message'] == '')
		$errors[] = translate('blankmsg');
	
	// New post + new topic
	if ($dirs[2] == 'forum' && empty($errors) && !isset($_POST['preview'])) {
		$fid = intval($dirs[3]);
		$name = URLEngine::make_friendly($_POST['subject']);
		$base_name = $name;
		//check for forums with the same URL
		$result = $db->query('SELECT url FROM `#^topics` WHERE url LIKE \'' . $db->escape($name) . '%\'') or error('Failed to check for similar URLs', __FILE__, __LINE__, $db->error());
		$urllist = array();
		while (list($url) = $db->fetch_row($result)) {
			$urllist[] = $url;
		}
		$ok = false;
		$add_num = 0;
		while (!$ok) {
			$ok = true;
			if (in_array($name, $urllist)) {
				$add_num++;
				$name = $base_name . '-' . $add_num;
				$ok = false;
			}
		}
		$db->query('INSERT INTO `#^topics`(subject,url,forum_id) VALUES(\'' . $db->escape($_POST['subject']) . '\',\'' . $db->escape($name) . '\',' . $fid . ')') or error('Failed to create topic', __FILE__, __LINE__, $db->error());
		$tid = $db->insert_id();
		$parsedtext = BBCodeController::parse_msg($_POST['message'], !isset($_POST['hidesmilies']));
		$db->query('INSERT INTO `#^posts`(poster,poster_ip,content,parsed_content,posted,topic_id,disable_smilies) VALUES(' . $futurebb_user['id'] . ',\'' . $db->escape($_SERVER['REMOTE_ADDR']) . '\',\'' . $db->escape($_POST['message']) . '\',\'' . $db->escape($parsedtext) . '\',' . time() . ',' . $tid . ',' . intval(isset($_POST['hidesmilies'])) . ')') or error('Failed to make first post', __FILE__, __LINE__, $db->error());
		$pid = $db->insert_id();
		// Let's take a break to fire any notifications from @ tags
		if($futurebb_config['allow_notifications'] == 1) {
			if(preg_match_all('%@([a-zA-Z0-9_\-]+)%', $parsedtext, $matches)) {
				array_slice($matches[1], 0, 8);
				foreach($matches[1] as $tagged_user) {
					$tagged_res = $db->query('SELECT id, block_notif FROM `#^users` WHERE username = \'' . $tagged_user . '\'') or error('Failed to find users to tag', __FILE__, __LINE__, $db->error());
					if($db->num_rows($tagged_res)) {
						$tagged_id = $db->fetch_assoc($tagged_res);
						if($tagged_id['block_notif'] == 0) {
							$db->query('INSERT INTO `#^notifications` (type, user, send_time, contents, arguments)
							VALUES (\'notification\', ' . intval($tagged_id['id']) . ', ' . time() . ', '. $pid . ', \'' . $futurebb_user['username'] . ',' . $db->escape($_POST['subject']) . '\')');
						}
					}
				}
			}
		}
		
		// Continue posting
		$db->query('UPDATE `#^topics` SET last_post=' . time() . ',last_post_id=' . $pid . ',first_post_id=' . $pid . ' WHERE id=' . $tid) or error('Failed to update topic info', __FILE__, __LINE__, $db->error());
		$db->query('UPDATE `#^forums` SET last_post=' . time() . ',last_post_id=' . $pid . ',num_posts=num_posts+1,num_topics=num_topics+1 WHERE id=' . $fid) or error('Failed to update forum last post', __FILE__, __LINE__, $db->error());
		$db->query('DELETE FROM `#^read_tracker` WHERE forum_id=' . $fid . ' AND user_id<>' . $futurebb_user['id']) or error('Failed to update read tracker', __FILE__, __LINE__, $db->error());
		$db->query('UPDATE `#^users` SET num_posts=num_posts+1 WHERE id=' . $futurebb_user['id']) or error('Failed to update number of posts', __FILE__, __LINE__, $db->error());
		
		update_search_index($pid,$_POST['message']);
		
		redirect($base_config['baseurl'] . '/' . $forum_info['url'] . '/' . $name);
		
		// New post
	} else if ($dirs[2] == 'topic' && empty($errors) && !isset($_POST['preview'])) {
		$tid = intval($dirs[3]);
		$parsedtext = BBCodeController::parse_msg($_POST['message'], !isset($_POST['hidesmilies']));
		$db->query('INSERT INTO `#^posts`(poster,poster_ip,content,parsed_content,posted,topic_id,disable_smilies) VALUES(' . $futurebb_user['id'] . ',\'' . $db->escape($_SERVER['REMOTE_ADDR']) . '\',\'' . $db->escape($_POST['message']) . '\',\'' . $db->escape($parsedtext) . '\',' . time() . ',' . $tid . ',' . intval(isset($_POST['hidesmilies'])) . ')') or error('Failed to make first post', __FILE__, __LINE__, $db->error());
		$pid = $db->insert_id();
		
		// Let's take a break to fire any notifications from @ tags
		if($futurebb_config['allow_notifications'] == 1) {
			if(preg_match_all('%@([a-zA-Z0-9_\-]+)%', $parsedtext, $matches)) {
				array_slice($matches[1], 0, 8);
				foreach($matches[1] as $tagged_user) {
					$tagged_res = $db->query('SELECT id, block_notif FROM `#^users` WHERE username = \'' . $tagged_user . '\'') or error('Failed to find users to tag', __FILE__, __LINE__, $db->error());
					if($db->num_rows($tagged_res)) {
						$tagged_id = $db->fetch_assoc($tagged_res);
						if($tagged_id['block_notif'] == 0) {
							$db->query('INSERT INTO `#^notifications` (type, user, send_time, contents, arguments)
							VALUES (\'notification\', ' . intval($tagged_id['id']) . ', ' . time() . ', '. $pid . ', \'' . $futurebb_user['username'] . ',' . $db->escape($cur_topic['subject']) . '\')');
						}
					}
				}
			}
		}
		
		// Continue posting
		$db->query('UPDATE `#^topics` SET last_post=' . time() . ',last_post_id=' . $pid . ',num_replies=num_replies+1 WHERE id=' . $tid) or error('Failed to update topic info', __FILE__, __LINE__, $db->error());
		$db->query('UPDATE `#^forums` SET last_post=' . time() . ',last_post_id=' . $pid . ',num_posts=num_posts+1 WHERE id=' . $cur_topic['f_id']) or error('Failed to forum last post', __FILE__, __LINE__, $db->error());
		$db->query('DELETE FROM `#^read_tracker` WHERE (forum_id=' . $cur_topic['f_id'] . ' OR topic_id=' . $tid . ') AND user_id<>' . $futurebb_user['id']) or error('Failed to update read tracker', __FILE__, __LINE__, $db->error());
		$db->query('UPDATE `#^users` SET num_posts=num_posts+1 WHERE id=' . $futurebb_user['id']) or error('Failed to update number of posts', __FILE__, __LINE__, $db->error());
		
		update_search_index($pid,$_POST['message']);
		
		redirect($base_config['baseurl'] . '/posts/' . $pid); return;
	} else if (isset($_POST['preview']) && empty($errors)) {
		echo '<div class="quotebox preview">' . BBCodeController::parse_msg($_POST['message'], !isset($_POST['hidesmilies']), true) . '</div>';
	}
}
if (isset($errors) && !empty($errors)) {
	echo '<p>' . translate('errordesc') . '<ul>';
	foreach ($errors as $val) {
		echo '<li>' . $val . '</li>';
	}
	echo '</ul></p>';
}
if (isset($_GET['quote'])) {
	$result = $db->query('SELECT p.content,u.username FROM `#^posts` AS p LEFT JOIN `#^users` AS u ON u.id=p.poster WHERE p.id=' . intval($_GET['quote'])) or error('Failed to get post to quote', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result)) {
		httperror(404);
	}
	list($post,$poster) = $db->fetch_row($result);
}
?>
<link href="/app_resources/pages/css/default.css" rel="stylesheet" type="text/css" />

<form action="<?php echo $base_config['baseurl']; ?>/post/<?php echo htmlspecialchars($dirs[2]); ?>/<?php echo htmlspecialchars($dirs[3]); ?>" method="post" enctype="multipart/form-data">
	<?php if ($dirs[2] == 'forum') { ?><p><?php echo translate('subject'); ?> <input type="text" name="subject" size="50"<?php if (isset($_POST['subject'])) echo ' value="' . htmlspecialchars($_POST['subject']) . '"'; ?> /></p><?php } ?>
	<p><?php echo translate('message'); ?><br /><textarea name="message" rows="20" cols="70"><?php if (isset($_POST['message'])) echo htmlspecialchars($_POST['message']); else if (isset($post)) echo '[quote=' . htmlspecialchars($poster) . ']' . htmlspecialchars($post) . '[/quote]' . "\n"; ?></textarea></p>
	<p><input type="submit" name="form_sent" value="<?php echo translate('post'); ?>" /> <input type="submit" name="preview" value="<?php echo translate('preview'); ?>" /> <input name="hidesmilies" type="checkbox" value="1"<?php if (isset($_POST['hidesmilies'])) echo ' checked="checked"'; ?>  id="disablesmilies" /> <label for="disablesmilies"><?php echo translate('disablesmilies'); ?></label></p>
</form>