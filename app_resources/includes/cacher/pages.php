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