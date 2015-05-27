<?php
if (!$futurebb_user['g_admin_privs']) {
	httperror(403);
}
translate('<addfile>', 'admin');
$page_title = translate('style');
include FORUM_ROOT . '/app_resources/includes/admin.php';

if (isset($_POST['form_sent']) && isset($_FILES['extension_file'])) {
	switch (pathinfo($_FILES['extension_file']['name'], PATHINFO_EXTENSION)) {
		case 'css':
			//basic CSS stylesheet
			$fname = basename($_FILES['extension_file']['name']);
			if (file_exists(FORUM_ROOT . '/app_resources/pages/css/' . $fname)) {
				echo '<div class="forum_content"><p>' . translate('styleconflict') . '</p></div>'; return;
			}
			move_uploaded_file($_FILES['extension_file']['tmp_name'], FORUM_ROOT . '/app_resources/pages/css/' . $fname);
			if (!file_exists(FORUM_ROOT . '/app_resources/pages/css/' . $fname)) {
				echo '<div class="forum_content"><p>' . translate('uploadfailed') . '</p></div>'; return;
			}
			break;
		case 'zip':
			//full template set
			$fname = basename($_FILES['extension_file']['name'], '.zip');
			if (file_exists(FORUM_ROOT . '/app_config/templates/' . $fname)) {
				echo '<div class="forum_content"><p>' . translate('styleconflict') . '</p></div>'; return;
			}
			mkdir(FORUM_ROOT . '/app_config/templates/' . $fname);
			//unzip
			$zip = new ZipArchive();
			if ($zip->open($_FILES['extension_file']['tmp_name'])) {
				$zip->extractTo(FORUM_ROOT . '/app_config/templates/' . $fname);
				$zip->close();
				if (file_exists(FORUM_ROOT . '/app_config/templates/' . $fname . '/style.css')) {
					rename(FORUM_ROOT . '/app_config/templates/' . $fname . '/style.css', FORUM_ROOT . '/app_resources/pages/css/' . $fname . '.css');
				}
			} else {
				echo '<div class="forum_content"><p>' . translate('unzipfailed') . '<br /><a href="' . $base_config['baseurl'] . '/admin/style">' . translate('tryagain') . '</a></p></div>';
			}
			break;
		case 'png':
		case 'jpg':
		case 'jpeg':
		case 'gif':
			// upload new logo
			break;
		default:
			echo '<div class="forum_content"><p>' . translate('invalidfile') . '</p></div>'; return;
	}
}
if (isset($_GET['delete_css'])) {
	$fname = basename($_GET['delete_css']);
	if (file_exists(FORUM_ROOT . '/app_resources/pages/css/' . $fname . '.css')) {
		unlink(FORUM_ROOT . '/app_resources/pages/css/' . $fname . '.css');
	}
	if (file_exists(FORUM_ROOT . '/app_config/templates/' . $fname)) {
		//remove the directory and all contents
		$handle = opendir(FORUM_ROOT . '/app_config/templates/' . $fname);
		while ($file = readdir($handle)) {
			if ($file != '.' && $file != '..') {
				unlink(FORUM_ROOT . '/app_config/templates/' . $fname . '/' . $file);
			}
		}
		rmdir(FORUM_ROOT . '/app_config/templates/' . $fname);
	}
	header('Refresh: 0');
}
if (isset($_FILES['icon_file']) && is_uploaded_file($_FILES['icon_file']['tmp_name']) && pathinfo($_FILES['extension_file']['name'], PATHINFO_EXTENSION) == 'ico') {
	move_uploaded_file($_FILES['icon_file']['tmp_name'], FORUM_ROOT . '/static/favicon.ico');
}
?>
<div class="container">
	<?php make_admin_menu(); ?>
	<div class="forum_content rightbox admin">
		<h2><?php echo translate('appearanceandstyle'); ?></h2>
		<h3><?php echo translate('stylesets'); ?></h3>
		<ul><?php
		$handle = opendir(FORUM_ROOT . '/app_resources/pages/css');
		while ($f = readdir($handle)) {
			if (pathinfo($f, PATHINFO_EXTENSION) == 'css') {
				$name = htmlspecialchars(basename($f, '.css'));
				echo '<li>';
				if ($name != 'default')
					echo '<a href="' . $base_config['baseurl'] . '/admin/style?delete_css=' . $name . '" style="text-decoration:none">[X]</a> ';
				echo $name . '</li>';
			}
		}
		unset($handle);
		?></ul>
		
		<?php
		if (ini_get('file_uploads')) {
			?>
			<h3><?php echo translate('installnewcss'); ?></h3>
			<form action="<?php echo $base_config['baseurl']; ?>/admin/style" method="post" enctype="multipart/form-data">
				<p><?php echo translate('cssfile') ?> <input type="file" name="extension_file" /></p>
				<p><input type="submit" name="form_sent" value="<?php echo translate('install'); ?>" /></p>
			</form>
			<h3><?php echo translate('favicon'); ?></h3>
			<form action="<?php echo $base_config['baseurl']; ?>/admin/style" method="post" enctype="multipart/form-data">
				<p><?php echo translate('icofile'); ?> <input type="file" name="extension_file" accept="image/x-icon" /></p>
				<p><input type="submit" name="form_sent" value="<?php echo translate('replace'); ?>" /></p>
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