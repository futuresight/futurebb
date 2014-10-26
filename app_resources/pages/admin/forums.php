<?php
if (!$futurebb_user['g_admin_privs']) {
	httperror(403);
}
translate('<addfile>', 'admin');
$page_title = translate('forums');
include FORUM_ROOT . '/app_resources/includes/admin.php';
if (isset($dirs[3]) && $dirs[3] == 'enhanced') {
	include FORUM_ROOT . '/app_resources/pages/admin/forums_ajax.php';
	return;
}
if (isset($dirs[3]) && $dirs[3] == 'edit' && isset($_GET['popup'])) { //popup window for editing a forum
	include FORUM_ROOT . '/app_resources/pages/admin/includes/edit_forum.php';
	$page_info['template'] = false;
	return;
}
if (isset($_POST['add_new_category'])) {
	$result = $db->query('SELECT MAX(sort_position) FROM `#^categories`') or enhanced_error('Failed to get sort position');
	list($max) = $db->fetch_row($result);
	$db->query('INSERT INTO `#^categories`(name,sort_position) VALUES(\'New category\',' . ($max + 1) . ')') or error('Failed to create new category', __FILE__, __LINE__, $db->error());
}
if (isset($_POST['add_new_forum'])) {
	//get allowed user groups
	$view = array();
	$topics = array();
	$replies = array();
	$result = $db->query('SELECT g_id AS id,g_view_forums,g_post_topics,g_post_replies FROM `#^user_groups`') or enhanced_error('Failed to find user groups', true);
	while ($group = $db->fetch_assoc($result)) {
		if ($group['g_view_forums']) {
			$view[] = $group['id'];
		}
		if ($group['g_post_topics']) {
			$topics[] = $group['id'];
		}
		if ($group['g_post_replies']) {
			$replies[] = $group['id'];
		}
	}
	//make new forum
	$base_name = URLEngine::make_friendly($_POST['name']);
	$name = $base_name;
	$add_num = 0;
	
	//check for forums with the same URL
	$result = $db->query('SELECT url FROM `#^forums` WHERE url LIKE \'' . $db->escape($name) . '%\'') or error('Failed to check for similar URLs', __FILE__, __LINE__, $db->error());
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
			$name = $base_name . $add_num;
			$ok = false;
		}
	}
	$db->query('INSERT INTO `#^forums`(url,name,cat_id,sort_position,view_groups,topic_groups,reply_groups) VALUES(\'' . $db->escape($name) . '\',\'' . $db->escape($_POST['name']) . '\',' . intval($_POST['category']) . ',0,\'-' . implode('-', $view) . '-\',\'-' . implode('-', $topics) . '-\',\'-' . implode('-', $replies) . '-\')') or error('Failed to create new category', __FILE__, __LINE__, $db->error());
}
if (!isset($dirs[3])) {
	$dirs[3] = '';
}
if ($dirs[3] == 'edit') {
	//this file is included because it can also be a stand-alone pop-up
	include FORUM_ROOT . '/app_resources/pages/admin/includes/edit_forum.php';
	return;
}
if (isset($_POST['form_sent_forums'])) {
	foreach ($_POST['pos'] as $id => $pos) {
		$db->query('UPDATE `#^forums` SET sort_position=' . intval($pos) . ' WHERE id=' . intval($id)) or error('Failed to update forum', __FILE__, __LINE__, $db->error());
	}
	$result = $db->query('SELECT id,url,name FROM `#^forums` ORDER BY id ASC') or error('Failed to get forums', __FILE__, __LINE__, $db->error());
	while (list($id,$furl,$title) = $db->fetch_row($result)) {
		if ($_POST['title'][$id] != $title && isset($_POST['title'][$id]) && $_POST['title'][$id] != '') {
			//make redirect forum
			$base_name = URLEngine::make_friendly($_POST['title'][$id]);
			$name = $base_name;
			$add_num = 0;
			$result = $db->query('SELECT url FROM `#^forums` WHERE url LIKE \'' . $db->escape($name) . '%\'') or error('Failed to check for similar URLs', __FILE__, __LINE__, $db->error());
			$urllist = array();
			while (list($url) = $db->fetch_row($result)) {
				$urllist[] = $url;
			}
			$ok = false;
			while (!$ok) {
				$ok = true;
				if (in_array($name, $urllist)) {
					$add_num++;
					$name = $base_name . $add_num;
					$ok = false;
				}
			}
			$db->query('UPDATE `#^forums` SET url=\'' . $name . '\',name=\'' . $db->escape($_POST['title'][$id]) . '\' WHERE id=' . intval($id)) or error('Failed to update forum URL', __FILE__, __LINE__, $db->error());
			$db->query('INSERT INTO `#^forums`(url,redirect_id) VALUES(\'' . $db->escape($furl) . '\',' . $id . ')') or error('Failed to insert redirect forum', __FILE__, __LINE__, $db->error());
		}
	}
	if (isset($_POST['del'])) {
		//delete any forums
		$dels = array();
		foreach ($_POST['del'] as $key => $val) {
			$dels[] = intval($key);
		}
		$db->query('DELETE FROM `#^forums` WHERE id IN(' . implode(',', $dels) . ')') or error('Failed to delete forum', __FILE__, __LINE__, $db->error());
	}
}
if (isset($_POST['form_sent_categories'])) {
	foreach ($_POST['titles'] as $id => $title) {
		$pos = $_POST['pos'][$id];
		$db->query('UPDATE `#^categories` SET name=\'' . $db->escape($title) . '\',sort_position=' . intval($pos) . ' WHERE id=' . intval($id)) or error('Failed to update category', __FILE__, __LINE__, $db->error());
	}
	if (isset($_POST['del'])) {
		//delete any categories
		$dels = array();
		foreach ($_POST['del'] as $key => $val) {
			$dels[] = intval($key);
		}
		$db->query('DELETE FROM `#^categories` WHERE id IN(' . implode(',', $dels) . ')') or error('Failed to delete category', __FILE__, __LINE__, $db->error());
	}
}
?>
<div class="container">
	<?php make_admin_menu(); ?>
	<div class="forum_content rightbox admin">
		<h3><?php echo translate('editcats'); ?></h3>
		<form action="<?php echo $base_config['baseurl']; ?>/admin/forums" method="post" enctype="multipart/form-data">
			<p><input type="submit" name="add_new_category" value="<?php echo translate('addcat'); ?>" /></p>
			<table border="0">
				<tr>
					<th><?php echo translate('catname'); ?></th>
					<th><?php echo translate('sortpos'); ?></th>
					<th><?php echo translate('delete?'); ?></th>
				</tr>
				<?php
				$result = $db->query('SELECT id,name,sort_position FROM `#^categories` AS c ORDER BY sort_position ASC') or error('Failed to get categories', __FILE__, __LINE__, $db->error());
				while ($cur_category = $db->fetch_assoc($result)) {
					?>
				<tr>
					<td><input type="text" name="titles[<?php echo $cur_category['id']; ?>]" value="<?php echo htmlspecialchars($cur_category['name']); ?>" size="50" /></td>
					<td><input type="text" name="pos[<?php echo $cur_category['id']; ?>]" value="<?php echo htmlspecialchars($cur_category['sort_position']); ?>" size="2" /></td>
					<td><input type="checkbox" name="del[<?php echo $cur_category['id']; ?>]" value="1" /></td>
				</tr>
					<?php
				}
				?>
			</table>
			<p><input type="submit" name="form_sent_categories" value="<?php echo translate('updatecats'); ?>" /></p>
		</form>
		
		<h3><?php echo translate('editforums'); ?></h3>
		<form action="<?php echo $base_config['baseurl']; ?>/admin/forums" method="post" enctype="multipart/form-data">
			<h4><?php echo translate('newforum'); ?></h4>
			<hr />
			<table border="0">
				<tr>
					<td><?php echo translate('category'); ?></td>
					<td>
						<select name="category">
						<?php
						$result = $db->query('SELECT id,name,sort_position FROM `#^categories` AS c ORDER BY sort_position ASC') or error('Failed to get categories', __FILE__, __LINE__, $db->error());
						while ($cur_category = $db->fetch_assoc($result)) {
							echo '<option value="' . $cur_category['id'] . '">' . htmlspecialchars($cur_category['name']) . '</option>';
						}
						?>
						</select>
					</td>
				</tr>
				<tr>
					<td><?php echo translate('name'); ?></td>
					<td>
						<input type="text" name="name" value="<?php echo translate('newforumname'); ?>" size="50" />
					</td>
				</tr>
			</table>
			<p><input type="submit" name="add_new_forum" value="<?php echo translate('add'); ?>" /></p>
			<?php
			//get forums
			$result = $db->query('SELECT c.id AS cat_id,c.name AS cat_name,f.name AS forum_name,f.id,f.sort_position FROM `#^forums` AS f LEFT JOIN `#^categories` AS c ON c.id=f.cat_id WHERE c.id IS NOT NULL ORDER BY c.sort_position ASC,f.sort_position ASC') or error('Failed to get categories', __FILE__, __LINE__, $db->error());
			if ($db->num_rows($result)) {
				$last_cid = 0;
				while ($cur_forum = $db->fetch_assoc($result)) {
					if ($cur_forum['cat_id'] != $last_cid) {
						if ($last_cid != 0) {
							echo '</table>';
						}
						$last_cid = $cur_forum['cat_id'];
						echo '<h4>' . htmlspecialchars($cur_forum['cat_name']) . '</h4>
						<hr />
						<table border="0">
							<tr>
								<th>' . translate('forumname') . '</th>
								<th>' . translate('sortpos') . '</th>
								<th>' . translate('delete?') . '</th>
								<th>' . translate('edit') . '</th>
							</tr>';
					}
					echo '<tr>
						<td><input type="text" name="title[' . $cur_forum['id'] . ']" value="' . htmlspecialchars($cur_forum['forum_name']) . '" size="50" /></td>
						<td><input type="text" name="pos[' . $cur_forum['id'] . ']" value="' . $cur_forum['sort_position'] . '" size="2" /></td>
						<td><input type="checkbox" name="del[' . $cur_forum['id'] . ']" value="1" /></td>
						<td><a href="' . $base_config['baseurl'] . '/admin/forums/edit/' . $cur_forum['id'] . '">' . translate('edit') . '</a></td>
					</tr>';
				}
				echo '</table>';
			}
?>
			<p><input type="submit" name="form_sent_forums" value="<?php echo translate('updateforums'); ?>" /></p>
		</form>
	</div>
</div>
<?php
if ($db_info['type'] != 'sqlite3') {
?>
<script type="text/javascript">
//redirect people if their browser supports JS
window.location = "<?php echo $base_config['baseurl']; ?>/admin/forums/enhanced";
</script>
<?php } ?>