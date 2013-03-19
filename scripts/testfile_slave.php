<!-- Action script for /testfile.php -->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>testfile_slave.php</title>
</head>

<body>
<?php
echo '<br><br>The post array is:<br>';
print_r($_POST);
echo '<br>The posted color is: '.$_POST['color'];
echo '<br>The posted username is: '.$_POST['username'];
?>
</body>
</html>
