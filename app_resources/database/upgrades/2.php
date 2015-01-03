<?php
//upgrade from v1.1 to v1.2 (DB 0 -> 1)
//add new config values

$new_fld = new DBField('load_extra','TINYINT(1)');
$new_fld->add_extra('NOT NULL');
$new_fld->set_default('0');
$db->add_field('config', $new_fld, 'c_value');

set_config('header_links', '<?xml version="1.0" ?>
<linkset>
    <link path="">index</link>
    <link path="users/$username$" perm="valid">profile</link>
    <link path="users" perm="g_user_list">userlist</link>
    <link path="search">search</link>
    <link path="admin" perm="g_admin_privs">administration</link>
    <link path="admin/bans" perm="g_mod_privs ~g_admin_privs">administration</link>
    <link path="register/$reghash$" perm="~valid">register</link>
    <link path="logout" perm="valid">logout</link>
</linkset>');
set_config('admin_pages', 'PT5pbmRleApiYW5zPT5iYW5zCnJlcG9ydHM9PnJlcG9ydHMKY2Vuc29yaW5nPT5jZW5zb3JpbmcKZm9ydW1zPT5mb3J1bXMKaXBfdHJhY2tlcj0+aXB0cmFja2VyCnVzZXJfZ3JvdXBzPT51c2VyZ3JvdXBzCnRyYXNoX2Jpbj0+dHJhc2hiaW4KbWFpbnRlbmFuY2U9Pm1haW50ZW5hbmNlCnN0eWxlPT5zdHlsZQpleHRlbnNpb25zPT5leHRlbnNpb25zCmludGVyZmFjZT0+aW50ZXJmYWNl');
set_config('mod_pages', 'YmFucz0+YmFucwpyZXBvcnRzPT5yZXBvcnRzCnRyYXNoX2Jpbj0+dHJhc2hiaW4KaXBfdHJhY2tlcj0+aXB0cmFja2Vy');
set_config('db_version', 2);
$db->query('DELETE FROM `#^config` WHERE c_name=\'addl_header_links\'') or enhanced_error('Failed to remove old header links', true);
echo '<li>RV2: Adding new config values... success</li>';

$tables['language'] = new DBTable('language');
$new_fld = new DBField('id','INT');
$new_fld->add_key('PRIMARY');
$new_fld->add_extra('NOT NULL');
$new_fld->add_extra('AUTO_INCREMENT');
$tables['language']->add_field($new_fld);
$new_fld = new DBField('language','VARCHAR(20)');
$new_fld->add_extra('NOT NULL');
$new_fld->set_default('\'English\'');
$tables['language']->add_field($new_fld);
$new_fld = new DBField('langkey','VARCHAR(50)');
$new_fld->add_extra('NOT NULL');
$new_fld->set_default('\'\'');
$new_fld->set_default('\'\'');
$tables['language']->add_field($new_fld);
$new_fld = new DBField('value','TEXT');
$new_fld->add_extra('NOT NULL');
$new_fld->set_default('\'\'');
$new_fld->set_default('\'\'');
$tables['language']->add_field($new_fld);
$new_fld = new DBField('category','VARCHAR(15)');
$new_fld->add_extra('NOT NULL');
$new_fld->set_default('\'main\'');
$tables['language']->add_field($new_fld);
$tables['language']->commit();
echo '<li>RV2: Adding language table... success</li>';

$tables['pages'] = new DBTable('pages');
$new_fld = new DBField('id','INT');
$new_fld->add_key('PRIMARY');
$new_fld->add_extra('NOT NULL');
$new_fld->add_extra('AUTO_INCREMENT');
$tables['pages']->add_field($new_fld);
$new_fld = new DBField('url','TEXT');
$new_fld->add_extra('NOT NULL');
$new_fld->set_default('\'\'');
$new_fld->set_default('\'\'');
$tables['pages']->add_field($new_fld);
$new_fld = new DBField('file','TEXT');
$new_fld->add_extra('NOT NULL');
$new_fld->set_default('\'\'');
$new_fld->set_default('\'\'');
$tables['pages']->add_field($new_fld);
$new_fld = new DBField('template','TINYINT(1)');
$new_fld->add_extra('NOT NULL');
$tables['pages']->add_field($new_fld);
$new_fld = new DBField('nocontentbox','TINYINT(1)');
$new_fld->add_extra('NOT NULL');
$tables['pages']->add_field($new_fld);
$new_fld = new DBField('admin','TINYINT(1)');
$new_fld->add_extra('NOT NULL');
$tables['pages']->add_field($new_fld);
$new_fld = new DBField('moderator','TINYINT(1)');
$new_fld->add_extra('NOT NULL');
$tables['pages']->add_field($new_fld);
$new_fld = new DBField('subdirs','TINYINT(1)');
$new_fld->add_extra('NOT NULL');
$tables['pages']->add_field($new_fld);
$tables['pages']->commit();
echo '<li>RV2: Adding pages table... success</li>';

$tables['interface_history'] = new DBTable('interface_history');
$new_fld = new DBField('id','INT');
$new_fld->add_key('PRIMARY');
$new_fld->add_extra('NOT NULL');
$new_fld->add_extra('AUTO_INCREMENT');
$tables['interface_history']->add_field($new_fld);
$new_fld = new DBField('action','enum(\'edit\',\'create\',\'delete\')');
$new_fld->add_extra('NOT NULL');
$new_fld->set_default('\'edit\'');
$tables['interface_history']->add_field($new_fld);
$new_fld = new DBField('area','enum(\'language\',\'interface\',\'pages\')');
$new_fld->add_extra('NOT NULL');
$tables['interface_history']->add_field($new_fld);
$new_fld = new DBField('field','VARCHAR(50)');
$new_fld->add_extra('NOT NULL');
$new_fld->set_default('\'\'');
$new_fld->set_default('\'\'');
$tables['interface_history']->add_field($new_fld);
$new_fld = new DBField('user','INT');
$new_fld->add_extra('NOT NULL');
$tables['interface_history']->add_field($new_fld);
$new_fld = new DBField('time','INT');
$new_fld->add_extra('NOT NULL');
$tables['interface_history']->add_field($new_fld);
$new_fld = new DBField('old_value','TEXT');
$new_fld->add_extra('NOT NULL');
$new_fld->set_default('\'\'');
$new_fld->set_default('\'\'');
$tables['interface_history']->add_field($new_fld);
$tables['interface_history']->commit();
echo '<li>RV2: Adding interface history table... success</li>';

//run through stock cache to insert pages and language keys
include FORUM_ROOT . '/app_config/pages.php';
$q = 'INSERT INTO `#^pages`(url,file,template,nocontentbox,admin,moderator,subdirs) VALUES';
$page_insert_data = array();
foreach ($pages as $url => $info) {
	$page_insert_data[] = '(\'' . $db->escape($url) . '\',\'' . $db->escape($info['file']) . '\',' . ($info['template'] ? '1' : '0') . ',' . (isset($info['nocontentbox']) ? '1' : '0') . ',' . (isset($info['admin']) && $info['admin'] ? '1' : '0') . ',' . (isset($info['mod']) && $info['mod'] ? '1' : '0') . ',0)';
}
foreach ($pagessubdirs as $url => $info) {
	$page_insert_data[] = '(\'' . $db->escape($url) . '\',\'' . $db->escape($info['file']) . '\',' . ($info['template'] ? '1' : '0') . ',' . (isset($info['nocontentbox']) ? '1' : '0') . ',' . (isset($info['admin']) && $info['admin'] ? '1' : '0') . ',' . (isset($info['mod']) && $info['mod'] ? '1' : '0') . ',1)';
}
$db->query($q . implode(',', $page_insert_data)) or enhanced_error('Failed to insert page data', true);
unset($page_insert_data);
unset($pages);
unset($pagessubdirs);

//insert the language keys
$handle = opendir(FORUM_ROOT . '/app_config/langs');
while ($language = readdir($handle)) {
	if ($language != '.' && $language != '..') {
		$subhandle = opendir(FORUM_ROOT . '/app_config/langs/' . $language);
		while ($langfile = readdir($subhandle)) {
			if ($langfile != '.' && $langfile != '..') {
				include FORUM_ROOT . '/app_config/langs/' . $language . '/' . $langfile;
				if ($langfile != 'main.php') {
					$lang = $lang_addl;
					unset($lang_addl);
				}
				$q = 'INSERT INTO `#^language`(language,langkey,value,category) VALUES';
				$lang_insert_data = array();
				foreach ($lang as $key => $val) {
					$lang_insert_data[] = '(\'' . $db->escape($language) . '\',\'' . $db->escape($key) . '\',\'' . $db->escape($val) . '\',\'' . $db->escape(basename($langfile, '.php')) . '\')';
				}
				$db->query($q . implode(',', $lang_insert_data)) or enhanced_error('Failed to insert language stuff', true);
			}
		}
	}
}
unset($lang);