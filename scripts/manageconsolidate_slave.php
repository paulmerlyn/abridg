<?php
/*
manageconsolidate_slave.php is the slave for the "consolidatediv" div form inside manage.php by which an owner can consolidate (merge) two accounts (with separate logins) into a single account with the username/password of whichever account he/she designates as primary.
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
$UsernameAccount1 = $_POST['UsernameAccount1'];
$PasswordAccount1 = $_POST['PasswordAccount1'];
$UsernameAccount2 = $_POST['UsernameAccount2'];
$PasswordAccount2 = $_POST['PasswordAccount2'];
$PrimaryAccount = $_POST['PrimaryAccount'];

// Assign values to the session variables that are used to prepopulate the preserved values of the posted field values in createowner.php if/when that page is re-presented to the user with PHP form validation errors.
$_SESSION['UsernameAccount1'] = $UsernameAccount1;
$_SESSION['PasswordAccount1'] = $PasswordAccount1;
$_SESSION['UsernameAccount2'] = $UsernameAccount2;
$_SESSION['PasswordAccount2'] = $PasswordAccount2;
$_SESSION['PrimaryAccount'] = $PrimaryAccount;
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>manageconsolidate_slave Script</title>
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
$_SESSION['MsgAcct1Login'] = null;
$_SESSION['MsgAcct2Login'] = null;
$_SESSION['MsgPrimaryAccount'] = null;

// Seek to validate whether the username/password pairs entered for Account #1 and #2 in the consolidatediv div in manage.php are legitimate logins
$query = "SELECT count(*) AS TheCount FROM owners_table WHERE OwnerUsername = '".$UsernameAccount1."' AND OwnerPassword = '".$PasswordAccount1."'";
$result = mysql_query($query) or die('Query (select of count(*) for a matching OwnerUsername and OwnerPassword re Account #1 from owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
$row = mysql_fetch_assoc($result); 
$count = $row['TheCount'];
if ($count == 0)
	{
	$_SESSION['MsgAcct1Login'] = "<span class='errorphp' style='padding-left: 40px;'>Your login for Account #1 doesn&rsquo;t match to a registered account. Please try again.<br></span>";
	$_SESSION['phpinvalidflag'] = true; 
	}

$query = "SELECT count(*) AS TheCount FROM owners_table WHERE OwnerUsername = '".$UsernameAccount2."' AND OwnerPassword = '".$PasswordAccount2."'";
$result = mysql_query($query) or die('Query (select of count(*) for a matching OwnerUsername and OwnerPassword re Account #2 from owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
$row = mysql_fetch_assoc($result); 
$count = $row['TheCount'];
if ($count == 0)
	{
	$_SESSION['MsgAcct2Login'] = "<span class='errorphp'>Your login for Account #2 doesn&rsquo;t match to a registered account. Please try again.<br></span>";
	$_SESSION['phpinvalidflag'] = true; 
	}

// Seek to validate $PrimaryAccount. It will have a null value if neither of the radio buttons has been clicked by the user. (Their default state is to both be initially unchecked.)
if (is_null($PrimaryAccount))
	{
	$_SESSION['MsgPrimaryAccount'] = "<span class='errorphp'><br>Select either Account #1 or #2<br></span>";
	$_SESSION['phpinvalidflag'] = true; 
	};

//Now go back to the previous page (createowner.php) and show any PHP inline validation error messages if the $_SESSION['phpinvalidflag'] has been set to true. ... otherwise, proceed to update the database with the user's form data.
if ($_SESSION['phpinvalidflag'])
	{
	$_SESSION['ManageConsolidateValidationError'] = 'true'; // Use this to control which divs have display='bock' (cf. 'none') in manage.php
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

/* The user's form submission has passed PHP form validation stage. */

/* Step 0: Obtain and store the OwnerID of both the primary account and the secondary account. (The primary account was selected by the user in manage.php by selecting the radio button for either Account #1 or Account #2.)  */
if ($PrimaryAccount == '1')
	{
	// Account #1 is to be the primary account ...
	$query = "SELECT * FROM owners_table WHERE OwnerUsername = '".$UsernameAccount1."' AND OwnerPassword = '".$PasswordAccount1."'";
	$result = mysql_query($query) or die('Query (select of OwnerID et al to relate primary account to matching OwnerUsername and OwnerPassword re Account #1 from owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	$rowPrim = mysql_fetch_assoc($result); 
	$PrimOwnerID = $rowPrim['OwnerID'];

	// ... so Account #2 is to be the secondary account
	$query = "SELECT * FROM owners_table WHERE OwnerUsername = '".$UsernameAccount2."' AND OwnerPassword = '".$PasswordAccount2."'";
	$result = mysql_query($query) or die('Query (select of OwnerID et al to relate secondary account to matching OwnerUsername and OwnerPassword re Account #2 from owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	$rowSec = mysql_fetch_assoc($result); 
	$SecOwnerID = $rowSec['OwnerID'];
	}
else
	{
	// Account #2 is to be the primary account ...
	$query = "SELECT * FROM owners_table WHERE OwnerUsername = '".$UsernameAccount2."' AND OwnerPassword = '".$PasswordAccount2."'";
	$result = mysql_query($query) or die('Query (select of OwnerID et al to relate primary account to matching OwnerUsername and OwnerPassword re Account #2 from owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	$rowPrim = mysql_fetch_assoc($result); 
	$PrimOwnerID = $rowPrim['OwnerID'];

	// ... so Account #1 is to be the secondary account
	$query = "SELECT * FROM owners_table WHERE OwnerUsername = '".$UsernameAccount1."' AND OwnerPassword = '".$PasswordAccount1."'";
	$result = mysql_query($query) or die('Query (select of OwnerID et al to relate secondary account to matching OwnerUsername and OwnerPassword re Account #1 from owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	$rowSec = mysql_fetch_assoc($result); 
	$SecOwnerID = $rowSec['OwnerID'];
	}	

/* Step 1: For the row of owners_table corresponding to the primary owner (i.e. where OwnerID == $PrimOwnerID), obtain the AssociateID that pertains to the "My Gallery Favorites" associate. This will always be the first ID number in the comma-separated list in the AssociateIDs column. Store it as $PrimMyGalFavAssociateID. */
$PrimAssociateIDs = $rowPrim['AssociateIDs'];
$PrimAssociateIDsArray = explode(',', $PrimAssociateIDs);
$PrimMyGalFavAssociateID = $PrimAssociateIDsArray[0];
$PrimOwnerDofAdmission = $rowPrim['OwnerDofAdmission'];
$PrimOwnerLastLogin = $rowPrim['OwnerLastLogin'];
$PrimShowWelcomeMsg = $rowPrim['ShowWelcomeMsg'];
$PrimOwnerFirstName = $rowPrim['OwnerFirstName'];
$PrimOwnerLastName = $rowPrim['OwnerLastName'];
$PrimOwnerLabel = $rowPrim['OwnerLabel'];
$PrimOwnerUsername = $rowPrim['OwnerUsername'];  // used to send a post-consolidation email confirmation
$PrimOwnerPassword = $rowPrim['OwnerPassword'];  // used to send a post-consolidation email confirmation

/* Step 2: For the row of owners_table corresponding to the secondary owner (i.e. where OwnerID == $SecOwnerID), obtain the AssociateID that pertains to the "My Gallery Favorites" associate. This will always be the first ID number in the comma-separated list in the AssociateIDs column. Store it as $SecMyGalFavAssociateID.  */
$SecAssociateIDs = $rowSec['AssociateIDs'];
$SecAssociateIDsArray = explode(',', $SecAssociateIDs);
$SecMyGalFavAssociateID = $SecAssociateIDsArray[0];
$SecOwnerDofAdmission = $rowSec['OwnerDofAdmission'];
$SecOwnerLastLogin = $rowSec['OwnerLastLogin'];
$SecShowWelcomeMsg = $rowSec['ShowWelcomeMsg'];
$SecOwnerFirstName = $rowSec['OwnerFirstName'];
$SecOwnerLastName = $rowSec['OwnerLastName'];
$SecOwnerLabel = $rowSec['OwnerLabel'];
$SecOwnerUsername = $rowSec['OwnerUsername']; // used to send a post-consolidation email confirmation

/* Step 3: Update certain column values in the row of owners_table corresponding to the primary owner as follows: */
// (a) Strip out $SecMyGalFavAssociateID from $SecAssociateIDs (because that associate is no longer needed), then append what's left to the AssociateIDs column.
$SecAssociateIDsMinusMyGalFavArray = $SecAssociateIDsArray;
unset($SecAssociateIDsMinusMyGalFavArray[0]); // The 'MyGalleryFavorite' AssociateID is always going to be the first element because of how the AssociateID column gets built up in owners_table.
$SecAssociateIDsMinusMyGalFav = implode(',', $SecAssociateIDsMinusMyGalFavArray); // Convert back to a string ready to be appended.
if (!empty($SecAssociateIDsMinusMyGalFav)) $AssociateIDsConsolidated = $PrimAssociateIDs.','.$SecAssociateIDsMinusMyGalFav; // Only bother to append the comma + $SecAssociateIDsMinusMyGalFav if it's not blank.

// (b) Obtain the earlier of $PrimOwnerDofAdmission or $SecOwnerDofAdmission
if ($PrimOwnerDofAdmission <= $SecOwnerDofAdmission) $OwnerDofAdmissionConsolidated = $PrimOwnerDofAdmission; else $OwnerDofAdmissionConsolidated = $SecOwnerDofAdmission;

// (c) Obtain the later of $PrimOwnerLastLogin or $SecOwnerLastLogin
if ($PrimOwnerLastLogin >= $SecOwnerLastLogin) $OwnerLastLoginConsolidated = $PrimOwnerLastLogin; else $OwnerLastLoginConsolidated = $SecOwnerLastLogin;

// (d) Set ShowWelcomeMsg to 0 if it's 0 for either the primary or the secondary account.
if ($PrimShowWelcomeMsg == 0 || $SecShowWelcomeMsg == 0) $ShowWelcomeMsgConsolidated = 0; else $ShowWelcomeMsgConsolidated = 1;

// (e) It's possible that either (or even both) Account #1 and Account #2 are non-registered (remembering that, even though the user who filled out the ConsolidateAccount form in manage.php had to be a logged in Owner, he/she might still have entered non-registered accounts as Account #1 and Account #2). We should attempt to ensure that the primary account is registered (i.e. has values for OwnerFirstName, OwnerLastName, and OwnerLabel by utilizing column values from the the designated secondary account if they're blank for the designated primary account. However, if both are non-registered then the consolidated account will necessarily be non-registered too. (We test for that and set $_SESSION['RegOwnerStatus'] accordingly in Step 9.)
if (empty($PrimOwnerFirstName)) $OwnerFirstNameConsolidated = $SecOwnerFirstName; else $OwnerFirstNameConsolidated = $PrimOwnerFirstName;
if (empty($PrimOwnerLastName)) $OwnerLastNameConsolidated = $SecOwnerLastName; else $OwnerLastNameConsolidated = $PrimOwnerLastName;
if (empty($PrimOwnerLabel)) $OwnerLabelConsolidated = $SecOwnerLabel; else $OwnerLabelConsolidated = $PrimOwnerLabel;


// Finally, update the row of owners_table for the primary account with the consolidated values. 
$query = "UPDATE owners_table SET OwnerFirstName = '".$OwnerFirstNameConsolidated."', OwnerLastName = '".$OwnerLastNameConsolidated."', OwnerLabel = '".$OwnerLabelConsolidated."', AssociateIDs = '".$AssociateIDsConsolidated."', OwnerDofAdmission = '".$OwnerDofAdmissionConsolidated."', OwnerLastLogin = '".$OwnerLastLoginConsolidated."', ShowWelcomeMsg = ".$ShowWelcomeMsgConsolidated." WHERE OwnerID = ".$PrimOwnerID;
$result = mysql_query($query) or die('Query (update of owners_table with consolidated values) failed: ' . mysql_error().' and the query string was: '.$query);

/* Step 4: In the associates_table, (a) obtain the value in the AuthorizedFileIDs column for the row where AssociateID == $SecMyGalFavAssociateID, and append it to the value of that column for row == $PrimMyGalFavAssociateID, (b) delete the row where AssociateID == $SecMyGalFavAssociateID b/c it's no longer relevant; and (c) update any rows where OwnerID == $SecOwnerID, setting the OwnerID column to $PrimOwnerID instead; (d) update any rows where OwnerID == $SecOwnerID, setting OwnerUsername to $SecOwnerUsername. See Step 7 re duplication handling. */

// Do (a)
// Obtain the value in the AuthorizedFileIDs column for the row where AssociateID == $SecMyGalFavAssociateID
$query = "SELECT AuthorizedFileIDs FROM associates_table WHERE AssociateID = ".$SecMyGalFavAssociateID;
$result = mysql_query($query) or die('Query (select of AuthorizedFileIDs in associates_table for the secondary My Gal Favorites row) failed: ' . mysql_error().' and the query string was: '.$query);
$row = mysql_fetch_assoc($result);
$SecMyGalFavAuthorizedFileIDs = $row['AuthorizedFileIDs'];

// Append this value to the value of that column for row == $PrimMyGalFavAssociateID
$query = "SELECT AuthorizedFileIDs FROM associates_table WHERE AssociateID = ".$PrimMyGalFavAssociateID;
$result = mysql_query($query) or die('Query (select of AuthorizedFileIDs in associates_table for the primary My Gal Favorites row) failed: ' . mysql_error().' and the query string was: '.$query);
$row = mysql_fetch_assoc($result);
$PrimMyGalFavAuthorizedFileIDs = $row['AuthorizedFileIDs'];

if (empty($PrimMyGalFavAuthorizedFileIDs))
	{
	$NewPrimMyGalFavAuthorizedFileIDs = $SecMyGalFavAuthorizedFileIDs; // No need for a separating comma
	}
else
	{
	$NewPrimMyGalFavAuthorizedFileIDs = $PrimMyGalFavAuthorizedFileIDs.','.$SecMyGalFavAuthorizedFileIDs;
	};
	
// Perform the update
$query = "UPDATE associates_table SET AuthorizedFileIDs = '".$NewPrimMyGalFavAuthorizedFileIDs."' WHERE AssociateID = ".$PrimMyGalFavAssociateID;
$result = mysql_query($query) or die('Query (update of AuthorizedFileIDs for primary My Gal Fav associate) failed: ' . mysql_error().' and the query string was: '.$query);

// Do (b)
$query = "DELETE FROM associates_table WHERE AssociateID = ".$SecMyGalFavAssociateID;
$result = mysql_query($query) or die('Query (delete of the My Gallery Favorites row in associates_table for the secondary account) failed: ' . mysql_error().' and the query string was: '.$query);

// Do (c)
$query = "UPDATE associates_table SET OwnerID = ".$PrimOwnerID." WHERE OwnerID = ".$SecOwnerID;
$result = mysql_query($query) or die('Query (update of OwnerID in rows of associates_table for other secondary associates) failed: ' . mysql_error().' and the query string was: '.$query);

// Do (d)
$query = "UPDATE associates_table SET OwnerUsername = '".$PrimOwnerUsername."' WHERE OwnerUsername = '".$SecOwnerUsername."'";
$result = mysql_query($query) or die('Query (update of Username from secondary to primary in associates_table) failed: ' . mysql_error().' and the query string was: '.$query);


/* Step 5:  In media_table, (a) replace any references to $SecMyGalFavAssociateID in AuthorizedAssociateIDs column, replacing them with a reference to $PrimMyGalFavAssociateID instead; and (b) set the OwnerID column to $PrimOwnerID where OwnerID = $SecOwnerID */
// First do (a)
$query = "SELECT FileID, AuthorizedAssociateIDs FROM media_table WHERE OwnerID = ".$SecOwnerID;
$result = mysql_query($query) or die('Query (select FileID, AuthorizedAssociateIDs from media_table for any associate owned by a secondary account) failed: ' . mysql_error().' and the query string was: '.$query);
while ($row = mysql_fetch_assoc($result))
	{
	// Convert the AuthorizedAssociateIDs column value into an array
	$AuthorizedAssociateIDsArray = explode(',', $row['AuthorizedAssociateIDs']);
	// If the array contains $SecMyGalFavAssociateID, find out which element (index) and replace that element with $PrimMyGalFavAssociateID ... otherwise move on to the next row of media_table where OwnerID == $SecOwnerID
	if (in_array($SecMyGalFavAssociateID, $AuthorizedAssociateIDsArray))
		{
		for ($i = 0; $i < count($AuthorizedAssociateIDsArray); $i++)
			{ 
		    // Make the replacement at whichever index coincides with the sought $SecMyGalFavAssociateID
			if ($AuthorizedAssociateIDsArray[$i] == $SecMyGalFavAssociateID)
				{
				$AuthorizedAssociateIDsArray[$i] = $PrimMyGalFavAssociateID; 
				// Convert the array back to a string ready to be used to update the AuthorizedAssociateIDs column of media_table.
				$AuthorizedAssociateIDs = implode(',', $AuthorizedAssociateIDsArray); 
				// Update the row of media_table for FileID == $row['FileID'].
				$queryUpdAuthAssIDs = "UPDATE media_table SET AuthorizedAssociateIDs = '".$AuthorizedAssociateIDs."' WHERE FileID = ".$row['FileID'];
				$resultUpdAuthAssIDs = mysql_query($queryUpdAuthAssIDs) or die('Query (update of AuthorizedAssociateIDs in media_table) failed: ' . mysql_error().' and the query string was: '.$queryUpdAuthAssIDs);
				break;
				}
			} 
		}
	}

// Now do (b)
$query = "UPDATE media_table SET OwnerID = ".$PrimOwnerID." WHERE OwnerID = ".$SecOwnerID;
$result = mysql_query($query) or die('Query (update of OwnerID in media_table) failed: ' . mysql_error().' and the query string was: '.$query);

/* Step 6: In assign_table, where AssociateID == $SecMyGalFavAssociateID, set AssociateID column to $PrimMyGalFavAssociateID */
$query = "UPDATE assign_table SET AssociateID = ".$PrimMyGalFavAssociateID." WHERE AssociateID = ".$SecMyGalFavAssociateID;
$result = mysql_query($query) or die('Query (update of AssociateID in assign_table) failed: ' . mysql_error().' and the database query string was: '.$query);

/*
Step 7: It's possible that the primary and secondary owner have an associate who is the same person i.e. as defined by having the same OwnerUsername. Example: Paul wants to consolidate his paul@yahoo.com owner account (which he designates as primary) and his paul@hotmail.com account (secondary). He had previously add'ed a friend (who subsequently accepted/registered) Vaughan from each of these owner accounts whom he identified as vaughan@gmail.com. After Step 4, there could be two rows in associates_table, both with OwnerID == $PrimOwnerID and OwnerUsername = vaughan@gmail.com. We need to consolidate these two rows into one. Proceed as follows:
	Designate the "duplicate" row of associates_table with AssociateID = xx as alpha, and the other row with AssociateID = yy as beta. We'll delete beta, but not before the following consolidation (update of alpha):
	Retain AssociateName of alpha. Combine AuthorizedFileIDs columns for alpha and beta. Use the later of the two AlertLastSent values. Keep the earlier of the AssociateDofAdmission values. Keep the later of the two AssociateLastLogin values. Then delete the beta row from associates_table.
	Also, if such a superfluous row in associates_table exists, remove the corresponding AssociateID from the (now consolidated via Step 3) AssociateIDs column in owners_table for the primary account and from the AuthorizedAssociateIDs column of media_table. In addition, replace this AssociateID (beta) value in any rows of assign_table with the AssociateID alpha value.
*/
$AlphasArray = array(); // Initialize this array to empty. We'll use it below to store any alpha values of AssociateID. Storing the alpha values in this way allows us to check whether a beta value is the same as a past alpha value in which case we wouldn't want to consider that beta value. To consider it would lead to double-processing of a duplicate pair. For example, if $row['AssociateID'] (alpha) is 130 and $line['AssociateID'] is 132 (beta) the first time around, then we certainly wouldn't want to later process the operations for a duplicate whereby $row['AssociateID'] (alpha) is 132 and $line['AssociateID'] is 130 (beta).
$query = "SELECT * FROM associates_table WHERE OwnerID = ".$PrimOwnerID;
$resultAlpha = mysql_query($query) or die('Query (select of * from associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
while ($row = mysql_fetch_assoc($resultAlpha))
	{
	$AlphasArray[] = $row['AssociateID']; // Push the value of the alpha AssociateID onto the $AlphasArray
	$query = "SELECT *, count(*) AS TheCount FROM associates_table WHERE (AssociateID != ".$row['AssociateID']." AND OwnerUsername = '".$row['OwnerUsername']."' AND OwnerID = ".$PrimOwnerID.")";
	$resultBeta = mysql_query($query) or die('Query (select of *, count * from associates_table for any other associate who has the same OwnerUsername) failed: ' . mysql_error().' and the database query string was: '.$query);
	$line = mysql_fetch_assoc($resultBeta);
	$TheCount = $line['TheCount'];
	// Only proceed to execute these additional operations necessitated by a duplicative associate if $TheCount == 1 AND the beta value of the AssociateID isn't in the $DuplicatesArray.
	if ($TheCount == 1 && !in_array($line['AssociateID'], $AlphasArray))
		{
		/* Create the consolidated values for the unified row in associates_table */

		// Combine AuthorizedFileIDs columns
		if (empty($line['AuthorizedFileIDs'])) $AuthorizedFileIDsConsolidated = $row['AuthorizedFileIDs'];
		else if (empty($row['AuthorizedFileIDs'])) $AuthorizedFileIDsConsolidated = $line['AuthorizedFileIDs'];
		else $AuthorizedFileIDsConsolidated = $row['AuthorizedFileIDs'].','.$line['AuthorizedFileIDs'];
		
		// Use the later of the two AlertLastSent values
		if ($row['AlertLastSent'] >= $line['AlertLastSent']) $AlertLastSentConsolidated = $row['AlertLastSent']; else $AlertLastSentConsolidated = $line['AlertLastSent'];

		// Use the earlier of AssociateDofAdmission values
		if ($row['AssociateDofAdmission'] <= $line['AssociateDofAdmission']) $AssociateDofAdmissionConsolidated = $row['AssociateDofAdmission']; else $AssociateDofAdmissionConsolidated = $line['AssociateDofAdmission'];

		// Use the later of the two AssociateLastLogin values
		if ($row['AssociateLastLogin'] >= $line['AssociateLastLogin']) $AssociateLastLoginConsolidated = $row['AssociateLastLogin']; else $AssociateLastLoginConsolidated = $line['AssociateLastLogin'];

		/* Update the alpha row using the consolidated values to become a unified alpha/beta row in associates_table */
		$queryConsolAlpha = "UPDATE associates_table SET AuthorizedFileIDs = '".$AuthorizedFileIDsConsolidated."', AlertLastSent = '".$AlertLastSentConsolidated."', AssociateDofAdmission = '".$AssociateDofAdmissionConsolidated."', AssociateLastLogin = '".$AssociateLastLoginConsolidated."' WHERE AssociateID = ".$row['AssociateID'];
		$resultConsolAlpha = mysql_query($queryConsolAlpha) or die('Query (update of associates_table for consolidated values) failed: ' . mysql_error().' and the query string was: '.$queryConsolAlpha);

		/* Delete the beta row in associates_table */
		$queryDelBeta = "DELETE FROM associates_table WHERE AssociateID = ".$line['AssociateID'];
		$resultDelBeta = mysql_query($queryDelBeta) or die('Query (delete of beta row in associates_table) failed: ' . mysql_error().' and the query string was: '.$queryDelBeta);
		
		/* Remove the corresponding AssociateID from the (now consolidated via Step 3) AssociateIDs column in owners_table for the primary account */
		$querySelectAssID = "SELECT AssociateIDs FROM owners_table WHERE OwnerID = ".$PrimOwnerID;
		$resultSelectAssID = mysql_query($querySelectAssID) or die('Query (select of AssociateIDs from owners_table) failed: ' . mysql_error().' and the database query string was: '.$querySelectAssID);
		$bar = mysql_fetch_assoc($resultSelectAssID);
		$PrimAssociateIDsArray = explode(',', $bar['AssociateIDs']);
		// Locate the key for the superfluous AssociateID and delete (unset the element with that key) in the array
		$key = array_search($line['AssociateID'], $PrimAssociateIDsArray);
	    unset($PrimAssociateIDsArray[$key]);
		// Convert back to a string
		$PrimAssociateIDsPostDuplicateRemoval = implode(',', $PrimAssociateIDsArray);
		
		// Now update the primary owner row in owners_table with the new value of AssociateIDs
		$queryAssIDPostDup = "UPDATE owners_table SET AssociateIDs = '".$PrimAssociateIDsPostDuplicateRemoval."' WHERE OwnerID = ".$PrimOwnerID;
		$resultAssIDPostDup = mysql_query($queryAssIDPostDup) or die('Query (update of AssociateIDs in primary row of owners_table) failed: ' . mysql_error().' and the query string was: '.$queryAssIDPostDup);

		/* Post Step 5, search for the beta AssociateID in the AuthorizedAssociateIDs column in media_table where OwnerID == $PrimOwnerID. Iff you find it, replace it with the corresponding alpha AssociateID in the AuthorizedAssociateIDs column. */
		$queryAuthAssIDs = "SELECT AuthorizedAssociateIDs, FileID FROM media_table WHERE OwnerID = ".$PrimOwnerID; 
		$resultAuthAssIDs = mysql_query($queryAuthAssIDs) or die('Query (select of AuthorizedAssociateIDs in media_table) failed: ' . mysql_error().' and the query string was: '.$queryAuthAssIDs);
		while ($bar = mysql_fetch_assoc($resultAuthAssIDs))
			{
			// Convert to array
			$AuthorizedAssociateIDsArray = explode(',', $bar['AuthorizedAssociateIDs']);
			// Locate the key for the superfluous AssociateID and delete (unset the element with that key) in the array
			$key = array_search($line['AssociateID'], $AuthorizedAssociateIDsArray); // $key will take value 'false' if the beta AssociateID is not found.
			if ($key > 0) // The beta AssociateID was found and needs to be replaced
				{
		    	$AuthorizedAssociateIDsArray[$key] = $row['AssociateID']; // replacement happens here
				// Convert back to a string
				$NewAuthorizedAssociateIDs = implode(',', $AuthorizedAssociateIDsArray);
			
				// Now update this row of media_table with the new value of AuthorizedAssociateIDs
				$queryAuthAssIDsPostDup = "UPDATE media_table SET AuthorizedAssociateIDs = '".$NewAuthorizedAssociateIDs."' WHERE FileID = ".$bar['FileID'];
				$resultAuthAssIDsPostDup = mysql_query($queryAuthAssIDsPostDup) or die('Query (update of AuthorizedAssociateIDs in media_table) failed: ' . mysql_error().' and the query string was: '.$queryAuthAssIDsPostDup);
				}
			}
			
		/* In addition, replace the beta AssociateID value in any rows of assign_table with the AssociateID alpha value */
		$queryDelDup = "UPDATE assign_table SET AssociateID = ".$row['AssociateID']." WHERE AssociateID = ".$line['AssociateID'];
		$resultDelDup = mysql_query($queryDelDup) or die('Query (update of Associate for duplicative row in assign_table) failed: ' . mysql_error().' and the query string was: '.$queryDelDup);
			
		}
	}

/* Step 8: In owners_table, delete row where OwnerID == $SecOwnerID */
$query = "DELETE FROM owners_table WHERE OwnerID = ".$SecOwnerID;
$result = mysql_query($query) or die('Query (delete of secondary row in owners_table) failed: ' . mysql_error().' and the query string was: '.$query);

/* Step 9: Upon successful login in index.php, several session variables get set. If the user has opted to make the primary account the one to which he/she is currently logged in or if he/she is currently logged into neither Account #1 nor Account #2 in the ConsolidateAccount form in manage.php, then all these session variable values can remain in their current states. However, if the user has opted to make the account to which he/she is currently logged in as the secondary account, then the logged in Owner row will have been deleted in Step 8 and we must reset all these session variables as a safeguard against their having now defunct values.  */

// Determine whether the user is logged into the account that he/she designated as secondary and reset session variables if so.
if ($_SESSION['LoggedInOwnerID'] == $SecOwnerID)
	{
	$_SESSION['LoggedInOwnerID'] = $PrimOwnerID;
	$_SESSION['OwnerID'] = $PrimOwnerID;
	$_SESSION['OwnerLabel'] = $PrimOwnerLabel;
	$_SESSION['LoggedInOwnerLabel'] = $PrimOwnerLabel;
	$_SESSION['LoggedInOwnerUsername'] = $PrimOwnerUsername;
	$_SESSION['OwnerUsername'] = $PrimOwnerUsername;
	$_SESSION['OwnerDofAdmission'] = $PrimOwnerDofAdmission;
	$_SESSION['ShowWelomeMsg'] = 0;
	$_SESSION['AssociateID'] = $PrimMyGalFavAssociateID;
	$_SESSION['LoggedInOwnersOwnAssociate'] = $PrimMyGalFavAssociateID;
	$_SESSION['InitialAssociateIDcc'] = $PrimMyGalFavAssociateID;

	// In the list of the Owner's content producers (i.e. the people who provide content to the Owner), we'll initialize that list so that the initially preselected radio button (content producer) is the one whose OwnerLabel is alphabetically first. Obtain the corresponding AssociateID from the following query and store it in $_SESSION['InitialAssociateIDcp'].
	$query = "SELECT owners_table.OwnerLabel, associates_table.AssociateID FROM associates_table, owners_table WHERE associates_table.OwnerUsername = '".$_SESSION['LoggedInOwnerUsername']."' AND associates_table.OwnerID != ".$_SESSION['LoggedInOwnerID']." AND associates_table.OwnerID = owners_table.OwnerID ORDER BY owners_table.OwnerLabel ASC LIMIT 1";
	$result = mysql_query($query) or die('Query (select first alphabetical AssociateID, AssociateName from associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	$row = mysql_fetch_assoc($result);
	$_SESSION['InitialAssociateIDcp'] = $row['AssociateID']; // $_SESSION['InitiaAssociateIDcp'] stores the initial AssociateID of a content producer to the Owner, whose OwnerID is stored in $_SESSION['LoggedInOwnerID']

	// Determine whether the primary account pertains to an Owner who is fully registered (i.e. has a completed row in owners_table) or semiregistered (i.e. has provided OwnerUsername and OwnerPassword, but hasn't yet provided OwnerLabel, OwnerFirstName, and OwnerLastName). Then set $_SESSION['RegOwnerStatus'] accordingly.
	$query = "SELECT OwnerLabel, OwnerFirstName, OwnerLastName FROM owners_table WHERE OwnerID = ".$PrimOwnerID;
	$result = mysql_query($query) or die('Query (select OwnerLabel, OwnerFirstName, OwnerLastName for primary account from owners_table ) failed: ' . mysql_error().' and the database query string was: '.$query);
	$row = mysql_fetch_assoc($result);
	if (empty($row['OwnerLabel']) || empty($row['OwnerFirstName']) || empty($row['OwnerLastName'])) $_SESSION['RegOwnerStatus'] = 'semi';
	else $_SESSION['RegOwnerStatus'] = 'full';
	}

/* Send an email message to $PrimOwnerUsername and $SecOwnerUsername */

require('Mail.php');
require('Mail/mime.php');

$messageHTML = "<html><body><table cellspacing='10'><tr><td colspan='2' style='font-family: Arial, Helvetica, sans-serif'>Hello ".$PrimOwnerFirstName."</td></tr>";
$messageHTML .= "<tr><td colspan='2' style='font-family: Arial, Helvetica, sans-serif'>This is to confirm your recent consolidation of accounts on Abridg. The account with username = <kbd>".$SecOwnerUsername."</kbd> no longer exists. None of your media files or friends have been lost. Everything has been consolidated (merged) into a single account. To access your consolidated account, log in as follows:</td></tr>";
$messageHTML .= "<tr><th style='font-family: Arial, Helvetica, sans-serif; text-align: left; width: 280px;'>Username</th><th style='font-family: Arial, Helvetica, sans-serif; text-align: left;'>Password</th></tr>";
$messageHTML .=	"<tr><td style='font-family: Arial, Helvetica, sans-serif'>".$PrimOwnerUsername."</td><td style='font-family: Arial, Helvetica, sans-serif'>".$PrimOwnerPassword."</td></tr>";
$messageHTML .= "<tr><td colspan='2' style='font-family: Arial, Helvetica, sans-serif'><br>Thanks for using Abridg!</td></tr>";
$messageHTML .= "<tr><td colspan='2' style='font-family: Arial, Helvetica, sans-serif'>The Abridg Team</td></tr>";
$messageHTML .= "</table></body></html>";

$messageText = "Hello ".$PrimOwnerFirstName."\n\nThis is to confirm your recent consolidation of accounts on Abridg. The account with username = ".$SecOwnerUsername." no longer exists. None of your media files or friends have been lost. Everything has been consolidated (merged) into a single account. To access your consolidated account, log in as follows:\n";
$messageText .= "Username              Password\n";
$messageText .= $PrimOwnerUsername."              ".$PrimOwnerPassword."\n\n";
$messageText .= "Thanks for using Abridg!\n\nThe Abridg Team";
		
$sendto = $PrimOwnerUsername.','.$SecOwnerUsername;
$crlf = "\n";
$hdrs = array(
'From'    => 'donotreply@abridg.com',
'Subject' => 'Consolidation of Accounts on Abridg',
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
		
// Display an on-screen confirmation.
?>
<table cellpadding="0" cellspacing="0" style="width: 900px; margin-top: 90px; margin-left: auto; margin-right: auto;">
<tr>
<td colspan="2" style="text-align: left;">
<p class='text' style='margin-bottom: 20px;'>Account consolidation is now complete. The account with username = <kbd><?=$SecOwnerUsername; ?></kbd> no longer exists. None of your media files or friends have been lost. Everything has been merged into the following account:</p>
</td>
</tr>
<tr>
<td width='300' style='margin-top: 10px; margin-bottom: 20px;'><label>Username</label></td>
<td><label>Password</label></td>
</tr>
<tr>
<td>
<p class='text' style='margin-top: 10px; margin-bottom: 20px;'><?=$PrimOwnerUsername; ?></p>
</td>
<td><p class='text' style='margin-top: 10px; margin-bottom: 20px;'><?=$PrimOwnerPassword; ?></p></td>
</tr>
<tr>
<td colspan="2" style="text-align: left;">
<p class='text' style='margin-top: 10px; margin-bottom: 20px;'>We&rsquo;ve also sent you an email confirmation.</p>
</td>
</tr>
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