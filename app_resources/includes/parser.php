<?php
abstract class BBCodeController {
	public static $pattern = array();
	public static $replace = array();
	public static $tags = array();
	public static $smilies = array(
		':D' => 'bigsmile.png',
		'8D' => 'cool.png',
		'8)' => 'cool.png',
		':cool:' => 'cool.png',
		':/' => 'hmm.png',
		':lol:' => 'lol.png',
		':mad:' => 'mad.png',
		'>:(' => 'mad.png',
		':|' => 'neutral.png',
		':roll:' => 'roll.png',
		'-_-' => 'roll.png',
		':rolleyes:' => 'roll.png',
		':(' => 'sad.png',
		':-(' => 'sad.png',
		':)' => 'smile.png',
		':-)' => 'smile.png',
		':P' => 'tongue.png',
		';)' => 'wink.png',
		':O' => 'yikes.png',
		':0' => 'yikes.png'
	);
	
	static function parse_msg($text, $show_smilies = true, $preview = false, $bbcode = true) {
		global $db, $futurebb_user, $futurebb_config;
				
		if ($bbcode && empty(self::$pattern)) {
			self::$pattern = array();
			self::$replace = array();
			self::add_bbcode('%\[b\](.*?)\[/b\]%ms', '<strong>$1</strong>');
			self::add_bbcode('%\[i\](.*?)\[/i\]%ms', '<em>$1</em>');
			self::add_bbcode('%\[u\](.*?)\[/u\]%ms', '<u>$1</u>');
			self::add_bbcode('%\[s\](.*?)\[/s\]%ms', '<del>$1</del>');
			self::add_bbcode('%\[quote\](.*?)\[/quote\]%ms', '</p><div class="quotebox">$1</div><p>');
			self::add_bbcode('%\[quote=(.*?)\](.*?)\[/quote\]%ms', '</p><div class="quotebox"><p><b>$1 ' . translate('wrote') . '</b><br />$2</p></div><p>');
			self::add_bbcode('%\[colou?r=(white|black|red|green|blue|orange|yellow|pink|gray|magenta|#[0-9a-fA-F]{6}|\#[0-9a-fA-F]{3})\](.*?)\[/colou?r\]%m', '<span style="color:$1">$2</span>');
			self::add_bbcode('%\[url=?(.*?)\](.*?)\[/url\]%se', 'self::handle_url_tag(\'$1\',\'$2\');');
			self::add_bbcode('%\[img\](.*?)\[/img\]%se', 'self::handle_img_tag(\'$1\');');
		}
		
		$text = htmlspecialchars($text);

		$text = preg_replace('%\[code\](.*?)\[/code\]%msie', 'self::handle_code_tag(\'$1\', 1);', $text);
		
		// Format @username into tags
		if($futurebb_config['allow_notifications'] == 1) {
			$text = preg_replace('%@([a-zA-Z0-9_\-]+)%', '<span class="usertag">@$1</span>', $text);
		}
		
		if ($bbcode) {
			self::parse_bbcode($text);
		}
		if($show_smilies) { // only parse similies if they were enabled by poster
			self::parse_smilies($text);
		}
		
		$text = preg_replace('%\[code\](.*?)\[/code\]%msie', 'self::handle_code_tag(\'$1\', 2);', $text);
		
		$text = preg_replace('%\[list(=(\*|1))?\](.*?)\[/list\]%msie', 'self::handle_list_tag(\'$2\', \'$3\');', $text);
	
		$text = self::add_line_breaks($text);
		
		$text = censor($text);
		return $text;
	}
	
	static function handle_list_tag($type, $text) {
		if ($type == '') {
			$type = '*';
		}
		//pull out everything in item tags
		$text = preg_replace('%\[\*\](.*?)\[/\*\]%msie', 'self::handle_list_item_tag(\'$1\');', $text);
		
		if ($type == '*') {
			$text = '</p><ul>' . self::handle_list_item_tag('', 1) . '</ul><p>';
		} else if ($type == '1') {
			$text = '</p><ol>' . self::handle_list_item_tag('', 1) . '</ol><p>';
		}
		
		self::handle_list_item_tag('', 2);
		
		return $text;
	}
	
	static function handle_list_item_tag($item, $action = 0) {
		static $items;
		if ($action == 2) {
			$items = array();
		}
		if (!isset($items)) {
			$items = array();
		}
		if ($action == 0) {
			$items[] = $item;
			return '';
		} else if ($action == 1) {
			foreach ($items as &$val) {
				$val = '<li>' . $val . '</li>';
			}
			return implode($items);
		}
	}
	
	static function add_line_breaks($text) {
		$text = str_replace("\r\n", "\n", $text);
		$text = str_replace("\r", '<br />', $text);
		$text = str_replace("\n", '<br />', $text);
		return $text;
	}
	
	static function handle_code_tag($text, $mode) {
		static $i1, $i2, $code_matches;
		//MODES: 1 = extract, 2 = replace
		
		if (!isset($code_matches)) {
			$code_matches = array();
		}
		
		if ($mode == 1) {
			if (!isset($i1)) {
				$i1 = 0;
			}
			$i1++;
			$code_matches[$i1] = $text;
			return '[code]' . $i1 . '[/code]';
		} else if ($mode == 2) {
			if (!isset($i2)) {
				$i2 = 0;
			}
			$i2++;
			return '<div class="quotebox" style="font-family:Courier">' . $code_matches[$i2] . '</div>';
		} else {
			return '';
		}
	}
	
	static function handle_url_tag($v1, $v2) {
		if ($v1 == '') {
			$url = $v2;
			$text = $v2;
		} else {
			$url = $v1;
			$text = $v2;
		}
		if (strpos($url, 'javascript:') === 0) {
			$url = '';
		}
		if (strpos($url, 'http://') === false && strpos($url, 'https://') === false) {
			$url = 'http://' . $url;
		}
		
		return '<a href="' . $url . '">' . $text . '</a>';
	}
	
	static function handle_img_tag($url) {
		if (!preg_match('%^(ht|f)tps?://%', $url)) {
			$url = 'http://' . $url;
		}
		return '<img src="' . $url . '" alt="" />';
	}
	
	static function add_bbcode($find, $repl) {
		self::$pattern[] = $find;
		self::$replace[] = $repl;
	}
	
	static function parse_smilies(&$text) {
		global $base_config;	
		foreach (self::$smilies as $smiley => $url) {
			//thank you FluxBB for the RegEx code
			$text = preg_replace('%(^|(?<=[>\s]))' . preg_quote($smiley) . '((?=[^\p{L}\p{N}])|$)%mu', '$1<img src="' . $base_config['baseurl'] . '/static/img/smile/' . $url . '" alt="' . $smiley . '" width="15px" height="15px" />$2', $text);
		}
	}
	
	static function parse_bbcode(&$text) {
		$done = false;
		while (!$done) {
			$done = true;
			foreach (self::$pattern as $val) {
				if (preg_match($val, $text)) {
					$text = preg_replace(self::$pattern, self::$replace, $text);
					$done = false;
					break;
				}
			}
		}
	}
	
	static function error_check($text, &$errors) {
		global $futurebb_user, $futurebb_config;
		static $filter_data, $filter_domains;
		if (!$futurebb_user['g_post_links'] && preg_match('%\[url.*?\]%', $text)) {
			$errors[] = translate('nolinks');
		}
		if (!$futurebb_user['g_post_images'] && preg_match('%\[img.*?\]%', $text)) {
			$errors[] = translate('noimgs');
		}
		if (!isset($filter_data)) {
			$filter_data = explode('|', $futurebb_config['imghostrestriction']);
		}
		if ((!$futurebb_user['g_mod_privs'] && !$futurebb_user['g_admin_privs']) && $filter_data[0] != 'none') {
			if (!isset($filter_domains)) {
				$filter_domains = explode("\n", $filter_data[1]);
			}
			preg_match_all('%\[img\](.*?)\[/img\]%', $text, $matches);
			foreach ($matches[1] as $url) {
				if (!preg_match('%^(ht|f)tps?://%', $url)) {
					$url = 'http://' . $url;
				}
				$parse = parse_url($url);
				$host = $parse['host'];
				if ($filter_data[0] == 'blacklist' && in_array($host, $filter_domains)) {
					$errors[] = translate('imgblacklisterror', $url, implode(', ', $filter_domains));
				} else if ($filter_data[0] == 'whitelist' && !in_array($host, $filter_domains)) {
					$errors[] = translate('imgwhitelisterror', $url, implode(', ', $filter_domains));
				}
			}
		}
		if (empty(self::$tags)) {
			self::$tags = array('b','i','u','s','color','colour','url','img','quote','code','list','\*');
		}
		if (preg_match_all('%\[(' . implode('|', self::$tags) . ')=(.*?)(\[|\])\]%', $text, $matches)) {
			$errors[] = translate('bracketparam', $matches[1][0]);
			return;
		}
		
		$bbcode_parts = preg_split('%(\[[\*a-zA-Z0-9-/]*?(?:=.*?)?\])%', $text, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY); //this regular expression was copied from FluxBB. However, everything used to parse it is completely original
		//split the message into tags and check syntax
		$open_tags = array();
		$last_key = 0;
		$quotes = 0;
		foreach ($bbcode_parts as $val) {
			if (preg_match('%^\[/(' . implode('|', self::$tags) . ')\]$%', $val, $matches)) {
				if ($last_key == 0) {
					$errors[] = translate('closenoopen', $matches[1]);
					return;
				}
				if ($matches[1] != $open_tags[$last_key - 1]) {
					$errors[] = translate('expectedfound', $open_tags[$last_key - 1], $matches[1]);
					return;
				}
				if ($open_tags[$last_key - 1] == 'quote') {
					$quotes--;
				}
				unset($open_tags[$last_key - 1]);
				$last_key--;
			} else if (preg_match('%^\[(' . implode('|', self::$tags) . ')(=.*?)?\]$%', $val, $matches)) {
				$open_tags[$last_key] = $matches[1];
				if ($open_tags[$last_key ] == 'quote') {
					$quotes++;
					if ($quotes > $futurebb_config['max_quote_depth']) {
						$errors[] = translate('toomanynestedquotes', $futurebb_config['max_quote_depth']);
					}
				}
				$last_key++;
			}
		}
		if (sizeof($open_tags) > 0) {
			foreach ($open_tags as &$val) {
				$val = '<b>[' . $val . ']</b>';
			}
			$errors[] = translate('tagsnotclosed', implode(', ', $open_tags));
		}
	}
}