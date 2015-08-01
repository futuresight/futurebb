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
			self::add_bbcode('%\[colou?r=(white|black|red|green|blue|orange|yellow|pink|gray|magenta|#[0-9a-fA-F]{6}|\#[0-9a-fA-F]{3})\](.*?)\[/colou?r\]%m', '<span style="color:$1">$2</span>');
		}
		
		$text = htmlspecialchars($text); //clear out any funny business
		
		$text = preg_replace_callback('%\s?\[code\](.*?)\[/code\]\s?%msi', 'self::handle_code_tag_remove', $text); //remove content of code tags prior to parsing
		while (preg_match('%\[quote(=.*?)?\](.*?)\[/quote\]%ms', $text)) {
			$text = preg_replace_callback('%\[quote\](.*?)\[/quote\]%ms', 'self::handle_quote_tag', $text);
			$text = preg_replace_callback('%\[quote=(.*?)\](.*?)\[/quote\]%ms', 'self::handle_quote_tag', $text);
		}
		
		//links and images (these can't be grouped with the rest because they use a different function
		$text = preg_replace_callback('%\[url=?(.*?)\](.*?)\[/url\]%s', 'self::handle_url_tag', $text);
		$text = preg_replace_callback('%\[img\](.*?)\[/img\]%s', 'self::handle_img_tag', $text);
		
		// Format @username into tags
		if($futurebb_config['allow_notifications'] == 1) {
			$text = preg_replace('%(\s|^)@([a-zA-Z0-9_\-]+)%', '$1<span class="usertag">@$2</span>', $text);
		}
		
		//run the bbcode parser with the items entered into the array at the beginning of this function
		if ($bbcode) {
			self::parse_bbcode($text);
		}
		if($show_smilies) { // only parse similies if they were enabled by poster
			self::parse_smilies($text);
		}
		
		$text = self::add_line_breaks($text);
		
		//make the @username into links where applicable
		$at_usernames = array();
		$text = preg_replace_callback('%<span class="usertag">@([a-zA-Z0-9_\-]+)</span>%', function($matches) use(&$at_usernames) {
			if (in_array($matches[1], $at_usernames)) {
				$return = array_search($matches[1], $at_usernames);
			} else {
				$at_usernames[] = $matches[1];
				$return = sizeof($at_usernames) - 1;
			}
			return '<span class="usertag">' . $return . '</span>';
		}, $text);
		
		if (!empty($at_usernames)) {
			$at_usernames_safe = array();
			foreach ($at_usernames as $username) {
				$at_usernames_safe[] = '\'' . $db->escape(strtolower($username)) . '\'';
			}
			$returned_usernames = array();
			$result = $db->query('SELECT LOWER(username) FROM `#^users` WHERE LOWER(username) IN(' . implode(',', $at_usernames_safe) . ')') or enhanced_error('Failed to validate usernames', true);
			while (list($username) = $db->fetch_row($result)) {
				$returned_usernames[] = $username;
			}
			$text = preg_replace_callback('%<span class="usertag">(\d+)</span>%', function($matches) use($at_usernames, $returned_usernames) {
				global $base_config;
				$req_username = $at_usernames[$matches[1]];
				if (in_array(strtolower($req_username), $returned_usernames)) {
					$return = '<a href="' . $base_config['baseurl'] . '/users/' . $req_username . '">@' . $req_username . '</a>';
				} else {
					$return = '@' . $req_username;
				}
				return '<span class="usertag">' . $return . '</span>';
			}, $text);
		}
		
		//handle list tags last, they're weird
		$text = self::handle_list_tags($text);
		$text = self::handle_table_tags($text);
		
		$text = preg_replace_callback('%\s?\[code\](.*?)\[/code\]\s?%msi', 'self::handle_code_tag_replace', $text); //put [code] tags back
		
		$text = censor($text);
		return $text;
	}
	
	static function handle_quote_tag($matches) {
		if (sizeof($matches) == 2) {
			$body = trim($matches[1]); //just [quote]text[/quote]
		} else {
			$body = '<b> ' . $matches[1] . ' ' . translate('wrote') . '</b><br />' . trim($matches[2]); //with author: [quote=someone]text[/quote]
		}
		return '</p><div class="quotebox">' . $body . '</div><p>';
	}
	
	static function handle_list_tags($text) {
		//all other tags have been already parsed, so we can just look at [list] tags
		$list_tags = preg_split('%(\[[\*a-zA-Z0-9-/]*?(?:=.*?)?\])%', $text, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
		//split the message into [list] or [*] tags and start parsing
		$open_tags = array();
		$output = '';
		foreach ($list_tags as $val) {
			if (sizeof($open_tags) != 0) {
				//no line breaks inside lists
				$val = str_replace('<br />', '', $val);
			}
			//go inside each list
			if (preg_match('%^\[(list(=(\*|1))?)\]%', $val, $matches)) {
				//we're inside a list!
				$open_tags[] = $matches[1] . '=' . (isset($matches[3]) ? $matches[3] : '*');
				if (isset($matches[3]) && $matches[3] == '1') {
					$val = preg_replace('%^\[list=1\]%', '<ol>', $val);
				} else {
					$val = preg_replace('%^\[list(=\*)?\]%', '<ul>', $val);
				}
			} else if (preg_match('%^\[\*\]%', $val)) {
				$open_tags[] = '*';
				$val = preg_replace('%^\[\*\]%', '<li>', $val);
			} else if (preg_match('%^\[/(\*|list)\]%', $val, $matches)) {
				$last_tag = array_pop($open_tags);
				if (strpos($last_tag, $matches[1]) === 0) {
					if ($matches[1] == '*') {
						$val = preg_replace('%^\[/\*\]%', '</li>', $val);;
					} else {
						if ($last_tag == 'list=*') {
							$val = preg_replace('%^\[/list\]%', '</ul>', $val);
						} else {
							$val = preg_replace('%^\[/list\]%', '</ol>', $val);
						}
					}
					
				} else {
					error('List parsing error: expected ' . $matches[1] . ' but had ' . $last_tag, __FILE__, __LINE__);
				}
			} else if (preg_match('%^\[/list\]%', $val)) {
			}
			$output .= $val;
		}
		return $output;
	}
	
	static function handle_table_tags($text) {
		//all other tags have been already parsed, so we can just look at [table] tags
		$table_tags = preg_split('%(\[[\*a-zA-Z0-9-/]*?(?:=.*?)?\])%', $text, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
		//split the message into [table], [tr], [td], [th] tags and start parsing
		$open_tags = array();
		$output = '';
		foreach ($table_tags as $val) {
			if (sizeof($open_tags) != 0) {
				//no line breaks inside tables
				$val = str_replace('<br />', '', $val);
			}
			//first do tag openings
			//here's the deal: the order MUST be: [table][tr][th/td]
			if (sizeof($open_tags) == 0) {
				//is it a table tag?
				if (preg_match('%^\[table\]%', $val)) {
					$open_tags[] = 'table';
					$val = preg_replace('%^\[table\]%', '<table>', $val);
				}
			} else if (sizeof($open_tags) == 1) {
				//is it a tr tag?
				if (preg_match('%^\[tr\]%', $val)) {
					$open_tags[] = 'tr';
					$val = preg_replace('%^\[tr\]%', '<tr>', $val);
				} else if (preg_match('%^\[/table\]%', $val)) {
					//it might also be [/table]
					$last_tag = array_pop($open_tags);
					$val = preg_replace('%^\[/table\]%', '</table>', $val);
				}
			} else if (sizeof($open_tags) == 2) {
				if (preg_match('%^\[(td|th)\]%', $val, $matches)) {
					$open_tags[] = $matches[1];
					$val = preg_replace('%^\[(td|th)\]%', '<$1>', $val);
				} else if (preg_match('%^\[/tr\]%', $val)) {
					//it might also be [/tr]
					$val = preg_replace('%^\[/tr\]%', '</tr>', $val);
					$last_tag = array_pop($open_tags);
				}
				
			} else if (sizeof($open_tags) == 3) {
				//it must be [/tr] or [/th]
				if (preg_match('%^\[/(td|th)\]%', $val, $matches)) {
					$last_tag = array_pop($open_tags);
					if ($matches[1] != $last_tag) {
						error('Tag mismatch: expected [/' . $last_tag . '] found [/' . $matches[1] . ']');
					}
					$val = preg_replace('%^\[/(td|th)\]%', '</$1>', $val);
				}
			}
			$output .= $val;
		}
		return $output;
	}
	
	static function add_line_breaks($text) {
		$text = str_replace("\r\n", "\n", $text);
		$text = str_replace("\r", '<br />', $text);
		$text = str_replace("\n", '<br />', $text);
		return $text;
	}
	
	static function handle_code_tag_remove($matches) {
		return self::handle_code_tag($matches[1], 1);
	}
	static function handle_code_tag_replace($matches) {
		return self::handle_code_tag($matches[1], 2);
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
			$code_matches[$i1] = trim($text);
			return '[code]' . $i1 . '[/code]';
		} else if ($mode == 2) {
			if (!isset($i2)) {
				$i2 = 0;
			}
			$i2++;
			return '</p><div class="quotebox" style="font-family:Courier">' . $code_matches[$i2] . '</div><p>';
		} else {
			return '';
		}
	}
	
	static function handle_url_tag($matches) {
		$v1 = $matches[1];
		$v2 = isset($matches[2]) ? $matches[2] : '';
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
		if (strpos($url, 'http://') === false && strpos($url, 'https://') === false && strpos($url, 'mailto:') === false) {
			$url = 'http://' . $url;
		}
		
		return '<a href="' . $url . '">' . $text . '</a>';
	}
	
	static function handle_img_tag($matches) {
		$url = $matches[1];
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
				if ($filter_data[0] == 'blacklist') {
					foreach ($filter_domains as $domain) {
						if (preg_match('%' . preg_quote($domain) . '$%', $host)) {
							$errors[] = translate('imgblacklisterror', $url, implode(', ', $filter_domains));
							break;
						}
					}
				} else if ($filter_data[0] == 'whitelist') {
					$ok = false;
					foreach ($filter_domains as $domain) {
						if (preg_match('%' . preg_quote($domain) . '$%', $host)) {
							$ok = true;
						}
						
					}
					if (!$ok) {
						$errors[] = translate('imgwhitelisterror', $url, implode(', ', $filter_domains));
					}
				}
			}
		}
		if (empty(self::$tags)) {
			self::$tags = array('b','i','u','s','color','colour','url','img','quote','code','list','\*', 'table', 'tr', 'td', 'th');
		}
		if (preg_match_all('%\[(' . implode('|', self::$tags) . ')=(.*?)(\[|\])\]%', $text, $matches)) {
			$errors[] = translate('bracketparam', $matches[1][0]);
			return;
		}
		
		//parsing rules
		$no_nest_tags = array('img');
		$block_tags = array('quote', 'code', 'list', 'table', 'tr');
		$inline_tags = array('b', 'i', 'u', 's', 'color', 'colour', 'url', 'img', '\*', 'th', 'td');
		$nest_only = array('table' => array('tr'), 'tr' => array('td', 'th'), 'list' => array('*')); //tags that can only have a specific set of subtags
		$nest_forbid = array('td' => array('td', 'th'), 'th' => array('td', 'th'));
		$no_body = array('table', 'tr', 'list'); //tags that can't have text inside them
		
		$bbcode_parts = preg_split('%(\[[\*a-zA-Z0-9-/]*?(?:=.*?)?\])%', $text, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY); //this regular expression was copied from FluxBB. However, everything used to parse it is completely original
		//split the message into tags and check syntax
		$open_tags = array();
		$last_key = 0;
		$quotes = 0;
		foreach ($bbcode_parts as $key => $val) {
			if (preg_match('%^\[/(' . implode('|', self::$tags) . ')\]$%', $val, $matches)) {
				//closing tag of some sort
				if ($last_key == 0) {
					$errors[] = translate('closenoopen', $matches[1]);
					$errors[] = self::highlight_error($text, $matches[0], $bbcode_parts, $key);
					return;
				}
				if ($matches[1] != $open_tags[$last_key - 1]) {
					$errors[] = translate('expectedfound', $open_tags[$last_key - 1], $matches[1]);
					$errors[] = self::highlight_error($text, $matches[0], $bbcode_parts, $key);
					return;
				}
				if ($open_tags[$last_key - 1] == 'quote') {
					$quotes--;
				}
				unset($open_tags[$last_key - 1]);
				$last_key--;
			} else if (preg_match('%^\[(' . implode('|', self::$tags) . ')(=.*?)?\]$%', $val, $matches)) {
				//opening tag of some sort
				$open_tags[$last_key] = $matches[1];
				//check if there are any block tags inside inline tags
				if ($last_key > 0 && in_array($open_tags[$last_key - 1], $inline_tags) && in_array($matches[1], $block_tags)) {
					$errors[] = translate('blockininline', $matches[1], $open_tags[$last_key - 1]);
					$errors[] = self::highlight_error($text, $matches[0], $bbcode_parts, $key);
				}
				//check for the tags that only allow specific tags directly inside them
				if ($last_key > 0 && array_key_exists($open_tags[$last_key - 1], $nest_only) && !in_array($open_tags[$last_key], $nest_only[$open_tags[$last_key - 1]])) {
					$errors[] = translate('specificnestingerror', $matches[1], $open_tags[$last_key - 1]);
					$errors[] = self::highlight_error($text, $matches[0], $bbcode_parts, $key);
				}
				if ($last_key > 0 && array_key_exists($open_tags[$last_key - 1], $nest_forbid) && in_array($open_tags[$last_key], $nest_forbid[$open_tags[$last_key - 1]])) {
					$errors[] = translate('specificnestingerror', $matches[1], $open_tags[$last_key - 1]);
					$errors[] = self::highlight_error($text, $matches[0], $bbcode_parts, $key);
				}
				//check if there is any bbcode inside a tag which can't nest
				if ($last_key > 0 && in_array($open_tags[$last_key - 1], $no_nest_tags)) {
					$errors[] = translate('nonesting', $open_tags[$last_key - 1]);
					$errors[] = self::highlight_error($text, $matches[0], $bbcode_parts, $key);
				}
				if ($open_tags[$last_key] == 'quote') {
					$quotes++;
					if ($quotes > $futurebb_config['max_quote_depth']) {
						$errors[] = translate('toomanynestedquotes', $futurebb_config['max_quote_depth']);
						$errors[] = self::highlight_error($text, $matches[0], $bbcode_parts, $key);
					}
				}
				
				$last_key++;
			} else if ($last_key > 0) {
				//no tag, just text
				if (!preg_match('%^\s$%ms', $val) && in_array($open_tags[$last_key - 1], $no_body)) {
					$errors[] = translate('notextinsidetag', $open_tags[$last_key - 1]);
					$errors[] = self::highlight_error($text, $val, $bbcode_parts, $key);
				}
			}
		}
		if (sizeof($open_tags) > 0) {
			$location_notices = array();
			foreach ($open_tags as &$val) {
				//find the last occurrence of this tag
				$reverse_parts = array_reverse($bbcode_parts);
				foreach ($reverse_parts as $partkey => $part) {
					if (strpos($part, '[' . $val) === 0) {
						$location_notices[] = self::highlight_error($text, $part, $bbcode_parts, sizeof($bbcode_parts) - $partkey - 1, 'tagwasopened', $val);
						break;
					}
				}
				//bold this so it goes on the error list
				$val = '<b>[' . $val . ']</b>';
			}
			$errors[] = translate('tagsnotclosed', implode(', ', $open_tags));
			$errors = array_merge($errors, $location_notices);
		}
	}
	
	static private function highlight_error($text, $problem, $bbcode_parts, $key, $special_notice = '', $other_arg = '') {
		$pos = self::get_total_length($bbcode_parts, $key);
		if ($pos < 15) {
			$len = $pos;
		} else {
			$len = 15;
		}
		return translate($special_notice == '' ? 'errorwaslocated' : $special_notice, $other_arg) . '<code>' . substr($text, max(array(0, $pos - 15)), $len) . '<b style="color:#A00">' . $problem . '</b>' . substr($text, $pos + strlen($problem), 15) . '</code>';
	}
	
	static function get_total_length($array, $key) {
		$len = 0;
		for ($i = 0; $i < $key; $i++) {
			$len += strlen($array[$i]);
		}
		return $len;
	}
}