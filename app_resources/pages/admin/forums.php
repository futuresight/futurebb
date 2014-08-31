<?php
if (!$futurebb_user['g_admin_privs']) {
	httperror(403);
}
$page_title = translate('forums');
include FORUM_ROOT . '/app_resources/includes/admin.php';
if (isset($_POST['add_new_category'])) {
	$db->query('INSERT INTO `#^categories`(name,sort_position) VALUES(\'New category\',0)') or error('Failed to create new category', __FILE__, __LINE__, $db->error());
}
if (isset($_POST['add_new_forum'])) {
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
	$db->query('INSERT INTO `#^forums`(url,name,cat_id,sort_position) VALUES(\'' . $db->escape($name) . '\',\'' . $db->escape($_POST['name']) . '\',' . $_POST['category'] . ',0)') or error('Failed to create new category', __FILE__, __LINE__, $db->error());
}
if (!isset($dirs[3])) {
	$dirs[3] = '';
}
if ($dirs[3] == 'edit') {
	$fid = intval($dirs[4]);
	$result = $db->query('SELECT name,cat_id,description,view_groups,topic_groups,reply_groups FROM `#^forums` WHERE id=' . $fid) or error('Failed to find forum', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result)) {
		httperror(404);
	}
	if (isset($_POST['update_forum'])) {
		$view = '-';
		foreach ($_POST['view'] as $key => $val) {
			$view .= $key . '-';
		}
		$topics = '-';
		foreach ($_POST['topics'] as $key => $val) {
			$topics .= $key . '-';
		}
		$replies = '-';
		foreach ($_POST['reply'] as $key => $val) {
			$replies .= $key . '-';
		}
		$db->query('UPDATE `#^forums` SET description=\'' . $db->escape($_POST['desc']) . '\',view_groups=\'' . $view . '\',topic_groups=\'' . $topics . '\',reply_groups=\'' . $replies . '\',cat_id=' . intval($_POST['category']) . ' WHERE id=' . $fid) or error('Failed to update forum', __FILE__, __LINE__, $db->error());
		header('Location: ' . $base_config['baseurl'] . '/admin/forums'); return;
	}

	$cur_forum = $db->fetch_assoc($result);
	$breadcrumbs = array('Index' => '', 'Administration' => 'admin', 'Forums' => 'admin/forums', $cur_forum['name'] => '/admin/forums/edit/' . $fid);
	?>
	<div class="forum_content">
		<form action="<?php echo $base_config['baseurl']; ?>/admin/forums/edit/<?php echo $fid; ?>" method="post" enctype="multipart/form-data">
			<h3><?php echo translate('information'); ?></h3>
			<table border="0">
				<tr>
					<td><?php echo translate('forumname'); ?></td>
					<td><?php echo htmlspecialchars($cur_forum['name']); ?></td>
				</tr>
				<tr>
					<td><?php echo translate('category'); ?></td>
					<td><select name="category"><?php
					$result = $db->query('SELECT id,name FROM `#^categories` ORDER BY sort_position ASC') or error('Failed to get categories', __FILE__, __LINE__, $db->error());
					while (list($id,$name) = $db->fetch_row($result)) {
						echo '<option value="' . $id . '"';
						if ($id == $cur_forum['cat_id']) {
							echo ' selected="selected"';
						}
						echo '>' . htmlspecialchars($name) . '</option>';
					}
					?></select></td>
				</tr>
				<tr>
					<td><?php echo translate('description'); ?></td>
					<td><textarea name="desc" rows="5" cols="50"><?php echo htmlspecialchars($cur_forum['description']); ?></textarea></td>
				</tr>
			</table>
			<h3><?php echo translate('permissions'); ?></h3>
			<table border="0">
				<tr>
					<th><?php echo translate('grouptitle'); ?></th>
					<th><?php echo translate('viewforum'); ?></th>
					<th><?php echo translate('posttopics'); ?></th>
					<th><?php echo translate('postreplies'); ?></th>
				</tr>
			<?php
			$result = $db->query('SELECT g_id,g_name FROM `#^user_groups` ORDER BY g_permanent ASC,g_title ASC') or error('Failed to get user groups', __FILE__, __LINE__, $db->error());
			while (list($id,$name) = $db->fetch_row($result)) {
				echo '
				<tr>
					<td>' . htmlspecialchars($name) . '</td>
					<td><input type="checkbox" name="view[' . $id . ']"';
					if (strstr($cur_forum['view_groups'], '-' . $id . '-')) {
						echo ' checked="checked"';
					}
					echo ' /></td>
					<td><input type="checkbox" name="topics[' . $id . ']"';
					if (strstr($cur_forum['topic_groups'], '-' . $id . '-')) {
						echo ' checked="checked"';
					}
					echo ' /></td>
					<td><input type="checkbox" name="reply[' . $id . ']"';
					if (strstr($cur_forum['reply_groups'], '-' . $id . '-')) {
						echo ' checked="checked"';
					}
					echo ' /></td>
				</tr>';
			}
			?>
			</table>
			<p><input type="submit" name="update_forum" value="<?php echo translate('update'); ?>" /></p>
		</form>
	</div>
	<?php
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
			<p><input type="submit" name="add_new_category" value="Add new category" /></p>
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