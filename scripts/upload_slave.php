<?php
/*
upload_slave.php inserts a new row into the media_table. Specifically, it inserts OwnerID (obtained from upload.php via $_SESSION['LoggedInOwnerID'], Filename, Snapshot (applicable to video categories only, and optional), AuthorizedAssociateIDs (optional), FileDescription, FileType, FileCategory, CaptureDate (when the media item was recorded), and UploadDate (date/time of the upload) into the media_table. It also automatically assigns a FileID (for the Filename media item) via autoincrement and a unique QueryString (for accessing the uploaded media item on a unique web page without needing to be a logged-in authorized account holder) automatically. And it determines a value for the MediaClass field (i.e. 'image', 'video', audio', etc.) according to the media file's Internet Media Type (i.e. FileType).
	For example, the video of Filename = "CharlottePlaying.flv" may be assigned to the 'Roger & Estelle' and 'Mel & Carole' associates. However, using the automatically created QueryString = "5ui89yT", the respective account holders may wish to share the video with their own friends via web page www.abridg.com?5ui89yT.
	In addition, if the Administrator (aka account holder or Owner) assigned one or more associates of AssociateID == XX to the media file, upload_slave.php will append the FileID to the AuthorizedFileIDs column of associates_table for each row corresponding to AssociateID==XX. Thus the AuthorizedAssociateIDs column in media_table and the AuthorizedFileIDs column in associates_table are synchronized. upload_slave.php will also insert a new row into assign_table for each AssociateID to whom the file of FileID was assigned.
	Note: As of 11/29/11, viewing of SVG in Google Docs Viewer is unreliable. For that reason, although the code will permit the upload of .svg files (i.e. no client-side or server-side validation blocking), I don't mention .svg files in the documentation (or light grey text on the upload.php screen) as an allowed file type.
*/

// Start a session
session_start();

// Rather than set "gd.jpeg_ignore_warning = 1" directly within php.ini (which I could certainly do as a global patch), I alternatively set it just for upload_slave.php via PHP's ini_set() function. The patch is to suppress overly zealous error detection by GD functions such as imagecreatefromjpeg() used in this script. I found the solution courtesy of http://pyrocms.com/forums/topics/view/1817 and https://bugs.php.net/bug.php?id=39918.
ini_set("gd.jpeg_ignore_warning", 1);

if ($_SESSION['Authenticated'] != 'true') header("Location: /index.php");

ob_start(); // Used in conjunction with ob_flush() [see www.tutorialized.com/view/tutorial/PHP-redirect-page/27316], this allows me to postpone issuing output to the screen until after the header has been sent.

// Connect to mysql
$db = mysql_connect('localhost', 'paulme6_merlyn', 'fePhaCj64mkik')
or die('Could not connect: ' . mysql_error());
mysql_select_db('paulme6_abridg') or die('Could not select database: ' . mysql_error());

require('../ssi/alertgenerator.php'); // Include the alertgenerator.php file, which contains the alertgenerator($assocsarray) function for generating alert email messages to inform an associate that a Content Producer (in this case, the logged in Owner) has assigned new content to him/her.

// Create short variable names
$InsertMedia = $_POST['InsertMedia'];
$Filename = $_FILES['Filename']['name'][0]; // For easier scalability (i.e. potential of multiple media file uploads in the future), I'm using array format (i.e. []) for Filename and Snapshot in the input tag names of Filename and Snapshot in update.php HTML form -- see http://php.net/manual/en/features.file-upload.multiple.php
$Snapshot = $_FILES['Snapshot']['name'][0];
$Title = $_POST['Title'];
$FileDescription = $_POST['FileDescription'];
$CaptureDate = $_POST['CaptureDate'];
$FileCategory = $_POST['FileCategory'];
$Associates = $_POST['Associates']; // This is an array
	
// Assign values to the session variables that are used to prepopulate the preserved values of the posted field values in addassociate.php if/when that page is represented to the user with form validation errors.
$_SESSION['Title'] = $Title;
$_SESSION['FileDescription'] = $FileDescription;
$_SESSION['CaptureDate'] = $CaptureDate;
$_SESSION['FileCategory'] = $FileCategory;
$_SESSION['Associates'] = $Associates; // This is an array.
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>upload Slave Script</title>
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
$_SESSION['MsgFilename'] = NULL;
$_SESSION['MsgSnapshot'] = NULL;
$_SESSION['MsgFileDescription'] = NULL;
$_SESSION['MsgCaptureDate'] = NULL;
$_SESSION['MsgFileCategory'] = NULL;

// Seek to validate $Filename (required field i.e. must not be empty)
$illegalCharSet = '[#^*+`|";,<>]+'; // Exclude everything except A-Z, a-z, numbers, period, hyphen, apostrophe (single quote), $, %, ~, &, ?, =, !, slash, backslash, space, period, and parentheses. (Note that spaces and apostrophes in the filename are allowed but are replaced later below with underscores via str_replace().)
if (ereg($illegalCharSet, $Filename))
	{
	$_SESSION['MsgFilename'] = "<span class='errorphp'><br>Please select and upload a valid media file or document.<br></span>";
	$_SESSION['phpinvalidflag'] = true; 
	};

// Seek to validate $Snapshot (optional field)
$illegalCharSet = '[#^*+`|";,<>]+'; // Exclude everything except A-Z, a-z, numbers, period, hyphen, apostrophe (single quote), $, %, ~, &, ?, =, !, slash, backslash, space, period, and parentheses. (Note that spaces and apostrphoes in the filename are allowed but are replaced later below with underscores via str_replace().)
if (!empty($Snapshot) && ereg($illegalCharSet, $Snapshot))
	{
	$_SESSION['MsgSnapshot'] = "<span class='errorphp'><br>Please select and upload a valid snapshot file.<br></span>";
	$_SESSION['phpinvalidflag'] = true; 
	};

// Seek to validate $FileDescription (required field)
if (strlen($FileDescription) > 250) // Require no more than 250 characters (including spaces)
	{
	$_SESSION['MsgFileDescription'] = "<span class='errorphp'>Maximum of 250 characters.<br></span>";
	$_SESSION['phpinvalidflag'] = true; 
	};

// Seek to validate $CaptureDate (required)
$illegalCharSet = '/[^0-9\/]+/'; // Reject everything that contains one or more characters that is neither a slash (/) nor a digit. (Note the need to escape the slash.)
$reqdCharSet = '[0-1][0-9]\/[0-3][0-9]\/[0-9]{4}';  // Required format is MM/DD/YYYY. (Note my choice to use ereg for reqdCharSet (less confusing re slashes than using preg_match.)
if (preg_match($illegalCharSet, $CaptureDate) || !ereg($reqdCharSet, $CaptureDate))
	{
	$_SESSION['MsgCaptureDate'] = '<span class="errorphp"><br>Date must have format MM/DD/YYYY. Use only numbers and slash (/) character.<br></span>';
	$_SESSION['phpinvalidflag'] = true; 
	}

// Seek to validate $FileCategory (required field)
if ($FileCategory == "")
	{
	$_SESSION['MsgFileCategory'] = '<span class="errorphp"><br>Please make a selection from the drop-down menu.<br></span>';
	$_SESSION['phpinvalidflag'] = true; 
	};

//Now go back to the previous page (upload.php) and show any PHP inline validation error messages if the $_SESSION['phpinvalidflag'] has been set to true. ... otherwise, proceed to update the database with the user's form data.
if ($_SESSION['phpinvalidflag'])
	{
	?>
	<script type='text/javascript' language='javascript'>window.location = '/upload.php';</script>
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

// Assess the error code associated with the filename for the media file upload for any problem (i.e. non-zero code).
if ($_FILES['Filename']['error'][0] > 0)
	{
	echo '<p class=\'text\' style=\'margin-left: 150px; margin-right: 150px; margin-top: 40px; margin-bottom: 20px;\'>Problem: ';
	switch ($_FILES['Filename']['error'][0])
		{
		case 1: echo 'Media file exceeded upload_max_file_size which was set at 1GB.'; break;
		case 2: echo 'Your media file exceeded the maximum allowable file size.'; break;
		case 3: echo 'Media file only partially uploaded.'; break;
		case 4: echo 'No media file uploaded.'; break;
		}
	echo ' Use your browser\'s Back button and try again.</p></body>';
	echo '</html>';
	ob_flush();
	exit;
	}

// Assess the error code associated with the filename for the (optional) snapshot file upload for any problem (i.e. non-zero code).
if (!empty($Snapshot) && $_FILES['Snapshot']['error'][0] > 0)
	{
	echo '<p class=\'text\' style=\'margin-left: 150px; margin-right: 150px; margin-top: 40px; margin-bottom: 20px;\'>Problem: ';
	switch ($_FILES['Snapshot']['error'][0])
		{
		case 1: echo 'Snapshot file exceeded upload_max_file_size.'; break;
		case 2: echo 'Your snapshot file exceeded the maximum allowable file size.'; break;
		case 3: echo 'Snapshot file only partially uploaded.'; break;
		case 4: echo 'No Snapshot file uploaded.'; break;
		}
	echo ' Use your browser\'s Back button and try again.</p></body>';
	echo '</html>';
	ob_flush();
	exit;
	}

// Does the media file have the correct MIME type? Note the need to treat a type == 'application/octet-stream' as permissible along with more explicit video and image types. This is necessary b/c my server obtains the unhelpful type value of "application/octet-stream" for .flv files rather than recognize them as, say, 'video/x-flv'.
if ($_FILES['Filename']['type'][0] != 'video/x-flv' && $_FILES['Filename']['type'][0] != 'video/flv' && $_FILES['Filename']['type'][0] != 'video/mp4' && $_FILES['Filename']['type'][0] != 'video/quicktime' && $_FILES['Filename']['type'][0] != 'video/x-quicktime' && $_FILES['Filename']['type'][0] != 'image/jpg' && $_FILES['Filename']['type'][0] != 'image/jpeg' && $_FILES['Filename']['type'][0] != 'image/pjpeg' && $_FILES['Filename']['type'][0] != 'image/gif' && $_FILES['Filename']['type'][0] != 'image/png' && $_FILES['Filename']['type'][0] != 'application/octet-stream' && $_FILES['Filename']['type'][0] != 'application/msword' && $_FILES['Filename']['type'][0] != 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' && $_FILES['Filename']['type'][0] != 'application/vnd.ms-excel' && $_FILES['Filename']['type'][0] != 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' && $_FILES['Filename']['type'][0] != 'application/vnd.ms-powerpoint' && $_FILES['Filename']['type'][0] != 'application/vnd.openxmlformats-officedocument.presentationml.presentation' && $_FILES['Filename']['type'][0] != 'application/pdf' && $_FILES['Filename']['type'][0] != 'application/x-pdf' && $_FILES['Filename']['type'][0] != 'application/x-photoshop' && $_FILES['Filename']['type'][0] != 'image/psd' && $_FILES['Filename']['type'][0] != 'image/vnd.adobe.photoshop' && $_FILES['Filename']['type'][0] != 'image/x-photoshop' && $_FILES['Filename']['type'][0] != 'application/postscript' && $_FILES['Filename']['type'][0] != 'application/illustrator' && $_FILES['Filename']['type'][0] != 'application/dxf' && $_FILES['Filename']['type'][0] != 'application/x-autocad' && $_FILES['Filename']['type'][0] != 'image/vnd.dwg' && $_FILES['Filename']['type'][0] != 'image/x-dwg' && $_FILES['Filename']['type'][0] != 'application/x-dxf' && $_FILES['Filename']['type'][0] != 'drawing/x-dxf' && $_FILES['Filename']['type'][0] != 'image/vnd.dxf' && $_FILES['Filename']['type'][0] != 'image/x-autocad' && $_FILES['Filename']['type'][0] != 'image/x-dxf' && $_FILES['Filename']['type'][0] != 'text/plain' && $_FILES['Filename']['type'][0] != 'zz-application/zz-winassoc-dxf' && $_FILES['Filename']['type'][0] != 'application/eps' && $_FILES['Filename']['type'][0] != 'application/x-eps' && $_FILES['Filename']['type'][0] != 'image/eps' && $_FILES['Filename']['type'][0] != 'image/x-eps' && $_FILES['Filename']['type'][0] != 'application/vnd.ms-xpsdocument' && $_FILES['Filename']['type'][0] != 'application/x-font-ttf' && $_FILES['Filename']['type'][0] != 'font/ttf' && $_FILES['Filename']['type'][0] != 'image/tiff' && $_FILES['Filename']['type'][0] != 'image/tif' && $_FILES['Filename']['type'][0] != 'image/x-tif' && $_FILES['Filename']['type'][0] != 'image/x-tiff' && $_FILES['Filename']['type'][0] != 'application/tif' && $_FILES['Filename']['type'][0] != 'application/tiff' && $_FILES['Filename']['type'][0] != 'application/x-tif' && $_FILES['Filename']['type'][0] != 'application/x-tiff' && $_FILES['Filename']['type'][0] != 'image/svg+xml' && $_FILES['Filename']['type'][0] != 'image/svg-xml' && $_FILES['Filename']['type'][0] != 'audio/mpeg' && $_FILES['Filename']['type'][0] != 'audio/x-mpeg' && $_FILES['Filename']['type'][0] != 'audio/x-mp3' && $_FILES['Filename']['type'][0] != 'audio/mpeg3' && $_FILES['Filename']['type'][0] != 'audio/x-mpeg3' && $_FILES['Filename']['type'][0] != 'audio/x-mpg' && $_FILES['Filename']['type'][0] != 'audio/x-mpegaudio' && $_FILES['Filename']['type'][0] != 'audio/mp3' && $_FILES['Filename']['type'][0] != 'audio/mpg')
	{
	echo "<p class='text' style='margin-left: 150px; margin-right: 150px; margin-top: 40px; margin-bottom: 20px;'>";
	echo 'Your media file is an '.$_FILES['Filename']['type'][0].' file. This is a problem.<br><br>' ;
	echo 'Your file must be of type .flv, f4v, mp4, mov, jpg, jpeg, gif, png, tiff, tif, svg, mp3, doc, docx, xls, xlsx, ppt, pptx, pdf, psd, ai, dxf, ps, eps, xps, or ttf.<br><br>';
	echo 'Please <a href=\'/upload.php\' onclick=\'javascript: window.open("/upload.php",\'_self\'); return false;\'>try again</a> using a compatible file type.';
	echo "</p>";
	echo '</body>';
	echo '</html>';
	ob_flush();
	exit;
	}

// Does the (optional) Snapshot file have the correct MIME type (if the administrator uploaded a snapshot)?
if (!empty($Snapshot))
	{ 
	if ($_FILES['Snapshot']['type'][0] != 'image/jpg' && $_FILES['Snapshot']['type'][0] != 'image/jpeg' && $_FILES['Snapshot']['type'][0] != 'image/pjpeg' && $_FILES['Snapshot']['type'][0] != 'image/gif' && $_FILES['Snapshot']['type'][0] != 'image/png')
		{
		echo "<p class='text' style='margin-left: 150px; margin-right: 150px; margin-top: 40px; margin-bottom: 20px;'>";
		echo 'Your snapshot file is an '.$_FILES['Snapshot']['type'][0].' file. This is a problem.<br><br>' ;
		echo 'Your snapshot file must be of type .jpg or .jpeg or .gif or .png.<br><br>';
		echo 'Please <a href=\'/upload.php\' onclick=\'javascript: window.open("/upload.php",\'_self\'); return false;\'>try again</a> using a compatible file type.';
		echo "</p>";
		echo '</body>';
		echo '</html>';
		ob_flush();
		exit;
		}
	}

// Next, as an additional security check, examine the uploaded media file's extension.
$fileExt = strrchr($_FILES['Filename']['name'][0], '.'); // Note: I could alternatively (and do later below) use pathinfo(myfilename, PATHINFO_EXTENSION) here instead to get the extension. It's actually a little faster.
$allowableExtensions = array(".flv", ".FLV", ".f4v", ".F4V", ".mp4", ".MP4", ".mov", ".MOV", ".jpg", ".JPG",".jpeg", ".JPEG", ".gif", ".GIF", ".png", ".PNG", ".doc", ".DOC", ".xls", ".XLS", ".xlsx", ".XLSX", ".ppt", ".PPT", ".pptx", ".pdf", ".PDF", ".PPTX", ".ai", ".AI", ".psd", ".PSD", ".tiff", ".TIFF", ".tif", ".TIF", ".dxf", ".DXF", ".svg", ".SVG", ".eps", ".EPS", ".ps", ".PS", ".xps", ".XPS", ".ttf", ".TTF", ".mp3", ".MP3");
if (!in_array($fileExt, $allowableExtensions))
	{
	echo '<p class="text" style="margin-left: 150px; margin-right: 150px; margin-top: 40px; margin-bottom: 20px;">Your media file must be of type "mp4" or "flv" or "f4v" or "mov" or "jpg" or "jpeg" or "png" or "gif" or "mp3" or "doc" or "xls" or "xlsx" or "ppt" or "pptx" or "pdf" or "ai" or "psd" or "tiff" or "tif" or "dxf" or "svg" or "eps" or "ps" or "xps" or "ttf". Please <a href=\'javascript: location.href = "/upload.php";\'>try again</a> using a file of this type.</p>';
	echo '</body>';
	echo '</html>';
	ob_flush();
	exit;
	}     

// Also, as an additional security check, examine the (optional) snapshot file's extension if the administrator uploaded one.
if (!empty($Snapshot))
	{ 
	$fileExt = strrchr($_FILES['Snapshot']['name'][0], '.'); 
	$allowableExtensions = array(".jpg", ".JPG",".jpeg", ".JPEG", ".gif", ".GIF", ".png", ".PNG");
	if (!in_array($fileExt, $allowableExtensions))
		{
		echo '<p class="text" style="margin-left: 150px; margin-right: 150px; margin-top: 40px; margin-bottom: 20px;">Your snapsho file must be of type "jpg" or "jpeg" or "png" or "gif". Please <a href=\'javascript: location.href = "/upload.php";\'>try again</a> using a file of this type.</p>';
		echo '</body>';
		echo '</html>';
		ob_flush();
		exit;
		}
	}    

/* Manipulate data prior to insertion into the media_table */

// Decontaminate $Filename, removing/replacing problematic characters in the name 
$Filename = str_replace('&', 'and', $Filename);
$Filename = str_replace(' ', '_', $Filename);
$Filename = str_replace("'", "", $Filename); // Remove any apostrophes (') in filename. (Apache needs either this or urlencode()'ing of the filename.)
$myArray = explode('.', $Filename);  // Also remove any periods (.) except for the final period before the file extension (e.g. ".jpg").
$NofParts = sizeof($myArray);
$myString = '';
for ($i=0; $i < $NofParts - 1; $i++)
	{
	$myString .= $myArray[$i];
	}
$myString = $myString.'.'.$myArray[$NofParts - 1];
$Filename = $myString;

// Decontaminate $Snapshot, removing/replacing problematic characters in the name 
if (!empty($Snapshot))
	{
	$Snapshot = str_replace('&', 'and', $Snapshot);
	$Snapshot = str_replace(' ', '_', $Snapshot);
	$Snapshot = str_replace("'", "", $Snapshot); // Remove any apostrophes (') in filename. (Apache needs either this or urlencode()'ing of the filename.)
	$myArray = explode('.', $Snapshot);  // Also remove any periods (.) except for the final period before the file extension (e.g. ".jpg").
	$NofParts = sizeof($myArray);
	$myString = '';
	for ($i=0; $i < $NofParts - 1; $i++)
		{
		$myString .= $myArray[$i];
		}
	$myString = $myString.'.'.$myArray[$NofParts - 1];
	$Snapshot = $myString;
	}

// Manipulate (obtain) the FileType (i.e. the Media File Type, formerly known as the MIME Type) of the media file.
// Note the special exception handling of .flv files. This is necessary b/c my server obtains the unhelpful FileType value of "application/octet-stream" when I upload a .flv file, which wouldn't produce a MediaClass of "video" when manipulated (see below). So, I manually force the FileType to "video/x-flv" whenever I detect the file extension (using PHP's pathinfo() function) to be "flv".
$ExtOfFilename = pathinfo($Filename, PATHINFO_EXTENSION);
if ($ExtOfFilename == 'flv')
	{
	$FileType = 'video/x-flv';
	}
else
	{
	$FileType = $_FILES['Filename']['type'][0];
	}
	
/* Manipulate (determine) the value to be inserted into the MediaClass column of the media_table (i.e. 'video', 'image', 'audio', 'application' [commonly used for documents such as Word, PowerPoint, etc.], and so on) by examining the media file's $FileType (i.e. the Media File Type, formerly known as the MIME Type). See http://en.wikipedia.org/wiki/Internet_media_type#List_of_common_media_types for list. */
$firstinstance = strpos($FileType, '/'); // Find the position of the first slash such as in "video/mp4" or in "image/gif".
$MediaClass = substr($FileType, 0, $firstinstance); // Obtain just the part of $FileType from the beginning up until $firstinstance.
// Having done that, we now need to make some corrective adjustments. Certain file types (specifically, Photoshop, DXF CAD drawings, EPS, TIFF, and SVG files) may have a Media File (MIME) Type that begins with "image/". Without a corrective adjustment, my code would give then them a $MediaClass of 'image'. However, because none of these file types is yet broadly supported for native display in browsers (SVG may be in a couple of years), my code needs to give a $MediaClass of 'application' so that they aren't handled by display files such as index.php and assign.php as if they were images. By instead giving them a $MediaClass of 'application', they will be handled (i.e. displayed) via the Google Docs Viewer (see https://docs.google.com/viewer).
if ($FileType == 'image/psd' || $FileType == 'image/vnd.adobe.photoshop' || $FileType == 'image/x-photoshop' || $FileType == 'image/vnd.dxf' || $FileType == 'image/x-autocad' || $FileType == 'image/x-dxf' || $FileType == 'image/vnd.dwg' || $FileType == 'image/x-dwg' || $FileType == 'text/plain' || $FileType == 'image/eps' || $FileType == 'image/x-eps' || $FileType == 'image/tiff' || $FileType == 'image/tif' || $FileType == 'image/x-tif' || $FileType == 'image/x-tiff' || $FileType == 'image/svg+xml' || $FileType == 'image/svg-xml') $MediaClass = 'application';


/* Define third-party createThumbnail() function to reduce size of an image. Ref. http://nodstrum.com/2006/12/09/image-manipulation-using-php/ for details (Note: also used in nrmedlic file processprofile.php.) */
function createThumbnail($img, $imgPath, $suffix, $newWidth, $newHeight, $quality)
	{
	// Use the file's extension (obtained from pathinfo()) to determine whether we should use imagecreatefromjpeg(), imagecreatefromgif(), or imagecreatefrompng() in the thumbnail creation process.
	switch (strtolower(pathinfo($img, PATHINFO_EXTENSION))) // Note: use of strtolower() alleviates need to case, say, "JPG" in addition to "jpg", etc.
		{
		case 'jpg': $original = imagecreatefromjpeg("$imgPath/$img") or die("Error Opening original"); break;
		case 'jpeg': $original = imagecreatefromjpeg("$imgPath/$img") or die("Error Opening original"); break;
		case 'gif': $original = imagecreatefromgif("$imgPath/$img") or die("Error Opening original"); break;
		case 'png': $original = imagecreatefrompng("$imgPath/$img") or die("Error Opening original"); break;
		default: echo '<p class="text">Sorry. Your filename appears to be an image file that is not supported. Please provide images as .jpg, .jpeg, .gif, or .png file types only. Use the Back button in your browser and try again.</p>'; exit;
		}
			
	// Open and create the original image from the path/file in the argument using PHP's imagecreatefromjpeg() function.
	list($width, $height, $type, $attr) = getimagesize("$imgPath/$img");

	// Resample the image.
	$tempImg = imagecreatetruecolor($newWidth, $newHeight) or die("Cant create temp image");
	imagecopyresized($tempImg, $original, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height) or die("Cant resize copy");

	// Create the new file name. It would actually take the same name as before if I omitted the suffix (set it to '') when calling the createThumbnail() function.
	$newNameE = explode(".", $img);
	$Snapshot = $newNameE[0].$suffix.'.'.$newNameE[1];

	// Save the Snapshot image to the /snapshots directory.
	imagejpeg($tempImg, '/home/paulme6/public_html/abridg/snapshots/'.$Snapshot, $quality) or die("Unable to save snapshot image.");

	// Clean up.
	imagedestroy($original);
	imagedestroy($tempImg);
	return $Snapshot;
	}

// Move (via the move_uploaded_file function) the media file to where we want it.
$originalFileName = $Filename; // (Albeit, after replacement of any extraneous spaces, periods, and any apostrophes)
$upfile = '../media/'.$originalFileName; // A reference to the new path (location) of the media file (which shall retain its original name)

if (is_uploaded_file($_FILES['Filename']['tmp_name'][0])) // Thus, is_uploaded_file() is called!!
	{
	if (!move_uploaded_file($_FILES['Filename']['tmp_name'][0], $upfile)) // Thus, move_uploaded_file() is called!!
		{
		echo 'Problem: Could not move media file to destination directory.';
		exit;
		}
	}
else
	{
	echo 'Problem: Possible file upload attack. Filename: ';
	echo $_FILES['Filename']['name'][0];
	exit;
	}

// If the user bothered to upload a Snapshot file, move (via the move_uploaded_file function) the Snapshot file to the /snapshots directory. Note that the snapshot file (if the user bothered to upload one) might pertain either to an image media file (note: upload.php doesn't actually present the Snapshot file upload field for a user drop-down selected File Category of an image or a document, so this shouldn't ever really happen in practice) or to a video media file. If for an image file, we should scale it to 120x90px (landscape) or 90x120px (portrait) after moving it. There's no need to scale the snapshot if it's for a video file because all snapshots associated with MediaClass == 'video' get manipulated below anyway (two images "jwplayerbar.png" and "jwplayerbutton.png" are overlaid on such video snapshots). Note also that audio items and documents (e.g. Word, PDF, Photoshop, etc.) are matched with a generic "snapshot" .png file according to MediaClass and file extension.
// Part I: Moving the uploaded snapshot file.
$originalFileName = $Snapshot;  // (Albeit, after replacement of any extraneous spaces, periods, and any apostrophes)
$upfile = '../snapshots/'.$originalFileName; // A reference to the new path (location) of the snapshot file (which shall retain its original name)
if (!empty($Snapshot))
	{
	if (is_uploaded_file($_FILES['Snapshot']['tmp_name'][0])) // Thus, is_uploaded_file() is called!!
		{
		if (!move_uploaded_file($_FILES['Snapshot']['tmp_name'][0], $upfile)) // Thus, move_uploaded_file() is called!!
			{
			echo 'Problem: Could not move snapshot file to destination directory.';
			exit;
			}
		}
	else
		{
		echo 'Problem: Possible file upload attack. Filename: ';
		echo $_FILES['Snapshot']['name'][0];
		exit;
		}

	// Part II: Having now moved the snapshot file (if the user bothered to provide a snapshot) to the /snapshots directory, we must resize it if $MediaClass == 'image'
	if ($MediaClass == 'image')
		{
		$imgPath = '/home/paulme6/public_html/abridg/snapshots'; // no forward slash.
		$img = $Snapshot; // The name of the file from which a thumbnail will be created.

		// In order to determine the width and height for the scaled thumbnail, we first use the php function getimagesize() to deduce whether the image is landscape or portrait. If landscape, fix width = 120 and scale height proportionately. If portrait, fix height = 120 and scale width proportionately.
		list($width, $height, $type, $attr) = getimagesize('/home/paulme6/public_html/abridg/snapshots/'.$Snapshot);
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
	
		$Snapshot = createThumbnail($img, $imgPath, "_thumb", $newWidth, $newHeight, 100);
		// Finally, it's appropriate to delete (i.e. unlink in technical PHP parlance) the originally uploaded snapshot file that was moved to the /snapshots directory in Part I above, before it got scaled and renamed.
		@unlink('/home/paulme6/public_html/abridg/snapshots/'.$originalFileName); // (The @ symbol suppresses warning messages if the file that we're trying to delete (i.e. unlink) doesn't exist on the server.)
		}
	}

	
// Manipulate CaptureDate from its native MM/DD/YYYY format into a YYYY-MM-DD format for insertion into a MySQL table.
$datearray = explode('/', $CaptureDate);
$CaptureDate = $datearray[2].'-'.$datearray[0].'-'.$datearray[1];

// Manipulate (set) the value of AuthorizedAssociateIDs string from the $Associates() array
if(empty($Associates))
	{
    $PerformAssignments = false; // The user didn't elect to perform any assignments as part of the upload.
	$AuthorizedAssociateIDs = '';
	}
else
	{
    $PerformAssignments = true; // The user checked at least one of the account holders' check-boxes in upload.php to perform an assignment of the media file to account holder(s).
	$AuthorizedAssociateIDs = implode(',', $Associates);
	}

/* If the Administrator uploaded an image file as the media file but didn't bother to upload a Snapshot (highly likely b/c I've set up upload.php to not show the Snapshot file field when the user selects an image Category), create a snapshot from the media file. It's important to always have a Snapshot for every image in order to quickly display thumbnails of the potentially hundred of image files that might be uploaded and displayed -- either for presentation to end-users or in Admin views such as during assignment operations via assign.php. */
if (empty($Snapshot) && $MediaClass == 'image')
	{
	$imgPath = '/home/paulme6/public_html/abridg/media'; // no forward slash.
	$img = $Filename; // The name of the file from which a thumbnail will be created.

	// In order to determine the width and height for the thumbnail, we first use the php function getimagesize() to deduce whether the image is landscape or portrait. If landscape, fix width = 120 and scale height proportionately. If portrait, fix height = 120 and scale width proportionately.
	list($width, $height, $type, $attr) = getimagesize('/home/paulme6/public_html/abridg/media/'.$Filename);
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
	
	$Snapshot = createThumbnail($img, $imgPath, "_thumb", $newWidth, $newHeight, 100);
	}

/* For each media file that has $MediaClass == 'video', we need to ensure that there's an image file identified in the VideoSnapshot column of the media_table. That image gets used on the index.php page as a graphical hyperlink which, when clicked, opens up the associated media video file within JW Player.
	In cases where the user didn't bother to provide a Snapshot image to accompany his/her video media file, we will simply store the file name "jwplayerframe.png" in the VideoSnapshot column. (The actual image is stored on the server in the /snapshots directory. It's just a thumbnail of the JW Player frame.)
	In cases where the user did bother to provide a Snapshot image to accompany his/her video media file, we can make use of that Snapshot. We'll actually invoke some GD functions to dynamically build the file whose name will be stored in the VideoSnapshot column (and used as a graphical hyperlink as mentioned above). Specifically, we'll overlay two images onto the user-supplied Snapshot image: (i) firstly, jwplayerbar.png (the control bar of JW Player) along the bottom edge of the Snapshot image, and (ii) secondarily, jwplayerbutton.png (the play triangle button) in the center of the image. This composite image will be stored in the /snapshots directory. It will have the suffix "_comp" to distinguish its name from the name of the user-supplied Snapshot. */
$VideoSnapshot = ''; // By default, assign $VideoSnapshot to a blank string. It will only get a non-blank value if $MediaClass == 'video'.
if ($MediaClass == 'video')
	{
	if (empty($Snapshot)) $VideoSnapshot = 'jwplayerframe.png'; // User-didn't bother to upload a Snapshot to accompany his/her video media file.
	else
		{
		// Part 1, superimposing the jwplayerbar (120px x 10px) onto the lower edge of the user-supplied snapshot image. Specifically, load the source image (jwplayerbar) and the user's snapshot (i.e. destination image) to which the "watermark" (i.e. the jwplayerbar.png image) is applied.
		$sourceImg = imagecreatefrompng('/home/paulme6/public_html/abridg/images/jwplayerbar.png');

		$ExtOfSnapshot = pathinfo($Snapshot, PATHINFO_EXTENSION);
		switch (strtolower($ExtOfSnapshot)) // Note: use of strtolower() alleviates need to case, say, "JPG" in addition to "jpg", etc.
			{
			case 'jpg': $destImg = imagecreatefromjpeg('/home/paulme6/public_html/abridg/snapshots/'.$Snapshot) or die("Error opening jpg original"); break;
			case 'jpeg': $destImg = imagecreatefromjpeg('/home/paulme6/public_html/abridg/snapshots/'.$Snapshot) or die("Error opening jpeg original"); break;
			case 'gif': $destImg = imagecreatefromgif('/home/paulme6/public_html/abridg/snapshots/'.$Snapshot) or die("Error opening gif original"); break;
			case 'png': $destImg = imagecreatefrompng('/home/paulme6/public_html/abridg/snapshots/'.$Snapshot) or die("Error opening png original"); break;
			default: echo '<p class="text">Sorry. Your snapshot appears to be an image file that is not supported. Please provide images as .jpg, .jpeg, .gif, or .png file types only. Use the Back button in your browser and try again.</p>'; exit;
			}
		
		// Resize the destination image to 120px x 90px via GD's imagecopyresized() function (Note: unless the user uploaded a Snapshot exactly in the 4:3 ratio (e.g. 120px x 90px), some stretching or compressing will be necessary (performed automatically).
		$destImgResized = imagecreatetruecolor(120, 90) or die("Cant create a resized image");
		imagecopyresized($destImgResized, $destImg, 0, 0, 0, 0, 120, 90, imagesx($destImg), imagesy($destImg));

		// Get the height/width of the jwplayerbar source image. (This should always be 120px x 10 px landscape as in "JW Player Snapshot Image.mic".)
		$sourceWidth = imagesx($sourceImg);
		$sourceHeight = imagesy($sourceImg);

		// Copy the jwplayerbar source image onto our snapshot image using GD function imagecopy().  (For the best tutorial on the not-so-intuitive imagecopy() params, see: http://www.lateralcode.com/manipulating-images-using-the-php-gd-library/)
		imagecopy($destImgResized, $sourceImg, 0, 80, 0, 0, $sourceWidth, $sourceHeight);

		$snapshotfilenameonly = pathinfo($Snapshot, PATHINFO_FILENAME); // E.g. if the Snapshot was called "mysnapshot.gif", then $snapshotfilenameonly would be "mysnapshot".

		// Save the (now composite) snapshot + jwplayerbar image to the /snapshots directory as a jpg image. If the user-uploaded snapshot file was called, say, "mysnap.png", the new composite image will be called "comp_mysnap.jpg".
		imagejpeg($destImgResized, '/home/paulme6/public_html/abridg/snapshots/'.$snapshotfilenameonly.'_comp.jpg', 95) or die("Unable to save Snapshot + jwplayerbar composite image.");

		// Now part 2, the superimposition of the jwplayerbutton.png image onto the center of the (Snapshot + jwplayerbar) composite image. Redefine source and destination images:
		$sourceImg = imagecreatefrompng('/home/paulme6/public_html/abridg/images/jwplayerbutton.png');

		// Get the height/width of the jwplayerbutton source image. (This should be 15px x 15px.)
		$sourceWidth = imagesx($sourceImg);
		$sourceHeight = imagesy($sourceImg);

		// Copy the jwplayerbutton source image (to go approx in the center of the destination image) onto our composite (jwplayer bar + snapshot) image using GD function imagecopy().  (For the best tutorial on the not-so-intuitive imagecopy() params, see: http://www.lateralcode.com/manipulating-images-using-the-php-gd-library/)
		imagecopy($destImgResized, $sourceImg, 54, 37, 0, 0, $sourceWidth, $sourceHeight);

		// Save the new composite (snapshot + jwplayerbar + jwplayerbutton) image to the /snapshots directory using the same name as it had before.
		imagejpeg($destImgResized, '/home/paulme6/public_html/abridg/snapshots/'.$snapshotfilenameonly.'_comp.jpg', 95) or die("Unable to save Snapshot + jwplayerbar + jwplayerbutton composite image.");

		// Free memory
		imagedestroy($destImgResized);
		
		// Assign the new composite image's name to $VideoSnapshot in readiness for insertion into the media_table.
		$VideoSnapshot = $snapshotfilenameonly.'_comp.jpg';
		}
	}

/* If the user has uploaded a document (identified as having a MediaClass that is neither 'video' nor 'image', then give that media item (i.e. document) the most appropriate generic snapshot according to its file extension. */
if (empty($Snapshot) && $MediaClass == 'application')
	{
	switch (strtolower($ExtOfFilename))
		{
			case 'tiff' :
				$Snapshot = 'tiff-document.png';
				break;
			case 'tif' :
				$Snapshot = 'tiff-document.png';
				break;
			case 'svg' :
				$Snapshot = 'svg-document.png';
				break;
			case 'doc' :
				$Snapshot = 'word-document.png';
				break;
			case 'docx' :
				$Snapshot = 'word-document.png';
				break;
			case 'xls' :
				$Snapshot = 'excel-document.png';
				break;
			case 'xlsx' :
				$Snapshot = 'excel-document.png';
				break;
			case 'ppt' :
				$Snapshot = 'ppt-document.png';
				break;
			case 'pptx' :
				$Snapshot = 'ppt-document.png';
				break;
			case 'pdf' :
				$Snapshot = 'pdf-document.png';
				break;
			case 'psd' :
				$Snapshot = 'photoshop-document.png';
				break;
			case 'ai' :
				$Snapshot = 'ai-document.png';
				break;
			case 'dxf' :
				$Snapshot = 'dxf-document.png';
				break;
			case 'ps' :
				$Snapshot = 'postscript-document.png';
				break;
			case 'eps' :
				$Snapshot = 'eps-document.png';
				break;
			case 'xps' :
				$Snapshot = 'xps-document.png';
				break;
			case 'ttf' :
				$Snapshot = 'ttf-document.png';
				break;
			default :
				$Snapshot = 'generic-document.png';
				break;
		}
	}

/* If the user has uploaded an audio item (identified as having a MediaClass that is neither 'video' nor 'image', then give that media item (i.e. document) the most appropriate generic snapshot according to its file extension. */
if (empty($Snapshot) && $MediaClass == 'audio') $Snapshot = 'generic-audio.png';

/* Prevent cross-site scripting via htmlspecialchars on these user-entry form field */
$Filename = htmlspecialchars($Filename, ENT_COMPAT);
$Snapshot = htmlspecialchars($Snapshot, ENT_COMPAT);
$Title = htmlspecialchars($Title, ENT_QUOTES);
$FileDescription = htmlspecialchars($FileDescription, ENT_QUOTES);
$CaptureDate = htmlspecialchars($CaptureDate, ENT_COMPAT);

if (!get_magic_quotes_gpc())
	{
	$Filename = addslashes($Filename);
	$Snapshot = addslashes($Snapshot);
	$FileDescription = addslashes($FileDescription);
	$CaptureDate = addslashes($CaptureDate);
	}		

// Insert data into media_table.
$query = "INSERT INTO media_table set OwnerID = ".$_SESSION['LoggedInOwnerID'].", Filename = '".$Filename."', Snapshot = '".$Snapshot."', VideoSnapshot = '".$VideoSnapshot."', AuthorizedAssociateIDs = '".$AuthorizedAssociateIDs."', Title = '".$Title."', FileDescription = '".$FileDescription."', FileType = '".$FileType."', FileCategory = '".$FileCategory."', MediaClass = '".$MediaClass."', CaptureDate = '".$CaptureDate."', UploadDate = NOW()";

$result = mysql_query($query) or die('Query (insert into media_table) failed: ' . mysql_error().' and the database query string was: '.$query);
$theMediaFileID = mysql_insert_id(); // This variable gets used in a couple of places below in upload_slave.php.

/* Note that we haven't yet inserted a value into the QueryString (for use in providing a unique "Sharelink" URL where the uploaded media file can be viewed) column of media_table. That's intentional. We are actually going to enter a value into that column by means of a MySQL update rather than an insertion. We use the former because the method of generating the QueryString (below) actually uses the auto-generated autoincrement FileID as a suffix, and we won't know what that FileID value is until the row has been inserted. */
// Step 1: Rather than use mt_rand() to create a large integer (courtesy: www.php.net/manual/en/function.rand.php), which could theoretically be the same as one previously created (i.e. non-unique), I use the better (i.e. always unique) method of generating the large integer via PHP's uniqid() function with the (optional) prefix of "q" (short for "query string". Notes: (1) With more_entropy flag set to false, the $QueryString is about 10 characters (easier to, say, type in manually); when set to true, it's about 18 characters. However, the 'true' flag adds entropy whereas when set to false the uniqid() is just returning the unix timestamp in microseconds. (2) A related issue vis a vis uniqueness would be to prepend a prefix based on the Account Owner's (cf. account holder) OwnerID or password (perhaps scrambled) or the media file's FileID when I scale to support multiple Account Owners. That should avoid the possibility of losing uniqueness by two different Account Holders each uploading media files at the exact same microsecond.
$largeInteger = uniqid("", false); 
$largeInteger = base_convert($largeInteger, 16, 36); // Convert the large integer from base 16 to base 36 in order to reduce the number of characters without sacrificing uniqueness.
// Step 2: Secondarily, to address the potential issue of two Account Owners who upload media files at the exact same microsecond and get assigned the same value for the in the media item's QueryString column, we're going to prepend the last three digits of the FileID, converted into base 36 to reduce the number of characters.
$TheFileID = mysql_insert_id();
$QueryString = base_convert($TheFileID, 10, 36).$largeInteger;
$QueryString = 'q'.$QueryString; // Prepend 'q' (for "query string") - aids perception of trust/authenticity.

$query = "UPDATE media_table set QueryString = '".$QueryString."' WHERE FileID = ".$TheFileID;
$result = mysql_query($query) or die('Query (update of QueryString in media_table) failed: ' . mysql_error().' and the database query string was: '.$query);

/*
Although we've already inserted Filename, Snapshot, and VideoSnapshot into media_table and have moved the associated files to the /media and /snapshots directories, we must now rename those actual files to circumvent a situation whereby Owner Jill has uploaded, say, an image of filename = "mycutepuppy.jpg" and Owner Tom has also uploaded a totally different file of the same name. We solve this problem by appending the FileID (a unique table key, stored above as $TheFileID) to the filename. So, for example, Jill's file in /media/mycutepuppy.jpg becomes renamed to /media/mycutepuppy_759.jpg (where "759" is the FileID for that particular media item) and Tom's /media/mycutepuppy.jpg becomes renamed to /media/mycutepuppy_372.jpg (where "372" is the FileID for that particular media item).
*/
// Construct a new name for the actual Filename media file ...
$extensionBegins = strrpos($Filename, '.'); // This gives the position of the final period, which precedes the file extension.
$filenameStub = substr($Filename, 0, $extensionBegins); // Example: if $Filename is "piglet.png" then $filenameStub will be "piglet".
$filenameStubID = $filenameStub."_".$TheFileID; // Append the FileID e.g. "piglet_65
$TheNewFilenameFileName = $filenameStubID.substr($Filename, $extensionBegins); // Append filename's extension e.g "piglet_65.png"
rename('/home/paulme6/public_html/abridg/media/'.$Filename, '/home/paulme6/public_html/abridg/media/'.$TheNewFilenameFileName);

// Construct a new name for the actual Snapshot file if one exists (unless the MediaClass is 'application' or 'audio', in which case generic snapshot file names will have been given to such media items)...
if (!empty($Snapshot) && $MediaClass != 'application' && $MediaClass != 'audio')
	{
	$extensionBegins = strrpos($Snapshot, '.'); // This gives the position of the final period, which precedes the file extension.
	$snapshotStub = substr($Snapshot, 0, $extensionBegins); // Example: if $Snapshot is "piglet.png" then $snapshotStub will be "piglet".
	$snapshotStubID = $snapshotStub."_".$TheFileID; // Append the FileID e.g. "piglet_65
	$TheNewSnapshotFileName = $snapshotStubID.substr($Snapshot, $extensionBegins); // Append snapshot's extension e.g "piglet_65.png"
	rename('/home/paulme6/public_html/abridg/snapshots/'.$Snapshot, '/home/paulme6/public_html/abridg/snapshots/'.$TheNewSnapshotFileName);
	}

// Construct a new name for the actual VideoSnapshot file if one exists...
if (!empty($VideoSnapshot) && $VideoSnapshot != 'jwplayerframe.png')
	{
	$extensionBegins = strrpos($VideoSnapshot, '.'); // This gives the position of the final period, which precedes the file extension.
	$videoSnapshotStub = substr($VideoSnapshot, 0, $extensionBegins); // Example: if $Snapshot is "piglet.png" then $videoSnapshotStub will be "piglet".
	$videoSnapshotStubID = $videoSnapshotStub."_".$TheFileID; // Append the FileID e.g. "piglet_65
	$TheNewVideoSnapshotFileName = $videoSnapshotStubID.substr($VideoSnapshot, $extensionBegins); // Append VideoSnapshot's extension e.g "piglet_65.png"
	rename('/home/paulme6/public_html/abridg/snapshots/'.$VideoSnapshot, '/home/paulme6/public_html/abridg/snapshots/'.$TheNewVideoSnapshotFileName);
	}

/*
Next (iff $PerformAssignments is true i.e. the Administrator bothered to assign the uploaded media file to any associates) update the AuthorizedFileIDs column of the associates_table for each of the AssociateID values (stored in the $Associates array) corresponding to mysql_insert_id(), which is a PHP function that returns the value of AUTO_INCREMENTed FileID column when the previous query was successfully executed. Also insert a row into assign_table for each associate to whom the file was assigned.
	And send alerts if the logged in Owner selected "Send alerts automatically upon assignment" in one of the manage.php HTML forms (that's initially processed by managealerts_slave.php).
*/
if ($PerformAssignments)
	{
	foreach($Associates as $key => $value)
		{ 
		// Obtain the existing value of the AuthorizedFileIDs column for each AssociateID
		$query = "SELECT AuthorizedFileIDs FROM associates_table WHERE AssociateID = ".$Associates[$key];
		$result = mysql_query($query) or die('Query (select AuthorizedFileIDs from associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
		$row = mysql_fetch_assoc($result);
		$existingAuthorizedFileIDsString = $row['AuthorizedFileIDs'];
		if (empty($existingAuthorizedFileIDsString))
			{
			$updatedAuthorizedFileIDsString = $existingAuthorizedFileIDsString.$theMediaFileID;
			}
		else
			{
			$updatedAuthorizedFileIDsString = $existingAuthorizedFileIDsString.','.$theMediaFileID;
			};
		
		// Update associates_table with the updatedAuthorizedFileIDsString
		$query = "UPDATE associates_table SET AuthorizedFileIDs = '".$updatedAuthorizedFileIDsString."' WHERE AssociateID = ".$Associates[$key];
		$result = mysql_query($query) or die('Query (select UPDATE associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
		
		// Insert a row into assign_table for the AssociateID and FileID pair
		$query = "INSERT INTO assign_table SET AssociateID = ".$Associates[$key].", FileID = ".$theMediaFileID.", AssignDate = NOW()";
		$result = mysql_query($query) or die('Query (insert AssociateID, FileID, AssignDate into assign_table) failed: ' . mysql_error().' and the database query string was: '.$query);
		}
	
	/* Send any alerts upon assignment */
	// First check whether logged in Owner's AlertType == 'auto_onassign' in owners_table
	$query = "SELECT AlertType from owners_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
	$result = mysql_query($query) or die('Query (select AlertType from owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	$row = mysql_fetch_assoc($result);
	if ($row['AlertType'] == 'auto_onassign')
		{
		alertgenerator(NULL); // Call this function, defined in include'd file alertgenerator.php, with input parameter set to NULL to indicate that alert generation should not be restricted to a subset of associate IDs.
		}

	}
		
/* Create a web page whose URL contains the unique $QueryString so that an account holder can share that URL with a friend, thereby allowing that friend to view a picture media file without needing any disclosure of the account holder's password. */
// First determine the actual file names. This expression looks complex, but it's just a means to insert "_XXX" (where XXX is the media file's $theMediaFileID, obtained above) into "myfile.jpg", making it "myfile_XXX.jpg".
$theFilenameFile = substr($Filename, 0, strrpos($Filename, '.')).'_'.$theMediaFileID.substr($Filename, strrpos($Filename, '.'));
$theSnapshotFile = substr($Snapshot, 0, strrpos($Snapshot, '.')).'_'.$theMediaFileID.substr($Snapshot, strrpos($Snapshot, '.'));
$uniquePageContent = '';
switch ($MediaClass)
	{
	// If the $MediaClass is an 'application', then the Sharelink page should simply consist of a redirect (implemented as a PHP header redirect) to the Google Docs Viewer, passing the Viewer the appropriate URL (i.e. $theurl) to the document file. Nothing more. (Note: I experimented with placing the Google Docs Viewer page inside an iframe page on the abridg server. However, as verified in stackoverflow, IE doesn't have good support for iframes, and the Google Docs Viewer failed to open several files when placed in an iframe. So I abandoned that idea.)
	case 'application' :
		// When $MediaClass is 'application', we need to create a url that will be passed to either the Zoho Viewer (preferred choice for Word, Excel, and PowerPoint documents because no Zoho account required) or the Google Docs Viewer (necessary alternative for PDF, Photoshop, Illustrator, AutoCAD, PS, EPS, XPS, and TTF b/c Zoho Viewer doesn't support these file types; note that user will need a Google account to use Google Docs) API as a query string (ref. https://apihelp.wiki.zoho.com/Zoho-Viewer-APIs.html and https://docs.google.com/viewer).
		$theurl = 'http://www.abridg.com/media/'.$theFilenameFile; // Note that urlencode()'ing the URL actually produced much poorer recognition by the Google Docs Viewer, so don't encode the URL.
		switch ($FileType)
			{
			case 'application/msword' :
				$uniquePageContent .= "<?php header('Location: ".'https://viewer.zoho.com/api/urlview.do?url='.$theurl.'&embed=false'."'); ?>";
				break;
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' :
				$uniquePageContent .= "<?php header('Location: ".'https://viewer.zoho.com/api/urlview.do?url='.$theurl.'&embed=false'."'); ?>";
				break;
			case 'application/msexcel' :
				$uniquePageContent .= "<?php header('Location: ".'https://viewer.zoho.com/api/urlview.do?url='.$theurl.'&embed=false'."'); ?>";
				break;
			case 'application/x-excel' :
				$uniquePageContent .= "<?php header('Location: ".'https://viewer.zoho.com/api/urlview.do?url='.$theurl.'&embed=false'."'); ?>";
				break;
			case 'application/x-msexcel' :
				$uniquePageContent .= "<?php header('Location: ".'https://viewer.zoho.com/api/urlview.do?url='.$theurl.'&embed=false'."'); ?>";
				break;
			case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' :
				$uniquePageContent .= "<?php header('Location: ".'https://viewer.zoho.com/api/urlview.do?url='.$theurl.'&embed=false'."'); ?>";
				break;
			case 'application/excel' :
				$uniquePageContent .= "<?php header('Location: ".'https://viewer.zoho.com/api/urlview.do?url='.$theurl.'&embed=false'."'); ?>";
				break;
			case 'application/vnd.ms-excel' :
				$uniquePageContent .= "<?php header('Location: ".'https://viewer.zoho.com/api/urlview.do?url='.$theurl.'&embed=false'."'); ?>";
				break;
			case 'application/vnd.ms-powerpoint' :
				$uniquePageContent .= "<?php header('Location: ".'https://viewer.zoho.com/api/urlview.do?url='.$theurl.'&embed=false'."'); ?>";
				break;
			case 'application/vnd.openxmlformats-officedocument.presentationml.presentation' :
				$uniquePageContent .= "<?php header('Location: ".'https://viewer.zoho.com/api/urlview.do?url='.$theurl.'&embed=false'."'); ?>";
				break;
			case 'application/mspowerpoint' :
				$uniquePageContent .= "<?php header('Location: ".'https://viewer.zoho.com/api/urlview.do?url='.$theurl.'&embed=false'."'); ?>";
				break;
			case 'application/powerpoint' :
				$uniquePageContent .= "<?php header('Location: ".'https://viewer.zoho.com/api/urlview.do?url='.$theurl.'&embed=false'."'); ?>";
				break;
			case 'application/x-mspowerpoint' :
				$uniquePageContent .= "<?php header('Location: ".'https://viewer.zoho.com/api/urlview.do?url='.$theurl.'&embed=false'."'); ?>";
				break;
			default :
				$uniquePageContent .= "<?php header('Location: ".'http://docs.google.com/viewer?url='.$theurl.'&embedded=true'."'); ?>";
			}
		break;
	default :
		$uniquePageContent .= "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>";
		$uniquePageContent .= "<html>";
		$uniquePageContent .= "<head>";
		$uniquePageContent .= "<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'><meta http-equiv='CONTENT-LANGUAGE' CONTENT='en-US'>";
		$uniquePageContent .= "<title>".$Title."</title>";
		$uniquePageContent .= "<meta NAME='description' CONTENT='Unique page for shareable media file'>";
		$uniquePageContent .= "<link href='/abridg.css' rel='stylesheet' type='text/css'>";
		switch ($MediaClass)
			{
			case 'video' :
				$uniquePageContent .= "<script type='text/javascript' src='/jwplayer/jwplayer.js'></script>";
				break;
			case 'audio' :
				$uniquePageContent .= "<script type='text/javascript' src='/jwplayer/jwplayer.js'></script>";
				break;
			default :
				break; // take no action
			}
		$uniquePageContent .= "</head>";
		$uniquePageContent .= "<body>";
		$uniquePageContent .= "<div id='main'>"; // A pretty good attempt at horz centering in light of 5% left margin in main courtesy http://demo.tutorialzine.com/2010/03/centering-div-vertically-and-horizontally/demo.html.
		$uniquePageContent .= "<div id='relwrapper'>";
		$uniquePageContent .= "<div style='text-align: left;'>";
		if ($MediaClass == 'image' || $MediaClass == 'video') $thepadding = '100px'; else if ($MediaClass == 'audio') $thepadding = '160px'; else $thepadding = '0px';
		$uniquePageContent .= "<table align='center'><tr><td style='padding-top: ".$thepadding.";'>";
		// This expression looks complex, but it's just a means to insert "_XXX" (where XXX is the media file's $theMediaFileID, obtained above) into "myfile.jpg", making it "myfile_XXX.jpg".
		$theFilenameFile = substr($Filename, 0, strrpos($Filename, '.')).'_'.$theMediaFileID.substr($Filename, strrpos($Filename, '.'));
		$theSnapshotFile = substr($Snapshot, 0, strrpos($Snapshot, '.')).'_'.$theMediaFileID.substr($Snapshot, strrpos($Snapshot, '.'));
		switch ($MediaClass)
			{
			case 'image' :
				list($width, $height, $type, $attr) = getimagesize('/home/paulme6/public_html/abridg/media/'.$theFilenameFile);
				$uniquePageContent .= "<img alt='Image loading - please wait' src='/media/".$theFilenameFile."' width='".$width."' height='".$height."'>";
				break;
			case 'video' :
				$uniquePageContent .= "<div id='container'>Loading the player ...</div>";
				$uniquePageContent .= "<script type='text/javascript'>";
				$uniquePageContent .= "jwplayer('container').setup({ flashplayer: '/jwplayer/player.swf', file: '/media/".$theFilenameFile."', height: 377, width: 668, skin: '/jwplayer/skins/newtubedark.zip', image: '/snapshots/".$theSnapshotFile."', stretching: 'fill' }); // Actual video is 377 x 668 (16:9 widescreen); allow extra pixel on either edge";
				$uniquePageContent .= "</script>";
				break;
			case 'audio' :
				$theSnapshotFile = 'generic-audio.png'; // The snapshot file will actually be the generic file 'generic-audio.png', now stored in the Snapshot column of media_table.
				$uniquePageContent .= "<div id='container'>Loading the audio player ...</div>";
				$uniquePageContent .= "<script type='text/javascript'>";
				$uniquePageContent .= "jwplayer('container').setup({ flashplayer: '/jwplayer/player.swf', file: '/media/".$theFilenameFile."', height: 256, width: 256, skin: '/jwplayer/skins/simple.zip', image: '/snapshots/".$theSnapshotFile."', stretching: 'fill' });";
				$uniquePageContent .= "</script>";
				break;
			}
		$uniquePageContent .= "</td></tr></table>";
		$uniquePageContent .= "</div>";
		$uniquePageContent .= "</div>";
		$uniquePageContent .= "</div>";
		$uniquePageContent .= "</body>";
		$uniquePageContent .= "</html>";
	}
	
// Opening file
$fp = fopen('../'.$QueryString.".php","w");

// Attempt to apply an exclusive lock
$lk = flock($fp, LOCK_EX);
if (!$lk) echo "Error locking the unique page content page file!";

// Write to file
fwrite($fp, $uniquePageContent, strlen($uniquePageContent));

// Unlock the file (this would get done by the o/s automatically on exit of the script, but this is a good safe thing to do in case the script served by Apache doesn't end.
flock($fp, LOCK_UN);

// Closing file
fclose($fp);

		
// Reset the session variables for use in prepopulating values in upload.php after a PHP validation error, and present the user with a success notification with buttons to navigate onto a next page.
$_SESSION['Title'] = '';
$_SESSION['FileCategory'] = '';
$_SESSION['FileDescription'] = '';


/* Finally, we need to set a value for $_SESSION['AssociateID'] (which used on return to index.php Media Gallery to dictate whether the logged in Owner sees his/her own "My Gallery Favorites" or view his/her own content as if he/she were one of his/her content consumers (i.e. friends). We'll set it to $_SESSION['LoggedInOwnersOwnAssociate'] (i.e. the Owner's own "My Gallery Favorites" AssociateID [which gets set when the Owner first logs in in index.php] as a good default choice after an Edit Media opeation. */
$_SESSION['AssociateID'] = $_SESSION['LoggedInOwnersOwnAssociate'];
?>
<div style="text-align: center"> <!-- This div provides centering for older browsers incl. NS4 and IE5. (See http://theodorakis.net/tablecentertest.html#intro.) Use of margin-left: auto and margin-right: auto in the style of the table itself (see below) takes care of centering in newer browsers. -->
<!-- Depending on whether or not the Administrator elected to assign access rights to the uploaded media file concomitant with the upload, he/she is invited to either do so next, or to visit the home page next. -->
<form method="post" action="<?php if ($PerformAssignments) echo '/index.php'; else echo '/assign.php'; ?>">
<table cellpadding="0" cellspacing="0" style="margin-top: 50px; margin-left: auto; margin-right: auto; position: relative; left: -7px;">
<tr>
<td style="text-align: left;">
<p class='text' style='margin-left: 150px; margin-right: 150px; margin-top: 40px; margin-bottom: 20px;'>Congratulations! You have successfully uploaded the following file to the Abridg web site: <em><?=$Filename; ?>.</em></p>
<p class='text' style='margin-left: 150px; margin-right: 150px; margin-bottom: 60px;'><?php if ($PerformAssignments) echo 'Click the button below to visit the Media Gallery.'; else echo 'Click the button below to assign access to this file.'?> Alternatively, click <a target='_self' href='/upload.php'>here</a> to upload additional items.</p>
</td>
<tr>
<td style="text-align: center;">
<input type='submit' name='next' class='buttonstyle' style="text-align: center;" <?php if ($PerformAssignments) echo 'value="Media Gallery" name="galleryview"'; else echo 'value="Assign Access"'; ?>'>
</td>
</tr>
</table>
</form>
</div>
<?php
ob_end_flush();
?>
</body>
</html>