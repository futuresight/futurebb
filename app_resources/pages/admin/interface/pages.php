<?php
$page_title = 'Edit page list';
$breadcrumbs = array(translate('administration') => 'admin', translate('interface') => 'admin/interface', 'URL Mapping' => 'admin/interface/pages');

if (isset($_POST['delete'])) {
	if (isset($_POST['confirmpwd']) && futurebb_hash($_POST['confirmpwd']) == $futurebb_user['password']) {
		$id = intval($_POST['delete_id']);
		$q = new DBSelect('pages', array('*'), 'id=' . $id, 'Failed to get page to delete');
		$result = $q->commit();
		if (!$db->num_rows($result)) {
			httperror(404);
		}
		$page = $db->fetch_assoc($result);
		
		$lines = array();
		foreach ($page as $key => $val) {
			$lines[] = $key . '=>' . $val;
		}
		
		$q = new DBInsert('interface_history', array('action' => 'delete', 'area' => 'pages', 'field' => intval($id), 'user' => $futurebb_user['id'], 'time' => time(), 'old_value' => base64_encode(implode("\n", $lines))), 'Failed to insert history entry');
		$q->commit();
		
		$q = new DBDelete('pages', 'id=' . $id, 'Failed to delete page entry');
		$q->commit();
		
		CacheEngine::CachePages();
	} else {
		$_GET['delete'] = $_POST['delete_id'];
	}
}

if (isset($_GET['delete'])) {
	$id = intval($_GET['delete']);
	$q = new DBSelect('pages', array('*'), 'id=' . $id, 'Failed to get page to delete');
	$result = $q->commit();
	if (!$db->num_rows($result)) {
		httperror(404);
	}
	$page = $db->fetch_assoc($result);
	?>
	<form action="<?php echo $base_config['baseurl']; ?>/admin/interface/pages" method="post" enctype="multipart/form-data">
		<h3>Delete the following page?</h3>
		<?php
		if (isset($_POST['delete'])) {
			echo '<p>Invalid password. Please try again.</p>';
		}
		?>
		<ul>
		<?php
		foreach ($page as $key => $val) {
			echo '<li>' . htmlspecialchars($key) . ' - ' . htmlspecialchars($val) . '</li>';
		}
		?>
		</ul>
		<p>Enter your password: <input type="password" name="confirmpwd" /></p>
		<p><input type="hidden" name="delete_id" value="<?php echo $id; ?>" /> <input type="submit" name="delete" value="Yes" /> <a href="<?php echo $base_config['baseurl']; ?>/admin/interface/pages">No</a></p>
	</form>
	<?php
	return;
}

$q = new DBSelect('pages', array('*'), '', 'Failed to get page list');
$result = $q->commit();
if (isset($_POST['form_sent_b'])) {
	if (futurebb_hash($_POST['confirmpwd']) == $futurebb_user['password']) {
		foreach ($_POST as $key => $val) {
			if (ctype_digit((string)$key)) {
				//insert history entry
				$select = new DBSelect('pages', array('*'), 'id=' . intval($key), 'Failed to get old value');
				$result = $select->commit();
				$element = $db->fetch_assoc($result);
				$lines = array();
				foreach ($element as $db_key => $db_val) {
					$lines[] = $db_key . '=>' . $db_val;
				}
				$insertquery = new DBInsert('interface_history', array('action' => 'edit', 'area' => 'pages', 'field' => intval($key), 'user' => $futurebb_user['id'], 'time' => time(), 'old_value' => base64_encode(implode("\n", $lines))), 'Failed to insert history entry');
				$insertquery->commit();
				foreach ($val as $field => $field_value) {
					//update the field
					$updatequery = new DBUpdate('pages', array($field => $field_value), 'id=' . intval($key), 'Failed to update page entry');
					$updatequery->commit();
				}
			}
		}
		CacheEngine::CachePages();
		redirect($base_config['baseurl'] . '/admin/interface/pages');
	} else {
		echo '<p>Your password was incorrect. Hit the back button to try again.</p>';
		return;
	}
} else if (isset($_POST['form_sent_a'])) {
	//submitted, but not confirmed
	$changes_list = array();
	$changes_submit = array();
	while ($page = $db->fetch_assoc($result)) {
		if (isset($_POST['url'][$page['id']])) {
			//it's changed somehow, reflect the changes
			$changed = false;
			$cur_change = '<tr><td';
			if ($_POST['url'][$page['id']] != $page['url']) {
				$cur_change .= ' style="font-weight:bold; color:#39F; background-color:#555"';
				$changed = true;
				$changes_submit[] = array('id' => $page['id'], 'name' => 'url', 'value' => $_POST['url'][$page['id']]);
			}
			$cur_change .= '>' . htmlspecialchars($_POST['url'][$page['id']]) . '</td><td';
			if ($_POST['file'][$page['id']] != $page['file']) {
				$cur_change .= ' style="font-weight:bold; color:#39F; background-color:#555"';
				$changed = true;
				$changes_submit[] = array('id' => $page['id'], 'name' => 'file', 'value' => $_POST['file'][$page['id']]);
			}
			$cur_change .= '>' . htmlspecialchars($_POST['file'][$page['id']]) . '</td><td';
			if ($page['template'] != isset($_POST['template'][$page['id']])) {
				$cur_change .= ' style="font-weight:bold; color:#39F; background-color:#555"';
				$changed = true;
				$changes_submit[] = array('id' => $page['id'], 'name' => 'template', 'value' => (isset($_POST['template'][$page['id']]) ? 1 : 0));
			}
			$cur_change .= '>' . (isset($_POST['template'][$page['id']]) ? 'Yes' : 'No') . '</td><td';
			if ($page['nocontentbox'] != isset($_POST['nocontentbox'][$page['id']])) {
				$cur_change .= ' style="font-weight:bold; color:#39F; background-color:#555"';
				$changed = true;
				$changes_submit[] = array('id' => $page['id'], 'name' => 'nocontentbox', 'value' => (isset($_POST['nocontentbox'][$page['id']]) ? 1 : 0));
			}
			$cur_change .= '>' . (isset($_POST['nocontentbox'][$page['id']]) ? 'Yes' : 'No') . '</td><td';
			if ($page['moderator'] != isset($_POST['moderator'][$page['id']])) {
				$cur_change .= ' style="font-weight:bold; color:#39F; background-color:#555"';
				$changed = true;
				$changes_submit[] = array('id' => $page['id'], 'name' => 'moderator', 'value' => (isset($_POST['moderator'][$page['id']]) ? 1 : 0));
			}
			$cur_change .= '>' . (isset($_POST['moderator'][$page['id']]) ? 'Yes' : 'No') . '</td><td';
			if ($page['admin'] != isset($_POST['admin'][$page['id']])) {
				$cur_change .= ' style="font-weight:bold; color:#39F; background-color:#555"';
				$changed = true;
				$changes_submit[] = array('id' => $page['id'], 'name' => 'admin', 'value' => (isset($_POST['admin'][$page['id']]) ? 1 : 0));
			}
			$cur_change .= '>' . (isset($_POST['admin'][$page['id']]) ? 'Yes' : 'No') . '</td><td';
			if ($page['subdirs'] != isset($_POST['subdirs'][$page['id']])) {
				$cur_change .= ' style="font-weight:bold; color:#39F; background-color:#555"';
				$changed = true;
				$changes_submit[] = array('id' => $page['id'], 'name' => 'subdirs', 'value' => (isset($_POST['subdirs'][$page['id']]) ? 1 : 0));
			}
			$cur_change .= '>' . (isset($_POST['subdirs'][$page['id']]) ? 'Yes' : 'No') . '</td></tr>';
			if ($changed) {
				$changes_list[] = $cur_change;
			}
		}
	}
	if (sizeof($changes_list) != 0) {
		?>
	<h3>Confirm</h3>
	<p>These are the values you are changing:</p>
	<table border="0">
		<tr>
			<th>URL</th>
			<th>File</th>
			<th>Template</th>
			<th>No content box</th>
			<th>Moderators</th>
			<th>Administrators</th>
			<th>Subdirectories</th>
		</tr>
		<?php
		echo implode("\n\t\t", $changes_list);
	?>
	</table>
	<form action="<?php echo $base_config['baseurl']; ?>/admin/interface/pages" method="post" enctype="multipart/form-data">
		<p>Please enter your password: <input type="password" name="confirmpwd" /></p>
		<p>
		<?php
		//put all changes into hidden fields
		foreach ($changes_submit as $change) {
			echo '<input type="hidden" name="' . $change['id'] . '[' . $change['name'] . ']" value="' . $change['value'] . '" />';
		}
		?>
		<input type="submit" name="form_sent_b" value="Submit" /> <a href="<?php echo $base_config['baseurl']; ?>/admin/interface/pages">Cancel</a></p>
	</form>
	<?php
		return;
	} else {
		echo '<p>You didn&apos;t make any changes. Hit your browser&apos;s back button to try again.</p>';
		return;
	}
}
?>
<h3>URL Mapping</h3>
<p style="color:#C00; font-weight:bold">Warning: Use extreme caution on this page. Certain URL mappings are critical to proper operation of FutureBB. If you edit them, you run the risk of blocking all access to your forum.</p>
<form action="<?php echo $base_config['baseurl']; ?>/admin/interface/pages" method="post" enctype="multipart/form-data">
	<table border="0">
		<tr>
			<th>URL</th>
			<th>File</th>
			<th>Template</th>
			<th>No content box</th>
			<th>Moderators</th>
			<th>Administrators</th>
			<th>Subdirectories</th>
			<th>Delete</th>
		</tr>
	<?php
	while ($page = $db->fetch_assoc($result)) {
		echo '<tr><td><input type="text" value="' . htmlspecialchars($page['url']) . '" size="25" name="url[' . $page['id'] . ']" /></td><td><input type="text" value="' . htmlspecialchars($page['file']) . '" size="27" name="file[' . $page['id'] . ']" /></td><td><input type="checkbox"' . ($page['template'] ? ' checked="checked"' : '') . ' name="template[' . $page['id'] . ']" /></td><td><input type="checkbox"' . ($page['nocontentbox'] ? ' checked="checked"' : '') . ' name="nocontentbox[' . $page['id'] . ']" /></td><td><input type="checkbox"' . ($page['moderator'] ? ' checked="checked"' : '') . ' name="moderator[' . $page['id'] . ']" /></td><td><input type="checkbox"' . ($page['admin'] ? ' checked="checked"' : '') . ' name="admin[' . $page['id'] . ']" /></td><td><input type="checkbox"' . ($page['subdirs'] ? ' checked="checked"' : '') . ' name="subdirs[' . $page['id'] . ']" /></td><td><a href="' . $base_config['baseurl'] . '/admin/interface/pages?delete=' . $page['id'] . '" target="_BLANK" style="cursor:pointer; text-decoration:none">&#10060;</a></td></tr>' . "\n";
	}
	?>
	</table>
	<p><input type="submit" name="form_sent_a" value="Save" /></p>
</form>