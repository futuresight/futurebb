<?php
header('HTTP/1.1 403 Forbidden');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Access restricted</title>
<style type="text/css">
#content {
	margin-top:50px;
	margin-left: 20%;
	margin-right:20%;
	background-color:#AAA;
	text-align:center;
	border:1px solid #000;
	font-family:Arial, Helvetica, sans-serif;
}
</style>
</head>

<body>
	<div id="content">
		<form action="" method="post" enctype="multipart/form-data">
			<p><img src="http://futuresight.org/img/fs-logo-256.png" /></p>
			<h1>Restricted</h1>
			<p>Warning! This is a private prototype only accessible to FutureSight employees. If any FutureSight employee reveals the access code to anyone else, then they can and will be fired immediately without any question or discussion.</p>
			<p>
				<input type="password" name="accesscode" /><br />
				<input type="submit" value="Go" />
			</p>
		</form>
	</div>
</body>
</html>