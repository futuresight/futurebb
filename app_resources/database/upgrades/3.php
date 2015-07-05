<?php
//upgrade from v1.2 to v1.3 (DB 1 -> 2)
//add new config values
set_config('date_format', 'd M Y');
set_config('time_format', 'H:i');
set_config('bbcode_privatemsg', 1);
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
//I hate mass queries as much as anyone, but they have to be done
foreach ($mappings as $oldtime => $newtime) {
	$db->query('UPDATE `#^users` SET timezone=' . $newtime . ' WHERE timezone=' . $oldtime) or enhanced_error('Failed to update timezone', true);
}
echo '<li>RV3: Converting timezones... success</li>';

ExtensionConfig::add_page('/styles', array('file' => 'style.php', 'template' => false, 'admin' => false, 'mod' => false));
echo '<li>RV3: Adding missing pages... success</li>';

ExtensionConfig::add_language_key('showallposts', 'Show all posts', 'English');
ExtensionConfig::add_language_key('timeformatdesc', 'The following two entries allow you to set the format used for displaying all times by the software. For items that only display the date, the date format is used, but for items that display the time, the date format and time format are joined together. The formats must follow the <a href="http://php.net/manual/en/function.date.php#refsect1-function.date-parameters">PHP guidelines</a>.', 'English');
ExtensionConfig::add_language_key('unknownerror', 'An unknown error occurred', 'English');
ExtensionConfig::add_language_key('specificnestingerror', 'The tag <b>[$1]</b> cannot be placed directly inside <b>[$2]</b>.', 'English');
ExtensionConfig::add_language_key('errorwaslocated', 'The above error was located at: ', 'English');
ExtensionConfig::add_language_key('tagwasopened', 'The <b>[$1]</b> tag was opened at the following location: ', 'English');
ExtensionConfig::add_language_key('archived', '(Archived)', 'English');
ExtensionConfig::add_language_key('dateformat', 'Date format', 'English');
ExtensionConfig::add_language_key('timeformat', 'Time format', 'English');
ExtensionConfig::add_language_key('basegroupon', 'Base new group on:', 'English');
ExtensionConfig::add_language_key('forumalreadyopen', 'You have modified a forum or already have one open for editing. Please refresh the page and then try again.', 'English');
ExtensionConfig::add_language_key('existingwords', 'Existing words', 'English');
ExtensionConfig::add_language_key('bbcodeinPM', 'BBCode in private messages', 'English');
ExtensionConfig::add_language_key('otherforumeditsconfirmrefresh', 'Edits to a forum have been submitted in another window. Do you want to refresh this page to reflect those changes?', 'English');
ExtensionConfig::add_language_key('archiveforum', 'Archive forum', 'English');
ExtensionConfig::add_language_key('changecategory', 'Change category', 'English');
ExtensionConfig::add_language_key('noextdir', 'The directory "app_config/extensions" does not exist or is not writable. Please create it and change the file permissions appropriately to fix this (if in doubt, chmod to 0777).', 'English');
echo '<li>RV3: Adding missing language keys... success</li>';

set_config('db_version', 3);