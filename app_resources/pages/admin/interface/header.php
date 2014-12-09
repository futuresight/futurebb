<?php
$page_title = 'Edit Header';
$breadcrumbs = array(translate('administration') => 'admin', translate('interface') => 'admin/interface', 'Header links' => 'admin/interface/header');
if (isset($_POST['form_sent']) || isset($_POST['restore_default'])) {
	if (isset($_POST['restore_default'])) {
		$_POST['content'] = '<?xml version="1.0" ?>
<linkset>
    <link path="">index</link>
    <link path="users/$username$" perm="valid">profile</link>
    <link path="users" perm="g_user_list">userlist</link>
    <link path="search">search</link>
    <link path="admin" perm="g_admin_privs">administration</link>
    <link path="admin/bans" perm="g_mod_privs ~g_admin_privs">administration</link>
    <link path="register/$reghash$" perm="~valid">register</link>
    <link path="logout" perm="valid">logout</link>
</linkset>';
	}
	$preload = $_POST['content'];
	if (futurebb_hash($_POST['confirmpwd']) == $futurebb_user['password']) {
		//check the XML validity - we can't stop you from posting stupid stuff, but we can at least make sure it's formatted properly
		try {
			$xml = new SimpleXMLElement($_POST['content']);
			if (isset($xml->link)) {
				$q = new DBInsert('interface_history', array('action' => 'edit', 'area' => 'interface', 'user' => $futurebb_user['id'], 'time' => time(), 'old_value' => $futurebb_config['header_links']), 'Failed to update interface editing history');
				$q->commit();
				set_config('header_links', $_POST['content']);
				CacheEngine::CacheHeader();
			} else {
				echo '<p>No &lt;link&gt; elements are present. Please read the format guide linked below.</p>';
			}
		} catch (Exception $e) {
			echo '<p>XML format error: ' . $e->getMessage() . ' (not well-formed)</p>';
		}
		
	} else {
		echo '<p>Your password was incorrect. Please try again.</p>';
	}
} else {
	$preload = $futurebb_config['header_links'];
}
?>
<form action="<?php echo $base_config['baseurl']; ?>/admin/interface/header" method="post" enctype="multipart/form-data">
	<h3>Edit header links</h3>
	<p>Please read <a href="https://github.com/futuresight/futurebb/wiki/Header-link-format">the format guide</a> before continuing.</p>
	<p><textarea name="content" rows="20" cols="80"><?php echo htmlspecialchars($preload); ?></textarea></p>
	<p><?php echo translate('confirmpwd'); ?>: <input type="password" name="confirmpwd" /></p>
	<p><input type="submit" name="form_sent" value="<?php echo translate('save'); ?>" /> <input type="submit" name="restore_default" value="Restore default" /></p>
</form>