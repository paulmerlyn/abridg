<?php
/*
editassociate_slave.php is the slave script for editassociate.php. It updates AssociateName in the row in the associates_table corresponding to the AssociateID identified by $_SESSION['EditAssociate'].
*/

// Start a session
session_start();

if ($_SESSION['ValidatedAdmin'] != 'true') exit;

ob_start(); // Used in conjunction with ob_flush() [see www.tutorialized.com/view/tutorial/PHP-redirect-page/27316], this allows me to postpone issuing output to the screen until after the header has been sent.

// Create short variable names
$AssociateName = $_POST['AssociateName'];

// Connect to mysql
$db = mysql_connect('localhost', 'paulme6_merlyn', 'fePhaCj64mkik')
or die('Could not connect: ' . mysql_error());
mysql_select_db('paulme6_abridg') or die('Could not select database: ' . mysql_error());
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>editassociate Slave Script</title>
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

// Before validating the (potentially newly proposed) $AssociateName for required and illegal characters, ensure that the Administrator-supplied value for $AssociateName is unique i.e. is not a duplicate of any other values of AssociateName in associates_table where OwnerID == $_SESSION['LoggedInOwnerID'] (whose value was set when the Administrator logged in, either in index.php, upload.php, assign.php, or addassociate.php). Note that an illegal character or missing required character will override the assignment of $_SESSION['MsgAssociateName'], which is fine.
$query = "SELECT AssociateName from associates_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID']." AND AssociateID != ".$_SESSION['EditAssociate']; // Gather up all values in the AssociateName that have the Owner's OwnerID (stored in $_SESSION['LoggedInOwnerID']) except omit the AssociateName in the row whose AssociateID is the account being edited. This intentional omission ensures that, in searching for any duplicate AssociateName values, we don't inadvertently compare the newly propsed (i.e. edited value of) $AssociateName with its preexisting value. (It would be fine if the two were the same -- in other words, the Administrator were not trying to change the value of the AssociateName.)
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
	$_SESSION['MsgAssociateName'] = "<span class='errorphp'><br />Please use only alphanumerics (A-Z, a-z, 0-9), dash (-), slash (/), period (.),<br>apostrophe ('), &, and space characters.<br></span>";
	$_SESSION['phpinvalidflag'] = true; 
	};

//Now go back to the previous page (editassociate.php) and show any PHP inline validation error messages if the $_SESSION['phpinvalidflag'] has been set to true. ... otherwise, proceed to update the database with the user's form data.
if ($_SESSION['phpinvalidflag'])
	{
	?>
	<script type='text/javascript' language='javascript'>window.location = '/editassociate.php';</script>
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

if (!get_magic_quotes_gpc())
	{
	$AssociateName = addslashes($AssociateName);
	}		

// Update data in the associates_table for AssociateID = $_SESSION['EditAssociate'].
$query = "UPDATE associates_table set AssociateName = '".$AssociateName."' WHERE AssociateID = ".$_SESSION['EditAssociate'];
$result = mysql_query($query) or die('Query (update AssociateName in associates_table) failed: ' . mysql_error().' and the query string was: '.$query);
?>
<div style="text-align: center"> <!-- This div provides centering for older browsers incl. NS4 and IE5. (See http://theodorakis.net/tablecentertest.html#intro.) Use of margin-left: auto and margin-right: auto in the style of the table itself (see below) takes care of centering in newer browsers. -->
<form method="post" action="/assign.php">
<table cellpadding="0" cellspacing="0" style="margin-top: 50px; margin-left: auto; margin-right: auto; position: relative; left: -7px;">
<tr>
<td style="text-align: left;">
<p class='text' style='margin-left: 150px; margin-right: 150px; margin-top: 40px; margin-bottom: 20px;'>Congratulations! You have updated your friend&rsquo;s name to <?=$AssociateName; ?>.</p>
<p class='text' style='margin-left: 150px; margin-right: 150px; margin-bottom: 60px;'>Click the button below to assign access to specific media for this account. Alternatively, click <a target='_self' href='/upload.php'>here</a> to upload media. Or click <a href='/index.php'>here</a> to visit the Abridg home page.</p>
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