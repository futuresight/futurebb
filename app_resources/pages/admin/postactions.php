<?php
$page_title = 'Bulk post actions';
translate('<addfile>', 'admin');
//TODO: implement the list of actions that can be done when a bunch topics/posts are checked
if (isset($_POST['form_sent'])) {
	//actually initiate the final action
	if (!isset($_POST['type']) || ($_POST['type'] != 'topics' && $_POST['type'] != 'posts')
		|| ($_POST['type'] == 'posts' && (in_array($_POST['action'], array('open', 'close', 'stick', 'unstick')) || !in_array($_POST['action'], array('delete', 'undelete'))))
		|| ($_POST['type'] == 'topics' && (!in_array($_POST['action'], array('open', 'close', 'stick', 'unstick', 'delete', 'undelete'))))
	) {
			httperror(404);
	}
	foreach ($_POST['items'] as &$item) {
		$item = intval($item);
	}
	if ($_POST['type'] == 'topics') {
		//working with topics
		$result = $db->query('SELECT f.url FROM `#^topics` AS t LEFT JOIN `#^forums` AS f ON f.id=t.forum_id WHERE t.id=' . intval(array_keys($_POST['items'])[0])) or enhanced_error('Failed to get forum info', true);
		if (!$db->num_rows($result)) {
			httperror(404);
		}
		switch ($_POST['action']) {
			case 'delete':
				break;
			case 'undelete':
				break;
			case 'close':
				break;
			case 'open':
				break;
			case 'stick':
				break;
			case 'unstick':
				break;
			default:
				httperror(404);
		}
	} else {
		//working with posts
		$result = $db->query('SELECT t.url AS turl,t.id AS id,f.url AS furl,f.id AS fid FROM `#^posts` AS p LEFT JOIN `#^topics` AS t ON t.id=p.topic_id LEFT JOIN `#^forums` AS f ON f.id=t.forum_id WHERE p.id=' . intval(array_keys($_POST['items'])[0])) or enhanced_error('Failed to get first post info', true);
		if (!$db->num_rows($result)) {
			httperror(404);
		}
		$topic_info = $db->fetch_assoc($result);
		switch ($_POST['action']) {
			case 'delete':
				$db->query('UPDATE `#^posts` SET deleted=' . time() . ',deleted_by=' . $futurebb_user['id'] . ' WHERE id IN(' . implode(',', array_keys($_POST['items'])) . ')') or enhanced_error('Failed to delete posts', true);
				//update post counts
				$db->query('UPDATE `#^topics` SET num_replies=num_replies-' . sizeof($_POST['items']) . ' WHERE id=' . $topic_info['id']) or error('Failed to delete post', __FILE__, __LINE__, $db->error());
				$db->query('UPDATE `#^forums` SET num_posts=num_posts-' . sizeof($_POST['items']) . ' WHERE id=' . $topic_info['fid']) or error('Failed to update topic count', __FILE__, __LINE__, $db->error());
				break;
			case 'undelete':
				$db->query('UPDATE `#^posts` SET deleted=NULL,deleted_by=NULL WHERE id IN(' . implode(',', array_keys($_POST['items'])) . ')') or enhanced_error('Failed to delete posts', true);
				//update post counts
				$db->query('UPDATE `#^topics` SET num_replies=num_replies+' . sizeof($_POST['items']) . ' WHERE id=' . $topic_info['id']) or error('Failed to delete post', __FILE__, __LINE__, $db->error());
				$db->query('UPDATE `#^forums` SET num_posts=num_posts+' . sizeof($_POST['items']) . ' WHERE id=' . $topic_info['fid']) or error('Failed to update topic count', __FILE__, __LINE__, $db->error());
				break;
			default:
				httperror(404);
		}
		//update topic last post data
		$result = $db->query('SELECT id,posted FROM `#^posts` WHERE topic_id=' . $topic_info['id'] . ' AND deleted IS NULL ORDER BY posted DESC') or error('Failed to get new last post', __FILE__, __LINE__, $db->error());
		update_last_post($topic_info['id'], $topic_info['fid']);
		redirect($base_config['baseurl'] . '/' . rawurlencode($topic_info['furl']) . '/' . rawurlencode($topic_info['turl']));
	}
} else {
	//show a confirmation
	//check the validity of the data
	if (!isset($_POST['type']) || ($_POST['type'] != 'topics' && $_POST['type'] != 'posts')
		|| ($_POST['type'] == 'posts' && (isset($_POST['form_sent_close']) || isset($_POST['form_sent_open']) || isset($_POST['form_sent_stick']) || isset($_POST['form_sent_unstick']) || (!isset($_POST['form_sent_delete']) && !isset($_POST['form_sent_undelete']))))
		|| ($_POST['type'] == 'topics' && (!isset($_POST['topic_action']) || (!isset($_POST['form_sent_close']) && !isset($_POST['form_sent_open']) && !isset($_POST['form_sent_stick']) && !isset($_POST['form_sent_unstick']) && !isset($_POST['form_sent_delete']) && !isset($_POST['form_sent_undelete']))))
	) {
			httperror(404);
	}
	if (isset($_POST['form_sent_close']))
		$action = 'close';
	if (isset($_POST['form_sent_open']))
		$action = 'open';
	if (isset($_POST['form_sent_delete']))
		$action = 'delete';
	if (isset($_POST['form_sent_undelete']))
		$action = 'undelete';
	if (isset($_POST['form_sent_stick']))
		$action = 'stick';
	if (isset($_POST['form_sent_unstick']))
		$action = 'unstick';
	?>
	<form action="<?php echo $base_config['baseurl']; ?>/admin/postactions" method="post" enctype="multipart/form-data">
		<h3>Confirm</h3>
		<p><?php
		$items = $_POST['type'] == 'topics' ? $_POST['topic_action'] : $_POST['post_action'];
		foreach ($items as $item) {
			echo '<input type="hidden" name="items[' . intval($item) . ']" value="' . intval($item) . '" />';
		}
		?>
		<input type="hidden" name="type" value="<?php echo $_POST['type']; ?>" />
		<input type="hidden" name="action" value="<?php echo $action; ?>" />Are you sure you want to <?php echo strtolower(translate($action)); ?> the following <?php echo $_POST['type'] == 'topics' ? translate('topicsp', sizeof($_POST['topic_action'])) : translate('postsp', sizeof($_POST['post_action'])); ?>?</p>
		<p><input type="submit" name="form_sent" value="<?php echo translate('yes'); ?>" /> <a href="<?php echo $_SERVER['HTTP_REFERER']; ?>"><?php echo translate('no'); ?></a></p>
	</form>
	<?php
}