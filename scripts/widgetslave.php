<?php
/*
widgetslave.php is actually an action script for the contentconsumersform and contentproducersform HTML forms that exist in index.php. (There is no "widget.php" master script.) The widget is the 2-tab panel selector that the logged in Owner uses to select a content consumer (one tab) or a content producer (the other tab).
*/

// Start a session
session_start();

if ($_SESSION['Authenticated'] != 'true') exit;

// Connect to database
$db = mysql_connect('localhost', 'paulme6_merlyn', 'fePhaCj64mkik')
or die('Could not connect: ' . mysql_error());
mysql_select_db('paulme6_abridg') or die('Could not select database: ' . mysql_error());

// Create short variable names
$ContentConsumer = $_POST['ContentConsumer'];
$ContentProducer = $_POST['ContentProducer'];

if (isset($ContentConsumer))
	{
	/* Perform the action script for when the user clicked one of the radio buttons under the "My Stuff" (aka "My Content Consumers") tab within the two-tab panels. */

	// Update the value of $_SESSION['AssociateID'] for use in presetting one of the ContentConsumer radio buttons
	$_SESSION['AssociateID'] = $ContentConsumer;
	$_SESSION['InitialAssociateIDcc'] = $ContentConsumer; // Update the initialization value for preselection of one radio button in the content consumers radio-button list that gets reconstructed in HTML below after this slave code has been executed.
	
	// Reset $_SESSION['OwnerLabel'] and $_SESSION['OwnerID'] to the values of the logged in Owner (as defined when he/she initially logged in via index.php).
	$_SESSION['OwnerLabel'] = $_SESSION['LoggedInOwnerLabel'];
	$_SESSION['OwnerID'] = $_SESSION['LoggedInOwnerID'];
	
	// Update the value of $_SESSION['PreviousLogIn'] so that, upon redisplay of index.php, the display will show only the appropriate new items for the particular associate (i.e. consumer of this logged-in Owner) selected by the radio button when the 'New' link (which controls display of the NewMediaScreen) is clicked. To do that, we need to retrieve the AssociateLastLogin value from associates_table where AssociateID = $ContentConsumer.
	// However, there's an exception required for the associate (in associates_table) pertaining to the logged in Owner. The value of OwnerLastLogin for that Owner was updated to the present (recent, perhaps a few seconds earlier) time when he/she just logged in. But we did store its prior value as session variable $_SESSION['PreviousLogInOfLoggedInOwner'] in index.php when the Owner logged in.
	$query = "SELECT AssociateLastLogin, OwnerUsername FROM associates_table WHERE AssociateID = ".$ContentConsumer;
	$result = mysql_query($query) or die('Query (select of AssociateLastLogin, OwnerUsername from associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	$row = mysql_fetch_assoc($result); 
	if ($row['OwnerUsername'] == $_SESSION['LoggedInOwnerUsername']) 
		{
			$_SESSION['PreviousLogIn'] = $_SESSION['PreviousLogInOfLoggedInOwner']; // The exception
		}
	else
		{
		$_SESSION['PreviousLogIn'] = $row['AssociateLastLogin']; // Whenever the associate is not the same person as the logged in Owner.
		}

	$_SESSION['TabNumber'] = 0; // $_SESSION['TabNumber'] gets used when invoking the Spry Tabbed Panel initialization javascript code below to set the default (i.e. displayed) tab.

	// Unset $ContentConsumer so that the slave clause for selection of a content consumer isn't perpetually executed, even when the user hasn't clicked a content consumer radio button.
	unset($ContentConsumer);
	}

if (isset($ContentProducer))
	{
	/* Perform the action script for when the user clicked one of the radio buttons under the "My Friends' Stuff" (aka "My Content Producers") tab within the two-tab panels. */

	// Update the value of $_SESSION['AssociateID'] for use in presetting one of the ContentProducer radio buttons
	$_SESSION['AssociateID'] = $ContentProducer;
	$_SESSION['InitialAssociateIDcp'] = $ContentProducer; // Update the initialization value for preselection of one radio button in the content producers radio-button list that gets reconstructed in HTML below after this slave code has been executed.
	
	// Reset the value of $_SESSION['OwnerLabel'] and $_SESSION['OwnerID']. $_SESSION['OwnerLabel'] gets used in index.php in the masthead to say e.g. "John Doe's Media | powered by Abridg" where 'John Doe' is $_SESSION['OwnerLabel']. Both $_SESSION['OwnerLabel'] and $_SESSION['OwnerID'] get initially set in index.php when the user logs in.
	// First obtain the OwnerLabel and OwnerID of the content producer selected via a radio button click in the Content Producers panel. Use a table join.
	$query = "SELECT owners_table.OwnerLabel, owners_table.OwnerID FROM owners_table, associates_table WHERE associates_table.AssociateID = ".$ContentProducer." AND associates_table.OwnerID = owners_table.OwnerID";
	$result = mysql_query($query) or die('Query (select OwnerLabel in owners_table, associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	$row = mysql_fetch_assoc($result);
	$_SESSION['OwnerLabel'] = $row['OwnerLabel'];
	$_SESSION['OwnerID'] = $row['OwnerID'];

	// Update the AssociateLastLogin column of associates_table for the row where AssociateID == $ContentProducer. Note that this column stores the datetime (i.e. today's date) of the logged in Owner's most recent viewing of the media files pertaining to this row of the associates_table.
	$query = "UPDATE associates_table SET AssociateLastLogin = NOW() WHERE AssociateID = ".$ContentProducer;
	$result = mysql_query($query) or die('Query (update AssociateLastLogin in associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);

	// Update the value of $_SESSION['PreviousLogIn'] so that, upon redisplay of index.php, the display will show to the content consumer (i.e. the Owner who is currently logged in) only the new items that the selected content producer (who is selected via a radio button in the panel) from among all items assigned to this content consumer by the content producer when the 'New' link (which controls display of the NewMediaScreen) is clicked. To do that, we simply set $_SESSION['PreviousLogIn'] to $_SESSION['PreviousLogInOfLoggedInOwner'], which was saved as a value in index.php when the logged in owner initially logged into index.php.
	$_SESSION['PreviousLogIn'] = $_SESSION['PreviousLogInOfLoggedInOwner'];

	$_SESSION['TabNumber'] = 1; // $_SESSION['TabNumber'] gets used when invoking the Spry Tabbed Panel initialization javascript code below to set the default (i.e. displayed) tab.

	// Unset $ContentProducer so that the slave clause for selection of a content producer isn't perpetually executed, even when the user hasn't clicked a content producer radio button.
	unset($ContentProducer);
	}

// Set $_SESSION['KeepWidgetOpen'] to true for subsequent use by index.php
$_SESSION['KeepWidgetOpen'] = 'true';

// Now go back to index.php.
?>
<script type='text/javascript' language='javascript'>
window.location = '/index.php';
</script>
<?php
exit;	
?>