<?php
$page_title = 'Banned';
$result = $db->query('SELECT message,expires FROM `#^bans` WHERE (username=\'' . $db->escape($futurebb_user['username']) . '\' OR ip=\'' . $db->escape($_SERVER['REMOTE_ADDR']) . '\') AND (expires>' . time() . ' OR expires IS NULL)') or error('Failed to check for bans', __FILE__, __LINE__, $db->error());
$cur_ban = $db->fetch_assoc($result);
?>
<h2><?php echo translate('banned'); ?></h2>
<p><?php echo translate('bannedmsg1'); if ($cur_ban['expires'] != null) echo ' ' . translate('until') . ' ' . user_date($cur_ban['expires']); ?>. <?php echo translate('bannedmsg2'); ?><br /><b><?php echo htmlspecialchars($cur_ban['message']); ?></b><br /><?php echo translate('bannedmsg3', $futurebb_config['admin_email']); ?></p>