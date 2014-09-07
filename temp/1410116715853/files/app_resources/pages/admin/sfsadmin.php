<?php
if (!$futurebb_user['g_mod_privs'] && !$futurebb_user['g_admin_privs']) {
	httperror(403);
}
$page_title = translate('iptracker');
include FORUM_ROOT . '/app_resources/includes/admin.php';

if (!isset($dirs[3])) {
	$dirs[3] = '';
}
?>
<div class="container">
	<?php make_admin_menu(); ?>
	<div class="forum_content rightbox admin">
		
	</div>
</div>