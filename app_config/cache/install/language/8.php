<?php
$q = 'INSERT INTO `#^language`(language,langkey,value,category) VALUES';
$lang_insert_data = array (
  0 => '(\'English\',\'sent\',\'Sent\',\'main\')',
  1 => '(\'English\',\'couldnot_display_notif\',\'The notification could not be displayed because the type of message could not be determined.\',\'main\')',
  2 => '(\'English\',\'nonotifs\',\'You have no notifications at the moment\',\'main\')',
  3 => '(\'English\',\'maintenance\',\'Maintenance\',\'main\')',
  4 => '(\'English\',\'wrote\',\'wrote\',\'main\')',
  5 => '(\'English\',\'bbcode\',\'BBCode\',\'main\')',
  6 => '(\'English\',\'produces\',\'produces\',\'main\')',
  7 => '(\'English\',\'bbcodehelp\',\'BBCode Help\',\'main\')',
  8 => '(\'English\',\'bbcodehelpintro\',\'BBCode is a tool that lets you format your posts\',\'main\')',
  9 => '(\'English\',\'basicformatting\',\'Basic formatting\',\'main\')',
  10 => '(\'English\',\'tagssupported\',\'The following tags are supported:\',\'main\')',
  11 => '(\'English\',\'boldtext\',\'Bold text\',\'main\')',
  12 => '(\'English\',\'italictext\',\'Italic text\',\'main\')',
  13 => '(\'English\',\'underlinedtext\',\'Underlined text\',\'main\')',
  14 => '(\'English\',\'struckouttext\',\'Struckouttext\',\'main\')',
  15 => '(\'English\',\'bluetext\',\'Blue text\',\'main\')',
  16 => '(\'English\',\'magentatext\',\'Magenta\',\'main\')',
  17 => '(\'English\',\'quotes\',\'Quotes\',\'main\')',
  18 => '(\'English\',\'textquoting\',\'This is the text I am quoting.\',\'main\')',
  19 => '(\'English\',\'johnsmith\',\'John Smith\',\'main\')',
  20 => '(\'English\',\'linksandimages\',\'Links and images\',\'main\')',
  21 => '(\'English\',\'style\',\'Style\',\'main\')',
  22 => '(\'English\',\'notranslation\',\'Unfortunately, this page does not have a translation available.\',\'main\')',
  23 => '(\'English\',\'blockininline\',\'The tag <b>[$1]</b> can not be placed inside <b>[$2]</b> because block tags can not be placed inside of inline tags.\',\'main\')',
  24 => '(\'English\',\'nonesting\',\'Additional BBCode tags can not go inside of a <b>[$1]</b> tag.\',\'main\')',
  25 => '(\'English\',\'shortpass\',\'Your password is shorter than 8 characters\',\'main\')',
  26 => '(\'English\',\'commonpass\',\'Your password is in the list of the most commonly used passwords\',\'main\')',
  27 => '(\'English\',\'reply\',\'Reply\',\'main\')',
  28 => '(\'English\',\'lists\',\'Lists\',\'main\')',
  29 => '(\'English\',\'listsintro\',\'If you wish to have items in a series, you can do so using the <code>[list]</code> tag.\',\'main\')',
  30 => '(\'English\',\'forbulletedlist\',\'For a bulleted list, just use <code>[list]</code>. For example,\',\'main\')',
  31 => '(\'English\',\'fornumberlist\',\'For a numbered list, just use <code>[list=1]</code>. For example,\',\'main\')',
  32 => '(\'English\',\'item#\',\'Item #$1\',\'main\')',
  33 => '(\'English\',\'timeformatdesc\',\'The following two entries allow you to set the format used for displaying all times by the software. For items that only display the date, the date format is used, but for items that display the time, the date format and time format are joined together. The formats must follow the <a href=\\"http://php.net/manual/en/function.date.php#refsect1-function.date-parameters\\">PHP guidelines</a>.\',\'main\')',
  34 => '(\'English\',\'unknown error\',\'An unknown error occurred\',\'main\')',
  35 => '(\'English\',\'specificnestingerror\',\'The tag <b>[$1]</b> cannot be placed directly inside <b>[$2]</b>.\',\'main\')',
  36 => '(\'English\',\'errorwaslocated\',\'The above error was located at: \',\'main\')',
  37 => '(\'English\',\'tagwasopened\',\'The <b>[$1]</b> tag was opened at the following location: \',\'main\')',
  38 => '(\'English\',\'archived\',\'(Archived)\',\'main\')',
  39 => '(\'English\',\'bans\',\'Bans\',\'admin\')',
);
$q = new DBMassInsert('language', array('language', 'langkey', 'value', 'category'), $lang_insert_data, 'Failed to insert language data');
$q->commit();