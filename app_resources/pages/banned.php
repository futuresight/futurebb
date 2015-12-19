<?php
$db->query('DELETE FROM `#^bans` WHERE expires<=' . time()) or enhanced_error('Failed to delete old bans', true); //delete any bans that have already expired
if ($ban_type == 'ban') {
	$page_title = translate('banned');
	$ban_page_replaced = false; //this sets up for a hook, where the ban page can be replaced
	//get all ban info
	$result = $db->query('SELECT * FROM `#^bans` WHERE id=' . $ban_id) or enhanced_error('Failed to get ban info', true);
	$ban_info = $db->fetch_assoc($result);
	ExtensionConfig::run_hooks('ban_page', array('ban_info' => $ban_info, 'type' => $ban_type));
	if (!$ban_page_replaced) {
		?>
		<h2><?php echo translate('banned'); ?></h2>
		<p><?php echo translate('bannedmsg1'); if ($ban_info['expires'] != null) echo ' ' . translate('until') . ' ' . user_date($ban_info['expires']); ?>. <?php echo translate('bannedmsg2'); ?><br /><b><?php echo htmlspecialchars($ban_info['message']); ?></b><br /><?php echo translate('bannedmsg3', $futurebb_config['admin_email']); ?></p>
		<?php
	}
} else if ($ban_type == 'no_guest') {
	$page_title = translate('accessdenied'); ?>
	<h2><?php echo translate('accessdenied'); ?></h2>
    <p><?php
	if ($futurebb_user['id'] == 0) {
		echo translate('noguests');
	} else {
		echo translate('nogroupview'); 
	}
	?></p>
<?php } ?>