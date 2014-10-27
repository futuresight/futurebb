<?php
//upgrade from v1.0 to v1.1 (DB 0 -> 1)
//add new config values
set_config('last_update_check', 0);
set_config('new_version', 0);
set_config('max_quote_depth', 4);
set_config('disable_registrations', 0);
set_config('db_version', 1);
echo '<li>RV1: Set new configuration values... success</li>';

//allow "special" reports
$new_fld = new DBField('post_type','enum(\'post\',\'msg\',\'special\')');
$new_fld->add_extra('NOT NULL');
$new_fld->set_default('\'post\'');
$db->alter_field('reports', $new_fld, '');
echo '<li>RV1: Updating reports table... success</li>';

//add avatar extension and rss token to users
$new_fld = new DBField('avatar_extension','VARCHAR(4)');
$new_fld->set_default('NULL');
$db->add_field('users', $new_fld, 'last_page_load');
$new_fld = new DBField('rss_token','VARCHAR(50)');
$new_fld->add_extra('NOT NULL');
$new_fld->set_default('\'\'');
$db->add_field('users', $new_fld, 'avatar_extension');

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