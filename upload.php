<?php
/*
upload.php is available to a registered account holder (i.e. someone who has an OwnerUsername and OwnerPassword, not just an "owner-in-waiting" whose account was created when a registered Owner added a friend by providing the friend's email address (OwnerUsername) and an OwnerLabel for that friend). A registered account holder is also known as an Owner or an Administrator (cf. super-administrator). The Owner can upload media (e.g. a video or image file). This script is processed by slave upload_slave.php. Note that the slave inserts into the media_table a corresponding row of data associated with that file, and also needs to determine a value for the MediaClass field (i.e. 'image', 'video', audio', etc.) according to the media file's Internet Media Type (i.e. FileType) as well as generate a unique QueryString field (which is inserted into the row of media_table) and create a page with that associated URL where the file can be accessed on the Web in isolation (i.e. no need to log in).
	Note that the method for generating the upload progress bar exploits the PECL Uploadprogress extension (which has to be installed on the server). That extension provides a way to obtain the percentage of the upload that has already been uploaded and is pretty well explained here http://media.nata2.org/2007/04/16/pecl-uploadprogress-example/ and here http://php.net/manual/en/features.file-upload.php (scroll to jazfresh comment). We also need a method for then displaying a dynamic progress bar, and for that I use Jonathan Christensen's method at  http://riotriot.net/2010/02/simple-php-progress-bar/?cmd=continue.
	In a nutshell, (i) include a hidden form element of name = "UPLOAD_IDENTIFIER" and value = some random identifier ($id in my case); (ii) include an onsubmit event handler in the form that opens either an iframe or a popup window to display the progress bar, passing the random identifier as a query string (e.g. progresswindow.php?ID=<?php echo $id;?>); (iii) within progresswindow.php (which uses Javascript to refresh every few seconds), define and call a PHP function (progressBar($percentage), courtesy http://riotriot.net/2010/02/simple-php-progress-bar/?cmd=continue) that takes as its input parameter a percentage; (iv) calculate that $percentage value in progresswindow.php by using the PECL extension-provided function uploadprogress_get_info($id) with the random identifier set in the upload.php form -- note that the extension returns an associative array with useful data such as 'bytes_uploaded' and 'bytes_total', which can be readily divided by one another to get a dynamic % upload so far; (v) don't forget to add the necessary styles for the progress bar to the style sheet if they aren't defined locally.
	Note: As of 11/29/11, viewing of SVG in Google Docs Viewer is unreliable. For that reason, although the code will permit the upload of .svg files (i.e. no client-side or server-side validation blocking), I don't mention .svg files in the documentation (or light grey text on the upload.php screen) as an allowed file type.
*/

// Start a session
session_start();

if ($_SESSION['Authenticated'] != 'true') header("Location: /index.php");

// Call set_time_limit(0) (i.e. 0 signifies never) in order to override the default of 60 seconds that is set via the max_input_time setting inside the php.ini file (which is stored in my root /public_html folder under paulmerlyn.com.
set_time_limit(0); // Recommended also (see PHP Manual http://www.php.net/manual/en/function.set-time-limit.php) that I use flush() at the end of the script when setting this time limit to never (i.e. 0).

/* Method for PHP progress bar courtesty Christian Stocker (http://svn.php.net/viewvc/pecl/uploadprogress/trunk/examples/), with more valuable commentary here (http://media.nata2.org/2007/04/16/pecl-uploadprogress-example/) and from jazfresh here (http://php.net/manual/en/features.file-upload.php). */
$id = md5(microtime() . rand());

// Connect to DB
$db = mysql_connect('localhost', 'paulme6_merlyn', 'fePhaCj64mkik')
or die('Could not connect: ' . mysql_error());
mysql_select_db('paulme6_abridg') or die('Could not connect to the abridg database: ' . mysql_error());
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Upload to Abridg</title>
<meta NAME="description" CONTENT="Form to upload media (video, image, etc.) to the Abridg Site">
<link href="/abridg.css" rel="stylesheet" type="text/css">
<link href="/scripts/tigra_calendar/calendar.css" rel="stylesheet" type="text/css">
<script type='text/javascript' language="JavaScript" src="/scripts/windowpops.js"></script>
<script language="JavaScript" type="text/javascript" src="/scripts/tigra_calendar/calendar_us.js"></script>
<script>
/* Begin JS form validation functions. */
// Function testFileType() is used for javascript validation that the file that the user is about to upload is of an appropriate file type. The function is called onclick of the 'Upload File' button. Source: http://www.codestore.net/store.nsf/cmnts/92EB6662D4D026048625697C00413F63?OpenDocument. I use variants of that function below to test of the file type of both Filename (a required media file) and Snapshot (an optional snapshot file).
function testFileTypeForFilename(fileName, fileTypes)
{
var fileName, fileTypes;
if (!fileName)  // Include this check for a required (i.e. non-optional) file only
	{
	document.getElementById("FilenameError").style.display = "inline";
	return false;
	}
var dots = fileName.split(".")
//get the part AFTER the LAST period.
fileType = "." + dots[dots.length-1];
if (fileTypes.join(".").indexOf(fileType) != -1) // The fileType of the fileName was among the allowable fileTypes
	{
	document.getElementById("FilenameError").style.display = "none";
	return true;
	}
	else
	{
	document.getElementById("FilenameError").style.display = "inline";
	alert("Your file is of type " + fileType + ". Please only upload media files that end in types: \n\n" + (fileTypes.join(" or ")) + "\n\nPlease select a new media file and try again.");
	return false;
	}
}

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

function checkForm() // Gets called when user clicks the 'InsertMedia' submit button/
{
var formvalidity = true;
hideAllErrors();
// Note: the seemingly complex structure of the if/else statements is to prevent the short-circuiting effect that takes place when evaluating the truth of StatementA && StatementB && StatementC in javascript -- which would prevent StatementB from being evaluated if StatementA were true and which is not what we want in this situation!
if (!testFileTypeForFilename(document.getElementById('Filename').value, ['.flv', '.FLV', '.f4v', '.F4V', '.mp4', '.MP4', '.mov', '.MOV', '.jpg', '.jpeg', '.JPG', '.JPEG', '.gif', '.GIF', '.png', '.PNG', '.doc', '.DOC', '.xls', '.XLS', '.xlsx', '.XLSX', '.ppt', '.PPT', '.pptx', '.PPTX', '.pdf', '.PDF', '.ai', '.AI', '.psd', '.PSD', '.tif', '.TIF', '.tiff', '.TIFF', '.dxf', '.DXF', '.svg', '.SVG', '.eps', '.EPS', '.ps', '.PS', '.xps', '.XPS', '.ttf', '.TTF', '.mp3', '.MP3'])) 
	{
	formvalidity = false;
	};
if (document.getElementById('Snapshot').value != '' && !testFileTypeForSnapshot(document.getElementById('Snapshot').value, ['.jpg', '.jpeg', '.JPG', '.JPEG', '.gif', '.GIF', '.png', '.PNG'])) // Only run a validity check on the file type of Snapshot if this field value is non-blank -- because Snapshot is optional.
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
document.getElementById("FilenameError").style.display = "none";
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

function HideRow(rowID)
	{
	var therow = document.getElementById(rowID);
	therow.style.display = 'none';
	}

function ShowRow(rowID)
	{
	var therow = document.getElementById(rowID);
	therow.style.display = '';
	}
</script>
</head>

<body <?php echo 'onUnload="thepop.close();"; document.getElementById("Filename").focus();'; ?>><!-- Close the progress monitor window (file = progresswindow.php) -- which is opened as 'thepop' via poptasticDIY() function, defined in windowpops.js -- on unload of upload.php because at this point the uploading has finished and control is now being passed to the form's action script upload_slave.php. There's no rason to have the upload progress monitor still displaying. -->
<div id="main" style="text-align: center; padding: 10px 0px 0px 0px;">
<div id="relwrapper">

<div style="margin-top: 10px; text-align: center; padding: 0px;">
<form method="post" action="/index.php">
<input type="submit" class="submitLinkSmall"  name="galleryview" value="Media Gallery">
</form>
</div>

<div class="gloss" style="font-weight: bold; font-variant: small-caps; margin-top: 24px; color: #E1B378;">Abridg Director</div>
<h1 style="margin-top: 12px; font-size: 22px; color: #9F0251; font-family: 'Century Gothic', Geneva, Arial, sans-serif;">Upload  to Abridg</h1>
<?php
require('/home/paulme6/public_html/abridg/ssi/adminmenu.php'); // Include the navigation menu.

// Display the form for upload of a file (and, as part of that process, allow him/her to assign access to other account holders).
?>
<div style="width: 820px; margin: 40px auto 0px auto; text-align: left; border: 1px solid #E1B378; padding: 30px 5px 20px 10px;">
<form method="post" name="UploadMedia" enctype="multipart/form-data" onSubmit="var theHeight; if (navigator.appName == 'Microsoft Internet Explorer') { theHeight = 150 } else { theHeight = 120 }; poptasticDIY('/progresswindow.php?ID=<?php echo $id;?>', theHeight, 400, 0, 0, 0, 0, 'no')" action="/scripts/upload_slave.php">
<table align="center">
<tr style="height: 110px;">
<td style="width: 250px; vertical-align: top; padding-top: 20px;"><label>Media File or Document</label></td>
<td style="width: 720px;">
<input type="hidden" name="UPLOAD_IDENTIFIER" value="<?php echo $id;?>" /> 
<input type="hidden" name="MAX_FILE_SIZE" value="1000000000"> <!-- Max file size is 1 gigabyte -->
<input name="Filename[]" id="Filename" type="file" size="39" style="border-color: #000000; border-style: solid; border-width: 1px;" onFocus="this.style.background='#FFFF97'" onBlur="this.style.background='white'; if (this.value != '') testFileTypeForFilename(this.form.Filename.value, ['.flv', '.FLV', '.f4v', '.F4V', '.mp4', '.MP4', '.mov', '.MOV', '.jpg', '.jpeg', '.JPG', '.JPEG', '.gif', '.GIF', '.png', '.PNG', '.doc', '.DOC', '.xls', '.XLS', '.xlsx', '.XLSX', '.ppt', '.PPT', '.pptx', '.PPTX' '.pdf', '.PDF', '.ai', '.AI', '.psd', '.PSD', '.tif', '.TIF', '.tiff', '.TIFF', '.dxf', '.DXF', '.svg', '.SVG', '.eps', '.EPS', '.ps', '.PS', '.xps', '.XPS', '.ttf', '.TTF', '.mp3', '.MP3']);"><!-- Note use of [] in name attribute of Filename and Snapshot fields. I do this to support easier scalability in the future, when I may want to adapt upload.php for upload of multiple media files in one batch. -->
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<div class="greytextsmall">Allowable image formats: .jpg, .gif, .png, .tiff<br />
		  Allowable video formats: .mp4, .flv and .f4v (Flash), .mov<br />
		  Allowable audio formats: .mp3<br />
		Other allowable document types: Word, Excel, PowerPoint, PDF, Photoshop, Adobe Illustrator, AutoCAD DXF, PostScript, EPS, XPS, TTF<br />
		Maximum file size: 1 gigabyte (1000 MB)</div>
<div class="error" id="FilenameError">Please select a .mp4, flv, f4v, mov, jpg, jpeg, gif, png, tiff, doc, docx, xls, xlsx, ppt, pptx, pdf, psd, ai, dxf, ps, eps, xps, ttf, or mp3 file for upload.<br></div><?php if ($_SESSION['MsgFilename'] != null) { echo $_SESSION['MsgFilename']; $_SESSION['MsgFilename']=null; } ?>
</td> 
</tr>
<tr style="height: 60px;">
<td valign="top"><label>Title (optional)</label></td>
<td valign="top">
<input name="Title" id="Title" class="textfield" maxlength="100" size="50" onFocus="this.style.background='#FFFF97'" onBlur="this.style.background='white';" value="<?=$_SESSION['Title']; ?>">
<div class="greytextsmall">Maximum 100 characters.</div>
</td>
</tr>
<tr style="height: 110px;">
<td valign="top"><label>Description (optional)</label></td>
<td valign="top">
<textarea name="FileDescription" id="FileDescription" rows="5" cols="80" wrap="soft" style="overflow:auto; height: 75px; width: 520px; font-family: Geneva, Arial, Helvetica, sans-serif;" onFocus="this.style.background='#FFFF97'" onBlur="this.style.background='white'; checkFileDescriptionOnly();"><?=$_SESSION['FileDescription']; ?></textarea>
<div class="greytextsmall">Minimum 10 characters. Limit: 250 characters</div>
<div class="error" id="FileDescriptionError">No more than 250 characters of description permitted.<br></div><?php if ($_SESSION['MsgFileDescription'] != null) { echo $_SESSION['MsgFileDescription']; $_SESSION['MsgFileDescription']=null; } ?>
</td>
</tr>
<tr style="height: 60px;">
<td><label>Capture Date</label></td>
<td>
<input type="text" name="CaptureDate" id="CaptureDate" class="textfield" maxlength="10" size="10" value="<?php echo date('m/d/Y'); ?>" onFocus="this.style.background='#FFFF97'" onBlur="this.style.background='white'; checkCaptureDateOnly();">		
<script language="JavaScript">
			new tcal ({
			'formname': 'UploadMedia',
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
<select name="FileCategory" id="FileCategory" class="plainoutline" style="font-size: 12px; font-weight:normal; font-family:Geneva,Arial,Helvetica,sans-serif" onChange="checkFileCategoryOnly(); if ((this.value == 'video_montage_audio_theme') || (this.value == 'video_montage_original_audio') || (this.value == 'video_event_edited') || (this.value == 'video_raw')) { 
		ShowRow('snapshotrow'); } else { HideRow('snapshotrow'); };">
<option value="">&lt;&nbsp;select&nbsp;&gt;</option>
<option value="video_montage_audio_theme" <?php if ($_SESSION['FileCategory'] == 'video_montage_audio_theme') echo 'SELECTED'; ?>>Video Montage/Compilation (Audio Theme)</option>
<option value="video_montage_original_audio" <?php if ($_SESSION['FileCategory'] == 'video_montage_original_audio') echo 'SELECTED'; ?>>Video Montage/Compilation (Original Audio)</option>
<option value="video_event_edited" <?php if ($_SESSION['FileCategory'] == 'video_event_edited') echo 'SELECTED'; ?>>Edited Video Event</option>
<option value="video_raw" <?php if ($_SESSION['FileCategory'] == 'video_raw') echo 'SELECTED'; ?>>Raw Video</option>
<option value="image_edited" <?php if ($_SESSION['FileCategory'] == 'image_edited') echo 'SELECTED'; ?>>Edited Image</option>
<option value="image_unedited" <?php if ($_SESSION['FileCategory'] == 'image_unedited') echo 'SELECTED'; ?>>Unedited Image</option>
<option value="audio_talk" <?php if ($_SESSION['FileCategory'] == 'audio_talk') echo 'SELECTED'; ?>>Audio (Talk)</option>
<option value="audio_music" <?php if ($_SESSION['FileCategory'] == 'audio_music') echo 'SELECTED'; ?>>Audio (Music)</option>
<option value="document_or_other" <?php if ($_SESSION['FileCategory'] == 'document_or_other') echo 'SELECTED'; ?>>Document/Other</option>
</select>
<div class="error" id="FileCategoryError"><br>Please make a selection from the drop-down menu.<br></div><?php if ($_SESSION['MsgFileCategory'] != null) { echo $_SESSION['MsgFileCategory']; $_SESSION['MsgFileCategory']=null; } ?>
</td>
</tr>
<tr style="height: 80px; display: none;" id="snapshotrow">
<td style="vertical-align: top; padding-top: 24px;"><label>Snapshot (optional)</label></td>
<td>
<input name="Snapshot[]" id="Snapshot" type="file" size="39" style="border-color: #000000; border-style: solid; border-width: 1px;" onFocus="this.style.background='#FFFF97'" onBlur="this.style.background='white'; if (this.value != '') testFileTypeForSnapshot(this.form.Snapshot.value, ['.jpg', '.jpeg', '.JPG', '.JPEG', '.gif', '.GIF', '.png', '.PNG']);">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<div class="greytextsmall">Recommended for video files only. Upload a snapshot image as a thumbnail to help identify the associated video.</div>
<div class="error" id="SnapshotError">Please select a .jpg, .jpeg, .gif, or .png file for upload to the server.<br></div><?php if ($_SESSION['MsgSnapshot'] != null) { echo $_SESSION['MsgSnapshot']; $_SESSION['MsgSnapshot']=null; } ?>
</td> 
</tr>
<?php
$query = "SELECT COUNT(*) AS TheCount FROM associates_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
$result = mysql_query($query) or die('Query (select COUNT(*) from associates_table has failed: ' . mysql_error());
$row = mysql_fetch_assoc($result);
if ($row['TheCount'] > 0)
	{
	$query = "SELECT AssociateName, AssociateID FROM associates_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
	$result = mysql_query($query) or die('Query (select AssociateName, AssociateID from associates_table has failed: ' . mysql_error());
?>
	<tr>
	<td style="vertical-align: top; padding-top: 8px;"><label>Assign Access (optional)</label></td>
	<td>
	<!-- Nest within a <td> cell a table that holds the list of accounts to which the media item may be assigned -->
<table id="assignablemedia" cellspacing="0" cellpadding="6" border="0" style="font-size: 14px; font-family: Geneva, Arial, Helvetica, sans-serif; padding: 0px;">
	<tr>
	<td>
	<input type="checkbox" id="checkall" name="checkall" value="checkall" onclick="function checkAll() { <?php	while ($row = mysql_fetch_assoc($result)) { echo "document.getElementById('Account".$row['AssociateID']."').checked = true; ";}; ?> }; function uncheckAll() { <?php mysql_data_seek($result, 0); while ($row = mysql_fetch_assoc($result)) { echo "document.getElementById('Account".$row['AssociateID']."').checked = false; ";}; ?> }; if (this.checked) checkAll(); else uncheckAll();">
	</td>
	<td>[all]</td>
	</tr>
<?php
	mysql_data_seek($result, 0); 
	while ($row = mysql_fetch_assoc($result))
		{
		echo '<tr>';
		echo '<td><input type="checkbox" name="Associates[]" id="Account'.$row['AssociateID'].'" value="'.$row['AssociateID'].'"></td>';
		echo '<td>'.$row['AssociateName'].'</td>';
		echo '</tr>';
		}
?>
	</table>
	<!-- End of nested table -->
	</td>
	</tr>
<?php
	}
?>
<tr>
<td colspan="2" style="text-align: center;"><br>
<input style="" type="submit" name="InsertMedia" value="Upload Media" class="buttonstyle" onClick="hideAllErrors(); return checkForm();">
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