<?php
$changes = array(
	0 => array(
		'file'		=> 'app_resources/pages/register.php',
		'type'		=> 'add',
		'find'		=> array('$errors[] = translate(\'bademail\');', '}'),
		'change'	=> array("\t" . 'include FORUM_ROOT . \'/app_resources/includes/sfs.php');
	)
);