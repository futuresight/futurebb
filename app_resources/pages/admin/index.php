<?php
if (!$futurebb_user['g_admin_privs']) {
	httperror(403);
}
translate('<addfile>', 'admin');
$page_title = 'FutureBB Administration';
include FORUM_ROOT . '/app_resources/includes/admin.php';

if (!isset($dirs[2])) {
	$dirs[2] = '';
}

// Check for form submissions
if(isset($_POST['form_sent'])) {
	$cfg_list = array(
		//format: 'name'		=> 'type'
		'avatars'				=> 'bool',
		'avatar_max_filesize'	=> 'int',
		'avatar_max_height'		=> 'int',
		'avatar_max_width'		=> 'int',
		'announcement_enable'	=> 'bool',
		'verify_registrations'	=> 'bool',
		'disable_registrations'	=> 'bool',
		'maintenance'			=> 'bool',
		'show_post_count'		=> 'bool',
		'announcement_text'		=> 'string',
		'board_title'			=> 'string',
		'admin_email'			=> 'string',
		'footer_text'			=> 'string',
		'maintenance_message'	=> 'string',
		'rules'					=> 'string',
		'default_language'		=> 'string',
		'allow_privatemsg'		=> 'bool',
		'allow_notifications'	=> 'bool',
		'addl_header_links'		=> 'string',
		'online_timeout'		=> 'int',
		'topics_per_page'		=> 'int',
		'posts_per_page'		=> 'int',
		'sig_max_length'		=> 'int',
		'sig_max_lines'			=> 'int',
		'sig_max_height'		=> 'int',
		'max_quote_depth'		=> 'int',
		'enable_bbcode'			=> 'bool',
		'enable_smilies'		=> 'bool',
	);
	//check first
	$errors = array();
	if ($_POST['config']['max_quote_depth'] < 2) {
		$errors[] = translate('quotedepth>1');
	}
	if (empty($errors)) {
		if ($_POST['config']['turn_on_maint'] != '') {
			$_POST['config']['turn_on_maint'] = time() + 60 * intval($_POST['config']['turn_on_maint']);
			$cfg_list['turn_on_maint'] = 'int';
		}
		if ($_POST['config']['turn_off_maint'] != '') {
			$_POST['config']['turn_off_maint'] = time() + 60 * intval($_POST['config']['turn_off_maint']);
			$cfg_list['turn_off_maint'] = 'int';
		}
		if ($futurebb_config['turn_off_maint'] > time() && !isset($_POST['maintenance'])) {
			$_POST['config']['turn_off_maint'] = 0;
			$cfg_list['turn_off_maint'] = 'int';
		}
		foreach ($cfg_list as $name => $type) {
			switch ($type) {
				case 'bool':
					$val = (isset($_POST['config'][$name]) ? '1' : '0'); break;
				case 'string':
					$val = $_POST['config'][$name]; break;
				case 'int':
					$val = intval($_POST['config'][$name]);
			}
			set_config($name, $val);
		}
		header('Refresh: 0'); return;
	}
}

//automatically check for updates
if (ini_get('allow_url_fopen')) {
	if ($futurebb_config['last_update_check'] < time() - 60 * 60 * 24 && !$futurebb_config['new_version']) {
		$version = file_get_contents('http://futuresight.org/api/getversion/futurebb');
		if ($version > FUTUREBB_VERSION) {
			$q = new DBInsert('reports', array('post_type' => 'special', 'reason' => translate('newversionmsg'), 'time_reported' => time()), 'Failed to insert update notification');
			$q->commit();
			set_config('new_version', 1);
		}
		set_config('last_update_check', time());
	}
}
?>
<div class="container">
	<?php make_admin_menu();
	if (isset($errors) && !empty($errors)) {
		echo '<div class="forum_content rightbox admin"><h3>' . translate('fixerrors') . '</h3><ul>';
		foreach ($errors as $error) {
			echo '<li>' . $error . '</li>';
		}
		echo '</ul></div>';
	} ?>
	<div class="forum_content rightbox admin">
    	<h3><?php echo translate('toc'); ?></h3>
    	<ul>
        	<li><a href="#boardsettings"><?php echo translate('boardsettings'); ?></a></li>
            <li><a href="#registration"><?php echo translate('registration'); ?></a></li>
            <li><a href="#siteappearance"><?php echo translate('siteappearance'); ?></a></li>
            <li><a href="#bbcode"><?php echo translate('bbcode'); ?></a></li>
            <li><a href="#signatures"><?php echo translate('signatures'); ?></a></li>
            <li><a href="#avatars"><?php echo translate('avatars'); ?></a></li>
            <li><a href="#maint"><?php echo translate('maintenance'); ?></a></li>
        </ul>
		<form action="<?php echo $base_config['baseurl']; ?>/admin" method="post" enctype="multipart/form-data">
		<h3 id="boardsettings"><?php echo translate('boardsettings'); ?></h3>
		<table border="0" class="optionstable">
			<tr>
				<th><?php echo translate('boardtitle'); ?></th>
				<td><input type="text" name="config[board_title]" value="<?php echo htmlspecialchars($futurebb_config['board_title']); ?>" /></td>
			</tr>
			<tr>
				<th><?php echo translate('adminemail'); ?></th>
				<td><input type="text" name="config[admin_email]" value="<?php echo htmlspecialchars($futurebb_config['admin_email']); ?>" /></td>
			</tr>
			<tr>
				<th><?php echo translate('onlinetimeout'); ?></th>
				<td><input type="text" name="config[online_timeout]" value="<?php echo intval($futurebb_config['online_timeout']); ?>" size="5" /><br /><?php echo translate('onlinetimeoutdesc'); ?></td>
			</tr>
			<tr>
				<th>Default language</th>
				<td><select name="config[default_language]"><?php
				$handle = opendir(FORUM_ROOT . '/app_config/langs');
				while ($f = readdir($handle)) {
					if ($f != '.' && $f != '..') {
						$f = htmlspecialchars($f);
						echo '<option value="' . $f . '"';
						if ($f == $futurebb_config['default_language']) {
							echo ' selected="selected"';
						}
						echo '>' . $f . '</option>';
					}
				}
				?></select></td>
			</tr>
			<tr>
				<th><?php echo translate('allowPM'); ?></th>
				<td><input type="checkbox" name="config[allow_privatemsg]" <?php if($futurebb_config['allow_privatemsg'] == 1) echo 'checked="checked"'; ?> /></td>
			</tr>
			<tr>
				<th><?php echo translate('allownotifs'); ?></th>
				<td><input type="checkbox" name="config[allow_notifications]" <?php if($futurebb_config['allow_notifications'] == 1) echo 'checked="checked"'; ?> /></td>
			</tr>
		</table>
        
        <h3 id="registration"><?php echo translate('registration'); ?></h3>
        <table border="0" class="optionstable">
			<tr>
				<th><?php echo translate('verifyregs'); ?></th>
                <td><input name="config[verify_registrations]" type="checkbox" <?php if($futurebb_config['verify_registrations'] == 1) echo 'checked="checked"'; ?> value="1" id="verify_registrations" /> <label for="verify_registrations"><?php echo translate('verifyregsdesc'); ?></label></td>
            </tr>
            <tr>
            	<th><?php echo translate('disableregs'); ?></th>
               	<td><input name="config[disable_registrations]" type="checkbox" <?php if($futurebb_config['disable_registrations'] == 1) echo 'checked="checked"'; ?> value="1" id="disable_registrations" /> <label for="disable_registrations"><?php echo translate('disableregsdesc'); ?></label></td>
            </tr>
        </table>
        <h4><?php echo translate('rules'); ?></h4>
		<p><?php echo translate('rulesdesc'); ?><br /><textarea name="config[rules]" cols="50" rows="4"><?php echo htmlspecialchars($futurebb_config['rules']); ?></textarea></p>
        
        <h3 id="siteappearance"><?php echo translate('siteappearance'); ?></h3>
		<h4><?php echo translate('announcement'); ?></h4>
		<p><?php echo translate('announcementdesc'); ?><br />
		
		<input name="config[announcement_enable]" type="checkbox" <?php if($futurebb_config['announcement_enable'] == 1) echo 'checked="checked"'; ?> value="1" id="announcement_enable" /> <label for="announcement_enable"><?php echo translate('enableannouncement'); ?></label><br />
		<textarea name="config[announcement_text]" cols="50" rows="4"><?php echo htmlspecialchars($futurebb_config['announcement_text']); ?></textarea></p>
		<p><?php echo translate('customfooter'); ?><br /><textarea name="config[footer_text]" cols="50" rows="3"><?php echo htmlspecialchars($futurebb_config['footer_text']); ?></textarea></p>
		<p><?php echo translate('addl_header_links'); ?><br /><textarea name="config[addl_header_links]" cols="50" rows="3"><?php echo htmlspecialchars($futurebb_config['addl_header_links']); ?></textarea></p>
		<p><input type="text" name="config[topics_per_page]" value="<?php echo $futurebb_config['topics_per_page']; ?>" size="3" /> <?php echo translate('topicsperpage'); ?><br /><input type="text" name="config[posts_per_page]" value="<?php echo $futurebb_config['posts_per_page']; ?>" size="3" /> <?php echo translate('postsperpage'); ?></p>
		<p><input name="config[show_post_count]" type="checkbox" <?php if($futurebb_config['show_post_count'] == 1) echo 'checked="checked"'; ?> value="1" id="show_post_count" /> <label for="show_post_count"><?php echo translate('showpostcounts'); ?></label> - <?php echo translate('showpostcountsdesc'); ?></p>
        
        <h3 id="bbcode"><?php echo translate('bbcode'); ?></h3>
        <table border="0" class="optionstable">
       		<tr>
				<th><?php echo translate('enablebbcode'); ?></th>
				<td><input name="config[enable_bbcode]" type="checkbox" <?php if($futurebb_config['enable_bbcode'] == 1) echo 'checked="checked"'; ?> value="1" id="enable_bbcode" /> <label for="enable_bbcode"><?php echo translate('enablebbcodedesc'); ?></label></td>
			</tr>
            <tr>
				<th><?php echo translate('enablesmilies'); ?></th>
				<td><input name="config[enable_smilies]" type="checkbox" <?php if($futurebb_config['enable_smilies'] == 1) echo 'checked="checked"'; ?> value="1" id="enable_smilies" /> <label for="enable_smilies"><?php echo translate('enablesmiliesdesc'); ?></label></td>
			</tr>
			<tr>
				<th><?php echo translate('maxquotedepth'); ?></th>
				<td><input type="text" name="config[max_quote_depth]" value="<?php echo htmlspecialchars($futurebb_config['max_quote_depth']); ?>" size="5" /></td>
			</tr>
        </table>
        
        <h3 id="signatures"><?php echo translate('signatures'); ?></h3>
		<p><?php echo translate('zeronolimit'); ?></p>
		<table border="0" class="optionstable">
			<tr>
				<th><?php echo translate('maxchars'); ?></th>
				<td><input type="text" name="config[sig_max_length]" value="<?php echo htmlspecialchars($futurebb_config['sig_max_length']); ?>" size="5" /></td>
			</tr>
			<tr>
				<th><?php echo translate('maxlines'); ?></th>
				<td><input type="text" name="config[sig_max_lines]" value="<?php echo htmlspecialchars($futurebb_config['sig_max_lines']); ?>" size="5" /></td>
			</tr>
			<tr>
				<th><?php echo translate('maxheight'); ?></th>
				<td><input type="text" name="config[sig_max_height]" value="<?php echo htmlspecialchars($futurebb_config['sig_max_height']); ?>" size="5" /></td>
			</tr>
		</table>
        
        <h3><?php echo translate('avatars'); ?></h3>
        <table border="0" class="optionstable">
        	<tr>
            	<th><?php echo translate('avatars'); ?></th>
                <td><input name="config[avatars]" type="checkbox" <?php if($futurebb_config['avatars'] == 1) echo 'checked="checked"'; ?> value="1" id="avatars" /> <label for="avatars"><?php echo translate('enableavatarsdesc'); ?></label></td>
            </tr>
            <tr>
            	<th><?php echo translate('avatarmaxfilesize'); ?></th>
                <td><input type="text" name="config[avatar_max_filesize]" value="<?php echo intval($futurebb_config['avatar_max_filesize']); ?>" size="5" /><br /><?php echo translate('avatarfilesizedesc'); ?></td>
            </tr>
            <tr>
            	<th><?php echo translate('maxwidth'); ?></th>
                <td><input type="text" name="config[avatar_max_width]" value="<?php echo intval($futurebb_config['avatar_max_width']); ?>" size="5" /><br /><?php echo translate('avatarmaxwidthdesc'); ?></td>
            </tr>
            <tr>
            	<th><?php echo translate('maxheight'); ?></th>
                <td><input type="text" name="config[avatar_max_height]" value="<?php echo intval($futurebb_config['avatar_max_height']); ?>" size="5" /><br /><?php echo translate('avatarmaxheightdesc'); ?></td>
            </tr>
        </table>
		
		<h3 id="maint"><?php echo translate('maintenance'); ?></h3>
		<p><input name="config[maintenance]" type="checkbox" <?php if($futurebb_config['maintenance'] == 1) echo 'checked="checked"'; ?> value="1" id="maintenance" /> <label for="maintenance"><?php echo translate('maintenancemode'); ?></label><br />
		<?php echo translate('maintenancemsg'); ?><br /><textarea name="config[maintenance_message]" rows="3" cols="50"><?php echo htmlspecialchars($futurebb_config['maintenance_message']); ?></textarea></p>
		<p><?php echo translate('autoactivatemaint'); ?> <input type="text" name="config[turn_on_maint]" size="5" /> <?php echo strtolower(translate('minutes')); ?>.<?php if ($futurebb_config['turn_on_maint']) echo ' ' . translate('maintschedpanel', user_date($futurebb_config['turn_on_maint'])); ?></p>
		<p><?php echo translate('autodeactivatemaint'); ?> <input type="text" name="config[turn_off_maint]" size="5" /> <?php echo strtolower(translate('minutes')); ?>.<?php if ($futurebb_config['turn_off_maint']) echo ' ' . translate('maintoffschedpanel', user_date($futurebb_config['turn_off_maint'])); ?></p>
		
		<p><input name="form_sent" type="submit" value="<?php echo translate('save'); ?>" /></p>
		</form>
		<h3><?php echo translate('serverinfo'); ?></h3>
		<table border="0">
			<tr>
				<td><?php echo translate('fbbversion'); ?></td><td><?php echo FUTUREBB_VERSION; ?></td>
			</tr>
			<tr>
				<td><?php echo translate('database'); ?></td><td><?php echo $db->name(); ?> <?php echo $db->version(); ?></td>
			</tr>
			<tr>
				<td><?php echo translate('os'); ?></td><td><?php echo PHP_OS; ?></td>
			</tr>
		</table>
	</div>
</div>