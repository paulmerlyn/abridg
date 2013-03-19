<?php
/*
changeuserview.php is a form processor script that's called when an Administrator who is viewing a User Account view via index.php changes the drop-down menu that appears in the top-left corner of the screen whenever the person logged in is an Account Owner. The drop-down menu allows the Owner to see what each of his/her User Account holders would see if those people were logged in directly under their own passwords.
	changeuserview.php  sets $_SESSION['AccountID'] to the value pertaining to the selected user account holder, set $_SESSION['PreviousLogIn'] to the selected account holder's AccountLastLogin value, and then sends control back to index.php. index.php can then use the new value of $_SESSION['AccountID'] to redisplay the index.php page, this time retrieving only those media items to which this account holder has been granted access. The value of $_SESSION['OwnerID'] (i.e. the account of the Owner who owns the content) remains unchanged.
*/

// Start a session
session_start();

ob_start(); // Used in conjunction with ob_flush() [see www.tutorialized.com/view/tutorial/PHP-redirect-page/27316], this allows me to postpone issuing output to the screen until after the header has been sent.

if ($_SESSION['Authenticated'] != 'true') exit;

// Create short variable names
$AccountInView = $_POST['AccountInView'];

$_SESSION['AccountID'] = $AccountInView;

// Also update the value of $_SESSION['PreviousLogIn'] so that the Media Gallery view shows only the appropriate new items (i.e. new relative to whichever user has been selected in the AccountInView drop-down menu in index.php) when the 'New' panel (i.e. NewMediaScreen) is clicked. To do that, we need to retrieve the AccountLastLogin value from accounts_table for the account whose AccountID is $AccountInView.

$db = mysql_connect('localhost', 'paulme6_merlyn', 'fePhaCj64mkik')
or die('Could not connect: ' . mysql_error());
mysql_select_db('paulme6_abridg') or die('Could not select database: ' . mysql_error());

$query = "SELECT AccountLastLogin FROM accounts_table WHERE AccountID = ".$AccountInView;
$result = mysql_query($query) or die('Query (select of AccountLastLogin from accounts_table) failed: ' . mysql_error().' and the database query string was: '.$query);
$row = mysql_fetch_assoc($result); 
$_SESSION['PreviousLogIn'] = $row['AccountLastLogin'];

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Form Processor for the AccountInView Drop-Down Menu</title>
</head>

<body>
<script type='text/javascript' language='javascript'>window.location = '/index.php';</script>
<noscript>
<?php
if (isset($_SERVER['HTTP_REFERER'])) header("Location: ".$_SERVER['HTTP_REFERER']); // Go back to previous page. (Similar to echoing the Javascript statement: history.go(-1) or history.back() except I think $_SERVER['HTTP_REFERER'] reloads the page. So the javascript 'history.back()' method is more suitable. However, if Javascript is enabled, php form validation is moot. And if Javascript is disabled, then the javascript 'history.back()' method won't work anyway.
?>
</noscript>
<?php
ob_end_flush();
exit;
?>
</body>
</html>
