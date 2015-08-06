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
include_once FORUM_ROOT . '/app_resources/includes/parser.php';
include_once FORUM_ROOT . '/app_resources/includes/search.php';
if ($dirs[2] == 'forum') {
	$result = $db->query('SELECT name,url,topic_groups,archived FROM `#^forums` WHERE id=' . intval($dirs[3])) or error('Failed to get forum info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result)) {
		httperror(404);
	}
	$forum_info = $db->fetch_assoc($result);
	if (!strstr($forum_info['topic_groups'], '-' . $futurebb_user['group_id'] . '-') || ($forum_info['archived'] && (!$futurebb_user['g_mod_privs'] && !$futurebb_user['g_admin_privs']))) {
		httperror(403);
	}
	$page_title = translate('posttopic') . ' - ' . $forum_info['name'];
	$breadcrumbs = array(translate('index') => '', $forum_info['name'] => $forum_info['url'], translate('posttopic') => '!nourl!');
} else if ($dirs[2] == 'topic') {
	$result = $db->query('SELECT t.subject,t.url,t.closed,t.deleted,f.name AS forum_name,f.url AS forum_url,f.id AS f_id,f.reply_groups,f.archived AS forum_archived FROM `#^topics` AS t LEFT JOIN `#^forums` AS f ON f.id=t.forum_id WHERE t.id=' . intval($dirs[3]) . ($futurebb_user['g_admin_privs'] ? '' : ' AND t.deleted IS NULL')) or error('Failed to get forum info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result)) {
		httperror(404);
	}
	$cur_topic = $db->fetch_assoc($result);
	if (!strstr($cur_topic['reply_groups'], '-' . $futurebb_user['group_id'] . '-') || ($cur_topic['closed'] && !$futurebb_user['g_mod_privs']) || ($cur_topic['forum_archived'] && !$futurebb_user['g_mod_privs'])) {
		httperror(403);
	}
	$page_title = translate('postreply') . ' - ' . $cur_topic['subject'];
	$breadcrumbs = array(translate('index') => '', $cur_topic['forum_name'] => $cur_topic['forum_url'], $cur_topic['subject'] => $cur_topic['forum_url'] . '/' . $cur_topic['url'], translate('postreply') => '!nourl!');
} else {
	httperror(404);
}
if (isset($_POST['form_sent']) || isset($_POST['preview'])) {
	if (!$futurebb_config['enable_smilies']) {
		$_POST['hidesmilies'] = true;
	}
	$errors = array();
	if ($futurebb_config['enable_bbcode']) {
		BBCodeController::error_check($_POST['message'], $errors);
	}
	if (strlen($_POST['message']) > 256000) {
		$errors[] = translate('msgtoolong', 256000);
	}
	
	check_flood($errors);
	if ($dirs[2] == 'forum' && trim($_POST['subject']) == '')
		$errors[] = translate('blanksubject');
	if (trim($_POST['message']) == '')
		$errors[] = translate('blankmsg');
	
	$continue_posting = ExtensionConfig::run_hooks('check-post', 
		array(
			'type' => $dirs[2] == 'forum' ? 'topic' : 'reply',
			'subject' => isset($_POST['subject']) ? $_POST['subject'] : '',
			'message' => $_POST['message'],
			'topic_id' => $dirs[3] == 'topic' ? intval($dirs[2]) : '',
			'forum_id' => $dirs[3] == 'forum' ? intval($dirs[2]) : ''
		)
	); 
	
	if (!$continue_posting && empty($errors)) {
		$errors[] = translate('unknownerror');
	}
	
	// New post + new topic
	if ($dirs[2] == 'forum' && empty($errors) && !isset($_POST['preview'])) {
		$fid = intval($dirs[3]);
		$topic_url = create_topic($_POST['subject'], $_POST['message'], $futurebb_user['id'], $fid, isset($_POST['hidesmilies']));
		ExtensionConfig::run_hooks('new_topic',
			array(
				'subject' => $_POST['subject'],
				'message' => $_POST['message'],
				'poster' => $futurebb_user['username'],
				'forum_url' => $forum_info['url'],
				'forum' => $forum_info['name'],
				'topic_url' => $topic_url,
			)
		);
		redirect($base_config['baseurl'] . '/' . $forum_info['url'] . '/' . $topic_url);
	} else if ($dirs[2] == 'topic' && empty($errors) && !isset($_POST['preview'])) {
		//new post
		$tid = intval($dirs[3]);
		$parsedtext = BBCodeController::parse_msg($_POST['message'], !isset($_POST['hidesmilies']), $futurebb_config['enable_bbcode']);
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
		$db->query('UPDATE `#^forums` SET last_post=' . time() . ',last_post_id=' . $pid . ($cur_topic['deleted'] ? '' : ',num_posts=num_posts+1') . ' WHERE id=' . $cur_topic['f_id']) or error('Failed to forum last post', __FILE__, __LINE__, $db->error());
		$db->query('DELETE FROM `#^read_tracker` WHERE (forum_id=' . $cur_topic['f_id'] . ' OR topic_id=' . $tid . ') AND user_id<>' . $futurebb_user['id']) or error('Failed to update read tracker', __FILE__, __LINE__, $db->error());
		$db->query('UPDATE `#^users` SET num_posts=num_posts+1 WHERE id=' . $futurebb_user['id']) or error('Failed to update number of posts', __FILE__, __LINE__, $db->error());
		
		update_search_index($pid,$_POST['message']);
		
		ExtensionConfig::run_hooks('new_post',
			array(
				'id' => $pid,
				'topic' => $cur_topic['subject'],
				'topic_url' => $cur_topic['url'],
				'poster' => $futurebb_user['username'],
				'message' => $_POST['message'],
				'forum_url' => $cur_topic['forum_url'],
				'forum' => $cur_topic['forum_name']
			)
		);
		
		redirect($base_config['baseurl'] . '/posts/' . $pid); return;
	} else if (isset($_POST['preview']) && empty($errors)) {
		echo '<div class="quotebox preview">' . BBCodeController::parse_msg($_POST['message'], !isset($_POST['hidesmilies']), true, $futurebb_config['enable_bbcode']) . '</div>';
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

<form action="<?php echo $base_config['baseurl']; ?>/post/<?php echo htmlspecialchars($dirs[2]); ?>/<?php echo htmlspecialchars($dirs[3]); ?>" method="post" enctype="multipart/form-data">
	<?php if ($dirs[2] == 'forum') { ?><p><?php echo translate('subject'); ?> <input type="text" name="subject" size="50"<?php if (isset($_POST['subject'])) echo ' value="' . htmlspecialchars($_POST['subject']) . '"'; ?> /></p><?php } ?>
	<?php ExtensionConfig::run_hooks('bbcode_toolbar'); ?>
	<p><?php echo translate('message'); ?><br /><textarea name="message" id="message" rows="20" cols="70"><?php if (isset($_POST['message'])) echo htmlspecialchars($_POST['message']); else if (isset($post)) echo '[quote=' . htmlspecialchars($poster) . ']' . htmlspecialchars($post) . '[/quote]' . "\n"; ?></textarea></p>
    <?php ExtensionConfig::run_hooks('bbcode_toolbar_bottom'); ?>
	<?php //the bar at the bottom indicating which features are available ?>
    <p><a href="<?php echo $base_config['baseurl']; ?>/bbcodehelp"><?php echo translate('bbcode'); ?></a>: <?php if ($futurebb_config['enable_bbcode']) echo translate('on'); else echo translate('off'); ?>, <a href="<?php echo $base_config['baseurl']; ?>/bbcodehelp#smilies"><?php echo translate('smilies'); ?></a>: <?php if ($futurebb_config['enable_smilies']) echo translate('on'); else echo translate('off'); ?>, <a href="<?php echo $base_config['baseurl']; ?>/bbcodehelp#linksimages"><?php echo translate('imgtag'); ?></a>: <?php if ($futurebb_user['g_post_links']) echo translate('on'); else echo translate('off'); ?>, <a href="<?php echo $base_config['baseurl']; ?>/bbcodehelp#linksimages"><?php echo translate('urltag'); ?></a>: <?php if ($futurebb_user['g_post_images']) echo translate('on'); else echo translate('off'); ?></p>
	<p><input type="submit" name="form_sent" value="<?php echo translate('post'); ?>" /> <input type="submit" name="preview" value="<?php echo translate('preview'); ?>" />
	<?php if ($futurebb_config['enable_smilies']) { ?>
     <input name="hidesmilies" type="checkbox" value="1"<?php if (isset($_POST['hidesmilies'])) echo ' checked="checked"'; ?>  id="disablesmilies" /> <label for="disablesmilies"><?php echo translate('disablesmilies'); ?></label><?php
	} ?></p>
</form>