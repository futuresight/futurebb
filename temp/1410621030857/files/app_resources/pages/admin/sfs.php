<?php
if (!$futurebb_user['g_mod_privs'] && !$futurebb_user['g_admin_privs']) {
	httperror(403);
}
$page_title = translate('iptracker');
include FORUM_ROOT . '/app_resources/includes/admin.php';

if (isset($_POST['form_sent'])) {
	set_config('sfs_max_score', intval($_POST['maxscore']));
	$check_stuff = array();
	if (isset($_POST['check']['ip'])) {
		$check_stuff[] = 'ip';
	}
	if (isset($_POST['check']['email'])) {
		$check_stuff[] = 'email';
	}
	set_config('sfs_check_values', implode('|', $check_stuff));
}
?>
<div class="container">
	<?php make_admin_menu(); ?>
	<div class="forum_content rightbox admin">
		<form action="<?php echo $base_config['baseurl']; ?>/admin/sfs" method="post" enctype="multipart/form-data">
        	<p>Check:</p>
            <ul>
            	<li><input type="checkbox" value="check[ip]"<?php if (strstr($futurebb_config['sfs_check_values'], 'ip')) echo ' checked="checked"'; ?> /> IP address</li>
                <li><input type="checkbox" value="check[email]"<?php if (strstr($futurebb_config['sfs_check_values'], 'email')) echo ' checked="checked"';  /> Email address</li>
            </ul>
        	<p>The "score" is defined as the amount of records returned for email address + records for IP address. Please note that username is not counted.</p>
            <p>Maximum acceptable score: <input type="text" name="maxscore" value="<?php echo $futurebb_config['sfs_max_score']; ?>" /> <input type="submit" name="form_sent" value="Save" /></p>
	</div>
</div>