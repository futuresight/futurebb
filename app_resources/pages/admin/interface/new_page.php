<?php
if (isset($_POST['form_sent'])) {
	$q = new DBInsert('pages', array('url' => $_POST['url'], 'file' => $_POST['file'], 'template' => (isset($_POST['template']) ? 1 : 0), 'nocontentbox' => (isset($_POST['nocontentbox']) ? 1 : 0), 'admin' => (isset($_POST['admin']) ? 1 : 0), 'moderator' => (isset($_POST['moderator']) ? 1 : 0), 'subdirs' => (isset($_POST['subdirs']) ? 1 : 0)), 'Failed to insert new page');
	$q->commit();
	
	$q = new DBInsert('interface_history', array('action' => 'create', 'area' => 'pages', 'field' => $db->insert_id(), 'user' => $futurebb_user['id'], 'time' => time(), 'old_value' => ''), 'Failed to insert history entry');
	$q->commit();
	redirect($base_config['baseurl'] . '/admin/interface/pages');
}
?>
<h3>Add new page</h3>
<form action="<?php echo $base_config['baseurl']; ?>/admin/interface/pages/new" method="post" enctype="multipart/form-data">
	<table border="0" class="optionstable">
		<tr>
			<th>URL</th>
			<td><input type="text" name="url" size="40" /></td>
		</tr>
		<tr>
			<th>File</th>
			<td><input type="text" name="file" size="40" /></td>
		</tr>
		<tr>
			<th>Template</th>
			<td><input type="checkbox" name="template" id="template" /> <label for="template">Enable?</label></td>
		</tr>
		<tr>
			<th>No content box</th>
			<td><input type="checkbox" name="nocontentbox" id="nocontentbox" /> <label for="nocontentbox">Enable?</label></td>
		</tr>
		<tr>
			<th>Moderator-restricted</th>
			<td><input type="checkbox" name="moderator" id="moderator" /> <label for="moderator">Enable?</label></td>
		</tr>
		<tr>
			<th>Administrator-restricted</th>
			<td><input type="checkbox" name="admin" id="admin" /> <label for="admin">Enable?</label></td>
		</tr>
		<tr>
			<th>Subdirectories</th>
			<td><input type="checkbox" name="subdirs" id="subdirs" /> <label for="subdirs">Enable?</label></td>
		</tr>
	</table>
	<p><input type="submit" name="form_sent" value="Create" /></p>
</form>