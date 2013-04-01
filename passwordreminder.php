<?php
/*
The passwordreminder.php page displays when a user fails to provide a satisfactory login in index.php. Like the login screen of index.php, it's based on the Typica Login bootstrap theme obtainable from wrapbootstrap.com. It allows the user to click a 'Try Again' button that redirects him/her back to index.php for another attempt at logging in. Alternatively, the user can instead type his/her email address into a form field in order to trigger a password reminder. If the email address is verified as registered to a legitimate Account Owner, his/her username(s)/password(s) will be sent as a reminder to that address. passwordreminder.php is also its own slave script for processing the form submission, specifically (i) displaying a message to the user (either success for email address found, or invitation to try again or quit), and (ii) issuing an email if the user submission was a registered email address. 
*/

// Start a session
session_start();

ob_start(); // Used in conjunction with ob_flush() [see www.tutorialized.com/view/tutorial/PHP-redirect-page/27316], this allows me to postpone issuing output to the screen until after the header has been sent.

// Create short variable names
$VerifyEmail = $_POST['VerifyEmail'];
$CandidateEmail = $_POST['CandidateEmail'];

/* Prevent cross-site scripting via htmlspecialchars on these user-entry form field */
$CandidateEmail = htmlspecialchars($CandidateEmail, ENT_COMPAT);

if (!get_magic_quotes_gpc()) $CandidateEmail = addslashes($CandidateEmail);		
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<!-- Required to support border-radius css. See http://stackoverflow.com/questions/10784999/border-radius-not-working-in-ie9 -->
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"> 
<!-- Required for responsive Bootstrap-->
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Abridg&trade; | Password Reminder</title>
<link href="/abridg-custom.css" rel="stylesheet" type="text/css">
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>

<!-- Typica Login (a customized Twitter Bootstrap theme) stuff from wrapbootstrap.com -->
<!-- Le styles -->
<link href="css/bootstrap.css" rel="stylesheet">
<link href="css/bootstrap-responsive.min.css" rel="stylesheet">

<link rel="stylesheet" href="css/typica-login.css">

<!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
<!--[if lt IE 9]>
<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<!-- End of Typica Login stuff -->

<script type="text/javascript" language="javascript">
$(document).ready(function(){
  $(".short").click(function(){
    this.select();
  });
});

function checkEmailOnly(emailValue)
	{ 
	var re = /^\s*[\w\-\+_]+(\.[\w\-\+_]+)*\@[\w\-\+_]+\.[\w\-\+_]+(\.[\w\-\+_]+)*\s*$/;
	var emailLength = emailValue.length;
  	if (emailLength > 50 || !re.test(emailValue))
		{
		document.getElementById("EmailError").style.display = "inline";
		return false;
		}
	else
		{
		document.getElementById("EmailError").style.display = "none";
		return true;
		}
}

function hideAllErrors()
{
	document.getElementById("EmailError").style.display = "none";
	return true;
}
</script>
</head>

<body>

<div class="navbar navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container">
			<!-- DO NOT REMOVE this anchor tag or enclosed spans! -->
			<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			</a>
			<span class="brand"><img src="images/AbridgLoginLogo.png" alt="Abridg Login Logo"></span>
		</div>
	</div>
</div>
<?php
/* Process the form after the user clicked the 'Verify Email' submit button. */
if (isset($VerifyEmail))
	{
	unset($VerifyEmail);
	
	// If $CandidateEmail were blank (perhaps while Javascript was turned off), display anew the email submission form within passwordreminder.php ... otherwise, proceed to determine whether $CandidateEmail is actually registered to any accounts.
	if (empty($CandidateEmail))
		{
		?>
		<script type='text/javascript' language='javascript'>window.location = '/passwordreminder.php';</script>
		<noscript>
		<?php
		if (isset($_SERVER['HTTP_REFERER']))
			header("Location: /passwordreminder.php");
		?>
		</noscript>
		<?php
		}
	else // The user-submitted email address is of a valid format. Now see if it's registered to any account.
		{
		// Connect to mysql
		$db = mysql_connect('localhost', 'paulme6_merlyn', '')
		or die('Could not connect: ' . mysql_error());
		mysql_select_db('paulme6_abridg') or die('Could not select database: ' . mysql_error());
		
		$NoMatches = true; // Initialize to true. We'll make it false if we find at least one email that matches the user-submitted $CandidateEmail address.
		$ownerAccountHTML = ''; // Clear for fresh run through
		$ownerAccountText = '';
		$NofOwnerMatches = 0;
		$NofNonOwnerMatches = 0;
	
		// Look for any OwnerUsename (email addresses) values in owners_table that match the $CandidateEmail address.
		$query = "SELECT COUNT(*) AS OwnerCount, OwnerFirstName, OwnerLastName, OwnerOrganization, OwnerUsername, OwnerPassword FROM owners_table WHERE OwnerUsername = '".$CandidateEmail."'";
		$result = mysql_query($query) or die('Query (select COUNT(*) of matching owner emails in owners_table has failed: ' . mysql_error());
		$row = mysql_fetch_assoc($result);
		if ($row['OwnerCount'] > 0) // Only display account holders if at least one match occurred with an Owner and $CandidateEmail. Note that there should never be more than one match because email addresses are supposed to be unique.
			{
			$NoMatches = false;
			$NofOwnerMatches = $row['OwnerCount'];
			// Store the matching Owner in a table row string for later inclusion in an email to the person who forgot his/her username/password.
			$theOwnerFirstName = $row['OwnerFirstName'];
			$theOwnerLastName = $row['OwnerLastName'];
			$theOwnerOrganization = $row['OwnerOrganization'];
			$theOwnerLabel = $row['OwnerLabel'];
			if (!empty($theOwnerFirstName) AND !empty($theOwnerLastName) AND !empty($theOwnerOrganization))	$TheName = $theOwnerFirstName.' '.$theOwnerLastName.' ('.$theOwnerOrganization.')';
			else if (empty($theOwnerOrganization) && empty($theOwnerLastName)) $TheName = $theOwnerFirstName;
			else if (empty($theOwnerOrganization) && empty($theOwnerFirstName)) $TheName = $theOwnerLastName;
			else if (empty($theOwnerOrganization)) $TheName = $theOwnerFirstName.' '.$theOwnerLastName;
			else if (!empty($theOwnerFirstName) && !empty($theOwnerOrganization)) $TheName = $theOwnerFirstName.' ('.$theOwnerOrganization.')';
			else if (!empty($theOwnerOrganization)) $TheName = $theOwnerOrganization;
			else if (!empty($theOwnerLastName) && !empty($theOwnerOrganization)) $TheName = $theOwnerLastName.' ('.$theOwnerOrganization.')';
			else $TheName = theOwnerLabel; // The only other possible situation is that OwnerFirstName, OwnerLastName, OwnerOrganization are all empty, in which case we must assgin $TheName to theOwnerLabel.
			$ownerAccountHTML .= "<tr><td style='font-family: Arial, Helvetica, sans-serif'>".$TheName."</td><td style='font-family: Arial, Helvetica, sans-serif'>".$row['OwnerUsername']."</td><td style='font-family: Arial, Helvetica, sans-serif'>".$row['OwnerPassword']."</td></tr>";
			$ownerAccountText .= $TheName."          ".$row['OwnerUsername']."          ".$row['OwnerPassword']."\n";
			}

		/* If there was a match with $CandidateEmail, create and send an email to remind the user of his/her username and password for his/her Owner account. I'm using a Mail package that readily handles MIME and email attachments. In order to run it, I needed to first install Mail on the server (see http://pear.php.net/manual/en/package.mail.mail.php) and Mail_mime (see http://pear.php.net/manual/en/package.mail.mail-mime.example.php) via cPanel's PEAR gateway, and then include() them (see below). */
		if (!$NoMatches)
			{
			require('Mail.php');
			require('Mail/mime.php');

			// Compose the entire email body now that we've figured out which (if any) rows of owners_table and associates_table have a matching email address.
			$messageHTML = "<html><body><table cellspacing='10'><tr><td colspan='3' style='font-family: Arial, Helvetica, sans-serif'>Hello ".$TheName."</td></tr>";
			$messageHTML .= "<tr><td colspan='3' style='font-family: Arial, Helvetica, sans-serif'>This is an automated response to your request for a reminder of your username and password in order to access your Abridg account.</td></tr>";
			if ($NofOwnerMatches > 0) $messageHTML .= "<tr><td colspan='3' style='font-family: Arial, Helvetica, sans-serif'>You may log in as follows:</td></tr>";
			if ($NofOwnerMatches > 0) $messageHTML .= "<tr><th style='font-family: Arial, Helvetica, sans-serif; text-align: left;'>Account Name</td><th style='font-family: Arial, Helvetica, sans-serif; text-align: left;'>Username</td><th style='font-family: Arial, Helvetica, sans-serif; text-align: left;'>Password</td></tr>";
			if ($NofOwnerMatches > 0) $messageHTML .= $ownerAccountHTML;
			$messageHTML .= "</table></body></html>";

			$messageText = "Hello ".$TheName."\n\nThis is an automated response to your request for a reminder of your username and password in order to access your Abridg account.\n\n";
			if ($NofOwnerMatches > 0) $messageText .= "You may log in as follows:\n";
			if ($NofOwnerMatches > 0) $messageText .= "Account Name          Username          Password\n";
			if ($NofOwnerMatches > 0) $messageText .= $ownerAccountText;
		
			$sendto = $CandidateEmail;
			$crlf = "\n";
			$hdrs = array(
              'From'    => 'donotreply@abridg.com',
   	          'Subject' => 'Your Username/Password Reminder',
			  'Bcc' => 'paul@abridg.com'
              );

			$mime = new Mail_mime($crlf);
			$mime->setTXTBody($messageText);
			$mime->setHTMLBody($messageHTML);
	
			//do not ever try to call these lines in reverse order
			$body = $mime->get();
			$hdrs = $mime->headers($hdrs);

			$mail =& Mail::factory('mail');
			$mail->send("$sendto", $hdrs, $body);
		
			// Given a match was found, display a message on the screen to say that an email message has been sent to this address with the username/password in the message.
			?>
			<div class="container">
				<div id="login-wraper" style="width: 450px;">
					<legend class="biglegend">It&rsquo;s on its way!</legend>
            
					<div class="body">
						<br><br>
						<label>We&rsquo;ve sent a password reminder email to <?=$CandidateEmail; ?>.</label>
						<br>
			   			<!-- logout.php will unset any session variables such as $_SESSION['Authenticated'] and redirect user back to index.php -->
						<button class="btn btn-success" type="button" onClick="window.location = '/scripts/logout.php'; return false;">Continue</button>
					</div>
				</div>  
			</div>
			<?php
			ob_end_flush();
			}		
		else // No match was found. Invite the user to try again, submitting a different candidate email address.
			{
			?>
			<div class="container">
			<div id="login-wraper" style="width: 450px;">
				<form method="post" name="oopsform" id="oopsform" class="form login-form" action="/passwordreminder.php">
				<legend>No luck finding that address&hellip;</legend>
            
				<div class="body">
					<label>We didn&rsquo;t locate any accounts registered to <?=$CandidateEmail; ?>.</label>
		   			<!-- logout.php will unset any session variables such as $_SESSION['Authenticated'] and redirect user back to index.php -->
					<br><br>
					<label>Try another email address?</label>
					<br>
					<div class="input-append">
					<input autocapitalize="off" class="short" id="appendedInputButton" type="text" name="CandidateEmail" maxlength="40" size="30" value="<?php if (isset($_SESSION['SignUpEmail'])) echo $_SESSION['SignUpEmail']; else echo 'Email (Username)'; ?>" onFocus="hideAllErrors();" onBlur="return checkEmailOnly('CandidateEmail');">
					<button class="btn btn-success" name="VerifyEmail" type="submit" onClick="return CheckEmailOnly(this.value);">Go!</button>
					<div class="error" id="EmailError"><br>Please correct the error in your email address.<br></div>
					</div>

					<br>
					<button class="btn" type="button" onClick="window.location = '/scripts/logout.php'; return false;">Cancel</button>

				</div>
				</form>
			</div>  
			</div>
			<?php
			ob_end_flush();
			}	
		}
	}
else // The user hasn't yet/just clicked the 'Verify Email' button. Simply display the HTML form for providining a candidate email address.
	{
?>
<!-- Start of the screen/form that gets displayed when the user fails to provide a valid username-password pair in index.php. Courtesy http://www.w3resource.com/twitter-bootstrap/modals-tutorial.php#firstexample -->
<div class="container">
	<div id="login-wraper" style="width: 450px;">
		<form method="post" name="oopsform" id="oopsform" class="form login-form" action="/passwordreminder.php">
		<legend>Oops! We can&rsquo;t validate your <span class="red">Abridg</span> account.</legend>
            
		<div class="body">
   			<!-- logout.php will unset any session variables such as $_SESSION['Authenticated'] and redirect user back to index.php -->
			<button class="btn btn-warning" type="button" onClick="window.location = '/scripts/logout.php'; return false;">Try Again</button>

			<br><br>
			<label>Forgotten your password? We can send a reminder.</label>
			<br>
			
			<div class="input-append">
			<input autocapitalize="off" class="short" id="appendedInputButton" type="text" name="CandidateEmail" maxlength="40" size="30" value="<?php if (isset($_SESSION['SignUpEmail'])) echo $_SESSION['SignUpEmail']; else echo 'Email (Username)'; ?>" onFocus="hideAllErrors();" onBlur="return checkEmailOnly(this.value);">
			<button class="btn btn-success" name="VerifyEmail" type="submit" onClick="return CheckEmailOnly('CandidateEmail');">Go!</button>
			<div class="error" id="EmailError"><br>Please correct the error in your email address.<br></div>
			</div>
		</div>
     
		</form>
	</div>  
</div>

<?php
	ob_end_flush();
	}
?>
<footer class="white navbar-fixed-bottom">
<!-- Here we invoke the bootstrap modal. See http://www.w3resource.com/twitter-bootstrap/modals-tutorial.php#firstexample -->
<form method="post" action="/index.php">
Don't have an account yet? <button type="submit" name="RegisterRequestViaPasswordReminder" class="btn btn-black" onClick="$('#SignUpModal').modal({show: true});">Register</button>
</form>
</footer>

<!-- Typica Login (a customized Twitter Bootstrap theme) stuff from wrapbootstrap.com. Note that I had better success by putting the jquery source reference in the header. -->
    <!-- Le javascript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="js/bootstrap.js"></script>
    <script src="js/backstretch.min.js"></script>
    <script src="js/typica-login.js"></script>
	<script src="js/bootstrap-modal.js"></script>  
<!-- End of Typica Login stuff -->

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
