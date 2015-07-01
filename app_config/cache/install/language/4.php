<?php
$q = 'INSERT INTO `#^language`(language,langkey,value,category) VALUES';
$lang_insert_data = array();
$lang_insert_data[] = '(\'English\',\'' . $db->escape('dstprofile') . '\',\'' . $db->escape('Daylight Saving Time (advance one hour)') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('pages') . '\',\'' . $db->escape('Pages: ') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('author') . '\',\'' . $db->escape('Author') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('replies') . '\',\'' . $db->escape('Replies') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('topicmoved') . '\',\'' . $db->escape('(Topic moved)') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('notopics') . '\',\'' . $db->escape('There are no topics in this forum.') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('rssfeed') . '\',\'' . $db->escape('RSS Feed') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('prev') . '\',\'' . $db->escape('Prev') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('next') . '\',\'' . $db->escape('Next') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('newposts') . '\',\'' . $db->escape('New posts') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('edited') . '\',\'' . $db->escape('Edited') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('lastedited') . '\',\'' . $db->escape('Last edited by $1 on $2') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('movetopic') . '\',\'' . $db->escape('Move topic') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('movetoforum') . '\',\'' . $db->escape('Move to forum:') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('move') . '\',\'' . $db->escape('Move') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('leaveredirect') . '\',\'' . $db->escape('Leave redirect?') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('online') . '\',\'' . $db->escape('Online') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('offline') . '\',\'' . $db->escape('Offline') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('posts:') . '\',\'' . $db->escape('Posts: ') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('report') . '\',\'' . $db->escape('Report') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('quote') . '\',\'' . $db->escape('Quote') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('postreply') . '\',\'' . $db->escape('Post reply') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('closednoreply') . '\',\'' . $db->escape('Topic is closed. You may not post a reply.') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('closetopic') . '\',\'' . $db->escape('Close topic') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('opentopic') . '\',\'' . $db->escape('Open topic') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('sticktopic') . '\',\'' . $db->escape('Stick topic') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('unsticktopic') . '\',\'' . $db->escape('Unstick topic') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('embed') . '\',\'' . $db->escape('Embed topic') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('undelete') . '\',\'' . $db->escape('Undelete') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('usergroup') . '\',\'' . $db->escape('User group') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('sortby') . '\',\'' . $db->escape('Sort by') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('numposts') . '\',\'' . $db->escape('Number of posts') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('dateregistered') . '\',\'' . $db->escape('Date registered') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('ascending') . '\',\'' . $db->escape('Ascending') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('descending') . '\',\'' . $db->escape('Descending') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('title') . '\',\'' . $db->escape('Title') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('registered') . '\',\'' . $db->escape('Registered') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('usersonline') . '\',\'' . $db->escape('Users online') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('nobody') . '\',\'' . $db->escape('Nobody') . '\',\'' . $db->escape('main') . '\')';
$lang_insert_data[] = '(\'English\',\'' . $db->escape('seeall') . '\',\'' . $db->escape('See all') . '\',\'' . $db->escape('main') . '\')';
$q = new DBMassInsert('language', array('language', 'langkey', 'value', 'category'), $lang_insert_data, 'Failed to insert language data');
$q->commit();