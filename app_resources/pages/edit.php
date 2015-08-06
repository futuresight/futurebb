<?php
$page_title = translate('editpost');
$pid = intval($dirs[2]);
$result = $db->query('SELECT p.poster,p.content,p.disable_smilies,t.url AS turl,t.subject,t.id AS tid,f.url AS furl,t.closed,f.name AS forum_name,f.archived,t.first_post_id FROM `#^posts` AS p LEFT JOIN `#^topics` AS t ON t.id=p.topic_id LEFT JOIN `#^forums` AS f ON f.id=t.forum_id WHERE p.id=' . $pid) or error('Failed to get post', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result)) {
	httperror(404);
}
$cur_post = $db->fetch_assoc($result);
if (!$futurebb_user['g_admin_privs'] && !$futurebb_user['g_mod_privs'] && ($cur_post['poster'] != $futurebb_user['id'] || !$futurebb_user['g_edit_posts']) || strstr($futurebb_user['restricted_privs'], 'edit')) {
	httperror(403);
}
if (($cur_post['closed'] || $cur_post['archived']) && (!$futurebb_user['g_mod_privs'] && !$futurebb_user['g_admin_privs'])) {
	httperror(403);
}
$can_edit_subject = ($cur_post['first_post_id'] == $pid); //only allow subject editing if the first post
$breadcrumbs = array('Index' => '', $cur_post['forum_name'] => $cur_post['furl'], $cur_post['subject'] => $cur_post['furl'] . '/' . $cur_post['turl'], 'Edit post' => '!nourl!');
include_once FORUM_ROOT . '/app_resources/includes/parser.php';
include FORUM_ROOT . '/app_resources/includes/search.php';
if (isset($_POST['form_sent']) || isset($_POST['preview'])) {
	$errors = array();
	if ($futurebb_config['enable_bbcode']) {
		BBCodeController::error_check($_POST['content'], $errors);
	}
	
	if ($can_edit_subject && trim($_POST['subject']) == '')
		$errors[] = translate('blanksubject');
	if (trim($_POST['content']) == '')
		$errors[] = translate('blankcontent');
		
	if (empty($errors) && !isset($_POST['preview'])) {
		if ($can_edit_subject && isset($_POST['subject']) && $_POST['subject'] != $cur_post['subject']) {
			//change topic subject
			$name = URLEngine::make_friendly($_POST['subject']);
			$base_name = $name;
			$result = $db->query('SELECT url FROM `#^topics` WHERE url LIKE \'' . $db->escape($name) . '%\'') or error('Failed to check for similar URLs', __FILE__, __LINE__, $db->error());
			$urllist = array();
			while (list($url) = $db->fetch_row($result)) {
				$urllist[] = $url;
			}
			$ok = false;
			$add_num = 0;
			while (!$ok) {
				$ok = true;
				if (in_array($name, $urllist)) {
					$add_num++;
					$name = $base_name . '-' . $add_num;
					$ok = false;
				}
			}
			$db->query('UPDATE `#^topics` SET subject=\'' . $db->escape($_POST['subject']) . '\',url=\'' . $db->escape($name) . '\' WHERE id=' . $cur_post['tid']) or error('Failed to update topic subject', __FILE__, __LINE__, $db->error());
			$db->query('INSERT INTO `#^topics`(url,redirect_id) VALUES(\'' . $db->escape($cur_post['turl']) . '\',' . $cur_post['tid'] . ')') or error('Failed to create redirect topic', __FILE__, __LINE__, $db->error());
		}
		update_search_index($pid,$_POST['content']);
		if (isset($_POST['silentedit']) && ($futurebb_user['g_mod_privs'] || $futurebb_user['g_admin_privs'])) {
			$last_edited_sql = '';
		} else {
			$last_edited_sql = 'last_edited=' . time() . ',last_edited_by=' . $futurebb_user['id'] . ',';
		}
		$db->query('UPDATE `#^posts` SET content=\'' . $db->escape($_POST['content']) . '\',parsed_content=\'' . $db->escape(BBCodeController::parse_msg($_POST['content'], !isset($_POST['hidesmilies']))) . '\',' . $last_edited_sql . 'disable_smilies=' . intval(isset($_POST['hidesmilies'])) . ' WHERE id=' . $pid) or error('Failed to update post', __FILE__, __LINE__, $db->error());
		redirect($base_config['baseurl'] . '/posts/' . $pid); return;
	} else if (isset($_POST['preview']) && empty($errors)) {
		echo '<div class="quotebox preview">' . BBCodeController::parse_msg($_POST['content'], !isset($_POST['hidesmilies'])) . '</div>';
	} else {
		echo '<p>' . translate('errordesc') . '<ul>';
		foreach ($errors as $val) {
			echo '<li>' . $val . '</li>';
		}
		echo '</ul></p>';
	}
	$content = $_POST['content'];
} else {
	$content = $cur_post['content'];
}
?>
<form action="<?php echo $base_config['baseurl']; ?>/edit/<?php echo $pid; ?>" method="post" enctype="multipart/form-data">
	<?php if ($can_edit_subject) { ?>
	<p><?php echo translate('subject'); ?><br /><input type="text" name="subject" value="<?php echo htmlspecialchars($cur_post['subject']); ?>" size="50" /></p>
	<?php } ?>
	<?php ExtensionConfig::run_hooks('bbcode_toolbar'); ?>
	<p><textarea name="content" id="message" rows="20" cols="70"><?php echo htmlspecialchars($content); ?></textarea></p>
	<?php ExtensionConfig::run_hooks('bbcode_toolbar_bottom'); ?>
	<?php
	if ($futurebb_user['g_mod_privs'] || $futurebb_user['g_admin_privs']) {
		?>
		<p><input type="checkbox" name="silentedit" id="silentedit"<?php if (isset($_POST['silentedit'])) echo ' checked="checked"'; ?> value="on" /><label for="silentedit"><?php echo translate('silentedit'); ?></label></p>
		<?php
	}
	?>
	<p><input type="submit" name="form_sent" value="<?php echo translate('save'); ?>" /> <input type="submit" name="preview" value="<?php echo translate('preview'); ?>" /> <input name="hidesmilies" type="checkbox" value="1"<?php if ((!isset($_POST['content']) && $cur_post['disable_smilies']) || (isset($_POST['preview']) && isset($_POST['hidesmilies']))) echo ' checked="checked"'; ?>  id="disablesmilies" /> <label for="disablesmilies"><?php echo translate('disablesmilies'); ?></label></p>
</form>