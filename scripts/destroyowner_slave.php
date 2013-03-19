<?php
/*
destroyowner_slave.php is the slave script for destroyowner.php (which allows a Superadministrator to select one radio button to designate an Owner in owners_table for destruction). To promote reusability (anticipating the Owner destruction will eventually be an automated process, invoked, say, by a cron job after an Owner has, say, failed to renew his/her subscription), I've placed most of the functionality of destroyowner_slave.php inside a PHP function OwnerDestroyer() within include'd file corefunctions.php. The heavy-duty work comprises: (1) delete (unlink) from the server every media item and snapshot and videosnapshot and unique querystring .php page associated with the destroyable owner and his/her media items; (2) delete all rows in assign_table where the AssociateID column contains a value found in the AssociateIDs field of owners_table for this Owner; (3) delete all media items in media_table associated with the OwnerID; (4) delete all account holders in associates_table associated with the OwnerID; (5) "unwind" (i.e. remove or return to their default values) the existing values of OwnerFirstName, OwnerLastName, OwnerOrganization, OwnerLabel, OwnerPassword, AssociateIDs, OwnerDofAdmission, OwnerLastLogin, and AlertType in the row of owners_table corresponding to that Owner(s).
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

require('../ssi/corefunctions.php'); // Include the corefunctions.php file, which contains the OwnerDestroyer($theOwnerID) function.

// Call OwnerDestroyer($OwnerID)
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