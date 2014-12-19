<?php
$page_title = 'Edit language';
$breadcrumbs = array(translate('administration') => 'admin', translate('interface') => 'admin/interface', 'Translation' => 'admin/interface/language');

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
				
				$q2 = new DBInsert('interface_history', array('action' => 'edit', 'area' => 'language', 'field' => $id, 'old_value' => implode("\n", $oldval)), 'Failed to insert history entry');
				$q2->commit();
				$q2 = new DBUpdate('language', $changes, 'id=' . $id, 'Failed to update language');
				$q2->commit();
			}
		}
		//clear the cache
		$maindir = FORUM_ROOT . '/app_config/cache/language/' . $language;
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
if (!isset($_GET['language']) || !isset($_GET['category'])) {
	?>
	<form action="<?php echo $base_config['baseurl']; ?>/admin/interface/language" method="get" enctype="application/x-www-form-urlencoded">
		<h3>Edit translator</h3>
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
		</tr>
	<?php
	while ($lang_entry = $db->fetch_assoc($result)) {
		echo '<tr><td><input type="text" name="key[' . $lang_entry['id'] . ']" value="' . htmlspecialchars($lang_entry['langkey']) . '" /></td><td><textarea name="value[' . $lang_entry['id'] . ']" cols="50" rows="' . (ceil(strlen($lang_entry['value']) / 50.0)) . '">' . htmlspecialchars($lang_entry['value']) . '</textarea></td><td><input type="text" name="category[' . $lang_entry['id'] . ']" value="' . htmlspecialchars($lang_entry['category']) . '" /></td></tr>';
	}
	?>
	</table>
	<p><?php echo translate('confirmpwd'); ?>: <input type="password" name="confirmpwd" /></p>
	<p><input type="hidden" name="language" value="<?php echo htmlspecialchars($_GET['language']); ?>" /><input type="submit" name="form_sent" value="Save" /></p>
</form>