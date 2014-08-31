<?php
$page_title = 'Import forum data';
if (!file_exists(FORUM_ROOT . '/converter.xml')) {
	httperror(404);
}
$xml = new SimpleXMLElement(file_get_contents(FORUM_ROOT . '/converter.xml'));
if (isset($dirs[2]) && $dirs[2] == 'done') {
	$result = $db->query('SELECT g_id FROM `#^user_groups` WHERE g_guest_group=0') or error('Failed to get user groups', __FILE__, __LINE__, $db->error());
	$groups = array();
	while (list($id) = $db->fetch_row($result)) {
		$groups[] = $id;
	}
	$groups_str = '-' . implode('-', $groups) . '-';
	$db->query('UPDATE `#^forums` SET topic_groups=\'' . $groups_str . '\',reply_groups=\'' . $groups_str . '\',view_groups=\'' . $groups_str . '\'') or error('Failed to fix forum permissions', __FILE__, __LINE__, $db->error());
	$db->query('UPDATE `#^user_groups` SET g_user_list_groups=\'' . implode(',', $groups) . '\'') or error('Failed to update user list info', __FILE__, __LINE__, $db->error());
	?>
	<h2>All done!</h2>
	<p>Now you just need to go to the <a href="<?php echo $base_config['baseurl']; ?>/admin/maintenance">maintenance page</a> and run each tool.</a>
	<?php
} else if (isset($_POST['form_sent'])) {
	//first apply config changes
	foreach ($xml->config->cfgset as $val) {
		set_config((string)$val->c_name, (string)$val->c_value);
	}
	echo '<p>Imported configuration!</p>';
	header('Refresh: 2; url=' . $base_config['baseurl'] . '/mass_import?import&part=users');
} else if (isset($_GET['import'])) {
	switch ($_GET['part']) {
		case 'users':
			$users_xml = $xml->users;
			
			if (!isset($_GET['startid'])) {
				$startid = 0;
				$q = new DBDelete('users', '1=1', 'Failed to delete all existing users');
				$q->commit();
				unset($q);
			} else {
				$startid = intval($_GET['startid']);
			}
			$q = new DBDelete('users', 'username=\'Guest\'', 'Failed to delete guest user');
			$q->commit();
			
			$i = 0;
			foreach ($users_xml->user as $val) {
				$i++;
				if ($i >= $startid) {
					$fields = array();
					foreach ($val as $field => $data) {
						$fields[(string)$field] = (string)$data;
					}
					
					$q = new DBInsert('users', $fields, 'Failed to insert user');
					$q->commit();
				}
				
				if ($i >= $startid + 299) {
					$db->query('INSERT INTO `#^users`(username) VALUES(\'Guest\')') or error('Failed to make temporary guest user', __FILE__, __LINE__, $db->error());
					echo '<p>Importing users...</p>';
					header('Refresh: 2; url=' . $base_config['baseurl'] . '/mass_import?import&part=users&startid=' . ($startid + 300));
					return;
				}
			}
			$q = new DBInsert('users', array('username' => 'Guest'), 'Failed to make guest user');
			$q->commit();
			unset($q);
			echo '<p>Users completed!</p>';
			header('Refresh: 2; url=' . $base_config['baseurl'] . '/mass_import?import&part=user_groups');
			break;
		case 'user_groups':
			$q = new DBDelete('user_groups', '1=1', 'Failed to delete all existing user groups');
			$q->commit();
			$groups_xml = $xml->user_groups;
			foreach ($groups_xml->group as $val) {
				$fields = array();
				foreach ($val as $field => $data) {
					$fields[(string)$field] = (string)$data;
				}
				$q = new DBInsert('user_groups', $fields, 'Failed to insert user group');
				$q->commit();
			}
			echo '<p>User groups completed!</p>';
			header('Refresh: 2; url=' . $base_config['baseurl'] . '/mass_import?import&part=topics');
			break;
		case 'topics':
			if (!isset($_GET['startid'])) {
				$startid = 0;
				$q = new DBDelete('topics', '1=1', 'Failed to delete all existing topics');
				$q->commit();
			} else {
				$startid = intval($_GET['startid']);
			}
			
			$topics_xml = $xml->topics;
			$i = 0;
			foreach ($topics_xml->topic as $val) {
				$i++;
				
				if ($i >= $startid) {
					$fields = array();
					foreach ($val as $field => $data) {
						$fields[(string)$field] = (string)$data;
					}
					$q = new DBInsert('topics', $fields, 'Failed to insert topic');
					$q->commit();
				}
				
				if ($i >= $startid + 299) {
					echo '<p>Importing topics...</p>';
					header('Refresh: 2; url=' . $base_config['baseurl'] . '/mass_import?import&part=topics&startid=' . ($startid + 300));
					return;
				}
			}
			echo '<p>Topics completed!</p>';
			header('Refresh: 0; url=' . $base_config['baseurl'] . '/mass_import?import&part=posts');
			break;
		case 'posts':
			if (!isset($_GET['startid'])) {
				$startid = 0;
				$q = new DBDelete('posts', '1=1', 'Failed to delete all existing posts');
				$q->commit();
			} else {
				$startid = intval($_GET['startid']);
			}
			
			$posts_xml = $xml->posts;
			$i = 0;
			foreach ($posts_xml->post as $val) {
				$i++;
				
				if ($i >= $startid) {
					$fields = array();
					foreach ($val as $field => $data) {
						$fields[(string)$field] = (string)$data;
					}
					$q = new DBInsert('posts', $fields, 'Failed to insert post');
					$q->commit();
				}
				
				if ($i >= $startid + 299) {
					echo '<p>Importing posts...</p>';
					header('Refresh: 2; url=' . $base_config['baseurl'] . '/mass_import?import&part=posts&startid=' . ($startid + 300));
					return;
				}
			}
			echo '<p>Posts completed!</p>';
			header('Refresh: 2; url=' . $base_config['baseurl'] . '/mass_import?import&part=forums');
			break;
		case 'forums':
			$q = new DBDelete('forums', '1=1', 'Failed to delete all existing forums');
			$q->commit();
			$forums_xml = $xml->forums;
			foreach ($forums_xml->forum as $val) {
				$fields = array();
				foreach ($val as $field => $data) {
					$fields[(string)$field] = (string)$data;
				}
				$q = new DBInsert('forums', $fields, 'Failed to insert forum');
				$q->commit();
			}
			echo '<p>Forums completed!</p>';
			header('Refresh: 2; url=' . $base_config['baseurl'] . '/mass_import?import&part=categories');
			break;
		case 'categories':
			$q = new DBDelete('categories', '1=1', 'Failed to delete all existing categories');
			$q->commit();
			$cats_xml = $xml->categories;
			foreach ($cats_xml->category as $val) {
				$fields = array();
				foreach ($val as $field => $data) {
					$fields[(string)$field] = (string)$data;
				}
				$q = new DBInsert('categories', $fields, 'Failed to insert category');
				$q->commit();
			}
			echo '<p>Categories completed!</p>';
			header('Refresh: 2; url=' . $base_config['baseurl'] . '/mass_import?import&part=createforumurls');
			break;
		case 'createforumurls':
			$q = new DBSelect('forums', array('id','name'), '1=1', 'Failed to get forum list');
			$r1 = $q->commit();
			while (list($id,$fname) = $db->fetch_row($r1)) {
				$base_name = URLEngine::make_friendly($fname);
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
								
				$db->query('UPDATE `#^forums` SET url=\'' . $db->escape($name) . '\' WHERE id=' . $id) or error('Failed to update forum URL', __FILE__, __LINE__, $db->error());
			}
			header('Refresh: 2; url=' . $base_config['baseurl'] . '/mass_import?import&part=createtopicurls');
			break;
		case 'createtopicurls':
			if (isset($_GET['startid'])) {
				$startid = intval($_GET['startid']);
			} else {
				$startid = 0;
			}
			$r1 = $db->query('SELECT id,subject FROM `#^topics` ORDER BY id ASC LIMIT ' .$startid . ',300') or error('Failed to get topic list', __FILE__, __LINE__, $db->error());
			if (!$db->num_rows($r1)) {
				redirect($base_config['baseurl'] . '/mass_import/done');
			}
			while (list($id,$fname) = $db->fetch_row($r1)) {
				$name = URLEngine::make_friendly($fname);
				$base_name = $name;
				//check for forums with the same URL
				$result = $db->query('SELECT url FROM `#^topics` WHERE url LIKE \'' . $db->escape($name) . '%\'') or error('Failed to check for similar URLs', __FILE__, __LINE__, $db->error());
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
						$name = $base_name . '-' . $add_num;
						$ok = false;
					}
				}
				
				$db->query('UPDATE `#^topics` SET url=\'' . $db->escape($name) . '\' WHERE id=' . $id) or error('Failed to update topic URL', __FILE__, __LINE__, $db->error());
			}
			echo '<p>Creating topic URLs......</p>';
			header('Refresh: 2; url=' . $base_config['baseurl'] . '/mass_import?import&part=createtopicurls&startid=' . ($startid + 300));
			break;
		default:
			httperror(404);
	}
} else {
	echo '<h2>Import forum data</h2>';
	$config = array();
	$cfg_xml = $xml->config;
	foreach ($cfg_xml->cfgset as $cfgitem) {
		$config[(string)$cfgitem->c_name] = ((string)$cfgitem->c_value);
	}
	echo '<p>This tool will import data that you have exported from another forum system. A few details are provided below. Please review them and make sure they are correct.</p>';
	echo '<table border="0">
		<tr>
			<td>Board title</td>
			<td>' . htmlspecialchars($config['board_title']) . '</td>
		</tr>
		<tr>
			<td>Admin email</td>
			<td>' . htmlspecialchars($config['admin_email']) . '</td>
		</tr>
	</table>';
	?>
	<form action="<?php echo $base_config['baseurl']; ?>/mass_import" method="post" enctype="multipart/form-data">
		<p style="font-weight:bold; color:#F00">Warning! Importing can take a long time, will greatly increase the server load, and erase all currently stored forum data!</p>
		<p><input type="checkbox" name="agree" id="agree" onchange="document.getElementById('submitbtn').disabled = !this.checked;" /><label for="agree">I understand the risk</label><br /><input type="submit" name="form_sent" value="Proceed" id="submitbtn" /></p>
		<script type="text/javascript">
		document.getElementById('submitbtn').disabled = true;
		</script>
	</form>
	<?php
}