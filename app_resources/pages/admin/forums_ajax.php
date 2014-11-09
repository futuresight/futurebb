<?php
if (isset($_POST['form_sent'])) {
	//process form input
	//update old forums
	$q = new DBSelect('forums', array('c.name AS cat_name','c.sort_position AS cat_sort_position','f.id','f.cat_id','f.sort_position','f.name AS forum_name','f.url AS furl'), 'c.id IS NOT NULL', 'Failed to get forum list');
	$q->table_as('f');
	$q->set_order('c.sort_position,f.sort_position');
	$q->add_join(new DBLeftJoin('categories', 'c', 'c.id=f.cat_id'));
	$result = $q->commit();
	while ($forum = $db->fetch_assoc($result)) {
		$id = $forum['id'];
		if ($forum['sort_position'] != $_POST['sort_order'][$id]) {
			$q = new DBUpdate('forums', array('sort_position' => intval($_POST['sort_order'][$id])), 'id=' . $id, 'Failed to update sort order');
			$q->commit();
		}
		$title = $forum['forum_name'];
		if ($_POST['title'][$id] != $title && isset($_POST['title'][$id]) && $_POST['title'][$id] != '') {
			//make redirect forum
			$furl = $forum['furl'];
			$base_name = URLEngine::make_friendly($_POST['title'][$id]);
			$name = $base_name;
			$add_num = 0;
			$result = $db->query('SELECT url FROM `#^forums` WHERE url LIKE \'' . $db->escape($name) . '%\'') or error('Failed to check for similar URLs', __FILE__, __LINE__, $db->error());
			$urllist = array();
			while (list($url) = $db->fetch_row($result)) {
				$urllist[] = $url;
			}
			$ok = false;
			while (!$ok) {
				$ok = true;
				if (in_array($name, $urllist)) {
					$add_num++;
					$name = $base_name . $add_num;
					$ok = false;
				}
			}
			$db->query('UPDATE `#^forums` SET url=\'' . $name . '\',name=\'' . $db->escape($_POST['title'][$id]) . '\' WHERE id=' . intval($id)) or error('Failed to update forum URL', __FILE__, __LINE__, $db->error());
			$db->query('INSERT INTO `#^forums`(url,redirect_id) VALUES(\'' . $db->escape($furl) . '\',' . $id . ')') or error('Failed to insert redirect forum', __FILE__, __LINE__, $db->error());
		}
	}
	
	//delete any forums marked for deletion
	if (isset($_POST['delete'])) {
		$dels = array();
		foreach ($_POST['delete'] as $key => $val) {
			$dels[] = intval($key);
		}
		$db->query('DELETE FROM `#^forums` WHERE id IN(' . implode(',', $dels) . ')') or error('Failed to delete forum', __FILE__, __LINE__, $db->error());
	}
	
	//delete any marked categories
	if (isset($_POST['delete_cat'])) {
		$dels = array();
		foreach ($_POST['delete_cat'] as $key => $val) {
			$dels[] = intval($key);
		}
		$db->query('DELETE FROM `#^categories` WHERE id IN(' . implode(',', $dels) . ')') or error('Failed to delete category', __FILE__, __LINE__, $db->error());
	}

	//create any new forums
	if (isset($_POST['new_forum'])) {
		//get allowed user groups
		$view = array();
		$topics = array();
		$replies = array();
		$result = $db->query('SELECT g_id AS id,g_view_forums,g_post_topics,g_post_replies FROM `#^user_groups`') or enhanced_error('Failed to find user groups', true);
		while ($group = $db->fetch_assoc($result)) {
			if ($group['g_view_forums']) {
				$view[] = $group['id'];
			}
			if ($group['g_post_topics']) {
				$topics[] = $group['id'];
			}
			if ($group['g_post_replies']) {
				$replies[] = $group['id'];
			}
		}
		foreach ($_POST['new_forum'] as $key => $forum_name) {
			//make new forum
			$base_name = URLEngine::make_friendly($forum_name);
			$name = $base_name;
			$add_num = 0;
			
			//check for forums with the same URL
			$result = $db->query('SELECT url FROM `#^forums` WHERE url LIKE \'' . $db->escape($name) . '%\'') or error('Failed to check for similar URLs', __FILE__, __LINE__, $db->error());
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
					$name = $base_name . $add_num;
					$ok = false;
				}
			}
			$db->query('INSERT INTO `#^forums`(url,name,cat_id,sort_position,view_groups,topic_groups,reply_groups) VALUES(\'' . $db->escape($name) . '\',\'' . $db->escape($forum_name) . '\',' . intval($_POST['new_forum_cat'][$key]) . ',' . intval($_POST['sort_order'][$key]) . ',\'-' . implode('-', $view) . '-\',\'-' . implode('-', $topics) . '-\',\'-' . implode('-', $replies) . '-\')') or error('Failed to create new forum', __FILE__, __LINE__, $db->error());
		}
	}
	
	$q = new DBSelect('categories', array('id', 'name', 'sort_position'), '', 'Failed to get category list');
	$result = $q->commit();
	while ($cat = $db->fetch_assoc($result)) {
		$id = $cat['id'];
		if ($cat['name'] != $_POST['cat_title'][$id] || $cat['sort_position'] != $_POST['cat_sort_order'][$id]) {
			$q = new DBUpdate('categories', array('name' => $_POST['cat_title'][$id],'sort_position' => $_POST['cat_sort_order'][$id]), 'id=' . $id, 'Failed to update category_title');
			$q->commit();
		}
	}
	header('Refresh: 0');
	return;
}
?>
<div class="container">
	<?php make_admin_menu(); ?>
    <script type="text/javascript">
	//<![CDATA[
	var numNewForums = 9999;
	function addForum(cat_id) {
		//add a new forum to a category
		numNewForums++; //this is the temporary id, the database will set the real one
		//row for forum name
		var newTr = document.createElement('tr');
		newTr.id = 'tr_' + numNewForums;
		var fNameTd = document.createElement('td');
		//sort order box
		var hiddenSortPos = document.createElement('input');
		hiddenSortPos.type = 'hidden';
		hiddenSortPos.name = 'sort_order[' + numNewForums + ']';
		hiddenSortPos.id = 'sort_order_' + numNewForums;
		highestSortOrders[cat_id]++;
		hiddenSortPos.value = highestSortOrders[cat_id];
		fNameTd.appendChild(hiddenSortPos);
		//hidden category
		var hiddenCat = document.createElement('input');
		hiddenCat.type = 'hidden';
		hiddenCat.name = 'new_forum_cat[' + numNewForums + ']';
		hiddenCat.value = cat_id;
		fNameTd.appendChild(hiddenCat);
		//input box for forum name
		var fNameBox = document.createElement('input');
		fNameBox.type = 'text';
		fNameBox.value = '<?php echo translate('newforum'); ?>';
		fNameBox.name = 'new_forum[' + numNewForums + ']';
		fNameTd.appendChild(fNameBox);
		//the rest of the table, look at var names
		var moveTd = document.createElement('td');
		moveTd.innerHTML = '<a onclick="move(' + numNewForums + ', \'up\');" style="cursor:pointer">&uarr;</a> <a onclick="move(' + numNewForums + ', \'down\');" style="cursor:pointer">&darr;</a>';
		var deleteTd = document.createElement('td');
		var editTd = document.createElement('td');
		editTd.innerHTML = '';
		var cancelTd = document.createElement('td');
		cancelTd.style.cursor = 'pointer';
		cancelTd.innerHTML = '&#10060;';
		cancelTd.onclick = function() {
			this.parentNode.parentNode.removeChild(this.parentNode);
		}
		newTr.appendChild(fNameTd);
		newTr.appendChild(moveTd);
		newTr.appendChild(deleteTd);
		newTr.appendChild(editTd);
		newTr.appendChild(cancelTd);
		document.getElementById('table_cat_' + cat_id).appendChild(newTr);
		
		unlockSubmit();
	}
	
	function unlockSubmit() {
		document.getElementById('submitBox').style.display = 'block';
		window.onbeforeunload = function() {
			return false;
		};
	}
	
	function prepareSubmit() {
		if (newWin != null) {
			newWin.close();
		}
		window.onbeforeunload = function() {};
		return true;
	}
	
	function move(forum_id,dir) {
		var origRow = document.getElementById('tr_' + forum_id);
		var parentTable = origRow.parentNode;
		var allRows = parentTable.getElementsByTagName('tr');
		
		//swap the two necessary elements
		for (var i = 1; i < allRows.length; i++) { //starting at 1 to ignore the header
			if (allRows[i] == origRow) {
				if ((i == 1 && dir == 'up') || (i == allRows.length && dir == 'down')) {
					break;
				}
				//perform swap
				if (dir == 'up') {
					swapElements(origRow, allRows[i - 1]);
				} else if (dir == 'down') {
					swapElements(origRow, allRows[i + 1]);
				}
				break;
			}
		}
		
		//set sort positions
		var allRows = parentTable.getElementsByTagName('tr');
		for (var i = 1; i < allRows.length; i++) {
			allRows[i].getElementsByTagName('td')[0].getElementsByTagName('input')[0].value = i;
		}
		
		unlockSubmit();
	}
	
	function prepareDelete(forum_id) {
		if (!confirm('Are you sure you want to delete that forum?')) {
			return;
		}
		var tr = document.getElementById('tr_' + forum_id);
		tr.style.backgroundColor = '#A00';
		var newHiddenInput = document.createElement('input');
		newHiddenInput.type = 'hidden';
		newHiddenInput.id = 'delete_forum_' + forum_id;
		newHiddenInput.name = 'delete[' + forum_id + ']';
		newHiddenInput.value = forum_id;
		document.getElementById('submitBox').appendChild(newHiddenInput);
		var cancelTd = document.createElement('a');
		cancelTd.innerHTML = '&#10060';
		cancelTd.style.cursor = 'pointer';
		cancelTd.onclick = function() {
			cancelDelete(forum_id);
			this.parentNode.removeChild(this);
		}
		tr.getElementsByTagName('td')[4].appendChild(cancelTd);
		unlockSubmit();
	}
	
	function cancelDelete(forum_id) {
		var tr = document.getElementById('tr_' + forum_id);
		tr.style.backgroundColor = '';
		var deleteFormItem = document.getElementById('delete_forum_' + forum_id);
		deleteFormItem.parentNode.removeChild(deleteFormItem);
	}
	
	function prepareDeleteCat(cat_id) {
		var tr = document.getElementById('cat_' + cat_id);
		if (document.getElementById('delete_cat_' + cat_id)) {
			//marked for deletion, so cancel
			tr.style.backgroundColor = '';
			tr.getElementsByTagName('a')[3].innerHTML = '&#10060;';
			var deleteFormItem = document.getElementById('delete_cat_' + cat_id);
			deleteFormItem.parentNode.removeChild(deleteFormItem);
		} else {
			//not marked for deletion, so mark it
			if (!confirm('Are you sure you want to delete that category?')) {
				return;
			}
			tr.style.backgroundColor = '#A00';
			var newHiddenInput = document.createElement('input');
			newHiddenInput.type = 'hidden';
			newHiddenInput.id = 'delete_cat_' + cat_id;
			newHiddenInput.name = 'delete_cat[' + cat_id + ']';
			newHiddenInput.value = cat_id;
			document.getElementById('submitBox').appendChild(newHiddenInput);
			tr.getElementsByTagName('a')[3].innerHTML = '<?php echo translate('cancel'); ?>';
			unlockSubmit();
		}
	}
	
	function cancelDeleteCat(cat_id) {
		var tr = document.getElementById('tr_' + forum_id);
		tr.style.backgroundColor = '';
		var deleteFormItem = document.getElementById('delete_forum_' + forum_id);
		deleteFormItem.parentNode.removeChild(deleteFormItem);
	}
	
	function moveCat(cat_id,dir) {
		//mvoe categories
		var origRow = document.getElementById('cat_' + cat_id);
		var parentTable = origRow.parentNode;
		var allRows = parentTable.getElementsByTagName('div');
		
		//swap the two necessary elements
		for (var i = 0; i < allRows.length; i++) { //starting at 1 to ignore the header
			if (allRows[i] == origRow) {
				if ((i == 0 && dir == 'up') || (i == allRows.length - 1 && dir == 'down')) {
					break;
				}
				//perform swap
				if (dir == 'up') {
					swapElements(origRow, allRows[i - 1]);
				} else if (dir == 'down') {
					swapElements(origRow, allRows[i + 1]);
				}
				break;
			}
		}
		
		//set sort positions
		var allRows = parentTable.getElementsByTagName('div');
		for (var i = 0; i < allRows.length; i++) {
			allRows[i].getElementsByTagName('h4')[0].getElementsByTagName('input')[0].value = i;
		}
		
		unlockSubmit();
	}
	
	function swapElements(obj1, obj2) {
		// create marker element and insert it where obj1 is
		var temp = document.createElement("div");
		obj1.parentNode.insertBefore(temp, obj1);
	
		// move obj1 to right before obj2
		obj2.parentNode.insertBefore(obj1, obj2);
	
		// move obj2 to right before where obj1 used to be
		temp.parentNode.insertBefore(obj2, temp);
	
		// remove temporary marker node
		temp.parentNode.removeChild(temp);
	}
	
	var newWin = null;
	function editForum(forum_id) {
		if (newWin == null && document.getElementById('submitBox').style.display == 'none') {
			newWin = window.open('<?php echo $base_config['baseurl']; ?>/admin/forums/edit/' + forum_id + '?popup=true', 'Edit forum', 'width=500, height=600');
			window.onunload = function() {
				newWin.close();
			}
		} else {
			alert('You have modified a forum or already have one open for editing. Please refresh the page and then try again.');
		}
	}
	//]]>
	</script>
	<div class="forum_content rightbox admin">
    	<form action="<?php echo $base_config['baseurl']; ?>/admin/forums" method="post" enctype="multipart/form-data">
        	<p><input type="submit" name="add_new_category" value="<?php echo translate('addcat'); ?>" /></p>
        </form>
    	<form action="<?php echo $base_config['baseurl']; ?>/admin/forums/enhanced" method="post" enctype="multipart/form-data" id="theform">
            <h3><?php echo translate('editforums'); ?></h3>
            <?php
            $q = new DBSelect('forums', array('c.name AS cat_name','c.sort_position AS cat_sort_position','f.id','c.id AS cat_id','f.sort_position','f.name AS forum_name'), 'c.id IS NOT NULL', 'Failed to get forum list');
            $q->table_as('f');
            $q->set_order('c.sort_position,f.sort_position');
            $q->add_join(new DBJoin('categories', 'c', 'c.id=f.cat_id', 'right'));
            $result = $q->commit();
            $last_cat_id = -1;
			$highest_sort_orders = array();
            while ($forum = $db->fetch_assoc($result)) {
                if ($forum['cat_id'] != $last_cat_id) {
                    if ($last_cat_id != -1) {
                        echo '</table></div>';
                    }
                    $last_cat_id = $forum['cat_id'];
                    echo '<div id="cat_' . $forum['cat_id'] . '"><h4><input type="hidden" name="cat_sort_order[' . $forum['cat_id'] . ']" value="' . $forum['cat_sort_position'] . '" /><input type="text" name="cat_title[' . $forum['cat_id'] . ']" value="' . htmlspecialchars($forum['cat_name']) . '" oninput="unlockSubmit();" /> <a onclick="moveCat(' . $forum['cat_id'] . ',\'up\');" style="cursor:pointer">&uarr;</a> <a onclick="moveCat(' . $forum['cat_id'] . ',\'down\');" style="cursor:pointer">&darr;</a> (<a onClick="addForum(' . $forum['cat_id'] . ');" style="cursor:pointer">&#10010 Add forum</a>) (<a onclick="prepareDeleteCat(' . $forum['cat_id'] . ');" style="cursor:pointer">&#10060;</a>)</h4><hr /><table border="0" id="table_cat_' . $forum['cat_id'] . '"><tr><th>' . translate('forumname') . '</th><th>Move</th><th>' . translate('delete') . '</th><th>' . translate('edit') . '</th><th>' . translate('cancel') . '</th></tr>' . "\n";
                }
				if ($forum['id'] != '') {
					echo '<tr id="tr_' . $forum['id'] . '"><td><input type="hidden" name="sort_order[' . $forum['id'] . ']" id="sort_order_' . $forum['id'] . '" value="' . $forum['sort_position'] . '" /><input type="text" name="title[' . $forum['id'] . ']" value="' . htmlspecialchars($forum['forum_name']) . '" oninput="unlockSubmit();" /></td><td><a onclick="move(' . $forum['id'] . ',\'up\');" style="cursor:pointer">&uarr;</a> <a onclick="move(' . $forum['id'] . ',\'down\');" style="cursor:pointer">&darr;</a></td><td><a onclick="prepareDelete(' . $forum['id'] . ');" style="cursor:pointer">&#10060;</a></td><td><a href="' . $base_config['baseurl'] . '/admin/forums/edit/' . $forum['id'] . '?popup=true" onclick="editForum(' . $forum['id'] . '); return false;" style="text-decoration:none" target="_BLANK">&#9998;</a></td><td></td></tr>' . "\n";
					if (!isset($highest_sort_orders[$forum['cat_id']]) || $forum['sort_position'] > $highest_sort_orders[$forum['cat_id']]) {
						$highest_sort_orders[$forum['cat_id']] = $forum['sort_position'];
					}
				}
            }
			if ($last_cat_id != -1) {
            	echo '</table></div>';
			}
            ?>
            <p id="submitBox" style="display:none"><input type="submit" value="Save" name="form_sent" onclick="return prepareSubmit();" /></p>
    	</form>
    </div>
</div>
<script type="text/javascript">
var highestSortOrders = [];
<?php
foreach ($highest_sort_orders as $key => $val) {
	echo 'highestSortOrders[' . $key . '] = ' . $val . ';';
}
?>
</script>