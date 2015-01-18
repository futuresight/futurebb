<?php
/*-authbegin-*/$user = ''; $pass = '';/*-authend-*/
if (!isset($_SERVER['PHP_AUTH_USER']) && $pass != '') {
    header('WWW-Authenticate: Basic realm="This is a protected script"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'To prevent abuse, you are required to authenticate.';
    exit;
} else {
	if ($pass != '')
		if ($_SERVER['PHP_AUTH_USER'] != $user || $_SERVER['PHP_AUTH_PW'] != $pass) {
			echo 'Incorrect authentication!'; die;
		}
}
?>
<?
if($_GET['special'] == 'getdir') {
	
	$array[] = '';
	
	$h = opendir($_GET['dir']);
	while ($f = readdir($h)) {
		if ($f != '..' && $f != '.' && is_dir(dirname(__FILE__) . '/' . $_GET['dir'] . '/'. $f))
			$array[] = $f;
	}
	sort($array);
	
	foreach ($array as $val) {
		if($val != '') {
			echo '<tr><td><input type=checkbox name="exclude[' . $val . ']" value="foo" /></td><td>' . $val . '</td></tr>' . "\n";
		}
	}
	
	die;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-type" content="text/html;charset=UTF-8">
<title>Directory checker</title>
<script language="javascript" type="text/javascript">
function checkAll(me) {
	arr = document.getElementsByTagName('input');
	for (i = 0; i < arr.length; i++) {
		if ((arr[i].type == 'checkbox') && (arr[i].value == 'foo')) {
			if (me.checked) {
				arr[i].checked = 'checked';
			} else {
				arr[i].checked = '';
			}
		}
	}
}
function updateExcludedDirs(newdir) {
	document.getElementById('excludeContainer').innerHTML = 'Loading...';
	document.getElementById('dir').value = newdir;
	var xmlhttp;
	if (window.XMLHttpRequest) { // code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp=new XMLHttpRequest();
	} else { // code for IE6, IE5
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	
	xmlhttp.onreadystatechange=function() {
		if (xmlhttp.readyState==4 && xmlhttp.status==200) {	
			document.getElementById('excludeContainer').innerHTML = xmlhttp.responseText;
		}
	}
	
	xmlhttp.open("GET", "?special=getdir&dir=" + newdir, true);
	xmlhttp.send();
	document.getElementById('excludeContainer').innerHTML = '<p>Loading...</p>';
}
</script>
<style type="text/css">
#gobutton {
	background-color: #90F;
	color: #FFF;
	width: 80%;
	font-weight: bold;
	font-size: 18px;
	border: thin #C0F solid;
}
#gobutton:hover {
	background-color: #96F;
}
#content {
	background-color: #DDF;
	margin: 0 0;
}
#header {
	background-color: #96F;
	min-height: 50px;
	margin-bottom: 10px;
}
#footer {
	background-color: #96F;
	min-height: 50px;
	margin-bottom: 10px;
}
</style>
</head>
<body style="background-color: #414; font-family: Verdana, Geneva, sans-serif;">
<div id="content">
<div id="header" style=>
<h3 style="color: #FFF; font-family: Verdana, Geneva, sans-serif; font-size:24px; padding: 10px 10px;">Website&nbsp;code&nbsp;line&nbsp;counter -&nbsp;FutureSight&nbsp;Technologies</h3>
</div>
<?php
if (isset($_POST['dir'])) {
	if (!isset($_POST['exts'])) {
		echo '<p>Um, you kind of have to specify extensions...</p>';
	} else {
		//define runtime constants
		$path = $_POST['dir'];
		foreach ($_POST['exts'] as $key => $val) {
			$allowedexts[] = $key;
		}
		function lines($f) {
			return sizeOf(file($f));
		}
		function GetFolderSize($dirname) {
			global $allowedexts;
			
			if($_POST['exclude'][basename($dirname)] == 'bar') {
				return 0;
			}
			$dir_handle = opendir($dirname);
			if (!$dir_handle) return 0;
			while ($file = readdir($dir_handle)){
				if ($file != '.' && $file  !=  '..') {
					if (is_dir($dirname . '/' . $file)) {
						$folderSize += GetFolderSize($dirname . '/' . $file);
					} else {
						$array = explode('.', $file);
						$ext = end($array);
						if (in_array($ext, $allowedexts))
							$folderSize += lines($dirname . '/' . $file);
					}
				}
			}
			
			closedir($dir_handle);
			return $folderSize;
		}
		$size = getfoldersize($path);
		if (!$size) {
			$size = 0;
		}
		echo '<p>There are <b>' . $size . '</b> lines of code in the directory "' . dirname(realpath($path)) . '/' . basename(realpath($path)) . '" with the following extensions.</p>';
		echo '<p>The script looked for files of type: ' . implode(', ', $allowedexts) . '</p>';
		if(sizeof($_POST['exclude']) > 0) {
			foreach($_POST['exclude'] as $ky => $val) {
				$ignored[] = $ky;
			}
			echo '<p>The following directories were ignored: ' . implode(', ', $ignored) . '</p>';
		}
	}
	echo '<p><a href="size.php">Check something else</a></p>';
} elseif (isset($_POST['newuser'])) {
	if ($_POST['newpass'] != $_POST['newpass2']) {
		echo 'Passwords don&apos;t match. <a href="javascript:history.go(-1);">Go back</a>';
	}
	$t = preg_replace('/\/\*-authbegin-\*\/(.*)\/\*-authend-\*\//', '/*-authbegin-*/$user = \'' . addslashes($_POST['newuser']) . '\'; $pass = \'' . addslashes($_POST['newpass']) . '\';/*-authend-*/', file_get_contents(__FILE__), 1);
	file_put_contents(__FILE__, $t);
	echo 'Password changed! <a href="javascript:window.location.reload()">Refresh</a>';
} else {
?>
<form action="size.php" method="post">
<table width="80%" border="1" align="center" cellpadding="5" cellspacing="0" style="border: thin solid #96F;">
<tr><td>Extensions</td><td><table border="0">
<?php
echo '<tr><td><input type=checkbox name="exts[php]" value="foo" checked /></td><td>PHP</td></tr>';
$exts = array('html', 'htm', 'css', 'js', 'asp', 'aspx', 'py', 'cgi', 'htaccess', 'ini');
foreach ($exts as $val) {
	echo '<tr><td><input type=checkbox name="exts[' . $val . ']" value="foo" /></td><td>' . strtoupper($val) . '</td></tr>';
}
?>
<tr><td><input type=checkbox onClick="checkAll(this)" /></td><td><em>Check all</em></td></tr>
</table></td><td></td></tr>
<tr><td>Path</td><td><input type=text name="dir" id="dir" value="." /></td>
	<td>
		<select onChange="updateExcludedDirs(this.value);">
		<?php
		$h = opendir('.');
		while ($f = readdir($h)) {
			if ($f != '..' && is_dir($f))
				$array[] = $f;
		}
		sort($array);
		foreach ($array as $val) {
			$lbl = $val;
			if($val == '.') $lbl = "&lt;current dir&gt;";
			echo '<option value="' . $val . '">' . $lbl . '</option>';
		}
		?>
		</select>
	</td>
</tr>
<tr>
<td>
Exclude</td><td>
<table border="0" id="excludeContainer">
	<?php
		foreach ($array as $val) {
			if($val != '.') {
				echo '<tr><td><input type=checkbox name="exclude[' . $val . ']" value="bar" /></td><td>' . $val . '</td></tr>';
			}
		}
	?>
</table>
</td><td>&nbsp;</td>
</tr>
</table>
<center><input type=submit value="Go" id="gobutton" /></center>
</form>
<div style="background-color: #C9F; width: 320px; margin-left: 20px; margin-top: 20px;">
<h3 style="text-align: center;">Change password</h3>
<form action="size.php" method="POST" enctype="multipart/form-data">
<table border="0">
<tr><td>New username</td><td><input type=text name="newuser" /></td></tr>
<tr><td>New password</td><td><input type=password name="newpass" /></td></tr>
<tr><td>Confirm password</td><td><input type=password name="newpass2" /></td></tr>
<tr><td><input type=submit value="Go" style="background-color: #90F; color: #FFF; width: 80%;" /></td><td></td></tr>
</table>
</form>
</div>
<?php } ?>
<div id="footer">
<p style="padding: 10px 10px; color:#FFF; font-weight: bold;">Copyright &copy;2012 <a href="http://futuresight.org">FutureSight Technologies</a></p>
</div>
</div>
</body>
</html>