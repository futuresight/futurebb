<?php
$file = isset($dirs[2]) ? $dirs[2] : '';
if ($file == '' || !file_exists(FORUM_ROOT . '/app_resources/pages/css/' . $file)) {
	httperror(404);
} else {
	header('Content-type: text/css');
	include FORUM_ROOT . '/app_resources/pages/css/' . $file;
}