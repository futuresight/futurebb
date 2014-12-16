<?php
$page_title = 'Edit page list';
$breadcrumbs = array(translate('administration') => 'admin', translate('interface') => 'admin/interface', 'URL Mapping' => 'admin/interface/pages');

$q = new DBSelect('pages', array('*'), '', 'Failed to get page list');
$result = $q->commit();
?>
<h3>URL Mapping</h3>
<p style="color:#C00; font-weight:bold">Warning: Use extreme caution on this page. Certain URL mappings are critical to proper operation of FutureBB. If you edit them, you run the risk of blocking all access to your forum.</p>
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