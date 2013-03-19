<?php
/*
createowner_slave.php inserts a new row into the owners_table. Specifically, it inserts OwnerFirstName, OwnerLastName, OwnerOrganization (for organizations only), OwnerLabel, OwnerUsername, and OwnerPassword. It also assigns an OwnerID via autoincrement as well as populates automatically the OwnerDofAdmission (for when the Owner was created).
	Note that createowner_slave.php also creates a row in the associates_table. The associate pertains to media assignments to the Owner himself/herself so he/she can log into the regular web site and show people media content without having to access it through an Administrator's console. The Owner would upload and assign content to his/her own "associate account" in the same way as he would do so for any other associate i.e. via the Admin console.
	After createowner_slave.php has performed its tasks, the new owner should be directed to automatic login into his/her account (index.php) by setting the session variable $_SESSION['Authenticated'] = 'true'.
*/

// Start a session
session_start();

if ($_SESSION['ValidatedSuperAdmin'] != 'true') exit;

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

// Assign values to the session variables that are used to prepopulate the preserved values of the posted field values in createowner.php if/when that page is represented to the user with form validation errors.
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
<title>createowner Slave Script</title>
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
	$_SESSION['MsgOwnerFirstName'] = "<span class='errorphp'><br>Please use only letters (A-Z, a-z), dash (-), period (.), apostrophe ('), and space characters.<br></span>";
	$_SESSION['phpinvalidflag'] = true; 
	};

// Seek to validate $OwnerLastName
$illegalCharSet = '[~#%\^\*_\+`\|&$?=!:";<>]+'; // Exclude everything except A-Z, a-z, numbers, period, hyphen, apostrophe (single quote), slash, space, comma, period, and parentheses.
$reqdCharSet = "[A-Za-z]{1,}";  // At least one letter
if (ereg($illegalCharSet, $OwnerLastName) || !ereg($reqdCharSet, $OwnerLastName))
	{
	$_SESSION['MsgOwnerLastName'] = "<span class='errorphp'><br>Please use only letters (A-Z, a-z), dash (-), period (.), apostrophe ('), and space characters.<br></span>";
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
	$_SESSION['MsgOwnerLabel'] = "<span class='errorphp'>Please use only alphanumerics (A-Z, a-z, 0-9), dash (-), slash (/),<br>period (.), apostrophe ('), &, and space characters.<br></span>";
	$_SESSION['phpinvalidflag'] = true; 
	};

// Before validating $OwnerUsername for required and illegal characters, ensure that $OwnerUsername is unique. (owners_table defines OwnerUsername as unique, but we still need a graceful validation process.) Note that an illegal character or missing required character will override the assignment of $_SESSION['MsgOwnerUsername'], which is fine.
$query = "SELECT OwnerUsername from owners_table";
$result = mysql_query($query) or die('Query (select OwnerUsername from owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
$PreexistingOwnerUsernamesArray = array();
while ($row = mysql_fetch_assoc($result))
	{
	array_push($PreexistingOwnerUsernamesArray, $row['OwnerUsername']);
	}
if (in_array($OwnerUsername, $PreexistingOwnerUsernamesArray))
	{
	$_SESSION['MsgOwnerUsername'] = "<span class='errorphp'><br>An account already exists for this username (email address).<br></span>";
	$_SESSION['phpinvalidflag'] = true; 
	}

// Seek to validate $OwnerUsername (required field)
$reqdCharSet = '^[A-Za-z0-9_\-\.]+@[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-\.]+$';  // Simple validation from Welling/Thomson book, p125.
if (!ereg($reqdCharSet, $OwnerUsername))
	{
	$_SESSION['MsgOwnerUsername'] = '<span class="errorphp"><br>Please check the format of this email address.<br></span>';
	$_SESSION['phpinvalidflag'] = true; 
	};
	
// Seek to validate $OwnerPassword (required field)
$illegalCharSet = '[~\.\-\(\)&=!#%\^\*_@\+`\|\$:";<>?]+'; // Exclude everything except A-Z, a-z, numbers, slash, space, comma.
$reqdCharSet = "[A-Za-z0-9]{8,}";  // At least eight alphanumerics
if (ereg($illegalCharSet, $OwnerPassword) || !ereg($reqdCharSet, $OwnerPassword))
	{
	$_SESSION['MsgOwnerPassword'] = "<span class='errorphp'>Please use only alphanumerics (A-Z, a-z, 0-9). Minimum 8 characters<br></span>";
	$_SESSION['phpinvalidflag'] = true; 
	};

//Now go back to the previous page (createowner.php) and show any PHP inline validation error messages if the $_SESSION['phpinvalidflag'] has been set to true. ... otherwise, proceed to update the database with the user's form data.
if ($_SESSION['phpinvalidflag'])
	{
	?>
	<script type='text/javascript' language='javascript'>window.location = '/createowner.php';</script>
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

/* Examine owners_table to see whether $OwnerUsername (the email address of the prospective newly created Owner) already exists in owners_table. It will if another preexisting Owner had previously submitted this same email address into the  addassociate.php HTML form in order to make this person (who is now the prospective Owner) one of his/her associates.  "Abridg Table Relationships.doc" explains this clearly. */
$query = "SELECT COUNT(*) FROM owners_table WHERE Username = '".$OwnerUsername."'";
$result = mysql_query($query) or die('Query (select count * from owners_table) failed: ' . mysql_error().' and the query string was: '.$query);
$row = mysql_fetch_row($result);
$usernameAlreadyExists = ($row[0] >= 1 ? true : false);
if ($usernameAlreadyExists)
	{
	// Update row in owners_table
	$query = "UPDATE owners_table SET OwnerFirstName = '".$OwnerFirstName."', OwnerLastName = '".$OwnerLastName."', OwnerOrganization = '".$OwnerOrganization."', OwnerLabel = '".$OwnerLabel."', OwnerPassword = '".$OwnerPassword."', OwnerDofAdmission = CURDATE()"; 
	$result = mysql_query($query) or die('Query (update of owners_table) failed: ' . mysql_error().' and the query string was: '.$query);
	}
else
	{
	// Insert data into owners_table.
	$query = "INSERT INTO owners_table set OwnerFirstName = '".$OwnerFirstName."', OwnerLastName = '".$OwnerLastName."', OwnerOrganization = '".$OwnerOrganization."', OwnerLabel = '".$OwnerLabel."', OwnerUsername = '".$OwnerUsername."', OwnerPassword = '".$OwnerPassword."', OwnerDofAdmission = CURDATE()";
	$result = mysql_query($query) or die('Query (insert into owners_table) failed: ' . mysql_error().' and the query string was: '.$query);
	}

// Unset session variables that would otherwise cause unwanted prepopulation of field values upon return to the form in createowner.php.
unset($_SESSION['EntityType']);
unset($_SESSION['OwnerFirstName']);
unset($_SESSION['OwnerLastName']);
unset($_SESSION['OwnerOrganization']);
unset($_SESSION['OwnerLabel']);
unset($_SESSION['OwnerUsername']);
unset($_SESSION['OwnerPassword']);

/* Create a row (insertion into associates_table) for the account Owner's own use so he/she can assign media to his/her own account. */

//Rather than make the value of the AssociateName column in associates_table the same as the OwnerLabel column in owners_table as provided or as supplied by the account creator in the createowner.php HTML form (a reasonable choice), I instead choose to make it "My Gallery Favorites".

// Since the autoincremented value of OwnerID that was automatically generated during the INSERT into owners_table above will need to be manually inserted into the associates_table, we need to obtain that value ... using PHP function mysql_insert_id().
$TheOwnerID = mysql_insert_id();

// Insert the newly created associate for this owner into associates_table.
$query = "INSERT INTO associates_table SET OwnerID = ".$TheOwnerID.", AssociateName = 'My Gallery Favorites', OwnerUsername = '".$OwnerUsername."', AssociateDofAdmission = CURDATE()";
$result = mysql_query($query) or die('Query (insert into associates_table) failed: ' . mysql_error().' and the query string was: '.$query);


/* Update the AssociateIDs column in owners_table. */

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
$query = "UPDATE owners_table set AssociateIDs = '".$AssociateIDsNew."' WHERE OwnerID = $TheOwnerID";
$result = mysql_query($query) or die('Query (update of AssociateIDs in owners_table) failed: ' . mysql_error().' and the query string was: '.$query);
?>
	<div style="text-align: center"> <!-- This div provides centering for older browsers incl. NS4 and IE5. (See http://theodorakis.net/tablecentertest.html#intro.) Use of margin-left: auto and margin-right: auto in the style of the table itself (see below) takes care of centering in newer browsers. -->
	<form method="post" action="/index.php">
	<table cellpadding="0" cellspacing="0" style="margin-top: 50px; margin-left: auto; margin-right: auto; position: relative; left: -7px;">
	<tr>
	<td style="text-align: left;">
	<p class='text' style='margin-left: 150px; margin-right: 150px; margin-top: 40px; margin-bottom: 20px;'>Congratulations! You have successfully added an account for <?=$OwnerLabel; ?> on the Abridg web site.</p>
	<p class='text' style='margin-left: 150px; margin-right: 150px;'>Username = <?=$OwnerUsername; ?><br />Password = <?=$OwnerPassword; ?></p>
	<p class='text' style='margin-left: 150px; margin-right: 150px; margin-bottom: 60px;'>Click the button below to begin using this account. Alternatively, click <a target='_self' href='/createowner.php'>here</a> to create another owner.</p>
	</td>
	<tr>
	<td style="text-align: center;">
	<input type='submit' name='AbridgHome' class='buttonstyle' style="text-align: center;" value='Abridg Home'>
	</td>
	</tr>
	</table>
	</form>
	</div>

<?php
ob_end_flush();
?>
</body>
</html>