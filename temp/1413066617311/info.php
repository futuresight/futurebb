<?php
$ext_info = array(
	'title'			=> 'StopForumSpam mod',
	'website'		=> 'http://futuresight.org',
	'support'		=> 'http://futuresight.org/support',
	'uninstallable'	=> true
);

$changes = array(
	0 => array(
		'file'		=> 'app_resources/pages/register.php',
		'type'		=> 'replace',
		'find'		=> array("\t" . 'include FORUM_ROOT . \'/app_resources/includes/sfs.php\';'),
		'change'	=> array('')
	),
);