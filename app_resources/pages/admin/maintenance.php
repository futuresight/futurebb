<?php
if (!$futurebb_user['g_admin_privs']) {
	httperror(403);
}
$page_title = translate('maintenance');
include FORUM_ROOT . '/app_resources/includes/admin.php';
if (!isset($dirs[3])) {
	$dirs[3] = '';
}
switch ($dirs[3]) {
	case 'removeorphans':
		$result = $db->query('SELECT t.id FROM `#^topics` AS t LEFT JOIN `#^posts` AS p ON p.topic_id=t.id AND p.deleted IS NULL WHERE p.id IS NULL AND t.deleted IS NULL LIMIT 30') or error('Failed to find orphans', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result)) {
			header('Location: ' . $base_config['baseurl'] . '/admin/maintenance'); return;
		}
		$ids = array();
		while (list($id) = $db->fetch_row($result)) {
			$ids[] = $id;
		}
		$db->query('UPDATE `#^topics` SET deleted=' . time() . ',deleted_by=0 WHERE id IN(' . implode(',', $ids) . ')') or error('Failed to delete orphans', __FILE__, __LINE__, $db->error());
		header('Refresh: 1');
		break;
	case 'updatelastposts':
		if (!isset($_GET['part'])) {
			$_GET['part'] = 'topics';
		}
		if (!isset($_GET['start'])) {
			$_GET['start'] = 0;
		}
		if ($_GET['start'] == 0) {
			if ($_GET['part'] == 'topics') {
				$db->query('UPDATE `#^topics` SET last_post=0,last_post_id=0') or error('Failed to reset all data', __FILE__, __LINE__, $db->error());
			}
			if ($_GET['part'] == 'forums') {
				$db->query('UPDATE `#^forums` SET last_post=0') or error('Failed to reset all data', __FILE__, __LINE__, $db->error());
			}
		}
		$start = intval($_GET['start']);
		if ($_GET['part'] == 'topics') {
			$per_page = 40;
			$result = $db->query('SELECT MAX(id) FROM `#^topics`') or error('Failed to get topic count', __FILE__, __LINE__, $db->error());
			list($num_topics) = $db->fetch_row($result);
			$result = $db->query('SELECT id FROM `#^topics` WHERE deleted IS NULL ORDER BY id LIMIT ' . $start . ',' . intval($per_page)) or error('Failed to get IDs', __FILE__, __LINE__, $db->error());
			echo '<div class="forum_content">';
			if (!$db->num_rows($result)) {
				header('Location: ?part=forums'); return;
			}
			$first = true;
			while (list($id) = $db->fetch_row($result)) {
				if ($first) {
					echo '<p style="border: 1px solid #000; margin-left:5%; margin-right:5%; padding:0px">
						<span style="width:' . (($id / $num_topics) * 100) . '%; background-color: #39F; display:block; padding-left: 1px; padding-top: 1px; padding-bottom: 1px; margin-left:0px">&nbsp;</span>
				</p>';
					$first = false;
				}
				$r2 = $db->query('SELECT id,posted FROM `#^posts` WHERE topic_id=' . $id . ' AND deleted IS NULL ORDER BY posted DESC LIMIT 1') or error('Failed to get last post', __FILE__, __LINE__, $db->error());
				echo '<p>' . translate('updatingtopic', $id . '/' . $num_topics) . '.</p>';
				if ($db->num_rows($r2)) {
					list($lastpostid,$lastposttime) = $db->fetch_row($r2);
				} else {
					$lastpostid = 0;
					$lastposttime = 0;
				}
				$r2 = $db->query('SELECT id FROM `#^posts` WHERE topic_id=' . $id . ' AND deleted IS NULL ORDER BY posted ASC LIMIT 1') or error('Failed to get last post', __FILE__, __LINE__, $db->error());
				if ($db->num_rows($r2)) {
					list($firstpostid) = $db->fetch_row($r2);
				} else {
					$firstpostid = 0;
				}
				$db->query('UPDATE `#^topics` SET last_post=' . $lastposttime . ',last_post_id=' . $lastpostid . ',first_post_id=' . $firstpostid . ' WHERE id=' . $id) or error('Failed to update last post', __FILE__, __LINE__, $db->error());
			}
			echo '<p>' . translate('redirmsg', '?start=' . ($start + $per_page) . '&amp;part=topics') . '</p></div>';
			header('Refresh: 1;url=?start=' . ($start + $per_page) . '&part=topics');
		} else if ($_GET['part'] == 'forums') {
			$per_page = 20;
			$result = $db->query('SELECT MAX(id) FROM `#^forums`') or error('Failed to get forum count', __FILE__, __LINE__, $db->error());
			list($num_forums) = $db->fetch_row($result);
			$result = $db->query('SELECT id FROM `#^forums` ORDER BY id LIMIT ' . $start . ',' . intval($per_page)) or error('Failed to get IDs', __FILE__, __LINE__, $db->error());
			echo '<div class="forum_content">';
			if (!$db->num_rows($result)) {
				header('Location: ' . $base_config['baseurl'] . '/admin/maintenance'); return;
			}
			while (list($id) = $db->fetch_row($result)) {
				$r2 = $db->query('SELECT p.posted,p.id FROM `#^posts` AS p LEFT JOIN `#^topics` AS t ON t.id=p.topic_id WHERE t.forum_id=' . $id . ' AND p.deleted IS NULL AND t.deleted IS NULL ORDER BY p.posted DESC LIMIT 1') or error('Failed to get last post', __FILE__, __LINE__, $db->error());
				if ($db->num_rows($r2)) {
					list($lastposttime,$lastpostid) = $db->fetch_row($r2);
				} else {
					$lastposttime = 0;
					$lastpostid = 0;
				}
				$db->query('UPDATE `#^forums` SET last_post=' . $lastposttime . ',last_post_id=' . $lastpostid . ' WHERE id=' . $id) or error('Failed to update last post', __FILE__, __LINE__, $db->error());
				echo '<p>' . translate('updatingforum', $id . '/' . $num_forums) . '.</p>';
			}
			echo '<p>' . translate('redirmsg', '?start=' . ($start + $per_page) . '&amp;part=forums') . '</p></div>';
			header('Refresh: 1;url=?start=' . ($start + $per_page) . '&part=forums');
		} else {
			httperror(404);
		}
		break;
	case 'reparse_posts':
		include FORUM_ROOT . '/app_resources/includes/parser.php';
		$per_page = 300;
		if (!isset($_GET['start'])) {
			$_GET['start'] = 0;
			$db->query('UPDATE `#^posts` SET parsed_content=\'Post awaiting reparsing\'') or error('Failed to erase all parsed posts', __FILE__, __LINE__, $db->error());
		}
		$result = $db->query('SELECT MAX(id) FROM `#^posts`') or error('Failed to find post count', __FILE__, __LINE__, $db->error());
		list($post_count) = $db->fetch_row($result);
		$result = $db->query('SELECT id,content,disable_smilies FROM `#^posts` ORDER BY id LIMIT ' . intval($_GET['start']) . ',' . $per_page) or error('Failed to get posts', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result)) {
			header('Location: ' . $base_config['baseurl'] . '/admin/maintenance'); return;
		}
		echo '<div class="forum_content">';
		$first = true;
		$outid = 0;
		while (list($id,$post,$disable_smilies) = $db->fetch_row($result)) {
			if ($first) {
				echo '<p style="border: 1px solid #000; margin-left:5%; margin-right:5%; padding:0px">
						<span style="width:' . (($id / $post_count) * 100) . '%; background-color: #39F; display:block; padding-left: 1px; padding-top: 1px; padding-bottom: 1px; margin-left:0px">&nbsp;</span>
				</p>
				<h3>' . translate('reparsingposts') . '</h3>';
				$first = false;
			}
			echo '<p>' . translate('reparsingpost', $id) . '</p>';
			$post = BBCodeController::parse_msg($post, !$disable_smilies);
			$db->query('UPDATE `#^posts` SET parsed_content=\'' . $db->escape($post) . '\' WHERE id=' . $id) or error('Failed to update post', __FILE__, __LINE__, $db->error());
			$outid = $id;
		}
		echo '<p>' . translate('redirmsg', '?start=' . ($_GET['start'] + $per_page)) . '</p>';
		echo '</div>';
		header('Refresh: 1; url=' . $base_config['baseurl'] . '/admin/maintenance/reparse_posts?start=' . ($_GET['start'] + $per_page));
		return;
	case 'reparse_sigs':
		include FORUM_ROOT . '/app_resources/includes/parser.php';
		$per_page = 300;
		if (!isset($_GET['start'])) {
			$_GET['start'] = 0;
			$db->query('UPDATE `#^users` SET parsed_signature=\'Signature awaiting reparsing\'') or error('Failed to erase all parsed signatures', __FILE__, __LINE__, $db->error());
		}
		$result = $db->query('SELECT MAX(id) FROM `#^users`') or error('Failed to find post count', __FILE__, __LINE__, $db->error());
		list($user_count) = $db->fetch_row($result);
		$result = $db->query('SELECT id,signature FROM `#^users` ORDER BY id LIMIT ' . intval($_GET['start']) . ',' . $per_page) or error('Failed to get posts', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result)) {
			header('Location: ' . $base_config['baseurl'] . '/admin/maintenance'); return;
		}
		echo '<div class="forum_content">';
		$first = true;
		$outid = 0;
		while (list($id,$sig) = $db->fetch_row($result)) {
			if ($first) {
				echo '<p style="border: 1px solid #000; margin-left:5%; margin-right:5%; padding:0px;">
						<span style="width:' . (($id / $user_count) * 100) . '%; background-color: #39F; display:block; padding-left: 1px; padding-top: 1px; padding-bottom: 1px; margin-left:0px">&nbsp;</span>
				</p>
				<h3>' . translate('reparsingsigs') . '</h3>';
				$first = false;
			}
			echo '<p>' . translate('reparsingsig', $id) . '</p>';
			$sig = BBCodeController::parse_msg($sig);
			$db->query('UPDATE `#^users` SET parsed_signature=\'' . $db->escape($sig) . '\' WHERE id=' . $id) or error('Failed to update post', __FILE__, __LINE__, $db->error());
			$outid = $id;
		}
		echo '<p>' . translate('redirmsg', '?start=' . ($_GET['start'] + $per_page)) . '</p>';
		echo '</div>';
		header('Refresh: 1; url=' . $base_config['baseurl'] . '/admin/maintenance/reparse_sigs?start=' . ($_GET['start'] + $per_page));
		return;
	case 'rebuildsearch':
		include FORUM_ROOT . '/app_resources/includes/search.php';
		$per_page = 300;
		if (!isset($_GET['start'])) {
			$_GET['start'] = 0;
			$db->query('TRUNCATE TABLE `#^search_index`') or error('Failed to erase search index', __FILE__, __LINE__, $db->error());
		}
		$result = $db->query('SELECT MAX(id) FROM `#^posts`') or error('Failed to find post count', __FILE__, __LINE__, $db->error());
		list($post_count) = $db->fetch_row($result);
		$result = $db->query('SELECT id,content FROM `#^posts` ORDER BY id LIMIT ' . intval($_GET['start']) . ',' . $per_page) or error('Failed to get posts', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result)) {
			header('Location: ' . $base_config['baseurl'] . '/admin/maintenance'); return;
		}
		echo '<div class="forum_content">';
		$first = true;
		$outid = 0;
		while (list($id,$content) = $db->fetch_row($result)) {
			if ($first) {
				echo '<p style="border: 1px solid #000; margin-left:5%; margin-right:5%; padding:0px;">
						<span style="width:' . (($id / $post_count) * 100) . '%; background-color: #39F; display:block; padding-left: 1px; padding-top: 1px; padding-bottom: 1px; margin-left:0px">&nbsp;</span>
				</p>
				<h3>' . translate('rebuildingsearch') . '</h3>';
				$first = false;
			}
			update_search_index($id, $content);
			echo '<p>' . translate('procpost', $id) . '</p>';
			$outid = $id;
		}
		echo '<p>' . translate('redirmsg', '?start=' . ($_GET['start'] + $per_page)) . '</p>';
		echo '</div>';
		header('Refresh: 1; url=' . $base_config['baseurl'] . '/admin/maintenance/rebuildsearch?start=' . ($_GET['start'] + $per_page));
		return;
	case 'update_user_post_counts':
		$per_page = 300;
		if (!isset($_GET['start'])) {
			$_GET['start'] = 0;
			$db->query('UPDATE `#^users` SET num_posts=-1') or error('Failed to erase search index', __FILE__, __LINE__, $db->error());
		}
		$result = $db->query('SELECT MAX(id) FROM `#^users`') or error('Failed to find post count', __FILE__, __LINE__, $db->error());
		list($user_count) = $db->fetch_row($result);
		$result = $db->query('SELECT id FROM `#^users` ORDER BY id LIMIT ' . intval($_GET['start']) . ',' . $per_page) or error('Failed to get posts', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result)) {
			header('Location: ' . $base_config['baseurl'] . '/admin/maintenance'); return;
		}
		echo '<div class="forum_content">';
		$first = true;
		$outid = 0;
		while (list($id) = $db->fetch_row($result)) {
			if ($first) {
				echo '<p style="border: 1px solid #000; margin-left:5%; margin-right:5%; padding:0px;">
						<span style="width:' . (($id / $user_count) * 100) . '%; background-color: #39F; display:block; padding-left: 1px; padding-top: 1px; padding-bottom: 1px; margin-left:0px">&nbsp;</span>
				</p>
				<h3>' . translate('updatingpostcounts') . '</h3>';
				$first = false;
			}
			echo '<p>' . translate('recountinguser', $id) . '</p>';
			$r2 = $db->query('SELECT 1 FROM `#^posts` WHERE poster=' . $id . ' AND deleted IS NULL') or error('Failed to find posts by user', __FILE__, __LINE__, $db->error());
			$num_posts = $db->num_rows($r2);
			$db->query('UPDATE `#^users` SET num_posts=' . $num_posts . ' WHERE id=' . $id) or error('Failed to update user', __FILE__, __LINE__, $db->error());
			$outid = $id;
		}
		echo '<p>' . translate('redirmsg', '?start=' . ($_GET['start'] + $per_page)) . '</p>';
		echo '</div>';
		header('Refresh: 1; url=' . $base_config['baseurl'] . '/admin/maintenance/update_user_post_counts?start=' . ($_GET['start'] + $per_page));
		return;
	case 'update_forum_topic_counts':
		$r1 = $db->query('SELECT id FROM `#^forums`') or error('Failed to get forum list', __FILE__, __LINE__, $db->error());
		while (list($id) = $db->fetch_row($r1)) {
			$r2 = $db->query('SELECT 1 FROM `#^topics` WHERE forum_id=' . $id . ' AND deleted IS NULL') or error('Failed to find topics', __FILE__, __LINE__, $db->error());
			$num_topics = $db->num_rows($r2);
			$db->query('UPDATE `#^forums` SET num_topics=' . $num_topics . ' WHERE id=' . $id) or error('Failed to update forum info', __FILE__, __LINE__, $db->error());
		}
		redirect($base_config['baseurl'] . '/admin/maintenance');
		break;
	case 'update_forum_post_counts':
		$r1 = $db->query('SELECT id FROM `#^forums`') or error('Failed to get forum list', __FILE__, __LINE__, $db->error());
		while (list($id) = $db->fetch_row($r1)) {
			$r2 = $db->query('SELECT 1 FROM `#^posts` AS p LEFT JOIN `#^topics` AS t ON t.id=p.topic_id WHERE t.forum_id=' . $id . ' AND p.deleted IS NULL AND t.deleted IS NULL') or error('Failed to find posts', __FILE__, __LINE__, $db->error());
			$num_posts = $db->num_rows($r2);
			$db->query('UPDATE `#^forums` SET num_posts=' . $num_posts . ' WHERE id=' . $id) or error('Failed to update forum info', __FILE__, __LINE__, $db->error());
		}
		redirect($base_config['baseurl'] . '/admin/maintenance');
		break;
	case 'update_topic_post_counts':
		$per_page = 300;
		if (!isset($_GET['start'])) {
			$_GET['start'] = 0;
			$db->query('UPDATE `#^topics` SET num_replies=0') or error('Failed to zap all topics', __FILE__, __LINE__, $db->error());
		}
		$result = $db->query('SELECT MAX(id) FROM `#^topics`') or error('Failed to find topic count', __FILE__, __LINE__, $db->error());
		list($topic_count) = $db->fetch_row($result);
		$result = $db->query('SELECT id FROM `#^topics` ORDER BY id LIMIT ' . intval($_GET['start']) . ',' . $per_page) or error('Failed to get posts', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result)) {
			header('Location: ' . $base_config['baseurl'] . '/admin/maintenance'); return;
		}
		echo '<div class="forum_content">';
		$first = true;
		$outid = 0;
		while (list($id) = $db->fetch_row($result)) {
			if ($first) {
				echo '<p style="border: 1px solid #000; margin-left:5%; margin-right:5%; padding:0px;">
						<span style="width:' . (($id / $topic_count) * 100) . '%; background-color: #39F; display:block; padding-left: 1px; padding-top: 1px; padding-bottom: 1px; margin-left:0px">&nbsp;</span>
				</p>
				<h3>' . translate('recountingtopicreplies') . '</h3>';
				$first = false;
			}
			$r2 = $db->query('SELECT 1 FROM `#^posts` WHERE topic_id=' . $id . ' AND deleted IS NULL') or error('Failed to get reply count', __FILE__, __LINE__, $db->error());
			$db->query('UPDATE `#^topics` SET num_replies=' . ($db->num_rows($r2) - 1) . ' WHERE id=' . $id) or error('Failed to update topic', __FILE__, __LINE__, $db->error());
			echo '<p>Processing topic ' . $id . '.</p>';
			$outid = $id;
		}
		echo '<p>' . translate('redirmsg', '?start=' . ($_GET['start'] + $per_page)) . '</p>';
		echo '</div>';
		header('Refresh: 1; url=' . $base_config['baseurl'] . '/admin/maintenance/update_topic_post_counts?start=' . ($_GET['start'] + $per_page));
		return;
	case 'purgenotifs':
		$addl_sql = '';
		if (isset($_GET['excludeadmin'])) {
			$addl_sql .= ' AND type<>\'warning\'';
		}
		if (isset($_GET['excludeunread'])) {
			$addl_sql .= ' AND read_time>0';
		}
		$q = new DBDelete('notifications', 'send_time<' . (time() - 60 * 60 * 24 * 7 * intval($_GET['weeksold'])) . $addl_sql, 'Failed to delete old notifications');
		$q->commit();
		redirect($base_config['baseurl'] . '/admin/maintenance');
	case '':
?>
<div class="container">
	<?php make_admin_menu(); ?>
	<div class="forum_content rightbox admin">
		<h2><?php echo translate('maintenance'); ?></h2>
		<p><?php echo translate('maintenancedesc'); ?></p>
		<h3><?php echo translate('rebuildsearch'); ?></h3>
		<p><?php echo translate('rebuildsearchdesc'); ?><br /><a href="<?php echo $base_config['baseurl']; ?>/admin/maintenance/rebuildsearch"><?php echo translate('go'); ?></a></p>
		<h3><?php echo translate('deleteorphans'); ?></h3>
		<p><?php echo translate('deleteorphansdesc'); ?><br /><a href="<?php echo $base_config['baseurl']; ?>/admin/maintenance/removeorphans"><?php echo translate('go'); ?></a></p>
		<h3><?php echo translate('updatelastpost'); ?></h3>
		<p><?php echo translate('updatelastpostdesc'); ?><br /><a href="<?php echo $base_config['baseurl']; ?>/admin/maintenance/updatelastposts"><?php echo translate('go'); ?></a></p>
		<h3><?php echo translate('reparse'); ?></h3>
		<p><?php echo translate('reparsedesc'); ?><br /><a href="<?php echo $base_config['baseurl']; ?>/admin/maintenance/reparse_posts"><?php echo translate('reparseposts'); ?></a><br /><a href="<?php echo $base_config['baseurl']; ?>/admin/maintenance/reparse_sigs"><?php echo translate('reparsesigs'); ?></a></p>
		<h3><?php echo translate('updatecounts'); ?></h3>
		<p><a href="<?php echo $base_config['baseurl']; ?>/admin/maintenance/update_user_post_counts"><?php echo translate('updateuserpostcounts'); ?></a><br /><a href="<?php echo $base_config['baseurl']; ?>/admin/maintenance/update_forum_post_counts"><?php echo translate('updateforumpostcounts'); ?></a><br /><a href="<?php echo $base_config['baseurl']; ?>/admin/maintenance/update_forum_topic_counts"><?php echo translate('updateforumtopiccounts'); ?></a><br /><a href="<?php echo $base_config['baseurl']; ?>/admin/maintenance/update_topic_post_counts"><?php echo translate('updatetopicpostcounts'); ?></a></p>
		<h3>Purge notifications</h3>
		<p>This tool will delete old notifications that users have received.</p>
		<form action="<?php echo $base_config['baseurl']; ?>/admin/maintenance/purgenotifs" method="get">
			<p>Delete notifications older than <input type="text" name="weeksold" value="10" size="3" maxlength="3" /> weeks<br /><input type="checkbox" name="excludeunread" id="excludeunread" checked="checked" value="1" /><label for="excludeunread">Exclude unread notifications</label><br /><input type="checkbox" name="excludeadmin" id="excludeadmin" checked="checked" value="1" /><label for="excludeadmin">Exclude admin notifications</label><br /><input type="submit" value="Go" /></p>
		</form>
	</div>
</div>
<?php
	break;
	default:
		httperror(404);
}