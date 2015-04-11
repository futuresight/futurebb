<?php
$page_title = 'Edit language';
$breadcrumbs = array(translate('administration') => 'admin', translate('interface') => 'admin/interface', 'Translation' => 'admin/interface/language');

if (isset($_POST['add_new'])) {
	$q = new DBInsert('language', array('language' => $_POST['language'], 'langkey' => $_POST['key'], 'value' => $_POST['content'], 'category' => $_POST['category']), 'Failed to insert language key');
	$q->commit();
	
	$q = new DBInsert('interface_history', array('action' => 'create', 'area' => 'language', 'field' => $db->insert_id(), 'user' => $futurebb_user['id'], 'time' => time(), 'old_value' => ''), 'Failed to insert history entry');
	$q->commit();
	
	//clear the cache
	CacheEngine::CacheLanguage();
	redirect($base_config['baseurl'] . '/admin/interface/language?language=' . $_POST['language'] . '&category=' . $_POST['category']);
}

if (isset($_POST['delete_id'])) {
	$id = intval($_POST['delete_id']);
	$q = new DBSelect('language', array('*'), 'id=' . $id, 'Failed to get language key to delete');
	$result = $q->commit();
	if (!$db->num_rows($result)) {
		httperror(404);
	}
	$page = $db->fetch_assoc($result);
		
	$lines = array();
	foreach ($page as $key => $val) {
		$lines[] = $key . '=>' . $val;
	}
	
	$q = new DBInsert('interface_history', array('action' => 'delete', 'area' => 'language', 'field' => intval($id), 'user' => $futurebb_user['id'], 'time' => time(), 'old_value' => implode("\n", $lines)), 'Failed to insert history entry');
	$q->commit();
	
	$q = new DBDelete('language', 'id=' . $id, 'Failed to delete page entry');
	$q->commit();
	
	//clear the cache
	$maindir = FORUM_ROOT . '/app_config/cache/language';
	if (file_exists($maindir) && is_dir($maindir)) {
		$handle = opendir($maindir);
		while ($file = readdir($handle)) {
			if ($file != '.' && $file != '..') {
				unlink($maindir . '/' . $file);
			}
		}
	}
	redirect($base_config['baseurl'] . '/admin/interface/language');
}
if (isset($_POST['form_sent'])) {
	if (futurebb_hash($_POST['confirmpwd']) == $futurebb_user['password']) {
		$q = new DBSelect('language', array('id', 'langkey', 'value', 'category'), 'id IN(' . implode(',', array_keys($_POST['key'])) . ')', 'Failed to find language list');
		$result = $q->commit();
		while ($old_lang = $db->fetch_assoc($result)) {
			$id = $old_lang['id'];
			
			$newkey = $_POST['key'][$id];
			$newval = $_POST['value'][$id];
			$newcategory = $_POST['category'][$id];
			
			$changes = array();
			if ($newkey != $old_lang['langkey']) {
				$changes['langkey'] = $newkey;
			}
			if ($newval != $old_lang['value']) {
				$changes['value'] = $newval;
			}
			if ($newcategory != $old_lang['category']) {
				$changes['category'] = $newcategory;
			}
			
			if (!empty($changes)) {
				$oldval = array();
				foreach ($old_lang as $db_key => $db_val) {
					$oldval[] = $db_key . '=>' . $db_val;
				}
				
				$q2 = new DBInsert('interface_history', array('action' => 'edit', 'area' => 'language', 'field' => $id, 'user' => $futurebb_user['id'], 'time' => time(), 'old_value' => implode("\n", $oldval)), 'Failed to insert history entry');
				$q2->commit();
				$q2 = new DBUpdate('language', $changes, 'id=' . $id, 'Failed to update language');
				$q2->commit();
			}
		}
		//clear the cache
		$maindir = FORUM_ROOT . '/app_config/cache/language';
		if (file_exists($maindir) && is_dir($maindir)) {
			$handle = opendir($maindir);
			while ($file = readdir($handle)) {
				if ($file != '.' && $file != '..') {
					unlink($maindir . '/' . $file);
				}
			}
		}
		redirect($base_config['baseurl'] . '/admin/interface/language');
		return;
	} else {
		echo '<p>Your password was incorrect. Hit the back button to try again.</p>';
		return;
	}
}
if (isset($_GET['delete'])) {
	?>
	<form action="<?php echo $base_config['baseurl']; ?>/admin/interface/language" method="post" enctype="multipart/form-data">
		<h3>Delete language key</h3>
		<p>Are you sure you want to delete the following language entry?</p>
		<p><?php
		$id = intval($_GET['delete']);
		$q = new DBSelect('language', array('*'), 'id=' . $id, 'Failed to find language item');
		$result = $q->commit();
		if (!$db->num_rows($result)) {
			httperror(404);
		}
		$lang_entry = $db->fetch_assoc($result);
		$lines = array();
		foreach ($lang_entry as $key => $val) {
			$lines[] = htmlspecialchars($key . '=>' . $val);
		}
		echo implode('<br />', $lines);
		?>
		<p><input type="hidden" name="delete_id" value="<?php echo $id; ?>" /><input type="submit" name="form_sent_delete" value="Yes" /> <a href="<?php echo $base_config['baseurl']; ?>/admin/interface/language">No</a></p>
	</form>
	<?php
	return;
}
if (!isset($_GET['language']) || !isset($_GET['category'])) {
	?>
	<form action="<?php echo $base_config['baseurl']; ?>/admin/interface/language" method="get" enctype="application/x-www-form-urlencoded">
		<h3>Edit translator</h3>
		<p>Please note that after making any changes, if they do not show up immediately, go into your forum root directory and delete everything in the directory <code>app_config/cache</code></p>
		<p>Select a language and category to edit: <select name="language"><?php
		$q = new DBSelect('language', array('DISTINCT(language)'), '', 'Failed to get language list');
		$result = $q->commit();
		while (list($language) = $db->fetch_row($result)) {
			echo '<option value="' . htmlspecialchars($language) . '">' . htmlspecialchars($language) . '</option>';
		}
		?></select> <select name="category"><?php
		$q = new DBSelect('language', array('DISTINCT(category)'), '', 'Failed to get category list');
		$result = $q->commit();
		while (list($cat) = $db->fetch_row($result)) {
			echo '<option value="' . htmlspecialchars($cat) . '">' . htmlspecialchars($cat) . '</option>';
		}
		?></select> <input type="submit" value="Go" /></p>
	</form>
	
	<form action="<?php echo $base_config['baseurl']; ?>/admin/interface/language" method="post" enctype="multipart/form-data">
		<h4>Add new key</h4>
		<table border="0" class="optionstable">
			<tr>
				<th>Language</th>
				<td><select name="language"><?php
		$q = new DBSelect('language', array('DISTINCT(language)'), '', 'Failed to get language list');
		$result = $q->commit();
		while (list($language) = $db->fetch_row($result)) {
			echo '<option value="' . htmlspecialchars($language) . '">' . htmlspecialchars($language) . '</option>';
		}
		?></select></td>
			</tr>
			<tr>
				<th>Key</th>
				<td><input type="text" name="key" /></td>
			</tr>
			<tr>
				<th>Content</th>
				<td><textarea name="content" rows="4" cols="50"></textarea></td>
			</tr>
			<tr>
				<th>Category</th>
				<td><input type="text" name="category" value="main" /></td>
			</tr>
		</table>
		<p><input type="submit" name="add_new" value="Add" /></p>
	</form>
	<?php
	return;
}

$q = new DBSelect('language', array('id', 'langkey', 'value', 'category'), 'language=\'' . $db->escape($_GET['language']) . '\' AND category=\'' . $db->escape($_GET['category']) . '\'', 'Failed to get language keys');
$q->set_order('langkey ASC');
$result = $q->commit();

$last_category = '';
?>
<form action="<?php echo $base_config['baseurl']; ?>/admin/interface/language" method="post" enctype="multipart/form-data">
	<table border="0">
		<tr>
			<th>Key</th>
			<th>Value</th>
			<th>Category</th>
			<th>Delete</th>
		</tr>
	<?php
	while ($lang_entry = $db->fetch_assoc($result)) {
		echo '<tr><td><input type="text" name="key[' . $lang_entry['id'] . ']" value="' . htmlspecialchars($lang_entry['langkey']) . '" /></td><td><textarea name="value[' . $lang_entry['id'] . ']" cols="50" rows="' . (ceil(strlen($lang_entry['value']) / 50.0)) . '">' . htmlspecialchars($lang_entry['value']) . '</textarea></td><td><input type="text" name="category[' . $lang_entry['id'] . ']" value="' . htmlspecialchars($lang_entry['category']) . '" /></td><td><a href="' . $base_config['baseurl'] . '/admin/interface/language?delete=' . $lang_entry['id'] . '" target="_BLANK" style="cursor:pointer; text-decoration:none">&#10060;</a></td></tr>';
	}
	?>
	</table>
	<p><?php echo translate('confirmpwd'); ?>: <input type="password" name="confirmpwd" /></p>
	<p><input type="hidden" name="language" value="<?php echo htmlspecialchars($_GET['language']); ?>" /><input type="submit" name="form_sent" value="Save" /></p>
</form>