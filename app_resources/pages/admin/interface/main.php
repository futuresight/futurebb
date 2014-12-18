<?php
if (!$futurebb_user['g_admin_privs']) {
	httperror(404);
}
$page_title = 'Interface editing';
translate('<addfile>', 'admin');
include FORUM_ROOT . '/app_resources/includes/admin.php';
?>
<div class="container">
	<?php make_admin_menu(); ?>
	<div class="forum_content rightbox admin">
		<?php
		if (isset($dirs[3]) && $dirs[3] != '') {
			switch ($dirs[3]) {
				case 'header':
				case 'pages':
				case 'language':
				case 'admin_pages':
				case 'history':
					include FORUM_ROOT . '/app_resources/pages/admin/interface/' . $dirs[3] . '.php';
					break;
				default:
					httperror(404);
			}
		} else {
			?>
			<h3>Interface Editor</h3>
			<?php
			if ($futurebb_user['language'] != 'English') {
				echo '<p style="color:#A00; font-weight:bold">' . translate('notranslation') . '</p>';
			}
			?>
			<p>Welcome to the interface editor! You are able to edit the interface that determines how FutureBB looks and works from here.</p>
			<p style="color:#C00; font-weight:bold">WARNING: IMPROPERLY EDITING THE INTERFACE COULD CAUSE YOUR FORUM TO STOP WORKING.<br />PLEASE BACK UP ALL FILES AND DATABASES BEFORE YOU EDIT ANYTHING.<br />SERIOUSLY, BACK THEM UP. DON&apos;T JUST START MESSING WITH STUFF.</p>
			<p>Here are the parts you can edit:</p>
			<ul>
				<li><a href="<?php echo $base_config['baseurl']; ?>/admin/interface/header">Header links (index, profile, etc.)</a></li>
				<li><a href="<?php echo $base_config['baseurl']; ?>/admin/interface/pages">Page list (URL mapping)</a></li>
				<li><a href="<?php echo $base_config['baseurl']; ?>/admin/interface/language">Translation keys (nearly all text)</a></li>
				<li><a href="<?php echo $base_config['baseurl']; ?>/admin/interface/admin_pages">Administration sidebar links</a></li>
				<li><a href="<?php echo $base_config['baseurl']; ?>/admin/interface/history">Editing history</a></li>
			</ul>
			<?php
		}
		?>
	</div>
</div>