<?php
/*
This action script (which is called by passwordreminder.php as well as the /ssi file footer.php, which in turn provides a "Log Out" link button) unsets any and all session variables that would validate a user -- specifically, $_SESSION['Authenticated'] (which is used to establish the authenticity of account holders/Owners) and $_SESSION['ValidatedSuperAdmin'] (which is used to establish the validity of a person (God) with rights to create Owners.
	logout.php also sends alerts if the logged in Owner selected "Send alerts automatically on logout" in one of the manage.php HTML forms (that's initially processed by managealerts_slave.php).
*/

// Start a session
session_start();

ob_start(); // Used in conjunction with ob_flush() [see www.tutorialized.com/view/tutorial/PHP-redirect-page/27316], this allows me to postpone issuing output to the screenuntil after the header has been sent.

// Create short variable names
$LoggedOut = $_POST['LoggedOut'];

/* Determine whether we should send any alerts upon logout. But don't bother if $_SESSION['ValidatedSuperAdmin'] is 'true' because the sending of alerts has no meaning when logout.php is executed by a Super-Administrator. Also don't both if the $_SESSION['LoggedInOwnerID'] session variable has since been cleared b/c there's no point -- you'll get a PHP error when trying to execute the query below in that situation anyway. */

if ($_SESSION['ValidatedSuperAdmin'] != 'true' && isset($LoggedOut) && !empty($_SESSION['LoggedInOwnerID']))
	{

	require('../ssi/alertgenerator.php'); // Include the alertgenerator.php file, which contains the alertgenerator() function for generating alert email messages to inform an associate (in this case, the logged in Owner) that a Content Producer has assigned new content to him/her.

	// Connect to mysql
	$db = mysql_connect('localhost', 'paulme6_merlyn', 'fePhaCj64mkik')
	or die('Could not connect: ' . mysql_error());
	mysql_select_db('paulme6_abridg') or die('Could not select database: ' . mysql_error());

	// First check whether logged in Owner's AlertType == 'auto_onlogout' in owners_table
	$query = "SELECT AlertType from owners_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
	$result = mysql_query($query) or die('Query (select AlertType from owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	$row = mysql_fetch_assoc($result);
	if ($row['AlertType'] == 'auto_onlogout')
		{
		alertgenerator(NULL); // Call this function, defined in include'd file alertgenerator.php
		}
	
	}

/* Clear out session variables */
$_SESSION['Authenticated'] = 'false';
$_SESSION['ValidatedSuperAdmin'] = 'false'; // The SuperAdministrator can create Owners
unset($_SESSION['AssociateSelected']); // used in assign.php
unset($_SESSION['AssociateID']); // used in assign.php
unset($_SESSION['AssociateSelected']); // used in assign.php
unset($_SESSION['FileSelected']); // used in assign.php
unset($_SESSION['VPwidth']); // viewport width, obtained, and set as a session variable, in index.php
unset($_SESSION['VPheight']); // viewport height, obtained, and set as a session variable, in index.php
unset($_SESSION['LoggedInOwnerID']); // used in index.php, widgetslave.php, and Abridg administrator pages to distinguish the Owner who is logged in from owners whose OwnerID is temporarily assigned to $_SESSION['OwnerID'] for purposes of displaying content.
unset($_SESSION['OwnerID']); // used in addassociate.php, addassociate_slave.php, upload.php, upload_slave.php, assign.php, assign_slave.php
unset($_SESSION['AssociateID']); // set in assign_slave.php and used upon return to assign.php to preset an account holder's radio button
unset($_SESSION['FileID']); // set in assign_slave.php and used upon return to assign.php to preset a media item's radio button
unset($_SESSION['PreviousLogIn']); // used in index.php and widgetslave.php
unset($_SESSION['PreviousLogInOfLoggedInOwner']); // used in index.php and widgetslave.php
unset($_SESSION['TabNumber']); // used in widgetslave.php
unset($_SESSION['KeepWidgetOpen']); // used in index.php and widgetslave.php
unset($_SESSION['LoggedInOwnerLabel']); // used in index.php and widgetslave.php
unset($_SESSION['LoggedInOwnersOwnAssociate']); // set in index.php
unset($_SESSION['UnregisteredOwnerLogin']); // set in index.php
unset($_SESSION['RegisteredOwnerUsernameViaAlert']); // set in index.php
unset($_SESSION['RegisteredOwnerPasswordViaAlert']); // set in index.php
unset($_SESSION['RegisteredOwnerViaAlert']); // set in index.php
unset($_SESSION['NonregisteredOwnerUsernameViaAlert']); // set in nonregisteredownerlogin_slave.php
unset($_SESSION['NonregisteredOwnerPasswordViaAlert']); // set in nonregisteredownerlogin_slave.php
unset($_SESSION['NonregisteredOwnerViaAlert']); // set in nonregisteredownerlogin_slave.php
unset($_SESSION['RegOwnerStatus']); // set in index.php
unset($_SESSION['WelcomeMsgShown']); // set in index.php
unset($_SESSION['ShowWelcomeMsg']); // set in index.php
unset($_SESSION['RegisterRequestViaPasswordReminder']); // set in index.php
						
// Unset session variables to prepopulate the value of a form field after a PHP form validation by a _slave.php script directed control back to the original form.
unset($_SESSION['AssociateName']);
unset($_SESSION['Title']);
unset($_SESSION['FileDescription']);
unset($_SESSION['FileCategory']);
unset($_SESSION['OwnerFirstName']); // Used in createowner.php and createowner_slave.php
unset($_SESSION['OwnerLastName']); // Used in createowner.php and createowner_slave.php
unset($_SESSION['OwnerOrganization']); // Used in createowner.php and createowner_slave.php
unset($_SESSION['OwnerLabel']); // Used in index.php
unset($_SESSION['LoggedInOwnerUsername']); // used in index.php, widgetslave.php, and Abridg administrator pages to distinguish the Owner who is logged in from owners whose OwnerUsername is temporarily assigned to $_SESSION['OwnerUsername'] for purposes of displaying content.
unset($_SESSION['SelectedAssociateID']); // Set in assign_slave.php and used in assign.php


// Send user back to Aridg home page.
if (isset($_SERVER['HTTP_REFERER'])) // Antivirus software sometimes blocks transmission of HTTP_REFERER.
	{
	echo "<script type='text/javascript' language='javascript'>window.location = '/index.php';</script>";
	ob_flush();
	}
else
	{
	header("Location: /index.php"); 
	};
exit;
?>