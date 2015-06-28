<?php
//upgrade from v1.2 to v1.3 (DB 1 -> 2)
//add new config values
set_config('date_format', 'd M Y');
set_config('time_format', 'H:i');
echo '<li>RV3: Adding new config values... success</li>';

$db->drop_field('users', 'dst');
$new_timezone = new DBField('timezone', 'INT(3)');
$new_timezone->set_default(0);
$db->alter_field('users', $new_timezone);

$archived_fld = new DBField('archived', 'TINYINT(1)');
$archived_fld->add_extra('NOT NULL');
$archived_fld->set_default(0);
$db->add_field('forums', $archived_fld, 'num_posts');

set_config('db_version', 2);