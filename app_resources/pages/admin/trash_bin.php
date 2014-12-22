<?php
if (!$futurebb_user['g_admin_privs'] && !$futurebb_user['g_mod_privs']) {
	httperror(403);
}
translate('<addfile>', 'admin');
$page_title = translate('trashbin');
include FORUM_ROOT . '/app_resources/includes/admin.php';
?>
<div class="container">
	<?php make_admin_menu(); ?>
	<div class="forum_content rightbox admin">
    	<?php
		if (isset($dirs[3]) && $dirs[3] == 'undelete') {
			if (isset($_POST['form_sent'])) {
				if (isset($_POST['post_id'])) {
					//undeleting a single post
					$result = $db->query('SELECT p.topic_id,t.forum_id FROM `#^posts` AS p LEFT JOIN `#^topics` AS t ON t.id=p.topic_id WHERE p.deleted IS NOT NULL AND p.id=' . intval($_POST['post_id'])) or enhanced_error('Failed to get topic', true);
					if (!$db->num_rows($result)) {
						httperror(404);
					}
					list($tid,$fid) = $db->fetch_row($result);
					
					//undelete, adjust counts
					$db->query('UPDATE `#^posts` SET deleted=NULL,deleted_by=NULL WHERE id=' . intval($_POST['post_id']) . ' AND deleted IS NOT NULL') or enhanced_error('Failed to undelete post', true);
					$db->query('UPDATE `#^topics` SET num_replies=num_replies+1 WHERE id=' . $tid) or error('Failed to update post count in topic', __FILE__, __LINE__, $db->error());
					$db->query('UPDATE `#^forums` SET num_posts=num_posts+1 WHERE id=' . $fid) or error('Failed to update post count in forum', __FILE__, __LINE__, $db->error());
					
					update_last_post($tid, $fid);
					
					redirect($base_config['baseurl'] . '/posts/' . intval($_POST['post_id']));
				} else if (isset($_POST['topic_id'])) {
					//undeleting a whole topic
					$result = $db->query('SELECT f.url AS furl,t.url AS turl,t.forum_id AS fid FROM `#^topics` AS t LEFT JOIN `#^forums` AS f ON f.id=t.forum_id WHERE t.deleted IS NOT NULL AND t.id=' . intval($_POST['topic_id'])) or enhanced_error('Failed to get topic', true);
					if (!$db->num_rows($result)) {
						httperror(404);
					}
					list($furl,$turl,$fid) = $db->fetch_row($result);
					//undelete, then update counts
					$db->query('UPDATE `#^topics` SET deleted=NULL,deleted_by=NULL WHERE id=' . intval($_POST['topic_id'])) or enhanced_error('Failed to undelete topic', true);
					$result = $db->query('SELECT 1 FROM `#^posts` WHERE topic_id=' . intval($_POST['topic_id']) . ' AND deleted IS NULL') or error('Failed to get number of replies', __FILE__, __LINE__, $db->error());
					$num_replies = $db->num_rows($result);
					$db->query('UPDATE `#^forums` SET num_posts=num_posts+' . $num_replies . ',num_topics=num_topics+1 WHERE id=' . $fid) or error('Failed to update post count<br />' . $q, __FILE__, __LINE__, $db->error());
					
					//
					update_last_post(-1, $fid);
					redirect($base_config['baseurl'] . '/' . $furl . '/' . $turl);
				} else {
					httperror(404);
				}
			} else if (isset($_POST['cancel'])) {
				redirect($base_config['baseurl'] . '/admin/trash_bin');
			}
			$id = intval($dirs[5]);
			?>
            <form action="<?php echo $base_config['baseurl']; ?>/admin/trash_bin/undelete/<?php echo htmlspecialchars($dirs[4]); ?>/<?php echo htmlspecialchars($id); ?>" method="post" enctype="multipart/form-data">
            	<h2><?php echo translate('undelete'); ?></h2>
            <?php
			if ($dirs[4] == 'topic') {
				$result = $db->query('SELECT t.subject,t.url AS turl,f.name AS forum_name,f.url AS furl FROM `#^topics` AS t LEFT JOIN `#^forums` AS f ON f.id=t.forum_id WHERE t.deleted IS NOT NULL AND t.id=' . $id) or enhanced_error('Failed to get topic', true);
				if (!$db->num_rows($result)) {
					httperror(404);
				}
				$cur_topic = $db->fetch_assoc($result);
				?>
                <p><?php echo translate('undeletetopicheader'); ?><input type="hidden" name="topic_id" value="<?php echo $id; ?>" /></p>
                <p><a href="<?php echo $base_config['baseurl'] . '/' . htmlspecialchars($cur_topic['furl']); ?>"><?php echo htmlspecialchars($cur_topic['forum_name']); ?></a> &raquo; <a href="<?php echo $base_config['baseurl'] . '/' . htmlspecialchars($cur_topic['furl']). '/' . htmlspecialchars($cur_topic['turl']); ?>"><?php echo htmlspecialchars($cur_topic['subject']); ?></a></p>
                <?php
			} else if ($dirs[4] == 'post') {
				$result = $db->query('SELECT p.id,p.parsed_content,t.subject,t.url AS turl,f.name AS forum_name,f.url AS furl FROM `#^posts` AS p LEFT JOIN `#^topics` AS t ON t.id=p.topic_id LEFT JOIN `#^forums` AS f ON f.id=t.forum_id WHERE p.deleted IS NOT NULL AND p.id=' . $id) or enhanced_error('Failed to get post', true);
				if (!$db->num_rows($result)) {
					httperror(404);
				}
				$cur_post = $db->fetch_assoc($result);
			?>
           		<p><?php echo translate('undeletepostheader'); ?><input type="hidden" name="post_id" value="<?php echo $id; ?>" /></p>
                <p><a href="<?php echo $base_config['baseurl'] . '/' . htmlspecialchars($cur_post['furl']); ?>"><?php echo htmlspecialchars($cur_post['forum_name']); ?></a> &raquo; <a href="<?php echo $base_config['baseurl'] . '/' . htmlspecialchars($cur_post['furl']). '/' . htmlspecialchars($cur_post['turl']); ?>"><?php echo htmlspecialchars($cur_post['subject']); ?></a> &raquo; <a href="<?php echo $base_config['baseurl'] . '/posts/' . htmlspecialchars($cur_post['id']); ?>"><?php echo translate('post') . ' #' . $cur_post['id']; ?></a></p>
            	<p class="quotebox"><?php echo $cur_post['parsed_content']; ?></p>
            <?php
			} else {
				httperror(404);
			}
			?>
            	<p><input type="submit" name="form_sent" value="<?php echo translate('yes'); ?>" /> <input type="submit" name="cancel" value="<?php echo translate('no'); ?>" /></p>
            </form>
            <?php
		} else if (!isset($dirs[3]) || $dirs[3] == '') {
			?>
			<h2><?php echo translate('trashbin'); ?></h2>
			<p><?php echo translate('trashbindesc'); ?></p>
			<h3><?php echo translate('recentdeleted', strtolower(translate('topics'))); ?></h3>
			<ul>
			<?php
			$result = $db->query('SELECT t.url,t.subject,f.url AS furl FROM `#^topics` AS t LEFT JOIN `#^forums` AS f ON f.id=t.forum_id WHERE t.deleted IS NOT NULL ORDER BY t.deleted DESC LIMIT 20') or error('Failed to find recent deleted topics', __FILE__, __LINE__, $db->error());
			while ($cur_topic = $db->fetch_assoc($result)) {
				echo '<li><a href="' . $base_config['baseurl'] . '/' . htmlspecialchars($cur_topic['furl']) . '/' . htmlspecialchars($cur_topic['url']) . '">' . htmlspecialchars($cur_topic['subject']) . '</a></li>';
			}
			?>
			</ul>
			<h3><?php echo translate('recentdeleted', strtolower(translate('posts'))); ?></h3>
			<table border="0">
				<tr>
					<th><?php echo translate('post'); ?></th>
					<th><?php echo translate('topic'); ?></th>
					<th><?php echo translate('author'); ?></th>
					<th><?php echo translate('deletiontime'); ?></th>
					<th><?php echo translate('deletedby'); ?></th>
				</tr>
			<?php
			$result = $db->query('SELECT p.id,p.parsed_content,p.deleted,du.username AS deleted_by,t.url AS turl,f.url AS furl,t.subject,u.username AS poster FROM `#^posts` AS p LEFT JOIN `#^topics` AS t ON t.id=p.topic_id LEFT JOIN `#^forums` AS f ON f.id=t.forum_id LEFT JOIN `#^users` AS du ON du.id=p.deleted_by LEFT JOIN `#^users` AS u ON u.id=p.poster WHERE p.deleted IS NOT NULL ORDER BY p.deleted DESC LIMIT 10') or error('Failed to find recent deleted posts', __FILE__, __LINE__, $db->error());
			while ($cur_post = $db->fetch_assoc($result)) {
				echo '<tr>
					<td><a href="' . $base_config['baseurl'] . '/posts/' . $cur_post['id'] . '">#' . $cur_post['id'] . '</a></td>
					<td><a href="' . $base_config['baseurl'] . '/' . htmlspecialchars($cur_post['furl']) . '/' . htmlspecialchars($cur_post['turl']) . '">' . htmlspecialchars($cur_post['subject']) . '</a></td>
					<td><a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($cur_post['poster']) . '">' . htmlspecialchars($cur_post['poster']) . '</a></td>
					<td>' . user_date($cur_post['deleted']) . '</td>
					<td><a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($cur_post['deleted_by']) . '">' . htmlspecialchars($cur_post['deleted_by']) . '</a>
				</tr>';
			}
			?>
			</table>
            <?php
		}
		?>
	</div>
</div>