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
$db->add_field('users', $field);
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

//insert new language keys
ExtensionConfig::add_language_key('exttoonew', 'The extension you are installing requires FutureBB version $1, while you are currently running $2. Go to <a href="http://futurebb.futuresight.org">the FutureBB website</a> to update your forum software.', 'English', 'admin');
ExtensionConfig::add_language_key('notextinsidetag', 'You are not allowed to place any text directly inside the <b>[$1]</b> tag.', 'English', 'main');
ExtensionConfig::add_language_key('posttime', 'Post time', 'English', 'main');
ExtensionConfig::add_language_key('tables', 'Tables', 'English', 'main');
ExtensionConfig::add_language_key('colrow', 'Col $1, Row $2', 'English', 'main');
ExtensionConfig::add_language_key('tableintro', 'You use the <code>[table][/table]</code> tags to start and end a table. You use <code>[tr][/tr]</code> to indicate a row, and <code>[td][/td]</code> to indicate a cell. The <code>[tr]</code> tag must go directly inside the <code>[table]</code> tag, and the <code>[td]</code> tag must go inside the <code>[tr]</code> tag.', 'English', 'main');
ExtensionConfig::add_language_key('relevance', 'Relevance', 'English', 'main');
ExtensionConfig::add_language_key('topicsp', 'topic<PLURAL $1>(,s)', 'main');
ExtensionConfig::add_language_key('postsp', 'post<PLURAL $1>(,s)', 'main');
ExtensionConfig::add_language_key('select', 'Select: ', 'main');
ExtensionConfig::add_language_key('stick', 'Stick', 'main');
ExtensionConfig::add_language_key('unstick', 'Unstick', 'main');
ExtensionConfig::add_language_key('close', 'Close', 'main');
ExtensionConfig::add_language_key('open', 'Open', 'main');
ExtensionConfig::add_language_key('confirm', 'Confirm', 'main');
ExtensionConfig::add_language_key('areyousureaction', 'Are you sure you want to $1 the following $2?', 'admin');
ExtensionConfig::add_language_key('signoutothersessions', 'Sign out all other sessions', 'profile');
ExtensionConfig::add_language_key('searchusername', 'Search username', 'admin');
ExtensionConfig::add_language_key('lastused', 'Last used', 'admin');
echo '<li>RV4: Adding new language keys... success</li>';

ExtensionConfig::remove_page('/login/');
ExtensionConfig::remove_page('/logout/');
ExtensionConfig::remove_page('/search/');
ExtensionConfig::remove_page('/admin/');
ExtensionConfig::remove_page('/messages/');
ExtensionConfig::remove_page('/online_list/');
echo '<li>RV4: Removing unnecessary pages... success</li>';

//alert the admin that the promotion operator has been changed from > to >=
$db->query('INSERT INTO `#^reports`(post_id,post_type,reason,reported_by,time_reported) VALUES(0, \'special\',\'' . $db->escape('For automatic user group promotion, the system now checks if the user\'s post count is greater than or equal to the number you enter, as opposed to strictly greater than.') . '\',0,' . time() . ')') or enhanced_error('Failed to alert admin about promotion operator change', true);

//welcome the admin to FutureBB 1.4
$db->query('INSERT INTO `#^reports`(post_id,post_type,reason,reported_by,time_reported) VALUES(0, \'special\',\'' . $db->escape('Welcome to FutureBB 1.4! Once you follow the steps explained in the other automatic notifications, your upgrade will be complete. We hope you enjoy it!') . '\',0,' . time() . ')') or enhanced_error('Failed to alert admin to rebuild search index', true);

set_config('db_version', 4);
set_config('new_version', 0);