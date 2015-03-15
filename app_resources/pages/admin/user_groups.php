<?php
if (!$futurebb_user['g_admin_privs']) {
	httperror(403);
}
translate('<addfile>', 'admin');
$page_title = 'User groups';
include FORUM_ROOT . '/app_resources/includes/admin.php';
if (isset($_POST['form_sent_update'])) {
	$_POST['config']['g_user_list_groups'] = isset($_POST['user_list']) ? implode($_POST['user_list'], ',') : '';
	$group_id = intval($_POST['group_id']);
	$cfg_list = array(
		//format: 'name'		=> 'type'
		'g_name'				=> 'string',
		'g_title'				=> 'string',
	);
	if ($group_id != 1) { //stuff not for admins
		$cfg_list = array_merge($cfg_list, array(
			'g_user_list_groups'	=> 'string',
			'g_user_list'			=> 'bool',
			'g_view_forums'			=> 'bool',
			'g_access_board'		=> 'bool',
		));
	}
	if ($group_id != 1 && $group_id != 2) { //stuff not for admins or guests
		$cfg_list = array_merge($cfg_list, array(
			'g_promote_group'		=> 'int',
			'g_promote_posts'		=> 'int',
			'g_promote_operator'	=> 'int',
			'g_promote_days'		=> 'int',
			'g_post_flood'			=> 'int',
			'g_posts_per_hour'		=> 'int',
			'g_edit_posts'			=> 'bool',
			'g_delete_posts'		=> 'bool',
			'g_mod_privs'			=> 'bool',
			'g_admin_privs'			=> 'bool',
			'g_signature'			=> 'bool',
			'g_post_links'			=> 'bool',
			'g_post_images'			=> 'bool',
			'g_post_topics'			=> 'bool',
			'g_post_replies'		=> 'bool'
		));
	}
	$sql = '';
	if (isset($_POST['new_group'])) {
		$keys = implode(',', array_keys($cfg_list));
		foreach ($cfg_list as $name => $type) {
			switch ($type) {
				case 'bool':
					$sql .= ',' . (isset($_POST['config'][$name]) ? '1' : '0'); break;
				case 'string':
					$sql .= ',\'' . $db->escape($_POST['config'][$name]) . '\''; break;
				case 'int':
					$sql .= ',' . intval($_POST['config'][$name]) . ''; break;
			}
		}
		$sql = substr($sql, 1);
		$db->query('INSERT INTO `#^user_groups`(' . $keys . ') VALUES(' . $sql . ')') or enhanced_error('Failed to insert new group', true);
	} else {
		foreach ($cfg_list as $name => $type) {
			switch ($type) {
				case 'bool':
					$sql .= ',' . $name . '=' . (isset($_POST['config'][$name]) ? '1' : '0'); break;
				case 'string':
					$sql .= ',' . $name . '=\'' . $db->escape($_POST['config'][$name]) . '\''; break;
				case 'int':
					$sql .= ',' . $name . '=' . intval($_POST['config'][$name]) . ''; break;
			}
		}
		$sql = substr($sql, 1);
		$db->query('UPDATE `#^user_groups` SET ' . $sql . ' WHERE g_id=' . intval($_POST['group_id'])) or error('Failed to update group info', __FILE__, __LINE__, $db->error());
	}
}

$user_groups = array();
$group_info = array();
$result = $db->query('SELECT g_id,g_name,g_permanent FROM `#^user_groups`') or error('Failed to make default group menu', __FILE__, __LINE__, $db->error());
while (list($id,$name,$perm) = $db->fetch_row($result)) {
	$user_groups[$id] = $name;
	$group_info[$id] = array('permanent' => $perm);
}
?>
<div class="container">
	<?php make_admin_menu(); ?>
	<div class="forum_content rightbox admin">
		<?php
		if (!isset($dirs[3])) {
			$dirs[3] = '';
		}
		if ($dirs[3] == '') {
			if (isset($_POST['default_user_group'])) {
				set_config('default_user_group', $_POST['default_user_group']);
			}
			?>
		<h2><?php echo translate('usergroups'); ?></h2>
		<form action="<?php echo $base_config['baseurl']; ?>/admin/user_groups" method="post" enctype="multipart/form-data">
			<p><?php echo translate('defaultusergroup'); ?> <select name="default_user_group"><?php
			foreach ($user_groups as $id => $name) {
				echo '<option value="' . $id . '"';
				if ($id == $futurebb_config['default_user_group']) {
					echo ' selected="selected"';
				}
				echo '>' . htmlspecialchars($name) . '</option>';
			}
			?></select> <input type="submit" name="form_sent" value="<?php echo translate('update'); ?>" /></p>
		</form>
		<form action="<?php echo $base_config['baseurl']; ?>/admin/user_groups/new" method="get" enctype="multipart/form-data">
			<h4><?php echo translate('newusergroup'); ?></h4>
			<p><?php echo translate('basegroupon'); ?> <select name="baseon"><?php
			foreach ($user_groups as $id => $name) {
				echo '<option value="' . $id . '">' . htmlspecialchars($name) . '</option>';
			}
			?></select> <input type="submit" value="<?php echo translate('submit'); ?>" /></p>
		</form>
		<table border="0">
		<?php
		foreach ($user_groups as $id => $name) {
			?>
			<tr>
				<td><?php echo htmlspecialchars($name); ?></td>
				<td><a href="<?php echo $base_config['baseurl']; ?>/admin/user_groups/<?php echo $id; ?>/edit"><?php echo translate('edit'); ?></a></td>
				<td><?php if (!$group_info[$id]['permanent']) { ?><a href="<?php echo $base_config['baseurl']; ?>/admin/user_groups/<?php echo $id; ?>/delete"><?php echo translate('delete'); ?></a><?php } ?></td>
			</tr>
			<?php
		}
		?>
		</table>
		<?php } else if ($dirs[3] == 'new' || (isset($dirs[3]) && $dirs[3] == intval($dirs[3]) && isset($dirs[4]) && $dirs[4] == 'edit')) {
			$group_id = ($dirs[3] == 'new' ? intval($_GET['baseon']) : intval($dirs[3]));
			$result = $db->query('SELECT * FROM `#^user_groups` WHERE g_id=' . $group_id) or error('Failed to get group info', __FILE__, __LINE__, $db->error());
			if (!$db->num_rows($result)) {
				httperror(404);
			}
			$cur_group = $db->fetch_assoc($result);
			$visible_groups = array();
			foreach (explode(',', $cur_group['g_user_list_groups']) as $val) {
				$visible_groups[] = $val;
			}
		?>
		<form action="<?php echo $base_config['baseurl']; ?>/admin/user_groups" method="post" enctype="multipart/form-data">
			<?php
			if ($dirs[3] == 'new') {
				echo '<h3>' . translate('newusergroup') . '<input type="hidden" name="new_group" value="1" /></h3>';
			}
			?>
			<table border="0">
				<tr>
					<td><?php echo translate('groupname'); ?></td>
					<td><input type="text" name="config[g_name]" value="<?php echo $cur_group['g_name']; ?>" /><br /><?php echo translate('groupnamedesc'); ?></td>
				</tr>
				<tr>
					<td><?php echo translate('usertitle'); ?></td>
					<td><input type="text" name="config[g_title]" value="<?php echo $cur_group['g_title']; ?>" /><br /><?php echo translate('usertitledesc'); ?></td>
				</tr>
                <?php if ($group_id != 1) { //not for admins ?>
                <tr>
					<td><?php echo translate('accessboard'); ?></td>
					<td><input type="checkbox" name="config[g_access_board]" id="g_access_board" <?php if ($cur_group['g_access_board']) echo 'checked="checked" '; ?>/> <label for="g_access_board"><?php echo translate('enable?'); ?></label><br /><?php echo translate('accessboarddesc'); ?></td>
				</tr>
                <?php 
				}
				if ($group_id != 2 && $group_id != 1) { //hide for guests/admins ?>
				<tr>
					<td><?php echo translate('editposts'); ?></td>
					<td><input type="checkbox" name="config[g_edit_posts]" id="g_edit_posts" <?php if ($cur_group['g_edit_posts']) echo 'checked="checked" '; ?>/> <label for="g_edit_posts"><?php echo translate('enable?'); ?></label><br /><?php echo translate('editpostsdesc'); ?></td>
				</tr>
				<tr>
					<td><?php echo translate('deleteposts'); ?></td>
					<td><input type="checkbox" name="config[g_delete_posts]" id="g_delete_posts" <?php if ($cur_group['g_delete_posts']) echo 'checked="checked" '; ?>/> <label for="g_delete_posts"><?php echo translate('enable?'); ?></label><br /><?php echo translate('deletepostsdesc'); ?></td>
				</tr>
				<tr>
					<td><?php echo translate('modprivs'); ?></td>
					<td><input type="checkbox" name="config[g_mod_privs]" id="g_mod_privs" <?php if ($cur_group['g_mod_privs']) echo 'checked="checked" '; ?>/> <label for="g_mod_privs"><?php echo translate('enable?'); ?></label><br /><?php echo translate('modprivsdesc'); ?></td>
				</tr>
				<tr>
					<td><?php echo translate('adminprivs'); ?></td>
					<td><input type="checkbox" name="config[g_admin_privs]" id="g_admin_privs" <?php if ($cur_group['g_admin_privs']) echo 'checked="checked" '; ?>/> <label for="g_admin_privs"><?php echo translate('enable?'); ?></label><br /><?php echo translate('adminprivsdesc'); ?></td>
				</tr>
				<tr>
					<td><?php echo translate('allowsig'); ?></td>
					<td><input type="checkbox" name="config[g_signature]" id="g_signature" <?php if ($cur_group['g_signature']) echo 'checked="checked" '; ?>/> <label for="g_signature"><?php echo translate('enable?'); ?></label><br /><?php echo translate('allowsigdesc'); ?></td>
				</tr>
                <tr>
					<td><?php echo translate('posttopics'); ?></td>
					<td><input type="checkbox" name="config[g_post_topics]" id="g_post_topics" <?php if ($cur_group['g_post_topics']) echo 'checked="checked" '; ?>/> <label for="g_post_topics"><?php echo translate('enable?'); ?></label><br /><?php echo translate('posttopicsdesc'); ?></td>
				</tr>
                <tr>
					<td><?php echo translate('postreplies'); ?></td>
					<td><input type="checkbox" name="config[g_post_replies]" id="g_post_replies" <?php if ($cur_group['g_post_replies']) echo 'checked="checked" '; ?>/> <label for="g_post_replies"><?php echo translate('enable?'); ?></label><br /><?php echo translate('postrepliesdesc'); ?></td>
				</tr>
                <?php } ?>
                <?php if ($group_id != 1) { //hide for admins ?>
                <tr>
					<td><?php echo translate('viewforums'); ?></td>
					<td><input type="checkbox" name="config[g_view_forums]" id="g_view_forums" <?php if ($cur_group['g_view_forums']) echo 'checked="checked" '; ?>/> <label for="g_view_forums"><?php echo translate('enable?'); ?></label><br /><?php echo translate('viewforumsdesc'); ?></td>
				</tr>
				<tr>
					<td><?php echo translate('viewuserlist'); ?></td>
					<td><input type="checkbox" name="config[g_user_list]" id="g_user_list" <?php if ($cur_group['g_user_list']) echo 'checked="checked" '; ?>/> <label for="g_user_list"><?php echo translate('enable?'); ?></label><br /><?php echo translate('viewuserlistdesc'); ?></td>
				</tr>
                <?php } ?>
                 <?php if ($group_id != 2 && $group_id != 1) { //hide for guests/admins ?>
				<tr>
					<td><?php echo translate('postlinks'); ?></td>
					<td><input type="checkbox" name="config[g_post_links]" id="g_post_links" <?php if ($cur_group['g_post_links']) echo 'checked="checked" '; ?>/> <label for="g_post_links"><?php echo translate('enable?'); ?></label><br /><?php echo translate('postlinksdesc'); ?></td>
				</tr>
				<tr>
					<td><?php echo translate('postimgs'); ?></td>
					<td><input type="checkbox" name="config[g_post_images]" id="g_post_images" <?php if ($cur_group['g_post_images']) echo 'checked="checked" '; ?>/> <label for="g_post_images"><?php echo translate('enable?'); ?></label><br /><?php echo translate('postimgs'); ?></td>
				</tr>
				<tr>
					<td><?php echo translate('postflood'); ?></td>
					<td><input type="text" name="config[g_post_flood]" value="<?php echo $cur_group['g_post_flood']; ?>" size="5" /><br /><?php echo translate('postflooddesc'); ?></td>
				</tr>
				<tr>
					<td><?php echo translate('maxpostsperhour'); ?></td>
					<td><input type="text" name="config[g_posts_per_hour]" value="<?php echo $cur_group['g_posts_per_hour']; ?>" size="5" /><br /><?php echo translate('maxpostsperhourdesc'); ?></td>
				</tr>
                <?php } ?>
                <?php if ($group_id != 1) { //hide for admins ?>
				<tr>
					<td><?php echo translate('userlistvisgrps'); ?></td>
					<td>
					<?php
					foreach ($user_groups as $id => $name) {
						echo '<input type="checkbox" name="user_list[' . $id . ']" id="user_list_' . $id . '" value="' . $id . '"';
						if (in_array($id, $visible_groups)) {
							echo ' checked="checked"';
						}
						echo ' /> <label for="user_list_' . $id . '">' . htmlspecialchars($name) . '</label><br />';
					}
					?><br /><?php echo translate('userlistvisgrpsdesc'); ?>
					</td>
				</tr>
                <?php } ?>
			</table>
            <?php if ($group_id != 2 && $group_id != 1) { //hide for guests/admins ?>
			<p><?php echo translate('promoteto'); ?> <select name="config[g_promote_group]"><option value="0"><?php echo translate('dontpromote'); ?></option><?php
			foreach ($user_groups as $id => $name) {
				if ($id != intval($dirs[3])) {
					echo '<option value="' . $id . '"';
					if ($id == $cur_group['g_promote_group']) {
						echo ' selected="selected"';
					}
					echo '>' . htmlspecialchars($name) . '</option>';
				}
			}
			?></select> <?php echo strtolower(translate('after')); ?> <input type="text" name="config[g_promote_days]" value="<?php echo $cur_group['g_promote_days']; ?>" size="3" /> <?php echo strtolower(translate('days')); ?> <select name="config[g_promote_operator]"><option value="1"<?php if ($cur_group['g_promote_operator'] == 1) echo ' selected="selected"'; ?>><?php echo translate('and'); ?></option><option value="2"<?php if ($cur_group['g_promote_operator'] == 2) echo ' selected="selected"'; ?>><?php echo translate('or'); ?></option></select> <input type="text" name="config[g_promote_posts]" value="<?php echo $cur_group['g_promote_posts']; ?>" size="3" /> <?php echo strtolower(translate('posts')); ?>.</p>
            <?php } ?>
			<p><input type="hidden" name="group_id" value="<?php echo intval($dirs[3]); ?>" /><input type="submit" name="form_sent_update" value="<?php echo translate('save'); ?>" /></p>
		</form>
		<?php } else if ($dirs[3] == intval($dirs[3]) && $dirs[4] == 'delete') {
			if (isset($_POST['form_sent'])) {
				$db->query('UPDATE `#^users` SET group_id=' . intval($_POST['newgroup']) . ' WHERE group_id=' . intval($dirs[3])) or error('Failed to move users', __FILE__, __LINE__, $db->error());
				$db->query('DELETE FROM `#^user_groups` WHERE g_id=' . intval($dirs[3])) or error('Failed to delete group', __FILE__, __LINE__, $db->error());
				header('Location: ' . $base_config['baseurl'] . '/admin/user_groups');
			}
			$result = $db->query('SELECT g_name FROM `#^user_groups` WHERE g_id=' . intval($dirs[3]) . ' AND g_permanent=0') or error('Failed to get user groups', __FILE__, __LINE__, $db->error());
			if (!$db->num_rows($result)) {
				httperror(404);
			}
			list($name) = $db->fetch_row($result);
			?>
		<form action="<?php echo $base_config['baseurl']; ?>/admin/user_groups/<?php echo intval($dirs[3]); ?>/delete" method="post" enctype="multipart/form-data">
			<h2><?php echo translate('deleteusergroup'); ?></h2>
			<p><?php echo translate('deletegroupconfirm', htmlspecialchars($name)); ?></p>
			<?php
			echo '<p>' . translate('moveallusersto') . ' <select name="newgroup">';
			foreach ($user_groups as $id => $name) {
				if ($id != intval($dirs[3])) {
					echo '<option value="' . $name . '">' . htmlspecialchars($name) . '</option>';
				}
			}
			echo '</select></p>';
			?>
			<p><input type="submit" name="form_sent" value="<?php echo translate('delete'); ?>" /> &bull; <a href="<?php echo $base_config['baseurl']; ?>/admin/user_groups"><?php echo translate('jk'); ?></a></p>
		</form>
		<?php
		} else { 
			httperror(404);
		}
		?>
	</div>
</div>