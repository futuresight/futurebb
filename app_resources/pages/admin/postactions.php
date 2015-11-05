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
	$items = array();
	foreach ($_POST['items'] as $key => &$item) {
		$items[] = intval($key);
	}
	if ($_POST['type'] == 'topics') {
		//working with topics
		$result = $db->query('SELECT f.url,f.id AS fid FROM `#^topics` AS t LEFT JOIN `#^forums` AS f ON f.id=t.forum_id WHERE t.id=' . $items[0]) or enhanced_error('Failed to get forum info', true);
		if (!$db->num_rows($result)) {
			httperror(404);
		}
		$forum_info = $db->fetch_assoc($result);
		
		//check for invalid topics (i.e. wrong forum, wrong state0
		$statecheck = array(
			'delete' => 'deleted IS NOT NULL',
			'undelete' => 'deleted IS NULL',
			'close' => 'closed=1',
			'open' => 'closed=0',
			'stick' => 'sticky=1',
			'unstick' => 'sticky=0'
		); //the state that nothing being modified should have
		if (!isset($statecheck[$_POST['action']])) {
			httperror(404);
		}
		$result = $db->query('SELECT 1 FROM `#^topics` WHERE (forum_id<>' . $forum_info['fid'] . ' OR ' . $statecheck[$_POST['action']] . ') AND id IN(' . implode(',', $items) . ')') or enhanced_error('Failed to check for invalid topics', true);
		if ($db->num_rows($result)) {
			httperror(404);
		}
		//now for the action
		switch ($_POST['action']) {
			case 'delete':
				$db->query('UPDATE `#^topics` SET deleted=' . time() . ',deleted_by=' . $futurebb_user['id'] . ' WHERE id IN(' . implode(',', $items) . ')') or error('Failed to delete post', __FILE__, __LINE__, $db->error());	
				//update post counts
				$result = $db->query('SELECT 1 FROM `#^posts` WHERE topic_id IN(' . implode(',', $items) . ') AND deleted IS NULL') or error('Failed to get number of replies', __FILE__, __LINE__, $db->error());
				$num_replies = $db->num_rows($result);
				$db->query('UPDATE `#^forums` SET num_posts=num_posts-' . $num_replies . ',num_topics=num_topics-' . sizeof($items) . ' WHERE id=' . $forum_info['fid']) or error('Failed to update post count<br />' . $q, __FILE__, __LINE__, $db->error());
				update_last_post(-1, $forum_info['fid']);
				break;
			case 'undelete':
				$db->query('UPDATE `#^topics` SET deleted=NULL,deleted_by=NULL WHERE id IN(' . implode(',', $items) . ')') or error('Failed to delete post', __FILE__, __LINE__, $db->error());	
				//update post counts
				$result = $db->query('SELECT 1 FROM `#^posts` WHERE topic_id IN(' . implode(',', $items) . ') AND deleted IS NULL') or error('Failed to get number of replies', __FILE__, __LINE__, $db->error());
				$num_replies = $db->num_rows($result);
				$db->query('UPDATE `#^forums` SET num_posts=num_posts+' . $num_replies . ',num_topics=num_topics+' . sizeof($items) . ' WHERE id=' . $forum_info['fid']) or error('Failed to update post count<br />' . $q, __FILE__, __LINE__, $db->error());
				update_last_post(-1, $forum_info['fid']);
				break;
			case 'close':
				$db->query('UPDATE `#^topics` SET closed=1 WHERE id IN(' . implode(',', $items) . ')') or error('Failed to close topics', __FILE__, __LINE__, $db->error());
				break;
			case 'open':
				$db->query('UPDATE `#^topics` SET closed=0 WHERE id IN(' . implode(',', $items) . ')') or error('Failed to open topics', __FILE__, __LINE__, $db->error());
				break;
			case 'stick':
				$db->query('UPDATE `#^topics` SET sticky=1 WHERE id IN(' . implode(',', $items) . ')') or error('Failed to stick topics', __FILE__, __LINE__, $db->error());
				break;
			case 'unstick':
				$db->query('UPDATE `#^topics` SET sticky=0 WHERE id IN(' . implode(',', $items) . ')') or error('Failed to unstick topics', __FILE__, __LINE__, $db->error());
				break;
			default:
				httperror(404);
		}
		redirect($base_config['baseurl'] . '/' . $forum_info['url']);
	} else {
		//working with posts
		//TODO: make sure that the posts selected to be deleted haven't already been deleted, and the opposite for undeletion
		$result = $db->query('SELECT t.url AS turl,t.id AS id,f.url AS furl,f.id AS fid FROM `#^posts` AS p LEFT JOIN `#^topics` AS t ON t.id=p.topic_id LEFT JOIN `#^forums` AS f ON f.id=t.forum_id WHERE p.id=' . $items[0]) or enhanced_error('Failed to get first post info', true);
		if (!$db->num_rows($result)) {
			httperror(404);
		}
		$topic_info = $db->fetch_assoc($result);
		//if any posts aren't on the current topic or have already been deleted/undeleted, then back out, something has gone wrong
		$result = $db->query('SELECT 1 FROM `#^posts` WHERE (topic_id<>' . $topic_info['id'] . ' OR deleted IS ' . ($_POST['action'] == 'delete' ? 'NOT ' : '') . ' NULL) AND id IN(' . implode(',', $items) . ')') or enhanced_error('Failed to find invalid posts', true);
		if ($db->num_rows($result)) {
			httperror(404);
		}
		switch ($_POST['action']) {
			case 'delete':
				$db->query('UPDATE `#^posts` SET deleted=' . time() . ',deleted_by=' . $futurebb_user['id'] . ' WHERE id IN(' . implode(',', $items) . ')') or enhanced_error('Failed to delete posts', true);
				//update post counts
				$db->query('UPDATE `#^topics` SET num_replies=num_replies-' . sizeof($items) . ' WHERE id=' . $topic_info['id']) or error('Failed to delete post', __FILE__, __LINE__, $db->error());
				$db->query('UPDATE `#^forums` SET num_posts=num_posts-' . sizeof($items) . ' WHERE id=' . $topic_info['fid']) or error('Failed to update topic count', __FILE__, __LINE__, $db->error());
				break;
			case 'undelete':
				$db->query('UPDATE `#^posts` SET deleted=NULL,deleted_by=NULL WHERE id IN(' . implode(',', array_keys($items)) . ')') or enhanced_error('Failed to delete posts', true);
				//update post counts
				$db->query('UPDATE `#^topics` SET num_replies=num_replies+' . sizeof($items) . ' WHERE id=' . $topic_info['id']) or error('Failed to delete post', __FILE__, __LINE__, $db->error());
				$db->query('UPDATE `#^forums` SET num_posts=num_posts+' . sizeof($items) . ' WHERE id=' . $topic_info['fid']) or error('Failed to update topic count', __FILE__, __LINE__, $db->error());
				break;
			default:
				httperror(404);
		}
		//update topic last post data
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
		<p><input type="hidden" name="type" value="<?php echo $_POST['type']; ?>" />
		<input type="hidden" name="action" value="<?php echo $action; ?>" />Are you sure you want to <?php echo strtolower(translate($action)); ?> the following <?php echo $_POST['type'] == 'topics' ? translate('topicsp', sizeof($_POST['topic_action'])) : translate('postsp', sizeof($_POST['post_action'])); ?>?</p>
		<?php
		$items = $_POST['type'] == 'topics' ? $_POST['topic_action'] : $_POST['post_action'];
		foreach ($items as $key => &$item) {
			$item = intval($key);
		}
		foreach ($items as $id) {
			echo '<input type="hidden" name="items[' . intval($id) . ']" value="' . intval($id) . '" />';
		}
		//show the topic list or post list
		if ($_POST['type'] == 'topics') {
			echo '<ul>';
			$result = $db->query('SELECT subject,forum_id FROM `#^topics` WHERE id IN(' . implode(',', $items) . ')') or enhanced_error('Failed to fetch topic list', true);
			$forum_id = 0;
			while ($cur_topic = $db->fetch_assoc($result)) {
				if ($forum_id == 0) { //make sure they're all the same forum
					$forum_id = $cur_topic['forum_id'];
				} else if ($forum_id != $cur_topic['forum_id']) {
					httperror(404);
				}
				echo '<li>' . htmlspecialchars($cur_topic['subject']) . '</li>';
			}
			echo '</ul>';
		} else {
			$result = $db->query('SELECT parsed_content,topic_id FROM `#^posts` WHERE id IN(' . implode(',', $items) . ')') or enhanced_error('Failed to fetch post list', true);
			$topic_id = 0;
			while ($cur_post = $db->fetch_assoc($result)) {
				if ($topic_id == 0) { //make sure they're all the same forum
					$topic_id = $cur_post['topic_id'];
				} else if ($topic_id != $cur_post['topic_id']) {
					httperror(404);
				}
				echo '<div class="quotebox"><p>' . $cur_post['parsed_content'] . '</p></div>';
			}
		}
		?>
		<p><input type="submit" name="form_sent" value="<?php echo translate('yes'); ?>" /> <a href="<?php echo $_SERVER['HTTP_REFERER']; ?>"><?php echo translate('no'); ?></a></p>
	</form>
	<?php
}