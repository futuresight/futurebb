<?php
$page_title = translate('reportpost');
if (!isset($dirs[2])) {
	httperror(404);
}

if($dirs[2] == 'message') {
	// Reporting a notification instead of a post
	$page_title = translate('report_abuse');
	if (!isset($dirs[3])) {
		httperror(404);
	}
	$pid = intval($dirs[3]);
	$result = $db->query('SELECT * FROM `#^notifications` WHERE id = ' . $pid)
			or error('Failed to find notification', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result)) {
		httperror(404);
	}
	$cur_msg = $db->fetch_assoc($result);
	if($cur_msg['user'] != $futurebb_user['id']) {
		httperror(403);
	}
	if (isset($_POST['form_sent'])) {
		$db->query('INSERT INTO `#^reports`(post_id,post_type,reason,reported_by,time_reported) VALUES(' . $pid . ',\'msg\',\'' . $db->escape($_POST['reason']) . '\',' . $futurebb_user['id'] . ',' . time() . ')') or error('Failed to insert report', __FILE__, __LINE__, $db->error());
		redirect('/');
	}
	echo '<h3>' . translate('report_abuse') . '</h3>';
	?>
	<form action="<?php echo $base_config['baseurl']; ?>/report/message/<?php echo $pid; ?>" method="post" enctype="multipart/form-data">
		<p><?php echo translate('reportpostreason'); ?><br /><textarea name="reason" rows="5" cols="50"></textarea><br /><input type="submit" name="form_sent" value="Report" /></p>
	</form>
<?php
} else {
	// Reporting a post
	$pid = intval($dirs[2]);
	$result = $db->query('SELECT t.subject,t.url AS turl,f.name AS fname,f.url AS furl FROM `#^posts` AS p LEFT JOIN `#^topics` AS t ON t.id=p.topic_id LEFT JOIN `#^forums` AS f ON f.id=t.forum_id WHERE p.id=' . $pid . ' AND f.view_groups LIKE \'%-' . $futurebb_user['group_id'] . '-%\'') or error('Failed to find post', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result)) {
		httperror(404);
	}
	if (isset($_POST['form_sent'])) {
		$db->query('INSERT INTO `#^reports`(post_id,post_type,reason,reported_by,time_reported) VALUES(' . $pid . ',\'post\',\'' . $db->escape($_POST['reason']) . '\',' . $futurebb_user['id'] . ',' . time() . ')') or error('Failed to insert report', __FILE__, __LINE__, $db->error());
		redirect('/');
	}
	$cur_post = $db->fetch_assoc($result);
	$breadcrumbs = array(translate('index') => '', $cur_post['fname'] => $cur_post['furl'], $cur_post['subject'] => $cur_post['furl'] . '/' . $cur_post['turl'], translate('reportpost') => '!nourl!');
	?>
	<form action="<?php echo $base_config['baseurl']; ?>/report/<?php echo $pid; ?>" method="post" enctype="multipart/form-data">
		<p><?php echo translate('reportpostreason'); ?><br /><textarea name="reason" rows="5" cols="50"></textarea><br /><input type="submit" name="form_sent" value="Report" /></p>
	</form>
<?php } ?>