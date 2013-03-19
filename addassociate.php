<?php
/*
addassociate.php allows an Owner (administrator cf. superadministrator) of the Abridg web site to add an associate who can view the account Owner's media files (if so assigned). The Owner simply provides an email address of the associate (which will be the associate's username) and an AssociateName (which will appear in the Owner's list of associates). The script is processed by slave addassociate_slave.php, which simply inserts OwnerUsername into a new row of owners_table (so the associate can have his/her own account) and inserts AssociateName, OwnerUsername, and OwnerID in a new row of associates_table for this associate of the Owner.
*/

// Start a session
session_start();

if ($_SESSION['Authenticated'] != 'true') header("Location: /index.php");

// Connect to DB
$db = mysql_connect('localhost', 'paulme6_merlyn', 'fePhaCj64mkik')
or die('Could not connect: ' . mysql_error());
mysql_select_db('paulme6_abridg') or die('Could not connect to the Abridg database: ' . mysql_error());
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Add a Friend</title>
<meta NAME="description" CONTENT="Form to add a friend on Abridg">
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

<body>
<div id="main" style="text-align: center; padding: 10px 0px 0px 0px;">
<div id="relwrapper">

<?php
/* When clicked, the 'Media Gallery' link takes the Administrator/Owner back to the index.php page. For an already logged in Owner, index.php needs to know a value for $_SESSION['AssociateID'], which may have been unset within the master and/or slave script. For that reason, this 'Gallery Link' (which is really a form) submits a (hidden) field (name = 'defaultassociateid') to convey the AssociateID pertaining to the logged in Owner himself/herself (which is typically given the AssociateName 'My Favorites Gallery"). */
?>
<div style="margin-top: 10px; text-align: center; padding: 0px;">
<form method="post" action="/index.php">
<input type="submit" class="submitLinkSmall" name="galleryview" value="Media Gallery">
</form>
</div>

<div class="gloss" style="font-weight: bold; font-variant: small-caps; margin-top: 24px; color: #E1B378;">Abridg Director</div>
<h1 style="margin-top: 12px; font-size: 22px; color: #9F0251; font-family: 'Century Gothic', Geneva, Arial, sans-serif;">Add a Friend</h1>
<?php
require('/home/paulme6/public_html/abridg/ssi/adminmenu.php'); // Include the navigation menu.

// Display the form for adding an Account Holder.
?><div style="width: 480px; margin: 40px auto 0px auto; text-align: left; border: 1px solid #E1B378; padding: 10px 5px 20px 10px;">

<form method="post" name="AddAssociate" action="/scripts/addassociate_slave.php">
<table align="center">
<tr style="height: 70px;">
<td style="width: 150px; vertical-align: top; padding-top: 22px;"><label>Friend&rsquo;s Name</label></td>
<td>
<input type="text" name="AssociateName" id="AssociateName" class="textfield" maxlength="40" size="30" value="<?php if (isset($_SESSION['AssociateName'])) echo $_SESSION['AssociateName']; ?>" onFocus="this.style.background='#FFFF97'" onBlur="this.style.background='white';">
<div class="greytextsmall">Examples: &ldquo;Jane&rdquo;, &ldquo;Brad &amp; Angelina&rdquo;, &ldquo;Uncle Sam&rdquo;</div>
<?php if ($_SESSION['MsgAssociateName'] != null) { echo $_SESSION['MsgAssociateName']; $_SESSION['MsgAssociateName']=null; } ?>
</td>
</tr>
<tr style="height: 80px;">
<td style="vertical-align: top; padding-top: 22px;"><label>Email (Username)</label></td>
<td>
<input type="text" name="OwnerUsername" id="OwnerUsername" class="textfield" maxlength="50" size="30" value="<?php if (isset($_SESSION['OwnerUsernameValidn'])) echo $_SESSION['OwnerUsernameValidn']; ?>" onFocus="this.style.background='#FFFF97'" onBlur="this.style.background='white';"><br>
<div class="greytextsmall">Enter your friend&rsquo;s email address. This will be their username.</div>
<?php if ($_SESSION['MsgOwnerUsername'] != null) { echo $_SESSION['MsgOwnerUsername']; $_SESSION['MsgOwnerUsername']=null; } ?>
</td>
</tr>
<tr>
<td colspan="2" style="text-align: center;"><br>
<input type="submit" name="InsertAccount" value="Add Friend" class="buttonstyle">
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