<?php
$page_title = translate('deletepost');
$pid = intval($dirs[2]);
$result = $db->query('SELECT t.first_post_id,t.url AS turl,t.closed,f.url AS furl,f.id AS fid,f.name AS forum_name,t.subject,t.id AS tid,p.parsed_content,p.poster FROM `#^posts` AS p LEFT JOIN `#^topics` AS t ON t.id=p.topic_id LEFT JOIN `#^forums` AS f ON f.id=t.forum_id WHERE p.id=' . $pid . ' AND p.deleted IS NULL') or error('Failed to get post info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result)) {
	httperror(404);
}
$cur_post = $db->fetch_assoc($result);
if ($cur_post['closed'] && !$futurebb_user['g_mod_privs'] || strstr($futurebb_user['restricted_privs'], 'delete')) {
	httperror(403);
}
if (!$futurebb_user['g_admin_privs'] && !$futurebb_user['g_mod_privs'] && ($cur_post['poster'] != $futurebb_user['id'] || !$futurebb_user['g_edit_posts'])) {
	httperror(404);
}
if (isset($_POST['form_sent'])) {
	if ($pid == $cur_post['first_post_id']) {
		//delete topic
		$db->query('UPDATE `#^topics` SET deleted=' . time() . ',deleted_by=' . $futurebb_user['id'] . ' WHERE id=' . $cur_post['tid']) or error('Failed to delete post', __FILE__, __LINE__, $db->error());	
		
		$result = $db->query('SELECT 1 FROM `#^posts` WHERE topic_id=' . $cur_post['tid'] . ' AND deleted IS NULL') or error('Failed to get number of replies', __FILE__, __LINE__, $db->error());
		$num_replies = $db->num_rows($result);
		//update forum last post data
		$result = $db->query('SELECT p.id,p.posted FROM `#^posts` AS p LEFT JOIN `#^topics` AS t ON t.id=p.topic_id WHERE p.deleted IS NULL AND t.deleted IS NULL AND t.forum_id=' . $cur_post['fid'] . ' ORDER BY p.posted DESC') or enhanced_error('Failed to find last post', true);
		list ($last_post_id,$last_post_time) = $db->fetch_row($result);
		$db->query('UPDATE `#^forums` SET num_posts=num_posts-' . $num_replies . ',num_topics=num_topics-1,last_post=' . $last_post_time . ',last_post_id=' . $last_post_id . ' WHERE id=' . $cur_post['fid']) or error('Failed to update post count<br />' . $q, __FILE__, __LINE__, $db->error());
		
		redirect($base_config['baseurl']);
	} else {
		//delete post
		$db->query('UPDATE `#^posts` SET deleted=' . time() . ',deleted_by=' . $futurebb_user['id'] . ' WHERE id=' . $pid) or error('Failed to delete post', __FILE__, __LINE__, $db->error());
		//update topic last post data
		$result = $db->query('SELECT id,posted FROM `#^posts` WHERE topic_id=' . $cur_post['tid'] . ' AND deleted IS NULL ORDER BY posted DESC') or error('Failed to get new last post', __FILE__, __LINE__, $db->error());
		list ($last_post_id,$last_post_time) = $db->fetch_row($result);
		$db->query('UPDATE `#^topics` SET num_replies=num_replies-1,last_post=' . $last_post_time . ',last_post_id=' . $last_post_id . ' WHERE id=' . $cur_post['tid']) or error('Failed to delete post', __FILE__, __LINE__, $db->error());
		$db->query('UPDATE `#^forums` SET num_posts=num_posts-1,last_post=' . $last_post_time . ',last_post_id=' . $last_post_id . ' WHERE id=' . $cur_post['fid']) or error('Failed to update topic count', __FILE__, __LINE__, $db->error());
		redirect($base_config['baseurl'] . '/' . $cur_post['furl'] . '/' . $cur_post['turl']); return;
	}
}
?>
<h2><?php echo translate('deletepost'); ?></h2>
<?php
if ($pid == $cur_post['first_post_id']) {
	$breadcrumbs = array($cur_post['forum_name'] => $cur_post['furl'], $cur_post['subject'] => $cur_post['furl'] . '/' . $cur_post['turl'], translate('delete') => '!nourl!');
?>
<p><?php echo translate('deletetopicwarning'); ?></p>   
<?php
} else { 
?>
<p><?php echo translate('deletepostwarning'); ?></p>
<p class="quotebox"><?php echo $cur_post['parsed_content']; ?></p>
<?php
}
?>
<form action="<?php echo $base_config['baseurl']; ?>/delete/<?php echo $pid; ?>" method="post" enctype="multipart/form-data">
<p><input type="submit" name="form_sent" value="<?php echo translate('delete'); ?>" /></p>
</form>