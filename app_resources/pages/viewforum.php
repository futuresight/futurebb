<?php
$forum_title = $dirs[1];
//get the forum
$result = $db->query('SELECT f.id,f.name,f.view_groups,f.topic_groups,f.archived,rt.id AS tracker_id FROM `#^forums` AS f LEFT JOIN `#^read_tracker` AS rt ON rt.forum_id=f.id AND rt.user_id=' . $futurebb_user['id'] . ' AND rt.topic_id IS NULL WHERE url=\'' . $db->escape($forum_title) . '\'') or error('Failed to get forum info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result)) {
	httperror(404);
}
$cur_forum = $db->fetch_assoc($result);
if (!strstr($cur_forum['view_groups'], '-' . $futurebb_user['group_id'] . '-')) {
	httperror(403);
}
if (isset($_GET['page'])) {
	$page = intval($_GET['page']);
} else {
	$page = 1;
}

//basic header stuff
$page_title = $cur_forum['name'];
$breadcrumbs = array(translate('index') => '', $cur_forum['name'] => $dirs[1]);
$other_head_stuff = array('<link rel="alternate" type="application/atom+xml" href="' . $base_config['baseurl'] . '/atom/' . htmlspecialchars($dirs[1]) . '" title="' . translate('atomfeed') . '" />');

//get topic count
$result = $db->query('SELECT COUNT(id) FROM `#^topics` WHERE forum_id=' . $cur_forum['id'] . ($futurebb_user['g_mod_privs'] ? '' : ' AND deleted IS NULL')) or error('Failed to get topic count', __FILE__, __LINE__, $db->error());
list($num_topics) = $db->fetch_row($result);

//get the topics (if any)
$result = $db->query('SELECT t.id,t.subject,t.url,t.last_post,t.last_post_id,t.closed,t.sticky,t.redirect_id,t.num_replies,t.deleted,lpa.username AS last_post_author,rt.id AS tracker_id,fpa.username AS author,du.username AS deleted_by FROM `#^topics` AS t LEFT JOIN `#^posts` AS lp ON lp.id=t.last_post_id LEFT JOIN `#^users` AS lpa ON lpa.id=lp.poster LEFT JOIN `#^read_tracker` AS rt ON rt.topic_id=t.id AND rt.user_id=' . $futurebb_user['id'] . ' AND rt.forum_id IS NULL LEFT JOIN `#^posts` AS fp ON fp.id=t.first_post_id LEFT JOIN `#^users` AS fpa ON fpa.id=fp.poster LEFT JOIN `#^users` AS du ON du.id=t.deleted_by WHERE t.forum_id=' . $cur_forum['id'] . ($futurebb_user['g_mod_privs'] ? '' : ' AND t.deleted IS NULL') . ' AND (t.redirect_id IS NULL OR t.show_redirect=1) ORDER BY t.sticky DESC,t.last_post DESC LIMIT ' . (($page - 1) * intval($futurebb_config['topics_per_page'])) . ',' . intval($futurebb_config['topics_per_page'])) or error('Failed to get topics', __FILE__, __LINE__, $db->error());
if ($futurebb_user['g_mod_privs'] || $futurebb_user['g_admin_privs']) {
?>
<form action="<?php echo $base_config['baseurl']; ?>/admin/postactions" method="post" enctype="multipart/form-data">
<?php
}
?>
<div class="forum_content noleftmargin">
	<h2 class="cat_header<?php if ($cur_forum['archived']) echo ' archived"'; ?>"><?php echo htmlspecialchars($cur_forum['name']);
	if ($cur_forum['archived']) {
		echo ' ' . translate('archived');
	} ?></h2>
	<div class="indentleft">
		<?php if ($futurebb_user['id'] != 0 && strstr($cur_forum['topic_groups'], '-' . $futurebb_user['group_id'] . '-')) { ?><p><a href="<?php echo $base_config['baseurl']; ?>/post/forum/<?php echo $cur_forum['id']; ?>">Post new topic</a></p><?php } 
		if ($num_topics) {
			?>
			<p><?php echo translate('pages');
			echo paginate('<a href="' . $base_config['baseurl'] . '/' .  htmlspecialchars($dirs[1]) . '?page=$page$"$bold$>$page$</a>', $page, ceil($num_topics / $futurebb_config['topics_per_page']));
		?></p>
		<?php
		}
		?>
	</div>
	<?php
	$all_read = true;
	if ($db->num_rows($result)) { 
		$topic_list = array();
		?>
	<table border="0" class="forumtable">
		<tr>
			<?php if ($futurebb_user['g_mod_privs'] || $futurebb_user['g_admin_privs']) { //add extra column for admin checkboxes ?>
			<th style="width: 20px;">&nbsp;</th>
			<?php } ?>
			<th style="width: 20px;">&nbsp;</th>
			<th><?php echo translate('subject'); ?></th>
			<th><?php echo translate('author'); ?></th>
			<th><?php echo translate('replies'); ?></th>
			<th><?php echo translate('lastpost'); ?></th>
		</tr>
		<?php while ($cur_topic = $db->fetch_assoc($result)) {
				if (!in_array($cur_topic['id'], $topic_list)) {
				$topic_list[] = $cur_topic['id'];
				// prepare a handy boolean
				$cur_topic['unread'] = ($futurebb_user['id'] != 0 && $cur_topic['tracker_id'] == null);
				if ($futurebb_user['last_visit'] > $cur_topic['last_post'] && $cur_topic['unread']) {
					$db->query('INSERT INTO `#^read_tracker`(user_id,topic_id) VALUES(' . $futurebb_user['id'] . ',' . $cur_topic['id'] . ')') or error('Failed to mark topic as read', __FILE__, __LINE__, $db->error());
					$cur_topic['unread'] = false;
				}
		?>
		<tr>
			<?php if ($futurebb_user['g_mod_privs'] || $futurebb_user['g_admin_privs']) { //add extra column for admin checkboxes ?>
			<td style="text-align:center">
				<input type="checkbox" name="topic_action[<?php echo $cur_topic['id']; ?>]" value="<?php echo $cur_topic['id']; ?>" />
			</td>
			<?php } ?>
			<td style="text-align:center">
			<?php // add status icon before topic title
			if ($cur_topic['deleted']) {
					echo '&#10060;';
			} else if ($cur_topic['sticky']) {
				if ($cur_topic['unread']) {
					//echo '<object data="' . $base_config['baseurl'] . '/static/img/posticon/U_sticky.png" type="image/svg+xml" width="10px" height="14px"></object> ';
					echo '<img class="svgimg" src="' . $base_config['baseurl'] . '/static/img/posticon/U_sticky.png" width="10px" alt="sticky" />';
				} else {
					//echo '<object data="' . $base_config['baseurl'] . '/static/img/posticon/R_sticky.png" type="image/svg+xml" width="10px" height="14px"></object> ';
					echo '<img class="svgimg" src="' . $base_config['baseurl'] . '/static/img/posticon/R_sticky.png" width="10px" alt="sticky" />';
				}
			} elseif ($cur_topic['closed']) {
				if ($cur_topic['unread']) {
					echo '<img src="' . $base_config['baseurl'] . '/static/img/posticon/U_closed.png" width="10px" alt="closed" />';
				} else {
					echo '<img src="' . $base_config['baseurl'] . '/static/img/posticon/R_closed.png" width="10px" alt="closed" />';
				}
			} elseif ($cur_topic['redirect_id'] != null) {
				echo '<strong>&rarr;</strong>';
			} else {
				if ($cur_topic['unread']) {
					echo '<img src="' . $base_config['baseurl'] . '/static/img/posticon/U.png" width="10px" alt="unread" />';
				} else {
					echo '&nbsp;';
				}
			}
			?>
			
			</td><td>
			<?php echo '<a href="' . $base_config['baseurl'] . '/' . htmlspecialchars($dirs[1]) . '/' . htmlspecialchars($cur_topic['url']) . '"';
			//if ($futurebb_user['id'] != 0 && $cur_topic['last_post'] > $futurebb_user['last_visit']) { #old tracker
			$class = array();
			if ($futurebb_user['id'] != 0 && $cur_topic['unread'] && $cur_topic['redirect_id'] == null) {
				$class[] = 'unread';
				$all_read = false;
			}
			if ($cur_topic['closed']) {
				$class[] = 'closed';
			}
			if (!empty($class)) {
				echo ' class="' . implode(' ', $class) . '"';
			}
			echo '>' . htmlspecialchars($cur_topic['subject']) . '</a>';
			if ($cur_topic['deleted']) {
				echo '<br />' . translate('deletedbyon', translate('topic'), htmlspecialchars($cur_topic['deleted_by']), user_date($cur_topic['deleted']));
			}
			?></td>
			<td><?php echo $cur_topic['author']; ?></td>
			<td><?php echo $cur_topic['num_replies']; ?></td>
			<td><?php if ($cur_topic['last_post'] != 0) { ?><a href="<?php echo $base_config['baseurl']; ?>/posts/<?php echo $cur_topic['last_post_id']; ?>"><?php echo user_date($cur_topic['last_post']) . ' ' . translate('by') .' ' . htmlspecialchars($cur_topic['last_post_author']); ?></a><?php } else { ?><?php echo translate('topicmoved'); ?><?php } ?></td>
		</tr>
		<?php
			}
		} ?>
	</table>
	<?php 
	} else {
		if ($num_topics == 0) {
			echo '<p>' . translate('notopics') . '</p>';
		} else {
			httperror(404);
		}
	}
	if ($futurebb_user['id'] != 0 && $cur_forum['tracker_id'] == null && $all_read) {
		$db->query('INSERT INTO `#^read_tracker`(user_id,forum_id) VALUES(\'' . $futurebb_user['id'] . '\',\'' . $cur_forum['id'] . '\')') or error('Failed to mark forum as read', __FILE__, __LINE__, $db->error());
	}
	if ($num_topics) {
		?>
		<div class="indentleft"><p><?php echo translate('pages');
		echo paginate('<a href="' . $base_config['baseurl'] . '/' .  htmlspecialchars($dirs[1]) . '?page=$page$"$bold$>$page$</a>', $page, ceil($num_topics / $futurebb_config['topics_per_page']));
		?></p></div>
	<?php
	}
	//send Atom URL back to dispatcher
	$feed_url = 'atom/' . htmlspecialchars($dirs[1]);
	?>
</div>
<?php
if (($futurebb_user['g_mod_privs'] || $futurebb_user['g_admin_privs']) && $num_topics > 0) { ?>
	<p>
		<input type="hidden" name="type" value="topics" />
		<input type="submit" name="form_sent_delete" value="<?php echo translate('delete'); ?>" />
		<input type="submit" name="form_sent_undelete" value="<?php echo translate('undelete'); ?>" />
		<input type="submit" name="form_sent_close" value="<?php echo translate('close'); ?>" />
		<input type="submit" name="form_sent_open" value="<?php echo translate('open'); ?>" />
		<input type="submit" name="form_sent_stick" value="<?php echo translate('stick'); ?>" />
		<input type="submit" name="form_sent_unstick" value="<?php echo translate('unstick'); ?>" />
	</p>
</form>
<?php
}