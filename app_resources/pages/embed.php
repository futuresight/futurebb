<?php
$tid = intval($_GET['tid']);
$result = $db->query('SELECT t.id,t.url,t.subject,t.closed,t.sticky,t.last_post,t.last_post_id,t.first_post_id,t.redirect_id,f.name AS forum_name,f.id AS forum_id,f.url AS forum_url,f.view_groups,f.reply_groups,rt.id AS tracker_id FROM `#^topics` AS t LEFT JOIN `#^forums` AS f ON f.id=t.forum_id LEFT JOIN `#^read_tracker` AS rt ON rt.topic_id=t.id AND rt.user_id=' . $futurebb_user['id'] . ' AND rt.forum_id IS NULL WHERE f.id IS NOT NULL AND t.id=\'' . $db->escape($tid) . '\' AND t.deleted IS NULL') or error('Failed to get topic info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result)) {
	httperror(404);
}

if (isset($_GET['page'])) {
	$page = intval($_GET['page']);
} else {
	$page = 1;
}
$cur_topic = $db->fetch_assoc($result);
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
    	<title><?php echo htmlspecialchars($cur_topic['subject'] . ' (Embedded) - ' . $futurebb_config['board_title']); ?></title>
        <link rel="stylesheet" href="<?php echo $base_config['baseurl']; ?>/styles/embed.css" />
        <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    </head>
    <body>
    	<div id="futurebb">
            <p id="breadcrumbs"><a href="<?php echo $base_config['baseurl']; ?>" target="_BLANK"><?php echo htmlspecialchars($futurebb_config['board_title']); ?></a> &raquo; <a href="<?php echo $base_config['baseurl']; ?>/<?php echo htmlspecialchars($cur_topic['forum_url']); ?>" target="_BLANK"><?php echo htmlspecialchars($cur_topic['forum_name']); ?></a> &raquo; <a href="<?php echo $base_config['baseurl']; ?>/<?php echo htmlspecialchars($cur_topic['forum_url']); ?>/<?php echo htmlspecialchars($cur_topic['url']); ?>" target="_BLANK"><?php echo htmlspecialchars($cur_topic['subject']); ?></a></p>
            <?php
            //get post count
            $result = $db->query('SELECT COUNT(id) FROM `#^posts` WHERE topic_id=' . $cur_topic['id']) or error('Failed to get topic count', __FILE__, __LINE__, $db->error());
            list($num_posts) = $db->fetch_row($result);
			?>
            <p><?php echo translate('pages');
			echo paginate('<a href="' . $base_config['baseurl'] . '/embed?tid=' . $tid . '&amp;page=$page$" $bold$>$page$</a>', $page, ceil($num_posts / $futurebb_config['posts_per_page']));
			?></p>
            <?php
            
            //get all of the posts
            $result = $db->query('SELECT p.id,p.parsed_content,p.posted,p.poster_ip,p.last_edited,u.username AS author,u.id AS author_id,u.parsed_signature AS signature,u.last_page_load,u.num_posts,u.avatar_extension,g.g_title AS user_title,leu.username AS last_edited_by FROM `#^posts` AS p LEFT JOIN `#^users` AS u ON u.id=p.poster LEFT JOIN `#^user_groups` AS g ON g.g_id=u.group_id LEFT JOIN `#^users` AS leu ON leu.id=p.last_edited_by WHERE p.topic_id=' . $cur_topic['id'] . ' AND p.deleted IS NULL ORDER BY p.posted ASC LIMIT ' . (($page - 1) * intval($futurebb_config['posts_per_page'])) . ',' . intval($futurebb_config['posts_per_page'])) or error('Failed to get posts', __FILE__, __LINE__, $db->error());
            
            while ($cur_post = $db->fetch_assoc($result)) {
                ?>
                <div class="catwrap" id="post<?php echo $cur_post['id']; ?>">
                    <h2 class="cat_header">
                    <?php echo '<span class="floatright"><a href="' . $base_config['baseurl'] . '/posts/' . $cur_post['id'] . '">' . user_date($cur_post['posted']) . '</a></span><span style="display:none">: </span>'; ?>
                    <?php
					if ($futurebb_config['avatars'] && file_exists(FORUM_ROOT . '/static/avatars/' . $cur_post['author_id'] . '.' . $cur_post['avatar_extension'])) {
						echo '<img src="' . $base_config['baseurl'] . '/static/avatars/' . $cur_post['author_id'] . '.' . $cur_post['avatar_extension'] . '" alt="user avatar" class="avatar" />';
					}
					?> <span class="username"><a href="<?php echo $base_config['baseurl']; ?>/users/<?php echo htmlspecialchars($cur_post['author']); ?>" target="_BLANK" title="<?php echo $cur_post['user_title']; ?>"><?php echo htmlspecialchars($cur_post['author']); ?></a></span></h2>
                    <div class="cat_body">
                        <div class="postright">
                            <p><?php echo str_replace('<a', '<a target="_BLANK"', $cur_post['parsed_content']); ?></p>
                            <?php
                            if ($cur_post['signature']) {
                                echo '<hr /><p';
                                if ($futurebb_config['sig_max_height']) {
                                    echo ' style="max-height:' . $futurebb_config['sig_max_height'] . 'px; overflow:hidden"';
                                }
                                echo '>' . $cur_post['signature'] . '</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <?php
            }
        ?>
        <p><?php echo translate('pages');
		echo paginate('<a href="' . $base_config['baseurl'] . '/embed?tid=' . $tid . '&amp;page=$page$" $bold$>$page$</a>', $page, ceil($num_posts / $futurebb_config['posts_per_page']));
		?></p>
        <p><?php echo translate('poweredby'); ?></p>
        </div>
    </body>
</html>