<?php
$fid = intval($dirs[4]);
$result = $db->query('SELECT name,cat_id,description,view_groups,topic_groups,reply_groups,archived FROM `#^forums` WHERE id=' . $fid) or error('Failed to find forum', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result)) {
	httperror(404);
}
if (isset($_POST['update_forum'])) {
	$view = '-';
	foreach ($_POST['view'] as $key => $val) {
		$view .= $key . '-';
	}
	$topics = '-';
	foreach ($_POST['topics'] as $key => $val) {
		$topics .= $key . '-';
	}
	$replies = '-';
	foreach ($_POST['reply'] as $key => $val) {
		$replies .= $key . '-';
	}
	$db->query('UPDATE `#^forums` SET description=\'' . $db->escape($_POST['desc']) . '\',view_groups=\'' . $view . '\',topic_groups=\'' . $topics . '\',reply_groups=\'' . $replies . '\',cat_id=' . intval($_POST['category']) . ',archived=' . intval($_POST['archive']) . ' WHERE id=' . $fid) or error('Failed to update forum', __FILE__, __LINE__, $db->error());
	if (isset($_POST['popup'])) {
		?>
<script type="text/javascript">
window.close();
</script>
        <?php
		return;
	} else {
		header('Location: ' . $base_config['baseurl'] . '/admin/forums'); return;
	}
}

$cur_forum = $db->fetch_assoc($result);
if (isset($_GET['popup'])) {
	?>
    <script type="text/javascript">
	var allLinks = document.getElementsByTagName('a');
	for (curLink in allLinks) {
		curLink.onclick = function() {
			window.close();
			return false;
		};
	}
	</script>
    <style type="text/css">
	#futurebb .forum_header {
		display:none;
	}
	</style>
    <?php
} else {
	$breadcrumbs = array('Index' => '', 'Administration' => 'admin', 'Forums' => 'admin/forums', $cur_forum['name'] => '/admin/forums/edit/' . $fid);
}
?>
<div class="forum_content">
    <form action="<?php echo $base_config['baseurl']; ?>/admin/forums/edit/<?php echo $fid; ?>" method="post" enctype="multipart/form-data">
        <h3><?php echo translate('information'); ?></h3>
        <table border="0">
            <tr>
                <td><?php echo translate('forumname'); ?></td>
                <td><?php echo htmlspecialchars($cur_forum['name']); ?></td>
            </tr>
            <tr>
                <td><?php echo translate('category'); ?></td>
                <td><select name="category"><?php
                $result = $db->query('SELECT id,name FROM `#^categories` ORDER BY sort_position ASC') or error('Failed to get categories', __FILE__, __LINE__, $db->error());
                while (list($id,$name) = $db->fetch_row($result)) {
                    echo '<option value="' . $id . '"';
                    if ($id == $cur_forum['cat_id']) {
                        echo ' selected="selected"';
                    }
                    echo '>' . htmlspecialchars($name) . '</option>';
                }
                ?></select></td>
            </tr>
            <tr>
                <td><?php echo translate('description'); ?></td>
                <td><textarea name="desc" rows="5" cols="40"><?php echo htmlspecialchars($cur_forum['description']); ?></textarea></td>
            </tr>
			<tr>
				<td>Archive forum</td>
				<td><input type="radio" name="archive" value="1" id="archive1"<?php if ($cur_forum['archived']) echo ' checked="checked"'; ?> /><label for="archive1"><?php echo translate('yes'); ?></label> <input type="radio" name="archive" value="0" id="archive0"<?php if (!$cur_forum['archived']) echo ' checked="checked"'; ?> /><label for="archive0"><?php echo translate('no'); ?></label></td>
			</tr>
        </table>
        <h3><?php echo translate('permissions'); ?></h3>
        <table border="0">
            <tr>
                <th><?php echo translate('grouptitle'); ?></th>
                <th><?php echo translate('viewforum'); ?></th>
                <th><?php echo translate('posttopics'); ?></th>
                <th><?php echo translate('postreplies'); ?></th>
            </tr>
        <?php
        $result = $db->query('SELECT g_id,g_name FROM `#^user_groups` ORDER BY g_permanent ASC,g_title ASC') or error('Failed to get user groups', __FILE__, __LINE__, $db->error());
        while (list($id,$name) = $db->fetch_row($result)) {
            echo '
            <tr>
                <td>' . htmlspecialchars($name) . '</td>
                <td><input type="checkbox" name="view[' . $id . ']"';
                if (strstr($cur_forum['view_groups'], '-' . $id . '-')) {
                    echo ' checked="checked"';
                }
                echo ' /></td>
                <td><input type="checkbox" name="topics[' . $id . ']"';
                if ($id != '2' && strstr($cur_forum['topic_groups'], '-' . $id . '-')) {
                    echo ' checked="checked"';
                }
				if ($id == 2) {
					echo ' disabled="disabled"';
				}
                echo ' /></td>
                <td><input type="checkbox" name="reply[' . $id . ']"';
                if ($id != 2 && strstr($cur_forum['reply_groups'], '-' . $id . '-')) {
                    echo ' checked="checked"';
                }
				if ($id == 2) {
					echo 'disabled="disabled"';
				}
                echo ' /></td>
            </tr>';
        }
        ?>
        </table>
        <p><?php if (isset($_GET['popup'])) {
			echo '<input type="hidden" name="popup" value="true" />';
		} ?><input type="submit" name="update_forum" value="<?php echo translate('update'); ?>" /></p>
    </form>
</div>