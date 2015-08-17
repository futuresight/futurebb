<?php
$page_title = 'Search';
define('BASE', 2); //the base number for scoring searches, like scoring 2^n
define('PAGE_SIZE', 25);
define('CACHE_SEARCHES', true); //comment to disable caching, should only be used for debugging
define('SEARCH_EXPIRY', 60 * 15); //minutes for searches to expire
//define('SHOW_SCORES', true); //uncomment to show the search scores when searching by relevance - this should only be used for debugging purposes

class SearchItem {
	private $mwords;
	private $score = -1;
	private $keywords = array();
	private $time;
	private $id;
	function __construct($message, $time, $id) {
		$this->mwords = split_into_words(strtolower($message));
		$this->time = $time;
		$this->id = $id;
	}
	
	function compareTo($other) {
		if ($this->getScore() == $other->getScore()) {
			return $this->getPosted() - $other->getPosted();
		} else {
			return $this->getScore() - $other->getScore();
		}
	}
	
	function addKeyword($keyword, $locations) {
		//when adding a keyword, index its locations
		$keyword = strtolower($keyword);
		$word = new SearchWord($keyword);
		$word->setLocations(explode(',', $locations));
		$this->keywords[$keyword] = $word;
	}
	
	function getScore() {
		global $keywords;
		$longestmatch = 0;
		if ($this->score == -1) {
			$this->score = 0;
			//the scores are calculated the first time anyone tries to see them
			//for each location of a given keyword, see if there is anything for the keyword after it
			foreach ($keywords as $keykey => $keyword) { //loop through the keywords
				if (isset($this->keywords[$keyword])) {
					$locations = $this->keywords[$keyword]->getLocations();
					foreach ($locations as $location) { //loop through each location of the keyword
						$subsequent = 1;
						while ($keykey + $subsequent < sizeof($keywords) && array_key_exists($keywords[$keykey + $subsequent], $this->keywords) && $this->keywords[$keywords[$keykey + $subsequent]]->hasLocation($location + $subsequent)) {
							$subsequent++;
						}
						$this->score += pow(BASE, $subsequent);
					}
				}
			}
		}
		return $this->score;
	}
	
	function getPosted() {
		return $this->time;
	}
	
	function getId() {
		return $this->id;
	}
}
class SearchWord {
	private $word;
	private $locations = array();
	
	function __construct($word) {
		$this->word = $word;
	}
	
	function setLocations($loc) {
		$this->locations = $loc;
	}
	
	function hasLocation($loc) {
		return in_array($loc, $this->locations);
	}
	
	function getLocations() {
		return $this->locations;
	}
}

if (isset($_GET['query'])) {
	include FORUM_ROOT . '/app_resources/includes/search.php';
	$terms = split_into_words(strtolower($_GET['query']));
	$keywords = $terms;
	foreach ($terms as &$term) {
		$term = '\'' . $db->escape($term) . '\'';
	}
	$addl_where = array();
	if (isset($_GET['show']) && $_GET['show'] == 'deleted' && ($futurebb_user['g_mod_privs'] || $futurebb_user['g_admin_privs'])) {
		$addl_where[] = '(p.deleted IS NOT NULL OR t.deleted IS NOT NULL)';
	} else {
		$addl_where[] = '(p.deleted IS NULL AND t.deleted IS NULL)';
	}
	if (isset($_GET['author']) && $_GET['author'] != '') {
		$addl_where[] = 'u.username LIKE \'' . $db->escape(str_replace('*', '%', $_GET['author'])) . '\'';
	}
	if (isset($_GET['forum']) && $_GET['forum'] != 0) {
		$addl_where[] = 't.forum_id=' . intval($_GET['forum']);
	}
	$addl_where[] = 'p.id IS NOT NULL'; //if a post is deleted but the index entry isn't removed
	$sortby = isset($_GET['sortby']) ? $_GET['sortby'] : 'relevance';
	if (!isset($_GET['query']) || $_GET['query'] == '') {
		if ($sortby == 'relevance') {
			$sortby = 'posttime';
		}
	}
	if (defined('CACHE_SEARCHES')) {
		$search_hash = md5('query=' . base64_encode(implode(',', $terms)) . '&sortby=' . $sortby . '&show=' . (isset($_GET['show']) && $_GET['show'] == 'deleted' ? 'deleted' : 'normal') . '&author=' . base64_encode(isset($_GET['username']) ? $_GET['username'] : '') . '&forum=' . (isset($_GET['forum']) ? intval($_GET['forum']) : '0')); //generate a hash that will be used to cache the results
		$result = $db->query('SELECT results FROM `#^search_cache` WHERE hash=\'' . $db->escape($search_hash) . '\' AND time>' . (time() - SEARCH_EXPIRY)) or enhanced_error('Failed to check cache', true);
		if ($db->num_rows($result)) {
			list($id_list) = $db->fetch_row($result);
			$sortby = 'cache';
		}
	}
	$results = array();
	$plain = false;
	switch ($sortby) {
		case 'relevance':
			//sort by relevance
			$result = $db->query('SELECT p.content,p.id AS post_id,p.posted,i.locations,i.word FROM `#^search_index` AS i LEFT JOIN `#^posts` AS p ON p.id=i.post_id LEFT JOIN `#^topics` AS t ON t.id=p.topic_id LEFT JOIN `#^users` AS u ON u.id=p.poster WHERE i.word IN(' . implode(',', $terms) . ') AND ' . implode(' AND ', $addl_where)) or enhanced_error('Failed to get search information', true);
			while ($match = $db->fetch_assoc($result)) {
				if (isset($results[$match['post_id']])) {
					$results[$match['post_id']]->addKeyword($match['word'], $match['locations']);
				} else {
					$item = new SearchItem($match['content'], $match['posted'], $match['post_id']);
					$item->addKeyword($match['word'], $match['locations']);
					$results[$match['post_id']] = $item;
				}
			}
			usort($results, function($m1, $m2) {
				return $m1->compareTo($m2);
			});
			if (!isset($_GET['direction']) || $_GET['direction'] == 'desc') {
				$results = array_reverse($results);
			}
			break;
		case 'posttime':
			$plain = true;
			$order = 'p.posted';
			break;
		case 'cache':
			$results = explode(',', $id_list);
			break;
		default:
			httperror(404);
	}
	if ($plain) {
		//we're just doing a basic SQL query to retrieve IDs, so don't do any of the fancy stuff (and the code is reusable)
		$direction = (isset($_GET['direction']) && in_array($_GET['direction'], array('asc', 'desc'))) ? strtoupper($_GET['direction']) : 'ASC';
		$result = $db->query('SELECT DISTINCT(p.id) FROM `#^search_index` AS i LEFT JOIN `#^posts` AS p ON p.id=i.post_id LEFT JOIN `#^topics` AS t ON t.id=p.topic_id LEFT JOIN `#^users` AS u ON u.id=p.poster WHERE ' . (isset($_GET['query']) && $_GET['query'] != '' ? 'i.word IN(' . implode(',', $terms) . ') AND ' : '') . implode(' AND ', $addl_where) . ' ORDER BY ' . $order . ' ' . $direction) or enhanced_error('Failed to get search information', true);
		while (list($id) = $db->fetch_row($result)) {
			$results[] = $id;
		}
	}
	//only keep the first 400 entries
	$results = array_slice($results, 0, 400);
	if ($sortby != 'cache' && defined('CACHE_SEARCHES')) {
		//cache the results
		$db->query('DELETE FROM `#^search_cache` WHERE time<' . (time() - SEARCH_EXPIRY)) or enhanced_error('Failed to remove old cache items', true); //delete any cached searches older than 15 minutes
		if (is_object($results[0])) {
			$result_list = array();
			foreach ($results as $searchitem) {
				$result_list[] = $searchitem->getId();
			}
		} else {
			$result_list = $results;
		}
		$db->query('INSERT INTO `#^search_cache`(hash,results,time) VALUES(\'' . $search_hash . '\',\'' . implode(',', $result_list) . '\',' . time() . ')') or enhanced_error('Failed to insert cache entry', true);
		unset($result_list);
	}
	//now that we have the results, choose the ones we want by page, and then get the rest of the information
	$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
	$num_pages = ceil(sizeof($results) / PAGE_SIZE);
	$results = array_slice($results, ($page - 1) * PAGE_SIZE, PAGE_SIZE);
	if (empty($results)) {
		//no results :(
		echo '<div class="forum_content"><p>' . translate('noresults') . '</p></div>';
	} else {
		//before continuing, paginate
		?>
		<p><?php echo translate('pages');
		$linktext = '<a href="' . $base_config['baseurl'] . '/search?query=' . htmlspecialchars($_GET['query']);
		if (isset($_GET['author'])) {
			$linktext .= '&author=' . htmlspecialchars($_GET['author']);
		}
		if (isset($_GET['forum'])) {
			$linktext .= '&forum=' . intval($_GET['forum']);
		}
		if (isset($_GET['show'])) {
			$linktext .= '&show=' . htmlspecialchars($_GET['show']);
		}
		if (isset($_GET['sortby'])) {
			$linktext .= '&sortby=' . htmlspecialchars($_GET['sortby']);
		}
		$linktext .= '&page=$page$"$bold$>$page$</a>';
		echo paginate($linktext, $page, $num_pages);
		echo '</p>';
		//get the list of post IDs
		$ids = array();
		if ($sortby == 'relevance' && defined('SHOW_SCORES')) {
			//store the scores for debugging
			$scores = array();
		}
		if (is_object($results[0])) {
			foreach ($results as $post) {
				$ids[] = $post->getId();
				if ($sortby == 'relevance' && defined('SHOW_SCORES')) {
					$scores[$post->getId()] = $post->getScore();
				}
			}
		} else {
			$ids = $results;
		}
		//now that we have the results, let's show this!
		$result = $db->query('SELECT p.deleted AS pdeleted,p.id,p.parsed_content,f.url AS furl,f.name AS forum,t.url AS turl,t.subject,t.deleted AS tdeleted,u.username AS poster,u.avatar_extension,u.id AS user_id,g.g_title AS poster_title FROM `#^posts` AS p LEFT JOIN `#^topics` AS t ON t.id=p.topic_id LEFT JOIN `#^forums` AS f ON f.id=t.forum_id LEFT JOIN `#^users` AS u ON u.id=p.poster LEFT JOIN `#^user_groups` AS g ON g.g_id=u.group_id WHERE p.id IN(' . implode(',', $ids) . ')') or enhanced_error('Failed to get post information' . implode(',', $ids), true);
		$boxes = array(); //the boxes to show
		while ($message = $db->fetch_assoc($result)) {
			$box_content = '<div class="catwrap" id="post' . $message['id'] . '"><h2 class="cat_header">';
			if ($message['pdeleted'] || $message['tdeleted']) {
				$box_content .= '&#10060; ';
			}
			$box_content .= '<a href="' . $base_config['baseurl'] . '/' . $message['furl'] . '">' . htmlspecialchars($message['forum']) . '</a> &raquo; <a href="' . $base_config['baseurl'] . '/' . $message['furl'] . '/' . $message['turl'] . '">' . htmlspecialchars($message['subject']) . '</a> &raquo; <a href="' . $base_config['baseurl'] . '/posts/' . $message['id'] . '">' . translate('post') . ' #' . $message['id'] . '</a></h2>';
			$box_content .= '<div class="cat_body' . ($message['pdeleted'] || $message['tdeleted'] ? ' deleted_post' : '') . '"><div class="postleft"><p><a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($message['poster']) . '">' . htmlspecialchars($message['poster']) . '</a></p><p><b>' . htmlspecialchars($message['poster_title']) . '</b></p>';
			if ($futurebb_config['avatars'] && file_exists(FORUM_ROOT . '/static/img/avatars/' . $message['user_id'] . '.' . $message['avatar_extension'])) {
				$box_content .= '<p><img src="' . $base_config['baseurl'] . '/img/avatars/' . $message['user_id'] . '.' . htmlspecialchars($message['avatar_extension']) . '" alt="avatar" class="avatar" /></p>';
			}
			$box_content .= '</div><div class="postright"><p>' . $message['parsed_content'] . '</p>';
			if ($sortby == 'relevance' && defined('SHOW_SCORES')) {
				$box_content .= '<hr />Score: ' . $scores[$message['id']];
			}
			$box_content .= '</div></div></div>';
			$boxes[$message['id']] = $box_content;
		}
		foreach ($ids as $id) {
			echo $boxes[$id];
		}
		?>
		<p><?php echo translate('pages');
		echo paginate($linktext, $page, $num_pages);?></p>
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
				<tr>
					<td><?php echo translate('sortby'); ?></td>
					<td><select name="sortby"><option value="relevance"><?php echo translate('relevance'); ?></option><option value="posttime"><?php echo translate('posttime'); ?></option></select> <select name="direction"><option value="desc"><?php echo translate('descending'); ?></option><option value="asc"><?php echo translate('ascending'); ?></option></select></td>
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