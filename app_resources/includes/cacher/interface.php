<?php
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