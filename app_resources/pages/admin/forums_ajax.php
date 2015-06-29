<?php
// Welcome to the AJAX-based forum editor. Starting in version 1.3, this is fully AJAX-capable.
// A note for whomever is looking at the code:
// This page originally was just JS editing with saving via a standard form submission. The AJAX was entirely retrofitted.
// As a result, the retrofitted AJAX may look a little weird.
// - Jacob G.

// Send no-cache headers
header('Expires: Mon, 1 Jan 1990 00:00:00 GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache'); // For HTTP/1.0 compatibility
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
		if (isset($_POST['title'][$id]) && $_POST['title'][$id] != $title && $_POST['title'][$id] != '') {
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
		$cat_dels = array();
		foreach ($_POST['delete_cat'] as $key => $val) {
			$cat_dels[] = intval($key);
		}
		$db->query('DELETE FROM `#^categories` WHERE id IN(' . implode(',', $cat_dels) . ')') or enhanced_error('Failed to delete categories', true);
	}
	
	//update category information
	$q = new DBSelect('categories', array('id', 'name', 'sort_position'), '', 'Failed to get category list');
	$result = $q->commit();
	$cats = array();
	while ($cat = $db->fetch_assoc($result)) {
		$id = $cat['id'];
		$cats[] = $id;
		if ($id > 0 && ($cat['name'] != $_POST['cat_title'][$id] || $cat['sort_position'] != $_POST['cat_sort_order'][$id])) {
			$q = new DBUpdate('categories', array('name' => $_POST['cat_title'][$id],'sort_position' => $_POST['cat_sort_order'][$id]), 'id=' . $id, 'Failed to update category_title');
			$q->commit();
		}
	}
	
	$cat_mappings = array();
	//any new categories?
	foreach ($_POST['cat_title'] as $id => $name) {
		//if the category found in POST data doesn't exist and it's not set to be deleted (i.e. deleted above), create it
		if (!in_array($id, $cats) && !isset($_POST['delete_cat'][$id])) {
			//the category is not present! create it!
			$q = new DBInsert('categories', array('name' => $name, 'sort_position' => $_POST['cat_sort_order'][$id]), 'Failed to insert new category');
			$q->commit();
			$cat_mappings[$id] = $db->insert_id(); //replace the temporary IDs given by JS with real IDs given by the database
		}
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
			$cat_id = intval($_POST['new_forum_cat'][$key]);
			if (isset($cat_mappings[$cat_id])) {
				$cat_id = $cat_mappings[$cat_id];
			}
			create_forum($cat_id, $forum_name, $view, $topics, $replies, intval($_POST['sort_order'][$key]));
		}
	}
	
	//any categories to change?
	$result = $db->query('SELECT id,cat_id FROM `#^forums`') or enhanced_error('Failed to get forum list', true);
	while (list($forum, $cat) = $db->fetch_row($result)) {
		if (isset($_POST['cat'][$forum]) && $_POST['cat'][$forum] != '' && $cat != $_POST['cat'][$forum]) {
			$db->query('UPDATE `#^forums` SET cat_id=' . intval($_POST['cat'][$forum]) . ' WHERE id=' . $forum) or enhanced_error('Failed to update forum category', true);
		}
	}
	
	print_r($_POST); die;
	
	header('Refresh: 0');
	return;
}
?>
<div class="container">
	<?php make_admin_menu(); ?>
    <script type="text/javascript">
	//<![CDATA[
	var numNewForums = 9999;
	var needRefresh = false;
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
		var changeCatTd = document.createElement('td');
		changeCatTd.innerHTML = '';
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
		newTr.appendChild(changeCatTd);
		newTr.appendChild(cancelTd);
		document.getElementById('table_cat_' + cat_id).appendChild(newTr);
		
		needRefresh = true;
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
				if ((i == 1 && dir == 'up') || (i == allRows.length - 1 && dir == 'down')) {
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
			var inputs = allRows[i].getElementsByTagName('td')[0].getElementsByTagName('input');
			for (index in inputs) {
				//find the first element that looks like the one that stores the sort order
				if (inputs[index].id != null && inputs[index].id.startsWith('sort_order_')) {
					inputs[index].value = i;
					break;
				}
			}
		}
		
		unlockSubmit();
	}
	
	var forums_to_delete = [];
	
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
		forums_to_delete.push(forum_id);
		tr.getElementsByTagName('td')[5].appendChild(cancelTd);
		unlockSubmit();
	}
	
	function cancelDelete(forum_id) {
		var tr = document.getElementById('tr_' + forum_id);
		tr.style.backgroundColor = '';
		var deleteFormItem = document.getElementById('delete_forum_' + forum_id);
		deleteFormItem.parentNode.removeChild(deleteFormItem);
		var index = forums_to_delete.indexOf(forum_id);
		if (index != -1) {
			forums_to_delete.splice(index, 1);
		}
	}
	
	var cats_to_delete = [];
	function prepareDeleteCat(cat_id) {
		var tr = document.getElementById('cat_' + cat_id);
		if (document.getElementById('delete_cat_' + cat_id)) {
			//marked for deletion, so cancel
			tr.style.backgroundColor = '';
			tr.getElementsByTagName('a')[3].innerHTML = '&#10060;';
			var deleteFormItem = document.getElementById('delete_cat_' + cat_id);
			deleteFormItem.parentNode.removeChild(deleteFormItem);
			var index = cats_to_delete.indexOf(cat_id);
			if (index != -1) {
				cats_to_delete.splice(index, 1);
			}
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
			cats_to_delete.push(cat_id);
			unlockSubmit();
		}
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
	
	function cancelCat(cat_id) {
		var mainCatDiv = document.getElementById('cat_' + cat_id);
		mainCatDiv.parentNode.removeChild(mainCatDiv);
	}
	
	<?php
	$result = $db->query('SELECT MAX(id),MAX(sort_position) FROM `#^categories`') or enhanced_error('Failed to get highest id', true);
	list($maxid,$maxpos) = $db->fetch_row($result);
	if (!$maxpos) {
		$maxpos = 0;
	}
	echo 'var maxCatSortOrder = ' . $maxpos . ';';
	?>
	var maxCatId = -1; //new categories start temporary IDs at -1 and work downwards (existing categories use existing IDs)
	
	var max_sort_orders = [];
	<?php
	$result = $db->query('SELECT cat_id,sort_position FROM `#^forums` WHERE cat_id IS NOT NULL') or enhanced_error('Failed to get highest sort orders', true);
	$max_sort_orders = array();
	while (list($cat, $pos) = $db->fetch_row($result)) {
		if (!isset($max_sort_orders[$cat]) || $pos > $max_sort_orders[$cat]) {
			$max_sort_orders[$cat] = $pos;
		}
	}
	foreach ($max_sort_orders as $cat => $val) {
		echo "\n\t" . 'max_sort_orders[' . $cat . '] = ' . $val;
	}
	?>
	
	function addCat() {
		maxCatId--;
		maxCatSortOrder++;
		var catDiv = document.createElement('div');
		catDiv.id = 'cat_' + maxCatId;
		
		var h4 = document.createElement('h4');
		var catSortOrderInput = document.createElement('input');
		catSortOrderInput.type = 'hidden';
		catSortOrderInput.name = 'cat_sort_order[' + maxCatId + ']';
		catSortOrderInput.value = maxCatSortOrder;
		h4.appendChild(catSortOrderInput);
		
		var catNameInput = document.createElement('input');
		catNameInput.name = 'cat_title[' + maxCatId + ']';
		h4.appendChild(catNameInput);
		
		var moveSpan = document.createElement('span');
		moveSpan.innerHTML = ' <a onclick="moveCat(' + maxCatId + ', \'up\');" style="cursor:pointer">&uarr;</a> <a onclick="moveCat(' + maxCatId + ', \'down\');" style="cursor:pointer">&darr;</a> (<a onclick="addForum(' + maxCatId + ');" style="cursor:pointer">&#10010 <?php echo translate('addforum'); ?></a>) (<a onclick="cancelCat(' + maxCatId + ');" style="cursor:pointer"><?php echo translate('cancel'); ?></a>)';
		h4.appendChild(moveSpan);
		
		catDiv.appendChild(h4);
		
		var catTable = document.createElement('table');
		catTable.id = 'table_cat_' + maxCatId;
		var topRow = document.createElement('tr');
		var th1 = document.createElement('th');
		th1.innerHTML = '<?php echo translate('forumname'); ?>';
		topRow.appendChild(th1);
		var th2 = document.createElement('th');
		th2.innerHTML = '<?php echo translate('move'); ?>';
		topRow.appendChild(th2);
		var th3 = document.createElement('th');
		th3.innerHTML = '<?php echo translate('delete'); ?>';
		topRow.appendChild(th3);
		var th4 = document.createElement('th');
		th4.innerHTML = '<?php echo translate('edit'); ?>';
		topRow.appendChild(th4);
		var th5 = document.createElement('th');
		th5.innerHTML = '<?php echo translate('changecategory'); ?>';
		topRow.appendChild(th5);
		var th6 = document.createElement('th');
		th6.innerHTML = '<?php echo translate('cancel'); ?>';
		topRow.appendChild(th6);
		
		catTable.appendChild(topRow);
		catDiv.appendChild(document.createElement('hr'));
		catDiv.appendChild(catTable);
		
		needRefresh = true;
		
		document.getElementById('cat_container').appendChild(catDiv);
		unlockSubmit();
	}
	
	var newWin = null;
	function editForum(forum_id) {
		if (newWin == null && document.getElementById('submitBox').style.display == 'none') {
			newWin = window.open('<?php echo $base_config['baseurl']; ?>/admin/forums/edit/' + forum_id + '?popup=true', 'Edit forum', 'width=500, height=600');
			window.onunload = function() {
				newWin.close();
			}
			newWin.onbeforeunload = function() {
				newWin = null;
			}
			newWin.onsubmit = function() {
				//edits were made in that window, so reload the page
				if (document.getElementById('submitBox').style.display == 'none') {
					window.location.reload();
				} else {
					var shouldReload = confirm('<?php echo translate('otherforumeditsconfirmrefresh'); ?>');
					if (shouldReload) {
						window.onbeforeonload = function() {};
						window.location.reload();
					}
				}
			}
		} else {
			alert('<?php echo translate('forumalreadyopen'); ?>');
		}
	}
	
	function ajaxSave() {
		//save via AJAX
		var form = document.getElementById('theform');
		var formStr = collectFormData(form).join('&');
		
		if (window.XMLHttpRequest) {
			req = new XMLHttpRequest();
		} else {
			req = new ActiveXObject("Microsoft.XMLHTTP");
		}
		req.open("POST", '<?php echo $base_config['baseurl']; ?>/admin/forums/enhanced', true);
		req.setRequestHeader("Content-type", "application/x-www-form-urlencoded")
		req.send(formStr);
		
		req.onreadystatechange = function() {
			if (req.readyState==4 && req.status==200) {
				window.onbeforeunload = function() {};
				document.getElementById('submitBox').style.display = 'none';
				
				//hide any forums or categories marked for deletion
				for (var forum_id in forums_to_delete) {
					document.getElementById('tr_' + forums_to_delete[forum_id]).parentNode.removeChild(document.getElementById('tr_' + forums_to_delete[forum_id]));
				}
				for (var cat_id in cats_to_delete) {
					document.getElementById('cat_' + cats_to_delete[cat_id]).parentNode.removeChild(document.getElementById('cat_' + cats_to_delete[cat_id]));
				}
				alert('Saved!');
				
				if (needRefresh) {
					window.location.reload();
				}
			} else {
				//failure
			}
		 }
	}
	
	//recursive function to collect all form
	function collectFormData(form) {
		var result = [];
		if (form.name != null && form.name != 'item' && form.name != '') {
			//it has a name, so throw it on
			result.push(encodeURIComponent(form.name) + '=' + encodeURIComponent(form.value));
		}
		if (form.hasChildNodes) {
			//it has children
			var children = form.childNodes;
			for (id in children) {
				result = result.concat(collectFormData(children[id]));
			}
		}
		return result;
	}
	
	function changeCat(forum_id, new_cat_id) {
		document.getElementById('changecat_' + forum_id).childNodes[0].selected = true;
		var forumRow = document.getElementById('tr_' + forum_id);
		document.getElementById('table_cat_' + new_cat_id).childNodes[0].appendChild(forumRow);
		document.getElementById('sort_order_' + forum_id).value = max_sort_orders[new_cat_id] + 1;
		max_sort_orders[new_cat_id]++;
		
		var oldCat = document.getElementById('catof_' + forum_id).value;
		document.getElementById('table_cat_' + oldCat).childNodes[0].removeChild(oldCat);
		max_sort_orders[oldCat]--;
		document.getElementById('catof_' + forum_id).value = new_cat_id;
		
		unlockSubmit();
	}
	//]]>
	</script>
	<?php
	//get the plain list of categories
	$result = $db->query('SELECT id,name FROM `#^categories` ORDER BY name ASC') or enhanced_error('Failed to get categories', true);
	$catlist_html = '<option value="-1">' . translate('changecategory') . '</option>';
	while (list($id, $name) = $db->fetch_row($result)) {
		$catlist_html .= '<option value="' . $id . '">' . htmlspecialchars($name) . '</option>';
	}
	?>
	<div class="forum_content rightbox admin">
    	<form action="<?php echo $base_config['baseurl']; ?>/admin/forums/enhanced" method="post" enctype="multipart/form-data" id="theform">
            <h3><?php echo translate('editforums'); ?></h3>
			<p><a style="text-decoration: underline;cursor:pointer" onclick="addCat();"><?php echo translate('addcat'); ?></a></p>
			<div id="cat_container">
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
						echo '<div id="cat_' . $forum['cat_id'] . '"><h4><input type="hidden" name="cat_sort_order[' . $forum['cat_id'] . ']" value="' . $forum['cat_sort_position'] . '" /><input type="text" name="cat_title[' . $forum['cat_id'] . ']" value="' . htmlspecialchars($forum['cat_name']) . '" oninput="unlockSubmit();" /> <a onclick="moveCat(' . $forum['cat_id'] . ',\'up\');" style="cursor:pointer">&uarr;</a> <a onclick="moveCat(' . $forum['cat_id'] . ',\'down\');" style="cursor:pointer">&darr;</a> (<a onClick="addForum(' . $forum['cat_id'] . ');" style="cursor:pointer">&#10010 ' . translate('addforum') . '</a>) (<a onclick="prepareDeleteCat(' . $forum['cat_id'] . ');" style="cursor:pointer">&#10060;</a>)</h4><hr /><table border="0" id="table_cat_' . $forum['cat_id'] . '"><tr><th>' . translate('forumname') . '</th><th>Move</th><th>' . translate('delete') . '</th><th>' . translate('edit') . '</th><th>' . translate('changecategory') . '</th><th>' . translate('cancel') . '</th></tr>' . "\n";
					}
					if ($forum['id'] != '') {
						echo '<tr id="tr_' . $forum['id'] . '"><td><input type="hidden" name="cat[' . $forum['id'] . ']" id="catof_' . $forum['id'] . '" value="' . $forum['cat_id'] . '" /><input type="hidden" name="sort_order[' . $forum['id'] . ']" id="sort_order_' . $forum['id'] . '" value="' . $forum['sort_position'] . '" /><input type="text" name="title[' . $forum['id'] . ']" value="' . htmlspecialchars($forum['forum_name']) . '" oninput="unlockSubmit();" /></td><td><a onclick="move(' . $forum['id'] . ',\'up\');" style="cursor:pointer">&uarr;</a> <a onclick="move(' . $forum['id'] . ',\'down\');" style="cursor:pointer">&darr;</a></td><td><a onclick="prepareDelete(' . $forum['id'] . ');" style="cursor:pointer">&#10060;</a></td><td><a href="' . $base_config['baseurl'] . '/admin/forums/edit/' . $forum['id'] . '?popup=true" onclick="editForum(' . $forum['id'] . '); return false;" style="text-decoration:none" target="_BLANK">&#9998;</a></td><td><select id="changecat_' . $forum['id'] . '" onchange="changeCat(' . $forum['id'] . ', this.value);">' . $catlist_html . '</select></td><td></td></tr>' . "\n";
						if (!isset($highest_sort_orders[$forum['cat_id']]) || $forum['sort_position'] > $highest_sort_orders[$forum['cat_id']]) {
							$highest_sort_orders[$forum['cat_id']] = $forum['sort_position'];
						}
					}
				}
				if ($last_cat_id != -1) {
					echo '</table></div>';
				}
				?>
			</div>
            <p id="submitBox" style="display:none"><input type="submit" value="Save" name="form_sent" onclick="ajaxSave(); return false;" /></p>
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