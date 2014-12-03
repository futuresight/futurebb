<?php
/*
changes in other files:
*database
*pages.php
*admin_pages.php
*parser.php in error_check
*language keys: disablebbcode,addnew,tag,tagandhow,confirmdeleteblockedtag,disablebbcodeintro
*/
translate('<addfile>', 'admin');
$page_title = translate('disablebbcode');
include FORUM_ROOT . '/app_resources/includes/admin.php';
$user_groups = array();
$result = $db->query('SELECT g_id,g_name FROM `#^user_groups` WHERE g_guest_group=0 ORDER BY g_id ASC') or enhanced_error('Failed to get user group list', true);
while (list($id,$name) = $db->fetch_row($result)) {
	$user_groups[$id] = $name;
}
?>
<div class="container">
	<?php make_admin_menu(); ?>
	<div class="forum_content rightbox admin">
<?php
if (isset($dirs[3]) && $dirs[3] != '') {
	$id = intval($dirs[4]);
	$result = $db->query('SELECT tag,groups FROM `#^blockedtags` WHERE id=' . $id) or enhanced_error('Failed to find tag', true);
	if (!$db->num_rows($result)) {
		httperror(404);
	}
	list($tag,$group_str) = $db->fetch_row($result);
	switch ($dirs[3]) {
		case 'edit':
			if (isset($_POST['form_sent'])) {
				$groups = array();
				foreach ($_POST['groups'] as $gid => $val) {
					$groups[] = $gid;
				}
				$group_str = '-' . implode('-', $groups) . '-';
				$q = new DBUpdate('blockedtags', array('tag' => $_POST['tag'], 'groups' => $group_str), 'id=' . $id, 'Failed to update group');
				$q->commit();
				redirect($base_config['baseurl'] . '/admin/disable_bbcode');
			}
			?>
			<form action="<?php echo $base_config['baseurl']; ?>/admin/disable_bbcode/edit/<?php echo $id; ?>" method="post" enctype="multipart/form-data">
				<h2><?php echo translate('edit'); ?></h2>
				<table border="0">
					<tr>
						<td><?php echo translate('tag'); ?></td>
						<td><input type="text" name="tag" value="<?php echo htmlspecialchars($tag); ?>" /></td>
					</tr>
					<tr>
						<td><?php echo translate('usergroups'); ?></td>
						<td><?php
						$groups = explode('-', $group_str);
						$disp_groups = array();
						foreach ($user_groups as $gid => $group_name) {
							$text = '<input type="checkbox" name="groups[' . $gid . ']" value="1"';
							if (in_array($gid, $groups)) {
								$text .= ' checked="checked"';
							}
							$text .= ' id="groups_' . $gid . '" /> <label for="groups_' . $gid . '">' . htmlspecialchars($group_name) . '</label>';
							$disp_groups[] = $text;
						}
						echo implode('<br />', $disp_groups);
						?></td>
					</tr>
				</table>
				<p><input type="submit" name="form_sent" value="<?php echo translate('update'); ?>" /></p>
			</form>
			<?php
			break;
		case 'delete':
			if (isset($_POST['form_sent'])) {
				$q = new DBDelete('blockedtags', 'id=' . $id, 'Failed to delete blocked tag');
				$q->commit();
				redirect($base_config['baseurl'] . '/admin/disable_bbcode');
			}
			?>
			<form action="<?php echo $base_config['baseurl']; ?>/admin/disable_bbcode/delete/<?php echo $id; ?>" method="post" enctype="multipart/form-data">
				<h2><?php echo translate('delete'); ?></h2>
				<p><?php echo translate('confirmdeleteblockedtag'); ?></p>
				<table border="0">
					<tr>
						<td><?php echo translate('tag'); ?></td>
						<td><?php echo htmlspecialchars($tag); ?></td>
					</tr>
					<tr>
						<td><?php echo translate('usergroups'); ?></td>
						<td><?php $groups = explode('-', $group_str);
						$disp_groups = array();
						foreach ($groups as $gid) {
							if ($gid != '') {
								$disp_groups[] = htmlspecialchars($user_groups[$gid]);
							}
						}
						echo implode('<br />', $disp_groups);
						?></td>
					</tr>
				</table>
				<p><input type="submit" name="form_sent" value="<?php echo translate('delete'); ?>" /> <a href="<?php echo $base_config['baseurl']; ?>/admin/disable_bbcode">Cancel</a></a>
			</form>
			<?php
			break;
		default:
			httperror(404);
	}
} else {
	if (isset($_POST['form_sent'])) {
	} else if (isset($_POST['form_sent_add_new'])) {
		$groups = array();
		foreach ($_POST['groups'] as $id => $val) {
			$groups[] = $id;
		}
		$group_str = '-' . implode('-', $groups) . '-';
		$q = new DBInsert('blockedtags', array('tag' => $_POST['tag'], 'groups' => $group_str), 'Failed to insert new blocked tag entry');
		$q->commit();
		echo '</div></div>';
		header('Refresh: 0');
		return;
	} else {
		?>
				<h2><?php echo translate('disablebbcode'); ?></h2>
				<p><?php echo translate('disablebbcodeintro'); ?></p>
				<form action="<?php echo $base_config['baseurl']; ?>/admin/disable_bbcode" method="post" enctype="multipart/form-data">
					<p><?php echo translate('addnew'); ?></p>
					<table border="0">
						<tr>
							<td><?php echo translate('tagandhow'); ?></td>
							<td><input type="text" name="tag" /></td>
						</tr>
						<tr>
							<td><?php echo translate('usergroups'); ?></td>
							<td><ul style="list-style:none; padding:0; margin:0;">
							<?php foreach ($user_groups as $id => $name) {
								echo '<li><input type="checkbox" name="groups[' . $id . ']" value="1" id="groups_' . $id . '" /> <label for="groups_' . $id . '">' . htmlspecialchars($name) . '</label></li>';
							} ?></td>
						</tr>
					</table>
					<p><input type="submit" value="<?php echo translate('add'); ?>" name="form_sent_add_new" /></p>
				</form>
				<form action="<?php echo $base_config['baseurl']; ?>/admin/disable_bbcode" method="post" enctype="multipart/form-data">
					<?php
					$result = $db->query('SELECT id,tag,groups FROM `#^blockedtags` ORDER BY tag ASC') or enhanced_error('Failed to get tags', true);
					if ($db->num_rows($result)) {
						?>
						<table border="0">
							<tr>
								<th><?php echo translate('tag'); ?></th>
								<th><?php echo translate('usergroups'); ?></th>
								<th><?php echo translate('actions'); ?></th>
							</tr>
						<?php
						while (list($id,$tag,$group_str) = $db->fetch_row($result)) {
							$groups = explode('-', $group_str);
							$disp_groups = array();
							foreach ($groups as $gid) {
								if ($gid != '') {
									$disp_groups[] = htmlspecialchars($user_groups[$gid]);
								}
							}
							echo '<tr><td>' . htmlspecialchars($tag) . '</td><td>' . implode('<br />', $disp_groups) . '</td><td><a href="' . $base_config['baseurl'] . '/admin/disable_bbcode/edit/' . $id . '">' . translate('edit') . '</a> / <a href="' . $base_config['baseurl'] . '/admin/disable_bbcode/delete/' . $id . '">' . translate('delete') . '</a></td></tr>';
						}
						?>
						</table>
						<?php
					} else {
					}
					?>
				</form>
			
		<?php
	}
}
?>
	</div>
</div>