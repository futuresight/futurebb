<?php
function split_into_words($text, $idx = false) {
	//this function is copied from FluxBB
	// Remove BBCode
	$text = preg_replace('%\[/?(b|i|u|url)\]%', ' ', $text);

	// Remove any apostrophes or dashes which aren't part of words
	$text = substr(preg_replace('%((?<=[^\p{L}\p{N}])[\'\-]|[\'\-](?=[^\p{L}\p{N}]))%u', '', ' '.$text.' '), 1, -1);

	// Remove punctuation and symbols (actually anything that isn't a letter or number), allow apostrophes and dashes (and % * if we aren't indexing)
	$text = preg_replace('%(?![\'\-'.($idx ? '' : '\%\*').'])[^\p{L}\p{N}]+%u', ' ', $text);

	// Replace multiple whitespace or dashes
	$text = preg_replace('%(\s){2,}%u', '\1', $text);

	// Fill an array with all the words
	$words = explode(' ', $text);

	return $words;
}

function update_search_index($pid,$msg) {
	global $db;
	$words = split_into_words($msg, true);
	$db->query('DELETE FROM `#^search_index` WHERE post_id=' . $pid) or error('Failed to delete existing search stuff', __FILE__, __LINE__, $db->error());
	$q = array();
	$locations = array();
	foreach ($words as $key => $word) {
		if (isset($locations[$word])) {
			$locations[$word][] = $key;
		} else {
			$locations[$word] = array($key);
		}
	}
	foreach ($locations as $word => $curlocs) {
		if (trim($word) != '') {
			$q[] = '(' . $pid . ',\'' . $db->escape(strtolower($word)) . '\',\'' . $db->escape(implode(',', $curlocs)) . '\')';
		}
	}
	if (!empty($q)) {
		$query = new DBMassInsert('search_index', array('post_id', 'word', 'locations'), $q, 'Failed to insert search engine data');
		$query->commit();
	}
}