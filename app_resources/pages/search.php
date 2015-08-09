<?php
$page_title = 'Search';
define('BASE', 2); //the base number, like scoring 2^n

class SearchItem {
	private $mwords;
	private $score = -1;
	private $keywords = array();
	private $time;
	private $id;
	function __construct($message, $time, $id) {
		$this->mwords = split_into_words($message);
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
	
	function addKeyword($keyword) {
		//when adding a keyword, index its locations
		$word = new SearchWord($keyword);
		foreach ($this->mwords as $key => $mword) {
			if ($keyword == $mword) {
				$word->addLocation($key);
			}
		}
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
	
	function addLocation($loc) {
		$this->locations[] = $loc;
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
	$terms = split_into_words($_GET['query']);
	$keywords = $terms;
	foreach ($terms as &$term) {
		$term = '\'' . $db->escape($term) . '\'';
	}
	$result = $db->query('SELECT p.content,p.id AS post_id,p.posted,i.num_matches,i.word FROM `#^search_index` AS i LEFT JOIN `#^posts` AS p ON p.id=i.post_id WHERE i.word IN(' . implode(',', $terms) . ')') or enhanced_error('Failed to get search information', true);
	$results = array();
	while ($match = $db->fetch_assoc($result)) {
		if (isset($results[$match['post_id']])) {
			$results[$match['post_id']]->addKeyword($match['word']);
		} else {
			$item = new SearchItem($match['content'], $match['posted'], $match['post_id']);
			$item->addKeyword($match['word']);
			$results[$match['post_id']] = $item;
		}
	}
	usort($results, function($m1, $m2) {
		return $m1->compareTo($m2);
	});
	$results = array_reverse($results);
	//now that we have the results, choose the ones we want by page, and then get the rest of the information
	$page = isset($_GET['p']) ? min(1, intval($_GET['p'])) : 1;
	$num_pages = ceil(sizeof($results) / 25);
	$results = array_slice($results, ($page - 1) * 25, 25);
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
		$linktext .= '&page=$page$"$bold$>$page$</a>';
		echo paginate($linktext, $page, $num_pages);
		echo '</p>';
		//get the list of post IDs
		$ids = array();
		foreach ($results as $post) {
			$ids[] = $post->getId();
		}
		//now that we have the results, let's show this!
		$result = $db->query('SELECT p.id,p.parsed_content,f.url AS furl,f.name AS forum,t.url AS turl,t.subject,u.username AS poster,u.avatar_extension,u.id AS user_id,g.g_title AS poster_title FROM `#^posts` AS p LEFT JOIN `#^topics` AS t ON t.id=p.topic_id LEFT JOIN `#^forums` AS f ON f.id=t.forum_id LEFT JOIN `#^users` AS u ON u.id=p.poster LEFT JOIN `#^user_groups` AS g ON g.g_id=u.group_id WHERE p.id IN(' . implode(',', $ids) . ')') or enhanced_error('Failed to get post information', true);
		$boxes = array(); //the boxes to show
		while ($message = $db->fetch_assoc($result)) {
			$box_content = '<div class="catwrap" id="post' . $message['id'] . '"><h2 class="cat_header"><a href="' . $base_config['baseurl'] . '/' . $message['furl'] . '">' . htmlspecialchars($message['forum']) . '</a> &raquo; <a href="' . $base_config['baseurl'] . '/' . $message['furl'] . '/' . $message['turl'] . '">' . htmlspecialchars($message['subject']) . '</a> &raquo; <a href="' . $base_config['baseurl'] . '/posts/' . $message['id'] . '">' . translate('post') . ' #' . $message['id'] . '</a></h2>';
			$box_content .= '<div class="cat_body"><div class="postleft"><p><a href="' . $base_config['baseurl'] . '/users/' . htmlspecialchars($message['poster']) . '">' . htmlspecialchars($message['poster']) . '</a></p><p><b>' . htmlspecialchars($message['poster_title']) . '</b></p>';
			if ($futurebb_config['avatars'] && file_exists(FORUM_ROOT . '/static/img/avatars/' . $message['user_id'] . '.' . $message['avatar_extension'])) {
				$box_content .= '<p><img src="' . $base_config['baseurl'] . '/img/avatars/' . $message['user_id'] . '.' . htmlspecialchars($message['avatar_extension']) . '" alt="avatar" class="avatar" /></p>';
			}
			$box_content .= '</div><div class="postright"><p>' . $message['parsed_content'] . '</p>';
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