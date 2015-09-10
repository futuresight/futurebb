<?php
$page_title = 'Clear cache';
$breadcrumbs = array(translate('administration') => 'admin', translate('interface') => 'admin/interface', 'Clear cache' => 'admin/interface/clearcache');

CacheEngine::CacheHeader();
CacheEngine::CacheLanguage();
CacheEngine::CacheAdminPages();
CacheEngine::CachePages();
CacheEngine::CacheCommonWords();

redirect($base_config['baseurl'] . '/admin/interface');