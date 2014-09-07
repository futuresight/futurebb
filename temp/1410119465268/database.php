<?php
ExtensionConfig::add_language_key('sfs', 'StopForumSpam');
ExtensionConfig::add_page('/admin/sfs', array('file' => 'index.php', 'template' => true, 'nocontentbox' => true, 'admin' => true));
ExtensionConfig::add_admin_menu('sfs', 'sfs');