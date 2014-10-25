<?php
$page_title = 'User list';
if (!$futurebb_user['g_user_list']) {
	httperror(404);
}
$visible_groups = array();
if ($futurebb_user['g_user_list_groups'] != '') {
	foreach (explode(',', $futurebb_user['g_user_list_groups']) as $val) {
		$visible_groups[] = intval($val);
	}
} else {
}
$visible_groups = implode(',', $visible_groups);
$per_page = 25;
?>
<div class="forum_content">
	<form action="<?php echo $base_config['baseurl']; ?>/users/" method="get">
		<table border="0" style="width: 100%;">
			<tr>
				<td><?php echo translate('username'); ?>: <input type="text" name="username" value="<?php if (isset($_GET['username'])) echo htmlspecialchars($_GET['username']); ?>" /></td>
				<td><?php echo translate('usergroup'); ?>: <select name="group"><option value="0">Any</option><?php
				$q = new DBSelect('user_groups', array('g_id,g_name'), ($visible_groups == '' ? '' : 'g_id IN(' . $visible_groups . ')'), 'Failed to form user group list');
				$q->set_order('g_name ASC');
				$result = $q->commit();
				unset($q);
				while (list($id,$name) = $db->fetch_row($result)) {
					echo '<option value="' . $id . '"';
					if (isset($_GET['group']) && $_GET['group'] == $id) {
						echo ' selected="selected"';
					}
					echo '>' . htmlspecialchars($name) . '</option>';
				}
				?></select></td>
				<td><?php echo translate('sortby'); ?> <select name="sort"><option value="username"<?php if (isset($_GET['sort']) && $_GET['sort'] == 'username') echo ' selected="selected"'; ?>><?php echo translate('username'); ?></option><option value="num_posts"<?php if (isset($_GET['sort']) && $_GET['sort'] == 'num_posts') echo ' selected="selected"'; ?>><?php echo translate('numposts'); ?></option><option value="registered"<?php if (isset($_GET['sort']) && $_GET['sort'] == 'registered') echo ' selected="selected"'; ?>><?php echo translate('dateregistered'); ?></option></select> <select name="order"><option value="asc"><?php echo translate('ascending'); ?></option><option value="desc"<?php if (isset($_GET['order']) && $_GET['order'] == 'desc') echo ' selected="selected"'; ?>><?php echo translate('descending'); ?></option></select></td>
				<td><input type="submit" value="<?php echo translate('search'); ?>" /></td>
			</tr>
		</table>
	</form>
</div>

<?php
$sql = '';
if (isset($_GET['username']) && $_GET['username'] != '') {
	$sql .= ' AND u.username LIKE \'' . $db->escape(str_replace('*', '%', $_GET['username'])) . '\'';
}
if (isset($_GET['group']) && $_GET['group'] != '0') {
	$sql .= ' AND u.group_id=' . intval($_GET['group']);
}
$q = new DBSelect('users', array('u.username','u.num_posts','u.registered','g.g_title AS title'), 'u.id>0 AND u.username<>\'Guest\' ' . $sql . ' ' . ($visible_groups == '' ? '' : 'AND u.group_id IN(' . $visible_groups . ')') . ' AND u.deleted=0 AND u.username<>\'Guest\'', 'Failed to get users');
$q->table_as('u');
$join = new DBLeftJoin('user_groups', 'g', 'g.g_id=u.group_id');
$q->add_join($join);
$result = $q->commit();
unset($q);
$num_users = $db->num_rows($result);
$page = isset($_GET['p']) ? intval($_GET['p']) : 1;
$linktext = '<a href="' . $base_config['baseurl'] . '/users?p=$page$';
if (isset($_GET['username'])) $linktext .= htmlspecialchars('&username=' . $_GET['username']);
if (isset($_GET['group'])) $linktext .= htmlspecialchars('&group=' . $_GET['group']);
if (isset($_GET['sort'])) $linktext .= htmlspecialchars('&sort=' . $_GET['sort']);
if (isset($_GET['order'])) $linktext .= htmlspecialchars('&order=' . $_GET['order']);
$linktext .= '"$bold$>$page$</a> ';
?>
<p><?php echo translate('pages');
echo paginate($linktext, $page, ceil($num_users / $per_page));
?></p>

<div class="forum_content">
	<table border="0" style="width: 100%;">
		<tr>
			<th style="text-align: left;"><?php echo translate('username'); ?></th>
			<th style="text-align: left;"><?php echo translate('title'); ?></th>
			<th style="text-align: left;"><?php echo translate('numposts'); ?></th>
			<th style="text-align: left;"><?php echo translate('registered'); ?></th>
		</tr>
		<?php
		if (isset($_GET['sort'])) {
			switch ($_GET['sort']) {
				case 'username':
					$order = 'u.username'; break;
				case 'num_posts':
					$order = 'u.num_posts'; break;
				case 'registered':
					$order = 'u.registered'; break;
				default:
					$order = 'u.username'; break;
			}
			if (isset($_GET['order']) && $_GET['order'] == 'desc') {
				$order .= ' DESC';
			} else {
				$order .= ' ASC';
			}
		} else {
			$order = 'u.username ASC';
		}
		$q = new DBSelect('users', array('u.username','u.num_posts','u.registered','g.g_title AS title'), ' u.id>0 AND u.username<>\'Guest\' ' . $sql . ($visible_groups == '' ? '' : ' AND u.group_id IN(' . $visible_groups . ')') . ' AND u.deleted=0', 'Failed to get users');
		$q->table_as('u');
		$join = new DBLeftJoin('user_groups', 'g', 'g.g_id=u.group_id');
		$q->add_join($join);
		$q->set_limit((($page - 1) * $per_page) . ',' . $per_page);
		$q->set_order($order);
		$result = $q->commit();
		unset($q);
		
		while ($cur_user = $db->fetch_assoc($result)) {
			echo '<tr>
				<td><a href="' . $base_config['baseurl'] . '/users/' . rawurlencode(htmlspecialchars($cur_user['username'])) . '">' . htmlspecialchars($cur_user['username']) . '</a></td>
				<td>' . htmlspecialchars($cur_user['title']) . '</td>
				<td>' . $cur_user['num_posts'] . '</td>
				<td>' . user_date($cur_user['registered']) . '</td>
			</tr>';
		}
		?>
	</table>
</div>
<p><?php echo translate('pages');
	echo paginate($linktext, $page, ceil($num_users / $per_page));
	?></p>