<?php
//$result = $db->query('SELECT t.id,t.url,t.subject,t.closed,t.sticky,t.last_post,t.last_post_id,t.first_post_id,t.redirect_id,f.name AS forum_name,f.id AS forum_id,f.url AS forum_url,f.view_groups,f.reply_groups,rt.id AS tracker_id,rtf.id AS ftracker_id FROM `#^topics` AS t LEFT JOIN `#^forums` AS f ON f.url=\'' . $db->escape($dirs[1]) . '\' LEFT JOIN `#^read_tracker` AS rt ON rt.topic_id=t.id AND rt.user_id=' . $futurebb_user['id'] . ' AND rt.forum_id IS NULL LEFT JOIN `#^read_tracker` AS rtf ON rtf.forum_id=f.id AND rtf.user_id=' . $futurebb_user['id'] . ' AND rtf.topic_id IS NULL WHERE f.id IS NOT NULL AND t.url=\'' . $db->escape($dirs[2]) . '\' AND t.deleted IS NULL') or error('Failed to get topic info', __FILE__, __LINE__, $db->error());
$result = $db->query('SELECT t.id,t.url,t.subject,t.closed,t.sticky,t.last_post,t.last_post_id,t.first_post_id,t.redirect_id,t.deleted,f.name AS forum_name,f.id AS forum_id,f.url AS forum_url,f.view_groups,f.reply_groups,f.archived AS forum_archived,rt.id AS tracker_id,du.username AS deleted_by FROM `#^topics` AS t LEFT JOIN `#^forums` AS f ON f.url=\'' . $db->escape($dirs[1]) . '\' LEFT JOIN `#^read_tracker` AS rt ON rt.topic_id=t.id AND rt.user_id=' . $futurebb_user['id'] . ' AND rt.forum_id IS NULL LEFT JOIN `#^users` AS du ON du.id=t.deleted_by WHERE f.id IS NOT NULL AND t.url=\'' . $db->escape($dirs[2]) . '\' AND t.forum_id=f.id ' . (($futurebb_user['g_mod_privs'] && $futurebb_user['g_mod_delete_posts']) || $futurebb_user['g_admin_privs']  ? '' : ' AND t.deleted IS NULL')) or error('Failed to get topic info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result)) {
	httperror(404);
}
$cur_topic = $db->fetch_assoc($result);
if (!strstr($cur_topic['view_groups'], '-' . $futurebb_user['group_id'] . '-')) {
	httperror(403);
}

//page header stuff
$breadcrumbs = array(translate('index') => '', $cur_topic['forum_name'] => $cur_topic['forum_url'], $cur_topic['subject'] => $cur_topic['forum_url'] . '/' . $cur_topic['url']);
$page_title = $cur_topic['subject'] . ' - ' . $cur_topic['forum_name'];
$other_head_stuff = array('<link rel="alternate" type="application/atom+xml" href="' . $base_config['baseurl'] . '/atom/' . htmlspecialchars($dirs[1]) . '/' . htmlspecialchars($dirs[2]) . '" title="' . translate('atomfeed') . '" />');

if ($cur_topic['redirect_id'] != null) {
	$result = $db->query('SELECT t.url AS turl,f.url AS furl FROM `#^topics` AS t LEFT JOIN `#^forums` AS f ON f.id=t.forum_id WHERE t.id=' . $cur_topic['redirect_id']) or error('Failed to get redirect info', __FILE__, __LINE__, $db->error());
	list($turl, $furl) = $db->fetch_row($result);
	redirect($base_config['baseurl'] . '/' . $furl . '/' . $turl);
	return;
}

if ($futurebb_user['g_mod_privs'] && isset($dirs[3]) && $dirs[3] == 'move') {
	if (isset($_POST['form_sent'])) {
		//check forum permissions first
		$result = $db->query('SELECT f.url FROM `#^forums` AS f WHERE id=' . intval($_POST['fid']) . ' AND f.view_groups LIKE \'%-' . $futurebb_user['group_id'] . '-%\' AND f.topic_groups LIKE \'%-' . $futurebb_user['group_id'] . '-%\'') or enhanced_error('Failed to search for forum info', true);
		list($newfurl) = $db->fetch_row($result);
		
		$name = URLEngine::make_friendly($cur_topic['subject']);
		$base_name = $name;
		//check for topics with the same URL
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
		$db->query('UPDATE `#^topics` SET url=\'' . $db->escape($name) . '\' WHERE id=' . $cur_topic['id']) or error('Failed to update URL', __FILE__, __LINE__, $db->error());
		$db->query('INSERT INTO `#^topics`(subject,url,forum_id,last_post,last_post_id,first_post_id,redirect_id,show_redirect) VALUES(\'' . $db->escape($cur_topic['subject']) . '\',\'' . $db->escape($dirs[2]) . '\',' . $cur_topic['forum_id'] . ',' . $cur_topic['last_post'] . ',' . $cur_topic['last_post_id'] . ',' . $cur_topic['first_post_id'] . ',' . $cur_topic['id'] . ',' . (isset($_POST['redirect']) ? 1 : 0) . ')') or error('Failed to make redirect', __FILE__, __LINE__, $db->error());
		$db->query('UPDATE `#^topics` SET forum_id=' . intval($_POST['fid']) . ' WHERE id=' . $cur_topic['id']) or error('Failed to move topic', __FILE__, __LINE__, $db->error());
		redirect($base_config['baseurl'] . '/' . $newfurl . '/' . $name); return;
	}
	?>
	<div class="forum_content">
		<h2><?php echo translate('movetopic'); ?></h2>
		<form action="<?php echo $base_config['baseurl']; ?>/<?php echo htmlspecialchars($dirs[1]); ?>/<?php echo htmlspecialchars($dirs[2]); ?>/move" method="post" enctype="multipart/form-data">
			<p><?php echo translate('movetoforum'); ?> <select name="fid"><?php
			$result = $db->query('SELECT f.name,f.id,c.id AS cid,c.name AS cname FROM `#^forums` AS f LEFT JOIN `#^categories` AS c ON c.id=f.cat_id WHERE c.id IS NOT NULL AND f.view_groups LIKE \'%-' . $futurebb_user['group_id'] . '-%\' AND f.topic_groups LIKE \'%-' . $futurebb_user['group_id'] . '-%\' ORDER BY c.sort_position ASC,f.sort_position ASC') or error('Failed to get forums', __FILE__, __LINE__, $db->error());
			$last_id = 0;
			while ($cur_forum = $db->fetch_assoc($result)) {
				if ($last_id != $cur_forum['cid']) {
					if ($last_id != 0) {
						echo '</optgroup>';
					}
					echo '<optgroup label="' . htmlspecialchars($cur_forum['cname']) . '">';
					$last_id = $cur_forum['cid'];
				}
				echo '<option value="' . $cur_forum['id'] . '">' . htmlspecialchars($cur_forum['name']) . '</option>';
			}
			if ($last_id != 0) {
				echo '</optgroup>';
			}
			?></select><br /><input type="checkbox" name="redirect" value="1" id="redirect" /><label for="redirect"><?php echo translate('leaveredirect'); ?></label></p>
			<p><input type="submit" name="form_sent" value="<?php echo translate('move'); ?>" /></p>
		</form>
	</div>
	<?php
	return;
}

//mark topic as read
if ($futurebb_user['id'] != 0 && $cur_topic['tracker_id'] == null) {
	$db->query('INSERT INTO `#^read_tracker`(user_id,topic_id) VALUES(' . $futurebb_user['id'] . ',' . $cur_topic['id'] . ')') or error('Failed to mark topic as read', __FILE__, __LINE__, $db->error());
}

if (isset($_GET['action']) && ($futurebb_user['g_mod_privs'] || $futurebb_user['g_admin_privs']) && $_GET['action'] == 'close') {
	$db->query('UPDATE `#^topics` SET closed=1 WHERE url=\'' . $db->escape($dirs[2]) . '\'') or error('Failed to close topic', __FILE__, __LINE__, $db->error());
	$cur_topic['closed'] = 1;
}
if (isset($_GET['action']) && ($futurebb_user['g_mod_privs'] || $futurebb_user['g_admin_privs']) && $_GET['action'] == 'open') {
	$db->query('UPDATE `#^topics` SET closed=0 WHERE url=\'' . $db->escape($dirs[2]) . '\'') or error('Failed to open topic', __FILE__, __LINE__, $db->error());
	$cur_topic['closed'] = 0;
}

if (isset($_GET['action']) && ($futurebb_user['g_mod_privs'] || $futurebb_user['g_admin_privs']) && $_GET['action'] == 'stick') {
	$db->query('UPDATE `#^topics` SET sticky=1 WHERE url=\'' . $db->escape($dirs[2]) . '\'') or error('Failed to stick topic', __FILE__, __LINE__, $db->error());
	$cur_topic['sticky'] = 1;
}
if (isset($_GET['action']) && ($futurebb_user['g_mod_privs'] || $futurebb_user['g_admin_privs']) && $_GET['action'] == 'unstick') {
	$db->query('UPDATE `#^topics` SET sticky=0 WHERE url=\'' . $db->escape($dirs[2]) . '\'') or error('Failed to unstick topic', __FILE__, __LINE__, $db->error());
	$cur_topic['sticky'] = 0;
}

if (isset($_GET['page'])) {
	$page = intval($_GET['page']);
} else {
	$page = 1;
}

$result = $db->query('SELECT COUNT(id) FROM `#^posts` WHERE topic_id=' . $cur_topic['id'] . (($futurebb_user['g_mod_privs'] && $futurebb_user['g_mod_delete_posts']) || $futurebb_user['g_admin_privs'] ? '' : ' AND deleted IS NULL')) or error('Failed to get post count', __FILE__, __LINE__, $db->error());
list($num_posts) = $db->fetch_row($result);

//get all of the posts
$result = $db->query('SELECT p.id,p.parsed_content,p.posted,p.poster_ip,p.last_edited,p.deleted AS deleted,u.username AS author,u.id AS author_id,u.parsed_signature AS signature,u.last_page_load,u.num_posts,u.avatar_extension,g.g_title AS user_title,leu.username AS last_edited_by,du.username AS deleted_by FROM `#^posts` AS p LEFT JOIN `#^users` AS u ON u.id=p.poster LEFT JOIN `#^user_groups` AS g ON g.g_id=u.group_id LEFT JOIN `#^users` AS leu ON leu.id=p.last_edited_by LEFT JOIN `#^users` AS du ON du.id=p.deleted_by WHERE p.topic_id=' . $cur_topic['id'] . (($futurebb_user['g_mod_privs'] && $futurebb_user['g_mod_delete_posts']) || $futurebb_user['g_admin_privs'] ? '' : ' AND p.deleted IS NULL') . ' ORDER BY p.posted ASC LIMIT ' . (($page - 1) * intval($futurebb_config['posts_per_page'])) . ',' . intval($futurebb_config['posts_per_page'])) or error('Failed to get posts', __FILE__, __LINE__, $db->error());
if (($futurebb_user['g_mod_privs'] && $futurebb_user['g_mod_delete_posts']) || $futurebb_user['g_admin_privs']) {
?>
<form action="<?php echo $base_config['baseurl']; ?>/admin/postactions" method="post" enctype="multipart/form-data">
<?php
}
?>
<p><?php echo translate('pages');
echo paginate('<a href="' . $base_config['baseurl'] . '/' . htmlspecialchars($dirs[1]) . '/' . htmlspecialchars($dirs[2]) . '?page=$page$" $bold$>$page$</a>', $page, ceil($num_posts / $futurebb_config['posts_per_page']));
?></p>
<p><?php
if ($cur_topic['deleted']) {
	echo translate('deletedbyon', translate('topic'), htmlspecialchars($cur_topic['deleted_by']), user_date($cur_topic['deleted']));
}
?></p>
<?php

if (!$db->num_rows($result)) {
	error('This topic has no posts on it. Please run the maintenance utility to remove orphans.');
}
$count = 0;
while ($cur_post = $db->fetch_assoc($result)) {
	$count++;
	?>
	<div class="catwrap" id="post<?php echo $cur_post['id']; ?>">
		<h2 class="cat_header">
		<?php echo '<span class="floatright">#' . ((($page - 1) * intval($futurebb_config['posts_per_page'])) + $count) . '</span><span style="display:none">: </span>'; 
		if ($cur_post['deleted'] || $cur_topic['deleted']) {
			echo '&#10060;';
		}
		?>
		<a href="<?php echo $base_config['baseurl']; ?>/posts/<?php echo $cur_post['id']; ?>"><?php echo user_date($cur_post['posted']); ?></a><?php
		// Show edit timestamp if available
		if ($cur_post['last_edited'] != null) {
			echo ' - <span style="cursor: default;" title="' . translate('lastedited', htmlspecialchars($cur_post['last_edited_by']), user_date($cur_post['last_edited'])) . '">' . translate('edited') . '</span>';
		}
		if ($cur_post['deleted']) {
			echo '<br />' . translate('deletedbyon', translate('post'), htmlspecialchars($cur_post['deleted_by']), user_date($cur_post['deleted']));
		}
		?></h2>
		<div class="cat_body<?php if ($cur_post['deleted'] || $cur_topic['deleted']) echo ' deleted_post'; ?>">
			<div class="postleft">
				<p><?php if($futurebb_config['online_timeout'] > 0) {
					if ($cur_post['last_page_load'] > time() - $futurebb_config['online_timeout']) {
						echo '<img class="svgimg" src="' . $base_config['baseurl'] . '/static/img/status/online.png" height="10" alt="online" title="Online" />';
					} else {
						echo '<img class="svgimg" src="' . $base_config['baseurl'] . '/static/img/status/offline.png" height="10" alt="offline" title="Offline" />';
					}
				}
				?>
				<a href="<?php echo $base_config['baseurl']; ?>/users/<?php echo htmlspecialchars($cur_post['author']); ?>"><?php echo htmlspecialchars($cur_post['author']); ?></a><br /></p>
				<p><b><?php echo $cur_post['user_title']; ?></b>
				<?php
				if ($futurebb_config['avatars'] && file_exists(FORUM_ROOT . '/static/avatars/' . $cur_post['author_id'] . '.' . $cur_post['avatar_extension'])) {
					echo '<br /><img src="' . $base_config['baseurl'] . '/static/avatars/' . $cur_post['author_id'] . '.' . $cur_post['avatar_extension'] . '" alt="user avatar" class="avatar" />';
				}
				if ($futurebb_config['show_post_count']) {
					echo '<br />' . translate('posts:') . $cur_post['num_posts'];
				}
				?>
				</p>
				<?php
				$actions = array();
				if ($futurebb_user['id'] != 0) {
					$actions[] = '<a href="' . $base_config['baseurl'] . '/report/' . $cur_post['id'] . '">' . translate('report') . '</a>';
				}
				if (($futurebb_user['g_mod_privs'] && $futurebb_user['g_mod_edit_posts']) || $futurebb_user['g_admin_privs'] || ($cur_post['author_id'] == $futurebb_user['id'] && $futurebb_user['g_edit_posts'] && !$cur_topic['closed'] && !$cur_topic['forum_archived'])) {
					$actions[] = '<a href="' . $base_config['baseurl'] . '/edit/' . $cur_post['id'] . '">' . translate('edit') . '</a>';
				}
				if (($futurebb_user['g_mod_privs'] && $futurebb_user['g_mod_delete_posts']) || $futurebb_user['g_admin_privs'] || ($cur_post['author_id'] == $futurebb_user['id'] && $futurebb_user['g_delete_posts'] && !$cur_topic['closed'] && !$cur_topic['forum_archived'])) {
					$actions[] = '<a href="' . $base_config['baseurl'] . '/delete/' . $cur_post['id'] . '">' . translate('delete') . '</a>';
				}
				if (strstr($cur_topic['reply_groups'], '-' . $futurebb_user['group_id'] . '-') && ((!$cur_topic['closed'] && !$cur_topic['forum_archived']) || $futurebb_user['g_mod_privs'])) {
					$actions[] = '<a href="' . $base_config['baseurl'] . '/post/topic/' . $cur_topic['id'] . '?quote=' . $cur_post['id'] . '">' . translate('quote') . '</a>';
				}
				if ($futurebb_user['g_mod_privs'] && $cur_post['deleted']) {
					$actions[] = '<a href="' . $base_config['baseurl'] . '/admin/trash_bin/undelete/post/' . $cur_post['id'] . '">' . translate('undelete') . '</a>';
				}
				if ($futurebb_user['g_mod_privs'] && $cur_topic['deleted'] && $cur_post['id'] == $cur_topic['first_post_id']) {
					$actions[] = '<a href="' . $base_config['baseurl'] . '/admin/trash_bin/undelete/topic/' . $cur_topic['id'] . '">' . translate('undelete') . ' ' . strtolower(translate('topic')) . '</a>';
				}
				if (($futurebb_user['g_mod_privs'] || $futurebb_user['g_admin_privs']) && $count > 1) {
					$actions[] = '<label for="post_action_' . $cur_post['id'] . '">' . translate('select') . '</label> <input type="checkbox" id="post_action_' . $cur_post['id'] . '" name="post_action[' . $cur_post['id'] . ']" value="' . $cur_post['id'] . '" />';
				}
			?>
			</div>
			<div class="postright">
				<p><?php echo $cur_post['parsed_content']; ?></p>
				<?php
				if ($cur_post['signature']) {
					echo '<hr /><p';
					if ($futurebb_config['sig_max_height']) {
						echo ' style="max-height:' . $futurebb_config['sig_max_height'] . 'px; overflow:hidden"';
					}
					echo '>' . $cur_post['signature'] . '</p>';
				}
				if (($futurebb_user['g_mod_privs'] && $futurebb_user['g_mod_view_ip']) || $futurebb_user['g_admin_privs']) {
					echo '<hr /><p class="ipaddress">IP: <a href="' . $base_config['baseurl'] . '/admin/ip_tracker/?ip=' . $cur_post['poster_ip'] . '">' . $cur_post['poster_ip'] . '</a></p>';
				}
				?>
				
			</div>
			<?php if (!empty($actions)) { ?>
			<div class="clearboth">
				<p>&nbsp;<span class="postactions"><?php echo implode(' | ', $actions); ?></span></p>
			</div>
			<?php } ?>
		</div>
	</div>
	
	<?php
}
?>
<$breadcrumbs/>
<p><?php echo translate('pages');
echo paginate('<a href="' . $base_config['baseurl'] . '/' . htmlspecialchars($dirs[1]) . '/' . htmlspecialchars($dirs[2]) . '?page=$page$" $bold$>$page$</a>', $page, ceil($num_posts / $futurebb_config['posts_per_page']));
?></p>
<?php
if (($futurebb_user['g_mod_privs'] && $futurebb_user['g_mod_delete_posts']) || $futurebb_user['g_admin_privs']) { ?>
	<p>
		<input type="hidden" name="type" value="posts" />
		<input type="submit" name="form_sent_delete" value="<?php echo translate('delete'); ?>" />
		<input type="submit" name="form_sent_undelete" value="<?php echo translate('undelete'); ?>" />
	</p>
</form>
<?php
}
if (strstr($cur_topic['reply_groups'], '-' . $futurebb_user['group_id'] . '-') && ((!$cur_topic['closed'] && !$cur_topic['forum_archived']) || $futurebb_user['g_mod_privs'])) {
	?>
	<div class="cat_wrap">
		<h2 class="cat_header"><?php echo translate('postreply'); ?></h2>
		<div class="cat_body">
			<form action="<?php echo $base_config['baseurl']; ?>/post/topic/<?php echo $cur_topic['id']; ?>" method="post" enctype="multipart/form-data">
				<?php ExtensionConfig::run_hooks('bbcode_toolbar'); ?>
				<p><?php if ($cur_topic['closed'] || $cur_topic['forum_archived']) echo '<span class="closedlabel">' . translate('topicisclosed') . '</span><br />'; ?>
				<textarea name="message" id="message" rows="10" cols="70"<?php if ($cur_topic['closed'] || $cur_topic['forum_archived']) echo ' style="background-color: #DDD; border-color: #AAA;"'; ?>></textarea></p>
                <?php ExtensionConfig::run_hooks('bbcode_toolbar_bottom'); ?>
				<p><a href="<?php echo $base_config['baseurl']; ?>/bbcodehelp"><?php echo translate('bbcode'); ?></a>: <?php if ($futurebb_config['enable_bbcode']) echo translate('on'); else echo translate('off'); ?>, <a href="<?php echo $base_config['baseurl']; ?>/bbcodehelp#smilies"><?php echo translate('smilies'); ?></a>: <?php if ($futurebb_config['enable_smilies']) echo translate('on'); else echo translate('off'); ?>, <a href="<?php echo $base_config['baseurl']; ?>/bbcodehelp#linksimages"><?php echo translate('imgtag'); ?></a>: <?php if ($futurebb_user['g_post_links']) echo translate('on'); else echo translate('off'); ?>, <a href="<?php echo $base_config['baseurl']; ?>/bbcodehelp#linksimages"><?php echo translate('urltag'); ?></a>: <?php if ($futurebb_user['g_post_images']) echo translate('on'); else echo translate('off'); ?></p>
				<p><input type="submit" name="form_sent" value="<?php echo translate('post'); ?>" /> <input type="submit" name="preview" value="Preview" /> <input name="hidesmilies" type="checkbox" value="1" id="disablesmilies" /> <label for="disablesmilies"><?php echo translate('disablesmilies'); ?></label></p>
			</form>
		</div>
	</div>
	<?php
}
if ($futurebb_user['id'] != 0) { ?>
<div class="cat_wrap">
	<h2 class="cat_header"><?php echo translate('actions'); ?></h2>
	<div class="cat_body">
		<ul>
			<?php if (($cur_topic['closed'] || $cur_topic['forum_archived']) && !$futurebb_user['g_mod_privs']) { ?>
			<li><?php echo translate('closednoreply'); ?></li>
			<?php } else if (strstr($cur_topic['reply_groups'], '-' . $futurebb_user['group_id'] . '-')) { ?>
			<li><a href="<?php echo $base_config['baseurl']; ?>/post/topic/<?php echo $cur_topic['id']; ?>"><?php echo translate('postreply'); ?></a></li>
			<?php } ?>
			<?php if ($futurebb_user['g_mod_privs']) { ?>
			<?php if ($cur_topic['closed']) { ?>
			<li><a href="<?php echo $base_config['baseurl'] . '/' . htmlspecialchars($cur_topic['forum_url']) . '/' . htmlspecialchars($cur_topic['url']) . '?action=open'; ?>"><?php echo translate('opentopic'); ?></a></li>
			<?php } else { ?>
			<li><a href="<?php echo $base_config['baseurl'] . '/' . htmlspecialchars($cur_topic['forum_url']) . '/' . htmlspecialchars($cur_topic['url']) . '?action=close'; ?>"><?php echo translate('closetopic'); ?></a></li>
			<?php } ?>
			<?php if ($cur_topic['sticky']) { ?>
			<li><a href="<?php echo $base_config['baseurl'] . '/' . htmlspecialchars($cur_topic['forum_url']) . '/' . htmlspecialchars($cur_topic['url']) . '?action=unstick'; ?>"><?php echo translate('unsticktopic'); ?></a></li>
			<?php } else { ?>
			<li><a href="<?php echo $base_config['baseurl'] . '/' . htmlspecialchars($cur_topic['forum_url']) . '/' . htmlspecialchars($cur_topic['url']) . '?action=stick'; ?>"><?php echo translate('sticktopic'); ?></a></li>
			<?php } ?>
			<li><a href="<?php echo $base_config['baseurl'] . '/' . htmlspecialchars($cur_topic['forum_url']) . '/' . htmlspecialchars($cur_topic['url']) . '/move'; ?>"><?php echo translate('movetopic'); ?></a></li>
			<?php } ?>
		</ul>
	</div>
</div>
<?php }
?>
<div class="cat_wrap">
	<h2 class="cat_header lonecatheader"><?php echo translate('embed'); ?> <a style="cursor:pointer" onclick="document.getElementById('embeddiv').style.display = 'block'; this.style.display = 'none'; this.parentElement.style.borderBottom = 'none';">(<?php echo translate('show'); ?>)</a></h2>
	<div class="cat_body" id="embeddiv">
		<textarea rows="5" cols="30" readonly="readonly"><?php echo htmlspecialchars('<iframe src="' . $base_config['baseurl'] . '/embed?tid=' . $cur_topic['id'] . '&amp;page=' . $page . '" width="400px" height="600px"></iframe>'); ?></textarea>
	</div>
    <script type="text/javascript">
	document.getElementById('embeddiv').style.display = 'none';
	</script>
</div>
<?php
//should we mark the forum as read?
$result = $db->query('SELECT 1 FROM `#^read_tracker` WHERE user_id=' . $futurebb_user['id'] . ' AND forum_id=' . $cur_topic['forum_id']) or error('Failed to check if forum is read');
if ($futurebb_user['id'] != 0 && !$db->num_rows($result) && $cur_topic['tracker_id'] == null) {
	$result = $db->query('SELECT 1 FROM `#^topics` AS t LEFT JOIN `#^read_tracker` AS rt ON rt.topic_id=t.id AND rt.user_id=' . $futurebb_user['id'] . ' AND rt.forum_id IS NULL WHERE t.forum_id=' . $cur_topic['forum_id'] . ' AND rt.id IS NULL AND t.redirect_id IS NULL AND t.deleted IS NULL') or error('Failed to check if there are any unread posts in the forum', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result)) {
		$db->query('INSERT INTO `#^read_tracker`(user_id,forum_id) VALUES(' . $futurebb_user['id'] . ',' . $cur_topic['forum_id'] . ')') or error('Failed to mark forum as read', __FILE__, __LINE__, $db->error());
	}
}

//send Atom URL back to dispatcher to put next to breadcrumbs
$feed_url = 'atom/' . htmlspecialchars($dirs[1]) . '/' . htmlspecialchars($dirs[2]);
?>