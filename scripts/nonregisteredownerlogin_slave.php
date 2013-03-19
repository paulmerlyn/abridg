<?php
/*
nonregisteredownerlogin_slave.php is a slave to the nonregisteredowner HTML form in index.php. That form (basically, a username/password form with an OwnerID as a hidden field) is presented to the user iff index.php detects a crypto-string (my term for a query string) of form III-XXXXXXXX where III is the OwnerID of the associate who is now trying to log in as a non-registered Owner in order to view content after having received an email alert as an associate of a Content Producer; XXXXXXXX is the first eight characters of a sha1() hash upon the associate's OwnerUsername. (Note: to thwart easy use of a rainbow table, OwnerUsername is salted by prepending the sum of 1 + OwnerID to it before running it through the sha1 algorithm.)
	This slave script (i) Looks to see whether the POST'ed nonregOwnerUsername differs from the already existing value of OwnerUsername in the owners_table for the POST'ed OwnerID i.e. whether the user has typed in a different email address; (ii) if it does differ, replace (i.e. update) the existing values of OwnerUsername in both the owners_table (the row corresponding to the POST'ed OwnerID value) and the associates_table (the row corresponding to the nonregistered Owner's "My Gallery Favorites") with the newly POST'ed nonregOwnerUsername; and (iii) update the OwnerPassword column of the owners_table with the POST'ed value; (iv) create session variables that act as pseudo-POST-ed values for handling by index.php when that page is reloaded.
	The login process for a nonregistered Owner is really a process by which he/she becomes registered by virtue of choosing a password. All the while he/she remains non-registered, his/her row in owners_table contains only an OwnerUsername (i.e. the email address provided by the Owner who added this person as a friend) and, of course, an OwnerID key and default values for OwnerDofAdmission, OwnerLogin, and AlertType.
	After choosing a password, the user can then be a registered Owner. However, in order to have the full power of adding his/her own friends (associates), uploading and assigning content, etc., he/she will need to later supply an OwnerFirstName, OwnerLastName, OwnerLabel, and (maybe) OwnerOrganization.
*/

session_start();

ob_start();

// Connect to DB (a connection is necessary for mysql_real_escape_string below)
$db = mysql_connect('localhost', 'paulme6_merlyn', 'fePhaCj64mkik')
or die('Could not connect: ' . mysql_error());
mysql_select_db('paulme6_abridg') or die('Could not connect to the abridg database: ' . mysql_error());

// Create short variables for POST'ed values from the non-registered Owner
$VPwidthNonregOwner = $_POST['VPwidthNonregOwner'];
$VPheightNonregOwner = $_POST['VPheightNonregOwner'];
$nonregOwnerID = $_POST['nonregOwnerID'];
$nonregOwnerUsername = $_POST['nonregOwnerUsername'];
$nonregOwnerPassword = $_POST['nonregOwnerPassword'];
$cryptoString = $_POST['cryptoString'];

/* Perform server-side form validation */

// Create a session variable for the PHP form validation flag, and initialize it to 'false' i.e. assume it's valid.
$_SESSION['phpinvalidflag'] = false;

// Create session variables to hold inline error messages, and initialize them to blank.
$_SESSION['MsgNonRegOwnerUsername'] = null;
$_SESSION['MsgNonRegOwnerPassword'] = null;

// Seek to validate $nonregOwnerUsername (an email address)
$reqdCharSet = '^[A-Za-z0-9_\-\.]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]+$';  // Simple validation from Welling/Thomson book, p125.
if (!ereg($reqdCharSet, $nonregOwnerUsername))
	{
	$_SESSION['MsgNonRegOwnerUsername'] = '<span class="errorphp">The format of your email address is invalid. Please provide a valid address. Example: <i>myname@gmail.com</i><br></span>';
	$_SESSION['phpinvalidflag'] = true; 
	};

// Seek to validate $nonregOwnerPassword (a password that must have at least one number and 8 characters) [courtesy: http://stackoverflow.com/questions/5848877/use-regular-expressions-to-validate-passwords]
$len = strlen($nonregOwnerPassword); 
if ($len < 8)
	{ 
     // too short 
	$_SESSION['MsgNonRegOwnerPassword'] = '<span class="errorphp">Your password must have at least 8 characters.<br></span>';
	$_SESSION['phpinvalidflag'] = true; 
	}
elseif ( $len > 20)
	{ 
     // too long. 
	$_SESSION['MsgNonRegOwnerPassword'] = '<span class="errorphp">Your password must have no more than 20 characters.<br></span>';
	$_SESSION['phpinvalidflag'] = true; 
	}
elseif (!preg_match('#[0-9]#', $nonregOwnerPassword))
	{ 
     // does not contain a digit 
	$_SESSION['MsgNonRegOwnerPassword'] = '<span class="errorphp">Your password must contain at least one number.<br></span>';
	$_SESSION['phpinvalidflag'] = true; 
	}
elseif (!preg_match('#[a-z]#i', $nonregOwnerPassword))
	{ 
     // does not have a character 
	$_SESSION['MsgNonRegOwnerPassword'] = '<span class="errorphp">Your password must contain at least one letter.<br></span>';
	$_SESSION['phpinvalidflag'] = true; 
	}  

//Now go back to the previous page (index.php) and show any PHP inline validation error messages if the $_SESSION['phpinvalidflag'] has been set to true. ... otherwise, proceed to update the database with the user's form data.
if ($_SESSION['phpinvalidflag'])
	{
	?>
	<script type='text/javascript' language='javascript'>window.location = '/index.php?<?=$cryptoString; ?>';</script>
	<noscript>
	<?php
	if (isset($_SERVER['HTTP_REFERER']))
		header("Location: /index.php?$cryptoString");
	?>
	</noscript>
	</body>
	</html>
	<?php
	exit;
	}

/* Prevent cross-site scripting via htmlspecialchars on these user-entry form field */
$nonregOwnerUsername = htmlspecialchars($nonregOwnerUsername, ENT_COMPAT);
$nonregOwnerPassword = htmlspecialchars($nonregOwnerPassword, ENT_COMPAT);

if (!get_magic_quotes_gpc())
	{
	$nonregOwnerUsername = addslashes($nonregOwnerUsername);
	$nonregOwnerPassword = addslashes($nonregOwnerPassword);
	}		

// Set viewport session variables (for a regular login via "authenticationform", these would be set in index.php instead).
$_SESSION['VPwidth'] = $VPwidthNonregOwner;
$_SESSION['VPheight'] = $VPheightNonregOwner;

// Retrieve (select) the existing value of the OwnerUsername column in owners_table for the $nonregOwnerID. (We know the nonregOwnerID value will match an actual OwnerID because that existence of a match was established in index.php before the user was presented with the nonregistered Owner HTML login form.)
$query = "SELECT OwnerUsername FROM owners_table WHERE OwnerID = ".$nonregOwnerID;
$result = mysql_query($query) or die('Query (select OwnerUsername from owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
$row = mysql_fetch_assoc($result);

// See whether the user typed in an email address that differs from the value of the OwnerUsername column. If it's different, we'll need to update the OwnerUsername column in both owners_table and associates_table.
if ($nonregOwnerUsername != $row['OwnerUsername'])
	{
	// Update (i.e. replace with the new email address) the OwnerUsername column for the nonregistered owner's row(s) (there would be more than one such row if more than one registered owner has independently added this person as an associate) in associates_table.
	$query = "UPDATE associates_table SET OwnerUsername = '".$nonregOwnerUsername."' WHERE OwnerUsername = '".$row['OwnerUsername']."'";
	$result = mysql_query($query) or die('Query (update of OwnerUsername in associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	}

/* Update OwnerUsername and OwnerPassword columns of owners_table, using POST'ed values = $nonregOwnerUsername and  $nonregOwnerPassword */
$query = "UPDATE owners_table SET OwnerUsername = '".$nonregOwnerUsername."', OwnerPassword = '".$nonregOwnerPassword."' WHERE OwnerID = ".$nonregOwnerID;
$result = mysql_query($query) or die('Query (update of OwnerUsername and OwnerPassword in owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);

/* Insert AssociateName ( = 'My Gallery Favorites'), OwnerUsername ( = $nonregOwnerUsername), OwnerID ( = $nonregOwnerID), and AssociateDofAdmission ( = CURDATE()) as a new row in associates_table. (Code reused from addassociate_slave.php) */
$query = "INSERT INTO associates_table SET AssociateName = 'My Gallery Favorites', OwnerUsername = '".$nonregOwnerUsername."', OwnerID = ".$nonregOwnerID.", AssociateDofAdmission = CURDATE()";
$result = mysql_query($query) or die('Query (insert of AssociatesName, OwnerUsername, OwnerID, and AssociateDofAdmission in associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);

/* Update the AssociateIDs column in owners_table for the newly created associate's AssociateID. Note that it will initially be empty because, since this owner is non-registered, he/she can't possibly have added any associates yet because only registered Owners have that privilege. Also, update the value of OwnerDofAdmission in owners_table. (Code adapted from addassociate_slave.php) */
// Since the autoincremented value of AssociateID that was automatically generated during the INSERT into associates_table above will need to be manually inserted into the owners_table, we need to obtain that value ... using PHP function mysql_insert_id().
$TheAssociateID = mysql_insert_id();

// Update the AssociateIDs column with this value of the AssociateID for this particular Owner account. (Also update OwnerDofAdmission)
$query = "UPDATE owners_table set AssociateIDs = '".$TheAssociateID."', OwnerDofAdmission = CURDATE() WHERE OwnerID = ".$nonregOwnerID;
$result = mysql_query($query) or die('Query (update of AssociateIDs in owners_table) failed: ' . mysql_error().' and the query string was: '.$query);

/* Set session variables to act as pseudo-POST form submissions, as if the user had manually submitted them to index.php via a conventional login form. They'll then be processed by the rest of the code in index.php. */
$_SESSION['NonregisteredOwnerUsernameViaAlert'] = $nonregOwnerUsername;
$_SESSION['NonregisteredOwnerPasswordViaAlert'] = $nonregOwnerPassword;
$_SESSION['NonregisteredOwnerViaAlert'] = 'true'; // This flag is set and used in index.php to distinguish whether to assign $OwnerUsername and $OwnerPassword in index.php to true POST-ed form submissions or whether to assign them to these pseudo-POST session variables.

/* Reload index.php (with the $_SESSION['NonegisteredOwnerUsernameViaAlert'] and $_SESSION['NonregisteredOwnerPasswordViaAlert'] serving as pseudo-POST-ed form submissions) */
header("Location: /index.php");
ob_flush();

exit;
?>