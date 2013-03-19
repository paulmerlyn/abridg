<?php
session_start();
ob_start();

// Create short variable names
$name = $_POST['name'];
$organization = $_POST['organization'];
$email = $_POST['email'];

// Assign values to the session variables that are used to prepopulate the preserved values of the posted field values in theplan.php if/when that page is represented to the user with a PHP form validation error.
$_SESSION['name'] = $name;
$_SESSION['organization'] = $organization;
$_SESSION['email'] = $email;

// Prevent cross-site scripting
$name = htmlspecialchars($name, ENT_QUOTES);
$email = htmlspecialchars($email, ENT_QUOTES);
$organization = htmlspecialchars($organization, ENT_QUOTES);

/*
Begin PHP form validation.
*/

// Create a session variable for the PHP form validation flag, and initialize it to 'false' i.e. assume it's valid.
$_SESSION['phpinvalidflag'] = false;

// Create session variables to hold inline error messages, and initialize them to blank.
$_SESSION['MsgName'] = null;
$_SESSION['MsgEmail'] = null;
$_SESSION['MsgOrganization'] = null;

// Seek to validate $name
$illegalCharSet = '[~%\^\*_`\$?=!:";<>]+'; // Exclude everything except A-Z, a-z, numbers, period, hyphen, apostrophe (single quote), slash, space, comma, period, and parentheses.
$reqdCharSet = "[A-Za-z]{1,}";  // At least one letter
if (ereg($illegalCharSet, $name) || !ereg($reqdCharSet, $name))
	{
	$_SESSION['MsgName'] = "<span class='errorphp'><br>Please provide a valid name.<br></span>";
	$_SESSION['phpinvalidflag'] = true; 
	};

// Seek to validate $organization
$illegalCharSet = '[~#\^_`\";<>]+'; // Exclude everything except A-Z, a-z, numbers, period, hyphen, apostrophe (single quote), slash, &, $, ?, =, |, :, +, space, comma, *, %, period, and parentheses.
$reqdCharSet = "[A-Za-z0-9]{1,}";  // At least one letter or number
if (ereg($illegalCharSet, $organization) || !ereg($reqdCharSet, $organization))
	{
	$_SESSION['MsgOrganization'] = "<span class='errorphp'><br>Please provide a valid organization.<br></span>";
	$_SESSION['phpinvalidflag'] = true; 
	};

// Seek to validate $email (courtesy: http://www.linuxjournal.com/article/9585)
function check_email_address($email) {
  // First, we check that there's one @ symbol, 
  // and that the lengths are right.
  if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
    // Email invalid because wrong number of characters 
    // in one section or wrong number of @ symbols.
	$_SESSION['MsgEmail'] = "<span class='errorphp'>Please provide a valid email address.<br></span>";
	$_SESSION['phpinvalidflag'] = true; 
  }
  // Split it into sections to make life easier
  $email_array = explode("@", $email);
  $local_array = explode(".", $email_array[0]);
  for ($i = 0; $i < sizeof($local_array); $i++) {
    if
(!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&
?'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$",
$local_array[$i])) {
	$_SESSION['MsgEmail'] = "<span class='errorphp'>Please provide a valid email address.<br></span>";
	$_SESSION['phpinvalidflag'] = true; 
    }
  }
  // Check if domain is IP. If not, 
  // it should be valid domain name
  if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) {
    $domain_array = explode(".", $email_array[1]);
    if (sizeof($domain_array) < 2) {
	$_SESSION['MsgEmail'] = "<span class='errorphp'>Please provide a valid email address.<br></span>"; // Not enough parts to domain
	$_SESSION['phpinvalidflag'] = true; 
    }
    for ($i = 0; $i < sizeof($domain_array); $i++) {
      if
(!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|
?([A-Za-z0-9]+))$",
$domain_array[$i])) {
	$_SESSION['MsgEmail'] = "<span class='errorphp'>Please provide a valid email address.<br></span>";
	$_SESSION['phpinvalidflag'] = true; 
      }
    }
  }
  return true;
}
check_email_address($email);

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<meta NAME="description" CONTENT="Request for business plan">
<title>Abridg&trade; | Request for Business Plan</title>
<link href="/abridg.css" rel="stylesheet" type="text/css">
</head>
<body>

<?php
//Now go back to the previous page (theplan.php) and show any PHP inline validation error messages if the $_SESSION['phpinvalidflag'] has been set to true. ... otherwise, proceed to send an email with the form results.
if ($_SESSION['phpinvalidflag'])
	{
	?>
	<script type='text/javascript' language='javascript'>window.location = '/theplan.php';</script>
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

/* The user's form submission has passed PHP form validation stage. */

// Unset the session variables that were used to prepopulate the form fields with prior values in theplan.php
unset($_SESSION['name']);
unset($_SESSION['organization']);
unset($_SESSION['email']);

// Send email
$message = "A user has completed the 'Request for Abridg Business Plan' form on abridg.com/theplan\n\n";
$message .= "Name: $name\n";
$message .= "Organization: $organization\n";
$message .= "Email: $email\n";
$to = 'paul@abridg.com, vmerlyn@themerlyngroup.com';
$subject = 'Request for Abridg Business Plan';
$headers = "From: $email"."\r\n" . 'X-Mailer: PHP/' . phpversion();
mail($to,$subject,$message,$headers);

// Display an on-screen confirmation.
?>
<p class='text' style='margin-left: auto; margin-right: auto; margin-top: 140px; text-align: center;'>Your submission was successful.</p>
</body>
</html>
<?php
ob_flush();
?>