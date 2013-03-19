<?php
/*
managedestroy_slave.php is one of the action scripts called by the form in the "deleteaccountdiv" div in manage.php. It pertains to the logged in Owner's (i.e. Administrator's) selection of a "Delete My Account" radio button found under the 'Manage' menu in Abridg Director/administrator console.
	This slave script predominantly invokes the OwnerDestroyer($theOwnerID) function, which it accesses via the indclude'd corefunctions.php SSI script. In this context, the input parameter will be $_SESSION['LoggedInOwnerID']. After function performs the heavy lifting, managedestroy_slave.php merely needs to present the (now ex-) Owner with an on-screen message to say his/her account has been destroyed.
*/

// Start a session
session_start();

if ($_SESSION['Authenticated'] != 'true') header("Location: /index.php");

ob_start(); // Used in conjunction with ob_flush() [see www.tutorialized.com/view/tutorial/PHP-redirect-page/27316], this allows me to postpone issuing output to the screen until after the header has been sent.

// Connect to mysql
$db = mysql_connect('localhost', 'paulme6_merlyn', 'fePhaCj64mkik')
or die('Could not connect: ' . mysql_error());
mysql_select_db('paulme6_abridg') or die('Could not select database: ' . mysql_error());

require('../ssi/corefunctions.php'); // Include the corefunctions.php file, which contains the OwnerDestroyer() function.

// Call the function
OwnerDestroyer($_SESSION['LoggedInOwnerID']);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Abridg&trade; | Connection 2.0&trade;</title>
<link href="/abridg.css" rel="stylesheet" type="text/css">
</head>
<body>
<table cellpadding="0" cellspacing="0" style="margin-top: 50px; margin-left: auto; margin-right: auto; position: relative; left: -7px;">
<tr>
<td style="text-align: left;">
<p class='text' style='margin-left: 150px; margin-right: 150px; margin-top: 40px; margin-bottom: 20px;'>
Your account has been deleted from the Abridg database. Thank you. Come try us again soon!</p>
</td>
<tr>
<td style="text-align: center;">
<!-- Clicking this "Continue" button will take the user to the index.php home page (not, obviously, logged in since the user has just destroyed his/her account) via logout.php. -->
<form method="post" action="/scripts/logout.php" style="display: inline;">
<input type="hidden" name="LoggedOut" value="true">
<input type="submit" class="buttonstyle" value="Continue" style="text-align: center;">
</form> 
</td>
</tr>
</table>
</body>
</html>