<?php
LoginController::LogInUser(0, '', '');
header('Location: ' . $base_config['baseurl']);
return;