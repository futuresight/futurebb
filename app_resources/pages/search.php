<?php
$page_title = 'Search';
if (isset($_GET['query'])) {
	include FORUM_ROOT . '/app_resources/includes/search.php';
	$terms = split_into_words($_GET['query']);
	foreach ($terms as &$val) {
		$val = '\'' . $db->escape(trim($val)) . '\'';
	}
	$addl_sql = '';
	if (isset($_GET['author']) && $_GET['author'] != '') {
		$addl_sql .= ' AND u.username LIKE \'' . $db->escape(str_replace('*', '%', $_GET['author'])) . '\'';
	}
	if (isset($_GET['forum']) && intval($_GET['forum']) != 0) {
		$addl_sql .= ' AND f.id=' . intval($_GET['forum']);
	}
	if (isset($_GET['show']) && ($futurebb_user['g_admin_privs'] || $futurebb_user['g_mod_privs'])) {
		switch ($_GET['show']) {
			case 'deleted':
				$addl_sql .= ' AND ((p.deleted IS NOT NULL AND p.deleted_by=p.poster) OR (t.deleted IS NOT NULL))'; break;
			default:
				$addl_sql .= ' AND p.deleted IS NULL AND t.deleted IS NULL'; break;
		}
	} else {
		$addl_sql .= ' AND p.deleted IS NULL AND t.deleted IS NULL';
	}
	if ($_GET['query'] != '') {
		$where = 'si.word IN (' . implode(',', $terms) . ') AND';
	} else {
		$where = '';
	}
	$result = $db->query('SELECT g.g_title AS user_title,u.username AS author,p.poster AS author_id,u.parsed_signature AS signature,p.posted,p.id,p.parsed_content,p.last_edited,p.deleted AS post_deleted,leu.username AS last_edited_by,si.num_matches,f.name AS forum,f.url AS furl,c.name AS category,t.deleted AS topic_deleted FROM `#^search_index` AS si LEFT JOIN `#^posts` AS p LEFT JOIN `#^users` AS u ON u.id=p.poster ON p.id=si.post_id LEFT JOIN `#^user_groups` AS g ON g.g_id=u.group_id LEFT JOIN `#^users` AS leu ON leu.id=p.last_edited_by LEFT JOIN `#^topics` AS t ON t.id=p.topic_id LEFT JOIN `#^forums` AS f ON f.id=t.forum_id LEFT JOIN `#^categories` AS c ON c.id=f.cat_id WHERE ' . $where . ' f.view_groups LIKE \'%-' . $futurebb_user['group_id'] . '-%\' ' . $addl_sql) or error('Failed to execute search', __FILE__, __LINE__, $db->error());
	$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
	$posts = array();
	while ($cur_post = $db->fetch_assoc($result)) {
		if (array_key_exists($cur_post['id'], $posts)) {
			$posts[$cur_post['id']]['num_matches'] += $cur_post['num_matches'];
		} else {
			$posts[$cur_post['id']] = $cur_post;
		}
	}
	function postsort($a1, $a2) {
		if ($a1['num_matches'] > $a2['num_matches']) {
			return -1;
		} else if ($a1['num_matches'] < $a2['num_matches']) {
			return 1;
		} else if ($a1['num_matches'] == $a2['num_matches']) {
			if ($a1['posted'] > $a2['posted']) {
				return -1;
			} else if ($a1['posted'] < $a2['posted']) {
				return 1;
			} else {
				return 0;
			}
		}
	}
	usort($posts, 'postsort');
	
	//take a quick break for pagination
	$num_pages = ceil(sizeof($posts) / $futurebb_config['posts_per_page']);
	?>
	<p><?php echo translate('pages');
	$linktext = '<a href="' . $base_config['baseurl'] . '/search?query=' . htmlspecialchars($_GET['query']);
	if (isset($_GET['author'])) {
		$linktext .= '&author=' . htmlspecialchars($_GET['author']);
	}
	if (isset($_GET['forum'])) {
		$linktext .= '&forum=' . intval($_GET['forum']);
	}
	$linktext .= '&page=$page$"$bold$>$page$</a>';
	echo paginate($linktext, $page, $num_pages);
	echo '</p>';
	
	$i = 0;
	foreach ($posts as $cur_post) {
		$i++;
		if ($i > ($page - 1) * $futurebb_config['posts_per_page'] && $i <= $page * $futurebb_config['posts_per_page']) {
			?>
			<div class="catwrap" id="post<?php echo $cur_post['id']; ?>">
				<h2 class="cat_header"><?php echo htmlspecialchars($cur_post['category']); ?> &raquo; <a href="<?php echo $base_config['baseurl']; ?>/<?php echo htmlspecialchars($cur_post['furl']); ?>"><?php echo htmlspecialchars($cur_post['forum']); ?></a> &raquo; <a href="<?php if ($cur_post['post_deleted'] || $cur_post['topic_deleted']) echo $base_config['baseurl'] . '/admin/trash_bin/posts/' . $cur_post['id'];  else echo $base_config['baseurl'] . '/posts/' . $cur_post['id']; ?>"><?php echo user_date($cur_post['posted']); ?></a></h2>
				<div class="cat_body">
					<div class="postleft">
						<p><a href="<?php echo $base_config['baseurl']; ?>/users/<?php echo htmlspecialchars($cur_post['author']); ?>"><?php echo htmlspecialchars($cur_post['author']); ?></a></p>
						<p><b><?php echo $cur_post['user_title']; ?></b></p>
						<?php
						if ($futurebb_config['avatars'] && file_exists(FORUM_ROOT . '/static/avatars/' . $cur_post['author_id'] . '.png')) {
							echo '<img src="' . $base_config['baseurl'] . '/static/avatars/' . $cur_post['author_id'] . '.png" class="avatar" />';
						}
						?>
					</div>
					<div class="postright">
						<p><?php echo $cur_post['parsed_content']; ?></p>
						<?php if ($cur_post['last_edited'] != null) {
							echo '<p style="font-style:italic">' . translate('lastedited', htmlspecialchars($cur_post['last_edited_by']), user_date($cur_post['last_edited'])) . '.</p>';
						} ?>
						<?php
						if ($cur_post['signature']) {
							echo '<hr /><p>' . $cur_post['signature'] . '</p>';
						}
						?>
					</div>
				</div>
			</div>
			<?php
		}
	}
	if ($db->num_rows($result)) {
		?>
		<p><?php echo translate('pages');
		echo paginate($linktext, $page, $num_pages); ;
	} else {
		?>
		<div class="forum_content">
			<p><?php echo translate('noresults'); ?></p>
		</div>
	<?php
	}
} else {
	?>
	<div class="forum_content">
		<form action="<?php echo $base_config['baseurl']; ?>/search" method="get" enctype="application/x-www-form-urlencoded">
			<h2><?php echo translate('search'); ?></h2>
			<table border="0">	
				<tr>
					<td><?php echo translate('keywords'); ?></td>
					<td><input type="text" name="query" /></td>
				</tr>
				<tr>
					<td><?php echo translate('author'); ?></td>
					<td><input type="text" name="author" /></td>
				</tr>
				<tr>
					<td><?php echo translate('forum'); ?></td>
					<td><select name="forum"><option value="0"><?php echo translate('allforums'); ?></option><?php
					$result = $db->query('SELECT f.name,f.id,f.cat_id,c.name AS cname FROM `#^forums` AS f LEFT JOIN `#^categories` AS c ON c.id=f.cat_id WHERE f.view_groups LIKE \'%-' . $futurebb_user['group_id'] . '-%\' ORDER BY c.sort_position ASC,f.sort_position ASC') or error('Failed to get forums', __FILE__, __LINE__, $db->error());
					$last_id = 0;
					while ($cur_forum = $db->fetch_assoc($result)) {
						if ($last_id != $cur_forum['cat_id']) {
							if ($last_id != 0) {
								echo '</optgroup>' . "\n";
							}
							$last_id = $cur_forum['cat_id'];
							echo '<optgroup label="' . htmlspecialchars($cur_forum['cname']) . '">' . "\n";
						}
						echo '<option value="' . $cur_forum['id'] . '">' . htmlspecialchars($cur_forum['name']) . '</option>' . "\n";
					}
					if ($last_id != 0) {
						echo '</optgroup>';
					}
					?></select></td>
				</tr>
				<?php if ($futurebb_user['g_admin_privs'] || $futurebb_user['g_mod_privs']) { ?>
				<tr>
					<td><?php echo translate('show'); ?></td>
					<td><select name="show"><option value="undeleted"><?php echo translate('search-undeleted'); ?></option><option value="deleted"><?php echo translate('search-deleted'); ?></option></select></td>
				</tr>
				<?php } ?>
			</table>
			<p><input type="submit" value="<?php echo translate('search'); ?>" /></p>
		</form>
	</div>
	<?php
}