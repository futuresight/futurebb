<?php
$q = 'INSERT INTO `#^language`(language,langkey,value,category) VALUES';
$lang_insert_data = array(
	array('English', $db->escape('noextdir'), $db->escape('The directory "app_config/extensions" does not exist or is not writable. Please create it and change the file permissions appropriately to fix this (if in doubt, chmod to 0777).'), 'admin'),
	array('English', $db->escape('notextinsidetag'), $db->escape('You are not allowed to place any text directly inside the <b>[$1]</b> tag.'), 'main'),
	array('English', $db->escape('exttoonew'), $db->escape('The extension you are installing requires FutureBB version $1, while you are currently running $2. Go to <a href="http://futurebb.futuresight.org">the FutureBB website</a> to update your forum software.'), 'admin'),
	array('English', $db->escape('posttime'), $db->escape('Post time'), 'main'),
	array('English', $db->escape('relevance'), $db->escape('Relevance'), 'main'),
	array('English', $db->escape('tables'), $db->escape('Tables'), 'main'),
	array('English', $db->escape('colrow'), $db->escape('Col $1, Row $2'), 'main'),
	array('English', $db->escape('tableintro'), $db->escape('You use the <code>[table][/table]</code> tags to start and end a table. You use <code>[tr][/tr]</code> to indicate a row, and <code>[td][/td]</code> to indicate a cell. The <code>[tr]</code> tag must go directly inside the <code>[table]</code> tag, and the <code>[td]</code> tag must go inside the <code>[tr]</code> tag.'), 'main'),
	array('English', $db->escape('topicsp'), $db->escape('topic<PLURAL $1>(,s)'), 'main'),
	array('English', $db->escape('postsp'), $db->escape('post<PLURAL $1>(,s)'), 'main'),
	array('English', $db->escape('select'), $db->escape('Select: '), 'main'),
	array('English', $db->escape('stick'), $db->escape('Stick'), 'main'),
	array('English', $db->escape('unstick'), $db->escape('Unstick'), 'main'),
	array('English', $db->escape('close'), $db->escape('Close'), 'main'),
	array('English', $db->escape('open'), $db->escape('Open'), 'main'),
	array('English', $db->escape('confirm'), $db->escape('Confirm'), 'main'),
	array('English', $db->escape('areyousureaction'), $db->escape('Are you sure you want to $1 the following $2?'), 'admin'),
	array('English', $db->escape('signoutothersessions'), $db->escape('Sign out all other sessions'), 'profile'),
	array('English', $db->escape('searchusername'), $db->escape('Search username'), 'admin'),
	array('English', $db->escape('lastused'), $db->escape('Last used'), 'admin'),
	array('English', $db->escape('searchresultsfor'), $db->escape('Search results for $1'), 'main'),
	array('English', $db->escape('parentrequired'), $db->escape('You must place the <b>[$1]</b> tag inside<PLURAL $3>(, one of) the following tag<PLURAL $3>(,s): $2'), 'main'),
	array('English', $db->escape('banuser'), $db->escape('Ban user'), 'admin'),
	array('English', $db->escape('unbanuser'), $db->escape('Unban user'), 'admin'),
	array('English', $db->escape('ban'), $db->escape('Ban'), 'admin'),
	array('English', $db->escape('modviewip'), $db->escape('View IP addresses'), 'admin'),
	array('English', $db->escape('modbanusers'), $db->escape('Ban users'), 'admin'),
	array('English', $db->escape('moddeleteposts'), $db->escape('Delete others&apos; posts'), 'admin'),
	array('English', $db->escape('modeditposts'), $db->escape('Edit others&apos; posts'), 'admin'),
	array('English', $db->escape('modviewipdesc'), $db->escape('Allow the user to view IP addresses of users when they post and register, and also allow use of the IP Tracker.<br /><b>Note:</b> this requires the group also to have moderator privileges.'), 'admin'),
	array('English', $db->escape('modbanusersdesc'), $db->escape('Allow users to ban other users by username. Also allows banning by IP if the "View IP addresses" option is enabled.<br /><b>Note:</b> this requires the group also to have moderator privileges.'), 'admin'),
	array('English', $db->escape('moddeletepostsdesc'), $db->escape('Allow users of this group to delete all posts. Also grants access to the trash bin.<br /><b>Note:</b> this requires the group also to have moderator privileges.'), 'admin'),
	array('English', $db->escape('modeditpostsdesc'), $db->escape('Allow users of this group to edit all posts.<br /><b>Note:</b> this requires the group also to have moderator privileges.'), 'admin'),
	array('English', $db->escape('defaultlanguage'), $db->escape('Default language'), 'admin'),
	array('English', $db->escape('defaultstyle'), $db->escape('Default style'), 'admin'),
);
foreach ($lang_insert_data as &$entry) {
	$entry = '(\'' . implode('\',\'', $entry) . '\')';
}
$q = new DBMassInsert('language', array('language', 'langkey', 'value', 'category'), $lang_insert_data, 'Failed to insert language data');
$q->commit();
