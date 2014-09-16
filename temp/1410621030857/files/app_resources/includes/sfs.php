<?php
$get_values = array();
if (strstr($futurebb_config['sfs_check_values'], 'ip')) {
	$get_values[] = 'ip=' . rawurlencode($_SERVER['REMOTE_ADDR']);
}
if (strstr($futurebb_config['sfs_check_values'], 'ip')) {
	$get_values[] = 'email=' . rawurlencode($_POST['email']);
}
$data = file_get_contents('http://stopforumspam.com/api?' . implode('&', $get_values));
preg_match_all('%<frequency>(\d+)</frequency>%', $data, $matches);
$score = 0;
foreach ($matches[1] as $val) {
	$score += $val;
}
if ($score > $futurebb_config['sfs_max_score']) {
	$errors[] = 'You have been automatically detected as a spammer. Please contact the board administrator if you think this is in error.';
}