<?php
$q = 'INSERT INTO `#^language`(language,langkey,value,category) VALUES';
$lang_insert_data = array(
	('English', $db->escape('noextdir'), $db->escape('The directory "app_config/extensions" does not exist or is not writable. Please create it and change the file permissions appropriately to fix this (if in doubt, chmod to 0777).'), 'admin'),
	('English', $db->escape('notextinsidetag'), $db->escape('You are not allowed to place any text directly inside the <b>[$1]</b> tag.'), 'main'),
	('English', $db->escape('exttoonew'), $db->escape('The extension you are installing requires FutureBB version $1, while you are currently running $2. Go to <a href="http://futurebb.futuresight.org">the FutureBB website</a> to update your forum software.'), 'admin'),
	('English', $db->escape('posttime'), $db->escape('Post time'), 'main'),
	('English', $db->escape('relevance'), $db->escape('Relevance'), 'main'),
	('English', $db->escape('tables'), $db->escape('Tables'), 'main'),
	('English', $db->escape('colrow'), $db->escape('Col $1, Row $2'), 'main'),
	('English', $db->escape('tableintro'), $db->escape('You use the <code>[table][/table]</code> tags to start and end a table. You use <code>[tr][/tr]</code> to indicate a row, and <code>[td][/td]</code> to indicate a cell. The <code>[tr]</code> tag must go directly inside the <code>[table]</code> tag, and the <code>[td]</code> tag must go inside the <code>[tr]</code> tag.'), 'main'),
	('English', $db->escape('topicsp'), $db->escape('topic<PLURAL $1>(,s)'), 'main'),
	('English', $db->escape('postsp'), $db->escape('post<PLURAL $1>(,s)'), 'main'),
	('English', $db->escape('select'), $db->escape('Select: '), 'main'),
	('English', $db->escape('stick'), $db->escape('Stick'), 'main'),
	('English', $db->escape('unstick'), $db->escape('Unstick'), 'main'),
	('English', $db->escape('close'), $db->escape('Close'), 'main'),
	('English', $db->escape('open'), $db->escape('Open'), 'main'),
	('English', $db->escape('confirm'), $db->escape('Confirm'), 'main'),
	('English', $db->escape('areyousureaction'), $db->escape('Are you sure you want to $1 the following $2?'), 'admin'),
	('English', $db->escape('signoutothersessions'), $db->escape('Sign out all other sessions'), 'profile'),
	('English', $db->escape('searchusername'), $db->escape('Search username'), 'admin'),
	('English', $db->escape('lastused'), $db->escape('Last used'), 'admin'),
);
$q = new DBMassInsert('language', array('language', 'langkey', 'value', 'category'), $lang_insert_data, 'Failed to insert language data');
$q->commit();
