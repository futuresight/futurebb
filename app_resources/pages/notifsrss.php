<?php
header('Content-type: text/xml');
$type = $dirs[2];
translate('<addfile>', 'rss');
$output = '<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0">

<channel>
	<title><$title></title>
	<description><$description></description>	
	<link>' . $base_config['baseurl'] . '/messages</link>
	<generator>FutureBB</generator>';

$output .= "\n\t" . '<item>' . "\n\t\t" . '<title><![CDATA[' . htmlspecialchars($cur_topic['forum_name']) . ' / ' . htmlspecialchars($cur_topic['subject']) . ']]></title>';
$output .= "\n\t\t" . '<pubDate>' . gmdate('D, d M Y H:i:s', $post['posted']) . ' +0000</pubDate>';
$output .= "\n\t\t" . '<link>' . $base_config['baseurl'] . '/posts/' . $post['id'] . '</link>';
$output .= "\n\t\t" . '<guid>' . $base_config['baseurl'] . '/posts/' . $post['id'] . '</guid>';
$output .= "\n\t\t" . '<author><![CDATA[' . htmlspecialchars($post['poster']) . ']]></author>';
$output .= "\n\t\t" . '<description><![CDATA[' . strip_tags($post['parsed_content']) . ']]></description>';
$output .= "\n\t" . '</item>';
$output .= "\n" . '</channel></rss>';
$output = str_replace('<$title>', $title, $output);
$output = str_replace('<$description>', $description, $output);
echo $output;