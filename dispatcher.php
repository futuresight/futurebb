<?php
/*
***** FutureBB Development Framework *****
Copyright (C)2012-2015 FutureSight Technologies - ALL RIGHTS RESERVED
See README.txt for more license details

***** DISPATCHER.PHP *****
All HTTP requests made to FutureBB (except those to static files and the installer) are redirected to this file.
This file handles the requests and sends them off to the appropriate unit or script.

Please note that in order to allow other software to interface with FutureBB, much of the key startup functions and variable initializations take place in app_resources/includes/startup.php

Variables created in this file and startup.php may be used globally in the rest of the forum.
These include:

TYPE			NAME					NOTES
array			base_config				Basic persistent configuration (stored in config.xml)
array			db_info					SQL Database settings
array			futurebb_config			Miscellaneous site configuration (stored in database)
array			futurebb_user			Information about the user from database
Database Object	db						The object representing the database (database platform-independent)

*/

$start_time = microtime();

error_reporting(E_ALL);

define('FORUM_ROOT', dirname(__FILE__));
include FORUM_ROOT . '/app_resources/includes/startup.php';

if (!isset($futurebb_config['db_version']) || $futurebb_config['db_version'] < DB_VERSION) {
	//outdated database, upgrade is needed
	include FORUM_ROOT . '/app_resources/database/upgrades/db_upgrade.php';
	$db->close();
	die;
}

// Get the list of pages
$page_info = false;
if (!file_exists(FORUM_ROOT . '/app_config/cache/pages.php')) {
	CacheEngine::CachePages();
}
include FORUM_ROOT . '/app_config/cache/pages.php';


$path = strtok($_SERVER['REQUEST_URI'], '?');
$path = preg_replace('%^' . preg_quote($base_config['basepath']) . '%', '', $path);
$dirs = explode('/', $path);

if (array_key_exists(rtrim($path, '/'), $pages)) {
	$page_info = $pages[rtrim($path, '/')];
} else if ($path == '/') {
	$page_info = $pages['/'];
}

if ($path == '/favicon.ico') {
	header('Content-type: image/ico');
	readfile(FORUM_ROOT . '/static/favicon.ico');
	die;
}
if (isset($dirs[1]) && $dirs[1] == 'static') {
	$types = array("png" => "image/png", "svg" => "application/svg+xml");
	header('Content-Type: '.$types[pathinfo($path, PATHINFO_EXTENSION)]);
	readfile(FORUM_ROOT . $path);
	die;
}

if (!$page_info) {
	// Are there subdir pages with that name?
	foreach ($pagessubdirs as $key => $val) {
		if (strpos($path, $key) === 0) {
			$page_info = $val;
		}
	}
}
	
if (!$page_info) {
	// It's not a system page, so does the forum exist?
	$result = $db->query('SELECT id,redirect_id FROM `#^forums` WHERE url=\'' . $db->escape($dirs[1]) . '\'') or error('Failed to check if forum exists', __FILE__, __LINE__, $db->error());
	if ($db->num_rows($result)) {
		if (!isset($dirs[2]) || $dirs[2] == '') {
			$forum_info = $db->fetch_assoc($result);
			if ($forum_info['redirect_id']) {
				$result = $db->query('SELECT url FROM `#^forums` WHERE id=' . $forum_info['redirect_id']) or error('Failed to find redirect', __FILE__, __LINE__, $db->error());
				list($url) = $db->fetch_row($result);
				redirect($base_config['baseurl'] . '/' . $url . '/' . $dirs[2]);
			}
			$page_info = array('file' => 'viewforum.php', 'template' => true, 'nocontentbox' => true);
		}
		if (isset($dirs[2]) && $dirs[2] != '') {
			$page_info = array('file' => 'viewtopic.php', 'template' => true, 'nocontentbox' => true);
		}
	} else {
		$page_info = false;
	}
}

//check if user is banned
$result = $db->query('SELECT 1 FROM `#^bans` WHERE (username=\'' . $db->escape($futurebb_user['username']) . '\' OR ip=\'' . $db->escape($_SERVER['REMOTE_ADDR']) . '\') AND (expires>' . time() . ' OR expires IS NULL)') or error('Failed to check for bans', __FILE__, __LINE__, $db->error());
if ($db->num_rows($result) && isset($dirs[1]) && $dirs[1] != 'styles' && $dirs[1] != 'login' && $dirs[1] != 'logout') {
	$ban_type = 'ban';
	$page_info = array('file' => 'banned.php', 'template' => true);
}

//check if user is in a group not allowed to access the board
if (!$futurebb_user['g_access_board'] && isset($dirs[1]) && $dirs[1] != 'login' && $dirs[1] != 'register' && $dirs[1] != 'styles') {
	$ban_type = 'no_guest';
	$page_info = array('file' => 'banned.php', 'template' => true);
}

if ($page_info) {
	// If we have valid page info, include the page
	ob_start();
	if (!ctype_alnum($futurebb_user['style'])) {
		$futurebb_user['style'] = 'default';
	}
	if ($page_info['template']) {
		if (file_exists(FORUM_ROOT . '/app_config/templates/' . $futurebb_user['style'] . '/header.php')) {
			include FORUM_ROOT . '/app_config/templates/' . basename($futurebb_user['style']) . '/header.php';
		} else {
			include FORUM_ROOT . '/app_resources/includes/header.php';
		}
	}
	include FORUM_ROOT . '/app_resources/pages/' . $page_info['file'];
	if ($page_info['template']) {
		if (file_exists(FORUM_ROOT . '/app_config/templates/' . $futurebb_user['style'] . '/footer.php')) {
			include FORUM_ROOT . '/app_config/templates/' . basename($futurebb_user['style']) . '/footer.php';
		} else {
			include FORUM_ROOT . '/app_resources/includes/footer.php';
		}
	}
	$page_contents = ob_get_contents();
	$page_contents = str_replace('<$page_title/>', htmlspecialchars($page_title), $page_contents);
	if (isset($breadcrumbs)) {
		$str = '';
		$first = true;
		foreach ($breadcrumbs as $name => $url) {
			if (!$first) {
				$str .= ' &raquo; ';
			}
			$first = false;
			if ($url == '!nourl!') {
				$str .= htmlspecialchars($name);
			} else {
				$str .= '<a href="' . $base_config['baseurl'] . '/' . $url . '">' . htmlspecialchars($name) . '</a>';
			}
		}
		if (isset($rss_url)) {
			$str .= ' (<a href="' . $base_config['baseurl'] . '/' . $rss_url . '">' . translate('rssfeed') . '</a>)';
		}
		$page_contents = str_replace('<$breadcrumbs/>', '<p>' . $str . '</p>', $page_contents);
		unset($first);
	} else {
		$page_contents = str_replace('<$breadcrumbs/>', '', $page_contents);
	}
	if (isset($other_head_stuff)) {
		$page_contents = str_replace('<$other_head_stuff/>', implode("\t\n", $other_head_stuff), $page_contents);
	} else {
		$page_contents = str_replace('<$other_head_stuff/>', '', $page_contents);
	}
	ob_end_clean();
} else {
	// No valid page info, display 404 not found
	httperror(404);
}
if (isset($base_config['debug']) && $base_config['debug'] == 'on') {
	$end_time = microtime();
	$time_diff = round($end_time - $start_time, 5);
	$page_contents = str_replace('<$debug_info/>', '<p style="text-align:center">[Page generated in ' . $time_diff . ' seconds]</p>', $page_contents);
} else {
	$page_contents = str_replace('<$debug_info/>', '', $page_contents);
}
echo $page_contents;

if ($futurebb_user['id'] != 0) {
	$db->query('UPDATE `#^users` SET last_page_load=' . time() . ' WHERE id=' . $futurebb_user['id']) or error('Failed to update online status', __FILE__, __LINE__, $db->error());
}

$db->close();
