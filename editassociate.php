<?php
/*
editassociate.php isn't intended for direct access but rather for access via assign_slave.php (such that the user clicked an edit icon next to an associate (friend) in assign.php). It is the front-end whereby the Administrator (i.e. Account Owner) can edit details (specifically, the AssociateName) of one of his/her associates identified by $_SESSION['EditAssociate'] (which is set inside assign_slave.php). Note that there's no good reason for the Administrator to edit the associates_table.OwnerUsername. If/when the Account Owner of that email address (as stored in owners_table) changes his/her email address, the change should be automatically made to the record of OwnerUsername in the associates_table also. Once an Owner has added an associate by declaring an AssociateName and OwnerUsername (email), he/she should have no good reason to ever bother with knowing or changing that email address ever again.
*/

// Start a session
session_start();

if ($_SESSION['Authenticated'] != 'true') header("Location: /index.php");

// To prevent a premature display of either the "Assign media items to account holder" screen or the "Assign account holders to a media item" screen, unset $_SESSION['AssociateSelected'] and $_SESSION['FileSelected'] session variables, which get set in assign_slave.php. Also, to prevent an unwanted preset of the radio button next to the associates in assign.php (for an "Assign media items to account holder" operation) or the radio button next to the media items in assign.php (for an "Assign account holders to a media item" operation), when those screens are displayed via a user click on the picture icon in assign.php, unset $_SESSION['AssociateID'] and $_SESSION['FileID'] session variables that are set in assign_slave.php.
unset($_SESSION['AssociateSelected']);
unset($_SESSION['FileSelected']);
unset($_SESSION['AssociateID']);
unset($_SESSION['FileID']);

// Connect to DB
$db = mysql_connect('localhost', 'paulme6_merlyn', 'fePhaCj64mkik')
or die('Could not connect: ' . mysql_error());
mysql_select_db('paulme6_abridg') or die('Could not connect to the abridg database: ' . mysql_error());
	
// Retrieve account information for AssociateID identified by $_SESSION['EditAssociate'] for use in prepopulating the HTML form.
$query = "SELECT AssociateName, OwnerUsername FROM associates_table WHERE AssociateID = ".$_SESSION['EditAssociate'];
$result = mysql_query($query) or die('Query (select * from associates_table, owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
$row = mysql_fetch_assoc($result);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Edit Friend</title>
<meta NAME="description" CONTENT="Form to edit name of an existing user account">
<link href="/abridg.css" rel="stylesheet" type="text/css">
<script type='text/javascript' language="JavaScript" src="/scripts/windowpops.js"></script>
<script>
function FocusFirst()
	{
	if (document.forms.length > 0 && document.forms[1].elements.length > 0)
		document.forms[1].elements[0].focus();
	};
</script>
</head>

<body onLoad="FocusFirst();">
<div id="main" style="text-align: center; padding: 10px 0px 0px 0px;">
<div id="relwrapper">

<div style="margin-top: 10px; text-align: center; padding: 0px;">
<form method="post" action="/index.php">
<input type="submit" class="submitLinkSmall"  name="galleryview" value="Media Gallery">
</form>
</div>

<div class="gloss" style="font-weight: bold; font-variant: small-caps; margin-top: 24px; color: #E1B378;">Abridg Director</div>
<h1 style="margin-top: 12px; font-size: 22px; color: #9F0251; font-family: 'Century Gothic', Geneva, Arial, sans-serif;">Edit Friend</h1>
<?php
require('/home/paulme6/public_html/abridg/ssi/adminmenu.php'); // Include the navigation menu.

?>
<div style="width: 400px; margin: 40px auto 0px auto; text-align: left; border: 1px solid #E1B378; padding: 0px 5px 20px 10px;">
<form method="post" name="EditAccount" action="/scripts/editassociate_slave.php">
<table align="center">
<tr style="height: 75px;">
<td style="width: 110px; vertical-align: top; padding-top: 30px;"><label>Friend&rsquo;s Name</label></td>
<td>
<input type="text" name="AssociateName" id="AssociateName" class="textfield" maxlength="40" size="20" value="<?=$row['AssociateName']; ?>" onFocus="this.style.background='#FFFF97'" onBlur="this.style.background='white';">
<?php if ($_SESSION['MsgAssociateName'] != null) { echo $_SESSION['MsgAssociateName']; $_SESSION['MsgAssociateName']=null; } ?>
</td>
</tr>
<tr>
<td colspan="2" style="text-align: center;"><br>
<input type="submit" name="UpdateAssociate" value="Update" class="buttonstyle">
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