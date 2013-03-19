<?php
/*
progresswindow.php provides the upload progress bar for display within an inline frame inside upload.php. Note that the method for generating the upload progress bar exploits the PECL Uploadprogress extension (which has to be installed on the server). That extension provides a way to obtain the percentage of the upload that has already been uploaded and is pretty well explained here http://media.nata2.org/2007/04/16/pecl-uploadprogress-example/ and here http://php.net/manual/en/features.file-upload.php (scroll to jazfresh comment). We also need a method for then displaying a dynamic progress bar, and for that I use Jonathan Christensen's method at  http://riotriot.net/2010/02/simple-php-progress-bar/?cmd=continue.
	In a nutshell, (i) include a hidden form element of name = "UPLOAD_IDENTIFIER" and value = some random identifier ($id in my case) in upload.php; (ii) either include an onsubmit event handler in the form that opens a popup window (e.g. onSubmit="wintasticsecond('/progresswindow.php?ID=<?php echo $id;?>')" to display the progress bar, or (as I've done instead here) simply open the progresswindow.php inside an iframe, passing the random identifier as a query string (e.g. progresswindow.php?ID=<?php echo $id;?>); (iii) within progresswindow.php (which uses Javascript to refresh every few seconds), define and call a PHP function (progressBar($percentage), courtesy http://riotriot.net/2010/02/simple-php-progress-bar/?cmd=continue) that takes as its input parameter a percentage; (iv) calculate that $percentage value in progresswindow.php by using the PECL extension-provided function uploadprogress_get_info($id) with the random identifier set in the upload.php form -- note that the extension returns an associative array with useful data such as 'bytes_uploaded' and 'bytes_total', which can be readily divided by one another to get a dynamic % upload so far; (v) don't forget to add the necessary styles for the progress bar to the style sheet if they aren't defined locally.
	PHP progress bar method courtesy: http://riotriot.net/2010/02/simple-php-progress-bar/?cmd=continue 
*/

// Start a session
session_start();

function progressBar($percentage)
	{
	print "<div id=\"progress-bar\" class=\"all-rounded\">\n";
	print "<div id=\"progress-bar-percentage\" class=\"all-rounded\" style=\"width: $percentage%\">";
	if ($percentage > 0)
		{
		print '<span style="margin-left: 140px">'.number_format($percentage, 1).'%</span>';
		}
	else
		{
		print "<div class=\"spacer\">&nbsp;</div>";
		}
	print '</div></div>';
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Abridg | Upload Progress Monitor</title>
<link href="/abridg.css" rel="stylesheet" type="text/css">
<style>
/* These styles relate to Jonathan Christensen's Simple PHP Progress Bar at http://riotriot.net/2010/02/simple-php-progress-bar/?cmd=continue */
.all-rounded {
    -webkit-border-radius: 5px;
    -moz-border-radius: 5px;
    border-radius: 5px;
}
.spacer {
	display: block;
}
#progress-bar {
	width: 300px;
	margin: 0 auto;
	background: #cccccc; /* Light grey */
	border: 3px solid #f2f2f2;
}
#progress-bar-percentage {
	background: #9F0251; /* was formerly Blue #3063A5; now violet-reddish #9F0251 */
	padding: 5px 0px;
 	color: #FFF;
 	font-weight: bold;
 	text-align: center;
}
</style>
<script type="text/javascript">
<!-- Begin
/* This script and many more are available free online at The JavaScript Source!! http://javascript.internet.com Created by: Lee Underwood */
function reFresh() 
	{
	location.reload(true)
	}

// Set the number below to the amount of delay, in milliseconds, you want between page reloads: 1 minute = 60000 milliseconds.
window.setInterval("reFresh()",1000);
// End -->
</script>
</head>

<body style="margin-top: 10px;">
<?php
$info = uploadprogress_get_info($_GET['ID']);
//echo '<br />Bytes uploaded is: '.$info['bytes_uploaded'];
//echo '<br />Total bytes is: '.$info['bytes_total'];
//echo '<br />Estimated time remaining (secs) is: '.$info['est_sec'];
@$progress = $info['bytes_uploaded']/$info['bytes_total'] * 100; // The @ symbol suppresses display of a division-by-zero warning when the file uploading first begins.
$theHoursRemaining = floor($info['est_sec']/(60*60));
$theMinsRemaining = floor(($info['est_sec'] - $theHoursRemaining * 60 * 60)/60);
$theSecsRemaining = $info['est_sec'] - $theHoursRemaining * 60 * 60 - $theMinsRemaining * 60;
//echo '<br />The hours remaining is: '.$theHoursRemaining.' The mins remaining is: '.$theMinsRemaining.' and the secs remaining is: '.$theSecsRemaining;
//echo '<br />Progress % is: '.number_format($progress, 1);
progressBar($progress);
echo '<div class="gloss" style="text-align: center; padding-top: 3px;">Estimated Time Remaining: '.sprintf("%02s:%02s:%02s", $theHoursRemaining, $theMinsRemaining, $theSecsRemaining).'</div>'; // Use sprintf to effect leading zeros in hrs, mins, and sec when single digit.
?>
<div style="text-align: center; padding-top: 12px;"><input type=button class="buttonstyle" onClick="javascript:self.close();" onKeyPress="javascript:self.close();" value="Close"></div>
</body>
</html>
