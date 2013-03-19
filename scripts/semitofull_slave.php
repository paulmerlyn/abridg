<?php
/*
semitofulll_slave.php is the slave for the "SemiToFull" HTML form inside the semitofullbox div within assign.php. It allows a semiregistered owner to "upgrade" to a fully registered owner by providing OwnerLabel, OwnerFirstName, OwnerLastName, and (optionally) OwnerOrganization values. These values are used to update the owner's row of owners_table. Control is then passed back to assign.php.
*/

// Start a session
session_start();

if ($_SESSION['Authenticated'] != 'true') header("Location: /index.php");

// Connect to mysql
$db = mysql_connect('localhost', 'paulme6_merlyn', 'fePhaCj64mkik')
or die('Could not connect: ' . mysql_error());
mysql_select_db('paulme6_abridg') or die('Could not select database: ' . mysql_error());

ob_start(); // Used in conjunction with ob_flush() [see www.tutorialized.com/view/tutorial/PHP-redirect-page/27316], this allows me to postpone issuing output to the screen until after the header has been sent.

// Create short variable names
$EntityType = $_POST['EntityType'];
$OwnerFirstName = $_POST['OwnerFirstName'];
$OwnerLastName = $_POST['OwnerLastName'];
$OwnerOrganization = $_POST['OwnerOrganization'];
$OwnerLabel = $_POST['OwnerLabel'];

// Assign values to the session variables that are used to prepopulate the preserved values of the posted field values in createowner.php if/when that page is represented to the user with PHP form validation errors.
$_SESSION['EntityType'] = $EntityType;
$_SESSION['OwnerFirstName'] = $OwnerFirstName;
$_SESSION['OwnerLastName'] = $OwnerLastName;
$_SESSION['OwnerOrganization'] = $OwnerOrganization;
$_SESSION['OwnerLabel'] = $OwnerLabel;
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>semitofull_slave Script</title>
<link href="/abridg.css" rel="stylesheet" type="text/css">
</head>
<body>

<?php
/*
Begin PHP form validation.
*/

// Create a session variable for the PHP form validation flag, and initialize it to 'false' i.e. assume it's valid.
$_SESSION['phpinvalidflag'] = false;

// Create session variables to hold inline error messages, and initialize them to blank.
$_SESSION['MsgOwnerFirstName'] = null;
$_SESSION['MsgOwnerLastName'] = null;
$_SESSION['MsgOwnerOrganization'] = null;
$_SESSION['MsgOwnerLabel'] = null;

// Seek to validate $OwnerFirstName
$illegalCharSet = '[~%\^\*_`\$?=!:";<>]+'; // Exclude everything except A-Z, a-z, numbers, period, hyphen, apostrophe (single quote), slash, space, comma, period, and parentheses.
$reqdCharSet = "[A-Za-z]{1,}";  // At least one letter
if (ereg($illegalCharSet, $OwnerFirstName) || !ereg($reqdCharSet, $OwnerFirstName))
	{
	$_SESSION['MsgOwnerFirstName'] = "<span class='errorphp'><br>Please provide a valid name.<br></span>";
	$_SESSION['phpinvalidflag'] = true; 
	};

// Seek to validate $OwnerLastName
$illegalCharSet = '[~#%\^\*_\+`\|&$?=!:";<>]+'; // Exclude everything except A-Z, a-z, numbers, period, hyphen, apostrophe (single quote), slash, space, comma, period, and parentheses.
$reqdCharSet = "[A-Za-z]{1,}";  // At least one letter
if (ereg($illegalCharSet, $OwnerLastName) || !ereg($reqdCharSet, $OwnerLastName))
	{
	$_SESSION['MsgOwnerLastName'] = "<span class='errorphp'><br>Please provide a valid name.<br></span>";
	$_SESSION['phpinvalidflag'] = true; 
	};

// Seek to validate $OwnerOrganization (required field only when $EntityType == 'organization')
$illegalCharSet = '[~#\^_`\";<>]+'; // Exclude everything except A-Z, a-z, numbers, period, hyphen, apostrophe (single quote), slash, &, $, ?, =, |, :, +, space, comma, *, %, period, and parentheses.
$reqdCharSet = "[A-Za-z0-9]{1,}";  // At least one letter or number
if ($EntityType == 'organization')
	if (ereg($illegalCharSet, $OwnerOrganization) || !ereg($reqdCharSet, $OwnerOrganization))
		{
		$_SESSION['MsgOwnerOrganization'] = "<span class='errorphp'>Please use only alphanumerics (A-Z, a-z, 0-9), dash (-), slash (/),<br>period (.), apostrophe ('), &, and space characters.<br></span>";
		$_SESSION['phpinvalidflag'] = true; 
		};

// Seek to validate $OwnerLabel
$illegalCharSet = '[~#\^_`\";<>]+'; // Exclude everything except A-Z, a-z, numbers, period, hyphen, apostrophe (single quote), slash, &, $, ?, =, |, :, +, space, comma, *, %, period, and parentheses.
$reqdCharSet = "[A-Za-z0-9]{1,}";  // At least one letter or number
if (ereg($illegalCharSet, $OwnerLabel) || !ereg($reqdCharSet, $OwnerLabel))
	{
	$_SESSION['MsgOwnerLabel'] = "<span class='errorphp'>Please use only alphanumerics (A-Z, a-z, 0-9), dash (-), slash (/),<br>period (.), ', &, and space characters.<br></span>";
	$_SESSION['phpinvalidflag'] = true; 
	};

//Now go back to the previous page (createowner.php) and show any PHP inline validation error messages if the $_SESSION['phpinvalidflag'] has been set to true. ... otherwise, proceed to update the database with the user's form data.
if ($_SESSION['phpinvalidflag'])
	{
	$_SESSION['SemiToFullValidationError'] = 'true';
	?>
	<script type='text/javascript' language='javascript'>window.location = '/assign.php';</script>
	<noscript>
	<?php
	if (isset($_SERVER['HTTP_REFERER']))
		header("Location: ".$_SERVER['HTTP_REFERER']); // Go back to previous page. (Similar to echoing the Javascript statement: history.go(-1) or history.back() except I think $_SERVER['HTTP_REFERER'] reloads the page. So the javascript 'history.back()' method is more suitable. However, if Javascript is enabled, php form validation is moot. And if Javascript is disabled, then the javascript 'history.back()' method won't work anyway.
	?>
	</noscript>
	</body>
	</html>
	<?php
	exit;
	}

/* Prevent cross-site scripting via htmlspecialchars on these user-entry form field */
$OwnerFirstName = htmlspecialchars($OwnerFirstName, ENT_COMPAT);
$OwnerLastName = htmlspecialchars($OwnerLastName, ENT_COMPAT);
$OwnerOrganization = htmlspecialchars($OwnerOrganization, ENT_COMPAT);
$OwnerLabel = htmlspecialchars($OwnerLabel, ENT_COMPAT);

if (!get_magic_quotes_gpc())
	{
	$OwnerFirstName = addslashes($OwnerFirstName);
	$OwnerLastName = addslashes($OwnerLastName);
	$OwnerOrganization = addslashes($OwnerOrganization);
	$OwnerLabel = addslashes($OwnerLabel);
	}		

// Update row in owners_table with values provided by the semiregistered owner, allowing him/her to now become fully registered
$query = "UPDATE owners_table SET OwnerFirstName = '".$OwnerFirstName."', OwnerLastName = '".$OwnerLastName."', OwnerOrganization = '".$OwnerOrganization."', OwnerLabel = '".$OwnerLabel."' WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
$result = mysql_query($query) or die('Query (update of owners_table for a semiregistered owner) failed: ' . mysql_error().' and the query string was: '.$query);

// Set $_SESSION['RegOwnerStatus'] to 'full' so that the owner will next time avoid seeing the black 'semitofullbox' div in assign.php on clicking the 'Abridg Director' icon.
$_SESSION['RegOwnerStatus'] = 'full';

// Send user back to assign.php page.
if (isset($_SERVER['HTTP_REFERER'])) // Antivirus software sometimes blocks transmission of HTTP_REFERER.
	{
	echo "<script type='text/javascript' language='javascript'>window.location = '/assign.php';</script>";
	ob_flush();
	}
else
	{
	header("Location: /assign.php"); 
	};
exit;

// Unset session variables that would otherwise cause unwanted prepopulation of field values upon return to the form in createowner.php.
unset($_SESSION['EntityType']);
unset($_SESSION['OwnerFirstName']);
unset($_SESSION['OwnerLastName']);
unset($_SESSION['OwnerOrganization']);
unset($_SESSION['OwnerLabel']);

ob_end_flush();
?>
</body>
</html>