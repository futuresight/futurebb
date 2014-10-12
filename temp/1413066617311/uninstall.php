<?php
ExtensionConfig::remove_language_key('sfs', 'English');
ExtensionConfig::remove_page('/admin/sfs');
ExtensionConfig::remove_admin_menu('sfs');
$q = new DBDelete('config', 'c_name=\'sfs_max_score\'');
$q->commit();
$q = new DBDelete('config', 'c_name=\'sfs_check_values\'');
$q->commit();