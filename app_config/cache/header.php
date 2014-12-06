<?php
$nav_items = array();
$nav_items[] = CacheEngine::replace_interface_strings('<a href="' . $base_config['baseurl'] . '/">' . translate('index') . '</a>');
if ($futurebb_user['id'] != 0) {
	$nav_items[] = CacheEngine::replace_interface_strings('<a href="' . $base_config['baseurl'] . '/users/$username$">' . translate('profile') . '</a>');
}
if ($futurebb_user['g_user_list']) {
	$nav_items[] = CacheEngine::replace_interface_strings('<a href="' . $base_config['baseurl'] . '/users">' . translate('userlist') . '</a>');
}
$nav_items[] = CacheEngine::replace_interface_strings('<a href="' . $base_config['baseurl'] . '/search">' . translate('search') . '</a>');
if ($futurebb_user['g_admin_privs']) {
	$nav_items[] = CacheEngine::replace_interface_strings('<a href="' . $base_config['baseurl'] . '/admin">' . translate('administration') . '</a>');
}
if ($futurebb_user['g_mod_privs'] && !$futurebb_user['g_admin_privs']) {
	$nav_items[] = CacheEngine::replace_interface_strings('<a href="' . $base_config['baseurl'] . '/admin/bans">' . translate('administration') . '</a>');
}
if ($futurebb_user['id'] == 0) {
	$nav_items[] = CacheEngine::replace_interface_strings('<a href="' . $base_config['baseurl'] . '/register/$reghash$">' . translate('register') . '</a>');
}
if ($futurebb_user['id'] != 0) {
	$nav_items[] = CacheEngine::replace_interface_strings('<a href="' . $base_config['baseurl'] . '/logout">' . translate('logout') . '</a>');
}
