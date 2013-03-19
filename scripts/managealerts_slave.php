<?php
/*
managealerts_slave.php is one of the action scripts called by more than one of the forms in manage.php. It pertains to the logged in Owner's (i.e. Administrator's) selection of a favored Alert Type (1. Send alerts automatically on logout; 2. Send alerts automatically upon assignment; 3. Send alerts on a specified hour; 4. Manually send alerts to specified friend(s); or 5. Don't send alerts).
	In ALL cases, managealerts_slave.php updates the value of AlertType in owners_table. But thereafter, the action and execution timing of the action differs.
	Re. "1. Send alerts automatically on logout": the action will take place within logout.php, which calls alertgenerator() function in alertgenerator.php.
	Re. "2. Send alerts automatically upon assignment": action for legacy assignments shall take place here within managealerts_slave.php. Action for subsequent assignments will take place upon processing of the assignment in either assign_slave.php or upload_slave.php, which call alertgenerator() function in alertgenerator.php with input parameter set to NULL to indicate that alert generation should not be restricted to a subset of associate IDs..
	Re. "3. Send alerts on a specified hour": the action will take place here within managealerts_slave.php by rewriting one of the hourly cron script files. Or, better?, save the user's requested hour as a two-digit suffix XX in form "auto_onhour_XX" in the AlertType column of owners_table, then have a cron script (executed hourly) query that column to help figure out which alerts should be sent at this hour. Regardless, I've chosen not to offer this option to the logged in Owner for the present. Without sniffing the logged in Owner's three-letter time zone (e.g. MST) and using a lot of complex switch statements while keeping track of when various regions change their clocks, it's problematic to implement a feature that let's the user specify an hour in his/her local time on which alerts are to be automatically sent.
	Re. "4. Manually send alerts now to specified friend(s)": the action will take place here within managealerts_slave.php, which calls the alertgenerator($assocsarray) function in alertgenerator.php.
	Re. "5. Don't send alerts": the action will take place here within managealerts_slave.php, including making sure none of the other actions still happen for this logged in Owner.
*/

// Start a session
session_start();

if ($_SESSION['Authenticated'] != 'true') header("Location: /index.php");

ob_start(); // Used in conjunction with ob_flush() [see www.tutorialized.com/view/tutorial/PHP-redirect-page/27316], this allows me to postpone issuing output to the screen until after the header has been sent.

// Connect to mysql
$db = mysql_connect('localhost', 'paulme6_merlyn', 'fePhaCj64mkik')
or die('Could not connect: ' . mysql_error());
mysql_select_db('paulme6_abridg') or die('Could not select database: ' . mysql_error());

require('../ssi/alertgenerator.php'); // Include the alertgenerator.php file, which contains the alertgenerator() function for generating alert email messages to inform an associate that a Content Producer (in this case, the logged in Owner) has assigned new content to him/her.

// Create short variable names
$AlertTypeSelector = $_POST['AlertTypeSelector'];
$Associates = $_POST['Associates']; // This is an array submitted in manage.php in connection with "4. Manually send alerts now to specified friend(s)"
$SendAlertsNowButton = $_POST['SendAlertsNowButton']; // Submit button

// Update AlertType in owners_table to the newly selected value.
if (isset($AlertTypeSelector))
	{
	$query = "UPDATE owners_table SET AlertType = '".$AlertTypeSelector."' WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
	$result = mysql_query($query) or die('Query (update AlertType in owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	}

/* As stated above re. "2. Send alerts automatically upon assignment": action for legacy assignments shall take place here within managealerts_slave.php. (Action for subsequent assignments takes place upon processing of the assignment in either assign_slave.php or upload_slave.php, which call alertgenerator(NULL) function in alertgenerator.php. Note that the input parameter is set to NULL to indicate that alert generation should not be restricted to a subset of associate IDs.) */

// First check whether logged in Owner's AlertType == 'auto_onassign' in owners_table
if ($AlertTypeSelector == 'auto_onassign')
	{
	alertgenerator(NULL); // Call this function, defined in include'd file alertgenerator.php
	}

/* As stated above re. "4. Manually send alerts now to specified friend(s)": the action will take place here within managealerts_slave.php, which calls the alertgenerator($assocsarray) function in alertgenerator.php. In this situation, the input parameter is an array of AssociateID values to limit generation of alert email recipients to a subset of all the logged in Owner's associates.*/
if (isset($SendAlertsNowButton))
	{
	// First set the AlertType column in owners_table to 'manual_dont' (NOT 'manual_now'). That's because a selection of 'manual_now' should actually get stored in the DB table as manual_dont ready for a susequent selection by the Administrator. */
	$query = "UPDATE owners_table SET AlertType = 'manual_dont' WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
	$result = mysql_query($query) or die('Query (update AlertType to manual_dont in owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	
	// Next, set the $AlertTypeSelector variable to either 'manual_now' or to 'manual_now_but_none' so that the correct screen confirmation message gets displayed below via the switch() statement.
	if (count($Associates) > 0)
		{
		$AlertTypeSelector = 'manual_now';
		}
	else
		{
		$AlertTypeSelector = 'manual_now_but_none';
		};
	
	// Finally, call the include'd function alertgenerator() for the particular AssociateID values in the $Associates array...
	if (count($Associates) > 0) alertgenerator($Associates); // No point in calling alertgenerator() if the user didn't check any of the check-boxes besides a listed associate in manage.php.
	unset($SendAlertsNowButton);	// ... and unset as good housekeeping
	unset($Associates);
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Abridg&trade; | Connection 2.0&trade;</title>
<link href="/abridg.css" rel="stylesheet" type="text/css">
</head>
<body>
<table cellpadding="0" cellspacing="0" style="margin-top: 50px; margin-left: auto; margin-right: auto; position: relative; left: -7px;">
<tr>
<td style="text-align: left;">
<p class='text' style='margin-left: 150px; margin-right: 150px; margin-top: 40px; margin-bottom: 20px;'>
<?php
switch ($AlertTypeSelector)
	{
	case 'auto_onassign' :
		echo 'An alert will be sent one time only when you assign media items to friends. (Your friends won&rsquo;t receive any additional alerts until they&rsquo;ve logged into their account <em>and</em> you&rsquo;ve assigned them additional content.)';
		break;
	case 'auto_onlogout' :
		echo 'An alert will be sent one time only when you next log out.';
		break;
	/* 'auto_onhour' ISN'T IMPLEMENTED AT THE PRESENT TIME
	case 'auto_onhour' :
		echo 'Any pending alerts will next be sent at ____. Your friends will not receive any additional alerts unless and until a friend has logged into his/her account <em>and</em> you&rsquo;ve assigned him/her additional content.';
		break;
	*/
	case 'manual_now' :
		echo 'The requested alerts have now been sent.<br /><br />Your friends won&rsquo;t receive any additional alerts unless you manually send them or select one of the automatic alert options.';
		break;
	case 'manual_now_but_none' :
		echo 'You didn&rsquo;t select any friends to whom an alert should be sent.<br /><br />Your friends won&rsquo;t receive an alert until you either send alerts manually or choose one of the automatic alert options.';
		break;
	case 'manual_dont' :
		echo 'No alerts will be sent to your friends. (This setting isn&rsquo;t recommended because it limits your ability to share with friends. Only friends who have their own Abridg accounts (and who log into their accounts) will discover the content you share with them.)';
		break;
	}
unset($AlertTypeSelector);
?>
</p>
</td>
<tr>
<td style="text-align: center;">
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
</body>
</html>