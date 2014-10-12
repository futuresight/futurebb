<?php
if ($futurebb_user['language'] != 'English') {
	$error = 'This extension only works in English. Please change your language.';
	return;
}
if (!ini_get('allow_url_fopen')) {
	$error = 'allow_url_fopen is not allowed in your PHP settings. This means that referencing the database at StopForumSpam.com will not work.';
	return;
}
ExtensionConfig::add_language_key('sfs', 'StopForumSpam', 'English');
ExtensionConfig::add_page('/admin/sfs', array('file' => 'admin/sfs.php', 'template' => true, 'nocontentbox' => true, 'admin' => true));
ExtensionConfig::add_admin_menu('sfs', 'sfs');
$q = new DBInsert('config', array('c_name' => 'sfs_max_score', 'c_value' => 10), 'Failed to insert new config entry: sfs_max_score');
$q->commit();

$q = new DBInsert('config', array('c_name' => 'sfs_check_values', 'c_value' => 'ip|email'), 'Failed to insert new config entry: sfs_check_values');
$q->commit();