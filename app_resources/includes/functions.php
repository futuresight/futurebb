<?php
// ***** Global functions for FutureBB ***** //

// Clears the output buffer and displays a generic PHP error page
function error($text, $file = null, $line = null, $db_error = null) {
	//database error
	// Send no-cache headers
	header('Expires: Mon, 1 Jan 1990 00:00:00 GMT');
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: post-check=0, pre-check=0', false);
	header('Pragma: no-cache'); // For HTTP/1.0 compatibility
	@ob_end_clean();
	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>FutureBB Error</title>
	</head>
	<body>
			
		<p><?php echo $text; ?></p>
		<?php if ($file != null) { ?><p>In file <i><?php echo str_replace(FORUM_ROOT, '[ROOT]', $file); ?></i> on line <b><?php echo $line; ?></b>.</p><?php } ?>
		<?php if ($db_error != null) { ?><p>The database reported: <b><?php echo $db_error; ?></b>.</p><?php } ?>
	</body>
	<?php
	die;
}

function enhanced_error($text, $db_error = false) {
	global $db;
	// Send no-cache headers
	header('Expires: Mon, 1 Jan 1990 00:00:00 GMT');
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: post-check=0, pre-check=0', false);
	header('Pragma: no-cache'); // For HTTP/1.0 compatibility
	//database error with enhanced debugging
	@ob_end_clean();
	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>FutureBB Error</title>
		<style type="text/css">
		body {
			font-family:Arial;
		}
		#content {
			margin-left: 20%;
			margin-right: 20%;
			margin-top:10%;
			border: 3px solid #000;
			padding:0px;
		}
		h1 {
			background-color:#000;
			color:#FFF;
			margin-top:0px;
			padding-left:10px;
		}
		#debuginfo {
			padding-left:3px;
		}
		</style>
	</head>
	<body>
		<div id="content">
			<h1>Error</h1>
            <div id="debuginfo">
                <p id="errormsg"><?php echo $text; ?></p>
                <h2>Debug information</h2>
                <table border="0">
                    <tr>
                        <th>File</th>
                        <th>Line</th>
                        <th>Function</th>
                    </tr>
                <?php
                $debug = debug_backtrace();
                foreach ($debug as $key => $val) {
                    echo '<tr><td>' . str_replace(FORUM_ROOT, '<i>[ROOT]</i>', $val['file']) . '</td><td>' . $val['line'] . '</td><td>' . $val['function'] . '</tr>';
                }
                ?>
                </table>
                <?php if ($db_error && isset($db) && $db->error() != null) { ?><p>The database reported: <b><?php echo $db->error(); ?></b>.</p><?php } ?>
            </div>
		</div>
	</body>
</html>
	<?php
	die;
}

// Clears output buffer and displays a standard HTTP Error page based on parameters passed
function httperror($errorcode) {
	global $db, $futurebb_user, $base_config, $futurebb_config;
	@ob_end_clean();
	ob_start();
	$page_info = array('file' => null);
	include FORUM_ROOT . '/app_resources/includes/header.php';
	switch($errorcode) {
	  case 403:
		include FORUM_ROOT . '/app_resources/errorpages/403.php';
		break;
	  case 404:
		include FORUM_ROOT . '/app_resources/errorpages/404.php';
		break;
	  case 'maint':
	  	$page_title = translate('maintenance');
		echo '<h2>' . translate('maintenance') . '</h2><p>' . translate('maintintro') . '<br /><b>' . $futurebb_config['maintenance_message'] . '</b><br />' . translate('maintintro2') . '</p>';
	  	break;
	  default:
		$page_title = $errorcode . ' ' . translate('Error');
		echo translate('genericerror', $errorcode);
		break;
	}
	include FORUM_ROOT . '/app_resources/includes/footer.php';
	$page_contents = ob_get_contents();
	ob_end_clean();
	$page_contents = str_replace('<$page_title/>', $page_title, $page_contents);
	$page_contents = str_replace('<$breadcrumbs/>', '', $page_contents);
	$page_contents = str_replace('<$debug_info/>', '', $page_contents);
	$page_contents = str_replace('<$other_head_stuff/>', '', $page_contents);
	header('Content-type: text/html');
	echo $page_contents;
	$db->close();
	die;
}

//redirect user
function redirect($url) {
	global $db;
	if (ob_get_contents()) {
		ob_end_clean();
	}
	
	// Send no-cache headers
	header('Expires: Mon, 1 Jan 1990 00:00:00 GMT');
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: post-check=0, pre-check=0', false);
	header('Pragma: no-cache'); // For HTTP/1.0 compatibility
	
	// Send the Content-type header in case the web server is setup to send something else
	header('Content-type: text/plain');
	
	header('HTTP/1.1 301 Moved');
	header('Location: ' . $url);
	
	echo 'Redirecting...' . "\n" . 'You are being redirected to the following URL:' . "\n" . $url;
	
	$db->close();
	die;
}

// Hash text using standard FutureBB hashing
function futurebb_hash($text) {
	return sha1($text);
}

// Load volatile configuration from the database
function load_db_config($extra = false) {
	global $futurebb_config, $db;
	$error = false;
	$result = @$db->query('SELECT c_name,c_value FROM `#^config` WHERE load_extra=' . ($extra ? '1' : '0')) or $error = true;
	if ($error) {
		$result = $db->query('SELECT c_name,c_value FROM `#^config`') or enhanced_error('Failed to load configuration', true);
	}
	while (list($key, $val) = $db->fetch_row($result)) {
		$futurebb_config[$key] = $val;
	}
}

// Set a configuration value
function set_config($c_name, $c_value) {
	global $futurebb_config, $db;
	if (isset($futurebb_config[$c_name])) {
		$db->query('UPDATE `#^config` SET c_value = \'' . $db->escape($c_value) . '\' WHERE c_name = \'' . $c_name . '\'') or error('Failed to insert config value', __FILE__, __LINE__, $db->error());
	} else {
		$db->query('INSERT INTO `#^config`(c_name,c_value) VALUES(\'' . $db->escape($c_name) . '\',\'' . $db->escape($c_value) . '\')') or error('Failed to insert new config value', __FILE__, __LINE__, $db->error());
	}
	$futurebb_config[$c_name] = $c_value;
}

// Return a formatted date/time following user preferences and time zone
function user_date($unix_stamp, $date_only = false) {
	global $futurebb_user, $futurebb_config;
	static $timezone;
	if (!isset($timezone)) {
		$timezones = DateTimeZone::listIdentifiers();
		if (isset($timezones[$futurebb_user['timezone']])) {
			$timezone = $timezones[$futurebb_user['timezone']];
		} else {
			$timezone = 'UTC';
		}
		unset($timezones);
	}
	//$unix_stamp += intval($futurebb_user['timezone']) * 3600;
	//print_r(DateTimeZone::listIdentifiers($futurebb_user['timezone']));
	
	$date = new DateTime('@' . $unix_stamp);
	$date->setTimezone(new DateTimeZone($timezone));
	if ($date_only) {
		return $date->format($futurebb_config['date_format']);
	} else {
		return $date->format($futurebb_config['date_format'] . ' ' . $futurebb_config['time_format']);
	}
}

// CLASS: the login system that controls FutureBB
abstract class LoginController {
	public static $randid;
	
	// Send an encoded cookie to the client using these details
	static function LogInUser($id, $password, $useragent, $remember = false) {
		global $base_config;
		if ($remember) {
			$expire = time() + 60 * 60 * 24 * 60;
		} else {
			$expire = 0;
		}
		$cookie = base64_encode($id . chr(1) . futurebb_hash($password . $useragent) . chr(1) . rand(1, 5000));
		setcookie($base_config['cookie_name'], $cookie, $expire, '/');
	}
	
	// Simply return the static variable $randid used for spam prevention
	static function GetRandId() {
		return self::$randid;
	}
	
	// Get the raw, non-sanitised username from a user ID
	static function GetUsername($user_id) {
		
	}
	
	// Authenticate the cookie against the database and load user info to array
	static function CheckCookie(&$futurebb_user) {
		global $base_config, $db;
		$futurebb_user = array();
		if (isset($_COOKIE[$base_config['cookie_name']])) {
			$cookie = base64_decode($_COOKIE[$base_config['cookie_name']]);
		} else {
			self::Guest(); return;
		}
		$parts = explode(chr(1), $cookie);
		if (sizeof($parts) < 3) {
			self::Guest(); return;
		} else {
			$id = intval($parts[0]);
			$hash = $parts[1];
			self::$randid = $parts[2];
		}
		$result = $db->query('SELECT u.*,g.* FROM `#^users` AS u LEFT JOIN `#^user_groups` AS g ON g.g_id=u.group_id WHERE u.id=' . $id) or error('Failed to check user', __FILE__, __LINE__, $db->error());
		$user_info = $db->fetch_assoc($result);
		if ($id != 0) {
			if ($hash != futurebb_hash($user_info['password'] . $_SERVER['HTTP_USER_AGENT'])) {
				self::Guest(); return;
			}
			$futurebb_user = $user_info;
			self::LoadNotifications();
			self::CheckPromotion();
		} else {
			self::Guest(); return;
		}
	}
	
	static function Guest() {
		global $db, $futurebb_user, $futurebb_config;
		$result = $db->query('SELECT u.*,g.* FROM `#^users` AS u LEFT JOIN `#^user_groups` AS g ON g.g_guest_group=1 WHERE u.username=\'Guest\'') or error('Failed to check user', __FILE__, __LINE__, $db->error());
		$futurebb_user = $db->fetch_assoc($result);
		$futurebb_user['group_id'] = $futurebb_user['g_id'];
		$futurebb_user['id'] = 0;
		$futurebb_user['language'] = $futurebb_config['default_language'];
		$futurebb_user['notifications'] = array();
		$futurebb_user['notifications_count'] = 0;
	}
	
	static function CheckPromotion() {
		global $futurebb_user, $db;
		if ($futurebb_user['g_promote_group'] == 0) {
			return;
		}
		$num_posts = $futurebb_user['num_posts'];
		$days_registered = floor((time() - $futurebb_user['registered']) / 60 / 60 / 24);
		if ($futurebb_user['g_promote_operator'] == 1) {
			$promote = ($num_posts > $futurebb_user['g_promote_posts'] && $days_registered > $futurebb_user['g_promote_days']);
		} else {
			$promote = ($num_posts > $futurebb_user['g_promote_posts'] || $days_registered > $futurebb_user['g_promote_days']);
		}
		if ($promote) {
			$db->query('UPDATE `#^users` SET group_id=' .  $futurebb_user['g_promote_group'] . ' WHERE id=' . $futurebb_user['id']) or error('Failed to update user group', __FILE__, __LINE__, $db->error());
		}
	}
	
	static function LoadNotifications() {
		// Select notifications from the database and put them into the user variable
		global $futurebb_user, $db, $base_config;
		$futurebb_user['notifications'] = array();
		// The following is the standard format for the array containing user notifications
		// (string $type, int $send_time, string $contents)
		// type: warning, msg, notification
		//
		// Arguments for type 'warning':
		//  issuer ID
		// Arguments for type 'msg':
		//  sender ID
		// Arguments for type 'notification':
		//  poster ID (contents = post ID where user was mentioned)
		
		$result = $db->query('SELECT id, type, send_time, read_time, contents, arguments FROM `#^notifications` WHERE user=' . $futurebb_user['id'] . ' ORDER BY send_time DESC LIMIT 100') or error('Failed to load user notifications', __FILE__, __LINE__, $db->error());
		
		// Load out into array and translate where needed
		while ($notifs_raw = $db->fetch_assoc($result)) {
			$sender = '';
			if($notifs_raw['type'] == 'warning') {
				$contents_raw = translate('user_sent_warning', '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($notifs_raw['arguments']) . '">' . htmlspecialchars($notifs_raw['arguments']) . '</a>') . '<br />' . $notifs_raw['contents'];
				$sender = $notifs_raw['arguments'];
			} elseif($notifs_raw['type'] == 'msg') {
				$contents_raw = translate('user_sent_msg', '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($notifs_raw['arguments']) . '">' . htmlspecialchars($notifs_raw['arguments']) . '</a>') . '<br />' . $notifs_raw['contents'];
				$sender = $notifs_raw['arguments'];
			} elseif($notifs_raw['type'] == 'notification') {
				$parts = explode(',', $notifs_raw['arguments'], 2);
				$contents_raw = translate('user_mentioned_you', '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($parts[0]) . '">' . htmlspecialchars($parts[0]) . '</a>') .
					'<a href="' . $base_config['baseurl'] . '/posts/' . $notifs_raw['contents'] . '">' . htmlspecialchars($parts[1]) . '</a>';
			} else {
				$contents_raw = translate('couldnot_display_notif');
			}
			$futurebb_user['notifications'][] = array('type' => $notifs_raw['type'],
				'id' => $notifs_raw['id'],
				'send_time' => $notifs_raw['send_time'],
				'read_time' => $notifs_raw['read_time'],
				'sender' => $sender,
				'contents' => $contents_raw);
		}
		
		
		// Check for unread notifications
		$futurebb_user['notifications_count'] = $db->num_rows($db->query('SELECT type, send_time, contents FROM `#^notifications` WHERE user=' . $futurebb_user['id'] . ' AND read_time = 0'));
		
	}
}

abstract class URLEngine {
	static function make_friendly($str) {
		global $pages, $pagessubdirs;
		//borrowed from the "Friendly URL" FluxBB mod. See https://fluxbb.org/resources/mods/friendly-url/ for more information.
		include FORUM_ROOT . '/app_resources/includes/lang_url_replace.php';
	
		$forum_reserved_strings = array();
	
		$str = strtr($str, $lang_url_replace);
		$str = strtolower(utf8_decode($str));
		$str = trim(preg_replace(array('/[^a-z0-9\s]/', '/\s+/'), array('', '-'), $str), '-');
	
		foreach ($forum_reserved_strings as $match => $replace)
			if ($str == $match)
				return $replace;
		
		foreach ($pages as $url => $val) {
			$parts = explode('/', $url);
			if (strlen($url) > 1 && $url{1} != '/' && isset($parts[1]) && strpos($str, $parts[1]) === 0) {
				$str = '0-' . $str;
				break;
			}
		}		
		
		foreach ($pagessubdirs as $url => $val) {
			$parts = explode('/', $url);
			if (strlen($url) > 1 && $url{1} != '/' && isset($parts[1]) && strpos($str, $parts[1]) === 0) {
				$str = '0-' . $str;
				break;
			}
		}
		
		if ($str == 'static') {
			$str = '0-static';
		}
		
		return $str;
	}
}

function censor($text) {
	global $futurebb_config;
	static $find, $replace;
	if (!isset($find)) {
		$censoring = base64_decode($futurebb_config['censoring']);
		$entries = explode("\n", $censoring);
		foreach ($entries as $val) {
			$data = explode(chr(1), $val);
			if (sizeof($data) > 1) {
				$find[] = $data[0];
				$replace[] = $data[1];
			}
		}
	}
	return str_replace($find, $replace, $text);
}

function array_move($array, $start, $count) {
	$size = sizeof($array);
	$newarray = $array;
	for ($i = $start; $i < $size; $i++) {
		$newarray[$i + $count] = $array[$i];
	}
	for ($i = $start; $i < $start + $count; $i++) {
		$newarray[$i] = '';
	}
	return $newarray;
}

abstract class ExtensionConfig {
	static function add_page($url, array $details) {
		global $db;
		$db->query('INSERT INTO `#^pages`(url,file,template,nocontentbox,admin,moderator,subdirs) VALUES(\'' . $db->escape($url) . '\',\'' . $db->escape($details['file']) . '\',' . (isset($details['template']) && $details['template'] ? '1' : '0') . ',' . (isset($details['nocontentbox']) && $details['nocontentbox'] ? '1' : '0') . ',' . (isset($details['admin']) && $details['admin'] ? '1' : '0') . ',' . (isset($details['mod']) && $details['mod'] ? '1' : '0') . ',' . (isset($details['subdirs']) && $details['subdirs'] ? '1' : '0') . ')') or enhanced_error('Failed to add page to database', true);
		
		CacheEngine::CachePages();
	}
	static function remove_page($url) {
		global $db;
		$db->query('DELETE FROM `#^pages` WHERE url=\'' . $db->escape($url) . '\'') or enhanced_error('Failed to remove page from database', true);
		
		CacheEngine::CachePages();
	}
	static function add_admin_menu($title, $url, $mod = false) {
		global $futurebb_config;
		$lines = explode("\n", base64_decode($futurebb_config['admin_pages']));
		$lines[] = $url . '=>' . $title;
		set_config('admin_pages', base64_encode(implode("\n", $lines)));
		
		if ($mod) {
			$lines = explode("\n", base64_decode($futurebb_config['mod_pages']));
			$lines[] = $url . '=>' . $title;
			set_config('mod_pages', base64_encode(implode("\n", $lines)));
		}
		if (file_exists(FORUM_ROOT . '/cache/admin_pages.php')) { //clear the cache
			unlink(FORUM_ROOT . '/cache/admin_pages.php');
		}
	}
	static function remove_admin_menu($url) {
		global $futurebb_config;
		$lines = explode("\n", base64_decode($futurebb_config['admin_pages']));
		foreach ($lines as $key => $line) {
			if (strpos($line, $url . '=>') === 0) {
				unset($lines[$key]);
			}
		}
		set_config('admin_pages', base64_encode(implode("\n", $lines)));
		
		$lines = explode("\n", base64_decode($futurebb_config['mod_pages']));
		foreach ($lines as $key => $line) {
			if (strpos($line, $url . '=>') === 0) {
				unset($lines[$key]);
			}
		}
		set_config('mod_pages', base64_encode(implode("\n", $lines)));
		if (file_exists(FORUM_ROOT . '/cache/admin_pages.php')) { //clear the cache
			unlink(FORUM_ROOT . '/cache/admin_pages.php');
		}
	}
	static function add_language_key($key, $text, $language = 'English') {
		$q = new DBInsert('language', array('language' => $language, 'langkey' => $key, 'value' => $text, 'category' => 'main'), 'Failed to insert language key');
		$q->commit();
		
		//clear the cache
		$maindir = FORUM_ROOT . '/app_config/cache/language/' . $language;
		if (file_exists($maindir) && is_dir($maindir)) {
			$handle = opendir($maindir);
			while ($file = readdir($handle)) {
				if ($file != '.' && $file != '..') {
					unlink($maindir . '/' . $file);
				}
			}
		}
	}
	static function remove_language_key($key, $language = 'English') {
		$q = new DBDelete('language', 'language=\'' . $db->escape($language) . '\' AND langkey=\'' . $db->escape($key) . '\'', 'Failed to delete langauge key');
		$q->commit();
		
		//clear the cache
		$maindir = FORUM_ROOT . '/app_config/cache/language/' . $language;
		if (file_exists($maindir) && is_dir($maindir)) {
			$handle = opendir($maindir);
			while ($file = readdir($handle)) {
				if ($file != '.' && $file != '..') {
					unlink($maindir . '/' . $file);
				}
			}
		}
	}
}

function translate() {
	global $futurebb_user, $base_config;
	static $lang;
	if (!isset($lang)) {
		//is there a cache file present? if not, we need to make one
		if (!file_exists(FORUM_ROOT . '/app_config/cache/language/' . basename($futurebb_user['language']) . '/main.php')) {
			CacheEngine::CacheLanguage();
		}
		
		//is there still not a cache file present? then we don't have a valid language
		if (!file_exists(FORUM_ROOT . '/app_config/cache/language/' . basename($futurebb_user['language']) . '/main.php')) {
			error('Invalid language file specified');
		}
		include FORUM_ROOT . '/app_config/cache/language/' . basename($futurebb_user['language']) . '/main.php';
	}
	if (func_num_args() == 0) {
		trigger_error('A text string was not provided to the translate function', E_USER_ERROR);
	}
	$args = func_get_args();
	if ($args[0] == '<addfile>') {
		include FORUM_ROOT . '/app_config/cache/language/' . basename($futurebb_user['language']) . '/' . $args[1] . '.php';
		$lang = array_merge($lang, $lang_addl);
	}
	if (!isset($lang[$args[0]])) {
		return 'Translator error: ' . $args[0] . ' is not a valid language key';
	}
	$returnstr = $lang[$args[0]];
	if (func_num_args() > 1) {
		unset($args[0]);
		foreach ($args as $key => $arg) {
			//before doing basic replacement, do "smart" replacement
			$returnstr = preg_replace_callback('%\<SWITCH \$' . $key . '>\((.*?)\)%', function($matches) use($arg) {
				$options = explode(',', $matches[1]);
				return $options[intval($arg) - 1];
			}, $returnstr);
			$returnstr = preg_replace_callback('%\<PLURAL \$' . $key . '>\((.*?),(.*?)\)%', function($matches) use($arg) {
				if ($arg == 1) {
					return $matches[1];
				} else {
					return $matches[2];
				}
			}, $returnstr);
			$returnstr = preg_replace('%\$' . $key . '([^0-9]|$)%', $arg . '$1', $returnstr);
		}
	}
	return $returnstr;
}

function writable($path) {
	if (is_dir($path)) {
		$rnd = rand(100000, 999999);
		@file_put_contents($path . '/' . $rnd . '.tmp', 'qwertyuiop');
		if (!file_exists($path . '/' . $rnd . '.tmp')) {
			return false;
		}
		unlink($path . '/' . $rnd . '.tmp');
		return true;
	} else {
		$file_exists = file_exists($path);
		$handle = @fopen($path, 'a');
	
		if ($handle === false) {
			return false;
		}
	
		fclose($handle);
	
		if (!$file_exists)
			@unlink($path);
	
		return true;
	}
}

function paginate($url, $page, $count) {
	//paginate links
	$links = array();
	if ($page > 1) {
		$text = str_replace('$page$', $page - 1, $url);
		$text = str_replace('>' . ($page - 1) . '<', '>' . translate('prev') . '<', $text);
		$text = str_replace('$bold$', '', $text);
		$links[] = $text;
	}	
	$text = str_replace('$page$', 1, $url);
	if ($page == 1) {
		$text = str_replace('$bold$', ' style="font-weight:bold"', $text);
	} else {
		$text = str_replace('$bold$', '', $text);
	}
	$links[] = $text;
	if ($page > 4) {
		$links[] = '...';
	}
	for ($i = max($page - 2, 2); $i <= min($page + 2, $count - 1); $i++) {
		$text = str_replace('$page$', $i, $url);
		if ($i == $page) {
			$text = str_replace('$bold$', ' class="bold"', $text);
		} else {
			$text = str_replace('$bold$', '', $text);
		}
		$links[] = $text;
	}
	if ($count > $page + 3) {
		$links[] = '...';
	}
	if ($count > 1) {
		$text = str_replace('$page$', $count, $url);
		if ($page == $count) {
			$text = str_replace('$bold$', ' class="bold"', $text);
		} else {
			$text = str_replace('$bold$', '', $text);
		}
		$links[] = $text;
	}
	if ($page < $count) {
		$text = str_replace('$page$', $page + 1, $url);
		$text = str_replace('>' . ($page + 1) . '<', '>' . translate('next') . '<', $text);
		$text = str_replace('$bold$', '', $text);
		$links[] = $text;
	}
	return implode(' ', $links);
}

abstract class CacheEngine {
	static function CacheHeader() {
		include_once FORUM_ROOT . '/app_resources/includes/cacher/interface.php';
		cache_header();
	}
	
	static function replace_interface_strings($text) {
		//this is for header text, when spitting it out in real time to replace stuff like $username$
		global $futurebb_user;
		$text = str_replace('$username$', $futurebb_user['username'], $text);
		$text = str_replace('$reghash$', futurebb_hash(LoginController::GetRandID()), $text);
		return $text;
	}
	
	static function CachePages() {
		include_once FORUM_ROOT . '/app_resources/includes/cacher/pages.php';
		cache_pages();
	}
	
	static function CacheAdminPages() {
		include_once FORUM_ROOT . '/app_resources/includes/cacher/pages.php';
		cache_admin_pages();
	}
	
	static function CacheLanguage() {
		include_once FORUM_ROOT . '/app_resources/includes/cacher/interface.php';
		cache_language();
	}
}

function update_last_post($topic_id, $forum_id = -1) {
	global $db;
	if ($topic_id != -1) {
		//update topic last post data
		$result = $db->query('SELECT id,posted FROM `#^posts` WHERE topic_id=' . $topic_id . ' AND deleted IS NULL ORDER BY posted DESC') or error('Failed to get new last post', __FILE__, __LINE__, $db->error());
		if ($db->num_rows($result)) {
			list ($last_post_id,$last_post_time) = $db->fetch_row($result);
		} else {
			$last_post_id = 0;
			$last_post_time = 0;
		}
		$db->query('UPDATE `#^topics` SET last_post=' . $last_post_time . ',last_post_id=' . $last_post_id . ' WHERE id=' . $topic_id) or enhanced_error('Failed to update topic last post data', true);
	}
	if ($forum_id != -1) {
		$result = $db->query('SELECT p.id,p.posted FROM `#^posts` AS p LEFT JOIN `#^topics` AS t ON t.id=p.topic_id WHERE p.deleted IS NULL AND t.deleted IS NULL AND t.forum_id=' . $forum_id . ' ORDER BY p.posted DESC') or enhanced_error('Failed to find last post', true);
		if ($db->num_rows($result)) {
			list ($last_post_id,$last_post_time) = $db->fetch_row($result);
		} else {
			$last_post_id = 0;
			$last_post_time = 0;
		}
		$db->query('UPDATE `#^forums` SET last_post=' . $last_post_time . ',last_post_id=' . $last_post_id . ' WHERE id=' . $forum_id) or enhanced_error('Failed to update forum last post data', true);
	}
}

function create_topic($subject, $message, $user, $forum, $hidesmiliespostentry) {
	//creates a new topic, returns the URL of that topic (just the topic, not the forum, so something like "my-great-topic")
	global $futurebb_config, $db;
	$name = URLEngine::make_friendly($subject);
	$base_name = $name;
	//check for forums with the same URL
	$result = $db->query('SELECT url FROM `#^topics` WHERE url LIKE \'' . $db->escape($name) . '%\'') or error('Failed to check for similar URLs', __FILE__, __LINE__, $db->error());
	$urllist = array();
	while (list($url) = $db->fetch_row($result)) {
		$urllist[] = $url;
	}
	$ok = false;
	$add_num = 0;
	while (!$ok) {
		$ok = true;
		if (in_array($name, $urllist)) {
			$add_num++;
			$name = $base_name . '-' . $add_num;
			$ok = false;
		}
	}
	$db->query('INSERT INTO `#^topics`(subject,url,forum_id) VALUES(\'' . $db->escape($subject) . '\',\'' . $db->escape($name) . '\',' . $forum . ')') or error('Failed to create topic', __FILE__, __LINE__, $db->error());
	$tid = $db->insert_id();
	$parsedtext = BBCodeController::parse_msg($message, !$hidesmiliespostentry, $futurebb_config['enable_bbcode']);
	$db->query('INSERT INTO `#^posts`(poster,poster_ip,content,parsed_content,posted,topic_id,disable_smilies) VALUES(' . $user . ',\'' . $db->escape($_SERVER['REMOTE_ADDR']) . '\',\'' . $db->escape($message) . '\',\'' . $db->escape($parsedtext) . '\',' . time() . ',' . $tid . ',' . intval($hidesmiliespostentry) . ')') or error('Failed to make first post<br />' . $q, __FILE__, __LINE__, $db->error());
	$pid = $db->insert_id();
	// Let's take a break to fire any notifications from @ tags
	if($futurebb_config['allow_notifications'] == 1) {
		if(preg_match_all('%@([a-zA-Z0-9_\-]+)%', $parsedtext, $matches)) {
			array_slice($matches[1], 0, 8);
			foreach($matches[1] as $tagged_user) {
				$tagged_res = $db->query('SELECT id, block_notif FROM `#^users` WHERE username = \'' . $tagged_user . '\'') or error('Failed to find users to tag', __FILE__, __LINE__, $db->error());
				if($db->num_rows($tagged_res)) {
					$tagged_id = $db->fetch_assoc($tagged_res);
					if($tagged_id['block_notif'] == 0) {
						$db->query('INSERT INTO `#^notifications` (type, user, send_time, contents, arguments)
						VALUES (\'notification\', ' . intval($tagged_id['id']) . ', ' . time() . ', '. $pid . ', \'' . $futurebb_user['username'] . ',' . $db->escape($subject) . '\')');
					}
				}
			}
		}
	}
	
	// Continue posting
	$db->query('UPDATE `#^topics` SET last_post=' . time() . ',last_post_id=' . $pid . ',first_post_id=' . $pid . ' WHERE id=' . $tid) or error('Failed to update topic info', __FILE__, __LINE__, $db->error());
	$db->query('UPDATE `#^forums` SET last_post=' . time() . ',last_post_id=' . $pid . ',num_posts=num_posts+1,num_topics=num_topics+1 WHERE id=' . $forum) or error('Failed to update forum last post', __FILE__, __LINE__, $db->error());
	$db->query('DELETE FROM `#^read_tracker` WHERE forum_id=' . $forum . ' AND user_id<>' . $user) or error('Failed to update read tracker', __FILE__, __LINE__, $db->error());
	$db->query('UPDATE `#^users` SET num_posts=num_posts+1 WHERE id=' . $user) or error('Failed to update number of posts', __FILE__, __LINE__, $db->error());
	
	update_search_index($pid,$_POST['message']);
	
	return $name;
}

function rename_forum($oldid, $oldurl, $newtitle) {
	global $db;
	//make redirect forum
	$base_name = URLEngine::make_friendly($newtitle);
	$name = $base_name;
	$add_num = 0;
	$result = $db->query('SELECT url FROM `#^forums` WHERE url LIKE \'' . $db->escape($name) . '%\'') or error('Failed to check for similar URLs', __FILE__, __LINE__, $db->error());
	$urllist = array();
	while (list($url) = $db->fetch_row($result)) {
		$urllist[] = $url;
	}
	$ok = false;
	while (!$ok) {
		$ok = true;
		if (in_array($name, $urllist)) {
			$add_num++;
			$name = $base_name . $add_num;
			$ok = false;
		}
	}
	$db->query('UPDATE `#^forums` SET url=\'' . $name . '\',name=\'' . $db->escape($newtitle) . '\' WHERE id=' . intval($oldid)) or error('Failed to update forum URL', __FILE__, __LINE__, $db->error());
	$db->query('INSERT INTO `#^forums`(url,redirect_id) VALUES(\'' . $db->escape($oldurl) . '\',' . $oldid . ')') or error('Failed to insert redirect forum', __FILE__, __LINE__, $db->error());
}

function create_forum($category, $fname, $view, $topics, $replies) {
	global $db;
	//make new forum
	$base_name = URLEngine::make_friendly($fname);
	$name = $base_name;
	$add_num = 0;
	
	//check for forums with the same URL
	$result = $db->query('SELECT url FROM `#^forums` WHERE url LIKE \'' . $db->escape($name) . '%\'') or error('Failed to check for similar URLs', __FILE__, __LINE__, $db->error());
	$urllist = array();
	while (list($url) = $db->fetch_row($result)) {
		$urllist[] = $url;
	}
	$ok = false;
	$add_num = 0;
	while (!$ok) {
		$ok = true;
		if (in_array($name, $urllist)) {
			$add_num++;
			$name = $base_name . $add_num;
			$ok = false;
		}
	}
	$db->query('INSERT INTO `#^forums`(url,name,cat_id,sort_position,view_groups,topic_groups,reply_groups) VALUES(\'' . $db->escape($name) . '\',\'' . $db->escape($fname) . '\',' . intval($category) . ',0,\'-' . implode('-', $view) . '-\',\'-' . implode('-', $topics) . '-\',\'-' . implode('-', $replies) . '-\')') or error('Failed to create new category', __FILE__, __LINE__, $db->error());
}