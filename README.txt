Welcome to FutureBB.

This app is based on the FutureBB Framework.

NGINX USERS:
If you use nginx, this software does not currently support automatic URL rewriting. This means that you either need to add /dispatcher.php to the base URL when setting up the software (in which case all URLs will look like http://futuresight.org/forums/dispatcher.php/some-forum/some-topic) or configure URL rewriting yourself. All requests to the forum directory need to go toward dispatcher.php except those directed at the "/static" directory, and all GET variables must be preserved.

Setup instructions:
1. If you plan on using MySQL and do not already have a database available, create one. If you are using SQLite, please make sure the necessary libraries are installed (please note that this software currently only supports SQLite 3, no earlier versions).
2. Go to http://yourforum.com/path/to/forum/install.php
3. Follow the on-screen instructions.

License
This software is copyrighted (C)2012-2013 FutureSight Technologies. All rights reserved.
This software is provided in the hope that it will be useful, but ABSOLUTELY NO GUARANTEES ARE MADE. This software is provided "as-is", WITHOUT ANY WARRANTY OF ANY KIND, EXPRESS OR IMPLIED. Should any advertised feature not work, you may notify FutureSight Technologies support (http://www.futuresight.org/support). However, FutureSight Technologies and its employees are under ABSOLUTELY NO OBLIGATION OF ANY KIND to fix it.
This software may be distributed freely, as long as there is no monetary gain involved ("non-commercial"). It may also be modified freely, but any copyright notices in the code comments MUST be kept intact.
