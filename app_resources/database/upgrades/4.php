<?php
//upgrade from v1.3 to v1.4 (DB 3 -> 4)
$db->drop_field('search_index', 'count');

$field = new DBField('locations', 'TEXT');
$field->add_extra('NOT NULL');
$db->add_field('search_index', $field, 'word');
$db->query('INSERT INTO `#^reports`(post_id,post_type,reason,reported_by,time_reported) VALUES(0, \'special\',\'' . $db->escape('To complete the upgrade to FutureBB 1.4, you need to rebuild the search index on the <a href=' . $base_config['baseurl'] . '/admin/maintenance">maintenance page</a>, as the search engine has been completely overhauled.') . '\',0,' . time() . ')') or enhanced_error('Failed to alert admin to rebuild search index', true);
echo '<li>RV4: Updating search table structure... success</li>';

