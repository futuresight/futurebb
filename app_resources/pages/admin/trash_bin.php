<?php
if (!$futurebb_user['g_mod_privs'] && !$futurebb_user['g_admin_privs']) {
	httperror(403);
}
translate('<addfile>', 'admin');
$page_title = translate('trashbin');
include FORUM_ROOT . '/app_resources/includes/admin.php';
?>
<div class="container">
	<?php make_admin_menu(); ?>
	<div class="forum_content rightbox admin">
		<?php if (isset($dirs[3]) && $dirs[3] == 'posts') {
			if (!isset($dirs[4])) {
				httperror(404);
			}
			$pid = intval($dirs[4]);
			$result = $db->query('SELECT u.username AS poster,p.id,p.parsed_content,p.deleted,p.posted,du.username AS deleted_by,tdu.username AS topic_deleted_by,t.url AS turl,t.subject,t.deleted AS topic_deleted,f.url AS furl,f.name AS forum_name,t.subject FROM `#^posts` AS p LEFT JOIN `#^topics` AS t ON t.id=p.topic_id LEFT JOIN `#^forums` AS f ON f.id=t.forum_id LEFT JOIN `#^users` AS du ON du.id=p.deleted_by LEFT JOIN `#^users` AS tdu ON tdu.id=t.deleted_by LEFT JOIN `#^users` AS u ON u.id=p.poster WHERE (p.deleted IS NOT NULL OR t.deleted IS NOT NULL) AND p.id=' . $pid . ' LIMIT 10') or error('Failed to find recent deleted posts', __FILE__, __LINE__, $db->error());
			$cur_post = $db->fetch_assoc($result);
			echo '<p><a href="' . $base_config['baseurl'] . '/admin/trash_bin">' . translate('trashbin') . '</a> &raquo; <a href="' . $base_config['baseurl'] . '/' . $cur_post['furl'] . '">' . htmlspecialchars($cur_post['forum_name']) . '</a> &raquo; <a href="' . $base_config['baseurl'] . '/' . $cur_post['furl'] . '/' . $cur_post['turl'] . '">' . htmlspecialchars($cur_post['subject']) . '</a><br />' . translate('posted') . ' ' . user_date($cur_post['posted']) . ' ' . translate('by') . ' <b>' . htmlspecialchars($cur_post['poster']) . '</b><br />';
			if ($cur_post['deleted_by']) {
				echo translate('deletedbyon', translate('post'), user_date($cur_post['deleted']), htmlspecialchars($cur_post['deleted_by']));
			} else if ($cur_post['topic_deleted_by']) {
				translate('deletedbyon', translate('topic'), user_date($cur_post['topic_deleted']), htmlspecialchars($cur_post['topic_deleted_by']));
			}
			echo '<div class="quotebox" id="post' . $cur_post['id'] . '"><p>' . $cur_post['parsed_content'] . '</p></div>';
		} else if (!isset($dirs[3]) || $dirs[3] == '') { ?>
			<h2><?php echo translate('trashbin'); ?></h2>
			<p><?php echo translate('trashbindesc'); ?></p>
			<h3><?php echo translate('recentdeleted', translate('topics')); ?></h3>
			<ul>
			<?php
			$result = $db->query('SELECT url,subject FROM `#^topics` WHERE deleted IS NOT NULL ORDER BY deleted DESC LIMIT 20') or error('Failed to find recent deleted topics', __FILE__, __LINE__, $db->error());
			while ($cur_topic = $db->fetch_assoc($result)) {
				echo '<li><a href="' . $base_config['baseurl'] . '/admin/trash_bin/' . htmlspecialchars($cur_topic['url']) . '">' . htmlspecialchars($cur_topic['subject']) . '</a></li>';
			}
			?>
			</ul>
			<h3><?php echo translate('recentdeleted', translate('posts')); ?></h3>
			<table border="0">
				<tr>
					<th><?php echo translate('post'); ?></th>
					<th><?php echo translate('topic'); ?></th>
					<th><?php echo translate('author'); ?></th>
					<th><?php echo translate('deletiontime'); ?></th>
					<th><?php echo translate('deletedby'); ?></th>
				</tr>
			<?php
			$result = $db->query('SELECT p.parsed_content,p.deleted,du.username AS deleted_by,t.url AS turl,f.url AS furl,t.subject,u.username AS poster FROM `#^posts` AS p LEFT JOIN `#^topics` AS t ON t.id=p.topic_id LEFT JOIN `#^forums` AS f ON f.id=t.forum_id LEFT JOIN `#^users` AS du ON du.id=p.deleted_by LEFT JOIN `#^users` AS u ON u.id=p.poster WHERE p.deleted IS NOT NULL ORDER BY p.deleted DESC LIMIT 10') or error('Failed to find recent deleted posts', __FILE__, __LINE__, $db->error());
			while ($cur_post = $db->fetch_assoc($result)) {
				echo '<tr>
					<td class="quotebox">' . $cur_post['parsed_content'] . '</td>
					<td><a href="' . $base_config['baseurl'] . '/' . htmlspecialchars($cur_post['furl']) . '/' . htmlspecialchars($cur_post['turl']) . '">' . htmlspecialchars($cur_post['subject']) . '</a></td>
					<td><a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($cur_post['poster']) . '">' . htmlspecialchars($cur_post['poster']) . '</a></td>
					<td>' . user_date($cur_post['deleted']) . '</td>
					<td><a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($cur_post['deleted_by']) . '">' . htmlspecialchars($cur_post['deleted_by']) . '</a>
				</tr>';
			}
			?>
			</table>
			<?php
		} else if (isset($dirs[3]) && $dirs[3] != '') {
			$result = $db->query('SELECT t.id,t.url,t.subject FROM `#^topics` AS t WHERE t.url=\'' . $db->escape($dirs[3]) . '\'') or error('Failed to get topic info', __FILE__, __LINE__, $db->error());
			if (!$db->num_rows($result)) {
				httperror(404);
			}
			$cur_topic = $db->fetch_assoc($result);
			$breadcrumbs = array(translate('trashbin') => 'admin/trash_bin', $cur_topic['subject'] => '!nourl!');
			$result = $db->query('SELECT p.id,p.parsed_content,p.posted,u.username AS poster FROM `#^posts` AS p LEFT JOIN `#^users` AS u ON u.id=p.poster WHERE p.topic_id=' . $cur_topic['id']) or error('Failed to get posts', __FILE__, __LINE__, $db->error());
			while ($cur_post = $db->fetch_assoc($result)) {
				echo '<h3><a href="#post' . $cur_post['id'] . '">' . user_date($cur_post['posted']) . '</a></h3>
				<p>' . translate('by') . ' <a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($cur_post['poster']) . '">' . htmlspecialchars($cur_post['poster']) . '</a></p>';
				echo '<div class="fullwidth quotebox" id="post' . $cur_post['id'] . '"><p>' . $cur_post['parsed_content'] . '</p></div>';
			}
		}
		?>
	</div>
</div>