<?php
//upgrade from v1.3 to v1.4 (DB 3 -> 4)

//update search index structure
$db->drop_field('search_index', 'count');

$field = new DBField('locations', 'TEXT');
$field->add_extra('NOT NULL');
$db->add_field('search_index', $field, 'word');
//notify admin that the search index needs to be rebuilt
$db->query('INSERT INTO `#^reports`(post_id,post_type,reason,reported_by,time_reported) VALUES(0, \'special\',\'' . $db->escape('To complete the upgrade to FutureBB 1.4, you need to rebuild the search index on the <a href=' . $base_config['baseurl'] . '/admin/maintenance">maintenance page</a>, as the search engine has been completely overhauled.') . '\',0,' . time() . ')') or enhanced_error('Failed to alert admin to rebuild search index', true);
echo '<li>RV4: Updating search table structure... success</li>';

//insert new language keys
ExtensionConfig::add_language_key('exttoonew', 'The extension you are installing requires FutureBB version $1, while you are currently running $2. Go to <a href="http://futurebb.futuresight.org">the FutureBB website</a> to update your forum software.', 'English', 'admin');
ExtensionConfig::add_language_key('notextinsidetag', 'You are not allowed to place any text directly inside the <b>[$1]</b> tag.', 'English', 'main');
ExtensionConfig::add_language_key('posttime', 'Post time', 'English', 'main');
ExtensionConfig::add_language_key('tables', 'Tables', 'English', 'main');
ExtensionConfig::add_language_key('colrow', 'Col $1, Row $2', 'English', 'main');
ExtensionConfig::add_language_key('tableintro', 'You use the <code>[table][/table]</code> tags to start and end a table. You use <code>[tr][/tr]</code> to indicate a row, and <code>[td][/td]</code> to indicate a cell. The <code>[tr]</code> tag must go directly inside the <code>[table]</code> tag, and the <code>[td]</code> tag must go inside the <code>[tr]</code> tag.', 'English', 'main');
echo '<li>RV4: Adding new language keys... success</li>';

//alert the admin that the promotion operator has been changed from > to >=
$db->query('INSERT INTO `#^reports`(post_id,post_type,reason,reported_by,time_reported) VALUES(0, \'special\',\'' . $db->escape('For automatic user group promotion, the system now checks if the user\'s post count is greater than or equal to the number you enter, as opposed to strictly greater than.') . '\',0,' . time() . ')') or enhanced_error('Failed to alert admin about promotion operator change', true);

//welcome the admin to FutureBB 1.4
$db->query('INSERT INTO `#^reports`(post_id,post_type,reason,reported_by,time_reported) VALUES(0, \'special\',\'' . $db->escape('Welcome to FutureBB 1.4! Once you follow the steps explained in the other automatic notifications, your upgrade will be complete. We hope you enjoy it!') . '\',0,' . time() . ')') or enhanced_error('Failed to alert admin to rebuild search index', true);

set_config('db_version', 4);