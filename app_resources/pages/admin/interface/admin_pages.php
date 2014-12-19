<?php
$page_title = 'Admin sidebar links';
$breadcrumbs = array(translate('administration') => 'admin', translate('interface') => 'admin/interface', 'Admin sidebar links' => 'admin/interface/admin_pages');

function proper_line_breaks($text) {
	$text = str_replace("\r", "\n", $text);
	while (strstr($text, "\n\n")) {
		$text = str_replace("\n\n", "\n", $text);
	}
	return $text;
}

if (isset($_POST['form_sent'])) {
	if (futurebb_hash($_POST['confirmpwd']) == $futurebb_user['password']) {
		if (proper_line_breaks($_POST['mod_pages']) != base64_decode($futurebb_config['mod_pages'])) {
			$q = new DBInsert('interface_history', array('action' => 'edit', 'area' => 'interface', 'field' => 'mod_pages', 'user' => $futurebb_user['id'], 'time' => time(), 'old_value' => base64_decode($futurebb_config['mod_pages'])), 'Failed to update interface editing history');
			$q->commit();
			set_config('mod_pages', base64_encode(proper_line_breaks($_POST['mod_pages'])));
		}
		if (proper_line_breaks($_POST['admin_pages']) != base64_decode($futurebb_config['admin_pages'])) {
			$q = new DBInsert('interface_history', array('action' => 'edit', 'area' => 'interface', 'field' => 'admin_pages', 'user' => $futurebb_user['id'], 'time' => time(), 'old_value' => base64_decode($futurebb_config['admin_pages'])), 'Failed to update interface editing history');
			$q->commit();
			set_config('admin_pages', base64_encode(proper_line_breaks($_POST['admin_pages'])));
		}
		CacheEngine::CacheAdminPages();
	} else {
		echo '<p>Your password was incorrect. Please try again.</p>';
	}
}
?>
<form action="<?php echo $base_config['baseurl']; ?>/admin/interface/admin_pages" method="post" enctype="multipart/form-data">
	<p>Enter in the following format:<br /><code>url=&gt;text</code></p>
	<p>The URL is simply the part that goes after <code><?php echo $base_config['baseurl']; ?>/admin/</code>, and the text is the language key to display in the link.</p>
	<h3>Administrator link list</h3>
	<textarea name="admin_pages" rows="15" cols="30"><?php echo htmlspecialchars(isset($_POST['admin_pages']) ? $_POST['admin_pages'] : base64_decode($futurebb_config['admin_pages'])); ?></textarea>
	<h3>Moderator link list</h3>
	<textarea name="mod_pages" rows="15" cols="30"><?php echo htmlspecialchars(isset($_POST['mod_pages']) ? $_POST['mod_pages'] : base64_decode($futurebb_config['mod_pages'])); ?></textarea>
	<p><?php echo translate('confirmpwd'); ?>: <input type="password" name="confirmpwd" /></p>
	<p><input type="submit" name="form_sent" value="Submit" /></p>
</form>