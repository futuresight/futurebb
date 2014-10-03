<?php
$pages = array(
	'welcome'		=>	false,
	'dbtype'		=>	false,
	'dbsetup'		=>	false,
	'syscfg'		=>	false,
	'adminacct'		=>	false,
	'brdtitle'		=>	false,
	'confirmation'	=>	false,
);

define('FORUM_ROOT', dirname(__FILE__));

function add_cookie_data($key, $data) {
	if (isset($_COOKIE['install_cookie'])) {
		$cookie = base64_decode($_COOKIE['install_cookie']);
	} else {
		$cookie = '';
	}
	$parts = explode(chr(1), $cookie);
	$cookie_data = array();
	foreach ($parts as $val) {
		$subparts = explode('|', $val, 2);
		if (sizeof($subparts) > 1) {
			$cookie_data[$subparts[0]] = $subparts[1];
		}
	}
	$cookie_data[$key] = $data;
	
	$new_cookie_data = array();
	foreach ($cookie_data as $key => $val) {
		$new_cookie_data[] = $key . '|' . $val;
	}
	$new_cookie = base64_encode(implode(chr(1), $new_cookie_data));
	$_COOKIE['install_cookie'] = $new_cookie;
	setcookie('install_cookie', $new_cookie);
}

function get_cookie_data($key) {
	if (!isset($_COOKIE['install_cookie'])) {
		return false;
	}
	$cookie = base64_decode($_COOKIE['install_cookie']);
	$parts = explode(chr(1), $cookie);
	foreach ($parts as $val) {
		$subparts = explode('|', $val, 2);
		if ($subparts[0] == $key) {
			return $subparts[1];
		}
	}
	return false;
}

function test_db() {
	global $db;
	if (file_exists('app_resources/database/' . get_cookie_data('dbtype') . '.php')) {
		include_once 'app_resources/database/' . get_cookie_data('dbtype') . '.php';
		$info = array(
			'host'		=>	get_cookie_data('dbhost'),
			'username'	=>	get_cookie_data('dbuser'),
			'password'	=>	get_cookie_data('dbpass'),
			'name'		=>	get_cookie_data('dbname'),
			'prefix'		=>	get_cookie_data('dbprefix'),
			'hide_errors'	=>	true
		);
		$db = new Database($info);
		if (get_cookie_data('dbname') == '') {
			return false;
		}
		if ($db->link) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function db_fail() {
	global $db_fail, $pages, $page;
	$db_fail = true;
	$pages['dbsetup'] = true;
	$page = 'dbsetup';
}

function get_db_info($name) {
	$dbtype = $name;
	if (ctype_alnum($dbtype)) {
		if (file_exists(FORUM_ROOT . '/app_resources/database/' . $dbtype . '.php')) {
			$contents = file_get_contents(FORUM_ROOT . '/app_resources/database/' . $dbtype . '.php');
			if (strstr($contents, 'FutureBB Database Spec - DO NOT REMOVE')) {
				//database file registered
				preg_match('%Name<(.*?)>%', $contents, $matches);
				if (!empty($matches[1])) {
					$db_name = $matches[1];
					preg_match('%Extension<(.*?)>%', $contents, $matches);
					if (!empty($matches[1]) && extension_loaded($matches[1])) {
						return $db_name;
					}
				}
			}
		}
	}
	return false;
}

//language stuff
include 'app_resources/includes/functions.php';
if (get_cookie_data('language') === false) {
	$futurebb_user = array('language' => 'English');
} else {
	$futurebb_user = array('language' => get_cookie_data('language'));
}
translate('<addfile>', 'install');

$page = '';
if (isset($_GET['downloadconfigxml'])) {
	//create config.xml file
	$xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8" ?><!--FutureBB Server Configuration - edit at your own risk--><config></config>');
	
	$db_xml = $xml->addChild('cfgset');
	$db_xml->addAttribute('type', 'database');
	$db_xml->addChild('type', get_cookie_data('dbtype'));
	$db_xml->addChild('host', get_cookie_data('dbhost'));
	$db_xml->addChild('username', get_cookie_data('dbuser'));
	$db_xml->addChild('password', get_cookie_data('dbpass'));
	$db_xml->addChild('name', get_cookie_data('dbname'));
	$db_xml->addChild('prefix', get_cookie_data('dbprefix'));
	
	$srv_xml = $xml->addChild('cfgset');
	$srv_xml->addAttribute('type', 'server');
	$srv_xml->addChild('baseurl', get_cookie_data('baseurl'));
	$srv_xml->addChild('basepath', get_cookie_data('basepath'));
	$srv_xml->addChild('cookie_name', 'futurebb_cookie_' . substr(md5(time()), 0, 10));
	$srv_xml->addChild('debug', 'off');
	
	header('Content-type: application/xml');
	header('Content-disposition: attachment; filename=config.xml');
	echo $xml->asXML();
	die;
} else if (isset($_GET['downloadhtaccess'])) {
	//download the default .htaccess file
	header('Content-type: text/plain');
	if (!strstr($_SERVER['SERVER_SOFTWARE'], 'Apache')) {
		echo 'You are not running Apache, therefore the .htaccess file is useless to you.'; die;
	}
	header('Content-disposition: attachment; filename=.htaccess');
	echo 'RewriteEngine On' . "\n";
	echo 'RewriteBase ' . get_cookie_data('basepath') . "\n";
	echo 'RewriteRule ^static/(.*?)$ static/$1 [L]' . "\n";
	echo 'RewriteRule ^(.*)$ dispatcher.php';
	die;
} else if (isset($_POST['install'])) {
	include 'app_resources/database/db_resources.php';
	if (test_db()) {
		//create database structure
		$tables = array();
		
		$tables['config'] = new DBTable('config');
		$new_fld = new DBField('c_name', 'VARCHAR(100)');
		$new_fld->add_key('PRIMARY');
		$new_fld->add_extra('NOT NULL');
		$tables['config']->add_field($new_fld);
		$new_fld = new DBField('c_value', 'TEXT');
		$new_fld->add_Extra('NOT NULL');
		$tables['config']->add_field($new_fld);
		$tables['config']->commit();
		
		$tables['bans'] = new DBTable('bans');
		$new_fld = new DBField('id', 'INT');
		$new_fld->add_key('PRIMARY');
		$new_fld->add_extra('auto_increment');
		$tables['bans']->add_field($new_fld);
		$new_fld = new DBField('username', 'VARCHAR(50)');
		$new_fld->add_extra('NOT NULL');
		$tables['bans']->add_field($new_fld);
		$new_fld = new DBField('ip', 'VARCHAR(50)');
		$new_fld->add_extra('NOT NULL');
		$tables['bans']->add_field($new_fld);
		$new_fld = new DBField('message', 'TEXT');
		$new_fld->add_extra('NOT NULL');
		$tables['bans']->add_field($new_fld);
		$new_fld = new DBField('expires', 'INT');
		$new_fld->set_default('NULL');
		$tables['bans']->add_field($new_fld);
		$tables['bans']->commit();
		
		$tables['categories'] = new DBTable('categories');
		$new_fld = new DBField('id', 'INT');
		$new_fld->add_key('PRIMARY');
		$new_fld->add_extra('auto_increment');
		$tables['categories']->add_field($new_fld);
		$new_fld = new DBField('name', 'VARCHAR(100)');
		$new_fld->add_extra('NOT NULL');
		$tables['categories']->add_field($new_fld);
		$new_fld = new DBField('sort_position', 'INT');
		$new_fld->add_extra('NOT NULL');
		$tables['categories']->add_field($new_fld);
		$tables['categories']->commit();
		
		$tables['forums'] = new DBTable('forums');
		$new_fld = new DBField('id', 'INT');
		$new_fld->add_key('PRIMARY');
		$new_fld->add_extra('auto_increment');
		$tables['forums']->add_field($new_fld);
		$new_fld = new DBField('url', 'VARCHAR(250)');
		$new_fld->add_extra('NOT NULL');
		$tables['forums']->add_field($new_fld);
		$new_fld = new DBField('name', 'VARCHAR(200)');
		$tables['forums']->add_field($new_fld);
		$new_fld = new DBField('cat_id', 'INT');
		$tables['forums']->add_field($new_fld);
		$new_fld = new DBField('sort_position', 'INT');
		$tables['forums']->add_field($new_fld);
		$new_fld = new DBField('description', 'TEXT');
		$new_fld->add_extra('NOT NULL');
		$tables['forums']->add_field($new_fld);
		$new_fld = new DBField('redirect_id', 'INT');
		$new_fld->set_default('NULL');
		$tables['forums']->add_field($new_fld);
		$new_fld = new DBField('last_post', 'INT');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['forums']->add_field($new_fld);
		$new_fld = new DBField('last_post_id', 'INT');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['forums']->add_field($new_fld);
		$new_fld = new DBField('view_groups', 'TEXT');
		$new_fld->add_extra('NOT NULL');
		$tables['forums']->add_field($new_fld);
		$new_fld = new DBField('topic_groups', 'TEXT');
		$new_fld->add_extra('NOT NULL');
		$tables['forums']->add_field($new_fld);
		$new_fld = new DBField('reply_groups', 'TEXT');
		$new_fld->add_extra('NOT NULL');
		$tables['forums']->add_field($new_fld);
		$new_fld = new DBField('num_topics', 'INT');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['forums']->add_field($new_fld);
		$new_fld = new DBField('num_posts', 'INT');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['forums']->add_field($new_fld);
		$tables['forums']->commit();
		
		$tables['notifications'] = new DBTable('notifications');
		$new_fld = new DBField('id', 'INT');
		$new_fld->add_key('PRIMARY');
		$new_fld->add_extra('auto_increment');
		$tables['notifications']->add_field($new_fld);
		$new_fld = new DBField('type', 'VARCHAR(20)');
		$new_fld->add_extra('NOT NULL');
		$tables['notifications']->add_field($new_fld);
		$new_fld = new DBField('user', 'INT');
		$new_fld->add_extra('NOT NULL');
		$tables['notifications']->add_field($new_fld);
		$new_fld = new DBField('send_time', 'INT');
		$new_fld->add_extra('NOT NULL');
		$tables['notifications']->add_field($new_fld);
		$new_fld = new DBField('contents', 'MEDIUMTEXT');
		$new_fld->add_extra('NOT NULL');
		$tables['notifications']->add_field($new_fld);
		$new_fld = new DBField('arguments', 'MEDIUMTEXT');
		$new_fld->add_extra('NOT NULL');
		$tables['notifications']->add_field($new_fld);
		$new_fld = new DBField('read_time', 'INT');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default(0);
		$tables['notifications']->add_field($new_fld);
		$new_fld = new DBField('read_ip', 'VARCHAR(50)');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['notifications']->add_field($new_fld);
		$tables['notifications']->commit();
		
		$tables['posts'] = new DBTable('posts');
		$new_fld = new DBField('id', 'INT');
		$new_fld->add_key('PRIMARY');
		$new_fld->add_extra('auto_increment');
		$tables['posts']->add_field($new_fld);
		$new_fld = new DBField('poster', 'INT');
		$new_fld->add_extra('NOT NULL');
		$tables['posts']->add_field($new_fld);
		$new_fld = new DBField('poster_ip', 'VARCHAR(50)');
		$new_fld->add_extra('NOT NULL');
		$tables['posts']->add_field($new_fld);
		$new_fld = new DBField('content', 'MEDIUMTEXT');
		$new_fld->add_extra('NOT NULL');
		$tables['posts']->add_field($new_fld);
		$new_fld = new DBField('parsed_content', 'MEDIUMTEXT');
		$new_fld->add_extra('NOT NULL');
		$tables['posts']->add_field($new_fld);
		$new_fld = new DBField('posted', 'INT');
		$new_fld->add_extra('NOT NULL');
		$tables['posts']->add_field($new_fld);
		$new_fld = new DBField('topic_id', 'INT');
		$new_fld->add_extra('NOT NULL');
		$tables['posts']->add_field($new_fld);
		$new_fld = new DBField('deleted', 'INT');
		$new_fld->set_default('NULL');
		$tables['posts']->add_field($new_fld);
		$new_fld = new DBField('deleted_by', 'INT');
		$new_fld->set_default('NULL');
		$tables['posts']->add_field($new_fld);
		$new_fld = new DBField('last_edited', 'INT');
		$new_fld->set_default('NULL');
		$tables['posts']->add_field($new_fld);
		$new_fld = new DBField('last_edited_by', 'INT');
		$new_fld->set_default('NULL');
		$tables['posts']->add_field($new_fld);
		$new_fld = new DBField('disable_smilies', 'TINYINT(1)');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['posts']->add_field($new_fld);
		$tables['posts']->commit();
		
		$tables['read_tracker'] = new DBTable('read_tracker');
		$new_fld = new DBField('id', 'INT');
		$new_fld->add_key('PRIMARY');
		$new_fld->add_extra('auto_increment');
		$tables['read_tracker']->add_field($new_fld);
		$new_fld = new DBField('user_id', 'INT');
		$new_fld->add_extra('NOT NULL');
		$tables['read_tracker']->add_field($new_fld);
		$new_fld = new DBField('topic_id', 'INT');
		$new_fld->set_default('NULL');
		$tables['read_tracker']->add_field($new_fld);
		$new_fld = new DBField('forum_id', 'INT');
		$new_fld->set_default('NULL');
		$tables['read_tracker']->add_field($new_fld);
		$tables['read_tracker']->commit();
		
		$tables['reports'] = new DBTable('reports');
		$new_fld = new DBField('id', 'INT');
		$new_fld->add_extra('auto_increment');
		$new_fld->add_key('PRIMARY');
		$tables['reports']->add_field($new_fld);
		$new_fld = new DBField('post_id', 'INT');
		$new_fld->add_extra('NOT NULL');
		$tables['reports']->add_field($new_fld);
		$new_fld = new DBField('post_type', 'ENUM(\'post\',\'msg\')');
		$new_fld->set_default('\'post\'');
		$new_fld->add_extra('NOT NULL');
		$tables['reports']->add_field($new_fld);
		$new_fld = new DBField('reason', 'TEXT');
		$new_fld->add_extra('NOT NULL');
		$tables['reports']->add_field($new_fld);
		$new_fld = new DBField('reported_by', 'INT');
		$new_fld->add_extra('NOT NULL');
		$tables['reports']->add_field($new_fld);
		$new_fld = new DBField('time_reported', 'INT');
		$new_fld->add_extra('NOT NULL');
		$tables['reports']->add_field($new_fld);
		$new_fld = new DBField('zapped', 'INT');
		$new_fld->set_default('NULL');
		$tables['reports']->add_field($new_fld);
		$new_fld = new DBField('zapped_by', 'INT');
		$new_fld->set_default('NULL');
		$tables['reports']->add_field($new_fld);
		$new_fld = new DBField('status', 'ENUM(\'unread\',\'review\',\'reject\',\'accept\',\'noresp\',\'withdrawn\')');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('\'unread\'');
		$tables['reports']->add_field($new_fld);
		$tables['reports']->commit();
		
		$tables['search_index'] = new DBTable('search_index');
		$new_fld = new DBField('id', 'INT');
		$new_fld->add_extra('auto_increment');
		$new_fld->add_key('PRIMARY');
		$tables['search_index']->add_field($new_fld);
		$new_fld = new DBField('post_id', 'INT');
		$new_fld->add_extra('NOT NULL');
		$tables['search_index']->add_field($new_fld);
		$new_fld = new DBField('word', 'VARCHAR(255)');
		$new_fld->add_extra('NOT NULL');
		$tables['search_index']->add_field($new_fld);
		$new_fld = new DBField('num_matches', 'INT');
		$new_fld->add_extra('NOT NULL');
		$tables['search_index']->add_field($new_fld);
		$tables['search_index']->commit();
		
		$tables['topics'] = new DBTable('topics');
		$new_fld = new DBField('id', 'INT');
		$new_fld->add_extra('auto_increment');
		$new_fld->add_key('PRIMARY');
		$tables['topics']->add_field($new_fld);
		$new_fld = new DBField('subject', 'VARCHAR(200)');
		$new_fld->add_extra('NOT NULL');
		$tables['topics']->add_field($new_fld);
		$new_fld = new DBField('url', 'VARCHAR(210)');
		$new_fld->add_extra('NOT NULL');
		$tables['topics']->add_field($new_fld);
		$new_fld = new DBField('forum_id', 'INT');
		$new_fld->add_extra('NOT NULL');
		$tables['topics']->add_field($new_fld);
		$new_fld = new DBField('deleted', 'INT');
		$new_fld->set_default('NULL');
		$tables['topics']->add_field($new_fld);
		$new_fld = new DBField('deleted_by', 'INT');
		$new_fld->set_default('NULL');
		$tables['topics']->add_field($new_fld);
		$new_fld = new DBField('last_post', 'INT');
		$new_fld->add_extra('NOT NULL');
		$tables['topics']->add_field($new_fld);
		$new_fld = new DBField('last_post_id', 'INT');
		$new_fld->add_extra('NOT NULL');
		$tables['topics']->add_field($new_fld);
		$new_fld = new DBField('first_post_id', 'INT');
		$new_fld->add_extra('NOT NULL');
		$tables['topics']->add_field($new_fld);
		$new_fld = new DBField('closed', 'TINYINT(1)');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['topics']->add_field($new_fld);
		$new_fld = new DBField('sticky', 'TINYINT(1)');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['topics']->add_field($new_fld);
		$new_fld = new DBField('redirect_id', 'INT');
		$new_fld->set_default('NULL');
		$tables['topics']->add_field($new_fld);
		$new_fld = new DBField('show_redirect', 'TINYINT(1)');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['topics']->add_field($new_fld);
		$new_fld = new DBField('num_replies', 'INT');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['topics']->add_field($new_fld);
		$tables['topics']->commit();
		
		$tables['users'] = new DBTable('users');
		$new_fld = new DBField('id', 'INT');
		$new_fld->add_extra('auto_increment');
		$new_fld->add_key('PRIMARY');
		$tables['users']->add_field($new_fld);
		$new_fld = new DBField('deleted', 'TINYINT(1)');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['users']->add_field($new_fld);
		$new_fld = new DBField('username', 'VARCHAR(50)');
		$new_fld->add_extra('NOT NULL');
		$new_fld->add_key('UNIQUE');
		$tables['users']->add_field($new_fld);
		$new_fld = new DBField('password', 'VARCHAR(100)');
		$new_fld->add_extra('NOT NULL');
		$tables['users']->add_field($new_fld);
		$new_fld = new DBField('email', 'VARCHAR(500)');
		$new_fld->add_extra('NOT NULL');
		$tables['users']->add_field($new_fld);
		$new_fld = new DBField('activate_key', 'VARCHAR(50)');
		$new_fld->set_default('NULL');
		$tables['users']->add_field($new_fld);
		$new_fld = new DBField('recover_key', 'VARCHAR(50)');
		$new_fld->set_default('NULL');
		$tables['users']->add_field($new_fld);
		$new_fld = new DBField('registered', 'INT');
		$new_fld->add_extra('NOT NULL');
		$tables['users']->add_field($new_fld);
		$new_fld = new DBField('registration_ip', 'VARCHAR(50)');
		$new_fld->add_extra('NOT NULL');
		$tables['users']->add_field($new_fld);
		$new_fld = new DBField('num_posts', 'INT');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['users']->add_field($new_fld);
		$new_fld = new DBField('last_post', 'INT');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['users']->add_field($new_fld);
		$new_fld = new DBField('group_id', 'INT');
		$new_fld->add_extra('NOT NULL');
		$tables['users']->add_field($new_fld);
		$new_fld = new DBField('signature', 'TEXT');
		$new_fld->add_extra('NOT NULL');
		$tables['users']->add_field($new_fld);
		$new_fld = new DBField('parsed_signature', 'TEXT');
		$new_fld->add_extra('NOT NULL');
		$tables['users']->add_field($new_fld);
		$new_fld = new DBField('last_visit', 'INT');
		$new_fld->add_extra('NOT NULL');
		$tables['users']->add_field($new_fld);
		$new_fld = new DBField('timezone', 'TINYINT(3)');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['users']->add_field($new_fld);
		$new_fld = new DBField('style', 'VARCHAR(100)');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('\'default\'');
		$tables['users']->add_field($new_fld);
		$new_fld = new DBField('language', 'VARCHAR(100)');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('\'English\'');
		$tables['users']->add_field($new_fld);
		$new_fld = new DBField('restricted_privs', 'SET(\'\',\'edit\',\'delete\')');
		$new_fld->add_extra('NOT NULL');
		$tables['users']->add_field($new_fld);
		$new_fld = new DBField('block_pm', 'TINYINT(1)');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['users']->add_field($new_fld);
		$new_fld = new DBField('block_notif', 'TINYINT(1)');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['users']->add_field($new_fld);
		$new_fld = new DBField('last_page_load', 'INT');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['users']->add_field($new_fld);
		$tables['users']->commit();
		
		$tables['user_groups'] = new DBTable('user_groups');
		$new_fld = new DBField('g_id', 'INT');
		$new_fld->add_extra('auto_increment');
		$new_fld->add_key('PRIMARY');
		$tables['user_groups']->add_field($new_fld);
		$new_fld = new DBField('g_permanent', 'TINYINT(1)');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['user_groups']->add_field($new_fld);
		$new_fld = new DBField('g_guest_group', 'TINYINT(1)');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['user_groups']->add_field($new_fld);
		$new_fld = new DBField('g_name', 'VARCHAR(50)');
		$new_fld->add_extra('NOT NULL');
		$tables['user_groups']->add_field($new_fld);
		$new_fld = new DBField('g_title', 'VARCHAR(50)');
		$new_fld->add_extra('NOT NULL');
		$tables['user_groups']->add_field($new_fld);
		$new_fld = new DBField('g_admin_privs', 'TINYINT(1)');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['user_groups']->add_field($new_fld);
		$new_fld = new DBField('g_mod_privs', 'TINYINT(1)');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['user_groups']->add_field($new_fld);
		$new_fld = new DBField('g_edit_posts', 'TINYINT(1)');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('1');
		$tables['user_groups']->add_field($new_fld);
		$new_fld = new DBField('g_delete_posts', 'TINYINT(1)');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('1');
		$tables['user_groups']->add_field($new_fld);
		$new_fld = new DBField('g_signature', 'TINYINT(1)');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('1');
		$tables['user_groups']->add_field($new_fld);
		$new_fld = new DBField('g_user_list', 'TINYINT(1)');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('1');
		$tables['user_groups']->add_field($new_fld);
		$new_fld = new DBField('g_user_list_groups', 'TEXT');
		$new_fld->add_extra('NOT NULL');
		$tables['user_groups']->add_field($new_fld);
		$new_fld = new DBField('g_promote_group', 'INT');
		$new_fld->add_extra('NOT NULL');
		$tables['user_groups']->add_field($new_fld);
		$new_fld = new DBField('g_promote_posts', 'INT');
		$new_fld->add_extra('NOT NULL');
		$tables['user_groups']->add_field($new_fld);
		$new_fld = new DBField('g_promote_operator', 'TINYINT(1)');
		$new_fld->add_extra('NOT NULL');
		$tables['user_groups']->add_field($new_fld);
		$new_fld = new DBField('g_promote_days', 'INT');
		$new_fld->add_extra('NOT NULL');
		$tables['user_groups']->add_field($new_fld);
		$new_fld = new DBField('g_post_flood', 'INT');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['user_groups']->add_field($new_fld);
		$new_fld = new DBField('g_posts_per_hour', 'INT');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['user_groups']->add_field($new_fld);
		$new_fld = new DBField('g_post_links', 'TINYINT(1)');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['user_groups']->add_field($new_fld);
		$new_fld = new DBField('g_post_images', 'TINYINT(1)');
		$new_fld->add_extra('NOT NULL');
		$new_fld->set_default('0');
		$tables['user_groups']->add_field($new_fld);
		$tables['user_groups']->commit();
		
		//add database data
		set_config('board_title', get_cookie_data('board_title'));
		set_config('announcement_text', '');
		set_config('announcement_enable', 0);
		set_config('online_timeout', 300);
		set_config('show_post_count', 1);
		set_config('sig_max_length', 0);
		set_config('sig_max_lines', 0);
		set_config('sig_max_height', 0);
		set_config('default_language', 'English');
		set_config('default_user_group', 3);
		set_config('topics_per_page', 25);
		set_config('posts_per_page', 25);
		set_config('verify_registrations', 0);
		set_config('avatars', 0);
		set_config('censoring', base64_encode(''));
		set_config('maintenance', 0);
		set_config('admin_email', get_cookie_data('adminemail'));
		set_config('maintenance_message', 'These forums are down for maintenance. Please come back later.');
		set_config('footer_text', '');
		set_config('turn_on_maint', 0);
		set_config('turn_off_maint', 0);
		set_config('rules', '');
		set_config('addl_header_links', '');
		set_config('allow_privatemsg', 0);
		set_config('allow_notifications', 1);
		set_config('imghostrestriction', 'none|');
		
		//create guest user
		$insert = new DBInsert('users', array(
			'username'		=> 'Guest',
			'password'		=> 'Guest',
			'email'			=> '',
			'registered'		=> 0,
			'registration_ip'	=> '',
			'group_id'		=> 0,
			'last_visit'		=> 0,
			'last_page_load'	=> 0
		), 'Failed to create admin user');
		$insert->commit();
		
		//create admin user
		$insert = new DBInsert('users', array(
			'username'		=> get_cookie_data('adminusername'),
			'password'		=> futurebb_hash(get_cookie_data('adminpass')),
			'email'			=> get_cookie_data('adminemail'),
			'registered'		=> time(),
			'registration_ip'	=> $_SERVER['REMOTE_ADDR'],
			'group_id'		=> 1,
			'last_visit'		=> time(),
			'last_page_load'	=> time()
		), 'Failed to create admin user');
		$insert->commit();
		
		//create user groups
		$insert = new DBInsert('user_groups', array(
			'g_permanent'		=> 1,
			'g_guest_group'	=> 0,
			'g_name'			=> 'Administrators',
			'g_title'			=> 'Administrator',
			'g_admin_privs'	=> 1,
			'g_mod_privs'		=> 1,
			'g_edit_posts'		=> 1,
			'g_delete_posts'	=> 1,
			'g_signature'		=> 1,
			'g_user_list'		=> 1,
			'g_user_list_groups'=> '1,2,3',
			'g_promote_group'	=> 0,
			'g_promote_posts'	=> 0,
			'g_promote_operator'=> 0,
			'g_promote_days'	=> 0,
			'g_post_flood'		=> 0,
			'g_posts_per_hour'	=> 0,
			'g_post_links'		=> 1,
			'g_post_images'	=> 1
		), 'Failed to create admin user group');
		$insert->commit();
		$insert = new DBInsert('user_groups', array(
			'g_permanent'		=> 1,
			'g_guest_group'	=> 1,
			'g_name'			=> 'Guests',
			'g_title'			=> 'Guest',
			'g_admin_privs'	=> 0,
			'g_mod_privs'		=> 0,
			'g_edit_posts'		=> 0,
			'g_delete_posts'	=> 0,
			'g_signature'		=> 0,
			'g_user_list'		=> 0,
			'g_user_list_groups'=> '',
			'g_promote_group'	=> 0,
			'g_promote_posts'	=> 0,
			'g_promote_operator'=> 0,
			'g_promote_days'	=> 0,
			'g_post_flood'		=> 0,
			'g_posts_per_hour'	=> 0,
			'g_post_links'		=> 0,
			'g_post_images'	=> 0
		), 'Failed to create guest user group');
		$insert->commit();
		$insert = new DBInsert('user_groups', array(
			'g_permanent'		=> 1,
			'g_guest_group'	=> 0,
			'g_name'			=> 'Members',
			'g_title'			=> 'Member',
			'g_admin_privs'	=> 0,
			'g_mod_privs'		=> 0,
			'g_edit_posts'		=> 1,
			'g_delete_posts'	=> 1,
			'g_signature'		=> 1,
			'g_user_list'		=> 1,
			'g_user_list_groups'=> '1,2,3',
			'g_promote_group'	=> 0,
			'g_promote_posts'	=> 0,
			'g_promote_operator'=> 0,
			'g_promote_days'	=> 0,
			'g_post_flood'		=> 60,
			'g_posts_per_hour'	=> 0,
			'g_post_links'		=> 1,
			'g_post_images'	=> 1
		), 'Failed to create member user group');
		$insert->commit();
		
		$page = 'complete';
	} else {
		db_fail();
	}
} else if (isset($_POST['brdsettings'])) {
	foreach ($_POST['config'] as $key => $val) {
		$pages['confirmation'] = true;
		$page = 'confirm';
		foreach ($_POST['config'] as $key => $val) {
			add_cookie_data($key, $val);
		}
		
	}
} else if (isset($_POST['adminacc'])) {
	add_cookie_data('adminusername', $_POST['adminusername']);
	add_cookie_data('adminemail', $_POST['adminemail']);
	add_cookie_data('adminpass', $_POST['adminpass']);
	if ($_POST['adminpass'] != $_POST['confirmadminpass']) {
		$pages['adminacct'] = true;
		$page = 'adminacc';
		$pwd_mismatch = true;
	} else {
		$pages['brdtitle'] = true;
		$page = 'brdsettings';
	}
} else if (isset($_POST['syscfg'])) {
	add_cookie_data('baseurl', $_POST['baseurl']);
	add_cookie_data('basepath', $_POST['basepath']);
	$pages['adminacct'] = true;
	$page = 'adminacc';
	$pwd_mismatch = false;
} else if (isset($_POST['dbsetup'])) {
	if (get_cookie_data('dbtype') != 'sqlite3') {
		add_cookie_data('dbhost', $_POST['dbhost']);
		add_cookie_data('dbuser', $_POST['dbuser']);
		add_cookie_data('dbpass', $_POST['dbpass']);
	}
	add_cookie_data('dbname', $_POST['dbname']);
	add_cookie_data('dbprefix', $_POST['dbprefix']);
	
	//test database
	if (test_db()) {
		$pages['syscfg'] = true;
		$page = 'syscfg';
	} else {
		db_fail();
	}
} else if (isset($_POST['dbtype'])) {
	//check a valid database was entered
	$ok = false;
	if (get_db_info($_POST['dbtype'])) {
		add_cookie_data('dbtype', $_POST['dbtype']);
		$db_fail = false;
		$pages['dbsetup'] = true;
		$page = 'dbsetup';
	} else {
		$pages['dbtype'] = true;
		$page = 'dbtype';
		$error = translate('baddbtype');
	}
} else if (isset($_POST['start'])) {
	$pages['dbtype'] = true;
	$page = 'dbtype';
} else if (isset($_POST['language'])) {
	add_cookie_data('language', $_POST['language']);
} else {
	setcookie('install_cookie', '');
	$pages['welcome'] = true;
	$page = 'welcome';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title><?php echo translate('headertext'); ?></title>
		<style type="text/css">
		<?php
		$data = file_get_contents('app_resources/pages/css/default.css');
		$data = preg_replace('%<\?php.*?\?>%ms', '', $data);
		echo $data;
		?>
		</style>
	</head>
	<body>
		<div id="futurebb">
			<div class="forum_header">
				<h1 style="text-align:center"><?php echo translate('headertext'); ?></h1>
				<div id="navlistwrap">
					<?php
					$pages_echo = array();
					foreach ($pages as $key => $current) {
						if ($current) {
							$pages_echo[] = '<b>' . translate($key) . '</b>';
						} else {
							$pages_echo[] = translate($key);
						}
					}
					echo implode(' &rarr; ', $pages_echo);
					?>
				</div>
			</div>
			<div class="forum_content">
				<?php
				switch ($page) {
					case 'welcome':
						?>
						<h2><?php echo translate('welcometofbb'); ?></h2>
						<p><?php echo translate('intro'); ?></p>
						<?php
						$ok = true;
						//check if necessary directories are writable
						if (!file_exists(FORUM_ROOT . '/temp') || !is_dir(FORUM_ROOT . '/temp')) {
							$ok = false;
							echo '<p style="color:#F00; font-weight:bold">The directory &quot;temp&quot; does not exist in the forum root directory. Please create it.</p>';
						}
						if (!writable(FORUM_ROOT . '/static/avatars/')) {
							$ok = false;
							echo '<p style="color:#F00; font-weight:bold">The directory &quot;static/avatars&quot; is not writable. Please change the permissions so that it is (chmod to 0777 if in doubt)</p>';
						}
						if (!writable(FORUM_ROOT . '/temp/')) {
							$ok = false;
							echo '<p style="color:#F00; font-weight:bold">The directory &quot;temp&quot; is not writable. Please change the permissions so that it is (chmod to 0777 if in doubt)</p>';
						}
						if (strstr($_SERVER['SERVER_SOFTWARE'], 'Apache') && !in_array('mod_rewrite', apache_get_modules())) { //check for mod_rewrite
							$ok = false;
							echo '<p style="color:#F00; font-weight:bold">mod_rewrite is not installed in Apache. This means that the URL system will not work. Please install it.</p>';
						}
						if (!strstr($_SERVER['SERVER_SOFTWARE'], 'Apache')) {
							echo '<p style="color:#A00; font-weight:bold">You are not running Apache. This means the automatic rewrite configuration is not available. You will have to set it up yourself.</p>';
						}
						?>
						<form action="install.php" method="post" enctype="multipart/form-data">
                        	<p><?php echo translate('selectlang'); ?> <select name="language"><?php
							$handle = opendir(FORUM_ROOT . '/app_config/langs');
							while ($lang = readdir($handle)) {
								if ($lang != '.' && $lang != '..') {
									echo '<option value="' . $lang . '">' . $lang . '</option>';
								}
							}
							?></select></p>
							<p><input type="submit" name="start" value="<?php echo translate('continue'); ?> &rarr;"<?php if (!$ok) echo ' disabled="disabled"'; ?> /></p>
						</form>
						<?php
						break;
					case 'dbtype':
						?>
                        <h2><?php echo translate('dbtype'); ?></h2>
                        <form action="install.php" method="post" enctype="multipart/form-data">
                        	<?php
							if (isset($error)) {
								echo '<p style="color:#F00; font-weight:bold">' . $error . '</p>';
							}
							?>
                        	<p><?php echo translate('selectdbtype'); ?> <select name="dbtype">
							<?php
							$handle = opendir(FORUM_ROOT . '/app_resources/database');
							$existing_db_type = get_cookie_data('dbtype');
							while ($file = readdir($handle)) {
								if ($file != '.' && $file != '..') {
									$contents = file_get_contents(FORUM_ROOT . '/app_resources/database/' . $file);
									if (strstr($contents, 'FutureBB Database Spec - DO NOT REMOVE')) {
										//database file registered
										preg_match('%Name<(.*?)>%', $contents, $matches);
										if (!empty($matches[1])) {
											$name = $matches[1];
											preg_match('%Extension<(.*?)>%', $contents, $matches);
											if (!empty($matches[1]) && extension_loaded($matches[1])) {
												echo '<option value="' . basename($file, '.php') . '"';
												if (basename($file, '.php') == $existing_db_type) {
													echo ' selected="selected"';
												}
												echo '>' . $name . '</option>';
											}
										}
									}
								}
							}
                            ?>
                            </select></p>
                            <p><input type="submit" value="<?php echo translate('continue'); ?> &rarr;" /></p>
                        </form>
                        <?php
						break;
					case 'dbsetup':
						?>
						<h2><?php echo translate('dbsetup'); ?></h2>
						<?php
						if ($db_fail) {
							if ($db->connect_error()) {
								$error = $db->connect_error();
							} else if (get_cookie_data('dbname') == '') {
								$error = 'No database specified';
							} else {
								$error = 'Unknown error';
							}
							echo '<p style="color:#F00; font-weight:bold">' . translate('baddb') . $error . '</p>';
						}
						?>
						<form action="install.php" method="post" enctype="multipart/form-data">
							<table border="0">
                            	<tr>
                                	<td><?php echo translate('type'); ?></td>
                                    <td><?php echo get_db_info(get_cookie_data('dbtype')); ?></td>
                                </tr>
                                <?php if (get_cookie_data('dbtype') == 'sqlite3') { ?>
                                <tr>
									<td><?php echo translate('dbfile'); ?></td>
									<td><input type="text" name="dbname" value="<?php echo get_cookie_data('dbname') ? get_cookie_data('dbname') : ''; ?>" /></td>
								</tr>
                                <?php } else { ?>
								<tr>
									<td><?php echo translate('host'); ?></td>
									<td><input type="text" name="dbhost" value="<?php echo get_cookie_data('dbhost') ? get_cookie_data('dbhost') : 'localhost'; ?>" /></td>
								</tr>
								<tr>
									<td><?php echo translate('username'); ?></td>
									<td><input type="text" name="dbuser" value="<?php echo get_cookie_data('dbuser') ? get_cookie_data('dbuser') : 'root'; ?>" /></td>
								</tr>
								<tr>
									<td><?php echo translate('pwd'); ?></td>
									<td><input type="password" name="dbpass" value="<?php echo get_cookie_data('dbpass') ? get_cookie_data('dbpass') : ''; ?>" /></td>
								</tr>
								<tr>
									<td><?php echo translate('name'); ?></td>
									<td><input type="text" name="dbname" value="<?php echo get_cookie_data('dbname') ? get_cookie_data('dbname') : ''; ?>" /></td>
								</tr>
                                <?php } ?>
                                <tr>
									<td><?php echo translate('prefix'); ?></td>
									<td><input type="text" name="dbprefix" value="<?php echo get_cookie_data('dbprefix') ? get_cookie_data('dbprefix') : 'futurebb_'; ?>" /></td>
								</tr>
							</table>
							<p><input type="submit" name="dbsetup" value="<?php echo translate('continuetest'); ?> &rarr;" /></p>
						</form>
						<?php
						break;
					case 'syscfg':
						?>
						<h2><?php echo translate('syscfg'); ?></h2>
						<p><?php echo translate('dbgood'); ?></p>
                        <p><?php echo translate('seturlstuff'); ?></p>
						<form action="install.php" method="post" enctype="multipart/form-data">
							<table border="0">
								<tr>
									<td><?php echo translate('baseurl'); ?></td>
									<td><input type="text" name="baseurl" value="<?php if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') echo 'https://'; else echo 'http://'; echo $_SERVER['HTTP_HOST']; echo str_replace('/install.php', '', $_SERVER['REQUEST_URI']); ?>" size="50" /></td>
								</tr>
								<tr>
									<td><?php echo translate('baseurlpath'); ?></td>
									<td><input type="text" name="basepath" value="<?php echo str_replace('/install.php', '', $_SERVER['REQUEST_URI']); ?>" size="50" /></td>
								</tr>
							</table>
							<p><input type="submit" name="syscfg" value="<?php echo translate('continue'); ?> &rarr;" /></p>
						</form>
						<?php
						break;
					case 'adminacc':
						?>
						<h2><?php echo translate('adminacct'); ?></h2>
						<?php
						if ($pwd_mismatch) {
							echo '<p>' . translate('pwdmismatch') . '</p>';
						}
						?>
						<form action="install.php" method="post" enctype="multipart/form-data">
							<table border="0">
								<tr>
									<td><?php echo translate('username'); ?></td>
									<td><input type="text" name="adminusername" value="<?php echo get_cookie_data('adminusername') ? get_cookie_data('adminusername') : ''; ?>" /></td>
								</tr>
								<tr>
									<td><?php echo translate('pwd'); ?></td>
									<td><input type="password" name="adminpass" value="<?php echo (get_cookie_data('adminpass') && !$pwd_mismatch) ? get_cookie_data('adminpass') : ''; ?>" /></td>
								</tr>
								<tr>
									<td><?php echo translate('confirmpwd'); ?></td>
									<td><input type="password" name="confirmadminpass" value="<?php echo (get_cookie_data('adminpass') && !$pwd_mismatch) ? get_cookie_data('adminpass') : ''; ?>" /></td>
								</tr>
								<tr>
									<td><?php echo translate('email'); ?></td>
									<td><input type="email" name="adminemail" value="<?php echo get_cookie_data('adminemail') ? get_cookie_data('adminemail') : ''; ?>" /></td>
								</tr>
							</table>
							<p><input type="submit" name="adminacc" value="<?php echo translate('continue'); ?> &rarr;" /></p>
						</form>
						<?php
						break;
					case 'brdsettings':
						?>
						<h2><?php echo translate('brdtitle'); ?></h2>
						<form action="install.php" method="post" enctype="multipart/form-data">
							<table border="0">
								<tr>
									<td><?php echo translate('brdtitle'); ?></td>
									<td><input type="text" name="config[board_title]" value="<?php echo get_cookie_data('board_title') ? get_cookie_data('board_title') : ''; ?>" /></td>
								</tr>
							</table>
							<p><input type="submit" name="brdsettings" value="<?php echo translate('continue'); ?> &rarr;" /></p>
						</form>
						<?php
						break;
					case 'confirm':
						?>
						<h2><?php echo translate('confirmation'); ?></h2>
						<p><?php echo translate('confirmintro'); ?></p>
						<p><?php echo translate('installdetails'); ?></p>
						<table border="0">
							<tr>
								<td><?php echo translate('dbtype'); ?></td>
								<td><?php echo get_db_info(get_cookie_data('dbtype')); ?></td>
							</tr>
							<tr>
								<td><?php echo translate('dbhost'); ?></td>
								<td><?php echo get_cookie_data('dbhost'); ?></td>
							</tr>
							<tr>
								<td><?php echo translate('dbuser'); ?></td>
								<td><?php echo get_cookie_data('dbuser'); ?></td>
							</tr>
							<tr>
								<td><?php echo translate('dbpwd'); ?></td>
								<td><em><?php echo translate('notdisplayed'); ?></em></td>
							</tr>
							<tr>
								<td><?php echo translate('dbname'); ?></td>
								<td><?php echo get_cookie_data('dbname'); ?></td>
							</tr>
							<tr>
								<td><?php echo translate('dbprefix'); ?></td>
								<td><?php echo get_cookie_data('dbprefix'); ?></td>
							</tr>
							<tr>
								<td><?php echo translate('baseurl'); ?></td>
								<td><?php echo get_cookie_data('baseurl'); ?></td>
							</tr>
							<tr>
								<td><?php echo translate('baseurlpath'); ?></td>
								<td><?php echo get_cookie_data('basepath'); ?></td>
							</tr>
							<tr>
								<td><?php echo translate('adminusername'); ?></td>
								<td><?php echo get_cookie_data('adminusername'); ?></td>
							</tr>
							<tr>
								<td><?php echo translate('adminpwd'); ?></td>
								<td><em><?php echo translate('notdisplayed'); ?></em></td>
							</tr>
							<tr>
								<td><?php echo translate('adminemail'); ?></td>
								<td><?php echo get_cookie_data('adminemail'); ?></td>
							</tr>
							<tr>
								<td><?php echo translate('brdtitle'); ?></td>
								<td><?php echo get_cookie_data('board_title'); ?></td>
							</tr>
						</table>
						<form action="install.php" method="post" enctype="multipart/form-data">
							<p><input type="submit" name="start" value="<?php echo translate('modify'); ?>" /> <input type="submit" name="install" value="<?php echo translate('install'); ?>" /></p>
						</form>
						<?php
						break;
					case 'complete':
						?>
						<h2><?php echo translate('installcomplete'); ?></h2>
						<p><?php echo translate('testout1'); ?><a href="<?php echo get_cookie_data('baseurl'); ?>" target="_blank"><?php echo translate('clickhere'); ?></a><?php echo translate('testout2'); ?></p>
                        <ol>
                        	<li><?php echo translate('downloadxml'); ?></li>
                            <?php if (strstr($_SERVER['SERVER_SOFTWARE'], 'Apache')) {
                            	echo translate('apachemsg');
                        	} else {
                            	echo translate('noapachemsg');
                            } ?>
                        </ol>
						<p style="font-size:30px"><a href="install.php?downloadconfigxml"><?php echo translate('xmllink'); ?></a></p>
                        <?php 
						if (strstr($_SERVER['SERVER_SOFTWARE'], 'Apache')) { 
						?>
                        	<p style="font-size:30px"><a href="install.php?downloadhtaccess"><?php echo translate('htalink'); ?></a></p>
						<?php
						} else if (strstr($_SERVER['SERVER_SOFTWARE'], 'nginx')) {
							?>
                            <p><?php echo translate('addtonginx'); ?></p>
          					<pre>
location / { 
    rewrite ^(.*)$ /dispatcher.php; 
}
                            </pre>
                            <?php
						}
						break;
					default:
						echo '<p>' . translate('weirderror') . '</p>';
				}
				?>
			</div>
		</div>
	</body>
</html>