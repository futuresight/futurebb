<?php
if (!$futurebb_user['g_mod_privs'] && !$futurebb_user['g_admin_privs']) {
	httperror(403);
}
translate('<addfile>', 'admin');
$page_title = 'Reports';
include FORUM_ROOT . '/app_resources/includes/admin.php';
if (isset($_POST['zap'])) {
	$rid = intval(key($_POST['zap']));
	$db->query('UPDATE `#^reports` SET zapped=' . time() . ',zapped_by=' . $futurebb_user['id'] . ',status=\'' . $db->escape(key($_POST['zap'][$rid])) . '\' WHERE id=' . $rid) or error('Failed to zap report', __FILE__, __LINE__, $db->error());
}
if (isset($_POST['review'])) {
	$rid = intval(key($_POST['review']));
	$db->query('UPDATE `#^reports` SET status=\'review\',zapped_by=' . $futurebb_user['id'] . ' WHERE id=' . $rid) or error('Failed to zap report', __FILE__, __LINE__, $db->error());
}
include FORUM_ROOT . '/app_resources/includes/parser.php';
?>
<div class="container">
	<?php make_admin_menu(); ?>
	<div class="forum_content rightbox admin">
		<h2><?php echo translate('unreadreports'); ?></h2>
		<form action="<?php echo $base_config['baseurl']; ?>/admin/reports" method="post" enctype="multipart/form-data">
		<?php
		$result = $db->query('SELECT r.status,r.id,r.post_id,r.post_type,r.reason,r.time_reported,r.zapped,
		t.subject,n.type,n.contents,n.arguments,n.send_time,
		t.url AS turl,
		f.name AS fname,
		f.url AS furl,
		u.username AS reported_by,
		z.username AS zapped_by FROM `#^reports` AS r
		LEFT JOIN `#^posts` AS p ON p.id=r.post_id
		LEFT JOIN `#^topics` AS t ON t.id=p.topic_id
		LEFT JOIN `#^forums` AS f ON f.id=t.forum_id
		LEFT JOIN `#^users` AS u ON u.id=r.reported_by
		LEFT JOIN `#^users` AS z ON z.id=r.zapped_by
		LEFT JOIN `#^notifications` AS n ON n.id=r.post_id
		WHERE r.zapped IS NULL AND status<>\'withdrawn\'
		ORDER BY r.status=\'review\' DESC,r.time_reported ASC LIMIT 50')
		or error('Failed to get new reports', __FILE__, __LINE__, $db->error());
		if ($db->num_rows($result)) {
			while ($cur_report = $db->fetch_assoc($result)) {
				echo '<div class="reportbox">
					<p>';
					if($cur_report['post_type'] == 'post') {
					echo '<a href="' . $base_config['baseurl'] . '/' . $cur_report['furl'] . '">' . htmlspecialchars($cur_report['fname']) . '</a> &raquo; <a href="' . $base_config['baseurl'] . '/' . $cur_report['furl'] . '/' . $cur_report['turl'] . '">' . htmlspecialchars($cur_report['subject']) . '</a> &raquo; <a href="' . $base_config['baseurl'] . '/posts/' . $cur_report['post_id'] . '">' . translate('post') . ' #' . $cur_report['post_id'] . '</a><br />';
					} elseif($cur_report['post_type'] == 'msg') {
						echo '</p><p class="whitebox">';
							switch ($cur_report['type']) {
								case 'warning':
									echo '<img src="' . $base_config['baseurl'] . '/static/img/msg_warning.png" alt="warning" width="22" />';
									echo '<span class="notifications_count">#' . $cur_report['post_id'] . '</span> ';
									echo translate('user_sent_warning', '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($cur_report['arguments']) . '">' . htmlspecialchars($cur_report['arguments']) . '</a>') . '<br />' . $cur_report['contents'];
									break;
								case 'msg':
									echo '<img src="' . $base_config['baseurl'] . '/static/img/msg_msg.png" alt="message" width="22" />';
									echo '<span class="notifications_count">#' . $cur_report['post_id'] . '</span> ';
									echo translate('user_sent_msg', '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($cur_report['arguments']) . '">' . htmlspecialchars($cur_report['arguments']) . '</a>') . '<br />' . $cur_report['contents'];
									break;
								case 'notification':
									$parts = explode(',', $cur_report['arguments'], 2);
									echo '<img src="' . $base_config['baseurl'] . '/static/img/msg_notif.png" alt="notification" width="22" />';
									echo '<span class="notifications_count">#' . $cur_report['post_id'] . '</span> ';
									echo translate('user_mentioned_you', '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($parts[0]) . '">' . htmlspecialchars($parts[0]) . '</a>') .
					'<a href="' . $base_config['baseurl'] . '/posts/' . $cur_report['contents'] . '">' . htmlspecialchars($parts[1]) . '</a>';
									break;
								default:
									echo '&nbsp;<span class="notifications_count" style="font-size: 12px;">#' . $cur_report['post_id'] . '</span> ';
									echo translate('couldnot_display_notif');
							}
						if ($cur_report['send_time'] != 0) echo '<br /><em>' . translate('sent') . ' ' . user_date($cur_report['send_time']) . '</em>';
						echo '</p><p>';
					}
					if ($cur_report['post_type'] == 'special') {
						echo translate('systemreportmsg');
					} else {
						echo translate('reportedby', '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($cur_report['reported_by']) . '">' . htmlspecialchars($cur_report['reported_by']) . '</a>', user_date($cur_report['time_reported']));
					}
					if ($cur_report['status'] == 'review') {
						echo '<br />' . translate('furtherreview',  '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($cur_report['zapped_by']) . '">' . htmlspecialchars($cur_report['zapped_by']) . '</a>');
					}
					echo '</p><p>' . translate('reason') . '<br /><b>';
					if ($cur_report['post_type'] == 'special') { //parse HTML for system reports
						echo $cur_report['reason'];
					} else {
						echo htmlspecialchars($cur_report['reason']);
					}
					echo '</b></p>
					<p><input type="submit" name="zap[' . $cur_report['id'] . '][noresp]" value="' . translate('donotresp') . '" /> <input type="submit" name="zap[' . $cur_report['id'] . '][accept]" value="' . translate('accept') . '" /> <input type="submit" name="zap[' . $cur_report['id'] . '][reject]" value="' . translate('reject') . '" /> <input type="submit" name="review[' . $cur_report['id'] . ']" value="' . translate('markreview') . '"';
					if ($cur_report['status'] == 'review') {
						echo 'disabled="disabled"';
					}
					echo ' /></p>
				</div>';
			}
		} else {
			echo '<p>' . translate('nonewreports') . '</p>';
		}
		?>
		</form>
		<h2><?php echo translate('processedreports'); ?></h2>
		<?php
		$result = $db->query('SELECT
		r.status,r.id,r.post_id,r.post_type,r.reason,r.time_reported,r.zapped,
		t.subject,n.type,n.contents,n.arguments,n.send_time,
		t.url AS turl,
		f.name AS fname,
		f.url AS furl,
		u.username AS reported_by,
		z.username AS zapped_by FROM `#^reports` AS r
		LEFT JOIN `#^posts` AS p ON p.id=r.post_id
		LEFT JOIN `#^topics` AS t ON t.id=p.topic_id
		LEFT JOIN `#^forums` AS f ON f.id=t.forum_id
		LEFT JOIN `#^users` AS u ON u.id=r.reported_by
		LEFT JOIN `#^users` AS z ON z.id=r.zapped_by
		LEFT JOIN `#^notifications` AS n ON n.id=r.post_id
		WHERE r.zapped IS NOT NULL OR status=\'withdrawn\'
		ORDER BY r.zapped DESC LIMIT 15')
		or error('Failed to get old reports', __FILE__, __LINE__, $db->error());
		if ($db->num_rows($result)) {
			while ($cur_report = $db->fetch_assoc($result)) {
				echo '<div class="reportbox">
					<p>';
					if($cur_report['post_type'] == 'post') {
					echo '<a href="' . $base_config['baseurl'] . '/' . $cur_report['furl'] . '">' . htmlspecialchars($cur_report['fname']) . '</a> &raquo; <a href="' . $base_config['baseurl'] . '/' . $cur_report['furl'] . '/' . $cur_report['turl'] . '">' . htmlspecialchars($cur_report['subject']) . '</a> &raquo; <a href="' . $base_config['baseurl'] . '/posts/' . $cur_report['post_id'] . '">' . translate('post') . ' #' . $cur_report['post_id'] . '</a><br />';
					} elseif($cur_report['post_type'] == 'msg') {
						echo '</p><p class="whitebox">';
							switch ($cur_report['type']) {
								case 'warning':
									echo '<img src="' . $base_config['baseurl'] . '/static/img/msg_warning.png" alt="warning" width="22" />';
									echo '<span class="notifications_count">#' . $cur_report['post_id'] . '</span>';
									echo translate('user_sent_warning', '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($cur_report['arguments']) . '">' . htmlspecialchars($cur_report['arguments']) . '</a>') . '<br />' . $cur_report['contents'];
									break;
								case 'msg':
									echo '<img src="' . $base_config['baseurl'] . '/static/img/msg_msg.png" alt="message" width="22" />';
									echo '<span class="notifications_count">#' . $cur_report['post_id'] . '</span>';
									echo translate('user_sent_msg', '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($cur_report['arguments']) . '">' . htmlspecialchars($cur_report['arguments']) . '</a>') . '<br />' . $cur_report['contents'];
									break;
								case 'notification':
									$parts = explode(',', $cur_report['arguments'], 2);
									echo '<img src="' . $base_config['baseurl'] . '/static/img/msg_notif.png" alt="notification" width="22" />';
									echo '<span class="notifications_count">#' . $cur_report['post_id'] . '</span> ';
									echo translate('user_mentioned_you', '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($parts[0]) . '">' . htmlspecialchars($parts[0]) . '</a>') .
					'<a href="' . $base_config['baseurl'] . '/posts/' . $cur_report['contents'] . '">' . htmlspecialchars($parts[1]) . '</a>';
									break;
								default:
									echo '<span class="notifications_count" style="font-size: 12px;>#' . $cur_report['post_id'] . '</span>';
									echo translate('couldnot_display_notif');
							}
						if ($cur_report['send_time'] != 0) echo '<br /><em>' . translate('sent') . ' ' . user_date($cur_report['send_time']) . '</em>';
						echo '</p><p>';
					}
					if ($cur_report['post_type'] == 'special') {
						echo translate('systemreportmsg');
					} else {
						echo translate('reportedby',  '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($cur_report['reported_by']) . '">' . htmlspecialchars($cur_report['reported_by']) . '</a>', user_date($cur_report['time_reported'])) . '<br />' . translate('markedreadby',  '<a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($cur_report['zapped_by']) . '">' . htmlspecialchars($cur_report['zapped_by']) . '</a>', user_date($cur_report['zapped'])) . '<br />' . translate('status') . ': <b>';
						switch ($cur_report['status']) {
							case 'unread':
								echo translate('pending'); break;
							case 'review':
								echo translate('underreview'); break;
							case 'reject':
								echo '<span style="color:#A00">' . translate('rejected') . '</span>'; break;
							case 'accept':
								echo '<span style="color:#0A0">' . translate('accepted') . '</span>'; break;
							case 'noresp':
								echo translate('noresp'); break;
							case 'withdrawn':
								echo translate('withdrawnbyreporter'); break;
							default:
								echo translate('unknown'); break;
						}
						echo '</b>';
					}
					
					echo '</p><p>' . translate('reason') . '<br /><b>';
					if ($cur_report['post_type'] == 'special') { //parse HTML for system reports
						echo $cur_report['reason'];
					} else {
						echo htmlspecialchars($cur_report['reason']);
					}
					echo '</b></p>';
					echo '
				</div>';
			}
		} else {
			echo '<p>' . translate('nooldreports') . '</p>';
		}
		?>
	</div>
</div>