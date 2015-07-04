<?php
if (!$futurebb_user['g_admin_privs']) {
	httperror(403);
}
translate('<addfile>', 'admin');
$page_title = translate('extensions');
include FORUM_ROOT . '/app_resources/includes/admin.php';

function removetemp() {
	//remove temporary files
	global $ext_test_dir;
	rrmdir($ext_test_dir);
}
function rrmdir($dir) {
	//recursively remove directory
	$handle = opendir($dir);
	while ($file = readdir($handle)) {
		if ($file != '.' && $file != '..') {
			if (is_dir($dir . '/' . $file)) {
				rrmdir($dir . '/' . $file);
			} else {
				unlink($dir . '/' . $file);
			}
		}
	}
	rmdir($dir);
}

function process_changes($changes) {
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
						for ($i = 0; $i < sizeof($change['find']); $i++) {
							$lines[$i + $line_num] = '';
						}
						//make more room if necessary
						if (sizeof($change['find']) < sizeof($change['change'])) {
							array_splice($lines, $line_num, 0, array_fill(0, sizeof($change['change']) - sizeof($change['find']), ''));
						} else if (sizeof($change['find']) > sizeof($change['change'])) {
							for ($i = 0; $i < sizeof($change['find']) - sizeof($change['change']); $i++) {
								unset($lines[$line_num + $i]);
							}
						}
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

if (isset($_GET['uninstall'])) {
	//uninstall detected
	$ext_id = intval($_GET['uninstall']);
	$q = new DBSelect('extensions', array('name'), 'id=' . $ext_id, 'Failed to find extension');
	$result = $q->commit();
	if (!$db->num_rows($result)) {
		httperror(404);
	}
	$ext_info = $db->fetch_assoc($result);
	if (isset($_POST['cancel'])) {
		redirect($base_config['baseurl'] . '/admin/extensions');
	}
	if (isset($_POST['form_sent'])) {
		//uninstall
		if (!file_exists(FORUM_ROOT . '/app_config/extensions/' . $ext_id . '/uninstall.php')) {
			echo '<p>' . translate('nouninstallphp') . '</p>';
			return;
		}
		if (!writable(FORUM_ROOT)) {
			echo '<p>' . translate('forumnotwritable') . '</p>';
			return;
		}
		include FORUM_ROOT . '/app_config/extensions/' . $ext_id . '/uninstall.php';
		if (isset($changes)) {
			process_changes($changes);
		}
		if (isset($files_to_delete)) {
			foreach ($files_to_delete as $file) {
				unlink(FORUM_ROOT . '/' . $file);
			}
		}
		if (isset($dirs_to_delete)) {
			foreach ($dirs_to_delete as $dir) {
				rrmdir(FORUM_ROOT . '/' . $dir);
			}
		}
		rrmdir(FORUM_ROOT . '/app_config/extensions/' . $ext_id);
		$q = new DBDelete('extensions', 'id=' . $ext_id, 'Failed to remove extension info from database');
		$q->commit();
		?>
    <div class="container">
		<?php make_admin_menu(); ?>
		<div class="forum_content rightbox admin">
			<h2><?php echo translate('success'); ?></h2>
            <p><?php echo translate('uninstalled'); ?><br /><a href="<?php echo $base_config['baseurl']; ?>/admin/extensions"><?php echo translate('return'); ?></a></p>
		</div>
	</div>
    	<?php
	} else {
		//warn the user before uninstalling
		?>
	<div class="container">
		<?php make_admin_menu(); ?>
		<div class="forum_content rightbox admin">
			<h2><?php echo translate('uninstallext'); ?></h2>
			<p><?php echo translate('uninstallextintro', $ext_info['name']); ?></p>
			<form action="?uninstall=<?php echo $ext_id; ?>" method="post" enctype="multipart/form-data">
            	<p><input type="submit" name="form_sent" value="<?php echo translate('yes'); ?>" /> <input type="submit" name="cancel" value="<?php echo translate('no'); ?>" /></p>
			</form>
		</div>
	</div>
		<?php
	}
	return;
}

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
	@mkdir($ext_test_dir);
	if (!file_exists($ext_test_dir)) {
		echo '<div class="forum_content"><p>' . translate('tempdirfailed') . '</p></div>';
		return;
	}
	$zip = new ZipArchive();
	if ($zip->open($_FILES['ext_file']['tmp_name'])) {
		$zip->extractTo($ext_test_dir);
		$zip->close();
		if (!file_exists($ext_test_dir . '/info.php')) {
			echo '<div class="forum_content"><p>' . translate('noinfophp') . '<br /><a href="' . $base_config['baseurl'] . '/admin/extensions">' . translate('tryagain') . '</a></p></div>';
			removetemp();
			return;
		}
		include $ext_test_dir . '/info.php';
		if (!isset($ext_info) || !is_array($ext_info)) {
			echo '<div class="forum_content"><p>' . translate('badextinfo') . '</p></div>';
			removetemp();
			return;
		}
		$ext_info_req = array('title', 'uninstallable');
		foreach ($ext_info_req as $val) {
			if (!isset($ext_info[$val])) {
				echo '<div class="forum_content"><p>' . translate('extinfomissingkey', $val) . '</b><br /><a href="' . $base_config['baseurl'] . '/admin/extensions">' . translate('tryagain') . '</a></p></div>';
				removetemp();
				return;
			}
		}
		//before doing anything, check that everything is writable
		if (!writable(FORUM_ROOT)) {
			echo '<div class="forum_content"><p>' . translate('forumnotwritable') . '</p></div>';
			removetemp();
			return;
		}
		if (file_exists($ext_test_dir . '/files') && is_dir($ext_test_dir . '/files')) {
			function list_files($dir, $first = true) {
				static $files;
				if ($first) {
					$files = array();
				}
				$handle = opendir($dir);
				while ($file = readdir($handle)) {
					if ($file != '.' && $file != '..') {
						$files[] = $dir . '/' . $file;
						if (is_dir($dir . '/' . $file)) {
							list_files($dir . '/' . $file, false);
						}
					}
				}
				if ($first) {
					return $files;
				}
			}
			$files = list_files($ext_test_dir . '/files');
			foreach ($files as $file) {
				if (!writable($file)) {
					echo '<div class="forum_content"><p>' . translate('filenotwritable', $file) . '</p></div>';
					removetemp();
					return;
				}
			}
		}
		if (file_exists($ext_test_dir . '/changes.php')) {
			include $ext_test_dir . '/changes.php';
			if (!isset($changes)) {
				echo '<li>' . translate('nochangesvar') . '</li></ul></div>';
				removetemp();
				return;
			}
			foreach ($changes as $change) {
				if (!is_writable($change['file'])) {
					echo '<div class="forum_content"><p>' . translate('filenotwritable', $change['file']) . '</p></div>';
					removetemp();
					return;
				}
			}
		}
		
		echo '<div class="forum_content"><h2>' . translate('extinstallation') . '</h2><h3>' . $ext_info['title'] . '</h3><ul>';
		if (file_exists($ext_test_dir . '/database.php')) {
			include $ext_test_dir . '/database.php';
			echo '<li>' . translate('makingdbchanges') . '</li>';
		}
		if (isset($error)) {
			echo '<li>' . translate('Error') . ': ' . $error . '</li></ul></div>';
			removetemp();
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
				removetemp();
				return;
			}
			process_changes($changes);
		}
		//store extension info in database
		if (!isset($ext_info['website'])) {
			$ext_info['website'] = null;
		}
		if (!isset($ext_info['support'])) {
			$ext_info['support'] = null;
		}
		if (!isset($ext_info['nolog'])) {
			$q = new DBInsert('extensions', array('name' => $ext_info['title'], 'website' => $ext_info['website'], 'support_url' => $ext_info['support'], 'uninstallable' => (string)$ext_info['uninstallable']), 'Failed to store extension info in DB');
			$q->commit();
		}
		
		$ext_id = $db->insert_id();
		
		//store uninstaller
		if (file_exists($ext_test_dir . '/uninstall.php') && $ext_info['uninstallable']) {
			mkdir(FORUM_ROOT . '/app_config/extensions/' . $ext_id);
			rename($ext_test_dir . '/uninstall.php', FORUM_ROOT . '/app_config/extensions/' . $ext_id . '/uninstall.php');
			if (!file_exists(FORUM_ROOT . '/app_config/extensions/' . $ext_id . '/uninstall.php')) {
				error('Failed to copy uninstall file. Please check file permissions in app_config directory.');
			}
		}
		
		//finish up
		echo '<li>' . translate('installcomplete') . '</li>';
		echo '</ul></div>';
		removetemp();
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
            <?php
			$writable = true;
			if (!writable(FORUM_ROOT . '/temp')) {
				$writable = false;
				echo '<p style="color:#A00; font-weight:bold">' . translate('forumnotwritable') . '</p>';
			}
			?>
			<form action="<?php echo $base_config['baseurl']; ?>/admin/extensions" method="post" enctype="multipart/form-data">
				<p><input type="file" name="ext_file" accept="application/x-zip-compressed" /><br /><input type="submit" name="form_sent" value="<?php echo translate('install'); ?>"<?php if (!$writable) echo ' disabled="disabled"'; ?> /></p>
			</form>
			<?php
		} else {
			?>
			<p><?php echo translate('nofileuploads'); ?></p>
			<?php
		}
		?>
        <h3><?php echo translate('existingexts'); ?></h3>
        <?php
		$q = new DBSelect('extensions', array('id', 'name', 'website', 'support_url', 'uninstallable'), '1', 'Failed to get installed extensions');
		$result = $q->commit();
		if (!$db->num_rows($result)) {
			echo '<p>' . translate('noexts') . '</p>';
		} else {
			?>
            <table border="0">
            	<tr>
                	<th><?php echo translate('name'); ?></th>
                    <th><?php echo translate('website'); ?></th>
                    <th><?php echo translate('supporturl'); ?></th>
                    <th><?php echo translate('uninstall'); ?></th>
                </tr>
                <?php
				while ($ext_info = $db->fetch_assoc($result)) {
					echo '<tr><td>' . htmlspecialchars($ext_info['name']) . '</td><td>';
					if ($ext_info['website'] != null) {
						echo '<a href="' . htmlspecialchars($ext_info['website']) . '">' .  htmlspecialchars($ext_info['website']) . '</a>';
					}
					echo '</td><td>';
					if ($ext_info['support_url'] != null) {
						echo '<a href="' . htmlspecialchars($ext_info['support_url']) . '">' . htmlspecialchars($ext_info['support_url']) . '</a>';
					}
					echo '</td><td>';
					if ($ext_info['uninstallable']) {
						echo '<a href="?uninstall=' . $ext_info['id'] . '">' . translate('uninstall') . '</a>';
					} else {
						echo translate('unavailable');
					}
					echo '</td></tr>' . "\n";
				}
				?>
            </table>
            <?php
		}
		?>
	</div>
</div>