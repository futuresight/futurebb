<?php
if (!$futurebb_user['g_admin_privs']) {
	httperror(403);
}
$page_title = translate('extensions');
include FORUM_ROOT . '/app_resources/includes/admin.php';

if (isset($_POST['form_sent'])) {
	if (!is_uploaded_file($_FILES['ext_file']['tmp_name'])) {
		echo '<div class="forum_content"><p>' . translate('uploadfailed') . '<br /><a href="' . $base_config['baseurl'] . '/admin/extensions">' . translate('tryagain') . '</a></p></div>';
		return;
	}
	$filename = basename($_FILES['ext_file']['name']);
	if (!file_exists(FORUM_ROOT . '/temp') || !is_dir(FORUM_ROOT . '/temp')) {
		mkdir(FORUM_ROOT . '/temp');
	}
	if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) != 'zip') {
		echo '<div class="forum_content"><p>' . translate('notzip') . '<br /><a href="' . $base_config['baseurl'] . '/admin/extensions">' . translate('tryagain') . '</a></p></div>';
		return;
	}
	$ext_test_dir = FORUM_ROOT . '/temp/' . time() . rand(1,1000);
	mkdir($ext_test_dir);
	$zip = new ZipArchive();
	if ($zip->open($_FILES['ext_file']['tmp_name'])) {
		$zip->extractTo($ext_test_dir);
		$zip->close();
		if (!file_exists($ext_test_dir . '/info.php')) {
			echo '<div class="forum_content"><p>' . translate('noinfophp') . '<br /><a href="' . $base_config['baseurl'] . '/admin/extensions">' . translate('tryagain') . '</a></p></div>';
			return;
		}
		include $ext_test_dir . '/info.php';
		if (!isset($ext_info) || !is_array($ext_info)) {
			echo '<div class="forum_content"><p>' . translate('badextinfo') . '</a></p></div>';
			return;
		}
		$ext_info_req = array('title');
		foreach ($ext_info_req as $val) {
			if (!isset($ext_info[$val])) {
				echo '<div class="forum_content"><p>' . translate('extinfomissingkey', $val) . '</b><br /><a href="' . $base_config['baseurl'] . '/admin/extensions">' . translate('tryagain') . '</a></p></div>';
				return;
			}
		}
		echo '<div class="forum_content"><h2>' . translate('extinstallation') . '</h2><h3>' . $ext_info['title'] . '</h3><ul>';
		if (file_exists($ext_test_dir . '/database.php')) {
			include $ext_test_dir . '/database.php';
			echo '<li>' . translate('makingdbchanges') . '</li>';
		}
		if (isset($error)) {
			echo '<li>' . translate('Error') . ': ' . $error . '</li></ul></div>';
			return;
		}
		if (file_exists($ext_test_dir . '/install.php')) {
			include $ext_test_dir . '/install.php';
			echo '<li>' . translate('runninginstallphp') . '</li>';
		}
		if (file_exists($ext_test_dir . '/files') && is_dir($ext_test_dir . '/files')) {
			//recursive file copying!!!
			function moveDir($src,$dst) {
				if (!file_exists($dst)) {
					mkdir($dst);
				}
				$handle = opendir($src);
				while ($file = readdir($handle)) {
					if ($file != '.' && $file != '..') {
						if (is_dir($src . '/' . $file)) {
							moveDir($src . '/' . $file, $dst . '/' . $file);
						} else {
							rename($src . '/' . $file, $dst . '/' . $file);
						}
					}
				}
			}
			moveDir($ext_test_dir . '/files', FORUM_ROOT);
			echo '<li>' . translate('copyingfiles') . '</li>';
		}
		if (file_exists($ext_test_dir . '/changes.php')) {
			include $ext_test_dir . '/changes.php';
			if (!isset($changes)) {
				echo '<li>' . translate('nochangesvar') . '</li></ul></div>';
				return;
			}
			/*
			$changes should be in the following structure:
			array(
				1 => array(
					'file'	=>	'app_resources/somefile.php',
					'type'	=>	'add | replace',
					'find'	=>	array('line1', 'line2'),
					'change'	=>	array('line to add', 'line to add')
				)
			);
			*/
			foreach ($changes as $key => $change) {
				if (!isset($change['file']) || !isset($change['type']) || !isset($change['find']) || !isset($change['change'])) {
					echo '<li>' . translate('changesmissingkey', $key) . '</li></ul></div>';
					return;
				}
				$file = file_get_contents(FORUM_ROOT . '/' . $change['file']);
				$lines = explode("\n", $file);
				foreach ($lines as $line_num => $line) {
					if (trim($line) == trim($change['find'][0])) {
						$success = true;
						foreach ($change['find'] as $find_num => $cur_find) {
							if (trim($cur_find) != trim($lines[$line_num + $find_num])) {
								$success = false;
								break;
							}
						}
						if ($success) {
							//if there is a match, then add the code!
							if ($change['type'] == 'add') {
								$lines = array_move($lines, $line_num + sizeof($change['find']), sizeof($change['change']));
								for ($i = 0; $i < sizeof($change['change']); $i++) {
									$lines[$i + $line_num + sizeof($change['find'])] = $change['change'][$i];
								}
							} else if ($change['type'] == 'replace') {
								for ($i = 0; $i < sizeof($change['change']); $i++) {
									$lines[$i + $line_num] = $change['change'][$i];
								}
							} else {
								echo '<li>' . translate('invalidchangetype', $change['type']) . '</li></ul></div>';
							}
							file_put_contents(FORUM_ROOT . '/' . $change['file'], implode("\n", $lines));
							break;
						}
					}
				}
			}
		}
		
		echo '<li>' . translate('installcomplete') . '</li>';
		echo '</ul></div>';
	} else {
		echo '<div class="forum_content"><p>' . translate('unzipfailed') . '<br /><a href="' . $base_config['baseurl'] . '/admin/extensions">' . translate('tryagain') . '</a></p></div>';
	}
	unlink($_FILES['ext_file']['tmp_name']);
}
?>
<div class="container">
	<?php make_admin_menu(); ?>
	<div class="forum_content rightbox admin">
		<h2><?php echo translate('extensions'); ?></h2>		
		<?php
		if (ini_get('file_uploads')) {
			?>
			<h3><?php echo translate('installnewext'); ?></h3>
			<form action="<?php echo $base_config['baseurl']; ?>/admin/extensions" method="post" enctype="multipart/form-data">
				<p><input type="file" name="ext_file" accept="application/x-zip-compressed" /><br /><input type="submit" name="form_sent" value="<?php echo translate('install'); ?>" /></p>
			</form>
			<?php
		} else {
			?>
			<p><?php echo translate('nofileuploads'); ?></p>
			<?php
		}
		?>
	</div>
</div>