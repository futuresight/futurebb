<?php
//upgrade from v1.2 to v1.3 (DB 1 -> 2)
//add new config values
set_config('date_format', 'd M Y');
set_config('time_format', 'H:i');
echo '<li>RV3: Adding new config values... success</li>';

set_config('db_version', 2);