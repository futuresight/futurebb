<?php
set_config('sfs_check', 'username|email');
add_page('/admin/sfs', 'admin/sfsadmin.php');
add_admin_menu('StopForumSpam', '/admin/sfs');