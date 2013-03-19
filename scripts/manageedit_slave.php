<?php
/*
manageedit_slave.php is the slave for the "editaccountdiv" div form inside manage.php by which an owner can edit his/her own account details -- specifically, OwnerLabel, OwnerFirstName, OwnerLastName, OwnerOrganization (optional), OwnerUsername, and OwnerPassword as stored in owners_table and associates_table. Changes impact owners_table and associates_table only.
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
$OwnerUsername = $_POST['OwnerUsername'];
$OwnerPassword = $_POST['OwnerPassword'];

// Assign values to the session variables that are used to prepopulate the preserved values of the posted field values in createowner.php if/when that page is represented to the user with PHP form validation errors.
$_SESSION['EntityType'] = $EntityType;
$_SESSION['OwnerFirstName'] = $OwnerFirstName;
$_SESSION['OwnerLastName'] = $OwnerLastName;
$_SESSION['OwnerOrganization'] = $OwnerOrganization;
$_SESSION['OwnerLabel'] = $OwnerLabel;
$_SESSION['OwnerUsername'] = $OwnerUsername;
$_SESSION['OwnerPassword'] = $OwnerPassword;
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>manageedit_slave Script</title>
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
$_SESSION['MsgOwnerUsername'] = null;
$_SESSION['MsgOwnerPassword'] = null;

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
	$_SESSION['ManageEditValidationError'] = 'true'; // Use this to control which divs have display='bock' (cf. 'none') in manage.php
	?>
	<script type='text/javascript' language='javascript'>window.location = '/manage.php';</script>
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
$OwnerUsername = htmlspecialchars($OwnerUsername, ENT_COMPAT);
$OwnerPassword = htmlspecialchars($OwnerPassword, ENT_COMPAT);

if (!get_magic_quotes_gpc())
	{
	$OwnerFirstName = addslashes($OwnerFirstName);
	$OwnerLastName = addslashes($OwnerLastName);
	$OwnerOrganization = addslashes($OwnerOrganization);
	$OwnerLabel = addslashes($OwnerLabel);
	$OwnerUsername = addslashes($OwnerUsername);
	$OwnerPassword = addslashes($OwnerPassword);
	}		

// First retrieve the preexisting value of the owner's OwnerPassword before any change/edit caused it to be overwritten in owners_table. (We'll need to know this later.)
$query = "SELECT OwnerPassword FROM owners_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
$result = mysql_query($query) or die('Query (select of OwnerPassword from owners_table) failed: ' . mysql_error().' and the query string was: '.$query);
$row = mysql_fetch_assoc($result); 
$PriorPassword = $row['OwnerPassword'];

// Update row in owners_table, replacing existing OwnerLabel, OwnerFirstName, OwnerLastName, OwnerOrganization (optional), OwnerUsername, and OwnerPassword with any new values provided by the logged in owner via the 'editaccountdiv' HTML form in manage.php. 
$query = "UPDATE owners_table SET OwnerFirstName = '".$OwnerFirstName."', OwnerLastName = '".$OwnerLastName."', OwnerOrganization = '".$OwnerOrganization."', OwnerLabel = '".$OwnerLabel."', OwnerUsername = '".$OwnerUsername."', OwnerPassword = '".$OwnerPassword."' WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
$result = mysql_query($query) or die('Query (update of owners_table upon account edit) failed: ' . mysql_error().' and the query string was: '.$query);

// Also update associates_table if the user changed his/her OwnerUsername
if ($OwnerUsername != $_SESSION['LoggedInOwnerUsername'])
	{
	$query = "UPDATE associates_table SET OwnerUsername = '".$OwnerUsername."' WHERE OwnerUsername = '".$_SESSION['LoggedInOwnerUsername']."'";
	$result = mysql_query($query) or die('Query (update of OwnerUsername in associates_table) failed: ' . mysql_error().' and the query string was: '.$query);
	}

/* If OwnerUsername and/or OwnerPassword were changed, send an email message to the former username (as recorded in $_SESSION['LoggedInOwnerUsername']) and, if different, the new username $OwnerUsername as a record of the change of login. */

if ($OwnerUsername != $_SESSION['LoggedInOwnerUsername']) $UsernameChange = true;
if ($OwnerPassword != $PriorPassword) $PasswordChange = true;

if ($UsernameChange || $PasswordChange)
	{
	require('Mail.php');
	require('Mail/mime.php');

	$messageHTML = "<html><body><table cellspacing='10'><tr><td colspan='3' style='font-family: Arial, Helvetica, sans-serif'>Hello ".$OwnerFirstName."</td></tr>";
	$messageHTML .= "<tr><td colspan='3' style='font-family: Arial, Helvetica, sans-serif'>This is an automated confirmation of your recent change to your ";
	if ($UsernameChange && $PasswordChange) $messageHTML .= "username and password ";
	else if ($UsernameChange) $messageHTML .= "username ";
	else $messageHTML .= "password ";
	$messageHTML .= "for logging into your Abridg account.</td></tr>";
	$messageHTML .= "<tr><td colspan='3' style='font-family: Arial, Helvetica, sans-serif'>You may now log in as follows:</td></tr>";
	$messageHTML .= "<tr><th style='font-family: Arial, Helvetica, sans-serif; text-align: left;'>Account Name</th><th style='font-family: Arial, Helvetica, sans-serif; text-align: left;'>Username</th><th style='font-family: Arial, Helvetica, sans-serif; text-align: left;'>Password</th></tr>";
	$messageHTML .=	"<tr><td style='font-family: Arial, Helvetica, sans-serif'>".$OwnerLabel."</td><td style='font-family: Arial, Helvetica, sans-serif'>".$OwnerUsername."</td><td style='font-family: Arial, Helvetica, sans-serif'>".$OwnerPassword."</td></tr>";
	$messageHTML .= "<tr><td colspan='3' style='font-family: Arial, Helvetica, sans-serif'><br>Thanks for using Abridg!</td></tr>";
	$messageHTML .= "<tr><td colspan='3' style='font-family: Arial, Helvetica, sans-serif'>The Abridg Team</td></tr>";
	$messageHTML .= "</table></body></html>";

	$messageText = "Hello ".$TheName."\n\nThis is an automated confirmation of your recent change to your ";
	if ($UsernameChange && $PasswordChange) $messageText .= "username and password ";
	else if ($UsernameChange) $messageText .= "username ";
	else $messageText .= "password ";
	$messageText .= "for logging into your Abridg account.\n\n";
	$messageText .= "You may log in as follows:\n";
	$messageText .= "Account Name          Username          Password\n";
	$messageText .= $OwnerLabel."          ".$OwnerUsername."          ".$OwnerPassword."\n\n";
	$messageText .= "Thanks for using Abridg!\n\nThe Abridg Team";
		
	$sendto = $CandidateEmail;
	$crlf = "\n";
	$hdrs = array(
              'From'    => 'donotreply@abridg.com',
   	          'Subject' => 'Change of Login to Abridg',
			  'Bcc' => 'paul@abridg.com'
              );

	$mime = new Mail_mime($crlf);
	$mime->setTXTBody($messageText);
	$mime->setHTMLBody($messageHTML);
	
	//do not ever try to call these lines in reverse order
	$body = $mime->get();
	$hdrs = $mime->headers($hdrs);

	$mail =& Mail::factory('mail');
	$mail->send("$sendto", $hdrs, $body);
	}
		
// Update the values of certain session variables that were originally set in index.php when owner first logged in.
$_SESSION['LoggedInOwnerLabel'] = $OwnerLabel; // for use in the "... powered by Abridg" banner text, which gets reset by widgetslave.php.
$_SESSION['LoggedInOwnerUsername'] = $OwnerUsername;

// Display an on-screen confirmation.
?>
<table cellpadding="0" cellspacing="0" style="width: 900px; margin-top: 50px; margin-left: auto; margin-right: auto;">
<tr>
<td colspan="3" style="text-align: left;">
<p class='text' style='margin-left: 100px; margin-right: 150px; margin-top: 40px; margin-bottom: 20px;'>Any requested changes have now been recorded in your account.</p>
</td>
</tr>
<?php
if ($UsernameChange || $PasswordChange) 
	{
	echo "<tr><td colspan='3'><p class='text' style='margin-left: 100px; margin-right: 150px; margin-top: 10px; margin-bottom: 20px;'>Your new login to access your Abridg account is as follows:</p></td></tr>";
	}
else
	{
	echo "<tr><td colspan='3'><p class='text' style='margin-left: 100px; margin-top: 10px; margin-bottom: 20px;'>Your login to access your Abridg account remains as follows:</p></td></tr>";
	}
echo "<tr><td width='300'><label style='margin-left: 100px; margin-top: 10px; margin-bottom: 20px;'>Account Name</label></td><td width='200'><label>Username</label></td><td width='200'><label>Password</label></td></tr>";
echo "<tr><td><p class='text' style='margin-left: 100px; margin-top: 10px; margin-bottom: 20px;'>".$OwnerLabel."</p></td><td><p class='text' style='margin-top: 10px; margin-bottom: 20px;'>".$OwnerUsername."</p></td><td><p class='text' style='margin-top: 10px; margin-bottom: 20px;'>".$OwnerPassword."</p></td></tr>";
?>
<tr>
<td colspan="3" style="text-align: center; height: 60px;">
<form method="post" action="/index.php" style="display: inline;">
<input type="submit" class="buttonstyle"  name="galleryview" value="Media Gallery" style="text-align: center;">
</form>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<form method="post" action="/manage.php" style="display: inline;">
<input type="submit" class="buttonstyle" value="Continue Administration" style="text-align: center;">
</form> 
</td>
</tr>
</table>

<?php
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