<?php
/*
destroyowner.php lists all registered (i.e. full) and semi-registerd (i.e. OwnerPassword but no OwnerFirtName, OwnerLastName, or OwnerLabel) account Owners. By full, we mean that the person has a fully complete row in owners_table by virtue of either having supplied the data via a form field within createowner.php, or having supplied the data after receiving an alert message from another Owner who had added this person as an associate (friend/content consumer) and then assigned content to the person (which would have triggered the alert email message inviting the person to sign up as a registered account holder). Specifically, destroyowner.php allows a Superadministrator to select one radio button (of value equal to the OwnerID) for destruction. It's a front-end that would be replaced by a cronjob script in a commercial version of abridg that destroys Owner accounts automatically if, say, the Owner hasn't paid a subscription fee. The heavy-duty work is done by a function defined in corefunctions.php that must (i) delete (unlink) from the server every media item and snapshot and videosnapshot and unique querystring .php page associated with the destroyable owner and his/her media items; (ii) delete all rows in assign_table where the AssociateID column contains a value found in the AssociateIDs field of owners_table for this Owner; (iii) delete all media items in media_table associated with the OwnerID; (iv) delete all associates in associates_table associated with the OwnerID; (v) delete the row of owners_table corresponding to that Owner(s).
*/

// Start a session
session_start();

// Connect to DB
$db = mysql_connect('localhost', 'paulme6_merlyn', '')
or die('Could not connect: ' . mysql_error());
mysql_select_db('paulme6_abridg') or die('Could not connect to the abridg database: ' . mysql_error());

// Create short variable names
$SuperAdminPassword = $_POST['SuperAdminPassword']; // The password entered by the person (the SuperAdministrator) with authority (supposedly) to add accounts, upload media, assign access, etc.

// Sanitize $Authentication
$SuperAdminPassword = htmlspecialchars($SuperAdminPassword);
if (!get_magic_quotes_gpc())
	{
	$SuperAdminPassword = mysql_real_escape_string($SuperAdminPassword); // More secure than addslashes according to: http://shiflett.org/blog/2006/jan/addslashes-versus-mysql-real-escape-string. Note that a MySQL connection is required (otherwise, the connection has no other purpose within this script) before using mysql_real_escape_string() otherwise an error of level E_WARNING is generated.
	}

if (sha1($SuperAdminPassword) == 'da48b412011698d4eff4ca21d0954b48ca445fd6' || $_SESSION['ValidatedSuperAdmin'] == 'true') 
	{
	$_SESSION['ValidatedSuperAdmin'] = 'true';
	}
else
	{
	unset($_SESSION['ValidatedSuperAdmin']);
	};
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Destroy an Account on Abridg</title>
<meta NAME="description" CONTENT="Form to destroy an owner on the Abridg Site">
<link href="/abridg.css" rel="stylesheet" type="text/css">
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

<div style="margin-top: 10px; text-align: center; padding: 0px;">
<form method="post" action="/index.php">
<input type="submit" class="submitLinkSmall" value="Home">
</form>
</div>

<h1 style="margin-top: 15px; font-size: 22px; color: #9F0251;">Destroy an Owner on Abridg</h1>
<?php
if  (empty($SuperAdminPassword) && $_SESSION['ValidatedSuperAdmin'] != 'true')
	{
	// Visitor needs to authenticate him/herself as a superadministrator by entering the superadministrator's password
	?>
	<h3 style="text-align: center;">Please enter password:</h3>
	<br>
	<script type="text/javascript">window.onload = FocusFirst;</script>
	<form method="post" action="/destroyowner.php">
	<table border="0" width="280" style="margin: 0px auto;">
	<tr>
	<td align="center"><input type="password" name="SuperAdminPassword" maxlength="40" size="20"></td>
	</tr>
	<tr><td>&nbsp;</td></tr>
	<tr>
	<td align="center"><input type="submit" class="buttonstyle" value="Continue"></td>
	</tr>
	</table>
	</form>
	<?php
	}
else
	{
	// See if the user has been validated by examining the $_SESSION['ValidatedSuperAdmin'] session variable, which will have been set to 'true' or 'false' earlier in the script according to whether or not it matched the correct value. If the superadministrator is validated, proceed to display the form for selection of an owner to be destroyed.
	if ($_SESSION['ValidatedSuperAdmin'] == 'true')
		{
		?>
		<div style="text-align: left;">
		<form method="post" name="CreateOwner" action="/scripts/destroyowner_slave.php">
		<table align="center">
		<?php
		// Retrieve all registered and semi-registered owners from owners_table i.e. owners who have a non-blank OwnerPassword column.
		$query = "SELECT COUNT(*) AS TheCount FROM owners_table WHERE OwnerPassword !=''";
		$result = mysql_query($query) or die('Query (select COUNT(*) from owners_table has failed: ' . mysql_error());
		$row = mysql_fetch_assoc($result);
		if ($row['TheCount'] > 0) // Only display the list of Owners if at least one Owner exists.
			{
			$query = "SELECT OwnerFirstName, OwnerLastName, OwnerOrganization, OwnerLabel, OwnerID, OwnerUsername FROM owners_table WHERE OwnerPassword != ''";
			$result = mysql_query($query) or die('Query (select OwnerFirstName, OwnerLastName, OwnerOrganization, OwnerID from owners_table has failed: ' . mysql_error());
			while ($row = mysql_fetch_assoc($result))
				{
				// Given the irreversible nature and consequences of an account destroy operation, it seems safer to display accounts by reference to OwnerFirstName, OwnerLastName, and/or OwnerOrganization rather than merely by OwnerLabel only.
				$theOwnerFirstName = $row['OwnerFirstName'];
				$theOwnerLastName = $row['OwnerLastName'];
				$theOwnerOrganization = $row['OwnerOrganization'];
				$theOwnerLabel = $row['OwnerLabel'];
				$theOwnerUsername = $row['OwnerUsername']; // never blank.
				if (!empty($theOwnerFirstName) AND !empty($theOwnerLastName) AND !empty($theOwnerOrganization))	$TheName = $theOwnerFirstName.' '.$theOwnerLastName.' ('.$theOwnerOrganization.')';
				else if (empty($theOwnerOrganization) && empty($theOwnerLastName) && (!empty($theOwnerFirstName) || !empty($theOwnerLabel))) $TheName = $theOwnerFirstName.' ('.$theOwnerLabel.')';
				else if (empty($theOwnerOrganization) && empty($theOwnerFirstName) && (!empty($theOwnerLastName) || !empty($theOwnerLabel))) $TheName = $theOwnerLastName.' ('.$theOwnerLabel.')';
				else if (empty($theOwnerOrganization) && (!empty($theOwnerFirstName) || !empty($theOwnerLastName) || !empty($theOwnerLabel))) $TheName = $theOwnerFirstName.' '.$theOwnerLastName.' ('.$theOwnerLabel.')';
				else if (!empty($theOwnerFirstName) && !empty($theOwnerOrganization)) $TheName = $theOwnerFirstName.' ('.$theOwnerOrganization.')';
				else if (!empty($theOwnerOrganization)) $TheName = $theOwnerOrganization.' ('.$theOwnerLabel.')';
				else if (!empty($theOwnerLastName) && !empty($theOwnerOrganization)) $TheName = $theOwnerLastName.' ('.$theOwnerOrganization.')';
				// The only other possible situation is that OwnerFirstName, OwnerLastName, OwnerOrganization are all empty, in which case we must assgin $TheName to theOwnerLabel if it's non-blank...
				else if (!empty($theOwnerLabel)) $TheName = theOwnerLabel; 
				// ... or if $theOwnerLabel is blank, then assign $TheName to $theOwnerUsername.
				else $TheName = $theOwnerUsername.'&nbsp;&nbsp;[non-registered owner]';
				?>
				<tr style="height: 32px;">
				<td style="width: 30px; vertical-align: top; padding-top: 4px;">
				<input type="radio" name="Owner" id="Owner<?=$row['OwnerID']; ?>" value="<?=$row['OwnerID']; ?>" onClick="this.form.submit();" style="position: relative; top: 2px;">
				</td>
				<td><label><?=$TheName; ?></label></td>
				</tr>
				<?php
				}
			}
		else
			{
		?>
			<tr style="height: 60px;">
			<td style="width: 180px;">
			<p class="text">No account owners exist</p>	
			</td>
			</tr>
		<?php
			}
		?>
		</table>
		</form>
		<div style="text-align: center;">
		<?php
		require ("/home/paulme6/public_html/abridg/ssi/footer.php");
		?>
		</div>
		</div>
		<?php
		}
	if  (!$_SESSION['ValidatedSuperAdmin'] == 'true') // The password entered for this administrator has been rejected and so the user is NOT validated.
		{
		// The password entered for this administrator has been rejected and so the user is NOT validated.
		echo "<p class='text' style='margin-top: 30px;'>Invalid password. SuperAdministrator access is denied. Click ";
		// Include a 'Back' button for redisplaying the Authentication form.
		if (isset($_SERVER['HTTP_REFERER'])) // Antivirus software sometimes blocks HTTP_REFERER.
			{
			echo "<a href='".$_SERVER['HTTP_REFERER']."'>here</a> to try again.</p>";
			}
		else
			{
			echo "<a href='javascript:history.back()'>here</a> to try again.</p>";
			}
		}
	}
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
