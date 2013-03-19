<?php
/*
signup_slave.php is the slave for the "SignUp" HTML form inside the signupbox div within index.php. In mot cases, it will insert a new row into the owners_table. However, in cases where the person endeavors to sign up with an email address that already exists in owners_table, we'll look to see if that row of owners_table also has a (non-blank) OwnerPassword.
	If we do find a non-blank OwnerPassword column for the matching email address (OwnerUsername), a registered (either fully or semiregistered) owner already exists and we certainly don't want to summarily write over his/her password. Instead, we'll present the would-be signer-upper with an on-screen message saying that an account already exists with this username. He/she can request a password reminder (which will be sent to that username/email address) if he/she set up the account originally. Otherwise, he/she will need to create a new account with a different email adddress.
	If we don't find anything in the OwnerPassword column, we'll allow the signer-upper to sign up with this email address. However, as explained in "Registration and Log In to Abridg.doc", this does enable a potentially malicious person to grab control of a person's account if that person is nonregistered and had received but failed to act upon an alert sent to him/her by a content producer. We can/will plug this security flaw by requiring all (or maybe just some -- such as in this particular situation) account holders to validate their email address (again, see "Registration and Log In to Abridg.doc").
	Specifically, signup_slave.php inserts or updates OwnerFirstName, OwnerLastName, OwnerOrganization (for organizations only), OwnerLabel, OwnerUsername, and OwnerPassword in owners_table. It also inserts a "My Gallery Favorites" row in associates_table, which pertains to media assignments to the Owner himself/herself so he/she can log into the regular web site and show people media content without having to access it through an Administrator's console. The Owner would upload and assign content to his/her own "associate account" in the same way as he would do so for any other associate i.e. via the Abridg Director/Admin console.
	Finally, signup_salve.php updates the AssociateIDs column of owners_table for the new "My Gallery Favorites" row in associates_table. It also presents an on-screen message and sends a confirmation email after a successful sign up.
	After signup_slave.php has performed its tasks, the new owner could/should be directed to automatic login into his/her account (index.php) by setting the session variable $_SESSION['Authenticated'] = 'true'.
*/

// Start a session
session_start();

// Connect to mysql
$db = mysql_connect('localhost', 'paulme6_merlyn', 'fePhaCj64mkik')
or die('Could not connect: ' . mysql_error());
mysql_select_db('paulme6_abridg') or die('Could not select database: ' . mysql_error());

ob_start(); // Used in conjunction with ob_flush() [see www.tutorialized.com/view/tutorial/PHP-redirect-page/27316], this allows me to postpone issuing output to the screen until after the header has been sent.

// Create short variable names
$VPwidthSignUp = $_POST['VPwidthSignUp']; // Passed as a hidden form field
$VPheightSignUp = $_POST['VPheightSignUp']; // Passed as a hidden form field
$EntityType = $_POST['EntityType'];
$OwnerFirstName = $_POST['OwnerFirstName'];
$OwnerLastName = $_POST['OwnerLastName'];
$OwnerOrganization = $_POST['OwnerOrganization'];
$OwnerLabel = $_POST['OwnerLabel'];
$SignUpEmail = $_POST['SignUpEmail'];
$SignUpPassword = $_POST['SignUpPassword'];

// Assign values to the session variables that are used to prepopulate the preserved values of the posted field values in createowner.php if/when that page is represented to the user with PHP form validation errors.
$_SESSION['EntityType'] = $EntityType;
$_SESSION['OwnerFirstName'] = $OwnerFirstName;
$_SESSION['OwnerLastName'] = $OwnerLastName;
$_SESSION['OwnerOrganization'] = $OwnerOrganization;
$_SESSION['OwnerLabel'] = $OwnerLabel;
$_SESSION['SignUpEmail'] = $SignUpEmail;
$_SESSION['SignUpPassword'] = $SignUpPassword;
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>signup slave Script</title>
<link href="/abridg-custom.css" rel="stylesheet" type="text/css">
</head>
<body>

<?php
/*
Begin PHP form validation. Note, however, I'm currently not performing PHP validation on SignUpEmail or SignUpPassword, using javascript validation only for those two fields. (I couldn't get the PHP validation to work for those two fields for some odd reason.)
*/

// Create a session variable for the PHP form validation flag, and initialize it to 'false' i.e. assume it's valid.
$_SESSION['phpinvalidflag'] = false;

// Create session variables to hold inline error messages, and initialize them to blank.
$_SESSION['MsgOwnerFirstName'] = null;
$_SESSION['MsgOwnerLastName'] = null;
$_SESSION['MsgOwnerOrganization'] = null;
$_SESSION['MsgOwnerLabel'] = null;
$_SESSION['MsgSignUpEmail'] = null;
$_SESSION['MsgSignUpPassword'] = null;

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

// Seek to validate $SignUpEmail (an email address)
$reqdCharSet = '^[A-Za-z0-9_\-\.]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]+$';  // Simple validation from Welling/Thomson book, p125.
if (!ereg($reqdCharSet, $SignUpEmail))
	{
	$_SESSION['MsgSignUpEmail'] = '<span class="errorphp">The format of your email address is invalid. Please provide a valid address. Example: <i>myname@gmail.com</i><br></span>';
	$_SESSION['phpinvalidflag'] = true; 
	};

// Seek to validate $nonregOwnerPassword (a password that must have at least one number and 8 characters) [courtesy: http://stackoverflow.com/questions/5848877/use-regular-expressions-to-validate-passwords]
$len = strlen($SignUpPassword); 
if ($len < 8)
	{ 
     // too short 
	$_SESSION['MsgSignUpPassword'] = '<span class="errorphp">Your password must have at least 8 characters.<br></span>';
	$_SESSION['phpinvalidflag'] = true; 
	}
elseif ( $len > 20)
	{ 
     // too long. 
	$_SESSION['MsgSignUpPassword'] = '<span class="errorphp">Your password must have no more than 20 characters.<br></span>';
	$_SESSION['phpinvalidflag'] = true; 
	}
elseif (!preg_match('#[0-9]#', $SignUpPassword))
	{ 
     // does not contain a digit 
	$_SESSION['MsgSignUpPassword'] = '<span class="errorphp">Your password must contain at least one number.<br></span>';
	$_SESSION['phpinvalidflag'] = true; 
	}
elseif (!preg_match('#[a-z]#i', $SignUpPassword))
	{ 
     // does not have a character 
	$_SESSION['MsgSignUpPassword'] = '<span class="errorphp">Your password must contain at least one letter.<br></span>';
	$_SESSION['phpinvalidflag'] = true; 
	}  

//Now go back to the previous page (createowner.php) and show any PHP inline validation error messages if the $_SESSION['phpinvalidflag'] has been set to true. ... otherwise, proceed to update the database with the user's form data.
if ($_SESSION['phpinvalidflag'])
	{
	$_SESSION['SignUpValidationError'] = 'true';
	?>
	<script type='text/javascript' language='javascript'>window.location = '/index.php';</script>
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
$SignUpEmail = htmlspecialchars($SignUpEmail, ENT_COMPAT);
$SignUpPassword = htmlspecialchars($SignUpPassword, ENT_COMPAT);

if (!get_magic_quotes_gpc())
	{
	$OwnerFirstName = addslashes($OwnerFirstName);
	$OwnerLastName = addslashes($OwnerLastName);
	$OwnerOrganization = addslashes($OwnerOrganization);
	$OwnerLabel = addslashes($OwnerLabel);
	$SignUpEmail = addslashes($SignUpEmail);
	$SignUpPassword = addslashes($SignUpPassword);
	}		

// Set viewport session variables (for a regular login via "authenticationform", these would be set in index.php instead).
$_SESSION['VPwidth'] = $VPwidthSignUp;
$_SESSION['VPheight'] = $VPheightSignUp;

/* Examine owners_table to see whether $SignUpEmail (the email address of the prospective newly created Owner) already exists in owners_table. It will if (i) another preexisting Owner had previously submitted this same email address into the  addassociate.php HTML form in order to make this person (who is now the prospective Owner) one of his/her associates.  "Abridg Table Relationships.doc" explains this clearly, or (ii) someone had previously used the 'SignUp' form in index.php to register under this same email address. */
$query = "SELECT OwnerID, OwnerPassword, COUNT(*) AS TheCount, OwnerID FROM owners_table WHERE OwnerUsername = '".$SignUpEmail."'";
$result = mysql_query($query) or die('Query (select count * from owners_table) failed: ' . mysql_error().' and the query string was: '.$query);
$row = mysql_fetch_assoc($result);
$usernameAlreadyExists = ($row['TheCount'] >= 1 ? true : false);
if ($usernameAlreadyExists)
	{
	// Does the row in owners_table corresponding to this OwnerUsername have a non-blank OwnerPassword column? If it does, then don't allow the Sign Up process to proceed because if you do, you'll be overwriting an existing fully registered owner in owners_table. 
	if (!empty($row['OwnerPassword']))
		{
		// Present an on-screen message saying an account already exists.
?>
		<div style="text-align: center"> <!-- This div provides centering for older browsers incl. NS4 and IE5. (See http://theodorakis.net/tablecentertest.html#intro.) Use of margin-left: auto and margin-right: auto in the style of the table itself (see below) takes care of centering in newer browsers. -->
		<form method="post" action="/index.php">
		<table cellpadding="0" cellspacing="0" style="margin-top: 50px; margin-left: auto; margin-right: auto; position: relative; left: -7px;">
		<tr>
		<td style="text-align: left;">
		<p class='text' style='margin-left: 150px; margin-right: 150px; margin-top: 40px; margin-bottom: 20px;'>An account with username <?=$SignUpEmail; ?> already exists on Abridg&trade;.</p>
		<p class='text' style='margin-left: 150px; margin-right: 150px;'>If you have previously registered this account but have since forgotten your password, click <a href="/passwordreminder.php">here</a> to request a reminder.</p>
		<p class='text' style='margin-left: 150px; margin-right: 150px; margin-bottom: 60px;'>Alternatively, if you entered this username in error and want to register with a different email address, click the button below to try again.</p>
		</td>
		<tr>
		<td style="text-align: center;">
		<input type='submit' class='buttonstyle' style="text-align: center;" value='Try Again'>
		</td>
		</tr>
		</table>
		</form>
		</div>
<?php
		// Unset session variables that would otherwise cause unwanted prepopulation of field values upon return to the form in createowner.php.
		unset($_SESSION['EntityType']);
		unset($_SESSION['OwnerFirstName']);
		unset($_SESSION['OwnerLastName']);
		unset($_SESSION['OwnerOrganization']);
		unset($_SESSION['OwnerLabel']);
		unset($_SESSION['SignUpEmail']);
		unset($_SESSION['SignUpPassword']);
		
		ob_flush();
?>
		</body>
		</html>
<?php
		exit;
		}
	else
		{
		// Update row in owners_table (no need to rewrite OwnerUsername)
		$query = "UPDATE owners_table SET OwnerFirstName = '".$OwnerFirstName."', OwnerLastName = '".$OwnerLastName."', OwnerOrganization = '".$OwnerOrganization."', OwnerLabel = '".$OwnerLabel."', OwnerPassword = '".$SignUpPassword."', OwnerDofAdmission = CURDATE() WHERE OwnerID = ".$row['OwnerID'];
		$result = mysql_query($query) or die('Query (update of owners_table) failed: ' . mysql_error().' and the query string was: '.$query);
		}
	}
else
	{
	// Insert data into owners_table.
	$query = "INSERT INTO owners_table set OwnerFirstName = '".$OwnerFirstName."', OwnerLastName = '".$OwnerLastName."', OwnerOrganization = '".$OwnerOrganization."', OwnerLabel = '".$OwnerLabel."', OwnerUsername = '".$SignUpEmail."', OwnerPassword = '".$SignUpPassword."', OwnerDofAdmission = CURDATE()";
	$result = mysql_query($query) or die('Query (insert into owners_table) failed: ' . mysql_error().' and the query string was: '.$query);
	}

/*	Note: When inserting a 'My Gallery Favorites' row into associates_table (which we'll do next, unless such a row already exists), we will need a value for the OwnerID column. That value will depend on whether the OwnerUsername (i.e. email address) entered into the createowner.php HTML form already existed in owners_table. If it did already exist, we'll have done an update operation on owners_table above and the value of the OwnerID column will have been SELECT'ed as $row['TheCount']. If, on the other hand, it didn't previously exist, we'll have done an insert operation into owners_table above and the value of the OwnerID column will be accessible via the mysql_insert() function. Use a ternary operator accordingly: */
$TheOwnerID = ($usernameAlreadyExists == true ? $row['OwnerID'] : mysql_insert_id());

/* Before creating a row in associates_table to be this Owner's own associate (i.e. AssociateName = "My Gallery Favorites"), check whether such a row already exists. (I actually don't think it could preexist. If the signer-upper were an Owner who had signed up some time ago (and therefore would have already a "My Gallery Favorites" row in associates_table) but forgot he/she had done so, he/she would have an existing password in owners_table.OwnerPassword, which would have been detected in the logic (above) and prevented this stage of the code from executing.) If the row does already exist, don't bother creating one. */
$query = "SELECT COUNT(*) As TheCount FROM associates_table WHERE OwnerUsername = '".$SignUpEmail."' AND OwnerID = ".$TheOwnerID;
$result = mysql_query($query) or die('Query (select count * from associates_table) failed: ' . mysql_error().' and the query string was: '.$query);
$row = mysql_fetch_assoc($result);
$associateRowAlreadyExists = ($row['TheCount'] >= 1 ? true : false);

/* Iff $associateRowAlreadyExists is not true, create a 'My Gallery Favorites' row (insertion into associates_table) for the account Owner's own use so he/she can assign media to his/her own account.
	Note: Rather than make the value of the AssociateName column in associates_table the same as the OwnerLabel column in owners_table as provided or as supplied by the account creator in the createowner.php HTML form (a reasonable choice), I instead choose to make it 'My Gallery Favorites'. */
if (!$associateRowAlreadyExists)
	{
	$query = "INSERT INTO associates_table SET OwnerID = ".$TheOwnerID.", AssociateName = 'My Gallery Favorites', OwnerUsername = '".$SignUpEmail."', AssociateDofAdmission = CURDATE()";
	$result = mysql_query($query) or die('Query (insert into associates_table) failed: ' . mysql_error().' and the query string was: '.$query);

	/* Also, update the AssociateIDs column in owners_table to reflect the newly inserted row within associates_table. */

	// Since the autoincremented value of AssociateID that was automatically generated during the INSERT into associates_table above will need to be manually inserted into the owners_table, we need to obtain that value ... using PHP function mysql_insert_id().
	$TheAssociateID = mysql_insert_id();

	// First obtain (select) the existing value of the AssociateIDs for OwnerID == $TheOwnerID
	$query = "SELECT AssociateIDs FROM owners_table WHERE OwnerID = ".$TheOwnerID;
	$result = mysql_query($query) or die('Query (select AssociateIDs from owners_table has failed: ' . mysql_error());
	$row = mysql_fetch_assoc($result);
	$AssociateIDsOld = $row['AssociateIDs'];

	// Now we can set the updated value of AssociateIDs
	if (empty($AssociateIDsOld))
		{
		$AssociateIDsNew = $TheAssociateID; 
		}
	else
		{
		$AssociateIDsNew = $AssociateIDsOld.','.$TheAssociateID;
		}

	// Finally, update the AssociateIDs column with the new value of AssociateIDs for this particular Owner account.
	$query = "UPDATE owners_table set AssociateIDs = '".$AssociateIDsNew."' WHERE OwnerID = ".$TheOwnerID;
	$result = mysql_query($query) or die('Query (update of AssociateIDs in owners_table) failed: ' . mysql_error().' and the query string was: '.$query);

	// If we got this far, we can enable automatic login into the newly signed up owner's account when he/she clicks the 'Continue' button above. Do this by POST'ing back to index.php values for OwnerUsername and OwnerPassword as hidden fields in the form submission. They'll be processed just as if the user had logged in manually via the name="authenticationform" form in index.php. */
	}
?>
	<div style="text-align: center"> <!-- This div provides centering for older browsers incl. NS4 and IE5. (See http://theodorakis.net/tablecentertest.html#intro.) Use of margin-left: auto and margin-right: auto in the style of the table itself (see below) takes care of centering in newer browsers. -->
	<form method="post" action="/index.php">
	<table cellpadding="0" cellspacing="0" style="margin-top: 50px; margin-left: auto; margin-right: auto; position: relative; left: -7px;">
	<tr>
	<td style="text-align: left;">
	<p class='text' style='margin-left: 150px; margin-right: 150px; margin-top: 40px; margin-bottom: 20px;'>Congratulations! <?=$OwnerLabel; ?> is now signed up with Abridg&trade;.</p>
	<p class='text' style='margin-left: 150px; margin-right: 150px;'>Username = <kbd><?=$SignUpEmail; ?></kbd><br />Password = <kbd><?=$SignUpPassword; ?></kbd></p>
	<p class='text' style='margin-left: 150px; margin-right: 150px;'>We&rsquo;ve also sent an email confirmation to <?=$SignUpEmail; ?>.</p>
	<p class='text' style='margin-left: 150px; margin-right: 150px; margin-bottom: 60px;'>Click the button below to begin using this account.</p>
	</td>
	<tr>
	<td style="text-align: center;">
	<input type="hidden" name="OwnerUsername" value="<?=$SignUpEmail; ?>">
	<input type="hidden" name="OwnerPassword" value="<?=$SignUpPassword; ?>">
	<input type='submit' name='AutoLogInNow' class='buttonstyle' style="text-align: center;" value='Continue'>
	</td>
	</tr>
	</table>
	</form>
	</div>

<?php
// Unset session variables that would otherwise cause unwanted prepopulation of field values upon return to the form in createowner.php.
unset($_SESSION['EntityType']);
unset($_SESSION['OwnerFirstName']);
unset($_SESSION['OwnerLastName']);
unset($_SESSION['OwnerOrganization']);
unset($_SESSION['OwnerLabel']);
unset($_SESSION['SignUpEmail']);
unset($_SESSION['SignUpPassword']);

/* 
Send the newly signed up Owner an email to confirm his/her username/password. Note: in the future, we'll probably want to require that a user verify his/her email address before a login will be accepted. This would entail an additional boolean "Verified" field in owners_table.
*/

require_once('Mail.php');
require_once('Mail/mime.php');

$messageHTML = "<html><body><table cellspacing='10'><tr><td style='font-family: Arial, Helvetica, sans-serif'>Hello ".$OwnerFirstName."</td></tr>";
$messageHTML .= "<tr><td style='font-family: Arial, Helvetica, sans-serif'>This is to confirm that you&rsquo;re now signed up with Abridg. Your username is <kbd>".$SignUpEmail."</kbd>, and your password is <kbd>".$SignUpPassword."</kbd>.</td></tr>";
$messageHTML .= "<tr><td style='font-family: Arial, Helvetica, sans-serif'>Abridg allows you to share videos, images, audio files, and documents in a uniquely personal and private manner. We think you'll be a fan!</td></tr>";
$messageHTML .= "<tr><td style='font-family: Arial, Helvetica, sans-serif'>Sincerely</td></tr>";
$messageHTML .= "<tr><td style='font-family: Arial, Helvetica, sans-serif'>Abridg&trade; | Connection 2.0&trade;</body></html>";

$messageText = "Hello ".$OwnerFirstName."\n\nThis is to confirm that you're now signed up with Abridg. Your username is '".$SignUpEmail."', and your password is '".$SignUpPassword."'.\n\n";
$messageText .= "Abridg allows you to share videos, images, audio files, and documents in a uniquely personal and private manner. We think you'll be a fan! You may log in any time to edit your trainer profile and add upcoming training events. Simply visit <a href='http://www.mediationtrainings.org'>mediationtrainings.org</a> and click on 'Registry' in the main menu.";
$messageText .= "Sincerely\n
Abridg(tm) | Connection 2.0(tm)";

$sendto = $SignUpEmail;
$crlf = "\n";
$hdrs = array(
	              'From'    => 'Abridg <donotreply@abridg.com>',
    	          'Subject' => 'Abridg Username/Password Confirmation',
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

ob_end_flush();
?>
</body>
</html>