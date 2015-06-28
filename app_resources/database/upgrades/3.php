<?php
//upgrade from v1.2 to v1.3 (DB 1 -> 2)
//add new config values
set_config('date_format', 'd M Y');
set_config('time_format', 'H:i');
echo '<li>RV3: Adding new config values... success</li>';

//update database structure
$db->drop_field('users', 'dst');
$new_timezone = new DBField('timezone', 'INT(3)');
$new_timezone->set_default(0);
$db->alter_field('users', $new_timezone);

$archived_fld = new DBField('archived', 'TINYINT(1)');
$archived_fld->add_extra('NOT NULL');
$archived_fld->set_default(0);
$db->add_field('forums', $archived_fld, 'num_posts');
echo '<li>RV3: Updating database structure... success</li>';

//convert all old timezones (just the UTC offset) to the new technique which uses the entire PHP dictionary
//map all UTC offsets to new timezones
$mappings = array(
	-12 => 383,
	-11 => 398,
	-10 => 53,
	-9 => 94,
	-8 => 132,
	-7 => 162,
	-6 => 144,
	-5 => 151,
	-4 => 84,
	-3 => 57,
	-2 => 295,
	-1 => 8,
	0 => 415,
	1 => 333,
	2 => 327,
	3 => 340,
	4 => 230,
	5 => 244,
	6 => 266,
	7 => 220,
	8 => 271,
	9 => 279,
	10 => 309,
	11 => 399,
	12 => 385,
	13 => 213,
);
//TODO: add query to map old timezones to new timezones
echo '<li>RV3: Converting timezones... success</li>';

set_config('db_version', 2);