<?php
$q = 'INSERT INTO `#^language`(language,langkey,value,category) VALUES';
$lang_insert_data = array(
	array('English', $db->escape('brdtitle'), $db->escape('Board title'), 'install'),
	array('English', $db->escape('confirmation'), $db->escape('Confirmation'), 'install'),
	array('English', $db->escape('welcometofbb'), $db->escape('Welcome to FutureBB'), 'install'),
	array('English', $db->escape('intro'), $db->escape('Before you can start using your forum, you are going to need to set a few things up. This installer will make it easy for you.'), 'install'),
	array('English', $db->escape('selectlang'), $db->escape('First, please select a language:'), 'install'),
	array('English', $db->escape('continue'), $db->escape('Continue'), 'install'),
	array('English', $db->escape('back'), $db->escape('Back'), 'install'),
	array('English', $db->escape('baddb'), $db->escape('Your database information was invalid. The database reported: '), 'install'),
	array('English', $db->escape('type'), $db->escape('Type'), 'install'),
	array('English', $db->escape('host'), $db->escape('Host'), 'install'),
	array('English', $db->escape('username'), $db->escape('Username'), 'install'),
	array('English', $db->escape('pwd'), $db->escape('Password'), 'install'),
	array('English', $db->escape('name'), $db->escape('Name'), 'install'),
	array('English', $db->escape('prefix'), $db->escape('Prefix'), 'install'),
	array('English', $db->escape('MySQL'), $db->escape('MySQL'), 'install'),
	array('English', $db->escape('mysqli'), $db->escape('MySQL Improved'), 'install'),
	array('English', $db->escape('continuetest'), $db->escape('Continue and test'), 'install'),
	array('English', $db->escape('dbgood'), $db->escape('Connecting to the database was successful.'), 'install'),
	array('English', $db->escape('seturlstuff'), $db->escape('Please set the URL information below. Please note that the pre-entered values are only educated guesses. Please verify them yourself before continuing. Also please verify that there are no trailing slashes.'), 'install'),
	array('English', $db->escape('baseurl'), $db->escape('Base URL'), 'install'),
	array('English', $db->escape('baseurlpath'), $db->escape('Base URL path'), 'install'),
	array('English', $db->escape('pwdmistmatch'), $db->escape('Passwords did not match. Please try again.'), 'install'),
	array('English', $db->escape('email'), $db->escape('Email address'), 'install'),
	array('English', $db->escape('install'), $db->escape('Install'), 'install'),
	array('English', $db->escape('modify'), $db->escape('Modify'), 'install'),
	array('English', $db->escape('dbhost'), $db->escape('Database host'), 'install'),
	array('English', $db->escape('dbuser'), $db->escape('Database username'), 'install'),
	array('English', $db->escape('dbpwd'), $db->escape('Database password'), 'install'),
	array('English', $db->escape('dbname'), $db->escape('Database name'), 'install'),
	array('English', $db->escape('dbprefix'), $db->escape('Database prefix'), 'install'),
	array('English', $db->escape('adminusername'), $db->escape('Admin username'), 'install'),
	array('English', $db->escape('adminpwd'), $db->escape('Admin password'), 'install'),
	array('English', $db->escape('notdisplayed'), $db->escape('[not displayed]'), 'install'),
	array('English', $db->escape('confirmintro'), $db->escape('You are now ready to set up your forum! When you click the install button, it will prepare the database for you, and create the configuration files. If you click the modify button, it will take you back to the first page so you can modify your settings.'), 'install'),
	array('English', $db->escape('installdetails'), $db->escape('The installation details are listed below. Please review them before you finalize the installation.'), 'install'),
	array('English', $db->escape('completed'), $db->escape('Installation complete!'), 'install'),
	array('English', $db->escape('clickhere'), $db->escape('here'), 'install'),
	array('English', $db->escape('testout1'), $db->escape('Please follow the steps below to finish setting up your forum. When done, click '), 'install'),
	array('English', $db->escape('downloadxml'), $db->escape('Download the config.xml file from the link below and place it in your forum root directory'), 'install'),
	array('English', $db->escape('testout2'), $db->escape(' to test it out'), 'install'),
	array('English', $db->escape('apachemsg'), $db->escape('<li>Make sure AllowOverride is set to ON for your forum directory. If it is not, then you need to enable it.</li><li>Download the .htaccess file below and place it in your forum root directory</li>'), 'install'),
	array('English', $db->escape('noapachemsg'), $db->escape('<li>Rewrite all HTTP requests to the root directory of your forum to dispatcher.php</li>'), 'install'),
	array('English', $db->escape('xmllink'), $db->escape('Download config.xml'), 'install'),
	array('English', $db->escape('htalink'), $db->escape('Download .htaccess'), 'install'),
	array('English', $db->escape('weirderror'), $db->escape('Installation error of some sort. We don&apos;t know why. Sorry!'), 'install'),
	array('English', $db->escape('selectdbtype'), $db->escape('Select a database type: '), 'install'),
	array('English', $db->escape('baddbtype'), $db->escape('Your server does not support the database type selected. Please try again.'), 'install'),
	array('English', $db->escape('adminemail'), $db->escape('Administrator email'), 'install'),
	array('English', $db->escape('dbfile'), $db->escape('File to store SQLite'), 'install'),
	array('English', $db->escape('installcomplete'), $db->escape('Installation complete!'), 'install'),
);
foreach ($lang_insert_data as &$entry) {
	$entry = '(\'' . implode('\',\'', $entry) . '\')';
}
$q = new DBMassInsert('language', array('language', 'langkey', 'value', 'category'), $lang_insert_data, 'Failed to insert language data');
$q->commit();
