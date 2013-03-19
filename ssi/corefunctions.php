<?php
/*
The heavy-duty work of OwnerDestroyer($theOwnerID) comprises: (1) delete (unlink) from the server every media item and snapshot and videosnapshot and unique querystring .php page associated with the destroyable owner and his/her media items; (2) delete all rows in assign_table where the AssociateID column contains a value found in the AssociateIDs field of owners_table for this Owner; (3) delete all media items in media_table associated with the OwnerID; (4) delete all account holders in associates_table associated with the OwnerID; (5) "unwind" (i.e. remove or return to their default values) the existing values of OwnerFirstName, OwnerLastName, OwnerOrganization, OwnerLabel, OwnerPassword, AssociateIDs, OwnerDofAdmission, OwnerLastLogin, and AlertType in the row of owners_table corresponding to that Owner(s).
*/
function OwnerDestroyer($theOwnerID)
	{
	/* Step 1: delete (unlink) from the server every media item and snapshot and videosnapshot and unique querystring .php page associated with the destroyable owner and his/her media items */
	// Select all media items from media_table whose OwnerID column is equal to $theOwnerID
	$query = "SELECT Filename, Snapshot, VideoSnapshot, MediaClass, QueryString, FileID FROM media_table WHERE OwnerID = ".$theOwnerID;
	$result = mysql_query($query) or die('Query (select Filename, Snapshot, VideoSnapshot, MediaClass, QueryString, FileID from media_table has failed: ' . mysql_error());
	while ($row = mysql_fetch_assoc($result))
		{
		// The actual file name in the /media folder will have "_XXX" appended to $row['Filename'], where XXX is the FileID. (We did that to avoid potential conflicts where two different Owners each uploaded "mypuppy.jpg" and the second upload would have overwritten the first file.) So we must construct the actual file name using the following expression:
		$theFilenameFile = substr($row['Filename'], 0, strrpos($row['Filename'], '.')).'_'.$row['FileID'].substr($row['Filename'], strrpos($row['Filename'], '.'));
		@unlink('/home/paulme6/public_html/abridg/media/'.$theFilenameFile); // (The @ symbol suppresses warning messages if the file that we're trying to delete (i.e. unlink) doesn't exist on the server.)
		// The actual snapshot file name in the /snapshots folder will have "_XXX" appended to $row['Snapshot'], where XXX is the FileID. (We did that to avoid potential conflicts where two different Owners each uploaded "mypuppy.jpg" and the second upload would have overwritten the first file.) So we must construct the actual file name using the following expression:
		if (!empty($row['Snapshot']))
			{
			$theSnapshotFile = substr($row['Snapshot'], 0, strrpos($row['Snapshot'], '.')).'_'.$row['FileID'].substr($row['Snapshot'], strrpos($row['Snapshot'], '.'));
			@unlink('/home/paulme6/public_html/abridg/snapshots/'.$theSnapshotFile); // (The @ symbol suppresses warning messages if the file that we're trying to delete (i.e. unlink) doesn't exist on the server.)
			}
		// Also (relevant for MediaClass == 'video' only), delete a file in the /snapshots directory associated with the composite VideoSnapshot column image.
		if ($row['MediaClass'] == 'video')
			{
			$theVideoSnapshotFile = substr($row['VideoSnapshot'], 0, strrpos($row['VideoSnapshot'], '.')).'_'.$row['FileID'].substr($row['VideoSnapshot'], strrpos($row['VideoSnapshot'], '.'));
			@unlink('/home/paulme6/public_html/abridg/snapshots/'.$theVideoSnapshotFile);
			}
		// Lastly, unlink the Sharelink page
		@unlink('/home/paulme6/public_html/abridg/'.$row['QueryString'].'.php');
		}
	
	/* Step 2: delete all rows in assign_table where the AssociateID column contains a value found in the AssociateIDs field of owners_table for this Owner */
	// First select the value of the AssociateID column, which will be a string -- a comma-separated list of AssociateIDs (e.g. 6,17,21,22)
	$query = "SELECT AssociateIDs FROM owners_table WHERE OwnerID = ".$theOwnerID;
	$result = mysql_query($query) or die('Query (select AssociateIDs from owners_table has failed: ' . mysql_error());
	$row = mysql_fetch_assoc($result);
	$theAssociateIDs = $row['AssociateIDs'];

	// Second, delete from assign_table using IN condition
	$query = "DELETE FROM assign_table WHERE AssociateID IN (".$theAssociateIDs.")";
	$result = mysql_query($query) or die('Query (delete from assign_table has failed: ' . mysql_error());
	
	/* Step 3: delete all media items in media_table associated with the OwnerID */
	$query = "DELETE FROM media_table WHERE OwnerID = ".$theOwnerID;
	$result = mysql_query($query) or die('Query (delete from media_table has failed: ' . mysql_error());

	/* Step 4: delete all associates in associates_table associated with the OwnerID */
	$query = "DELETE FROM associates_table WHERE OwnerID = ".$theOwnerID;
	$result = mysql_query($query) or die('Query (delete from associates_table has failed: ' . mysql_error());
	
	/* Step 5:  "unwind" (i.e. remove or return to their default values) the existing values of OwnerLabel, OwnerFirstName, OwnerLastName, OwnerOrganization, OwnerPassword, AssociateIDs, OwnerDofAdmission, OwnerLastLogin, and AlertType in the row of owners_table corresponding to that Owner(s). */
	// ... but before performing that update operation, first obtain the name of the Owner from the OwnerFirstName, OwnerLastName, OwnerOrganization, and OwnerLabel column values (all of which are populated via form fields in createowner.php) for use in the confirmation/success screen message below.
	$query = "SELECT OwnerFirstName, OwnerLastName, OwnerOrganization, OwnerLabel, OwnerUsername FROM owners_table WHERE OwnerID = ".$theOwnerID;
	$result = mysql_query($query) or die('Query (select OwnerFirstName, OwnerLastName, OwnerOrganization from owners_table has failed: ' . mysql_error());
	$row = mysql_fetch_assoc($result);
	$theOwnerFirstName = $row['OwnerFirstName'];
	$theOwnerLastName = $row['OwnerLastName'];
	$theOwnerOrganization = $row['OwnerOrganization'];
	$theOwnerLabel = $row['OwnerLabel'];
	$theOwnerUsername = $row['OwnerUsername']; // never blank.
	global $TheName; // Make the scope global so $TheName can also be used in the success/confirmation message outside the OwnerDestroyer function().
	if (!empty($theOwnerFirstName) AND !empty($theOwnerLastName) AND !empty($theOwnerOrganization))	$TheName = $theOwnerFirstName.' '.$theOwnerLastName.' ('.$theOwnerOrganization.')';
	else if (empty($theOwnerOrganization) && empty($theOwnerLastName) && (!empty($theOwnerFirstName) || !empty($theOwnerLabel))) $TheName = $theOwnerFirstName.' ('.$theOwnerLabel.')';
	else if (empty($theOwnerOrganization) && empty($theOwnerFirstName) && (!empty($theOwnerLastName) || !empty($theOwnerLabel))) $TheName = $theOwnerLastName.' ('.$theOwnerLabel.')';
	else if (empty($theOwnerOrganization) && (!empty($theOwnerFirstName) || !empty($theOwnerLastName) || !empty($theOwnerLabel))) $TheName = $theOwnerFirstName.' '.$theOwnerLastName.' ('.$theOwnerLabel.')';
	else if (!empty($theOwnerFirstName) && !empty($theOwnerOrganization)) $TheName = $theOwnerFirstName.' ('.$theOwnerOrganization.')';
	else if (!empty($theOwnerOrganization)) $TheName = $theOwnerOrganization.' ('.$theOwnerLabel.')';
	else if (!empty($theOwnerLastName) && !empty($theOwnerOrganization)) $TheName = $theOwnerLastName.' ('.$theOwnerOrganization.')';
	// The only other possible situation is that OwnerFirstName, OwnerLastName, OwnerOrganization are all empty, in which case we must assgin $TheName to theOwnerLabel if it's non-blank...
	else if (!empty($theOwnerLabel)) $TheName = theOwnerLabel; 
	// ... or if $theOwnerLabel is blank, then assign $TheName to $theOwnerUsername.
	else $TheName = $theOwnerUsername;

	// Now perform the "unwind" (update) operation
	$query = "UPDATE owners_table SET OwnerLabel = null, OwnerFirstName = null, OwnerLastName = null, OwnerOrganization = null, OwnerPassword = null, AssociateIDs = null, OwnerDofAdmission = '0000-00-00', OwnerLastLogin = '1900-01-01 12:00:00', AlertType = 'auto_onlogout' WHERE OwnerID = ".$theOwnerID;
	$result = mysql_query($query) or die('Query (update of owners_table has failed: ' . mysql_error());

	return true;
	}
?>