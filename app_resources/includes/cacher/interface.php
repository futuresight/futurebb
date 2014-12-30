<?php
load_db_config(true);
function cache_header() {
	global $futurebb_config;
	$xml = new SimpleXMLElement($futurebb_config['header_links']);
	$code = '<?php' . "\n" . '$nav_items = array();' . "\n";
	foreach ($xml->link as $link_xml) {
		$attr = $link_xml->attributes();
		if (isset($attr['path'])) {
			$link = '<a href="\' . $base_config[\'baseurl\'] . \'/' . ((string)$attr['path']) . '">';
		} else if (isset($attr['url'])) {
			$link = '<a href="' . ((string)$attr['url']) . '">';
		}
		if (isset($link)) { //only do the rest of the link if a URL or path is present
			$text = ((string)$link_xml);
			if (isset($attr['notranslate'])) {
				$link .= htmlspecialchars((string)$link_xml);
			} else {
				$link .= '\' . translate(\'' . $text . '\') . \'';
			}
			$link .= '</a>';
			//are there any permissions?
			if (isset($attr['perm'])) {
				$perms_raw = explode(' ', $attr['perm']);
				$perms_code = array();
				
				foreach ($perms_raw as $perm) {
					$perm = trim($perm);
					$not = false;
					if ($perm{0} == '~') {
						$not = true;
						$perm = substr($perm, 1);
					}
					if ($perm == 'valid') {
						if ($not) {
							$pcode = '$futurebb_user[\'id\'] == 0';
						} else {
							$pcode = '$futurebb_user[\'id\'] != 0';
						}
					} else {
						$pcode = ($not ? '!' : '');
						$pcode .= '$futurebb_user[\'' . $perm . '\']';
					}
					$perms_code[] = $pcode;
				}
				$code .= 'if (' . implode(' && ', $perms_code) . ') {' . "\n\t";
			}
			$code .= '$nav_items[] = CacheEngine::replace_interface_strings(\'' . $link . '\');' . "\n";
			if (isset($attr['perm'])) {
				$code .= '}' . "\n";
			}
		}
	}
	file_put_contents(FORUM_ROOT . '/app_config/cache/header.php', $code);
}

function cache_language() {
	global $db, $base_config;
	$q = new DBSelect('language', array('*'), '', 'Failed to get language entries');
	$result = $q->commit();
	$lang = array();
	while ($lang_entry = $db->fetch_assoc($result)) {
		if (!isset($lang[$lang_entry['language']])) {
			$lang[$lang_entry['language']] = array();
		}
		if (!isset($lang[$lang_entry['language']][$lang_entry['category']])) {
			$lang[$lang_entry['language']][$lang_entry['category']] = array();
		}
		$lang[$lang_entry['language']][$lang_entry['category']][$lang_entry['langkey']] = $lang_entry['value'];
	}
		
	foreach ($lang as $language => $categories) {
		if (!file_exists(FORUM_ROOT . '/app_config/cache/language')) {
			mkdir(FORUM_ROOT . '/app_config/cache/language');
		}
		if (!file_exists(FORUM_ROOT . '/app_config/cache/language/' . $language)) {
			mkdir(FORUM_ROOT . '/app_config/cache/language/' . $language);
		}
		foreach ($categories as $category => $lang_entries) {
			$lang_subset = array();
			foreach ($lang_entries as $key => $val) {
				$lang_subset[$key] = str_replace('$baseurl$', $base_config['baseurl'], $val);
			}
			$out = '<?php' . "\n";
			if ($category == 'main') {
				$out .= '$lang = ';
			} else {
				$out .= '$lang_addl = ';
			}
			$out .= var_export($lang_subset, true) . ';';
			file_put_contents(FORUM_ROOT . '/app_config/cache/language/' . $language . '/' . $category . '.php', $out);
		}
	}
}