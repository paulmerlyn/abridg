<?php
/*
alertgenerator.php is an SSI file that contains the alertgenerator($assocsarray) function, which generates alert email messages to inform an associate that a Content Producer has assigned new content to him/her. Its only input variable is $assocsarray, which will have been set to NULL if the function is to be run for every associate of the logged in Owner (the case when the Administrator selects "auto_logout", "auto_onassign", or "auto_onhour" (auto_onhour isn't actually implemented at present) when selecting an option for managing alerts in manage.php) or it will be an array containing specific AssociateID values if the function is to be run for only those specified associates of the logged in Owner (the case when the Administrator selects "manual_now" as the option for managing alerts in manage.php).
*/
function alertgenerator($assocsarray)
{
// First examine the value of $assocsarray to determine the correct syntax for the $query that will select which (if any) of the logged in Owner's associates should receive an alert email message. If $assocsarray == NULL, all of his/her associates are potential recipients. On the other hand, if $assocsarray is an array (the case when the Administrator selected "Send an alert now to specified friends only"), then only the AssociateID values in that array are potential recipients.
if (is_null($assocsarray))
	{
	// Check whether any of logged in Owner's associates have been assigned a media item with an AssignDate (recorded in assign_table) that is more recent than the associate's AssociateLastLogin (recorded in associates_table). Also, when doing this query, we need to check that the associate's AlertLastSent (recorded in associates_table) is less recent (i.e. older) than the AssociateLastLogin (in associates_table) in order to never send more than one alert to an associate. And we need to omit from the results set any assignments pertaining to the associate who is the same person as the logged in Owner (i.e. the "My Gallery Favorites" associate) via the "associates_table.OwnerUsername != owners_table.OwnerUsername" where clause. And the DISTINCT strips duplicate rows in the resultset pertaining to multiple FileID values assigned to a single AssociateID in assign_table.
	$query = "SELECT DISTINCT owners_table.OwnerFirstName, owners_table.OwnerLastName, owners_table.OwnerUsername As SenderEmail, associates_table.AssociateName, associates_table.OwnerUsername As RecipientEmail, associates_table.AssociateID FROM associates_table, assign_table, owners_table WHERE associates_table.OwnerID = ".$_SESSION['LoggedInOwnerID']." AND associates_table.AssociateID = assign_table.AssociateID AND TIMESTAMPDIFF(MINUTE, associates_table.AssociateLastLogin, assign_table.AssignDate) >= 0 AND TIMESTAMPDIFF(MINUTE, associates_table.AlertLastSent, associates_table.AssociateLastLogin) >= 0 AND owners_table.OwnerID = associates_table.OwnerID AND associates_table.OwnerUsername != owners_table.OwnerUsername";
	}
else
	{
	// Check whether any of logged in Owner's associate(s) in the $assocsarray array have been assigned a media item with an AssignDate (recorded in assign_table) that is more recent than the associate's AssociateLastLogin (recorded in associates_table). We need to omit from the results set any assignments pertaining to the associate who is the same person as the logged in Owner (i.e. the "My Gallery Favorites" associate) via the "associates_table.OwnerUsername != owners_table.OwnerUsername" where clause. Note: for this query (unlike the one above), we DO NOT need to check that the associate's AlertLastSent (recorded in associates_table) is less recent (i.e. older) than the AssociateLastLogin (in associates_table).
	$query = "SELECT DISTINCT owners_table.OwnerFirstName, owners_table.OwnerLastName, owners_table.OwnerUsername As SenderEmail, associates_table.AssociateName, associates_table.OwnerUsername As RecipientEmail, associates_table.AssociateID FROM associates_table, assign_table, owners_table WHERE associates_table.OwnerID = ".$_SESSION['LoggedInOwnerID']." AND associates_table.AssociateID = assign_table.AssociateID AND TIMESTAMPDIFF(MINUTE, associates_table.AssociateLastLogin, assign_table.AssignDate) >= 0 AND owners_table.OwnerID = associates_table.OwnerID AND associates_table.OwnerUsername != owners_table.OwnerUsername";
	$NofAssocs = count($assocsarray);
	if ($NofAssocs > 0)
		{
		$query .= ' AND (';
		for ($i=0; $i < $NofAssocs; $i++)
			{
			$query .= "associates_table.AssociateID = ".$assocsarray[$i];
			if ($i < $NofAssocs - 1) $query .= ' OR ';
			}
		$query .= ')';
		}
	}
$result = mysql_query($query) or die('Query (select recipients of alerts from owners_table, associates_table, and assign_table) failed: ' . mysql_error().' and the database query string was: '.$query);
while ($row = mysql_fetch_assoc($result))
	{
	/* Send an email alert to each associate in the resultset. But the content of that email alert depends on whether the recipient is a registered Owner in his/her own right or merely an associate of the logged in Owner. First we need to determine whether the associate is already a registered account owner (in which case his/her OwnerPassword field will be non-blank in owners_table) or whether he/she just exists in owners_table as an OwnerUsername only (no other fields specified). */
	$query2 = "SELECT OwnerID, OwnerUsername, OwnerPassword FROM owners_table WHERE OwnerUsername = '".$row['RecipientEmail']."'";
	$result2 = mysql_query($query2) or die('Query (select OwnerUsername, OwnerPassword for the email recipient) failed: ' . mysql_error().' and the database query string was: '.$query2);
	$line = mysql_fetch_assoc($result2);
	$theID = $line['OwnerID'];
	$theUsername = $line['OwnerUsername'];
	$thePassword = $line['OwnerPassword'];
	$RegisteredOwner = (empty($line['OwnerPassword']) ? false : true); // ternary operator
	
	if (!$RegisteredOwner)
		{
		/* For associates who are non-registered owners, create a value for $cryptostring, an encrypted version of the email recipient's OwnerID and OwnerUsername. $cryptostring shall be constructed in the format III-XXXXXXXX where III is the non-hashed OwnerID, $theID, of the associate; and XXXXXXXX is the first eight characters of a sha1() hash upon $theUsername, the associate's OwnerUsername as provided by the Owner who added this associate to his/her list of friends (content consumers). To thwart easy use of a rainbow table, $theUsername is salted by prepending the sum of 1 + $theID before running them through the hash algorithm. $cryptostring is used as a query string in the http://wwww.abridg.com/ [Default = index.php] hyperlink in the $messageHTML and $messageText contents of an email alert sent to an associate who is a non-registered owner. index.php can then examine this query string and test it for a possible match against the actual OwnerID and OwnerUsername columns in owners_table. Note that encryption of the query string is necessary to protect the email recipient's privacy/security rather than send them transparently in a query string. Also note that by including the non-hashed $theID, I guarantee there's no possibility of matching to the wrong registered owner. */
		$cryptostring = $theID.'-';
		$salt = 1 + $theID;
		$hashedUsername = sha1($salt.$theUsername);
		$cryptostring .= substr($hashedUsername, 0, 8);
		
		// Formulate email body content for non-registered owner recipient
		$messageHTML = "<html><body><table cellspacing='10'><tr><td style='font-family: Arial, Helvetica, sans-serif'>Hello ".$row['AssociateName']."</td></tr>";
		$messageHTML .= "<tr><td style='font-family: Arial, Helvetica, sans-serif'>I&rsquo;m using Abridg and have uploaded content to share with you. Abridg allows people to share videos, images, audio files, and documents in a uniquely personal and private manner. To view my content, click <a href='http://www.abridg.com?".$cryptostring."'>here</a>.</td></tr>";
		$messageHTML .= "<tr><td style='font-family: Arial, Helvetica, sans-serif'>Alternatively, visit <a href='http://www.abridg.com'>www.abridg.com</a>, enter your email address [<kbd>".$theUsername."</kbd>], choose a password, and you&rsquo;ll be able to see my stuff.</td></tr>";
		$messageHTML .= "<tr><td style='font-family: Arial, Helvetica, sans-serif'>Either way, you&rsquo;re just a few keystrokes from full acces to an incredible platform for private sharing.</td></tr>";
		$messageHTML .= "<tr><td style='font-family: Arial, Helvetica, sans-serif'>Enjoy!</td></tr>";
		$messageHTML .= "<tr><td style='font-family: Arial, Helvetica, sans-serif'>".$row['OwnerFirstName']." ".$row['OwnerLastName']."</td></tr>";
		$messageHTML .= "<tr><td style='font-family: Arial, Helvetica, sans-serif; font-size: 11px;'>[This is an automated notification sent by Abridg&trade; with permission from ".$row['OwnerFirstName']." ".$row['OwnerLastName']."]</td></tr></body></html>";

		$messageText = "Hello ".$row['AssociateName']."\n\nI am using Abridg.com and have uploaded content to share with you. Abridg allows people to share videos, images, audio files, and documents in a uniquely personal and private manner. To view my new content, click type\n\nhttp://www.abridg.com?".$cryptostring."\n\n into your browser.\n\n";
		$messageText .= "Alternatively, visit www.abridg.com, enter your email address (".$theUsername."), choose a password, and you&rsquo;ll be able to see my stuff.\n\n";
		$messageText .= "Either way, you are just a few keystrokes from full acces to an incredible platform for private sharing.\n\n";
		$messageText .= "Enjoy!\n\n";
		$messageText .= $row['OwnerFirstName']." ".$row['OwnerLastName']."\n\n";
		$messageText .= "[This is an automated notification sent by Abridg(TM) with permission from ".$row['OwnerFirstName']." ".$row['OwnerLastName']."]";

		// For non-registered owners, we don't know the first and last name of the recipient so can't use them in email address line.
		$sendto = $row['RecipientEmail'];
		}
	else 
		{
		/* For associates who are registered owners, create a value for $cryptolog, an encrypted version of the email recipient's OwnerID and log in (i.e. an OwnerUsername and OwnerPassword pair). $cryptolog shall be constructed in the format III-XXXXYYYY where III is the non-hashed OwnerID, $theID, of the associate; XXXX is the first four characters of a sha1() hash upon $theUsername, the associate's OwnerUsername; and YYYY is the first four characters of a sha1() hash upon $thePassword, the associate's OwnerPassword. In both cases, to thwart easy use of a rainbow table, $theUsername and $thePassword are salted by prepending the sum of 1 + $theID to each before running them through the hash algorithm. $cryptolog is used as a query string in the http://wwww.abridg.com/ [Default = index.php] hyperlink in the $messageHTML and $messageText contents of an email alert sent to an associate who is a registered owner. index.php can then examine this query string and test it for a possible match against the actual OwnerID, OwnerUsername, and OwnerPassword columns in owners_table. Note that encryption of the query string is necessary to protect the email recipient's privacy/security rather than send them transparently in a query string. Also note that by including the non-hashed $theID, I guarantee there's no possibility of matching to the wrong registered owner. */
		$cryptolog = $theID.'-';
		$salt = 1 + $theID;
		$hashedUsername = sha1($salt.$theUsername);
		$hashedPassword = sha1($salt.$thePassword);
		$cryptolog .= substr($hashedUsername, 0, 4).substr($hashedPassword, 0, 4);
		
		// Formulate email body content for registered owner recipient
		$messageHTML = "<html><body><table cellspacing='10'><tr><td style='font-family: Arial, Helvetica, sans-serif'>Hello ".$row['AssociateName']."</td></tr>";
		$messageHTML .= "<tr><td style='font-family: Arial, Helvetica, sans-serif'>You&rsquo;ve got new content from me on Abridg. Click <a href='http://www.abridg.com?".$cryptolog."'>here</a> to view it.</td></tr>";
		$messageHTML .= "<tr><td style='font-family: Arial, Helvetica, sans-serif'>Alternatively, visit <a href='http://www.abridg.com'>www.abridg.com</a> and log into your Abridg account.</td></tr>";
		$messageHTML .= "<tr><td style='font-family: Arial, Helvetica, sans-serif'>Enjoy!</td></tr>";
		$messageHTML .= "<tr><td style='font-family: Arial, Helvetica, sans-serif'>".$row['OwnerFirstName']." ".$row['OwnerLastName']."</td></tr>";
		$messageHTML .= "<tr><td style='font-family: Arial, Helvetica, sans-serif; font-size: 11px;'>[This is an automated notification sent by Abridg&trade; with permission from ".$row['OwnerFirstName']." ".$row['OwnerLastName']."]</td></tr></body></html>";

		$messageText = "Hello ".$row['AssociateName']."\n\nYou\'ve got new content from me on Abridg. To view it, type\n\nhttp://www.abridg.com?".$cryptolog."\n\n into your browser.";
		$messageText .= "Alternatively, visit www.abridg.com and log into your Abridg account";
		$messageText .= "Enjoy!";
		$messageText .= $row['OwnerFirstName']." ".$row['OwnerLastName'];
		$messageText .= "[This is an automated notification sent by Abridg(TM) with permission from ".$row['OwnerFirstName']." ".$row['OwnerLastName']."]";
		
		// For registered owners, we know the first and last name of recipient so can use them in email address line.
		$sendto = $row['OwnerFirstName'].' '.$row['OwnerLastName'].' <'.$row['RecipientEmail'].'>';
		}
			
	// Include mailing software. In order to run it, I needed to first install Mail on the server (see http://pear.php.net/manual/en/package.mail.mail.php) and Mail_mime (see http://pear.php.net/manual/en/package.mail.mail-mime.example.php) via cPanel's PEAR gateway, and then include() them (see below).
	require_once('Mail.php');
	require_once('Mail/mime.php');

/* COMMENT OUT THIS NEXT paulmerlyn@sbcglobal.net LINE (WHICH OVERWRITES VALUE OF $theEmail ASSIGNED ABOVE) WHEN NOT TESTING */
//	$sendto = 'paulmerlyn@sbcglobal.net';

	$crlf = "\n";
	$hdrs = array(
		'From'    => $row['OwnerFirstName'].' '.$row['OwnerLastName'].' <'.$row['SenderEmail'].'>',
		'Subject' => 'New content for you from '.$row['OwnerFirstName'].' on Abridg',
		'Bcc' => 'paulmerlyn@yahoo.com'
		);

	$mime = new Mail_mime($crlf);
	$mime->setTXTBody($messageText);
	$mime->setHTMLBody($messageHTML);

	//do not ever try to call these lines in reverse order
	$body = $mime->get();
	$hdrs = $mime->headers($hdrs);

	$mail =& Mail::factory('mail');
	$mail->send("$sendto", $hdrs, $body);

	/* Update the AlertLastSent column of associates_table */
	$query3 = "UPDATE associates_table SET AlertLastSent = NOW() WHERE AssociateID = ".$row['AssociateID'];
	$result3 = mysql_query($query3) or die('Query (update AlertLastSent in associates_table) failed: ' . mysql_error().' and the database query string was: '.$query3);
	}
}
?>