<?php
if (!$futurebb_user['g_admin_privs']) {
	httperror(403);
}
translate('<addfile>', 'admin');
$page_title = 'Censoring';
include FORUM_ROOT . '/app_resources/includes/admin.php';

if (isset($_GET['download'])) {
	$censoring = base64_decode($futurebb_config['censoring']);
	$entries = explode("\n", $censoring);
	foreach ($entries as $val) {
		$data = explode(chr(1), $val);
		if (sizeof($data) > 1) {
			$find[] = $data[0];
			$replace[] = $data[1];
		}
	}
	$xml = new SimpleXMLElement('<?xml version="1.0" ?>' . "\n" . '<dict></dict>');
	foreach ($find as $key => $val) {
		$dict = $xml->addChild('word');
		$dict->addChild('find', $val);
		$dict->addChild('repl', $replace[$key]);
	}
	ob_end_clean();
	header('Content-type: application/xml');
	header('Content-disposition: attachment; filename=censoring.xml');
	echo $xml->asXML();
	$db->close();
	die;
}
if (isset($_POST['form_sent_add'])) {
	//add a new entry
	$censoring = base64_decode($futurebb_config['censoring']);
	$entries = explode("\n", $censoring);
	$entries[] = $_POST['newfind'] . chr(1) . $_POST['newreplace'];
	foreach ($entries as $key => $val) {
		if ($val == '' || $val == chr(1)) {
			unset($entries[$key]);
		}
	}
	set_config('censoring', base64_encode(implode("\n", $entries)));
}
if (isset($_POST['form_sent'])) {
	//update/delete old entries
	$entries = array();
	foreach ($_POST['delete'] as $key => $val) {
		$_POST['find'][$key] = '';
	}
	foreach ($_POST['find'] as $key => $find) {
		if ($find == '') {
			unset($_POST['find'][$key]);
		} else {
			$replace = $_POST['replace'][$key];
			$entries[] = $find . chr(1) . $replace;
		}
	}
	set_config('censoring', base64_encode(implode("\n", $entries)));
}
if (isset($_POST['imghost_form_sent'])) {
	$list = $_POST['imghostrestrict'];
	$list = str_replace("\r", "\n", $list);
	while (strstr($list, "\n\n")) {
		$list = str_replace("\n\n", "\n", $list);
	}
	set_config('imghostrestriction', $_POST['imgrestricttype'] . '|' . $list);
}
if (isset($_POST['form_sent_upload']) && is_uploaded_file($_FILES['dict']['tmp_name'])) {
	$censoring = base64_decode($futurebb_config['censoring']);
	$entries = explode("\n", $censoring);
	foreach ($entries as $val) {
		$data = explode(chr(1), $val);
		if (sizeof($data) > 1) {
			$find[] = $data[0];
			$replace[] = $data[1];
		}
	}
	$xml = new SimpleXMLElement(file_get_contents($_FILES['dict']['tmp_name']));
	unlink($_FILES['dict']['tmp_name']);
	if (!$xml) {
		echo '<p>' . translate('invalidxml') . '</p>';
	}
	$xml_words = $xml->word;
	foreach ($xml_words as $word) {
		$find[] = (string)$word->find;
		$replace[] = (string)$word->repl;
	}
	$entries = array();
	foreach ($find as $key => $val) {
		$entries[] = $val . chr(1) . $replace[$key];
	}
	set_config('censoring', base64_encode(implode("\n", $entries)));
}
$censoring = base64_decode($futurebb_config['censoring']);
$entries = explode("\n", $censoring);
?>
<div class="container">
	<?php make_admin_menu(); ?>
	<div class="forum_content rightbox admin">
		<h2><?php echo translate('censoring'); ?></h2>
		<p><?php echo translate('censorintro'); ?></p>
		<form action="<?php echo $base_config['baseurl']; ?>/admin/censoring" method="post" enctype="multipart/form-data">
			<h3><?php echo translate('newword'); ?></h3>
			<table border="0">
				<tr><th><?php echo translate('find'); ?></th><th><?php echo translate('replacewith'); ?></th></tr>
				<tr><td><input type="text" name="newfind" /></td><td><input type="text" name="newreplace" /></td></tr>
			</table>
			<p><input type="submit" name="form_sent_add" value="<?php echo translate('submit'); ?>" /></p>
		</form>
		<form action="<?php echo $base_config['baseurl']; ?>/admin/censoring" method="post" enctype="multipart/form-data">
			<h3><?php echo translate('existingwords'); ?></h3>
			<table border="0">
				<tr>
					<th><?php echo translate('find'); ?></th>
					<th><?php echo translate('replacewith'); ?></th>
					<th><?php echo translate('delete'); ?></th>
				</tr>
			<?php
			if (sizeof($entries) > 1 || ($entries[0] != '' && $entries[0] != chr(1))) {
				foreach ($entries as $key => $val) {
					$parts = explode(chr(1), $val);
					?>
					<tr>
						<td><input type="text" name="find[<?php echo $key; ?>]" value="<?php if (isset($parts[0])) echo $parts[0]; ?>" /></td>
						<td><input type="text" name="replace[<?php echo $key; ?>]" value="<?php if (isset($parts[1])) echo $parts[1]; ?>" /></td>
						<td><input type="checkbox" name="delete[<?php echo $key; ?>]" /></td>
					</tr>
				<?php 
				} 
			}
			?>
			</table>
			<p><input type="submit" name="form_sent" value="<?php echo translate('update'); ?>" /></p>
		</form>
		<h3><?php echo translate('importcensor1'); ?></h3>
		<form action="<?php echo $base_config['baseurl']; ?>/admin/censoring" method="post" enctype="multipart/form-data">
			<p><?php echo translate('importcensor2'); ?></p>
			<p><input type="file" name="dict" accept="application/xml" /><br /><input type="submit" name="form_sent_upload" value="<?php echo translate('upload'); ?>" /></p>
		</form>
		<h3><?php echo translate('export'); ?></h3>
		<p><?php echo translate('exportcensor'); ?><br /><a href="?download"><?php echo translate('download'); ?></a></p>
		
		<h2><?php echo translate('imghostrestriction'); ?></h2>
		<form action="<?php echo $base_config['baseurl']; ?>/admin/censoring" method="post" enctype="multipart/form-data">
			<?php
			$parts = explode('|', $futurebb_config['imghostrestriction'], 2);
			?>
			<p><input type="radio" name="imgrestricttype" id="none" value="none"<?php if ($parts[0] == 'none') echo ' checked="checked"'; ?> /><label for="none"><?php echo translate('none'); ?></label><br /><input type="radio" name="imgrestricttype" id="whitelist" value="whitelist"<?php if ($parts[0] == 'whitelist') echo ' checked="checked"'; ?> /><label for="whitelist"><?php echo translate('whitelist'); ?></label><br /><input type="radio" name="imgrestricttype" id="blacklist" value="blacklist"<?php if ($parts[0] == 'blacklist') echo ' checked="checked"'; ?> /><label for="blacklist"><?php echo translate('blacklist'); ?></label></p>
			<p><?php echo translate('hostlist'); ?><br /><textarea name="imghostrestrict"><?php echo htmlspecialchars($parts[1]); ?></textarea></p>
			<p><input type="submit" name="imghost_form_sent" value="Update image rules" /></p>
		</form>
		
	</div>
</div>