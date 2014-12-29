<?php
$page_title = 'Interface Editing History';

$breadcrumbs = array(translate('administration') => 'admin', translate('interface') => 'admin/interface', 'History' => 'admin/interface/history');

$page_list = array();
$q = new DBSelect('pages', array('*'), '', 'Failed to get page list');
$result = $q->commit();
while ($page = $db->fetch_assoc($result)) {
	$page_list[$page['id']] = $page;
}

$q = new DBSelect('interface_history', array('h.*', 'u.username'), 'time>' . (time() - 60 * 60 * 24 * 60), 'Failed to retrieve history entries');
$q->set_order('time DESC');
$q->table_as('h');
$q->add_join(new DBJoin('users', 'u', 'u.id=h.user', 'LEFT'));
$result = $q->commit();

$page_edits = array();
$field_edits = array();
$lang_edits = array();
$lang_ids = array();
while ($entry = $db->fetch_assoc($result)) {
	if ($entry['area'] == 'pages') {
		if (!isset($page_edits[$entry['field']])) {
			$page_edits[$entry['field']] = array();
		}
		$page_edits[$entry['field']][] = array('time' => $entry['time'], 'old_value' => base64_decode($entry['old_value']), 'username' => $entry['username'], 'action' => $entry['action']);
	} else if ($entry['area'] == 'interface') {
		if (!isset($field_edits[$entry['field']])) {
			$field_edits[$entry['field']] = array();
		}
		$field_edits[$entry['field']][] = array('time' => $entry['time'], 'old_value' => $entry['old_value'], 'username' => $entry['username'], 'action' => $entry['action']);
	} else if ($entry['area'] == 'language') {
		if (!isset($lang_edits[$entry['field']])) {
			$lang_edits[$entry['field']] = array();
			$lang_ids[] = $entry['field'];
		}
		$lang_edits[$entry['field']][] = array('time' => $entry['time'], 'old_value' => $entry['old_value'], 'username' => $entry['username'], 'action' => $entry['action']);
	}
}

//put the latest value for each language entry changed
if (sizeof($lang_edits)) {
	$result = $db->query('SELECT * FROM `#^language` WHERE id IN(' . implode(',', $lang_ids) . ')') or enhanced_error('Failed to find latest language values', true);
	while ($lang_entry = $db->fetch_assoc($result)) {
		$lines = array();
		foreach ($lang_entry as $db_key => $db_val) {
			$lines[] = $db_key . '=>' . $db_val;
		}
		$lang_edits[$lang_entry['id']][0]['new_value'] = implode("\n", $lines);
	}
	foreach ($lang_edits as &$entry) {
		if (!isset($entry[0]['new_value'])) {
			$entry[0]['new_value'] = '';
		}
	}
}

foreach ($page_edits as $pageid => &$page_entry) {
	for ($i = 0; $i < sizeof($page_entry); $i++) {
		if ($page_entry[$i]['action'] == 'delete') {
			$page_entry[$i]['new_value'] = '';
		} else if ($i == 0) {
			$lines = array();
			foreach ($page_list[$pageid] as $db_key => $db_val) {
				$lines[] = $db_key . '=>' . $db_val;
			}
			$page_entry[$i]['new_value'] = implode("\n", $lines);
		} else {
			$page_entry[$i]['new_value'] = $page_entry[$i - 1]['old_value'];
		}
		$page_entry[$i]['id'] = $pageid;
	}
}

foreach ($lang_edits as $id => &$lang_entry) {
	for ($i = 0; $i < sizeof($lang_entry); $i++) {
		if ($i != 0) {
			$lang_entry[$i]['new_value'] = $lang_entry[$i - 1]['old_value'];
		}
		$lang_entry[$i]['id'] = $id;
	}
}

foreach ($field_edits as $field => &$field_entry) {
	for ($i = 0; $i < sizeof($field_entry); $i++) {
		if ($i == 0) {
			if ($field == 'header') {
				$field_entry[$i]['new_value'] = $futurebb_config['header_links'];
			}
			if ($field == 'mod_pages') {
				$field_entry[$i]['new_value'] = base64_decode($futurebb_config['mod_pages']);
			}
			if ($field == 'admin_pages') {
				$field_entry[$i]['new_value'] = base64_decode($futurebb_config['admin_pages']);
			}
		} else {
			$field_entry[$i]['new_value'] = $field_entry[$i - 1]['old_value'];
		}
		$field_entry[$i]['field'] = $field;
	}
}

function pagediff($page1, $page2) {
	if ($page1['time'] > $page2['time']) {
		return -1;
	} else if ($page1['time'] < $page2['time']) {
		return 1;
	} else {
		return 0;
	}
}

function diff($entry, &$old_disp, &$new_disp) {
	$oldlines = explode("\n", $entry['old_value']);
	$oldparts = array();
	foreach ($oldlines as $line) {
		$parts = explode('=>', $line, 2);
		if (sizeof($parts) > 1) {
			$oldparts[$parts[0]] = $parts[1];
		}
	}
	$newlines = explode("\n", $entry['new_value']);
	$newparts = array();
	foreach ($newlines as $line) {
		$parts = explode('=>', $line, 2);
		if (sizeof($parts) > 1) {
			$newparts[$parts[0]] = $parts[1];
		}
	}
	$old_disp = array();
	$new_disp = array();
	foreach ($oldparts as $key => $val) {
		if (!isset($newparts[$key]) || $newparts[$key] != $val) {
			$old_disp[] = '<b>' . $key . ':</b> ' . $val;
			$new_disp[] = '<b>' . $key . ':</b> ' . (isset($newparts[$key]) ? $newparts[$key] : '<i>None</i>');
		}
	}
}
?>
<div style="max-width:100%; overflow: auto">
<?php
if (!empty($page_edits)) {
	?>
	<h2>Page editing history</h2>
	<table border="0">
		<tr>
			<th>Page ID</th>
			<th>Username</th>
			<th>Time</th>
			<th>Old value</th>
			<th>New value</th>
		</tr>
		<?php
		$page_edit_final_list = array();
		foreach ($page_edits as $key => $cur_entry) {
			foreach ($cur_entry as $subentry) {
				$page_edit_final_list[] = $subentry;
			}
		}
		usort($page_edit_final_list, 'pagediff');
		foreach ($page_edit_final_list as $page_entry) {
			if ($page_entry['action'] == 'create') {
				$old_disp = array('<i>New page</i>');
				$lines = explode("\n", $page_entry['new_value']);
				$new_disp = array();
				foreach ($lines as $line) {
					$parts = explode('=>', $line);
					if (sizeof($parts) > 1) {
						$new_disp[] = '<b>' . $parts[0] . '</b>: ' . $parts[1];
					}
				}
			} else {
				diff($page_entry, $old_disp, $new_disp);
				if ($page_entry['action'] == 'delete') {
					$new_disp = array('<i>Deleted</i>');
				}
			}
			echo '<tr><td>' . $page_entry['id'] . '</td><td>' . htmlspecialchars($page_entry['username']) . '</td><td>' . user_date($page_entry['time']) . '</td><td><pre>' . implode('<br />', $old_disp) . '</pre></td><td><pre>' . implode('<br />', $new_disp) . '</pre></td></tr>';
		}
		?>
	</table>
<?php
}

if (!empty($lang_edits)) {
	?>
<h2>Language editing history</h2>
<table border="0">
	<tr>
		<th>Language ID</th>
		<th>Username</th>
		<th>Time</th>
		<th>Old value</th>
		<th>New value</th>
	</tr>
	<?php
	$lang_edit_final_list = array();
	foreach ($lang_edits as $id => $lang_entry) {
		for ($i = 0; $i < sizeof($lang_entry); $i++) {
			$lang_edit_final_list[] = $lang_entry[$i];
		}
	}
	usort($lang_edit_final_list, 'pagediff');
	foreach ($lang_edit_final_list as $entry) {
		if ($lang_entry['action'] == 'create') {
			$old_disp = array('<i>New entry</i>');
			$lines = explode("\n", $lang_entry['new_value']);
			$new_disp = array();
			foreach ($lines as $line) {
				$parts = explode('=>', $line);
				if (sizeof($parts) > 1) {
					$new_disp[] = '<b>' . $parts[0] . '</b>: ' . $parts[1];
				}
			}
		} else {
			diff($lang_entry, $old_disp, $new_disp);
			if ($lang_entry['action'] == 'delete') {
				$new_disp = array('<i>Deleted</i>');
			}
		}
		echo '<tr><td>' . $lang_entry['id'] . '</td><td>' . htmlspecialchars($lang_entry['username']) . '</td><td>' . user_date($lang_entry['time']) . '</td><td><pre>' . implode('<br />', $old_disp) . '</pre></td><td><pre>' . implode('<br />', $new_disp) . '</pre></td></tr>';
	}
	?>
</table>

<?php
}

if (!empty($field_edits)) {
?>
<h2>Field editing history</h2>
	<table border="0">
		<tr>
			<th>Field</th>
			<th>Username</th>
			<th>Time</th>
			<th>Old value</th>
			<th>New value</th>
		</tr>
		<?php
		$field_edit_final_list = array();
		foreach ($field_edits as $field => $field_entry) {
			for ($i = 0; $i < sizeof($field_entry); $i++) {
				$field_edit_final_list[] = $field_entry[$i];
			}
		}
		usort($field_edit_final_list, 'pagediff');
		foreach ($field_edit_final_list as $entry) {
			if (in_array($entry['field'], array('mod_pages', 'admin_pages'))) {
				diff($entry, $old_disp, $new_disp);
				$old = implode('<br />', $old_disp);
				$new = implode('<br />', $new_disp);
			} else {
				$old = htmlspecialchars($entry['old_value']);
				$new = htmlspecialchars($entry['new_value']);
			}
			echo '<tr><td>' . $entry['field'] . '</td><td>' . htmlspecialchars($entry['username']) . '</td><td>' . user_date($entry['time']) . '</td><td><pre>' . $old . '</pre></td><td><pre>' . $new . '</pre></td></tr>';
		}
		?>
	</table>
<?php
}
?>
</div>
<?php
$q = new DBDelete('interface_history', 'time<' . (time() - 60 * 60 * 24 * 60), 'Failed to delete old history items');
$q->commit();