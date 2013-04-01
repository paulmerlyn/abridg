<?php
/*
editmedia.php isn't intended for direct access but rather for access via assign_slave.php (such that the user clicked an edit icon next to a media file in assign.php). It is the front-end whereby the Administrator (i.e. Account Owner) can edit details associated with a particular media item identified by $_SESSION['EditMediaFile'] (which is set inside assign_slave.php) - specifically, the Snapshot file (for MediaClass == 'video' only), Title, FileDescription, FileCategory, and CaptureDate. 
	Regarding the PHP progress monitor that displays when the Administrator uploads a snapshot file: The method for implementing this monitor is fully described in the introductory comment in file upload.php.
*/

// Start a session
session_start();

if ($_SESSION['Authenticated'] != 'true') header("Location: /index.php");

// To prevent a premature display of either the "Assign media items to account holder" screen or the "Assign account holders to a media item" screen, unset $_SESSION['AssociateSelected'] and $_SESSION['FileSelected'] session variables, which get set in assign_slave.php. Also, to prevent an unwanted preset of the radio button next to the account holders in assign.php (for an "Assign media items to account holder" operation) or the radio button next to the media items in assign.php (for an "Assign account holders to a media item" operation), when those screens are displayed via a user click on the picture icon in assign.php, unset $_SESSION['AssociateID'] and $_SESSION['FileID'] session variables that are set in assign_slave.php.
unset($_SESSION['AssociateSelected']);
unset($_SESSION['FileSelected']);
unset($_SESSION['AssociateID']);
unset($_SESSION['FileID']);

// Call set_time_limit(0) (i.e. 0 signifies never) in order to override the default of 60 seconds that is set via the max_input_time setting inside the php.ini file (which is stored in my root /public_html folder under paulmerlyn.com.
set_time_limit(0); // Recommended also (see PHP Manual http://www.php.net/manual/en/function.set-time-limit.php) that I use flush() at the end of the script when setting this time limit to never (i.e. 0).

/* Method for PHP progress bar courtesty Christian Stocker (http://svn.php.net/viewvc/pecl/uploadprogress/trunk/examples/), with more valuable commentary here (http://media.nata2.org/2007/04/16/pecl-uploadprogress-example/) and from jazfresh here (http://php.net/manual/en/features.file-upload.php). */
$id = md5(microtime() . rand());

// Connect to DB
$db = mysql_connect('localhost', 'paulme6_merlyn', '')
or die('Could not connect: ' . mysql_error());
mysql_select_db('paulme6_abridg') or die('Could not connect to the abridg database: ' . mysql_error());

// Retrieve account information for FileID identified by $_SESSION['EditMediaFile'] for use in prepopulating the HTML form.
$query = "SELECT * FROM media_table WHERE FileID = ".$_SESSION['EditMediaFile'];
$result = mysql_query($query) or die('Query (select * from media_table) failed: ' . mysql_error().' and the database query string was: '.$query);
$row = mysql_fetch_assoc($result);
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Edit Media File Details</title>
<meta NAME="description" CONTENT="Form to upload media (video, image, etc.) to the Abridg Site">
<link href="/abridg.css" rel="stylesheet" type="text/css">
<link href="/scripts/tigra_calendar/calendar.css" rel="stylesheet" type="text/css">
<script type='text/javascript' language="JavaScript" src="/scripts/windowpops.js"></script>
<script language="JavaScript" type="text/javascript" src="/scripts/tigra_calendar/calendar_us.js"></script>
<script>
/* Begin JS form validation functions. */

// Function testFileType() is used for javascript validation that the file that the user is about to upload is of an appropriate file type. Source: http://www.codestore.net/store.nsf/cmnts/92EB6662D4D026048625697C00413F63?OpenDocument. I use a variant below to test the file type of Snapshot (an optional snapshot file).
function testFileTypeForSnapshot(fileName, fileTypes)
{
var fileName, fileTypes;
var dots = fileName.split(".")
//get the part AFTER the LAST period.
fileType = "." + dots[dots.length-1];
if (fileTypes.join(".").indexOf(fileType) != -1) // The fileType of the fileName was among the allowable fileTypes
	{
	document.getElementById("SnapshotError").style.display = "none";
	return true;
	}
	else
	{
	alert("Your Snapshot image file is of type " + fileType + ". Please only upload files that end in types: \n\n" + (fileTypes.join(" or ")) + "\n\nSelect a new snapshot file or leave this optional field blank.");
	document.getElementById("SnapshotError").style.display = "inline";
	return false;
	}
}

function checkFileDescriptionOnly()
{
// Validate CancNumber and LateCancFee fields.
var fileDescriptionValue = document.getElementById("FileDescription").value;
if (fileDescriptionValue.length > 250) // Field contains more than the 250-character maximum
	{
	document.getElementById("FileDescriptionError").style.display = "inline";
	return false;
	} 
else
	{
	document.getElementById("FileDescriptionError").style.display = "none";
	return true;
	}
}

function checkCaptureDateOnly()
{
// Validate CaptureDate field.
var captureDateValue = document.getElementById("CaptureDate").value;
var illegalCharSet = /[^0-9\/]+/; // Reject everything that contains one or more characters that is neither a slash (/) nor a digit. Note the need to escape the slash.
var reqdCharSet = /\d{2}\/\d{2}\/\d{4}/;  // Required format is MM/DD/YYYY.
if (illegalCharSet.test(captureDateValue)  || !reqdCharSet.test(captureDateValue))
	{
	document.getElementById("CaptureDateError").style.display = "inline";
	return false;
	} 
else
	{
	document.getElementById("CaptureDateError").style.display = "none";
	return true;
	}
}

function checkFileCategoryOnly()
{
// Validate CancNumber and LateCancFee fields.
var fileCategoryValue = document.getElementById("FileCategory").value;
if (fileCategoryValue == '') // Drop-down menu is still in the neutral position
	{
	document.getElementById("FileCategoryError").style.display = "inline";
	return false;
	} 
else
	{
	document.getElementById("FileCategoryError").style.display = "none";
	return true;
	}
}

function checkForm() // Gets called when user clicks the 'EditMedia' submit button/
{
var formvalidity = true;
hideAllErrors();
// Note: the seemingly complex structure of the if/else statements is to prevent the short-circuiting effect that takes place when evaluating the truth of StatementA && StatementB && 		StatementC in javascript -- which would prevent StatementB from being evaluated if StatementA were true and which is not what we want in this situation!
if (document.getElementById('Snapshot').value != '' && !testFileTypeForSnapshot(document.getElementById('Snapshot').value, ['.jpg', '.jpeg', '.JPG', '.JPEG', '.gif', '.GIF', '.png', '.PNG']))		// Only run a validity check on the file type of Snapshot if this field value is non-blank -- because Snapshot is optional.
	{
	formvalidity = false;
	};
if (!checkFileDescriptionOnly()) 
	{
	formvalidity = false;
	};
if (!checkCaptureDateOnly())
	{
	formvalidity = false;
	};
if (!checkFileCategoryOnly()) 
	{
	formvalidity = false;
	};
// If all elements passed the validity check, then formvalidity will still be of value true.
return formvalidity;
} // End of checkForm()

/* This function hideAllErrors() is called by checkForm() and by onblur event. */
function hideAllErrors()
{
document.getElementById("SnapshotError").style.display = "none";
document.getElementById("FileDescriptionError").style.display = "none";
document.getElementById("CaptureDateError").style.display = "none";
document.getElementById("FileCategoryError").style.display = "none";
return true;
}

function FocusFirst()
	{
	if (document.forms.length > 0 && document.forms[1].elements.length > 0)
	document.forms[1].elements[0].focus();
	};
</script>
</head>

<body onLoad="FocusFirst();" onUnload="thepop.close();">
<div id="main" style="text-align: center; padding: 10px 0px 0px 0px;">
<div id="relwrapper">
<div style="margin-top: 10px; text-align: center; padding: 0px;">
<form method="post" action="/index.php">
<input type="submit" class="submitLinkSmall"  name="galleryview" value="Media Gallery">
</form>
</div>

<div class="gloss" style="font-weight: bold; font-variant: small-caps; margin-top: 24px; color: #E1B378;">Abridg Director</div>
<h1 style="margin-top: 12px; font-size: 22px; color: #9F0251; font-family: 'Century Gothic', Geneva, Arial, sans-serif;">Edit Details of Media File <em><?=$row['Filename']; ?></em></h1>
<?php
require('/home/paulme6/public_html/abridg/ssi/adminmenu.php'); // Include the navigation menu.
?>
<div style="width: 700px; margin: 40px auto 0px auto; text-align: left; border: 1px solid #E1B378; padding: 10px 5px 20px 10px;">
<form method="post" name="EditMedia" enctype="multipart/form-data" <?php if ($row['MediaClass'] == 'video') echo 'onSubmit="poptasticDIY(\'/progresswindow.php?ID='.$id.'\', 100, 400, 0, 0, 0, 0, \'no\')"'; ?> action="/scripts/editmedia_slave.php">
<table align="center">
<?php
if (!empty($row['Snapshot'])) // Show the media file's snapshot if one exists in the 'Snapshots' column of the media_table, after first resizing it to thumbnail proportions. Note that the Snapshot column will always contain a value for any image, audio item, or document upload, but it could possibly be empty for a video upload (if the user chose not to upload a snapshot.)
	{
	$TheFileID = $_SESSION['EditMediaFile'];
	if ($row['MediaClass'] == 'image' || $row['MediaClass'] == 'video')
		{
		$theSnapshotFile = substr($row['Snapshot'], 0, strrpos($row['Snapshot'], '.')).'_'.$TheFileID.substr($row['Snapshot'], strrpos($row['Snapshot'], '.')); // This expression looks complex, but it's just a means to insert "_XXX" (where XXX is the media file's FileID) into "myfile.jpg", making it "myfile_XXX.jpg".
		// Scale the snapshot according to whether it's landscape or portrait.
		list($width, $height, $type, $attr) = getimagesize('/home/paulme6/public_html/abridg/snapshots/'.$theSnapshotFile);
		if ($width > $height) // landscape
			{
			$newWidth = 120;
			$newHeight = $height * 120/$width;
			}
		else
			{
			$newHeight = 120;
			$newWidth = $width * 120/$height;
			};
		}
	else // $MediaClass must be 'audio' or 'application' (for documents). Audio items and documents are automatically given generic thumbnails as snapshots (e.g. "generic-audio.png" or "generic-document.png").
		{
		$theSnapshotFile = $row['Snapshot'];
		// For an 'audio' or 'application' MediaClass, the snapshot will be a static generic thumbnail image that should be scaled to 50x60px (for documents) or 60x60px (for audio items).
		if ($row['MediaClass'] == 'audio') $newWidth = 60;
		if ($row['MediaClass'] == 'application') $newWidth = 50;
		$newHeight = 60;
		};
?>
	<tr>
	<td colspan="2" style="text-align:center;"><img style="padding-top: 6px; padding-bottom: 18px;" alt="Snapshot image" src="/snapshots/<?=$theSnapshotFile; ?>" width="<?=$newWidth; ?>" height="<?=$newHeight; ?>"></td>	
	</tr>
<?php
	}
else if ($row['MediaClass'] == 'video') // The Snapshot column of media_table can only ever be empty for a 'video' item. If there's nothing in the Snapshots column of media_table and the media item in question is a video, display a static custom image that says "No snapshot yet uploaded for this video".
	{
?>
	<tr>
	<td colspan="2" style="text-align:center;"><img style="padding-top: 6px; padding-bottom: 18px;" alt="Snapshot image" src="/images/NoSnapshotUploadedYet.png" width="120" height="90"></td>	
	</tr>
<?php
	}
if ($row['MediaClass'] == 'video') // No good reason to show the Snapshot form field unless the media file whose details are to be edited was a video. (Snapshots are automatically generated for media items that are images, and snapshots are automatically given to document items.)
	{
?>
	<tr style="height: 80px;">
	<td style="width: 200px; vertical-align: top; padding-top: 4px;"><label>Snapshot (optional)</label></td>
	<td style="position: relative; top: 0px; width: 700px; vertical-align: top;">
	<input type="hidden" name="UPLOAD_IDENTIFIER" value="<?php echo $id;?>" /> 
	<input name="Snapshot[]" id="Snapshot" type="file" size="39" style="border-color: #000000; border-style: solid; border-width: 1px;" onFocus="this.style.background='#FFFF97'" onBlur="this.style.background='white'; if (this.value != '') testFileTypeForSnapshot(this.form.Snapshot.value, ['.jpg', '.jpeg', '.JPG', '.JPEG', '.gif', '.GIF', '.png', '.PNG']);">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<div class="greytextsmall">Existing snapshot file: <?php if (empty($row['Snapshot'])) echo 'n/a'; else echo '<em>'.$row['Snapshot'].'</em>'; ?>. You may upload a snapshot image to replace<br />this one as a thumbnail to help identify the associated video.</div>
	<div class="error" id="SnapshotError">Please select a .jpg, .jpeg, .gif, or .png file for upload to the server.<br></div><?php if ($_SESSION['MsgSnapshot'] != null) { echo $_SESSION['MsgSnapshot']; $_SESSION['MsgSnapshot']=null; } ?>
	</td> 
	</tr>
<?php
	}
?>
<tr style="height: 60px;">
<td style="width: 200px; vertical-align: top; padding-top: 4px;"><label>Title (optional)</label></td>
<td style="width: 700px; vertical-align: top;">
<input name="Title" id="Title" class="textfield" maxlength="100" size="50" onFocus="this.style.background='#FFFF97'" onBlur="this.style.background='white';" value="<?=$row['Title']; ?>">
<div class="greytextsmall">Maximum 100 characters.</div>
</td>
</tr>
<tr style="height: 110px;">
<td valign="top"><label>Description (optional)</label></td>
<td valign="top">
<textarea name="FileDescription" id="FileDescription" rows="5" cols="80" wrap="soft" style="overflow:auto; height: 75px; width: 520px; font-family: Geneva, Arial, Helvetica, sans-serif;" onFocus="this.style.background='#FFFF97'" onBlur="this.style.background='white'; checkFileDescriptionOnly();"><?=$row['FileDescription']; ?></textarea>
<div class="greytextsmall">Minimum 10 characters. Limit: 250 characters</div>
<div class="error" id="FileDescriptionError">No more than 250 characters of description permitted.<br></div><?php if ($_SESSION['MsgFileDescription'] != null) { echo $_SESSION['MsgFileDescription']; $_SESSION['MsgFileDescription']=null; } ?>
</td>
</tr>
<tr style="height: 60px;">
<td><label>Capture Date</label></td>
<td>
<input type="text" name="CaptureDate" id="CaptureDate" class="textfield" maxlength="10" size="10" value="<?php if (isset($row['CaptureDate'])) { $dateArray = explode('-', $row['CaptureDate']); echo $dateArray[1].'/'.$dateArray[2].'/'.$dateArray[0]; } else echo date('m/d/Y'); ?>" onFocus="this.style.background='#FFFF97'" onBlur="this.style.background='white'; checkCaptureDateOnly();">		
<script language="JavaScript">
	new tcal ({
	'formname': 'EditMedia',
	'controlname': 'CaptureDate'
	});
</script>
<div class="error" id="CaptureDateError"><br>Date must have format MM/DD/YYYY. Use only numbers and slash (/) character.<br></div>
<?php if ($_SESSION['MsgCaptureDate'] != null) { echo $_SESSION['MsgCaptureDate']; $_SESSION['MsgCaptureDate']=null; } ?>
</td>
</tr>
<tr style="height: 60px;">
<td><label>Category</label></td>
<td>
<select name="FileCategory" id="FileCategory" style="font-size: 12px; font-weight:normal; font-family:Geneva,Arial,Helvetica,sans-serif" class="plainoutline" onChange="checkFileCategoryOnly();">
<option value="">&lt;&nbsp;select&nbsp;&gt;</option>
<option value="video_montage_audio_theme" <?php if ($row['FileCategory'] == 'video_montage_audio_theme') echo 'SELECTED'; ?>>Video Montage/Compilation (Audio Theme)</option>
<option value="video_montage_original_audio" <?php if ($row['FileCategory'] == 'video_montage_original_audio') echo 'SELECTED'; ?>>Video Montage/Compilation (Original Audio)</option>
<option value="video_event_edited" <?php if ($row['FileCategory'] == 'video_event_edited') echo 'SELECTED'; ?>>Edited Video Event</option>
<option value="video_raw" <?php if ($row['FileCategory'] == 'video_raw') echo 'SELECTED'; ?>>Raw Video</option>
<option value="image_edited" <?php if ($row['FileCategory'] == 'image_edited') echo 'SELECTED'; ?>>Edited Image</option>
<option value="image_unedited" <?php if ($row['FileCategory'] == 'image_unedited') echo 'SELECTED'; ?>>Unedited Image</option>
<option value="audio_talk" <?php if ($row['FileCategory'] == 'audio_talk') echo 'SELECTED'; ?>>Audio (Talk)</option>
<option value="audio_music" <?php if ($row['FileCategory'] == 'audio_music') echo 'SELECTED'; ?>>Audio (Music)</option>
<option value="document_or_other" <?php if ($row['FileCategory'] == 'document_or_other') echo 'SELECTED'; ?>>Document/Other</option>
</select>
<div class="error" id="FileCategoryError"><br>Please make a selection from the drop-down menu.<br></div><?php if ($_SESSION['MsgFileCategory'] != null) { echo $_SESSION['MsgFileCategory']; $_SESSION['MsgFileCategory']=null; } ?>
</td>
</tr>
<tr>
<td colspan="2" style="text-align: center;"><br>
<input style="" type="submit" name="EditMedia" value="Update" class="buttonstyle" onClick="hideAllErrors(); return checkForm();">
</td>
</tr>
</table>
</form>
</div>

<div style="text-align: center;">
<span style="text-align: center; position: relative; top: 50px;"><a style="font-weight: bold" href="/faqhelp.php" onClick="wintasticsecond('/faqhelp.php'); return false;"><img alt="Help Icon" border="0" src="/images/help-icon.png"></a></span>
<?php
require ("/home/paulme6/public_html/abridg/ssi/footer.php");
?>
</div>

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
<?php
flush(); // Recommended in PHP Manual after use of set_time_limit(0) (ref. http://www.php.net/manual/en/function.set-time-limit.php)
?>
