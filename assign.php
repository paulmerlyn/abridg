<?php
/*
assign.php allows an Owner (administrator cf. super-administrator) of the Abridg web site to (i) assign one or more account holders to a selected media file, or (ii) assign one or more media files to a selected account holder. These two tasks are processed by slave assign_slave.php.
	In addition, assign.php is the front-end by which the Administrator can perform four other tasks: (iii) delete an account holder from the associates_table, (iv) delete a media file from the media_table, (v) edit details of an existig account holder in the associates_table, and (vi) edit details of an existing media file in the media_table. These latter four tasks are also processed (via hidden fields and graphical submit buttons) by script assign_slave.php.
	Also important, assign.php is (arbitrarily) the gateway (i.e. landing page) by which a logged in Owner accesses the Abridg Director admin console. Not all owners are fully registered. Some are semiregistered (by virtue of having signed up via nonregisteredownerloginform form in index.php after having gotten an invitation/alert from a content producer who had added them as a friend/content consumer). Semiregistered owners have an OwnerUsername and OwnerPassword, but blank values for OwnerLabel, OwnerFirstName, OwnerLastName, and OwnerOrganization (optional). Now, the Abridg Director functionality really utilizes those fields (e.g. alertgenerator needs OwnerFirstName and OwnerLastName) so we need to obtain them from the logged in Owner before he/she can proceed any further with any of the Abridg Director scripts. We obtain them by checking the value of $_SESSION['RegOwnerStatus'] (set upon login in index.php) == 'semi' (cf. 'full') and presenting an HTML form in an overlay screen (div id = 'semitofullbox'). (This is very similar to the form inside signupbox in index.php.)
*/

// Start a session
session_start();

if ($_SESSION['Authenticated'] != 'true') header("Location: /index.php");

// Connect to DB (a connection is necessary for mysql_real_escape_string below)
$db = mysql_connect('localhost', 'paulme6_merlyn', '')
or die('Could not connect: ' . mysql_error());
mysql_select_db('paulme6_abridg') or die('Could not connect to the abridg database: ' . mysql_error());

/* Examine $_SESSION['RegOwnerStatus'] to see whether we first need to obtain values for OwnerLabel, OwnerFirstName, OwnerLastName, OwnerOrganization (optional) in the case of a semiregistered owner. */
if ($_SESSION['RegOwnerStatus'] == 'full')
	{
	// Count the number of media items belonging to this Owner. If $CountOfMediaItems == 0, we'll disable the radio buttons in the AssignMediaFilesToAssociateScreen screen.
	$query = "SELECT COUNT(*) FROM media_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
	$result = mysql_query($query) or die('Query (select COUNT(*) from media_table has failed: ' . mysql_error());
	$row = mysql_fetch_row($result);
	$CountOfMediaItems = $row[0];

	// Also, count the number of associates of this Owner. If $CountOfAssociates == 0, we'll disable the radio buttons in the AssignAssociatesToMediaFileScreen screen.
	$query = "SELECT COUNT(*) FROM associates_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
	$result = mysql_query($query) or die('Query (select COUNT(*) from associates_table has failed: ' . mysql_error());
	$row = mysql_fetch_row($result);
	$CountOfAssociates = $row[0];
	}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Assign Media Files to Friends</title>
<meta NAME="description" CONTENT="Form to assign media files (video, image, etc.) to Abridg account holders">
<link href="/abridg.css" rel="stylesheet" type="text/css">
<link href="/scripts/tigra_calendar/calendar.css" rel="stylesheet" type="text/css">
<script type='text/javascript' language="JavaScript" src="/scripts/windowpops.js"></script>
<script type="text/javascript" src="/jwplayer/jwplayer.js"></script>
<script>
function FocusFirst()
	{
	if (document.forms.length > 0 && document.forms[1].elements.length > 0)
		{
		document.getElementById('OwnerFirstName').focus();
		document.getElementById('OwnerFirstName').style.background = '#FFFF97';
		}
	};

// Javascript for display of overlay "semitofullbox" div, which contains the HTML form that allows a semiregistered owner to convert to a fully registered owner by providing OwnerLabel, OwnerFirstName, OwnerLastName, and (optional) OwnerOrganization. Courtesy: http://celestial-star.net/tutorials/49-check-referrer/. Note that I use Javascript form validation on the email and password fields only. I then use PHP form validation on all the other fields only.
function showSemiToFullBox(){
	var thediv=document.getElementById('semitofullbox');
	if(thediv.style.display == "none"){
		thediv.style.display = "block";
		thediv.innerHTML = "<div style='text-align: left; padding-top: 24px; position: relative;'><form method='post' action='/index.php'><input type='button' name='galleryview' class='buttonstyleX1' style='position: absolute; top: 0px; right: 0px; padding: 0px; font-family: sans-serif;' value='X' onClick=" + '"' + "document.getElementById('semitofullbox').style.display = 'none';" + '"' + "></form><form method='post' name='SemiToFull' action='/scripts/semitofull_slave.php'><table align='center'><tr height='50'><td colspan='2' style='text-align: center;'><input type='radio' name='EntityType' id='EntityType' value='individual' onClick=" + '"' + "offaddress(); document.getElementById('acctnameexamples').innerHTML = 'Examples: &ldquo;Jane&rdquo;, &ldquo;Jane Doe&rdquo;, &ldquo;The Doe Family&rdquo;<br />';" + '"' + "<?php if ($_SESSION['EntityType'] != 'organization') echo ' checked'; ?>><label class='white'>Private Individual&nbsp;&nbsp;&nbsp;</label><input type='radio' name='EntityType' id='EntityType' value='organization' onClick=" + '"' + "onaddress(); document.getElementById('acctnameexamples').innerHTML = 'Example: &ldquo;XYZ&rdquo;<br />';" + '"' + "<?php if ($_SESSION['EntityType'] == 'organization') echo 'checked'; ?>><label class='white'>Organization</label></td></tr><tr style='height: 60px;'><td style='width: 150px;'><label class='white'>First Name</label></td><td style='width: 330px;'><input type='text' name='OwnerFirstName' id='OwnerFirstName' maxlength='40' size='30' value='" + "<?php if (isset($_SESSION['OwnerFirstName'])) echo $_SESSION['OwnerFirstName']; ?>" + "' onFocus=" + '"this.style.background=' + "'#FFFF97'" + ' onBlur="this.style.background=' + "'white';" + "><?php if ($_SESSION['MsgOwnerFirstName'] != null) { echo $_SESSION['MsgOwnerFirstName']; $_SESSION['MsgOwnerFirstName']=null; } ?></td></tr><tr style='height: 60px;'><td><label class='white'>Last Name</label></td><td><input type='text' name='OwnerLastName' id='OwnerLastName' maxlength='40' size='30' value='<?php if (isset($_SESSION['OwnerLastName'])) echo $_SESSION['OwnerLastName']; ?>' onFocus=" + '"' + "this.style.background='#FFFF97'" + '"' +  " onBlur=" + '"' + "this.style.background='white';" +'"' + "><?php if ($_SESSION['MsgOwnerLastName'] != null) { echo $_SESSION['MsgOwnerLastName']; $_SESSION['MsgOwnerLastName']=null; } ?></td></tr><tr class='collapsible' style='height: 60px; display: <?php if ($_SESSION['EntityType'] == 'organization') echo 'block'; else echo 'none'; ?>;'><td><label class='white'>Organization</label></td><td><input type='text' name='OwnerOrganization' id='OwnerOrganization' maxlength='40' size='30' value='<?php if (isset($_SESSION['OwnerOrganization'])) echo $_SESSION['OwnerOrganization']; ?>' onFocus=" + '"' + "this.style.background='#FFFF97'" + '"' + " onBlur=" + '"' + "this.style.background='white';" + '"' + "><div class='helptextsmall' style='color: #9F0251;'>Example: &ldquo;XYZ Corporation&rdquo;<br /></div><?php if ($_SESSION['MsgOwnerOrganization'] != null) { echo $_SESSION['MsgOwnerOrganization']; $_SESSION['MsgOwnerOrganization']=null; } ?></td></tr><tr style='height: 60px;'><td><label class='white'>Account Name</label></td><td><input type='text' name='OwnerLabel' id='OwnerLabel' maxlength='40' size='30' value='<?php if (isset($_SESSION['OwnerLabel'])) echo $_SESSION['OwnerLabel']; ?>' onFocus=" + '"' + "this.style.background='#FFFF97'" + '"' + " onBlur=" + '"' + "this.style.background='white';" + '"' + "><div class='helptextsmall' id='acctnameexamples' style='color: #9F0251;'>Examples: &ldquo;Jane&rdquo;, &ldquo;Jane Doe&rdquo;, &ldquo;The Doe Family&rdquo;<br /></div><?php if ($_SESSION['MsgOwnerLabel'] != null) { echo $_SESSION['MsgOwnerLabel']; $_SESSION['MsgOwnerLabel']=null; } ?></td></tr><tr><td colspan='2' style='text-align: center;'><br><input type='submit' name='ConvertSemiToFull' value='Continue' class='buttonstyle' onclick='return checkForm('" + "SignUpEmail" + "'" + ", '" + "SignUpPassword" + "'" + ");'></td></tr></table></form></div>";
	}else{
		thediv.style.display = "none";
		thediv.innerHTML = '';
	}
	return false;
}

// These next three JS functions (courtesy Mike Foster at http://www.sitepoint.com/forums/showthread.php?434671-Show-hide-table-row-radio-button) enable the show/hide of the Organization row in the 'SemiToFull' HTML table that appears inside the "semitofullbox" div. They may look unnecessarily complex but proved the only way I could get this seemingly modest requirement to work right in all browsers! When the table row had more than one <td> (i.e. prettty much any table row does!), my simple approach failed in Firefox, Safari and Chrome. To make a table row disappear, call offaddress() as an onclick event; to make the row reappear, call onaddress() as an onclick event. Give all rows that you want to be subject to appearing/disappearing a class="collapsible". That's it!
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
<h1 style="margin-top: 12px; font-size: 22px; color: #9F0251; font-family: 'Century Gothic', Geneva, Arial, sans-serif;">Share with My Friends</h1>
<?php
require('/home/paulme6/public_html/abridg/ssi/adminmenu.php'); // Include the navigation menu.
?>
<div style="width: 540px; margin: 40px auto 0px auto; text-align: left; border: 1px solid #E1B378; padding: 30px 30px 30px 20px;">
<form>
<table cellpadding="0" cellspacing="0">
<tr>
<td colspan="2">
<a href="#" id="MediaFilesToAssociateLink" onClick="document.getElementById('AssignAssociatesToMediaFileRadioButton').checked = 'false'; document.getElementById('AssignMediaFilesToAssociateRadioButton').checked = 'true'; document.getElementById('AssignMediaFilesToAssociateScreen').style.display = 'block'; document.getElementById('AssignAssociatesToMediaFileScreen').style.display = 'none'; window.scrollTo(0,300); return false;"><img src="images/MediaFilesToAssociateIcon.png" alt="Media Files to Associate Icon" style="border: 0;"></a>
<span style="padding: 0px 40px 0px 100px;">&nbsp;</span>
<a href="#" id="AssociatesToMediaFileLink" onClick="document.getElementById('AssignMediaFilesToAssociateRadioButton').checked = 'false'; document.getElementById('AssignAssociatesToMediaFileRadioButton').checked = 'true'; document.getElementById('AssignAssociatesToMediaFileScreen').style.display = 'block'; document.getElementById('AssignMediaFilesToAssociateScreen').style.display = 'none'; window.scrollTo(0,300); return false;"><img src="images/AssociatesToMediaFileIcon.png" alt="Associates to Media File Icon" style="border: 0; position: relative; bottom: 8px;"></a>
</td>
</tr>
<td style="">
<input type="radio" name="AssignImages" id="AssignMediaFilesToAssociateRadioButton" onChange="document.getElementById('AssignMediaFilesToAssociateScreen').style.display = 'block'; document.getElementById('AssignAssociatesToMediaFileScreen').style.display = 'none'; window.scrollTo(0,300);" value="AssignMediaFilesToAssociate" style="position: relative; left: 75px; top: 10px;">
</td>
<td style="">
<input type="radio" name="AssignImages" id="AssignAssociatesToMediaFileRadioButton" onChange="document.getElementById('AssignAssociatesToMediaFileScreen').style.display = 'block'; document.getElementById('AssignMediaFilesToAssociateScreen').style.display = 'none'; window.scrollTo(0,300);" value="AssignAssociateToMediaFile" style="position: relative; left: 155px; top: 10px;">
</td>
</table>
</form>
</div>

<div id="AssignMediaFilesToAssociateScreen" style="text-align: left; margin-left: 150px; margin-right: 150px; margin-top: 50px; display: <?php if (isset($_SESSION['AssociateSelected'])) echo 'block'; else echo 'none'; ?>">
<table>
<tr style="height: 40px;">
<td style="width: 300px; vertical-align: text-top;"><label>Step 1: Select a friend</label></td>
<td style="width: 600px; text-align: left; vertical-align: text-top;"><div id="themediafilesdiv1" style="display: <?php if (isset($_SESSION['AssociateSelected'])) echo 'block'; else echo 'none'; ?>;"><label>Step 2: Assign media files <?php if (isset($_SESSION['AssociateSelected'])) echo 'to '.$_SESSION['AssociateNameSelected']; ?></label></div></td>
</tr>
<?php
$query = "SELECT COUNT(*) AS TheCount FROM associates_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID']; // $_SESSION['LoggedInOwnerID'] is set in assign.php (above) when the Owner (administrator) successfully logs in with a legitimate OwnerUsername/OwnerPassword.
$result = mysql_query($query) or die('Query (select COUNT(*) from associates_table has failed: ' . mysql_error());
$row = mysql_fetch_assoc($result);
$TheAssocCount = $row['TheCount'];

// Also check whether the logged in Owner has uploaded any media items yet. If he/she hasn't, we'll need to display a "No assignments are possible" message in lieu of what would have been under the "Step 2: Assign media files" heading.
$query1 = "SELECT COUNT(*) AS TheItemsCount FROM media_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
$result1 = mysql_query($query1) or die('Query (select COUNT(*) from associates_table has failed: ' . mysql_error());
$row1 = mysql_fetch_assoc($result1);
$TheItemsCount = $row1['TheItemsCount'];

if ($TheAssocCount > 0) // Only display account holders if at least one account holder has already been added by this Owner.
	{
?>
	<tr style="height: 60px;">
	<td valign="top">
	<!-- Nest within a td cell a table that holds the list of accounts to which media items may be assigned -->
	<form method="post" name="AssociatesToMedia" action="/scripts/assign_slave.php">
	<table cellspacing="0" cellpadding="6" border="0" style="font-size: 14px; font-family: Geneva, Arial, Helvetica, sans-serif; padding: 0px;">
<?php
	$query = "SELECT AssociateName, AssociateID, OwnerID, OwnerUsername FROM associates_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID']; // $_SESSION['LoggedInOwnerID'] is set in index.php when the Owner (administrator) successfully logs in with a legitimate OwnerUsername/OwnerPassword.
	$result = mysql_query($query) or die('Query (select AssociateName, AssociateID, OwnerID, OwnerUsername from associates_table has failed: ' . mysql_error());
	while ($row = mysql_fetch_assoc($result))
		{
		// We want to omit inclusion of the Edit and Delete icons if the associate happens to be the logged in Owner (i.e. the "My Gallery Favorites" row of the associates_table). Test for this and set $OmitEditDeleteIcons accordingly.
		if ($row['OwnerUsername'] == $_SESSION['LoggedInOwnerUsername'] && $row['OwnerID'] == $_SESSION['LoggedInOwnerID'])
			{
			$OmitEditDeleteIcons = true;
			}
		else
			{
			$OmitEditDeleteIcons = false;
			}
	?>
		<tr>
		<td valign="baseline"><input type="radio" name="Associate" id="Account<?=$row['AssociateID']; ?>" value="<?=$row['AssociateID']; ?>" onClick="this.form.submit();" style="position: relative; top: 2px;" <?php if ($CountOfMediaItems == 0) echo 'disabled'; elseif ($row['AssociateID'] == $_SESSION['SelectedAssociateID']) echo 'checked'; ?>></td>
		<td valign="baseline">
		<?=$row['AssociateName']; ?>
<?php
		if (!$OmitEditDeleteIcons)
			{
?>				
			&nbsp;&nbsp;<input type="image" name="EditAssociateButton<?=$row['AssociateID']; ?>" value="Edit This Account Holder" SRC="/images/edit-icon.png" HEIGHT="18" WIDTH="18" BORDER="0" ALT="Submit button to edit Account Holder" style="position: relative; top: 4px; border: 0;"><!-- Graphical submit button -->&nbsp;
			<input type="image" name="DeleteAssociateButton<?=$row['AssociateID']; ?>" value="Delete This Account Holder" SRC="/images/delete-icon.png" HEIGHT="18" WIDTH="18" BORDER="0" ALT="Submit button to delete Account Holder" style="position: relative; top: 3px; border: 0;"><!-- Graphical submit button -->
<?php
			}
?>
		</td>
		</tr>
<?php
		}
?>
	</table>
	</form>
	<!-- End of nested table -->
	</td>
	<td valign="top">
	<!-- Nest within a <td> cell a table that holds the list of media items that may be assigned to the selected account -->
	<div id="themediafilesdiv2" style="display: <?php if (isset($_SESSION['AssociateSelected']) || $TheItemsCount == 0) echo 'block'; else echo 'none'; ?>;">
<?php
	if ($TheItemsCount > 0) // Only display media items if Owner has uploaded at least one such item.
		{
?>
		<form method="post" name="AssignableMedia" action="/scripts/assign_slave.php">
		<table cellspacing="0" cellpadding="6" border="0" style="font-size: 14px; font-family: Geneva, Arial, Helvetica, sans-serif; padding: 0px;">
<?php
		if ($TheItemsCount > 1)
			{
			// Only show an "All" check box if there's more than one media item for the logged in Owner.
?>
			<tr>
			<td>
			<input type="checkbox" id="checkallmedia" name="checkallmedia" value="checkallmedia" onclick="function checkAllMedia() { <?php	$queryAllChkBx = 'SELECT FileID FROM media_table WHERE OwnerID = '.$_SESSION['LoggedInOwnerID']; $resultAllChkBx = mysql_query($queryAllChkBx) or die('Query (select FileID from media_table for all checkboxes has failed: ' . mysql_error()); while ($line = mysql_fetch_assoc($resultAllChkBx)) { echo "document.getElementById('FileID".$line['FileID']."').checked = true; ";}; ?> }; function uncheckAllMedia() { <?php mysql_data_seek($resultAllChkBx, 0); while ($line = mysql_fetch_assoc($resultAllChkBx)) { echo "document.getElementById('FileID".$line['FileID']."').checked = false; ";}; ?> }; if (this.checked) checkAllMedia(); else uncheckAllMedia();" <?php $PresetAllBoxToChecked = true; $queryChkBxPreset = 'SELECT AuthorizedAssociateIDs FROM media_table WHERE OwnerID = '.$_SESSION['LoggedInOwnerID']; $resultChkBxPreset = mysql_query($queryChkBxPreset) or die('Query (select AuthorizedAssociateIDs from media_table has failed: ' . mysql_error()); while ($bar = mysql_fetch_assoc($resultChkBxPreset)) { $AuthorizedAccountsArray = explode(',', $bar['AuthorizedAssociateIDs']); if (!in_array($_SESSION['AssociateID'], $AuthorizedAccountsArray)) { $PresetAllBoxToChecked = false; break; }; }; if ($PresetAllBoxToChecked == true) echo 'CHECKED'; ?>>
			</td>
			<td>[all]</td>
			</tr>
<?php
			}
		$query = "SELECT *, DATE_FORMAT(CaptureDate, '%M %e, %Y') as CaptureDateReformatted FROM media_table WHERE MediaClass = 'video' AND OwnerID = ".$_SESSION['LoggedInOwnerID']." ORDER BY CaptureDate DESC";
		$result = mysql_query($query) or die('Query (select videos from media_table has failed: ' . mysql_error());
		while ($row = mysql_fetch_assoc($result))
			{
			/* Obtain from the Filename and Snapshot column values in media_table the actual filenames under which the media item and snapshot are stored on the server in the /media and /snapshots directories respectively e.g. if $Filename is "piglet.png" then the actual file will be "/media/piglet_683.png, where 683 is the FileID. */
			// This expression looks complex, but it's just a means to insert "_XXX" (where XXX is the media file's FileID) into "myfile.jpg", making it "myfile_XXX.jpg".
			$theFilenameFile = substr($row['Filename'], 0, strrpos($row['Filename'], '.')).'_'.$row['FileID'].substr($row['Filename'], strrpos($row['Filename'], '.'));
			$theSnapshotFile = substr($row['Snapshot'], 0, strrpos($row['Snapshot'], '.')).'_'.$row['FileID'].substr($row['Snapshot'], strrpos($row['Snapshot'], '.'));
?>
			<tr>
			<td valign="top"><input type="checkbox" name="MediaFiles[]" id="FileID<?=$row['FileID']; ?>" value="<?=$row['FileID']; ?>" <?php if ($TheItemsCount > 1) echo 'onclick="if (!this.checked) document.getElementById(\'checkallmedia\').checked = false;"'; ?> <?php $AuthorizedAccountsArray = explode(',', $row['AuthorizedAssociateIDs']); if (in_array($_SESSION['AssociateID'], $AuthorizedAccountsArray)) echo ' checked'; ?>></td> 
			<td>File: <?=$row['Filename']; ?>&nbsp;&nbsp;|&nbsp;&nbsp;<?=$row['CaptureDateReformatted']; ?><span style="font-size: 10px; color:#666666;"><br /><?php if (!empty($row['Title'])) echo 'Title: '.$row['Title'].'&nbsp;&nbsp;|&nbsp;&nbsp;'; echo 'Sharelink: <a class="small" style="text-decoration: none;" target="sharelink" href="http://www.abridg.com/'.$row['QueryString'].'">www.abridg.com/'.$row['QueryString'].'</a>'; if (!empty($row['FileDescription'])) { $thedescription = substr($row['FileDescription'], 0, 100); if (strlen($row['FileDescription']) > 100) $thedescription .= '...'; echo '<br />Description: '.$thedescription; }; ?></span></td>
			</tr>
			<tr>
			<td>&nbsp;</td>
			<td>
			<!-- Note: IE9 browser for index.php (not assign.php) on Lenovo (not on Vostro!) showed an exclamation point icon instead of the video unless I uriencoded the file url. (Solution discovered http://www.longtailvideo.com/support/forums/jw-player/setup-issues-and-embedding/1758/bad-xml forum, although they used encodeURIComponent (which didn't work for me) rather than encodeURI() (which did work). Rather than risk the assign.php breaking in the future for the same odd reason, I'm preemptively fixing it here too. -->
			<div id="containerA2F<?=$row['FileID']; ?>">Loading the player ...</div>
			<script type="text/javascript">
			var fileurl = '/media/<?=$theFilenameFile; ?>';
			jwplayer("containerA2F<?=$row['FileID']; ?>").setup({ flashplayer: "/jwplayer/player.swf", file: encodeURI(fileurl), height: 145, width:257, skin: "/jwplayer/skins/newtubedark.zip", image: "/snapshots/<?=$theSnapshotFile; ?>", stretching: "fill" }); // Actual video is 288 x 514 (16:9 widescreen); allow extra pixel on either edge
			</script>
			</td>
			</tr>
<?php
			}
		$query = "SELECT *, DATE_FORMAT(CaptureDate, '%M %e, %Y') as CaptureDateReformatted FROM media_table WHERE MediaClass = 'image' AND OwnerID = ".$_SESSION['LoggedInOwnerID']." ORDER BY CaptureDate DESC";
		$result = mysql_query($query) or die('Query (select images from media_table has failed: ' . mysql_error());
		while ($row = mysql_fetch_assoc($result))
			{
			/* Obtain from the Snapshot column value in media_table the actual filename under which the snapshot is stored on the server in the/snapshots directory e.g. if $Snapshot is "piglet.png" then the actual file will be "/snapshots/piglet_683.png, where 683 is the FileID. */
			// This expression looks complex, but it's just a means to insert "_XXX" (where XXX is the media file's FileID) into "myfile.jpg", making it "myfile_XXX.jpg".
			$theSnapshotFile = substr($row['Snapshot'], 0, strrpos($row['Snapshot'], '.')).'_'.$row['FileID'].substr($row['Snapshot'], strrpos($row['Snapshot'], '.'));
?>
			<tr>
			<td valign="top"><input type="checkbox" name="MediaFiles[]" id="FileID<?=$row['FileID']; ?>" value="<?=$row['FileID']; ?>" <?php if ($TheItemsCount > 1) echo 'onclick="if (!this.checked) document.getElementById(\'checkallmedia\').checked = false;"'; ?> <?php $AuthorizedAccountsArray = explode(',', $row['AuthorizedAssociateIDs']); if (in_array($_SESSION['AssociateID'], $AuthorizedAccountsArray)) echo ' checked'; ?>></td>
			<td>File: <?=$row['Filename']; ?>&nbsp;&nbsp;|&nbsp;&nbsp;<?=$row['CaptureDateReformatted']; ?><span style="font-size: 10px; color:#666666;"><br /><?php if (!empty($row['Title'])) echo 'Title: '.$row['Title'].'&nbsp;&nbsp;|&nbsp;&nbsp;'; echo 'Sharelink: <a class="small" style="text-decoration: none;" target="sharelink" href="http://www.abridg.com/'.$row['QueryString'].'">www.abridg.com/'.$row['QueryString'].'</a>'; if (!empty($row['FileDescription'])) { $thedescription = substr($row['FileDescription'], 0, 100); if (strlen($row['FileDescription']) > 100) $thedescription .= '...'; echo '<br />Description: '.$thedescription; }; ?></span></td>
			</tr>
			<tr>
			<td>&nbsp;</td>
			<td>
			<img alt="An Image" src="/snapshots/<?=$theSnapshotFile; ?>">
			</td>
			</tr>
<?php
			}
		$query = "SELECT *, DATE_FORMAT(CaptureDate, '%M %e, %Y') as CaptureDateReformatted FROM media_table WHERE MediaClass = 'audio' AND OwnerID = ".$_SESSION['LoggedInOwnerID']." ORDER BY CaptureDate DESC";
		$result = mysql_query($query) or die('Query (select audio items from media_table has failed: ' . mysql_error());
		while ($row = mysql_fetch_assoc($result))
			{
			/* Obtain from the Filename and Snapshot column values in media_table the actual filenames under which the media item and snapshot are stored on the server in the /media and /snapshots directories respectively e.g. if $Filename is "piglet.png" then the actual file will be "/media/piglet_683.png, where 683 is the FileID. */
			// This expression looks complex, but it's just a means to insert "_XXX" (where XXX is the media file's FileID) into "myfile.jpg", making it "myfile_XXX.jpg".
			$theFilenameFile = substr($row['Filename'], 0, strrpos($row['Filename'], '.')).'_'.$row['FileID'].substr($row['Filename'], strrpos($row['Filename'], '.'));
			$theSnapshotFile = $row['Snapshot']; // In the case of audio items, the snapshot file is just 'generic-audio.png' as set within upload_slave.php.
?>
			<tr>
			<td valign="top"><input type="checkbox" name="MediaFiles[]" id="FileID<?=$row['FileID']; ?>" value="<?=$row['FileID']; ?>" <?php if ($TheItemsCount > 1) echo 'onclick="if (!this.checked) document.getElementById(\'checkallmedia\').checked = false;"'; ?> <?php $AuthorizedAccountsArray = explode(',', $row['AuthorizedAssociateIDs']); if (in_array($_SESSION['AssociateID'], $AuthorizedAccountsArray)) echo ' checked'; ?>></td> 
			<td>File: <?=$row['Filename']; ?>&nbsp;&nbsp;|&nbsp;&nbsp;<?=$row['CaptureDateReformatted']; ?><span style="font-size: 10px; color:#666666;"><br /><?php if (!empty($row['Title'])) echo 'Title: '.$row['Title'].'&nbsp;&nbsp;|&nbsp;&nbsp;'; echo 'Sharelink: <a class="small" style="text-decoration: none;" target="sharelink" href="http://www.abridg.com/'.$row['QueryString'].'">www.abridg.com/'.$row['QueryString'].'</a>'; if (!empty($row['FileDescription'])) { $thedescription = substr($row['FileDescription'], 0, 100); if (strlen($row['FileDescription']) > 100) $thedescription .= '...'; echo '<br />Description: '.$thedescription; }; ?></span></td>
			</tr>
			<tr>
			<td>&nbsp;</td>
			<td>
			<!-- Note: IE9 browser for index.php (not assign.php) on Lenovo (not on Vostro!) showed an exclamation point icon instead of the video unless I uriencoded the file url. (Solution discovered http://www.longtailvideo.com/support/forums/jw-player/setup-issues-and-embedding/1758/bad-xml forum, although they used encodeURIComponent (which didn't work for me) rather than encodeURI() (which did work). Rather than risk the assign.php breaking in the future for the same odd reason, I'm preemptively fixing it here too. -->
			<div id="containerA2F<?=$row['FileID']; ?>">Loading the audio player ...</div>
			<script type="text/javascript">
			var fileurl = '/media/<?=$theFilenameFile; ?>';
			jwplayer("containerA2F<?=$row['FileID']; ?>").setup({ flashplayer: "/jwplayer/player.swf", file: encodeURI(fileurl), height: 120, width:120, controlbar: "none", skin: "/jwplayer/skins/simple.zip", image: "/snapshots/<?=$theSnapshotFile; ?>", stretching: "fill" });
			</script>
			</td>
			</tr>
<?php
			}
		$query = "SELECT *, DATE_FORMAT(CaptureDate, '%M %e, %Y') as CaptureDateReformatted FROM media_table WHERE MediaClass = 'application' AND OwnerID = ".$_SESSION['LoggedInOwnerID']." ORDER BY CaptureDate DESC";
		$result = mysql_query($query) or die('Query (select documents from media_table has failed: ' . mysql_error());
		while ($row = mysql_fetch_assoc($result))
			{
			/* The Snapshot file name for every item of MediaClass == 'application' (i.e. documents) is going to be 'generic-document.png' as stored in the Snapshot column in media_table for every such class of item. (I decided there wasn't good reason to support user upload of snapshot files to accompany document items. */
			$theSnapshotFile = $row['Snapshot'];
?>
			<tr>
			<td valign="top"><input type="checkbox" name="MediaFiles[]" id="FileID<?=$row['FileID']; ?>" value="<?=$row['FileID']; ?>" <?php if ($TheItemsCount > 1) echo 'onclick="if (!this.checked) document.getElementById(\'checkallmedia\').checked = false;"'; ?> <?php $AuthorizedAccountsArray = explode(',', $row['AuthorizedAssociateIDs']); if (in_array($_SESSION['AssociateID'], $AuthorizedAccountsArray)) echo ' checked'; ?>></td>
			<td>File: <?=$row['Filename']; ?>&nbsp;&nbsp;|&nbsp;&nbsp;<?=$row['CaptureDateReformatted']; ?><span style="font-size: 10px; color:#666666;"><br /><?php if (!empty($row['Title'])) echo 'Title: '.$row['Title'].'&nbsp;&nbsp;|&nbsp;&nbsp;'; echo 'Sharelink: <a class="small" style="text-decoration: none;" target="sharelink" href="http://www.abridg.com/'.$row['QueryString'].'">www.abridg.com/'.$row['QueryString'].'</a>'; if (!empty($row['FileDescription'])) { $thedescription = substr($row['FileDescription'], 0, 100); if (strlen($row['FileDescription']) > 100) $thedescription .= '...'; echo '<br />Description: '.$thedescription; }; ?></span></td>
			</tr>
			<tr>
			<td>&nbsp;</td>
			<td>
			<img style="position: relative; bottom: 10px;" alt="Document Thumbnail" width="50" height="60" src="/snapshots/<?=$theSnapshotFile; ?>">
			</td>
			</tr>
<?php
			}
?>
		<tr>
		<td>&nbsp;</td>
		<td><br>
		<input type="submit" name="AssignMediaFilesToAssociate" value="Assign Media" class="buttonstyle">
		</td>
		</tr>
		</table>
		</form>
<?php
		}
	else
		{
		echo '<span class="gloss">You&rsquo;ll need to upload at least one media item (video, image, document, etc.) before you can assign content to a friend or to your &lsquo;My Favorites Gallery&rsquo;. Click the &lsquo;Upload&rsquo; link above.</span>';
		}
?>
	</div>
	<!-- End of nested table -->
	</td>
	</tr>
<?php
	}
else echo '<tr><td colspan="2"><p class="text">You must first add at least one friend to proceed with this assignment. Click the &lsquo;Add&rsquo; link above.</p></td></tr>';	
?>
</table>
</div>
		
<div id="AssignAssociatesToMediaFileScreen" style="text-align: left; margin-left: 150px; margin-right: 150px; margin-top: 50px; display: <?php if (isset($_SESSION['FileSelected'])) echo 'block'; else echo 'none'; ?>">
<table>
<tr style="height: 40px;">
<td style="width: 600px; vertical-align: text-top;"><label>Step 1: Select a media file</label></td>
<td style="width: 300px; text-align: left; vertical-align: text-top;"><div id="themediafilesdiv1" style="display: <?php if (isset($_SESSION['FileSelected'])) echo 'block'; else echo 'none'; ?>;"><label>Step 2: Assign &ldquo;<em><?php if (isset($_SESSION['FileSelected'])) echo $_SESSION['FilenameSelected']; ?></em>&rdquo; to:</label></div></td>
</tr>
<?php
if ($TheItemsCount > 0) // Only display media items if at least one media item has already been uploaded by this Owner.
	{
?>
	<tr style="height: 60px;">
	<td valign="top">
	<!-- Nest within a td cell a table that holds the list of media files, one of which will be selected then assigned to various account holders -->
	<form method="post" name="MediaToAssociates" action="/scripts/assign_slave.php">
	<table cellspacing="0" cellpadding="6" border="0" style="font-size: 14px; font-family: Geneva, Arial, Helvetica, sans-serif; padding: 0px;">
<?php
	$query = "SELECT *, DATE_FORMAT(CaptureDate, '%M %e, %Y') as CaptureDateReformatted FROM media_table WHERE MediaClass = 'video' AND OwnerID = ".$_SESSION['LoggedInOwnerID']." ORDER BY CaptureDate DESC";
	$result = mysql_query($query) or die('Query (select videos from media_table has failed: ' . mysql_error());
	while ($row = mysql_fetch_assoc($result))
		{
		/* Obtain from the Filename and Snapshot column values in media_table the actual filenames under which the media item and snapshot are stored on the server in the /media and /snapshots directories respectively e.g. if $Filename is "piglet.png" then the actual file will be "/media/piglet_683.png, where 683 is the FileID. */
		// This expression looks complex, but it's just a means to insert "_XXX" (where XXX is the media file's FileID) into "myfile.jpg", making it "myfile_XXX.jpg".
		$theFilenameFile = substr($row['Filename'], 0, strrpos($row['Filename'], '.')).'_'.$row['FileID'].substr($row['Filename'], strrpos($row['Filename'], '.'));
		$theSnapshotFile = substr($row['Snapshot'], 0, strrpos($row['Snapshot'], '.')).'_'.$row['FileID'].substr($row['Snapshot'], strrpos($row['Snapshot'], '.'));
?>
		<tr>
		<td valign="top"><input type="radio" name="MediaFile" id="File<?=$row['FileID']; ?>" value="<?=$row['FileID']; ?>" onClick="this.form.submit();" style="position: relative; top: 7px;" <?php if ($CountOfAssociates == 0) echo 'disabled'; elseif ($row['FileID'] == $_SESSION['FileID']) echo 'checked'; ?>></td>
		<td>File: <?=$row['Filename']; ?>&nbsp;&nbsp;|&nbsp;&nbsp;<?=$row['CaptureDateReformatted']; ?>&nbsp;&nbsp;
		<input type="image" name="EditMediaFileButton<?=$row['FileID']; ?>" value="Edit This Media File" title="Edit details of file <?=$row['Filename']; ?>" SRC="/images/edit-icon.png" HEIGHT="22" WIDTH="22" BORDER="0" ALT="Submit button to edit media file" style="position: relative; top: 5px;  border: 0;"><!-- Graphical submit button -->
			&nbsp;
		<input type="image" name="DeleteMediaFileButton<?=$row['FileID']; ?>" value="Delete This Media File" title="Delete <?=$row['Filename']; ?>" SRC="/images/delete-icon.png" HEIGHT="22" WIDTH="22" BORDER="0" ALT="Submit button to delete media file" style="position: relative; top: 3px;  border: 0;"><!-- Graphical submit button --><br />
		<span style="font-size: 10px; color:#666666;">
		<?php if (!empty($row['Title'])) echo 'Title: '.$row['Title'].'&nbsp;&nbsp;|&nbsp;&nbsp;'; echo 'Sharelink: <a class="small" style="text-decoration: none;" target="sharelink" href="http://www.abridg.com/'.$row['QueryString'].'">www.abridg.com/'.$row['QueryString'].'</a>'; if (!empty($row['FileDescription'])) { $thedescription = substr($row['FileDescription'], 0, 100); if (strlen($row['FileDescription']) > 100) $thedescription .= '...'; echo '<br />Description: '.$thedescription; }; ?>
		</span>
		</td>
		</tr>
		<tr>
		<td>&nbsp;</td>
		<td>
		<!-- Note: IE9 browser for index.php (not assign.php) on Lenovo (not on Vostro!) showed an exclamation point icon instead of the video unless I uriencoded the file url. (Solution discovered http://www.longtailvideo.com/support/forums/jw-player/setup-issues-and-embedding/1758/bad-xml forum, although they used encodeURIComponent (which didn't work for me) rather than encodeURI() (which did work). Rather than risk the assign.php breaking in the future for the same odd reason, I'm preemptively fixing it here too. -->
		<div id="containerF2A<?=$row['FileID']; ?>">Loading the player ...</div>
		<script type="text/javascript">
		var fileurl = '/media/<?=$theFilenameFile; ?>';
		jwplayer("containerF2A<?=$row['FileID']; ?>").setup({ flashplayer: "/jwplayer/player.swf", file: encodeURI(fileurl), height: 145, width:257, skin: "/jwplayer/skins/newtubedark.zip", image: "/snapshots/<?=$theSnapshotFile; ?>", stretching: "fill" }); // Actual video is 288 x 514 (16:9 widescreen); allow extra pixel on either edge
		</script>
		</td>
		</tr>
<?php
		}
	$query = "SELECT *, DATE_FORMAT(CaptureDate, '%M %e, %Y') as CaptureDateReformatted FROM media_table WHERE MediaClass = 'image' AND OwnerID = ".$_SESSION['LoggedInOwnerID']." ORDER BY CaptureDate DESC";
	$result = mysql_query($query) or die('Query (select images from media_table has failed: ' . mysql_error());
	while ($row = mysql_fetch_assoc($result))
		{
		/* Obtain from the Snapshot column value in media_table the actual filename under which the snapshot is stored on the server in the/snapshots directory e.g. if $Snapshot is "piglet.png" then the actual file will be "/snapshots/piglet_683.png, where 683 is the FileID. */
		// This expression looks complex, but it's just a means to insert "_XXX" (where XXX is the media file's FileID) into "myfile.jpg", making it "myfile_XXX.jpg".
		$theSnapshotFile = substr($row['Snapshot'], 0, strrpos($row['Snapshot'], '.')).'_'.$row['FileID'].substr($row['Snapshot'], strrpos($row['Snapshot'], '.'));
		?>
		<tr>
		<td valign="top"><input type="radio" name="MediaFile" id="File<?=$row['FileID']; ?>" value="<?=$row['FileID']; ?>" onClick="this.form.submit();" style="position: relative; top: 7px;" <?php if ($CountOfAssociates == 0) echo 'disabled'; else if ($row['FileID'] == $_SESSION['FileID']) echo 'checked'; ?>></td>
		<td>File: <?=$row['Filename']; ?>&nbsp;&nbsp;|&nbsp;&nbsp;<?=$row['CaptureDateReformatted']; ?>&nbsp;&nbsp;
		<input type="image" name="EditMediaFileButton<?=$row['FileID']; ?>" value="Edit This Media File" title="Edit details of file <?=$row['Filename']; ?>" SRC="/images/edit-icon.png" HEIGHT="22" WIDTH="22" BORDER="0" ALT="Submit button to edit media file" style="position: relative; top: 5px;  border: 0;"><!-- Graphical submit button -->
			&nbsp;
		<input type="image" name="DeleteMediaFileButton<?=$row['FileID']; ?>" value="Delete This Media File" title="Delete <?=$row['Filename']; ?>" SRC="/images/delete-icon.png" HEIGHT="22" WIDTH="22" BORDER="0" ALT="Submit button to delete media file" style="position: relative; top: 3px; border: 0;"><!-- Graphical submit button --><br />
		<span style="font-size: 10px; color:#666666;"><br /><?php if (!empty($row['Title'])) echo 'Title: '.$row['Title'].'&nbsp;&nbsp;|&nbsp;&nbsp;'; echo 'Sharelink: <a class="small" style="text-decoration: none;" target="sharelink" href="http://www.abridg.com/'.$row['QueryString'].'">www.abridg.com/'.$row['QueryString'].'</a>'; if (!empty($row['FileDescription'])) { $thedescription = substr($row['FileDescription'], 0, 100); if (strlen($row['FileDescription']) > 100) $thedescription .= '...'; echo '<br />Description: '.$thedescription; }; ?></span></td>
		</tr>
		<tr>
		<td>&nbsp;</td>
		<td>
		<img alt="An Image" src="/snapshots/<?=$theSnapshotFile; ?>">
		</td>
		</tr>
		<?php
		}
	$query = "SELECT *, DATE_FORMAT(CaptureDate, '%M %e, %Y') as CaptureDateReformatted FROM media_table WHERE MediaClass = 'audio' AND OwnerID = ".$_SESSION['LoggedInOwnerID']." ORDER BY CaptureDate DESC";
	$result = mysql_query($query) or die('Query (select videos from media_table has failed: ' . mysql_error());
	while ($row = mysql_fetch_assoc($result))
		{
		/* Obtain from the Filename and Snapshot column values in media_table the actual filenames under which the media item and snapshot are stored on the server in the /media and /snapshots directories respectively e.g. if $Filename is "piglet.png" then the actual file will be "/media/piglet_683.png, where 683 is the FileID. */
		// This expression looks complex, but it's just a means to insert "_XXX" (where XXX is the media file's FileID) into "myfile.jpg", making it "myfile_XXX.jpg".
		$theFilenameFile = substr($row['Filename'], 0, strrpos($row['Filename'], '.')).'_'.$row['FileID'].substr($row['Filename'], strrpos($row['Filename'], '.'));
		$theSnapshotFile = $row['Snapshot']; // In the case of audio items, the snapshot file is just 'generic-audio.png' as set within upload_slave.php.
	?>
		<tr>
		<td valign="top"><input type="radio" name="MediaFile" id="File<?=$row['FileID']; ?>" value="<?=$row['FileID']; ?>" onClick="this.form.submit();" style="position: relative; top: 7px;" <?php if ($CountOfAssociates == 0) echo 'disabled'; elseif ($row['FileID'] == $_SESSION['FileID']) echo 'checked'; ?>></td>
		<td>File: <?=$row['Filename']; ?>&nbsp;&nbsp;|&nbsp;&nbsp;<?=$row['CaptureDateReformatted']; ?>&nbsp;&nbsp;
		<input type="image" name="EditMediaFileButton<?=$row['FileID']; ?>" value="Edit This Media File" title="Edit details of file <?=$row['Filename']; ?>" SRC="/images/edit-icon.png" HEIGHT="22" WIDTH="22" BORDER="0" ALT="Submit button to edit media file" style="position: relative; top: 5px;  border: 0;"><!-- Graphical submit button -->
			&nbsp;
		<input type="image" name="DeleteMediaFileButton<?=$row['FileID']; ?>" value="Delete This Media File" title="Delete <?=$row['Filename']; ?>" SRC="/images/delete-icon.png" HEIGHT="22" WIDTH="22" BORDER="0" ALT="Submit button to delete media file" style="position: relative; top: 3px;  border: 0;"><!-- Graphical submit button --><br />
		<span style="font-size: 10px; color:#666666;">
		<?php if (!empty($row['Title'])) echo 'Title: '.$row['Title'].'&nbsp;&nbsp;|&nbsp;&nbsp;'; echo 'Sharelink: <a class="small" style="text-decoration: none;" target="sharelink" href="http://www.abridg.com/'.$row['QueryString'].'">www.abridg.com/'.$row['QueryString'].'</a>'; if (!empty($row['FileDescription'])) { $thedescription = substr($row['FileDescription'], 0, 100); if (strlen($row['FileDescription']) > 100) $thedescription .= '...'; echo '<br />Description: '.$thedescription; }; ?>
		</span>
		</td>
		</tr>
		<tr>
		<td>&nbsp;</td>
		<td>
		<!-- Note: IE9 browser for index.php (not assign.php) on Lenovo (not on Vostro!) showed an exclamation point icon instead of the video unless I uriencoded the file url. (Solution discovered http://www.longtailvideo.com/support/forums/jw-player/setup-issues-and-embedding/1758/bad-xml forum, although they used encodeURIComponent (which didn't work for me) rather than encodeURI() (which did work). Rather than risk the assign.php breaking in the future for the same odd reason, I'm preemptively fixing it here too. -->
		<div id="containerF2A<?=$row['FileID']; ?>">Loading the audio player ...</div>
		<script type="text/javascript">
		var fileurl = '/media/<?=$theFilenameFile; ?>';
		jwplayer("containerF2A<?=$row['FileID']; ?>").setup({ flashplayer: "/jwplayer/player.swf", file: encodeURI(fileurl), height: 120, width:120, controlbar: "none", skin: "/jwplayer/skins/simple.zip", image: "/snapshots/<?=$theSnapshotFile; ?>", stretching: "fill" }); // Actual video is 288 x 514 (16:9 widescreen); allow extra pixel on either edge
		</script>
		</td>
		</tr>
		<?php
		}
	$query = "SELECT *, DATE_FORMAT(CaptureDate, '%M %e, %Y') as CaptureDateReformatted FROM media_table WHERE MediaClass = 'application' AND OwnerID = ".$_SESSION['LoggedInOwnerID']." ORDER BY CaptureDate DESC";
	$result = mysql_query($query) or die('Query (select documents from media_table has failed: ' . mysql_error());
	while ($row = mysql_fetch_assoc($result))
		{
		/* The Snapshot file name for every item of MediaClass == 'application' (i.e. documents) is going to be 'generic-document.png' as stored in the Snapshot column in media_table for every such class of item. (I decided there wasn't good reason to support user upload of snapshot files to accompany document items. */
		$theSnapshotFile = $row['Snapshot'];
		?>
		<tr>
		<td valign="top"><input type="radio" name="MediaFile" id="File<?=$row['FileID']; ?>" value="<?=$row['FileID']; ?>" onClick="this.form.submit();" style="position: relative; top: 7px;" <?php if ($CountOfAssociates == 0) echo 'disabled'; else if ($row['FileID'] == $_SESSION['FileID']) echo 'checked'; ?>></td>
		<td>File: <?=$row['Filename']; ?>&nbsp;&nbsp;|&nbsp;&nbsp;<?=$row['CaptureDateReformatted']; ?>&nbsp;&nbsp;
		<input type="image" name="EditMediaFileButton<?=$row['FileID']; ?>" value="Edit This Media File" title="Edit details of file <?=$row['Filename']; ?>" SRC="/images/edit-icon.png" HEIGHT="22" WIDTH="22" BORDER="0" ALT="Submit button to edit media file" style="position: relative; top: 5px;  border: 0;"><!-- Graphical submit button -->
			&nbsp;
		<input type="image" name="DeleteMediaFileButton<?=$row['FileID']; ?>" value="Delete This Media File" title="Delete <?=$row['Filename']; ?>" SRC="/images/delete-icon.png" HEIGHT="22" WIDTH="22" BORDER="0" ALT="Submit button to delete media file" style="position: relative; top: 3px; border: 0;"><!-- Graphical submit button --><br />
				<span style="font-size: 10px; color:#666666;"><br /><?php if (!empty($row['Title'])) echo 'Title: '.$row['Title'].'&nbsp;&nbsp;|&nbsp;&nbsp;'; echo 'Sharelink: <a class="small" style="text-decoration: none;" target="sharelink" href="http://www.abridg.com/'.$row['QueryString'].'">www.abridg.com/'.$row['QueryString'].'</a>'; if (!empty($row['FileDescription'])) { $thedescription = substr($row['FileDescription'], 0, 100); if (strlen($row['FileDescription']) > 100) $thedescription .= '...'; echo '<br />Description: '.$thedescription; }; ?></span></td>
		</tr>
		<tr>
		<td>&nbsp;</td>
		<td>
		<img style="position: relative; bottom: 10px;" alt="Document Thumbnail" width="50" height="60" src="/snapshots/<?=$theSnapshotFile; ?>">
		</td>
		</tr>
<?php
		}
?>
	</table>
	</form>
	<!-- End of nested table -->
	</td>
	<td valign="top">
	<!-- Nest within a <td> cell a table that holds the list of account holders to whom the selected media file may be assigned -->
	<div id="theaccountholdersdiv2" style="display: <?php if (isset($_SESSION['FileSelected'])) echo 'block'; else echo 'none'; ?>;">
	<form method="post" name="Associates" action="/scripts/assign_slave.php">
	<table cellspacing="0" cellpadding="6" border="0" style="font-size: 14px; font-family: Geneva, Arial, Helvetica, sans-serif; padding: 0px;">
<?php
	if ($TheAssocCount > 1)
		{
		// Only show an "All" check box if there's more than one associate for the logged in Owner.
?>
		<tr>
		<td>
		<input type="checkbox" id="checkallaccounts" name="checkallaccounts" value="checkallaccounts" onclick="function checkAllAccounts() { <?php	$queryAllChkBx = 'SELECT AssociateID FROM associates_table WHERE OwnerID = '.$_SESSION['LoggedInOwnerID']; $resultAllChkBx = mysql_query($queryAllChkBx) or die('Query (select AssociateID from associates_table for all checkboxes has failed: ' . mysql_error()); while ($line = mysql_fetch_assoc($resultAllChkBx)) { echo "document.getElementById('AssociateID".$line['AssociateID']."').checked = true; ";}; ?> }; function uncheckAllAccounts() { <?php mysql_data_seek($resultAllChkBx, 0); while ($line = mysql_fetch_assoc($resultAllChkBx)) { echo "document.getElementById('AssociateID".$line['AssociateID']."').checked = false; ";}; ?> }; if (this.checked) checkAllAccounts(); else uncheckAllAccounts();" <?php $PresetAllBoxToChecked = true; $queryChkBxPreset = 'SELECT AuthorizedFileIDs FROM associates_table WHERE OwnerID = '.$_SESSION['LoggedInOwnerID']; $resultChkBxPreset = mysql_query($queryChkBxPreset) or die('Query (select AuthorizedFileIDs from associates_table has failed: ' . mysql_error()); while ($bar = mysql_fetch_assoc($resultChkBxPreset)) { $AuthorizedFilesArray = explode(',', $bar['AuthorizedFileIDs']); if (!in_array($_SESSION['FileID'], $AuthorizedFilesArray)) { $PresetAllBoxToChecked = false; break; }; }; if ($PresetAllBoxToChecked == true) echo 'CHECKED'; ?>>
		</td>
		<td>[all]</td>
		</tr>
<?php
		}
	$query = "SELECT AssociateID, AssociateName, AuthorizedFileIDs FROM associates_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
	$result = mysql_query($query) or die('Query (select from associates_table has failed: ' . mysql_error());
	while ($row = mysql_fetch_assoc($result))
		{
?>
		<tr>
		<td><input type="checkbox" name="Associates[]" id="AssociateID<?=$row['AssociateID']; ?>" value="<?=$row['AssociateID']; ?>" <?php if ($TheAssocCount > 1) echo 'onclick="if (!this.checked) document.getElementById(\'checkallaccounts\').checked = false;"'; ?> <?php $AuthorizedFilesArray = explode(',', $row['AuthorizedFileIDs']); if (in_array($_SESSION['FileID'], $AuthorizedFilesArray)) echo ' checked'; ?>></td>
		<td><?=$row['AssociateName']; ?></td>
		</tr>
<?php
		}
?>
	<tr>
	<td colspan="2"><br>
	<input type="submit" name="AssignAssociatesToMediaFile" value="Assign Friends" class="buttonstyle">
	</td>
	</tr>
	</table>
	</form>
	</div>
	<!-- End of nested table -->
	</td>
	</tr>
<?php
	}
else echo '<tr><td colspan="2"><span class="gloss">You must first upload at least one media item before you can assign items to friends. Click the &lsquo;Upload&rsquo; link above.</span></td></tr>';
?>
</table>
</div>
		
<div style="text-align: center;">
<span style="text-align: center; position: relative; top: 50px;"><a style="font-weight: bold" href="/faqhelp.php" onClick="wintasticsecond('/faqhelp.php'); return false;"><img alt="Help Icon" border="0" src="/images/help-icon.png"></a></span>
<?php
require ("/home/paulme6/public_html/abridg/ssi/footer.php");
?>
</div>
</div>
</div>

<div id="semitofullbox" style="display: none;"></div> <!-- This is a placeholder for the overlay screen to be filled (via innnerHTML) via the clicker() function. (It's also similar to the overlay screen 'signupbox' in assign.php.) -->

<?php
// If the visitor to this page is still only semiregistered, show the 'semitofullbox' div and disable the other hyperlinks (courtesy: http://www.sitepoint.com/forums/showthread.php?560556-How-to-disable-a-Hyper-Link) on the page (except the (innocuous) 'Media Gallery', 'Help', and "Log Out" links).
if ($_SESSION['RegOwnerStatus'] == 'semi' || $_SESSION['SemiToFullValidationError'] == 'true')
	{
?>
	<script type="text/javascript">
	showSemiToFullBox();
	FocusFirst();
	document.getElementById('MediaFilesToAssociateLink').removeAttribute('href');
	document.getElementById('MediaFilesToAssociateLink').removeAttribute('onClick');
	document.getElementById('AssociatesToMediaFileLink').removeAttribute('href');
	document.getElementById('AssociatesToMediaFileLink').removeAttribute('onClick');
	document.getElementById('uploadlink').removeAttribute('href');
	document.getElementById('assignlink').removeAttribute('href');
	document.getElementById('addlink').removeAttribute('href');
	document.getElementById('managelink').removeAttribute('href');
	</script>
<?php
	}
?>

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
