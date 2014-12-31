<?php
//upgrade from v1.1 to v1.2 (DB 0 -> 1)
//add new config values
set_config('header_links', '<?xml version="1.0" ?>
<linkset>
    <link path="">index</link>
    <link path="users/$username$" perm="valid">profile</link>
    <link path="users" perm="g_user_list">userlist</link>
    <link path="search">search</link>
    <link path="admin" perm="g_admin_privs">administration</link>
    <link path="admin/bans" perm="g_mod_privs ~g_admin_privs">administration</link>
    <link path="register/$reghash$" perm="~valid">register</link>
    <link path="logout" perm="valid">logout</link>
</linkset>');
set_config('admin_pages', 'PT5pbmRleApiYW5zPT5iYW5zCnJlcG9ydHM9PnJlcG9ydHMKY2Vuc29yaW5nPT5jZW5zb3JpbmcKZm9ydW1zPT5mb3J1bXMKaXBfdHJhY2tlcj0+aXB0cmFja2VyCnVzZXJfZ3JvdXBzPT51c2VyZ3JvdXBzCnRyYXNoX2Jpbj0+dHJhc2hiaW4KbWFpbnRlbmFuY2U9Pm1haW50ZW5hbmNlCnN0eWxlPT5zdHlsZQpleHRlbnNpb25zPT5leHRlbnNpb25zCmludGVyZmFjZT0+aW50ZXJmYWNl');
set_config('mod_pages', 'YmFucz0+YmFucwpyZXBvcnRzPT5yZXBvcnRzCnRyYXNoX2Jpbj0+dHJhc2hiaW4KaXBfdHJhY2tlcj0+aXB0cmFja2Vy');
echo '<li>RV2: Adding new config values... success</li>';