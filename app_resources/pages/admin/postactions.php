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
		switch ($_POST['action']) {
			case 'delete':
				$db->query('UPDATE `#^posts` SET deleted=' . time() . ',deleted_by=' . $futurebb_user['id'] . ' WHERE id IN(' . implode(',', array_keys($_POST['items'])) . ')') or enhanced_error('Failed to delete posts', true);
				//TODO: update topic information, including last post ID/date, as well as reply counts and such
				break;
			case 'undelete':
				break;
			default:
				httperror(404);
		}
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
		$action = 'undelete';
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