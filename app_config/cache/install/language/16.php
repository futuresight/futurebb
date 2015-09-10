<?php
$q = 'INSERT INTO `#^language`(language,langkey,value,category) VALUES';
$lang_insert_data = array();
$lang_insert_data[] = '(\'English\',\'' . $db->escape('exttoonew') . '\',\'' . $db->escape('The extension you are installing requires FutureBB version $1, while you are currently running $2. Go to <a href="http://futurebb.futuresight.org">the FutureBB website</a> to update your forum software.') . '\', \'admin\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('notextinsidetag') . '\',\'' . $db->escape('You are not allowed to place any text directly inside the <b>[$1]</b> tag.') . '\', \'main\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('posttime') . '\',\'' . $db->escape('Post time') . '\', \'main\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('tables') . '\',\'' . $db->escape('Tables') . '\', \'main\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('colrow') . '\',\'' . $db->escape('Col $1, Row $2') . '\', \'main\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('tableintro') . '\',\'' . $db->escape('You use the <code>[table][/table]</code> tags to start and end a table. You use <code>[tr][/tr]</code> to indicate a row, and <code>[td][/td]</code> to indicate a cell. The <code>[tr]</code> tag must go directly inside the <code>[table]</code> tag, and the <code>[td]</code> tag must go inside the <code>[tr]</code> tag.') . '\', \'main\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('relevance') . '\',\'' . $db->escape('Relevance') . '\', \'main\')';
$q = new DBMassInsert('language', array('language', 'langkey', 'value', 'category'), $lang_insert_data, 'Failed to insert language data');
$q->commit();