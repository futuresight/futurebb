<?php
function cache_pages() {
	global $db;
	$q = new DBSelect('pages', array('*'), '', 'Failed to get pages');
	$result = $q->commit();
	$pages = array();
	$pagessubdirs = array();
	while ($cur_page = $db->fetch_assoc($result)) {
		$page = array(
			'file'			=> $cur_page['file'],
			'template'		=> ($cur_page['template'] ? true : false),
			'nocontentbox'	=> ($cur_page['nocontentbox'] ? true : false),
			'admin'			=> ($cur_page['admin'] ? true : false),
			'mod'			=> ($cur_page['moderator'] ? true : false),
		);
		if ($cur_page['subdirs']) {
			$pagessubdirs[$cur_page['url']] = $page;
		} else {
			$pages[$cur_page['url']] = $page;
		}
	}
	
	file_put_contents(FORUM_ROOT . '/app_config/cache/pages.php', '<?php' . "\n" . '$pages = ' . var_export($pages, true) . ';' . "\n" . '$pagessubdirs = ' . var_export($pagessubdirs, true) . ';');
}

function cache_admin_pages() {
	global $futurebb_config;
	//admin pages are stored in the format url=>languagekey
	$admin_text = base64_decode($futurebb_config['admin_pages']);
	$lines = explode("\n", $admin_text);
	$admin_pages = array();
	foreach ($lines as $line) {
		$parts = explode('=>', $line);
		$admin_pages[$parts[0]] = $parts[1];
	}
	
	$mod_text = base64_decode($futurebb_config['mod_pages']);
	$lines = explode("\n", $mod_text);
	$mod_pages = array();
	foreach ($lines as $line) {
		$parts = explode('=>', $line);
		$mod_pages[$parts[0]] = $parts[1];
	}
	
	file_put_contents(FORUM_ROOT . '/app_config/cache/admin_pages.php', '<?php' . "\n" . '$admin_pages = ' . var_export($admin_pages, true) . ';' . "\n" . '$mod_pages = ' . var_export($mod_pages, true) . ';');
}