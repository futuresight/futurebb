<?php
$page_title = 'Index';
if (isset($_GET['action']) && $_GET['action'] == 'markread') {
	$db->query('UPDATE `#^users` SET last_visit=' . time() . ' WHERE id=' . $futurebb_user['id']) or error('Failed to mark all topics as read', __FILE__, __LINE__, $db->error());
	header('Location: ' . $base_config['baseurl']); die;
}
$result = $db->query('SELECT c.id AS cat_id,c.name AS cat_name,f.name AS forum_name,f.id AS fid,f.url,f.last_post,f.description,f.last_post,f.last_post_id,f.num_topics,f.num_posts,rt.id AS tracker_id,lpa.username AS last_poster,f.archived FROM `#^forums` AS f LEFT JOIN `#^categories` AS c ON c.id=f.cat_id LEFT JOIN `#^read_tracker` AS rt ON rt.forum_id=f.id AND rt.user_id=' . $futurebb_user['id'] . ' AND rt.topic_id IS NULL LEFT JOIN `#^posts` AS lp ON lp.id=f.last_post_id LEFT JOIN `#^users` AS lpa ON lpa.id=lp.poster WHERE c.id IS NOT NULL AND view_groups LIKE \'%-' . $futurebb_user['group_id'] . '-%\' ORDER BY c.sort_position ASC,f.sort_position ASC') or error('Failed to get categories', __FILE__, __LINE__, $db->error());
$ids = array();
if ($db->num_rows($result)) {
	$last_cid = 0;
	while ($cur_forum = $db->fetch_assoc($result)) {
		if ($cur_forum['cat_id'] != $last_cid) {
			if ($last_cid != 0) {
				echo '</tbody></table></div></div>';
			}
			$last_cid = $cur_forum['cat_id'];
			echo '<div class="cat_wrap"><h2 class="cat_header">' . htmlspecialchars($cur_forum['cat_name']) . '</h2>
			<div class="cat_body"><table border="0" class="fullwidth"><thead><tr><th>' . translate('forum') . '</th><th>' . translate('topics') . '</th><th>' . translate('posts') . '</th><th>' . translate('lastpost') . '</th></tr></thead><tbody>';
		}
		if (!in_array($cur_forum['fid'], $ids)) {
			$ids[] = $cur_forum['fid'];
			echo '<tr class="table-row"><td class="forum_info"><div class="relative"><a href="' . $base_config['baseurl'] . '/' . htmlspecialchars($cur_forum['url']) . '"';
			//$cur_forum['last_post'] > $futurebb_user['last_visit'] #old tracker
			$class = array();
			if ($futurebb_user['id'] != 0 && $futurebb_user['last_visit'] < $cur_forum['last_post'] && $cur_forum['tracker_id'] == null) {
				$class[] = 'unread';
			}
			if ($cur_forum['archived']) {
				$class[] = 'archived';
			}
			if (!empty($class)) {
				echo ' class="' . implode(' ', $class) . '"';
			}
			echo '>' . htmlspecialchars($cur_forum['forum_name']) . '</a>';
			if ($cur_forum['archived']) {
				echo ' ' . translate('archived');
			}
			echo '<br />' . $cur_forum['description'] . '</div></td><td class="forum_number">' . $cur_forum['num_topics'] . '</td><td class="forum_number">' . $cur_forum['num_posts'] . '</td><td class="forum_last_post">';
			if ($cur_forum['last_post_id']) {
				echo '<a href="' . $base_config['baseurl'] . '/posts/' . $cur_forum['last_post_id'] . '">' . user_date($cur_forum['last_post']) . ' by ' . htmlspecialchars($cur_forum['last_poster']) . '</a>';
			} else {
				echo 'None';
			}
			echo '</td></tr>';
		}
	}
	echo '</tbody></table></div></div>';
	if ($futurebb_user['id'] != 0) {
		echo '<p class="alignright"><a href="?action=markread">' . translate('markallread') . '</a></p>';
	}
} else {
	echo '<p>' . translate('noforums') . '</p>';
}
?>
<div class="cat_wrap" id="users_online">
	<h2 class="cat_header"><?php echo translate('usersonline'); ?></h2>
	<div class="cat_body" style="text-align:center">
		<?php
		$result = $db->query('SELECT id,username,avatar_extension FROM `#^users` WHERE last_page_load>' . (time() - $futurebb_config['online_timeout']) . ' AND username<>\'Guest\' ORDER BY RAND() LIMIT 10') or error('Failed to get online list', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result)) {
			echo translate('nobody');
		}
		$online = array();
		if ($futurebb_config['avatars']) {
			while (list($id,$username,$avatar_ext) = $db->fetch_row($result)) {
				if (file_exists(FORUM_ROOT . '/static/avatars/' . $id . '.' . $avatar_ext)) {
					$online[] = '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($username) . '"><img src="' . $base_config['baseurl'] . '/static/avatars/' . $id . '.' . $avatar_ext . '" alt="avatar" style="max-width:36px; max-height:36px" />' . '<br />' . htmlspecialchars($username) . '</a>';
				} else {
					$online[] = '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($username) . '">' . htmlspecialchars($username) . '</a>';
				}
			}
			echo '<table border="0" style="width:100%"><tr><td>' . implode('</td><td>', $online) . '</td></tr></table>';
		} else {
			while (list($id,$username) = $db->fetch_row($result)) {
				$online[] = '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($username) . '">' . htmlspecialchars($username) . '</a>';
			}
			echo implode(', ', $online);
		}
		if ($db->num_rows($result)) { ?>
		<br /><a href="<?php echo $base_config['baseurl']; ?>/online_list"><?php echo translate('seeall'); ?></a>
		<?php } ?>
	</div>
</div>
<?php
