<?php
// ***** Global functions for FutureBB ***** //

// Clears the output buffer and displays a generic PHP error page
function error($text, $file = null, $line = null, $db_error = null) {
	//database error
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
		echo '<h2>' . translate('maintenance') . '</h2><p>' . translate('maintintro') . '<br /><b>' . $futurebb_config['maintenance_message'] . '</b></p>';
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
	$page_contents = str_replace('<$addl_head_stuff/>', '', $page_contents);
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
	header('Expires: Thu, 21 Jul 1977 07:30:00 GMT'); // When yours truly first set eyes on this world! :)
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
function load_db_config() {
	global $futurebb_config, $db;
	$result = $db->query('SELECT c_name,c_value FROM `#^config`') or error('Failed to load config', __FILE__, __LINE__, $db->error());
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
	global $futurebb_user;
	$unix_stamp += intval($futurebb_user['timezone']) * 3600;
	if ($date_only) {
		return gmdate('d M y', $unix_stamp);
	} else {
		return gmdate('d M y H:i', $unix_stamp);
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
			if($notifs_raw['type'] == 'warning') {
				$contents_raw = translate('user_sent_warning', '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($notifs_raw['arguments']) . '">' . htmlspecialchars($notifs_raw['arguments']) . '</a>') . '<br />' . $notifs_raw['contents'];
			} elseif($notifs_raw['type'] == 'msg') {
				$contents_raw = translate('user_sent_msg', '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($notifs_raw['arguments']) . '">' . htmlspecialchars($notifs_raw['arguments']) . '</a>') . '<br />' . $notifs_raw['contents'];
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
			if (strpos($url, $str) === 0) {
				$str = '0' . $str;
			}
		}		
		
		foreach ($pagessubdirs as $url => $val) {
			if (strpos($url, '/' . $str) === 0) {
				$str = '0' . $str;
			}
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
		include FORUM_ROOT . '/app_config/pages.php';
		if (!is_array($details)) {
			trigger_error('Illegal arguments given to add_page: must be array', E_USER_ERROR);
		}
		$pages[$url] = $details;
		file_put_contents(FORUM_ROOT . '/app_config/pages.php', '<?php' . "\n" . '$pages = ' . var_export($pages, true) . ';' . "\n" . '$pagessubdirs = ' . var_export($pagessubdirs, true) . ';');
	}
	static function remove_page($url) {
		global $pages, $pagessubdirs;
		unset($pages[$url]);
		file_put_contents(FORUM_ROOT . '/app_config/pages.php', '<?php' . "\n" . '$pages = ' . var_export($pages, true) . ';' . "\n" . '$pagessubdirs = ' . var_export($pagessubdirs, true) . ';');
	}
	static function add_admin_menu($title, $url, $mod = false) {
		include FORUM_ROOT . '/app_config/admin_pages.php';
		$admin_pages[$url] = $title;
		if ($mod) {
			$mod_pages[$url] = $title;
		}
		file_put_contents(FORUM_ROOT . '/app_config/admin_pages.php', '<?php' . "\n" . '$admin_pages = ' . var_export($admin_pages, true) . ';' . "\n" . '$mod_pages = ' . var_export($mod_pages, true) . ';');
	}
	static function add_language_key($key, $text, $language = 'English') {
		if (!file_exists(FORUM_ROOT . '/app_config/langs/' . $language . '/main.php')) {
			trigger_error('Illegal argument: $language is not a valid language', E_USER_ERROR);
		}
		$lang_data = file_get_contents(FORUM_ROOT . '/app_config/langs/' . $language . '/main.php');
		$lines = explode("\n", $lang_data);
		foreach ($lines as $lineno => $line) {
			if (trim($line) == '//extensions') {
				$lines = array_move($lines, $lineno + 1, 1);
				$lines[$lineno + 1] = "\t" . '\'' . $key . '\' => \'' . addslashes($text) . '\',';
				break;
			}
		}
		file_put_contents(FORUM_ROOT . '/app_config/langs/' . $language . '/main.php', implode("\n", $lines));
	}
}

function translate() {
	global $futurebb_user, $base_config;
	static $lang;
	if (!isset($lang)) {
		if (!file_exists(FORUM_ROOT . '/app_config/langs/' . basename($futurebb_user['language']) . '/main.php')) {
			error('Invalid language file specified');
		}
		include FORUM_ROOT . '/app_config/langs/' . basename($futurebb_user['language']) . '/main.php';
	}
	if (func_num_args() == 0) {
		trigger_error('A text string was not provided to the translate function', E_USER_ERROR);
	}
	$args = func_get_args();
	if ($args[0] == '<addfile>') {
		include FORUM_ROOT . '/app_config/langs/' . basename($futurebb_user['language']) . '/' . $args[1] . '.php';
		$lang = array_merge($lang, $lang_addl);
	}
	if (!isset($lang[$args[0]])) {
		return 'Translator error: ' . $args[0] . ' is not a valid language key';
	}
	$returnstr = $lang[$args[0]];
	if (func_num_args() > 1) {
		unset($args[0]);
		foreach ($args as $key => $arg) {
			$returnstr = str_replace('$' . $key, $arg, $returnstr);
		}
	}
	return $returnstr;
}