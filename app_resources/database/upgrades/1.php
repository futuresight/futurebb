<?php
//upgrade from v1.0 to v1.1 (DB 0 -> 1)
//since v1.0 only supported MySQL, SQLite compatibility is not necessary in this version
//add new config values
set_config('last_update_check', 0);
set_config('new_version', 0);
set_config('max_quote_depth', 4);
set_config('disable_registrations', 0);
set_config('db_version', 1);
set_config('enable_bbcode', 1);
set_config('enable_smilies', 1);
set_config('avatar_max_filesize', 1024);
set_config('avatar_max_width', 64);
set_config('avatar_max_height', 64);
echo '<li>RV1: Set new configuration values... success</li>';

//allow "special" reports
$new_fld = new DBField('post_type','enum(\'post\',\'msg\',\'special\')');
$new_fld->add_extra('NOT NULL');
$new_fld->set_default('\'post\'');
$db->alter_field('reports', $new_fld, '');
echo '<li>RV1: Updating reports table... success</li>';

//add avatar extension, DST, and rss token to users
$new_fld = new DBField('avatar_extension','VARCHAR(4)');
$new_fld->set_default('NULL');
$db->add_field('users', $new_fld, 'last_page_load');
$new_fld = new DBField('rss_token','VARCHAR(50)');
$new_fld->add_extra('NOT NULL');
$new_fld->set_default('\'\'');
$db->add_field('users', $new_fld, 'avatar_extension');
$new_fld = new DBField('dst', 'TINYINT(1)');
$new_fld->add_extra('NOT NULL');
$new_fld->set_default('0');
$db->add_field('users', $new_fld, 'timezone');

//add new user group privileges
$new_fld = new DBField('g_access_board','TINYINT(1)');
$new_fld->add_extra('NOT NULL');
$new_fld->set_default('1');
$db->add_field('user_groups', $new_fld, 'g_post_images');
$new_fld = new DBField('g_view_forums','TINYINT(1)');
$new_fld->add_extra('NOT NULL');
$new_fld->set_default('1');
$db->add_field('user_groups', $new_fld, 'g_access_board');
$new_fld = new DBField('g_post_topics','TINYINT(1)');
$new_fld->add_extra('NOT NULL');
$new_fld->set_default('1');
$db->add_field('user_groups', $new_fld, 'g_view_forums');
$new_fld = new DBField('g_post_replies','TINYINT(1)');
$new_fld->add_extra('NOT NULL');
$new_fld->set_default('1');
$db->add_field('user_groups', $new_fld, 'g_post_topics');
echo '<li>RV1: Adding new user group privileges... success</li>';

//add extensions table
$tables = array();
$tables['extensions'] = new DBTable('extensions');
$new_fld = new DBField('id','INT');
$new_fld->add_key('PRIMARY');
$new_fld->add_extra('NOT NULL');
$new_fld->add_extra('AUTO_INCREMENT');
$tables['extensions']->add_field($new_fld);
$new_fld = new DBField('name','VARCHAR(50)');
$new_fld->add_extra('NOT NULL');
$new_fld->set_default('\'\'');
$new_fld->set_default('\'\'');
$tables['extensions']->add_field($new_fld);
$new_fld = new DBField('website','TEXT');
$new_fld->set_default('\'\'');
$new_fld->set_default('\'\'');
$tables['extensions']->add_field($new_fld);
$new_fld = new DBField('support_url','TEXT');
$new_fld->set_default('\'\'');
$new_fld->set_default('\'\'');
$tables['extensions']->add_field($new_fld);
$new_fld = new DBField('uninstallable','TINYINT(1)');
$new_fld->add_extra('NOT NULL');
$new_fld->set_default(0);
$tables['extensions']->add_field($new_fld);
$tables['extensions']->commit();

//RSS tokens
$db->query('UPDATE `#^users` SET rss_token=md5(id+RAND())') or error('Failed to update rss tokens', __FILE__, __LINE__, $db->error());
echo '<li>RV1: Giving RSS tokens... success</li>';