<?php
/*
assign_slave.php is the slave script for assign.php. That script handles two scenarios: (i) Where the user identifies one associate and then assigns (or deassigns) media to that associate; and (ii) where the user identifies one media file and then assigns (or deassigns) associates to that media file.
	In addition, assign_slave.php provides slave functionality when an Administrator (i.e. Owner) performs four other tasks: (iii) delete an associate from the associates_table, (iv) delete a media file from the media_table, (v) edit details of an existing associate in the associates_table, and (vi) edit details of an existing media file in the media_table.
	Note that assign_slave.php handles the two edit tasks (v) and (vi) by simply setting a session variable and redirecting to pages editassociate.php and editmedia.php respectively, which use that session variable.
	assign_slave.php also sends alerts if the logged in Owner selected "Send alerts automatically upon assignment" in one of the manage.php HTML forms (that's initially processed by managealerts_slave.php).
*/

// Start a session
session_start();

if ($_SESSION['Authenticated'] != 'true') header("Location: /index.php");

ob_start(); // Used in conjunction with ob_flush() [see www.tutorialized.com/view/tutorial/PHP-redirect-page/27316], this allows me to postpone issuing output to the screen until after the header has been sent.

require('../ssi/alertgenerator.php'); // Include the alertgenerator.php file, which contains the alertgenerator() function for generating alert email messages to inform an associate that a Content Producer (in this case, the logged in Owner) has assigned new content to him/her.

// Connect to mysql
$db = mysql_connect('localhost', 'paulme6_merlyn', 'fePhaCj64mkik')
or die('Could not connect: ' . mysql_error());
mysql_select_db('paulme6_abridg') or die('Could not select database: ' . mysql_error());

// Create short variable names for situation where an associate is first selected before various media files are assigned to him/her.
$Associate = $_POST['Associate']; // The value (i.e. AssociateID) of the selected radio button in assign.php when user is selecting one associate to whom to assign various media files.
$MediaFiles = $_POST['MediaFiles']; // This is an array of FileID column values in the media_table for media files whose check box is checked in assign.php
$AssignMediaFilesToAssociate = $_POST['AssignMediaFilesToAssociate']; // Submit button for assigning media files to the selected associate (aka friend)
	
// Create short variable names for situation where a media file is first selected before various associates are assigned to that file.
$MediaFile = $_POST['MediaFile']; // The value (i.e. FileID) of the selected radio button in assign.php when user is selecting one media file that will be assigned to various associates.
$Associates = $_POST['Associates']; // This is an array of AssociateID column values in the associates_table for the associate whose check box is checked in assign.php
$AssignAssociatesToMediaFile = $_POST['AssignAssociatesToMediaFile']; // Submit button for assigning associates to the selected media file

/*
Check to see whether either the DeleteAssociateButtonNNN (icon) or EditAssociateButtonNNN (where NNN is an AssociateID) were clicked for a particular account holder. If such a button was clicked, then $_POST['DeleteAssociateButtonNNN_x'] (or $_POST['EditAssociateButtonNNN_x']) will have been set. Because there is one delete icon (and one edit icon) next to each Account Holder in the left-hand side of the 'AssignMediaFilesToAssociateScreen' in assign.php, we need to loop through the POST array for every AssociateID in associates_table associated with the Owner whose OwnerID is $_SESSION['LoggedInOwnerID'] (set in assign.php), checking to see whether an icon was clicked. While we're doing the loop to check for delete clicks, we might as well check for whether an EditAssociateButtonNNN was clicked as well.
	Note the need, when referencing an image button in the POST array, to append the _x to the index. So, even though the name of the button is just 'DeleteAssociateButtonNNN', it will be referenced in the POST array as $_POST['DeleteAssociateNNN_x'] (see http://davidwalsh.name/php-form-submission-recognize-image-input-buttons). 
*/
$query = "SELECT AssociateID FROM associates_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
$result = mysql_query($query) or die('Select AssociateID in associates_table has failed: ' . mysql_error());
while ($row = mysql_fetch_assoc($result))
	{
	$thedeletebutton = 'DeleteAssociateButton'.$row['AssociateID'].'_x';
	$theeditbutton = 'EditAssociateButton'.$row['AssociateID'].'_x';
	if (isset($_POST[$thedeletebutton])) { $DeleteAssociate = $row['AssociateID']; break; }
	if (isset($_POST[$theeditbutton])) { $_SESSION['EditAssociate'] = $row['AssociateID']; header('Location: /editassociate.php'); exit; }
	}

/*
Check to see whether either the DeleteMediaFileButtonNNN (icon) or EditMediaFileButtonNNN (where NNN is an AssociateID) were clicked for a particular media file. If such a button was clicked, then $_POST['DeleteMediaFileButtonNNN_x'] (or $_POST['EditMediaFileButtonNNN_x']) will have been set. Because there is one delete icon (and one edit icon) next to each media file in the left-hand side of the 'AssignAssociatesToMediaFileScreen' in assign.php, we need to loop through the POST array for every media file FileID in media_table belonging to the Owner whose OwnerID is $_SESSION['LoggedInOwnerID'] (set in assign.php), checking to see whether an icon was clicked. While we're doing the loop to check for delete clicks, we might as well check for whether an EditMediaFileButtonNNN was clicked as well.
	Note the need, when referencing an image button in the POST array, to append the _x to the index. So, even though the name of the button is just 'DeleteMediaFileButtonNNN', it will be referenced in the POST array as $_POST['DeleteMediaFileNNN_x'] (see http://davidwalsh.name/php-form-submission-recognize-image-input-buttons). 
*/
$query = "SELECT FileID FROM media_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
$result = mysql_query($query) or die('Select FileID in media_table has failed: ' . mysql_error());
while ($row = mysql_fetch_assoc($result))
	{
	$thedeletebutton = 'DeleteMediaFileButton'.$row['FileID'].'_x';
	$theeditbutton = 'EditMediaFileButton'.$row['FileID'].'_x';
	if (isset($_POST[$thedeletebutton])) { $DeleteMediaFile = $row['FileID']; break; }
	if (isset($_POST[$theeditbutton])) { $_SESSION['EditMediaFile'] = $row['FileID']; header('Location: /editmedia.php'); exit; }
	}

// Define a custom function for later use below.
function remove_item_by_value($array, $knownvalue, $preserve_keys = false)
	{
	if (empty($array) || !is_array($array)) return false;
	foreach($array as $key => $value)
		{
		if ($value == $knownvalue) unset($array[$key]);
		}
	return ($preserve_keys === true) ? $array : array_values($array);
	}

if (isset($Associate)) // The user clicked one of the radio buttons to select a particular associate to whom he/she will assign various media files.
	{
	// Unset any legacy session variables pertaining to the other scenario i.e. assignment of associates to one media file.
	unset($_SESSION['FileSelected']);
	unset($_SESSION['FilenameSelected']);
	unset($_SESSION['FileID']);

	// Obtain the AssociateName of the associate corresponding to the radio button that was selected in assign.php and save it as a session variable for use in assign.php.
	$query = "SELECT AssociateName FROM associates_table WHERE AssociateID = ".$Associate;
	$result = mysql_query($query) or die('Query (select AssociateName from associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	$row = mysql_fetch_assoc($result);
	$_SESSION['AssociateNameSelected'] = $row['AssociateName'];

	// Assign values to the other session variables that are used upon return to assign.php.
	$_SESSION['AssociateID'] = (int)$Associate; // The posted value of the AssociateID (or any integer) gets posted as a string, so we convert it to an integer before assigning it to the session variable.
	$_SESSION['SelectedAssociateID'] = $_SESSION['AssociateID']; // $_SESSION['SelectedAssociateID'] is used to preset the radio button list of associates in assign.php after the user had made a prior selection while ensuring it's not set on the first visit to assign.php. 
	$_SESSION['AssociateSelected'] = true;

	// Unset $Associate so this clause isn't entered inadvertently.
	unset($Associate);
	}
	
if (isset($AssignMediaFilesToAssociate)) // The user clicked the 'Assign Media' button in order to assign/deassign media to the preselected associate according to the check-box next to each media file.
	{
	// Unset any legacy session variables pertaining to the other scenario i.e. assignment of associates to one media file.
	unset($_SESSION['FileSelected']);
	unset($_SESSION['FilenameSelected']);
	unset($_SESSION['FileID']);
	
	// Manipulate the $MediaFiles array to convert the POST'ed values (array elements) from string (default) to integer.
	for ($i = 0; $i < count($MediaFiles); $i++)
		{
		$MediaFiles[$i] = (int)$MediaFiles[$i];
		}

	// Convert the values in the $MediaFiles array into a string ready for entry into the associates_table's AuthorizedFileIDs column.
	if (!empty($MediaFiles)) $MediaFilesString = implode(',', $MediaFiles); else $MediaFilesString = NULL;
	
	/* Update the AuthorizedFileIDs column in the associates_table according to the $MediaFiles array for the associate identified with an AssociateID equal to the $_SESSION['AssociateID'] session variable. */
	$query = "UPDATE associates_table SET AuthorizedFileIDs = '".$MediaFilesString."' WHERE AssociateID = ".$_SESSION['AssociateID'];
	$result = mysql_query($query) or die('Query (update of AuthorizedFileIDs in associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	
	/* (A) Update the AuthorizedAssociateIDs column in the media_table; and (B) insert or delete a row in assign_table for every FileID according to whether each MediaFile checkbox was checked for the associate ID of $_SESSION['AssociateID']. Method for both (A) and (B): Loop through every row (i.e. each FileID) in media_table associated with the Owner (i.e. OwnerID == $_SESSION['LoggedInOwnerID']). */
	// Implementation of (A): If the $MediaFiles array contains that particular FileID (i.e. the logged in Owner checked the check-box next to this particular media file) then his/her AssociateID (which is stored within the $_SESSION['AssociateID'] session variable) should be included within the AuthorizedAssociateIDs string in the table. Examine that string and append the AssociateID if necessary. If, on the other hand, the $MediaFiles array doesn't contain the particular FileID for the row in question (i.e. the user didn't checked the check-box next to this particular media file) then his/her AssociateID (stored as $_SESSION['AssociateID']) should be deleted from the AuthorizedAssociateIDs string in the table if it was previously existent in that string. Examine that string and delete the AssociateID if necessary.
	$query = "SELECT FileID, AuthorizedAssociateIDs FROM media_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
	$result2 = mysql_query($query) or die('Query (select FileID, AuthorizedAssociateIDs from media_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	while ($row = mysql_fetch_assoc($result2))
		{
		if (!empty($MediaFiles) && in_array($row['FileID'], $MediaFiles)) // The check-box for this media file was checked
			{
			if (!empty($row['AuthorizedAssociateIDs'])) 
				{
				$AuthorizedAssociateIDsArray = explode(',', $row['AuthorizedAssociateIDs']); // Convert the column's string value into an array to ensure valid value searching (e.g. don't make the mistake of a false positive by looking for an AssociateID of, say, 3 and mistakenly "finding" it as part of, say, an AssociateID of 13 or 23.
				if (in_array($_SESSION['AssociateID'], $AuthorizedAssociateIDsArray)) 
					{
					// The user's AssociateID stored as $_SESSION['AssociateID'] is already present in the AuthorizedAssociateIDs column for this particular FileID. Leave the value to be entered into the AuthorizedAssociateIDs column of media_table unchanged.
					$updatedAuthorizedAssociateIDsString = $row['AuthorizedAssociateIDs']; 
					}
				else
					{
					$updatedAuthorizedAssociateIDsString = $row['AuthorizedAssociateIDs'].','.$_SESSION['AssociateID']; // Append the AssociateID to the existing AuthorizedAssociateIDs string.
					}	
				}
			else
				{
				$updatedAuthorizedAssociateIDsString = $_SESSION['AssociateID']; // The AssociateID is to be the first and (as yet) only item in AuthorizedAssociateIDs string (no comma case).
				}	
			}
		else // The media FileID is not found in the $MediaFiles array and hence we conclude that the check-box for this media file was not checked
			{
			if (!empty($row['AuthorizedAssociateIDs'])) // If the column isn't empty, look to see whether the FileID currently exists in the AuthorizedAssociateIDs column for this media file.
				{
				$AuthorizedAssociateIDsArray = explode(',', $row['AuthorizedAssociateIDs']); // Convert the column's string value into an array to ensure valid value searching (e.g. don't make the mistake of a false positive by looking for an AssociateID of, say, 3 and mistakenly "finding" it as part of, say, an AssociateID of 13 or 23.
				if (in_array($_SESSION['AssociateID'], $AuthorizedAssociateIDsArray)) 
					{
					// We now need to remove the AssociateID from the AuthorizedAssociateIDs column for this media file. Loop through the $AuthorizedAssociateIDsArray and unset the offending item . Use a custom remove_item_by_value() function (definied above) for removing an item from an array by known value courtesy: http://dev-tips.com/featured/remove-an-item-from-an-array-by-value.
					$newAuthorizedAssociateIDsArray = remove_item_by_value($AuthorizedAssociateIDsArray, $_SESSION['AssociateID']); // Call the custom remove_item_by_value() function.
					$updatedAuthorizedAssociateIDsString = implode(',', $newAuthorizedAssociateIDsArray); // Convert the $newAuthorizedAssociateIDsArray array into a string in readiness for entry into the $media_table.
					}
				else
					{
					// The AuthorizedAssociateIDsArray doesn't contain $_SESSION['AssociateID'] so we don't need to remove it from the AuthorizedAssociateIDs column in the media_table. Leave the value to be entered into the media_table unchanged.
					$updatedAuthorizedAssociateIDsString = $row['AuthorizedAssociateIDs'];
					}	
				}
			else
				{
				// The AuthorizedAssociateIDs column in the media_table is empty already so there's no need to worry about removing the AssociateID from it. Set the value to be entered to blank.
				$updatedAuthorizedAssociateIDsString = '';
				}
			}
		// Having now determined the appropriate value of $updatedAuthorizedAssociateIDsString, update media_table with that value for the current FileID row in the loop.
		$query1 = "UPDATE media_table SET AuthorizedAssociateIDs = '".$updatedAuthorizedAssociateIDsString."' WHERE FileID = ".$row['FileID'];
		$result = mysql_query($query1) or die('Query (update media_table for AuthorizedAssociateIDs) failed: ' . mysql_error().' and the database query string was: '.$query);
		}

	// Implementation of (B): If the $MediaFiles array contains that particular FileID (i.e. the logged in Owner checked the check-box next to this particular media file) then a row should exist within assign_table for this pairing of a FileID and AssociateID (stored as $_SESSION['AssociateID']). Examine whether such a row already exists and insert one if necessary. If, on the other hand, the $MediaFiles array doesn't contain the FileID for the media_table row in the loop (i.e. the user didn't checked the check-box next to this particular media file), then a row should not exist within assign_table for this pairing of a FileID and AssociateID. Examine whether such a row already exists and delete it if necessary.
	// Note: I ran a very similar query and loop just above. I'm running it again, sacrificing a little speed for programming simplicity/transparency.
	$query = "SELECT FileID FROM media_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID']; 
	$result2 = mysql_query($query) or die('Query (select FileID from media_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	while ($row = mysql_fetch_assoc($result2))
		{
		if (!empty($MediaFiles) && in_array($row['FileID'], $MediaFiles)) // The check-box for this media file was checked
			{
			// Examine whether assign_table already has a row for AssociateID == $_SESSION['AssociateID'] and FileID == $row['FileID']
			$query = "SELECT COUNT(*) FROM assign_table WHERE AssociateID = ".$_SESSION['AssociateID']." AND FileID = ".$row['FileID'];
			$result = mysql_query($query) or die('Query (select COUNT * from assign_table) failed: ' . mysql_error().' and the database query string was: '.$query);
			$line = mysql_fetch_row($result);
			$rowAlreadyExists = ($line[0] >= 1 ? true : false);
			if ($rowAlreadyExists)
				{
				// No action necessary
				}
			else
				{
				// Insert a row into assign_table for this $_SESSION['AssociateID'] and $row['FileID'] pair.
				$query = "INSERT INTO assign_table SET AssociateID = ".$_SESSION['AssociateID'].", FileID = ".$row['FileID'].", AssignDate = NOW()";
				$result = mysql_query($query) or die('Query (insert into assign_table) failed: ' . mysql_error().' and the database query string was: '.$query);
				}
			}
		else // The media FileID is not found in the $MediaFiles array and hence we conclude that the check-box for this media file was not checked
			{
			// Examine whether assign_table already has a row for AssociateID == $_SESSION['AssociateID'] and FileID == $row['FileID']
			$query = "SELECT COUNT(*) FROM assign_table WHERE AssociateID = ".$_SESSION['AssociateID']." AND FileID = ".$row['FileID'];
			$result = mysql_query($query) or die('Query (select COUNT * from assign_table) failed: ' . mysql_error().' and the database query string was: '.$query);
			$line = mysql_fetch_row($result);
			$rowAlreadyExists = ($line[0] >= 1 ? true : false);
			if ($rowAlreadyExists)
				{
				// Delete the row from assign_table for this $_SESSION['AssociateID'] and $row['FileID'] pair.
				$query = "DELETE FROM assign_table WHERE AssociateID = ".$_SESSION['AssociateID']." AND FileID = ".$row['FileID'];
				$result = mysql_query($query) or die('Query (delete from assign_table) failed: ' . mysql_error().' and the database query string was: '.$query);
				}
			else
				{
				// No action necessary
				}
			}
		}

	// Unset $_SESSION['AssociateID'] and $_SESSION['AssociateSelected']
	unset($_SESSION['AssociateID']); //Prevent unwanted preselection of an associate's radio button on a subsequent visit to assign.php
	unset($_SESSION['AssociateSelected']); // Prevent unwanted display of the "Step 2:..." right-hand panel upon a subsequent visit to assign.php
	}

if (isset($MediaFile)) // The user clicked one of the radio buttons to select a media file that will then be assigned to various associates.
	{
	// Unset any legacy session variables pertaining to the other scenario i.e. assignment of media files to one selected associate.
	unset($_SESSION['AssociateSelected']);
	unset($_SESSION['AssociateNameSelected']);
	unset($_SESSION['AssociateID']);
	
	// Obtain the name of the media file corresponding to the radio button that was selected in assign.php and save it as a session variable for use in assign.php.
	$query = "SELECT Filename FROM media_table WHERE FileID = ".$MediaFile;
	$result = mysql_query($query) or die('Query (select Filename from media_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	$row = mysql_fetch_assoc($result);
	$_SESSION['FilenameSelected'] = $row['Filename'];

	// Assign values to other session variables that are used upon return to assign.php.
	$_SESSION['FileID'] = (int)$MediaFile; // The posted value of the FileID (or any integer) gets posted as a string, so we convert it to an integer before assigning it to the session variable.
	$_SESSION['FileSelected'] = true;
	
	// Unset $MediaFile so this clause isn't entered inadvertently.
	unset($MediaFile);
	}
	
if (isset($AssignAssociatesToMediaFile)) // The user clicked the 'Assign Associates' button in order to assign/deassign associates to the preselected media file according to the check-box next to each account holder.
	{
	// Unset any legacy session variables pertaining to the other scenario i.e. assignment of media files to one selected associate.
	unset($_SESSION['AssociateSelected']);
	unset($_SESSION['AssociateNameSelected']);
	unset($_SESSION['AssociateID']);
	
	// Manipulate the $Associates array to convert the POST'ed values from string (default) to integer.
	for ($i = 0; $i < count($Associates); $i++)
		{
		$Associates[$i] = (int)$Associates[$i];
		}

	// Convert the values in the $Associates array into a string ready for entry into the media_table's AuthorizedAssociateIDs column.
	if (!empty($Associates)) $AssociatesString = implode(',', $Associates); else $AssociatesString = NULL;
	
	// Update the AuthorizedAssociateIDs column in the media_table according to the $Associates array for the media file identified with a FileID equal to the $_SESSION['FileID'] session variable.
	$query = "UPDATE media_table SET AuthorizedAssociateIDs = '".$AssociatesString."' WHERE FileID = ".$_SESSION['FileID'];
	$result = mysql_query($query) or die('Query (update of AuthorizedAssociateIDs in media_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	
	/* (A) Update the AuthorizedFileIDs column in the associates_table; and (B) insert or delete a row in assign_table for every AssociateID according to whether each of the Associates checkbox was checked for the file ID of $_SESSION['FileID']. Method for both (A) and (B): Loop through every row (i.e. each AssociateID) in associates_table associated with the Owner (i.e. OwnerID == $_SESSION['LoggedInOwnerID']). */
	// Implementation of (A): If the $Associates array contains that particular AssociateID (i.e. the user checked the check-box next to this particular associate) then the FileID (which is stored within the $_SESSION['FileID'] session variable) should be included within the AuthorizedFileIDs string in the table. Examine that string and append the FileID if necessary. If, on the other hand, the $Associates array doesn't contain the particular AssociateID for the row in question (i.e. the user didn't checked the check-box next to this particular associate) then the FileID (stored as $_SESSION['FileID']) should be deleted from the AuthorizedFileIDs string in the table if it was previously existent in that string. Examine that string and delete the FileID if necessary.
	$query = "SELECT AssociateID, AuthorizedFileIDs FROM associates_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
	$result2 = mysql_query($query) or die('Query (select AssociateID, AuthorizedFileIDs from associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	mysql_data_seek($result2, 0); // Again, reset the pointer to the beginning of the resultset before running thru the loop.
	while ($row = mysql_fetch_assoc($result2))
		{
		if (!empty($Associates) AND in_array($row['AssociateID'], $Associates)) // The check-box for this associate was checked
			{
			if (!empty($row['AuthorizedFileIDs'])) 
				{
				$AuthorizedFileIDsArray = explode(',', $row['AuthorizedFileIDs']); // Convert the column's string value into an array to ensure valid value searching (e.g. don't make the mistake of a false positive by looking for a FileID of, say, 3 and mistakenly "finding" it as part of, say, a FileID of 13 or 23.
				// Before proceeding any further, check whether the selected FileID (stored in session variable $_SESSION['FileID'] already exists in the AuthorizedFileIDs for each associate whose check-box was checked. If it does exist there, don't bother doing anything (no point in duplicating) except jump to the next iteration in the loop.
				if (in_array($_SESSION['FileID'], $AuthorizedFileIDsArray)) continue; // Jump ahead to the next iteration of the while loop
				if (in_array($_SESSION['MediaFile'], $AuthorizedFileIDsArray)) 
					{
					// The user's FileID stored as $_SESSION['FileID'] is already present in the AuthorizedFileIDs column for this particular AssociateID. Leave the value to be entered into the AuthorizedFileIDs column of associates_table unchanged.
					$updatedAuthorizedFileIDsString = $row['AuthorizedFileIDs']; 
					}
				else
					{
					$updatedAuthorizedFileIDsString = $row['AuthorizedFileIDs'].','.$_SESSION['FileID']; // Append the FileID to the existing AuthorizedFileIDs string.
					}	
				}
			else
				{
				$updatedAuthorizedFileIDsString = $_SESSION['FileID']; // The FileID is to be the first and (as yet) only item in AuthorizedFileIDs string (no comma case).
				}	
			}
		else // The AssociateID is not found in the $Associates array and hence we conclude that the check-box for this associate was not checked
			{
			if (!empty($row['AuthorizedFileIDs'])) // If the column isn't empty, look to see whether the AssociateID currently exists in the AuthorizedFileIDs column for this associate.
				{
				$AuthorizedFileIDsArray = explode(',', $row['AuthorizedFileIDs']); // Convert the column's string value into an array to ensure valid value searching (e.g. don't make the mistake of a false positive by looking for a FileID of, say, 3 and mistakenly "finding" it as part of, say, an AssociateID of 13 or 23.
				if (in_array($_SESSION['FileID'], $AuthorizedFileIDsArray)) 
					{
					// We now need to remove the FileID from the AuthorizedFileIDs column for this associate. Loop through the $AuthorizedFileIDsArray and unset the offending item . Use a custom remove_item_by_value() function (definied above) for removing an item from an array by known value courtesy: http://dev-tips.com/featured/remove-an-item-from-an-array-by-value.
					$newAuthorizedFileIDsArray = remove_item_by_value($AuthorizedFileIDsArray, $_SESSION['FileID']); // Call the custom remove_item_by_value() function.
					$updatedAuthorizedFileIDsString = implode(',', $newAuthorizedFileIDsArray); // Convert the $newAuthorizedFileIDsArray array into a string in readiness for entry into the $associates_table.
					}
				else
					{
					// The AuthorizedFileIDsArray doesn't contain $_SESSION['FileID'] so we don't need to remove it from the AuthorizedFileIDs column in the associates_table. Leave the value to be entered into the associates_table unchanged.
					$updatedAuthorizedFileIDsString = $row['AuthorizedFileIDs'];
					}	
				}
			else
				{
				// The AuthorizedFileIDs column in the associates_table is empty already so there's no need to worry about removing the FileID from it. Set the value to be entered to blank.
				$updatedAuthorizedFileIDsString = '';
				}
			}
		// Having now determined the appropriate value of $updatedAuthorizedFileIDsString, update associates_table with that value for the current AssociateID row in the loop.
		$query1 = "UPDATE associates_table SET AuthorizedFileIDs = '".$updatedAuthorizedFileIDsString."' WHERE AssociateID = ".$row['AssociateID'];
		$result = mysql_query($query1) or die('Query (update associates_table for AuthorizedFileIDs) failed: ' . mysql_error().' and the database query string was: '.$query);
		}

	// Implementation of (B): If the $Associates array contains that particular AssociateID (i.e. the logged in Owner checked the check-box next to this particular associate) then a row should exist within assign_table for this pairing of an AssociateID and a FileID (stored as $_SESSION['FileID']). Examine whether such a row already exists and insert one if necessary. If, on the other hand, the $Associates array doesn't contain the Associate for the associates_table row in the loop (i.e. the user didn't checked the check-box next to this particular associate), then a row should not exist within assign_table for this pairing of an AssociateID and a FileID. Examine whether such a row already exists and delete it if necessary.
	// Note: I ran a very similar query and loop just above. I'm running it again, sacrificing a little speed for programming simplicity/transparency.
	$query = "SELECT AssociateID FROM associates_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
	$result2 = mysql_query($query) or die('Query (select AssociateID from associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	while ($row = mysql_fetch_assoc($result2))
		{
		if (!empty($Associates) && in_array($row['AssociateID'], $Associates)) // The check-box for this associate was checked
			{
			// Examine whether assign_table already has a row for AssociateID == $row['AssociateID'] and FileID == $_SESSION['FileID']
			$query = "SELECT COUNT(*) FROM assign_table WHERE AssociateID = ".$row['AssociateID']." AND FileID = ".$_SESSION['FileID'];
			$result = mysql_query($query) or die('Query (my second select COUNT * from assign_table) failed: ' . mysql_error().' and the database query string was: '.$query);
			$line = mysql_fetch_row($result);
			$rowAlreadyExists = ($line[0] >= 1 ? true : false);
			if ($rowAlreadyExists)
				{
				// No action necessary
				}
			else
				{
				// Insert a row into assign_table for this $_SESSION['AssociateID'] and $row['FileID'] pair.
				$query = "INSERT INTO assign_table SET AssociateID = ".$row['AssociateID'].", FileID = ".$_SESSION['FileID'].", AssignDate = NOW()";
				$result = mysql_query($query) or die('Query (my second insert into assign_table) failed: ' . mysql_error().' and the database query string was: '.$query);
				}
			}
		else // The media AssociateID is not found in the $Associates array and hence we conclude that the check-box for this associate was not checked
			{
			// Examine whether assign_table already has a row for AssociateID == $row['AssociateID'] and FileID == $_SESSION['FileID']
			$query = "SELECT COUNT(*) FROM assign_table WHERE AssociateID = ".$row['AssociateID']." AND FileID = ".$_SESSION['FileID'];
			$result = mysql_query($query) or die('Query (my second select COUNT * from assign_table) failed: ' . mysql_error().' and the database query string was: '.$query);
			$line = mysql_fetch_row($result);
			$rowAlreadyExists = ($line[0] >= 1 ? true : false);
			if ($rowAlreadyExists)
				{
				// Delete the row from assign_table for this $row['AssociateID'] and $_SESSION['FileID'] pair.
				$query = "DELETE FROM assign_table WHERE AssociateID = ".$row['AssociateID']." AND FileID = ".$_SESSION['FileID'];
				$result = mysql_query($query) or die('Query (my second delete from assign_table) failed: ' . mysql_error().' and the database query string was: '.$query);
				}
			else
				{
				// No action necessary
				}
			}
		}

	// Unset $_SESSION['FileID'] and $_SESSION['FileSelected']
	unset($_SESSION['FileID']); // Prevent unwanted preselection of a media file radio button on a subsequent visit to assign.php
	unset($_SESSION['FileSelected']); // Prevent unwanted display of the "Step 2:..." right-hand panel upon a subsequent visit to assign.php
	}

if (isset($DeleteAssociate)) // The logged in Owner (user) clicked the delete icon (actually a graphical submit button) next to one of the associate names (whose AssociateID is identified by $DeleteAssociate).
	{
	// Loop through the media_table, removing the $DeleteAssociate AssociateID if found in the AuthorizedAssociateIDs column for every row in the table associated with the Owner.
	$query = "SELECT FileID, AuthorizedAssociateIDs FROM media_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
	$result = mysql_query($query) or die('Query (select FileID, AuthorizedAssociateIDs from media_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	while ($row = mysql_fetch_assoc($result))
		{
		$AuthorizedAssociateIDsArray = explode(',', $row['AuthorizedAssociateIDs']); // Convert the column's string value into an array to ensure valid value searching (e.g. don't make the mistake of a false positive by looking for an AssociateID of, say, 3 and mistakenly "finding" it as part of, say, an AssociateID of 13 or 23
		$newAuthorizedAssociateIDsArray = remove_item_by_value($AuthorizedAssociateIDsArray, $DeleteAssociate);
		$AuthorizedAssociateIDsString = implode(',', $newAuthorizedAssociateIDsArray);
		$query1 = "UPDATE media_table SET AuthorizedAssociateIDs = '".$AuthorizedAssociateIDsString."' WHERE FileID = ".$row['FileID']; // Update the AuthorizedAssociateIDs column for this row of the media_table.
		$result1 = mysql_query($query1) or die('Query (update AuthorizedAssociateIDs within media_table) failed: ' . mysql_error().' and the database query string was: '.$query1);
		}

	// Select the value of the AssociateIDs column in owners_table, then remove the $DeleteAssociate AssociateID from this column.
	$query = "SELECT AssociateIDs FROM owners_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
	$result = mysql_query($query) or die('Query (select AssociateIDs from owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	$row = mysql_fetch_assoc($result);

	$AssociateIDsArray = explode(',', $row['AssociateIDs']); // Convert the column's string value into an array to ensure valid value searching (e.g. don't make the mistake of a false positive by looking for an AssociateID of, say, 3 and mistakenly "finding" it as part of, say, an AssociateID of 13 or 23
	$newAssociateIDsArray = remove_item_by_value($AssociateIDsArray, $DeleteAssociate);
	$AssociateIDsString = implode(',', $newAssociateIDsArray);
	$query2 = "UPDATE owners_table SET AssociateIDs = '".$AssociateIDsString."' WHERE OwnerID = ".$_SESSION['LoggedInOwnerID']; // Update the AssociateIDs column for this row of the owners_table.
	$result2 = mysql_query($query2) or die('Query (update AssociateIDs within owners_table) failed: ' . mysql_error().' and the database query string was: '.$query2);
	
	// Having cleaned up the media_table and owners_table, we can now delete from the associates_table the account whose AssociateID == $DeleteAssociate...
	// ... but first retrieve and store the name of the associate who is to be deleted for use in the confirmation/success message displayed on the screen after the deletion (below).
	$query = "SELECT AssociateName FROM associates_table WHERE AssociateID = ".$DeleteAssociate;
	$result = mysql_query($query) or die('Query (select AssociateName from associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	$row = mysql_fetch_assoc($result);
	$deletedAssociateName = $row['AssociateName'];
	
	// ... Now we can delete the entire associate's row from associates_table.
	$query = "DELETE FROM associates_table WHERE AssociateID = ".$DeleteAssociate;
	$result = mysql_query($query) or die('Query (deletion of the account from associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	
	// Finally, delete from assign_table any rows where AssociateID == $DeleteAssociate
	$query = "DELETE FROM assign_table WHERE AssociateID = ".$DeleteAssociate;
	$result = mysql_query($query) or die('Query (deletion of the row from assign_table) failed: ' . mysql_error().' and the database query string was: '.$query);

	// To prevent a premature display of either the "Assign media items to associate" screen or the "Assign associates to a media item" screen upon return to the assign.php page after an account deletion, unset $_SESSION['AssociateSelected'] and $_SESSION['FileSelected'] session variables (which get set in assign_slave.php). Also, to prevent an unwanted preset of the radio button next to the associates in assign.php (for an "Assign media items to associate" operation) or the radio button next to the media items in assign.php (for an "Assign associates to a media item" operation), when those screens are displayed via a user click on the picture icon in assign.php, unset $_SESSION['AssociateID'] and $_SESSION['FileID'] session variables that are set in assign_slave.php.
	unset($_SESSION['AssociateSelected']);
	unset($_SESSION['FileSelected']);
	unset($_SESSION['AssociateID']);
	unset($_SESSION['FileID']);
	}

if (isset($DeleteMediaFile)) // The user clicked the delete icon (actually a graphical submit button) next to one of the media file names (whose FileID is identified by $DeleteMediaFile.
	{
	// Loop through the associates_table, removing the $DeleteMediaFile FileID if found in the AuthorizedFileIDs column for every row in the table associated with the Owner.
	$query = "SELECT AssociateID, AuthorizedFileIDs FROM associates_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
	$result = mysql_query($query) or die('Query (select AssociateID, AuthorizedFileIDs from associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	while ($row = mysql_fetch_assoc($result))
		{
		$AuthorizedFileIDsArray = explode(',', $row['AuthorizedFileIDs']); // Convert the column's string value into an array to ensure valid value searching (e.g. don't make the mistake of a false positive by looking for a FileID of, say, 10 and mistakenly "finding" it as part of, say, a FileID of 102.
		$newAuthorizedFileIDsArray = remove_item_by_value($AuthorizedFileIDsArray, $DeleteMediaFile);
		$AuthorizedFileIDsString = implode(',', $newAuthorizedFileIDsArray);
		$query1 = "UPDATE associates_table SET AuthorizedFileIDs = '".$AuthorizedFileIDsString."' WHERE AssociateID = ".$row['AssociateID']; // Update the AuthorizedFileIDs column for this row of the associates_table.
		$result1 = mysql_query($query1) or die('Query (update AuthorizedFileIDs within associates_table) failed: ' . mysql_error().' and the database query string was: '.$query1);
		}

	// Before we delete the media file row from the media_table where FileID == $DeleteMediaFile...
	// ... first retrieve and store the Filename, Snapshot, VideoSnapshot and QueryString column values for the file that is to be deleted so we can (i) use Filename in the confirmation/success message displayed on the screen after the deletion (below), and then (ii) delete the media file, snapshot file, and VideoSnapshot file (applicable only when MediaClass == 'video') from the server to free up storage, and (iii) delete the sharelink unique .php page e.g. qdfugt8j2a.php.
	$query = "SELECT Filename, Snapshot, VideoSnapshot, QueryString, MediaClass FROM media_table WHERE FileID = ".$DeleteMediaFile;
	$result = mysql_query($query) or die('Query (select Filename, Snapshot, QueryString from media_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	$row = mysql_fetch_assoc($result);
	$deletedFilename = $row['Filename'];
	$deletedSnapshot = $row['Snapshot'];
	$deletedVideoSnapshot = $row['VideoSnapshot'];
	$deletedSharelinkPage = $row['QueryString'].'.php'; // Don't forget the dot (.)!
	$deletedMediaClass = $row['MediaClass'];
	
	// Delete (i.e. unlink in technical PHP parlance) the mediafile that the Account Owner has selected for deletion and the Snapshot file as well as the associated Sharelink web page created from the unique encrypted QueryString....
	// ... but remember that the actual file name in the /media folder will have "_XXX" appended to $deletedFilename, where XXX is the FileID. (We do that to avoid potential conflicts where two different Owners each upload "mypuppy.jpg" and the second upload would overwrite the first file.) So we must construct the actual file name using the following expression:
	$theFilenameFile = substr($deletedFilename, 0, strrpos($deletedFilename, '.')).'_'.$DeleteMediaFile.substr($deletedFilename, strrpos($deletedFilename, '.'));
	@unlink('/home/paulme6/public_html/abridg/media/'.$theFilenameFile); // (The @ symbol suppresses warning messages if the file that we're trying to delete (i.e. unlink) doesn't exist on the server.)
	$theSnapshotFile = substr($deletedSnapshot, 0, strrpos($deletedSnapshot, '.')).'_'.$DeleteMediaFile.substr($deletedSnapshot, strrpos($deletedSnapshot, '.')); // Note: in the case of documents (i.e. MediaClass == 'application'), and audio items (i.e. MediaClass == 'audio'), which are all given a generic snapshot, the value of $theSnapshotFile will be something like "generic-document_XXX.png" or "generic-audio_XXX.png". Such a file (with the "_XXX" suffix) won't actually exist on the server so the unlink'ing won't have any effect. The "generic-document.png" and "generic-audio" file will remain on the server as desired, and the @ symbol below will suppress a warning message.
	@unlink('/home/paulme6/public_html/abridg/snapshots/'.$theSnapshotFile);
	@unlink('/home/paulme6/public_html/abridg/'.$deletedSharelinkPage);

	// In the same vein, it's also appropriate to delete (i.e. unlink in technical PHP parlance) the associated composite file stored as a VideoSnapshot when MediaClass == 'video'. (Because every media file, snapshot file, and videosnapshot file on the server is stored with a unique _FileID appended to the file name, we don't have to worry about deleting a file that's used elsewhere. However, in the case of a MediaClass == 'video', we must never delete the snapshot file from the /snapshots directory if $deletedVideoSnapshot is "jwplayerframe.png" because that's a generic file used as the default whenever the user doesn't bother to upload a snapshot for a video file.)
	if ($deletedMediaClass == 'video' && $deletedVideoSnapshot != 'jwplayerframe.png')
		{
		// Again, just as above, before unlink'ing, we first need to construct the actual name of the VideoSnapshot file that's stored on the server, which we do via the following complex statement that just appends _XXX into $deletedVideoSnapshot.
		$theVideoSnapshotFile = substr($deletedVideoSnapshot, 0, strrpos($deletedVideoSnapshot, '.')).'_'.$DeleteMediaFile.substr($deletedVideoSnapshot, strrpos($deletedVideoSnapshot, '.'));
		@unlink('/home/paulme6/public_html/abridg/snapshots/'.$theVideoSnapshotFile);
		}
	
	// Now we can delete the entire media file row from media_table.
	$query = "DELETE FROM media_table WHERE FileID = ".$DeleteMediaFile;
	$result = mysql_query($query) or die('Query (deletion of the media file from media_table) failed: ' . mysql_error().' and the database query string was: '.$query);

	// Finally, delete from assign_table any rows where AssociateID == $DeleteAssociate
	$query = "DELETE FROM assign_table WHERE FileID = ".$DeleteMediaFile;
	$result = mysql_query($query) or die('Query (this deletion of the row from assign_table) failed: ' . mysql_error().' and the database query string was: '.$query);

	// To prevent a premature display of either the "Assign media items to account holder" screen or the "Assign account holders to a media item" screen upon return to the assign.php page after a media item deletion, unset $_SESSION['AssociateSelected'] and $_SESSION['FileSelected'] session variables (which get set in assign_slave.php). Also, to prevent an unwanted preset of the radio button next to the account holders in assign.php (for an "Assign media items to account holder" operation) or the radio button next to the media items in assign.php (for an "Assign account holders to a media item" operation), when those screens are displayed via a user click on the picture icon in assign.php, unset $_SESSION['AssociateID'] and $_SESSION['FileID'] session variables that are set in assign_slave.php.
	unset($_SESSION['AssociateSelected']);
	unset($_SESSION['FileSelected']);
	unset($_SESSION['AssociateID']);
	unset($_SESSION['FileID']);
	}

/* Send alerts if the logged in Owner selected "Send alerts automatically upon assignment" in one of the manage.php HTML forms (that's initially processed by managealerts_slave.php). */
if (isset($AssignMediaFilesToAssociate) || isset($AssignAssociatesToMediaFile)) 
	{
	/* Determine whether we should send any alerts upon logout. */

	// First check whether logged in Owner's AlertType == 'auto_onassign' in owners_table
	$query = "SELECT AlertType from owners_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
	$result = mysql_query($query) or die('Query (select AlertType from owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	$row = mysql_fetch_assoc($result);
	if ($row['AlertType'] == 'auto_onassign')
		{
		alertgenerator(NULL); // Call this function, defined in include'd file alertgenerator.php, with input parameter set to NULL to indicate that alert generation should not be restricted to a subset of associate IDs.
		}
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Assign Slave Script</title>
<link href="/abridg.css" rel="stylesheet" type="text/css">
</head>
<body>
<?php
/* Handle redirection from this slave script. If the user merely clicked one of the radio buttons (i.e.. the Associate radio button or the MediaFile radio button in assign.php) in order to identify one associate or one Media File, simply redirect back to assign.php automatically. If user clicked another button (specifically, the 'AssignMediaFilesToAssociate' button, the 'AssignAssociatesToMediaFile' button, or a delete icon for deleting an Account Holder or a media file [in which case $DeleteAssociate or $DeleteMediaFile will be set]), display a confirmation message that the action was successful and present buttons that the user (Owner) can click to go to either the home page or continue with other administration tasks.
   Note that clicks to either the Edit Account icon or the Edit Media File icon ($EditAssociate or $EditMediaFile) are handled much earlier in assign_slave.php as simple redirects to editassociate.php and editmedia.php via PHP's header() function. */
if (isset($AssignMediaFilesToAssociate) || isset($AssignAssociatesToMediaFile) || isset ($DeleteAssociate) || isset ($DeleteMediaFile)) 
	{
?>
	<table cellpadding="0" cellspacing="0" style="margin-top: 50px; margin-left: auto; margin-right: auto; position: relative; left: -7px;">
	<tr>
	<td style="text-align: left;">
	<p class='text' style='margin-left: 150px; margin-right: 150px; margin-top: 40px; margin-bottom: 20px;'>
	<?php
	if (isset($AssignMediaFilesToAssociate)) echo 'Congratulations! The assignment of media to '.$_SESSION['AssociateNameSelected'].' was successful.';
	if (isset($AssignAssociatesToMediaFile)) echo 'Congratulations! Your assignment of '.$_SESSION['FilenameSelected'].' was successful.';
	if (isset($DeleteAssociate)) echo $deletedAssociateName.' has been deleted.';
	if (isset($DeleteMediaFile)) echo 'The file <em>'.$deletedFilename.'</em> has been deleted.';
	?>
	</p>
	</td>
	<tr>
	<td style="text-align: center;">
	<form method="post" action="/index.php" style="display: inline;">
	<input type="submit" class="buttonstyle"  name="galleryview" value="Media Gallery" style="text-align: center;">
	</form>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<form method="post" action="/assign.php" style="display: inline;">
	<input type="submit" class="buttonstyle" value="Continue Administration" style="text-align: center;">
	</form> 
	</td>
	</tr>
	</table>

<?php
	unset($AssignMediaFilesToAssociate);
	unset($AssignAssociatesToMediaFile);
	unset($DeleteAssociate);
	unset($DeleteMediaFile);
	}
else // Executed if the user clicked the 'Associate' or 'MediaFile' radio button in assign.php.
	{
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
	}
ob_end_flush();
exit;
?>