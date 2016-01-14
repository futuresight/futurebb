<?php
//upgrade from v1.3 to v1.4 (DB 3 -> 4)

//update search index structure
$db->drop_field('search_index', 'count');

$field = new DBField('locations', 'TEXT');
$field->add_extra('NOT NULL');
$field->set_default('\'\'');
$db->add_field('search_index', $field, 'word');
//notify admin that the search index needs to be rebuilt
$db->query('INSERT INTO `#^reports`(post_id,post_type,reason,reported_by,time_reported) VALUES(0, \'special\',\'' . $db->escape('To complete the upgrade to FutureBB 1.4, you need to rebuild the search index on the <a href="' . $base_config['baseurl'] . '/admin/maintenance">maintenance page</a>, as the search engine has been completely overhauled.') . '\',0,' . time() . ')') or enhanced_error('Failed to alert admin to rebuild search index', true);
echo '<li>RV4: Updating search table structure... success</li>';

$field = new DBField('login_hash','VARCHAR(50)');
$field->add_extra('NOT NULL');
$field->set_default('\'\'');
$db->add_field('users', $field, 'password');
echo '<li>RV4: Updating user table structure... success</li>';

$table = new DBTable('search_cache');
$new_fld = new DBField('id','INT');
$new_fld->add_key('PRIMARY');
$new_fld->add_extra('NOT NULL');
$new_fld->add_extra('AUTO_INCREMENT');
$table->add_field($new_fld);
$new_fld = new DBField('hash', 'VARCHAR(50)');
$new_fld->set_default('');
$new_fld->add_extra('NOT NULL');
$table->add_field($new_fld);
$new_fld = new DBField('results', 'TEXT');
$new_fld->set_default('');
$new_fld->add_extra('NOT NULL');
$table->add_field($new_fld);
$new_fld = new DBField('time','INT');
$new_fld->add_extra('NOT NULL');
$table->add_field($new_fld);
$table->commit();
echo '<li>RV4: Adding search cache table... success</li>';

$new_ip_fld = new DBField('ip','TEXT');
$new_ip_fld->add_extra('NOT NULL');
$new_ip_fld->set_default('\'\'');
$db->alter_field('bans', $new_ip_fld);
echo '<li>RV4: Updated Ip field</li>';
$db->drop_index('bans', 'username');
echo '<li>RV4: Updating bans table... success</li>';

//insert new language keys
$db->query('UPDATE `#^language` SET langkey=\'maxnumchars\' WHERE category=\'admin\' AND langkey=\'maxchars\'') or enhanced_error('Failed to change language keys', true);
$db->query('UPDATE `#^language` SET langkey=\'maxnumlines\' WHERE category=\'admin\' AND langkey=\'maxlines\'') or enhanced_error('Failed to change language keys', true);
ExtensionConfig::add_language_key('exttoonew', 'The extension you are installing requires FutureBB version $1, while you are currently running $2. Go to <a href="http://futurebb.futuresight.org">the FutureBB website</a> to update your forum software.', 'English', 'admin');
ExtensionConfig::add_language_key('notextinsidetag', 'You are not allowed to place any text directly inside the <b>[$1]</b> tag.', 'English', 'main');
ExtensionConfig::add_language_key('posttime', 'Post time', 'English', 'main');
ExtensionConfig::add_language_key('tables', 'Tables', 'English', 'main');
ExtensionConfig::add_language_key('colrow', 'Col $1, Row $2', 'English', 'main');
ExtensionConfig::add_language_key('tableintro', 'You use the <code>[table][/table]</code> tags to start and end a table. You use <code>[tr][/tr]</code> to indicate a row, and <code>[td][/td]</code> to indicate a cell. The <code>[tr]</code> tag must go directly inside the <code>[table]</code> tag, and the <code>[td]</code> tag must go inside the <code>[tr]</code> tag.', 'English', 'main');
ExtensionConfig::add_language_key('relevance', 'Relevance', 'English', 'main');
ExtensionConfig::add_language_key('topicsp', 'topic<PLURAL $1>(,s)','English',  'main');
ExtensionConfig::add_language_key('postsp', 'post<PLURAL $1>(,s)','English',  'main');
ExtensionConfig::add_language_key('select', 'Select: ', 'English', 'main');
ExtensionConfig::add_language_key('stick', 'Stick', 'English', 'main');
ExtensionConfig::add_language_key('unstick', 'Unstick', 'English', 'main');
ExtensionConfig::add_language_key('close', 'Close', 'English', 'main');
ExtensionConfig::add_language_key('open', 'Open', 'English', 'main');
ExtensionConfig::add_language_key('confirm', 'Confirm', 'English', 'main');
ExtensionConfig::add_language_key('areyousureaction', 'Are you sure you want to $1 the following $2?', 'English', 'admin');
ExtensionConfig::add_language_key('signoutothersessions', 'Sign out all other sessions', 'English', 'profile');
ExtensionConfig::add_language_key('searchusername', 'Search username', 'English', 'admin');
ExtensionConfig::add_language_key('lastused', 'Last used', 'English', 'admin');
ExtensionConfig::add_language_key('searchresultsfor', 'Search results for $1', 'English', 'main');
ExtensionConfig::add_language_key('atomfeed', 'Atom feed', 'English', 'main');
ExtensionConfig::add_language_key('tables', 'Tables', 'English', 'main');
ExtensionConfig::add_language_key('parentrequired', 'You must place the <b>[$1]</b> tag inside<PLURAL $3>(, one of) the following tag<PLURAL $3>(,s): $2', 'English', 'main');
ExtensionConfig::add_language_key('banuser', 'Ban user', 'English', 'admin');
ExtensionConfig::add_language_key('unbanuser', 'Unban user', 'English', 'admin');
ExtensionConfig::add_language_key('ban', 'Ban', 'English', 'admin');
ExensionConfig::add_language_key('modviewip', 'View IP addresses', 'English', 'admin');
ExensionConfig::add_language_key('modbanusers', 'Ban users', 'English', 'admin');
ExensionConfig::add_language_key('moddeleteposts', 'Delete other&apos; posts', 'English', 'admin');
ExensionConfig::add_language_key('modeditposts', 'Edit other&apos; posts', 'English', 'admin');
ExensionConfig::add_language_key('modviewipdesc', 'Allow the user to view IP addresses of users when they post and register, and also allow use of the IP Tracker.<br /><b>Note:</b> this requires the group also to have moderator privileges.', 'English', 'admin');
ExensionConfig::add_language_key('modbanusersdesc', 'Allow users to ban other users by username. Also allows banning by IP if the "View IP addresses" option is enabled.<br /><b>Note:</b> this requires the group also to have moderator privileges.', 'English', 'admin');
ExensionConfig::add_language_key('moddeletepostsdesc', 'Allow users of this group to delete all posts. Also grants access to the trash bin.<br /><b>Note:</b> this requires the group also to have moderator privileges.', 'English', 'admin');
ExensionConfig::add_language_key('modeditpostsdesc', 'Allow users of this group to edit all posts.<br /><b>Note:</b> this requires the group also to have moderator privileges.', 'English', 'admin');
echo '<li>RV4: Adding new language keys... success</li>';

//a few language keys changed
ExtensionConfig::remove_language_key('reportpostreason');
ExtensionConfig::add_language_key('reportpostreason', 'Please enter a short reason why you are <SWITCH $1>(reporting,appealing) this <SWITCH $2>(post,message,warning).', 'English', 'main');
ExtensionConfig::remove_language_key('noextdir');
ExtensionConfig::add_language_key('noextdir', 'The directory "app_config/extensions" does not exist or is not writable. Please create it and change the file permissions appropriately to fix this (if in doubt, chmod to 0777).', 'English', 'admin');
ExtensionConfig::remove_language_key('bademail');
ExtensionConfig::add_language_key('bademail', 'You entered an invalid email address.', 'English', 'register');
ExtensionConfig::remove_language_key('uploadfailed');
ExtensionConfig::add_language_key('uploadfailed', 'File upload failed. Please hit the back button and try again.', 'English', 'main');
ExtensionConfig::remove_language_key('maxchars');
ExtensionConfig::add_language_key('maxchars', 'Maximum characters: $1', 'English', 'main');
ExtensionConfig::remove_language_key('maxlines');
ExtensionConfig::add_language_key('maxlines', 'Maximum lines: $1', 'English', 'main');
ExtensionConfig::remove_language_key('searchip');
ExtensionConfig::add_language_key('searchip', 'Search IP', 'English', 'admin');
echo '<li>RV4: Changing updated language keys... success</li>';

ExtensionConfig::remove_page('/login/');
ExtensionConfig::remove_page('/logout/');
ExtensionConfig::remove_page('/search/');
ExtensionConfig::remove_page('/admin/');
ExtensionConfig::remove_page('/messages/');
ExtensionConfig::remove_page('/online_list/');
echo '<li>RV4: Removing unnecessary pages... success</li>';

//change moderator admin link to reports
load_db_config(true);
$xml = new SimpleXMLElement($futurebb_config['header_links']);
foreach ($xml->link as $link) {
	if ((string)$link->attributes()->path == 'admin/bans' && (string)$link->attributes()->perm == 'g_mod_privs ~g_admin_privs' && (string)$link == 'administration') {
		$link->attributes()->path = 'admin/reports';
	}
}
set_config('header_links', $xml->asXML());
echo '<li>RV4: Updating header links... success</li>';

//alert the admin that the promotion operator has been changed from > to >=
$db->query('INSERT INTO `#^reports`(post_id,post_type,reason,reported_by,time_reported) VALUES(0, \'special\',\'' . $db->escape('For automatic user group promotion, the system now checks if the user\'s post count is greater than or equal to the number you enter, as opposed to strictly greater than.') . '\',0,' . time() . ')') or enhanced_error('Failed to alert admin about promotion operator change', true);

//welcome the admin to FutureBB 1.4
$db->query('INSERT INTO `#^reports`(post_id,post_type,reason,reported_by,time_reported) VALUES(0, \'special\',\'' . $db->escape('Welcome to FutureBB 1.4! Once you follow the steps explained in the other automatic notifications, your upgrade will be complete. We hope you enjoy it!') . '\',0,' . time() . ')') or enhanced_error('Failed to alert admin to rebuild search index', true);

CacheEngine::CacheHeader();
CacheEngine::CacheLanguage();
CacheEngine::CacheAdminPages();
CacheEngine::CachePages();
CacheEngine::CacheCommonWords();
echo '<li>RV4: Clearing cache... success</li>';

set_config('db_version', 4);
set_config('new_version', 0);