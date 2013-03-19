<?php
// Start a session
session_start();

if ($_SESSION['Authenticated'] != 'true') header("Location: /index.php");

// Connect to mysql
$db = mysql_connect('localhost', 'paulme6_merlyn', 'fePhaCj64mkik')
or die('Could not connect: ' . mysql_error());
mysql_select_db('paulme6_abridg') or die('Could not select database: ' . mysql_error());
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Manage Settings</title>
<meta NAME="description" CONTENT="Form to manage settings on the Abridg platform">
<link href="/abridg.css" rel="stylesheet" type="text/css">
<!-- Start: The following javascripts pertain to Trio Solutions's glossary and image preview -->
<link href='/scripts/TSScript/TSContainer.css' rel='stylesheet' type='text/css'>
<script language='JavaScript' type='text/javascript' src='/scripts/TSScript/yahoo.js'></script>
<script language='JavaScript' type='text/javascript' src='/scripts/TSScript/event.js'></script>
<script language='JavaScript' type='text/javascript' src='/scripts/TSScript/dom.js'></script>
<script language='JavaScript' type='text/javascript' src='/scripts/TSScript/dragdrop.js'></script>
<script language='JavaScript' type='text/javascript' src='/scripts/TSScript/animation.js'></script>
<script language='JavaScript' type='text/javascript' src='/scripts/TSScript/container.js'></script>
<script language='JavaScript' type='text/javascript' src='/scripts/TSScript/TSPreviewImage/TSPreviewImage.js'></script>
<link rel='stylesheet' type='text/css' href='/scripts/TSScript/TSGlossary/TSGlossary.css' />
<script language='JavaScript' type='text/javascript' src='/scripts/TSScript/TSGlossary/TSGlossary.js'></script>
<!-- End: Trio Solutions's glossary and image preview -->
<script type='text/javascript' language="JavaScript" src="/scripts/windowpops.js"></script>
<script type="text/javascript">
function checkEmailOnly(theEmailID)
{ 
	var re = /^\s*[\w\-\+_]+(\.[\w\-\+_]+)*\@[\w\-\+_]+\.[\w\-\+_]+(\.[\w\-\+_]+)*\s*$/;
	var theEmailID
	var emailValue = document.getElementById(theEmailID).value;
	var emailLength = emailValue.length;
    if (emailLength > 50 || !re.test(emailValue))
		{
		document.getElementById("EmailError").style.display = "inline"; // This element appears in editaccountdiv
		return false;
		}
	else
		{
		document.getElementById("EmailError").style.display = "none";
		return true;
		}
}

function checkPasswordOnly(thePasswordID)
{
// Validate Password field in 'Sign Up' form.
document.getElementById("PasswordError").style.display = "none";
var thePasswordID;
var passwordValue = document.getElementById(thePasswordID).value;
var passwordLength = passwordValue.length;
illegalCharSet = /[^A-Za-z0-9]+/; // Exclude everything except A-Z, a-z, 0-9.
reqdCharSet = /^(?=.*[A-Za-z])(?=.*[0-9])(?!.*[^A-Za-z0-9])(?!.*\s).{8,20}$/; // Reg exp for a password that must contain at least 8 characters and have at least one number and at least one alphabet. Courtesy: http://www.tek-tips.com/viewthread.cfm?qid=1508574
if passwordValue != 'emc2' && if (illegalCharSet.test(passwordValue) || !reqdCharSet.test(passwordValue) ||  !(passwordLength>=8)) // Note: the exception for 'emc2' is for the Einstein test drive
	{
	document.getElementById("PasswordError").style.display = "inline";
	return false;
	} 
else
	{
	document.getElementById("PasswordError").style.display = "none";
	return true;
	}
}

function checkForm(theEmailID, thePasswordID) 
{
if (!checkEmailOnly(theEmailID) || !checkPasswordOnly(thePasswordID))
	{
	return false; // return false if any one of the individual field validation functions returned a false ...
	}
else 
	{
	return true; // ... otherwise, all individual field validations must have returned a true, so let checkForm() return true.
	}
}

// These next three JS functions (courtesy Mike Foster at http://www.sitepoint.com/forums/showthread.php?434671-Show-hide-table-row-radio-button) enable the show/hide of the Organization row in the 'CreateOwner' HTML table.
function offaddress()
{
  xGetElementsByClassName('collapsible', document, 'tr',
    function(e) {
      e.style.display = 'none';
    }
  );
}
function onaddress()
{
  xGetElementsByClassName('collapsible', document, 'tr',
    function(e) {
      try { e.style.display = 'table-row'; } // DOM
      catch (err) { e.style.display = 'block'; } // IE
    }
  );
}
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xGetElementsByClassName(c,p,t,f)
{
  var found = new Array();
  var re = new RegExp('\\b'+c+'\\b', 'i');
//  var list = xGetElementsByTagName(t, p);
  var list = p.getElementsByTagName(t);
  for (var i = 0; i < list.length; ++i) {
    if (list[i].className && list[i].className.search(re) != -1) {
      found[found.length] = list[i];
      if (f) f(list[i]);
    }
  }
  return found;
}
</script>
</head>
<body>
<div id="main" style="text-align: center; padding: 10px 0px 0px 0px;">
<div id="relwrapper">

<div style="margin-top: 10px; text-align: center; padding: 0px;">
<form method="post" action="/index.php">
<input type="submit" class="submitLinkSmall"  name="galleryview" value="Media Gallery">
</form>
</div>

<div class="gloss" style="font-weight: bold; font-variant: small-caps; margin-top: 24px; color: #E1B378;">Abridg Director</div>
<h1 style="margin-top: 12px; font-size: 22px; color: #9F0251; font-family: 'Century Gothic', Geneva, Arial, sans-serif;">Manage Alerts &amp; Other Settings</h1>
<?php
require('/home/paulme6/public_html/abridg/ssi/adminmenu.php'); // Include the navigation menu.
?>

<div id="managebuttons" style="width: 270px; margin: 40px auto 0px auto; text-align: left; border: 1px solid #E1B378; padding: 10px 5px 10px 10px; <?php if ($_SESSION['ManageEditValidationError'] == 'true') echo 'display: none'; ?>">
<form>
<table cellspacing="0" cellpadding="6" border="0" style="font-size: 14px; font-family: Geneva, Arial, Helvetica, sans-serif; padding: 0px;">
<tr>
<td valign="baseline" width="20">
<input type="radio" name="ManageSelector" id="ManageAlerts" value="manage_alerts" onClick="document.getElementById('managebuttons').style.display = 'none'; document.getElementById('alertsdiv').style.display = 'block'; return false;">
</td>
<td>
<label style="color: #E1B378;">Manage Alerts</label>
</td>
</tr>

<tr>
<td valign="baseline">
<input type="radio" name="ManageSelector" id="EditAccount" value="edit_account" onClick="document.getElementById('managebuttons').style.display = 'none'; document.getElementById('editaccountdiv').style.display = 'block';">
</td>
<td>
<label style="color: #E1B378;">Edit My Account</label>
</td>
</tr>

<tr>
<td valign="baseline">
<input type="radio" name="ManageSelector" id="PersonalizeAccount" value="personalize_account" onClick="document.getElementById('managebuttons').style.display = 'none'; document.getElementById('personalizediv').style.display = 'block';">
</td>
<td>
<label style="color: #E1B378;">Personalize My Account</label>
</td>
</tr>

<tr>
<td valign="baseline">
<input type="radio" name="ManageSelector" id="ConsolidateAccounts" value="consolidate_accounts" onClick="document.getElementById('managebuttons').style.display = 'none'; document.getElementById('consolidatediv').style.display = 'block';">
</td>
<td>
<label style="color: #E1B378;">Consolidate My Accounts</label>
</td>
</tr>

<tr>
<td valign="baseline">
<input type="radio" name="ManageSelector" id="DeleteAccount" value="delete_account" onClick="document.getElementById('managebuttons').style.display = 'none'; document.getElementById('deleteaccountdiv').style.display = 'block';">
</td>
<td>
<label style="color: #E1B378;">Destroy My Account</label>
</td>
</tr>

</table>
</form>
</div>

<?php
/* In the alertsdiv that follows, we want to preset one of the radio buttons to the value of AlertType in owners_table for the logged in Owner. Retrieve this value. */
$query = "SELECT AlertType from owners_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
$result = mysql_query($query) or die('Query (select AlertType from owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
$row = mysql_fetch_assoc($result);
$storedAlertType = $row['AlertType'];
?>

<div id="alertsdiv" style="width: 540px; margin: 40px auto 0px auto; text-align: left; border: 1px solid #E1B378; padding: 10px 5px 10px 10px; display: none;">
<form method="post" action="/scripts/managealerts_slave.php">
<img style="position: absolute; top: 134px; left: 50%; margin-left: 230px;" src="/images/megaphone.jpg" alt="stopsign">
<table cellspacing="0" cellpadding="6" border="0" style="font-size: 14px; font-family: Geneva, Arial, Helvetica, sans-serif; padding: 0px;">
<tr>
<td valign="baseline" width="20">
<input type="radio" style="padding-top: 12px;" onClick="return false;" CHECKED>
</td>
<td colspan="2">
<div style="text-align: right;"><a style="font-size: 10px;" href="#Link618865Context" name="Link618865Context" id="Link618865Context" onMouseOver="javascript:createGlossary('TSGlossaryPanelID618865', 'Alerts', '<b>Alerts</b> are email messages to notify friends that you have new content (video, pictures, etc.) to share. If your friend doesn&rsquo;t yet have an Abridg account, the Alert will contain a link to set one up and view your content with a single click.<br><br>Worried about email overload? Don&rsquo;t. Abridg will never send an Alert to a friend more than once.<br><br>You can manually control exactly when and to whom Alerts get sent. Or choose one of the automated options and let us take care of Alerts for you.', 'Link618865Context');">What are Alerts?&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a></div>
<label style="color: #E1B378;">Manage Alerts</label>
</td>
</tr>
<tr>
<td></td>
<td colspan="2" style="padding-top: 12px;">
<LABEL style="color: #9F0251;">I want alerts to be sent automatically...</LABEL>
</td>
</tr>
<tr>
<td width="20"></td>
<td valign="baseline" width="20">
<input type="radio" name="AlertTypeSelector" id="AlertOnAssign" value="auto_onassign" onClick="this.form.submit();" <?php if ($storedAlertType == 'auto_onassign') echo 'CHECKED'; ?>>
</td>
<td>
<label>... when I assign media items to friends (max. one alert per friend)</LABEL>
</td>
</tr>
<tr>
<td width="20"></td>
<td valign="baseline" width="20">
<input type="radio" name="AlertTypeSelector" id="AlertOnLogout" value="auto_onlogout" onClick="this.form.submit();" <?php if ($storedAlertType == 'auto_onlogout') echo 'CHECKED'; ?>>
</td>
<td>
<label>... when I log out (max. one alert per friend)</label>
</td>
</tr>
<!-- Without sniffing the logged in Owner's three-letter time zone (e.g. MST) and using a lot of complex switch statements while keeping track of when various regions change their clocks, it's problematic to implement a feature that let's the user specify an hour in his/her local time on which alerts are to be automatically sent. For the present, I've decided not to implement this option. -->
<!--
<tr>
<td width="20"></td>
<td valign="baseline" width="20">
<input type="radio" name="AlertTypeSelector" id="AlertOnHour" value="auto_onhour" onClick="this.form.submit();" <?php if ($storedAlertType == 'auto_onhour') echo 'CHECKED'; ?>>
</td>
<td>
<label>... at _____ daily (max. one alert per friend)</label></td>
</tr>
-->
<tr style="padding-top: 20px;">
<td></td>
<td colspan="2" style="padding-top: 20px;">
<label style="color: #9F0251;">I&rsquo;m a control freak! I&rsquo;ll send alerts manually.</label>
</td>
</tr>
<tr>
<td width="20"></td>
<td valign="baseline" width="20">
<input type="radio" name="AlertTypeSelector" id="AlertNow" value="manual_now" onClick="document.getElementById('alertsdiv').style.display = 'none'; document.getElementById('alertnowdiv').style.display = 'block'; return false;" <?php if ($storedAlertType == 'manual_now') echo 'CHECKED'; ?>>
</td>
<td>
<label>Send an alert now to specified friends only</label></td>
</tr>
<tr>
<td width="20"></td>
<td valign="baseline" width="20">
<input type="radio" name="AlertTypeSelector" id="AlertDont" value="manual_dont" onClick="this.form.submit();" <?php if ($storedAlertType == 'manual_dont') echo 'CHECKED'; ?>>
</td>
<td>
<label>Don&rsquo;t send alerts right now. <span style="font-size: 10px;">[not recommended&nbsp;&ndash;&nbsp;</span><span><a style="font-size: 10px;" href="#Link618867Context" name="Link618867Context" id="Link618867Context" onMouseOver="javascript:createGlossary('TSGlossaryPanelID618867', 'Warning', 'Unless your friends already have Abridg accounts, they won&rsquo;t be able to view the content you&rsquo;ve chosen to share.', 'Link618867Context');">why not?</a></span><span style="font-size: 10px;">]</span></label></td>
</tr>
</table>
</form>
</div>
		
<?php
/* If there are any associates of logged in Owner for whom alerts are pending, list them as a check-box list whose form action script is managealerts_slave.php within a div of id=alertnowdiv. If there are none, display a suitable screen message within a div of id=alertnowdiv. (Note that the two divs can have different style attributes, and PHP will ensure that only one of them gets written into the post-PHP HTML.) */

// Formulate the query to retrieve all such associates for whom an alert is pending. This query is similar to the one originally declared in alertgenerator.php, but it's modified a little st it doesn't require the associate to have an AssociateLastLogin date greater than the AlertLastSent date in order to permit the sending of another alert. 
$query = "SELECT DISTINCT associates_table.AssociateID, associates_table.AssociateName FROM associates_table, assign_table, owners_table WHERE associates_table.OwnerID = ".$_SESSION['LoggedInOwnerID']." AND associates_table.AssociateID = assign_table.AssociateID AND TIMESTAMPDIFF(MINUTE, associates_table.AssociateLastLogin, assign_table.AssignDate) >= 0 AND owners_table.OwnerID = associates_table.OwnerID AND associates_table.OwnerUsername != owners_table.OwnerUsername";
$result = mysql_query($query) or die('Query (select recipients of alerts from owners_table, associates_table, and assign_table) failed: ' . mysql_error().' and the database query string was: '.$query);

// Count the number of rows in the resultset (note that the mysql_fetch_assoc($result) statement gets called a little later below inside the check-box list creation and then again (after a mysql_data_seek($result, 0)) for generating the list of associates as check-boxes.
$NofPendingAlertRecipients = mysql_num_rows($result);

$ZeroPendingAlertRecipients = ($NofPendingAlertRecipients == 0 ? true : false); // ternary operator
if (!$ZeroPendingAlertRecipients) // Show a check-box list of pending alert recipients
	{
	// Note the same div id ["alertnowdiv"] appears in both the if and else clauses.
?>
	<div id="alertnowdiv" style="width: 540px; margin: 40px auto 0px auto; text-align: left; border: 1px solid #E1B378; padding: 10px 5px 10px 10px; display: none;">
	<form method="post" action="/scripts/managealerts_slave.php">
	<table cellspacing="0" cellpadding="6" border="0" style="font-size: 14px; font-family: Geneva, Arial, Helvetica, sans-serif; padding: 0px;">
	<tr>
	<td valign="baseline" width="20">
	<input type="radio" style="padding-top: 0px;" CHECKED>
	</td>
	<td colspan="3">
	<label style="color: #E1B378;">Manage Alerts</label>
	</td>
	</tr>
	<tr>
	<td></td>
	<td valign="baseline" width="20">
	<input type="radio" style="padding-top: 0px;" CHECKED>
	</td>
	<td colspan="2">
	<label>Send an alert now to these friends only:</label>
	</td>
	</tr>
	<tr>
	<td></td>
	<td></td>
	<td width="10">
	<input type="checkbox" id="checkall" name="checkall" value="checkall" onclick="function checkAll() { <?php	while ($row = mysql_fetch_assoc($result)) { echo "document.getElementById('Account".$row['AssociateID']."').checked = true; ";}; ?> }; function uncheckAll() { <?php mysql_data_seek($result, 0); while ($row = mysql_fetch_assoc($result)) { echo "document.getElementById('Account".$row['AssociateID']."').checked = false; ";}; ?> }; if (this.checked) checkAll(); else uncheckAll();">
	</td>
	<td style="text-align: left;">
	[all]
	</td>
	</tr>
	<?php
	mysql_data_seek($result, 0); 
	while ($row = mysql_fetch_assoc($result))
		{
		echo '<tr>';
		echo '<td></td>';
		echo '<td></td>';
		echo '<td><input type="checkbox" name="Associates[]" id="Account'.$row['AssociateID'].'" value="'.$row['AssociateID'].'" onclick="if (!this.checked) document.getElementById(\'checkall\').checked = false;"></td>';
		echo '<td style="text-align: left;">'.$row['AssociateName'].'</td>';
		echo '</tr>';
		}
?>
	<tr height="60" valign="middle">
	<td colspan="4" style="text-align: center;">
	<input type="submit" name="SendAlertsNowButton" value="Send Now" class="buttonstyle">
	</td>
	</tr>
	</table>
	</form>
	</div>
<?php
	}
else // No associates have an alert pending so display a screen message accordingly. (Note the same div id ["alertnowdiv"] appears in both the if and else clauses.)
	{
?>
	<div id="alertnowdiv" style="width: 800px; margin: 0px auto 0px auto; text-align: left; padding: 10px 5px 10px 10px; display: none;">
	<table cellpadding="0" cellspacing="0" style="margin-top: 0px; margin-left: auto; margin-right: auto; position: relative; left: -7px;">
	<tr>
	<td style="text-align: left;">
	<p class='text' style='margin-left: 150px; margin-right: 150px; margin-top: 10px; margin-bottom: 40px;'>
	You&rsquo;ve got nothing on deck right now! No alerts are currently pending for any of your friends. You&rsquo;ll see friends listed here to whom you can send alerts once you&rsquo;ve assigned them new content.
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
	</div>
<?php
	}

/* Retrieve values from owners_table for the logged in Owner in order to prepopulate the "Edit My Account" form */
$query = "SELECT OwnerLabel, OwnerFirstName, OwnerLastName, OwnerOrganization, OwnerUsername, OwnerPassword from owners_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
$result = mysql_query($query) or die('Query (select owner data from owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
$row = mysql_fetch_assoc($result);

?>
<div id="editaccountdiv" style="width: 600px; margin: 40px auto 0px auto; text-align: left; border: 1px solid #E1B378; padding: 10px 5px 10px 10px; <?php if ($_SESSION['ManageEditValidationError'] == 'true') echo 'display: block'; else echo 'display: none'; ?>">
<form method='post' name='EditAccount' action='/scripts/manageedit_slave.php' onsubmit="return checkForm('OwnerUsername', 'OwnerPassword');">
<img style="position: absolute; top: 134px; left: 50%; margin-left: 275px;" src="/images/bbpenciledit.jpg" alt="pencil edit">
<table cellspacing="0" cellpadding="6" border="0" style="font-size: 14px; font-family: Geneva, Arial, Helvetica, sans-serif; padding: 0px;">
<tr>
<td valign="baseline" width="20">
<input type="radio" style="padding-top: 0px;" CHECKED>
</td>
<td colspan="2">
<label style="color: #E1B378;">Edit Account</label>
</td>
</tr>
<tr height='50'>
<td></td>
<td colspan='2' style='text-align: center;'>
<input type='radio' name='EntityType' id='EntityType' value='individual' onClick="offaddress(); document.getElementById('acctnameexamples').innerHTML = 'Examples: &ldquo;Jane&rdquo;, &ldquo;Jane Doe&rdquo;, &ldquo;The Doe Family&rdquo;<br />';" <?php if (empty($row['OwnerOrganization'])) echo 'checked'; ?>>
<label>Private Individual&nbsp;&nbsp;&nbsp;</label>
<input type='radio' name='EntityType' id='EntityType' value='organization' onClick="onaddress(); document.getElementById('acctnameexamples').innerHTML = 'Example: &ldquo;XYZ&rdquo;<br />';" <?php if (!empty($row['OwnerOrganization'])) echo 'checked'; ?>>
<label>Organization</label>
</td>
</tr>
<tr style='height: 60px;'>
<td></td>
<td style='width: 150px;'>
<label>First Name</label>
</td>
<td style='width: 330px;'>
<input type='text' class='textfield' name='OwnerFirstName' id='OwnerFirstName' maxlength='40' size='30' value='<?=$row['OwnerFirstName']; ?>' onFocus="this.style.background='#FFFF97';" onBlur="this.style.background='white';">
<?php if ($_SESSION['MsgOwnerFirstName'] != null) { echo $_SESSION['MsgOwnerFirstName']; $_SESSION['MsgOwnerFirstName']=null; } ?>
</td>
</tr>
<tr style='height: 60px;'>
<td></td>
<td>
<label>Last Name</label>
</td>
<td>
<input type='text' class='textfield' name='OwnerLastName' id='OwnerLastName' maxlength='40' size='30' value='<?=$row['OwnerLastName']; ?>' onFocus="this.style.background='#FFFF97'" onBlur="this.style.background='white';">
<?php if ($_SESSION['MsgOwnerLastName'] != null) { echo $_SESSION['MsgOwnerLastName']; $_SESSION['MsgOwnerLastName']=null; } ?>
</td>
</tr>
<tr class='collapsible' style='height: 60px; display: <?php if (!empty($row['OwnerOrganization'])) echo 'block'; else echo 'none'; ?>;'>
<td></td>
<td>
<label>Organization</label>
</td>
<td>
<input type='text' class='textfield' name='OwnerOrganization' id='OwnerOrganization' maxlength='40' size='30' value='<?=$row['OwnerOrganization']; ?>' onFocus="this.style.background='#FFFF97'" onBlur="this.style.background='white';">
<div class='helptextsmall' style='color: #9F0251;'>Example: &ldquo;XYZ Corporation&rdquo;<br /></div>
<?php if ($_SESSION['MsgOwnerOrganization'] != null) { echo $_SESSION['MsgOwnerOrganization']; $_SESSION['MsgOwnerOrganization']=null; } ?>
</td>
</tr>
<tr style='height: 70px;'>
<td></td>
<td valign="top" style="padding-top: 22px;">
<label>Account Name</label>
</td>
<td>
<input type='text' class='textfield' name='OwnerLabel' id='OwnerLabel' maxlength='40' size='30' value='<?=$row['OwnerLabel']; ?>' onFocus="this.style.background='#FFFF97'" onBlur="this.style.background='white';">
<div class='helptextsmall' id='acctnameexamples' style='color: #9F0251;'>Examples: &ldquo;Jane&rdquo;, &ldquo;Jane Doe&rdquo;, &ldquo;The Doe Family&rdquo;<br /></div>
<?php if ($_SESSION['MsgOwnerLabel'] != null) { echo $_SESSION['MsgOwnerLabel']; $_SESSION['MsgOwnerLabel']=null; } ?>
</td>
</tr>
<tr style='height: 60px;'>
<td></td>
<td>
<label>Email (Username)</label>
</td>
<td>
<input type='text' class='textfield'  name='OwnerUsername' id='OwnerUsername' maxlength='40' size='30' value='<?=$row['OwnerUsername']; ?>' onFocus="this.style.background='#FFFF97'" onBlur="this.style.background='white'; return checkEmailOnly('OwnerUsername');">
<div class='error' id='EmailError'><br>Your email address is invalid. Please try again.<br></div>
<?php if ($_SESSION['MsgOwnerUsername'] != null) { echo $_SESSION['MsgOwnerUsername']; $_SESSION['MsgOwnerUsername']=null; } ?>
</td>
</tr>
<tr style='height: 60px;'>
<td></td>
<td>
<label>Password</label>
</td>
<td>
<input type='text' class='textfield' name='OwnerPassword' id='OwnerPassword' maxlength='20'  size='30' value='<?=$row['OwnerPassword']; ?>' onFocus="this.style.background='#FFFF97';" onBlur="this.style.background='white'; return checkPasswordOnly('OwnerPassword');">
<div class='helptextsmall' style='color: #9F0251;'>Include at least one number. 8-character minimum.
</div>
<div class='error' id='PasswordError'>You have chosen an invalid password. Please try again.<br></div>
<?php if ($_SESSION['MsgOwnerPassword'] != null) { echo $_SESSION['MsgOwnerPassword']; $_SESSION['MsgOwnerPassword']=null; } ?>
</td>
</tr>
<tr>
<td></td>
<td colspan='2' style='text-align: center;'>
<br>
<input type='submit' name='EditAccountOwner' value='Edit Account' class='buttonstyle'>
</td>
</tr>
</table>
</form>
</div>

<?php
// Having represented the "editaccountidv" in the case of there being any PHP form validation errors detected by manageedit_slave.php, we must now reset $_SESSION['ManageEditValidationError'] to 'false'
$_SESSION['ManageEditValidationError'] = 'false';
?>

<div id="personalizediv" style="width: 600px; margin: 40px auto 0px auto; text-align: left; border: 1px solid #E1B378; padding: 10px 5px 10px 10px; display: none;">
<img style="position: absolute; top: 134px; left: 50%; margin-left: 275px;" src="/images/bbsunglasses.jpg" alt="personalization icon">
<table cellspacing="0" cellpadding="6" border="0" style="font-size: 14px; font-family: Geneva, Arial, Helvetica, sans-serif; padding: 0px;">
<tr>
<td valign="baseline" width="20">
<input type="radio" style="padding-top: 0px;" CHECKED>
</td>
<td colspan="2">
<label style="color: #E1B378;">Peronalize Account</label>
</td>
</tr>
<tr>
<td></td>
<td colspan="2" style="padding-top: 12px;">
<p>This feature has not yet been implemented.</p>
</td>
</tr>
</table>
</div>

<div id="consolidatediv" style="width: 690px; margin: 40px auto 0px auto; text-align: left; border: 1px solid #E1B378; padding: 10px 5px 10px 10px; <?php if ($_SESSION['ManageConsolidateValidationError'] == 'true') echo 'display: block'; else echo 'display: none'; ?>">
<form method='post' name='ConsolidateAccount' action='/scripts/manageconsolidate_slave.php' onSubmit="var thereturn = true; if (!checkEmailOnly('UsernameAccount1')) { document.getElementById('EmailErrorAcct1').style.display = 'inline'; thereturn = false; } else { document.getElementById('EmailErrorAcct1').style.display = 'none'; }; if (!checkEmailOnly('UsernameAccount2')) { document.getElementById('EmailErrorAcct2').style.display = 'inline'; thereturn = false; } else { document.getElementById('EmailErrorAcct2').style.display = 'none'; } if (!document.getElementById('Acct1isPrim').checked && !document.getElementById('Acct2isPrim').checked) {  document.getElementById('PrimaryAccountError').style.display = 'inline'; thereturn = false; } else { document.getElementById('PrimaryAccountError').style.display = 'none'; }; return thereturn;">
<table cellspacing="0" cellpadding="6" border="0" style="font-size: 14px; font-family: Geneva, Arial, Helvetica, sans-serif; padding: 0px;">
<tr>
<td valign="middle" width="20">
<input type="radio" style="margin-top: 6px;" CHECKED>
</td>
<td colspan="5">
<div style="text-align: right;"><a style="font-size: 10px;" href="#Link618866Context" name="Link618866Context" id="Link618866Context" onMouseOver="javascript:createGlossary('TSGlossaryPanelID618866', 'Why consolidate?', 'If you ever find you have two Abridg accounts under different usernames (for example, you log into one via your personal email address and into the other via your work email address), you can combine the two accounts into a single account under one username.', 'Link618866Context');">Why?</a></div>
<label style="color: #E1B378;">Consolidate Accounts</label>
</td>
</tr>
<tr style="height: 40px; vertical-align: bottom;">
<td></td>
<td colspan="4"><LABEL style="color: #9F0251;">Log into the two accounts you wish to consolidate (merge) into a single account:</LABEL>
</td>
</tr>
<tr style="height: 35px; vertical-align: bottom;">
<td></td>
<td colspan="4"><span class="gloss" style="font-weight: bold; font-variant: small-caps;">Account #1</span>
</td>
</tr>
<tr style='height: 12px; vertical-align: top;'>
<td></td>
<td style='width: 70px;'>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label>Username</label>
</td>
<td style='width: 280px;'>
<input type='text' class="textfield" name='UsernameAccount1' id='UsernameAccount1' maxlength='40' size='25' value='<?=$row['OwnerUsername']; ?>' onFocus="this.style.background='#FFFF97';" onBlur="this.style.background='white';">
<div class='error' id='EmailErrorAcct1'><br>Enter a valid email address<br></div>
</td>
<td style='width: 70px;'>
<label>Password&nbsp;</label>
</td>
<td style='width: 250px;'>
<input type='text' class="textfield" name='PasswordAccount1' id='PasswordAccount1' maxlength='40' size='15' value='<?=$row['OwnerPassword']; ?>' onFocus="this.style.background='#FFFF97';" onBlur="this.style.background='white';">
</td>
</tr>
<tr>
<td></td>
<td colspan="4" style="height: 10px; vertical-align: top;"><?php if ($_SESSION['MsgAcct1Login'] != null) { echo $_SESSION['MsgAcct1Login']; $_SESSION['MsgAcct1Login']=null; } ?>
</td>
</tr>
<tr style="height: 35px; vertical-align: bottom;">
<td></td>
<td colspan="4"><span class="gloss" style="font-weight: bold; font-variant: small-caps;">Account #2</span>
</td>
</tr>
<tr style='height: 12px; vertical-align: top;'>
<td></td>
<td style='width: 70px;'>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label>Username</label>
</td>
<td style='width: 280px;'>
<input type='text' class="textfield" name='UsernameAccount2' id='UsernameAccount2' maxlength='40' size='25' value='<?php if (isset($_SESSION['UsernameAccount2'])) echo $_SESSION['UsernameAccount2']; ?>' onFocus="this.style.background='#FFFF97';" onBlur="this.style.background='white';">
<div class='error' id='EmailErrorAcct2'><br>Enter a valid email address<br></div>
</td>
<td style='width: 70px;'>
<label>Password&nbsp;</label>
</td>
<td style='width: 250px;'>
<input type='text' class="textfield" name='PasswordAccount2' id='PasswordAccount2' maxlength='40' size='15' value='<?php if (isset($_SESSION['PasswordAccount2'])) echo $_SESSION['PasswordAccount2']; ?>' onFocus="this.style.background='#FFFF97';" onBlur="this.style.background='white';">
</td>
</tr>
<tr>
<td></td>
<td colspan="4">
<?php if ($_SESSION['MsgAcct2Login'] != null) { echo $_SESSION['MsgAcct2Login']; $_SESSION['MsgAcct2Login']=null; } ?>
</td>
</tr>
<tr style="height: 40px; vertical-align: bottom;">
<td style="height: 20px; border-top: 1px dashed #E1B378;"></td>
<td colspan="4" style="height: 20px; border-top: 1px dashed #E1B378;">
<LABEL style="color: #9F0251;">Which account shall remain after the merge?</LABEL>
</td>
</tr>
<tr height='50'>
<td></td>
<td colspan='4' style='text-align: center;'>
<label>Account #1</label>
<input type='radio' name='PrimaryAccount' id='Acct1isPrim' value='1' <?php if ($_SESSION['PrimaryAccount'] == '1') echo 'checked'; ?>>
&nbsp;&nbsp;<label>or</label>&nbsp;&nbsp;
<input type='radio' name='PrimaryAccount' id='Acct2isPrim' value='2' <?php if ($_SESSION['PrimaryAccount'] == '2') echo 'checked'; ?>>
<label>Account #2</label>
<div class='error' id='PrimaryAccountError'><br>Select either Account #1 or #2</div>
<?php if ($_SESSION['MsgPrimaryAccount'] != null) { echo $_SESSION['MsgPrimaryAccount']; $_SESSION['MsgPrimaryAccount']=null; } ?>
</td>
</tr>
<tr style="height: 60px; vertical-align: middle;">
<td colspan="5" style="height: 20px; border-top: 1px dashed #E1B378; text-align: center;">
<input type='submit' class="buttonstyle" style="margin-top: 10px;" name="consolidateaccountsbutton" value="Consolidate My Accounts"; >
</td>
</tr>
</table>
</form>
</div>

<?php
// Having re-presented the "consolidatediv" in the case of there being any PHP form validation errors detected by manageconsolidate_slave.php, we must now reset $_SESSION['ManageConsolidateValidationError'] to 'false'
$_SESSION['ManageConsolidateValidationError'] = 'false';
?>

<div id="deleteaccountdiv" style="width: 600px; margin: 40px auto 0px auto; text-align: left; border: 1px solid #E1B378; padding: 10px 5px 10px 10px; display: none;">
<img style="position: absolute; top: 134px; left: 50%; margin-left: 270px;" src="/images/stopsign.jpg" alt="stopsign">
<table cellspacing="0" cellpadding="6" border="0" style="font-size: 14px; font-family: Geneva, Arial, Helvetica, sans-serif; padding: 0px;">
<tr>
<td valign="baseline" width="20">
<input type="radio" style="padding-top: 0px;" CHECKED>
</td>
<td colspan="2">
<label style="color: #E1B378;">Destroy Account</label>
</td>
</tr>
<tr>
<td></td>
<td colspan="2" style="padding-top: 12px;">
<label>You are about to destroy your Abridg account. This operation is not reversible.</label>
</td>
</tr>
<tr height="60">
<td></td>
<td colspan="2" style="text-align: center; padding-top: 12px;">
<form method="post" action="/scripts/managedestroy_slave.php" style="display: inline;">
<input type="submit" class="buttonstyle"  name="destroymyaccountbutton" value="Destroy My Account" style="text-align: center;">
</form>
</td>
</tr>
</table>
</div>

<div style="text-align: center;">
<span style="text-align: center; position: relative; top: 50px;"><a style="font-weight: bold" href="/faqhelp.php" onClick="wintasticsecond('/faqhelp.php'); return false;"><img alt="Help Icon" border="0" src="/images/help-icon.png"></a></span>
<?php
require ("/home/paulme6/public_html/abridg/ssi/footer.php");
?>
</div>
<?php
flush(); // Recommended in PHP Manual after use of set_time_limit(0) (ref. http://www.php.net/manual/en/function.set-time-limit.php)
?>
</div>
</div>

<!-- Start of StatCounter Code for Dreamweaver -->
<script type="text/javascript">
var sc_project=7700501; 
var sc_invisible=1; 
var sc_security="d01e6e4d"; 
</script>
<script type="text/javascript"
src="http://www.statcounter.com/counter/counter.js"></script>
<noscript><div class="statcounter"><a title="click
tracking" href="http://statcounter.com/"
target="_blank"><img class="statcounter"
src="http://c.statcounter.com/7700501/0/d01e6e4d/1/"
alt="click tracking"></a></div></noscript>
<!-- End of StatCounter Code for Dreamweaver -->

</body>
</html>
