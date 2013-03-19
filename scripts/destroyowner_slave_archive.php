<?php
/*
destroyowner_slave.php is the slave script for destroyowner.php (which allows a Superadministrator to select one radio button to designate an Owner in owners_table for destruction). To promote reusability (anticipating the Owner destruction will eventually be an automated process, invoked, say, by a cron job after an Owner has, say, failed to renew his/her subscription), I've placed most of the functionality of destroyowner_slave.php inside a PHP function OwnerDestroyer(). The heavy-duty work comprises: (1) delete (unlink) from the server every media item and snapshot and videosnapshot and unique querystring .php page associated with the destroyable owner and his/her media items; (2) delete all rows in assign_table where the AssociateID column contains a value found in the AssociateIDs field of owners_table for this Owner; (3) delete all media items in media_table associated with the OwnerID; (4) delete all account holders in associates_table associated with the OwnerID; (5) "unwind" (i.e. remove or return to their default values) the existing values of OwnerFirstName, OwnerLastName, OwnerOrganization, OwnerLabel, OwnerPassword, AssociateIDs, OwnerDofAdmission, OwnerLastLogin, and AlertType in the row of owners_table corresponding to that Owner(s).
*/

// Start a session
session_start();

if ($_SESSION['ValidatedSuperAdmin'] != 'true') exit;

ob_start(); // Used in conjunction with ob_flush() [see www.tutorialized.com/view/tutorial/PHP-redirect-page/27316], this allows me to postpone issuing output to the screen until after the header has been sent.

// Connect to mysql
$db = mysql_connect('localhost', 'paulme6_merlyn', 'fePhaCj64mkik')
or die('Could not connect: ' . mysql_error());
mysql_select_db('paulme6_abridg') or die('Could not select database: ' . mysql_error());

// Create short variable names for situation where an account holder is first selected before various media files are assigned to him/her.
$OwnerID = $_POST['Owner']; // The value (i.e. OwnerID) of the selected radio button in destroyowner.php when user is selecting one Owner for destruction.

// Define a custom function for later use below.
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
	
	/* Step 5:  delete the row of owners_table corresponding to that Owner(s) */
	// ... but before performing that deletion, first obtain the name of the Owner from the OwnerFirstName, OwnerLastName, OwnerOrganization, and OwnerLabel column values (all of which are populated via form fields in createowner.php) for use in the confirmation/success screen message below.
	$query = "SELECT OwnerFirstName, OwnerLastName, OwnerOrganization, OwnerLabel FROM owners_table WHERE OwnerID = ".$theOwnerID;
	$result = mysql_query($query) or die('Query (select OwnerFirstName, OwnerLastName, OwnerOrganization from owners_table has failed: ' . mysql_error());
	$row = mysql_fetch_assoc($result);
	$theOwnerFirstName = $row['OwnerFirstName'];
	$theOwnerLastName = $row['OwnerLastName'];
	$theOwnerOrganization = $row['OwnerOrganization'];
	$theOwnerLabel = $row['OwnerLabel'];
	global $TheName; // Make the scope global so $TheName can also be used in the success/confirmation message outside the OwnerDestroyer function().
	if (!empty($theOwnerFirstName) AND !empty($theOwnerLastName) AND !empty($theOwnerOrganization))	$TheName = $theOwnerFirstName.' '.$theOwnerLastName.' ('.$theOwnerOrganization.')';
	else if (empty($theOwnerOrganization) && empty($theOwnerLastName)) $TheName = $theOwnerFirstName.' ('.$theOwnerLabel.')';
	else if (empty($theOwnerOrganization) && empty($theOwnerFirstName)) $TheName = $theOwnerLastName.' ('.$theOwnerLabel.')';
	else if (empty($theOwnerOrganization)) $TheName = $theOwnerFirstName.' '.$theOwnerLastName.' ('.$theOwnerLabel.')';
	else if (!empty($theOwnerFirstName) && !empty($theOwnerOrganization)) $TheName = $theOwnerFirstName.' ('.$theOwnerOrganization.')';
	else if (!empty($theOwnerOrganization)) $TheName = $theOwnerOrganization.' ('.$theOwnerLabel.')';
	else if (!empty($theOwnerLastName) && !empty($theOwnerOrganization)) $TheName = $theOwnerLastName.' ('.$theOwnerOrganization.')';
	else $TheName = theOwnerLabel; // The only other possible situation is that OwnerFirstName, OwnerLastName, OwnerOrganization are all empty, in which case we must assgin $TheName to theOwnerLabel.

	$query = "DELETE FROM owners_table WHERE OwnerID = ".$theOwnerID;
	$result = mysql_query($query) or die('Query (delete from owners_table has failed: ' . mysql_error());

	return true;
	}

OwnerDestroyer($OwnerID);
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>destroyowner Slave Script</title>
<link href="/abridg.css" rel="stylesheet" type="text/css">
</head>
<body>

<?php
/* Display a "success" message to confirm that the selected Owner has been destroyed. */
?>
<div style="text-align: center"> <!-- This div provides centering for older browsers incl. NS4 and IE5. (See http://theodorakis.net/tablecentertest.html#intro.) Use of margin-left: auto and margin-right: auto in the style of the table itself (see below) takes care of centering in newer browsers. -->
<form method="post" action="/index.php">
<table cellpadding="0" cellspacing="0" style="margin-top: 50px; margin-left: auto; margin-right: auto; position: relative; left: -7px;">
<tr>
<td style="text-align: left;">
<p class='text' style='margin-left: 150px; margin-right: 150px; margin-top: 40px; margin-bottom: 20px;'>You have successfully removed <?=$TheName; ?> from the Abridg web site.</p>
<p class='text' style='margin-left: 150px; margin-right: 150px; margin-bottom: 60px;'>Click the button below to visit the home page. Alternatively, click <a target='_self' href='/destroyowner.php'>here</a> to remove another owner.</p>
</td>
<tr>
<td style="text-align: center;">
<input type='submit' name='AbridgHome' class='buttonstyle' style="text-align: center;" value='Abridg Home'>
</td>
</tr>
</table>
</form>
</div>
</body>
</html>
<?php
ob_end_flush();
exit;
?>