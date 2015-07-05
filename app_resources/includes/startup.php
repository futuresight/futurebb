<?php
include FORUM_ROOT . '/app_resources/includes/functions.php';
include FORUM_ROOT . '/app_config/sysinfo.php';

// Load configuration from XML file
if (!file_exists(FORUM_ROOT . '/config.xml')) {
	echo '<p><b style="color:#F00">Fatal error</b><br />No config.xml file was found in the forum root directory. If this is because you have not set up your forum, please go to the <a href="install.php">configuration utility</a> and do so.</p>'; die;
	die;
}
libxml_use_internal_errors(true);
$xml = simplexml_load_string(file_get_contents(FORUM_ROOT . '/config.xml'));
if (!$xml) {
	echo '<p><b style="color:#F00">Fatal error</b><br />A parse error was encountered when trying to read config.xml. Please check the file and try again.</p>'; die;
}
foreach ($xml->cfgset as $val) {
	$attribs = $val->attributes();
	if ($attribs['type'] == 'database') {
		$db_xml = $val;
	}
}
$db_info = array();
foreach ($db_xml->children() as $key => $val) {
	$db_info[$key] = (string) $val;
}

foreach ($xml->cfgset as $val) {
	$attribs = $val->attributes();
	if ($attribs['type'] == 'server') {
		$base_config_xml = $val;
	}
}
$base_config = array();
foreach ($base_config_xml->children() as $key => $val) {
	$base_config[$key] = (string) $val;
}
unset($base_config_xml);
unset($db_xml);
unset($xml);

// Set PHP settings for sessions and IO
ini_set('magic_quotes_runtime', 0);

if (ini_get('magic_quotes_gpc') == 'On') {
	function stripslashes_array(&$arr) {
		foreach ($arr as &$val) {
			if (is_array($val)) {
				$val = stripslashes_array($val);
			} else {
				$val = stripslashes($val);
			}
		}
	}
	stripslashes_array($_GET);
	stripslashes_array($_POST);
	stripslashes_array($_COOKIE);
}

// Initialize the database
if (!file_exists(FORUM_ROOT . '/app_resources/database/' . $db_info['type'] . '.php')) {
	echo '<p><b style="color:#F00">Fatal error</b><br />An invalid database type was specified in config.xml. Please check that a driver exists for the type you have specified and try again.</p>'; die;
}
include FORUM_ROOT . '/app_resources/database/' . $db_info['type'] . '.php';
include FORUM_ROOT . '/app_resources/database/db_resources.php';
$db = new Database($db_info);

// Load configuration from the database
$futurebb_config = array();
load_db_config();

// Fire the login controller
$futurebb_user = null;
LoginController::CheckCookie($futurebb_user);

if ($futurebb_config['turn_on_maint'] > 0 && $futurebb_config['turn_on_maint'] < time() && !$futurebb_config['maintenance']) {
	set_config('maintenance', 1);
	set_config('turn_on_maint', 0);
}
if ($futurebb_config['turn_off_maint'] > 0 && $futurebb_config['turn_off_maint'] < time() && $futurebb_config['maintenance']) {
	set_config('maintenance', 0);
	set_config('turn_off_maint', 0);
}
if ($futurebb_config['maintenance'] && !$futurebb_user['g_admin_privs'] && strpos(str_replace($base_config['basepath'], '', $_SERVER['REQUEST_URI']), '/styles') !== 0 && strpos(str_replace($base_config['basepath'], '', $_SERVER['REQUEST_URI']), '/login') !== 0) {
	httperror('maint');
}

if (isset($page_info['admin']) && !$futurebb_user['g_admin_privs']) {
	httperror(403);
}
if (isset($page_info['mod']) && !$futurebb_user['g_mod_privs']) {
	httperror(403);
}
//automatically check for updates
if (ini_get('allow_url_fopen')) {
	if ($futurebb_config['last_update_check'] < time() - 60 * 60 * 24 && !$futurebb_config['new_version']) {
		$version = file_get_contents('http://futuresight.org/api/getversion/futurebb');
		if ($version > FUTUREBB_VERSION) {
			translate('<addfile>', 'admin');
			$q = new DBInsert('reports', array('post_type' => 'special', 'reason' => translate('newversionmsg'), 'time_reported' => time()), 'Failed to insert update notification');
			$q->commit();
			set_config('new_version', 1);
		}
		set_config('last_update_check', time());
	}
}

ExtensionConfig::run_hooks('startup', array());