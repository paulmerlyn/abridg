<?php
/*
addassociate_slave.php does just three things. (1) It inserts the OwnerUsername (email address of an associate as provided by the web form in addassociate.php) as a new row in owners_table. At this point -- until the associate has visited the Abridg site and provided his/her chosen password, FirstName, LastName, OrganizationName (if not a private individual), and OwnerLabel -- the remaining columns for this "Owner-in-Waiting" will be blank. (2) It inserts AssociateName, OwnerUsername, and OwnerID (i.e. $_SESSION['LoggedInOwnerID']) into associates_table, using the AssociateName and OwnerUsername provided by the Owner via the web form in addassociate.php. (3) It also updates the AssociateIDs column of owners_table for the associate's AssociateID value just inserted in step (1).
	Note: We only need to perform step (1) iff the Username (email address) doesn't already exist in the owners_table. If it does exist, then this Owner's new associate already has an Owner account (perhaps set up by himself/herself via createowner.php or perhaps set up by a different Owner's decision to make this person an associate).
	Note also that the value of the OwnerID to be inserted into the associates_table is determined and set within addassociate.php as a session variable $_SESSION['LoggedInOwnerID'].
*/

// Start a session
session_start();

if ($_SESSION['ValidatedAdmin'] != 'true') exit;

ob_start(); // Used in conjunction with ob_flush() [see www.tutorialized.com/view/tutorial/PHP-redirect-page/27316], this allows me to postpone issuing output to the screen until after the header has been sent.

// Create short variable names
$AssociateName = $_POST['AssociateName'];
$OwnerUsername = $_POST['OwnerUsername'];

// Assign values to the session variables that are used to prepopulate the preserved values of the posted field values in addassociate.php if/when that page is represented to the user with form validation errors.
$_SESSION['AssociateName'] = $AssociateName;
$_SESSION['OwnerUsernameValidn'] = $OwnerUsername; // Not to be confused with $_SESSION['OwnerUsername'] as used by index.php

// Connect to mysql
$db = mysql_connect('localhost', 'paulme6_merlyn', 'fePhaCj64mkik')
or die('Could not connect: ' . mysql_error());
mysql_select_db('paulme6_abridg') or die('Could not select database: ' . mysql_error());
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>addaccount Slave Script</title>
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
$_SESSION['MsgAssociateName'] = null;
$_SESSION['MsgOwnerUsername'] = null;

// Before validating $AssociateName for required and illegal characters, ensure that $AssociateName is unique for this particular Owner. (It makes no sense to allow Owner Tom to have two friends, both with the same AssociateName "Jack Smith".) Note that an illegal character or missing required character will override the assignment of $_SESSION['MsgAssociateName'], which is fine.
$query = "SELECT AssociateName FROM associates_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
$result = mysql_query($query) or die('Query (select AssociateName from associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
$PreexistingAssociateNamesArray = array();
while ($row = mysql_fetch_assoc($result))
	{
	array_push($PreexistingAssociateNamesArray, $row['AssociateName']);
	}
if (in_array($AssociateName, $PreexistingAssociateNamesArray))
	{
	$_SESSION['MsgAssociateName'] = "<span class='errorphp'><br>You&rsquo;ve already used this name for one of your existing friends. Please choose another name.<br></span>";
	$_SESSION['phpinvalidflag'] = true; 
	}

// Seek to validate $AssociateName (required field i.e. must not be empty)
$illegalCharSet = '[~#%\^\*_\+`\|:";<>]+'; // Exclude everything except A-Z, a-z, numbers, period, hyphen, apostrophe (single quote), $, &, ?, =, !, slash, space, comma, period, and parentheses.
$reqdCharSet = "[A-Za-z]{1,}";  // At least one letter
if (ereg($illegalCharSet, $AssociateName) || !ereg($reqdCharSet, $AssociateName))
	{
	$_SESSION['MsgAssociateName'] = "<span class='errorphp'>Please use only alphanumerics (A-Z, a-z, 0-9), dash (-), slash (/), period (.),<br>apostrophe ('), &, and space characters.<br></span>";
	$_SESSION['phpinvalidflag'] = true; 
	};

/* Before validating $OwnerUsername for required and illegal characters, examine whether $OwnerUsername already exists in owners_table, and whether (if it does already exist) it's an associate of the account Owner. If the $OwnerUsername (email address) already exists in owners_table, that means (A) this Owner has already named this person as an associate (and thereby created an Owner account for him/her), (B) another Owner has already named this person as an associate (and thereby created an Owner account for him/her); or (C) the person himself/herself has independently created his/her own account via createowner.php. */
// If (A) then issue a validation error message to point out the duplication.
// If (B) or (C) then there'll be no need to create a new row in owners_table for this associate because he/she will already exist there. Ensure that step gets duly skipped by setting control flag $PreexistingOwner = true.
// Note that an illegal character or missing required character will override the assignment of $_SESSION['MsgAssociateName'], which is fine.
// First see whether the $OwnerUsername already exists in owners_table.
$PreexistingOwner = false; // Initialize flag
$query = "SELECT OwnerUsername FROM owners_table";
$result = mysql_query($query) or die('Query (select OwnerUsername from owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
$PreexistingOwnerUsernamesArray = array();
while ($row = mysql_fetch_assoc($result))
	{
	array_push($PreexistingOwnerUsernamesArray, $row['OwnerUsername']);
	}
if (in_array($OwnerUsername, $PreexistingOwnerUsernamesArray))
	{
	$PreexistingOwner = true;
	// Now that we know the $OwnerUsername is preexisting in owners_table, determine whether this preexisting owner is an associate of the Owner who is attempting to add an associate. Do this by (i) obtaining all the AssociateID values (from the AssociateIDs column in owners_table) for this Owner, then (ii) examine whether OwnerUsername column in associates_table matches $OwnerUsername for each AssociateID value.
	// First, step (i), obtain all the AssociateID values (from the AssociateIDs column in owners_table) for this Owner.
	$query = "SELECT AssociateIDs FROM owners_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
	$result = mysql_query($query) or die('Query (select AssociateIDs from owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	$row = mysql_fetch_assoc($result);
	$AssociateIDsArray = explode(',', $row['AssociateIDs']); // Convert the column's string value into an array to ensure valid value searching (e.g. don't make the mistake of a false positive by looking for an AssociateID of, say, 3 and mistakenly "finding" it as part of, say, an AssociateID of 13 or 23.
	// Second, step (ii), examine whether the OwnerUsername column in associates_table matches $OwnerUsername for each AssociateID value (now stored in array $AssociateIDsArray).
	foreach ($AssociateIDsArray as $theAssociateID)
		{
		$query = "SELECT OwnerUsername FROM associates_table WHERE AssociateID = ".$theAssociateID;
		$result = mysql_query($query) or die('Query (select OwnerUsername from associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
		$row = mysql_fetch_assoc($result);
		if ($row['OwnerUsername'] == $OwnerUsername) // The Owner already has an associate who has an OwnerUsername == $OwnerUsername. Issue a duplication error message.
			{
			$_SESSION['MsgOwnerUsername'] = "<span class='errorphp'>This email address (username) is already in use for one of your existing friends.<br>Each of your friends must have a different username.<br></span>";
			$_SESSION['phpinvalidflag'] = true;
			break; // No point in continuing to loop through the foreach loop.
			}
		}
	}

// Seek to validate $OwnerUsername
$reqdCharSet = '^[A-Za-z0-9_\-\.]+@[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-\.]+$';  // Simple validation from Welling/Thomson book, p125.
if (!ereg($reqdCharSet, $OwnerUsername))
	{
	$_SESSION['MsgOwnerUsername'] = '<span class="errorphp">Please check the format of this email address.<br></span>';
	$_SESSION['phpinvalidflag'] = true; 
	};

//Now go back to the previous page (addassociate.php) and show any PHP inline validation error messages if the $_SESSION['phpinvalidflag'] has been set to true. ... otherwise, proceed to update the database with the user's form data.
if ($_SESSION['phpinvalidflag'])
	{
	?>
	<script type='text/javascript' language='javascript'>window.location = '/addassociate.php';</script>
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
$AssociateName = htmlspecialchars($AssociateName, ENT_COMPAT);
$OwnerUsername = htmlspecialchars($OwnerUsername, ENT_COMPAT);

if (!get_magic_quotes_gpc())
	{
	$AssociateName = addslashes($AssociateName);
	$OwnerUsername = addslashes($OwnerUsername);
	}		

// If $PreexistingOwner is false, insert OwnerEmail in owners_table. Otherwise, skip this step.
if ($PreexistingOwner == false)
	{
	$query = "INSERT INTO owners_table SET OwnerUsername = '".$OwnerUsername."'";
	$result = mysql_query($query) or die('Query (insert OwnerUsername into owners_table) failed: ' . mysql_error().' and the query string was: '.$query);
	}
	
/* Insert AssociateName and OwnerUsername and OwnerID in associates_table. */
$query = "INSERT INTO associates_table set AssociateName = '".$AssociateName."', OwnerUsername = '".$OwnerUsername."', OwnerID = ".$_SESSION['LoggedInOwnerID'].", AssociateDofAdmission = CURDATE()";
$result = mysql_query($query) or die('Query (insert into associates_table) failed: ' . mysql_error().' and the query string was: '.$query);

// Update the AssociateIDs column in owners_table for the Owner to include the newly created associate's AssociateID.

// Since the autoincremented value of AssociateID that was automatically generated during the INSERT into associates_table above will need to be manually inserted into the owners_table, we need to obtain that value ... using PHP function mysql_insert_id().
$TheAssociateID = mysql_insert_id();

// First obtain (select) the existing value of the AssociateIDs for OwnerID == $_SESSION['LoggedInOwnerID']
$query = "SELECT AssociateIDs FROM owners_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
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
$query = "UPDATE owners_table set AssociateIDs = '".$AssociateIDsNew."' WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
$result = mysql_query($query) or die('Query (update of AssociateIDs in owners_table) failed: ' . mysql_error().' and the query string was: '.$query);

// Unset session variables that would otherwise cause unwanted prepopulation of field values upon return to the form in addassociate.php.
unset($_SESSION['AssociateName']);
unset($_SESSION['LoggedInOwnerUsername']);
unset($_SESSION['OwnerUsernameValidn']);

// Noting that addassociate_slave sometimes hands off (via a hyperlink -- coded in HTML below) to assign.php, it's important to set $_SESSION['AssociateID'], which holds the AssociateID of the newly created Account Holder. We obtained this value (just inserted via autoincrement into the associates_table) using mysql_insertid(). The session variable is then used by assign.php's slave script (i.e. assign_slave.php) (unless it's subsequently reset to a different AssociateID after the user selects a different account holder for assignment) to designate which row of the associates_table should have modifications to its AuthorizedFileIDs column as part of the assignment process.
	$_SESSION['AssociateID'] = $TheAssociateID;
	$_SESSION['AssociateSelected'] = true; // This session variable is set to true because it needs to be true to ensure that the 'Step 2: Assign Media Files' half of the 'AssignMediaFilesToAssociate' screen will be display'ed if addacount_slave.php does indeed hand off to assign.php.
?>
	<div style="text-align: center"> <!-- This div provides centering for older browsers incl. NS4 and IE5. (See http://theodorakis.net/tablecentertest.html#intro.) Use of margin-left: auto and margin-right: auto in the style of the table itself (see below) takes care of centering in newer browsers. -->
	<form method="post" action="/assign.php">
	<table cellpadding="0" cellspacing="0" style="margin-top: 50px; margin-left: auto; margin-right: auto; position: relative; left: -7px;">
	<tr>
	<td style="text-align: left;">
	<p class='text' style='margin-left: 150px; margin-right: 150px; margin-top: 40px; margin-bottom: 20px;'>Congratulations! You have successfully added friend <?=$AssociateName; ?>.</p>
	<p class='text' style='margin-left: 150px; margin-right: 150px; margin-bottom: 60px;'>Click the button below to allow <?=$AssociateName; ?> to access specific media. Alternatively, click <a target='_self' href='/addassociate.php'>here</a> to add another friend. Or click <a href='/index.php'>here</a> to visit the Abridg home page.</p>
	</td>
	<tr>
	<td style="text-align: center;">
	<input type='submit' name='Assign' class='buttonstyle' style="text-align: center;" value='Assign Access'>
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