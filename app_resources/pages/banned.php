<?php
$db->query('DELETE FROM `#^bans` WHERE expires<=' . time()) or enhanced_error('Failed to delete old bans', true); //delete any bans that have already expired
if ($ban_type == 'ban') {
	$page_title = translate('banned');
	$result = $db->query('SELECT * FROM `#^bans` WHERE (username=\'' . $db->escape($futurebb_user['username']) . '\' OR ip=\'' . $db->escape($_SERVER['REMOTE_ADDR']) . '\') AND (expires>' . time() . ' OR expires IS NULL)') or error('Failed to check for bans', __FILE__, __LINE__, $db->error());
	$cur_ban = $db->fetch_assoc($result);
	$ban_page_replaced = false; //this sets up for a hook, where the ban page can be replaced
	ExtensionConfig::run_hooks('ban_page', array('ban_info' => $cur_ban, 'type' => $ban_type));
	if (!$ban_page_replaced) {
		?>
		<h2><?php echo translate('banned'); ?></h2>
		<p><?php echo translate('bannedmsg1'); if ($cur_ban['expires'] != null) echo ' ' . translate('until') . ' ' . user_date($cur_ban['expires']); ?>. <?php echo translate('bannedmsg2'); ?><br /><b><?php echo htmlspecialchars($cur_ban['message']); ?></b><br /><?php echo translate('bannedmsg3', $futurebb_config['admin_email']); ?></p>
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