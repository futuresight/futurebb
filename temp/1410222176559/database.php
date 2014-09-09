<?php
$error = 'hi'; return;
ExtensionConfig::add_language_key('sfs', 'StopForumSpam', 'English');
ExtensionConfig::add_page('/admin/sfs', array('file' => 'admin/sfs.php', 'template' => true, 'nocontentbox' => true, 'admin' => true));
ExtensionConfig::add_admin_menu('sfs', 'sfs');