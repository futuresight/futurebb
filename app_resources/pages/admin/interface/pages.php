<?php
$page_title = 'Edit page list';
$breadcrumbs = array(translate('administration') => 'admin', translate('interface') => 'admin/interface', 'Page list' => 'admin/interface/pages');

$q = new DBSelect('pages', array('*'), '', 'Failed to get page list');
$result = $q->commit();
?>
<table border="0">
	<tr>
		<th>URL</th>
		<th>File</th>
		<th>Template</th>
		<th>No content box</th>
		<th>Moderators</th>
		<th>Administrators</th>
		<th>Subdirectories</th>
	</tr>
<?php
while ($page = $db->fetch_assoc($result)) {
	echo '<tr><td><input type="text" value="' . htmlspecialchars($page['url']) . '" size="25" /></td><td><input type="text" value="' . htmlspecialchars($page['file']) . '" size="27" /></td><td><input type="checkbox"' . ($page['template'] ? ' checked="checked"' : '') . ' /></td><td><input type="checkbox"' . ($page['nocontentbox'] ? ' checked="checked"' : '') . ' /></td><td><input type="checkbox"' . ($page['moderator'] ? ' checked="checked"' : '') . ' /></td><td><input type="checkbox"' . ($page['admin'] ? ' checked="checked"' : '') . ' /></td><td><input type="checkbox"' . ($page['subdirs'] ? ' checked="checked"' : '') . ' /></td></tr>' . "\n";
}
?>
</table>