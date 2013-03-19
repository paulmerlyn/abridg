<?php
/*
Upon logging in, $_SESSION['LoggedInOwnerID'] is set (this value never changes) and $_SESSION['OwnerID'] (which is changeable via the "My Stuff/Content Consumers" tab under the div-based popup widget/selectorpanel) is initially set to the same value. Also upon logging in, $_SESSION['LoggedInOwnerUsername'] is set (this value never changes) and $_SESSION['LoggedInOwnerUsername'] (which is changeable via the "My Friends/Content Providers" tab under the div-based popup widget/selectorpanel) is initially set to the same value. The user (i.e. the logged in account Owner) initially sees all files -- organized under 'New' (default), 'Videos', 'Images', and 'Documents' links -- to which he/she has assigned to his/her own associate account. (He/she may own additional media items, accessible via the Manager/Administrator utility, to which he/she hasn't assigned his/her own associate account -- perhaps so he/she can log in and show someone his/her stuff without clutter.)
	The user will initially see the OwnerLabel corresponding to $_SESSION['OwnerID'] in the masthead (e.g. "Paul & Sloan's Media") and will have access to a 'Log Out' button and a 'Manage' button (which connects him/her to the Administrator Upload, Assign, and Add Associate pages). The selector panel will initially show the "Content Consumers/My Stuff" tab, which list radio buttons for all his/her associates/content consumers (i.e. people whom he/she has granted access to his/her media items). The other tab (not initially selected) of the div-based popup widget/selectorpanel shows the logged in Owner's content producers (i.e. a radio button list of other Owners who have chosen to share content with the logged in Owner). The radio button values are AssociateID values. Once the Owner selects a radio button in the div-based popup widget/selectorpanel (whose HTML web forms have action script "widgetslave.php"), the index page reloads. When clicking a radio button under the Content Producer's tab, the masthead will change to reflect the appropriate OwnerLabel e.g. "Fran & Tim's Media".
    This rest of the page presents media content and is created dynamically by running through a MySQL query loop.
*/

ob_start();

// Start a session
session_start();

// Connect to database
$db = mysql_connect('localhost', 'paulme6_merlyn', 'fePhaCj64mkik')
or die('Could not connect: ' . mysql_error());
mysql_select_db('paulme6_abridg') or die('Could not select database: ' . mysql_error());
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<!-- Required to support border-radius css. See http://stackoverflow.com/questions/10784999/border-radius-not-working-in-ie9 -->
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"> 
<!-- Required for responsive Bootstrap-->
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Abridg&trade; | Connection 2.0&trade;</title>
<link href="/abridg-custom.css" rel="stylesheet" type="text/css">
<!--Link the CSS for the widget popup div -->
<link href="/widgetpopupdiv.css" rel="stylesheet" type="text/css">
<!--Link the CSS style  sheet that styles the tabbed popup panel -->
<link href="SpryTabbedPanels.css" rel="stylesheet" type="text/css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
<!--Link the Spry TabbedPanels JavaScript library-->
<script src="/scripts/SpryTabbedPanels.js" type="text/javascript"></script>
<script type="text/javascript" src="/jwplayer/jwplayer.js"></script>
<!--Link the JavaScript for the popup div -->
<script type='text/javascript' language="JavaScript" src="/scripts/widgetpopupdiv.js"></script>
<!-- Homegrown JS functions -->
<script type="text/javascript" src="/scripts/displayfunctions.js"></script>
<script type="text/javascript" src="/scripts/viewportinfo.js"></script>
<script type='text/javascript' language="JavaScript" src="/scripts/windowpops.js"></script>

<!-- Typica Login (a customized Twitter Bootstrap theme) stuff from wrapbootstrap.com. Note that I had more success by putting the jquery source and other references in the header rather than at the end as the theme designer suggested. -->
    <!-- Le javascript
    ================================================== -->
    <!-- Placed at the end of the document (no: see note above and http://stackoverflow.com/questions/2105327/should-jquery-code-go-in-header-or-footer) so the pages load faster -->
    <script src="js/bootstrap.js"></script>
    <script src="js/backstretch.min.js"></script>
    <script src="js/typica-login.js"></script>
	<script src="js/bootstrap-modal.js"></script>  
<!-- End of Typica Login stuff -->

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
  $("input[name='EntityType']").change(function(){
    $("#orgznlabel").toggle();
    $("#orgzninput").toggle();
    $("#orgznhint").toggle();
  });
  $("input:radio[name='EntityType']:nth(0)").click(function(){
	document.getElementById('acctnameexamples').innerHTML = 'Examples: &ldquo;Jane&rdquo;, &ldquo;The Doe Family&rdquo;<br />';
	$("#acctnameexamplesspan").removeClass('span2').addClass('span6');
  });
  $("input:radio[name='EntityType']:nth(1)").click(function(){
	document.getElementById('acctnameexamples').innerHTML = 'Example: &ldquo;XYZ&rdquo;<br />';
	$("#acctnameexamplesspan").removeClass('span6').addClass('span2');
  });
  /* For legacy browsers that don't support HTML5 placeholder attribute */
  if (!("placeholder" in document.createElement("input"))) {
	$(".short").click(function(){
		this.select();
	});
  }
  /* For legacy browsers that don't support the autofocus attribute of input elements. Great discussion of optimal techniques re page-loading here: http://diveintohtml5.info/forms.html */
 if (!("autofocus" in document.createElement("input"))) {
      $("#givefocuslegacy").focus();
  }	
});

/* For legacy browsers that don't support the autofocus attribute of input elements. */
 if (!("autofocus" in document.createElement("input"))) {
      document.getElementById("givefocuslegacy").focus();
  }	

function checkEmailOnly(theEmailID)
{ 
	var re = /^\s*[\w\-\+_]+(\.[\w\-\+_]+)*\@[\w\-\+_]+\.[\w\-\+_]+(\.[\w\-\+_]+)*\s*$/;
	var theEmailID
	var emailValue = document.getElementById(theEmailID).value;
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

function checkPasswordOnly(thePasswordID)
{
// Validate Password field in 'Sign Up' form.
document.getElementById("PasswordError").style.display = "none";
var thePasswordID;
var passwordValue = document.getElementById(thePasswordID).value;
var passwordLength = passwordValue.length;
illegalCharSet = /[^A-Za-z0-9]+/; // Exclude everything except A-Z, a-z, 0-9.
reqdCharSet = /^(?=.*[A-Za-z])(?=.*[0-9])(?!.*[^A-Za-z0-9])(?!.*\s).{8,20}$/; // Reg exp for a password that must contain at least 8 characters and have at least one number and at least one alphabet. Courtesy: http://www.tek-tips.com/viewthread.cfm?qid=1508574
if (illegalCharSet.test(passwordValue) || !reqdCharSet.test(passwordValue) ||  !(passwordLength>=8))
	{
	document.getElementById("PasswordError").style.display = "inline";
	return false;
	} 
else
	{
	document.getElementById("PasswordError").style.display = "none";
	return true;
	}
}

function checkForm(theEmailID, thePasswordID) 
{
if (!checkEmailOnly(theEmailID) || !checkPasswordOnly(thePasswordID))
	{
	return false; // return false if any one of the individual field validation functions returned a false ...
	}
else 
	{
	return true; // ... otherwise, all individual field validations must have returned a true, so let checkForm() return true.
	}
} // End of checkForm()
</script>
</head>

<body>
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

<!-- Calculate the coordinates to position the top-left corner of the widget (aka selector panel) i.e. towards the top-right corner of the screen, off-set for the widget's width and down a little from the top so it's out of the way. -->
<script type="text/javascript">
var avWd, widgetOffsetX, widgetOffsetY;
avWd = screen.availWidth; // Total available screen width
widgetOffsetX = avWd - 300;
widgetOffsetY = 120;
</script>

<?php
if  ($_SESSION['Authenticated'] != 'true') // We only need to retain this code block for use on the login screen i.e. don't show it if the user is already authenticated.
	{
?>
	<!-- Start of the 'Sign Up for Abridg' modal box, courtesy http://www.w3resource.com/twitter-bootstrap/modals-tutorial.php#firstexample -->
<div id="SignUpModal" class="modal hide fade in">  

<div class="modal-body">  
<a class="close" data-dismiss="modal" onClick="document.getElementById('SignUpModal').style.display = 'none';">&times;</a><!-- The onclick event handler is ordinarily redundant, but it's necessary to take care of the special case where this SignUpModal was displayed via a click on the Regiser button in passwordreminder.php. Without the onclick event, the user isn't able to hide (undisplay) the modal. -->
<legend>Register for a free <span class="red">Abridg</span> account</legend>

<form method='post' name='SignUp' id='SignUp' class="form" action='/scripts/signup_slave.php'>
<input type='hidden' name='VPwidthSignUp'>
<input type='hidden' name='VPheightSignUp'>

<div class="row">
<span class="span4 offset1">
<label style="display: inline;">Private Individual&nbsp;&nbsp;&nbsp;</label>
<input type='radio' name='EntityType' value='individual' style="display: inline;" checked>
&nbsp;&nbsp;&nbsp;
<input type="radio" name="EntityType" value="organization" style="display: inline;">
<label style="display: inline;">Organization</label>
</span>
</div>

<br>
<div class="row">
<span class="span2">
<input class="short" style="display: inline;" type="text" name="OwnerFirstName" maxlength="40" value="<?php if (isset($_SESSION['OwnerFirstName'])) echo $_SESSION['OwnerFirstName']; else echo 'First Name'; ?>" autofocus>
<?php if ($_SESSION['MsgOwnerFirstName'] != null) { echo $_SESSION['MsgOwnerFirstName']; $_SESSION['MsgOwnerFirstName']=null; } ?>
</span>
<span class="span1 offset1" style="display: block;">
<input class="short" style="display: inline;" type="text" name="OwnerLastName" maxlength="40" value="<?php if (isset($_SESSION['OwnerLastName'])) echo $_SESSION['OwnerLastName']; else echo 'Last Name'; ?>">
<?php if ($_SESSION['MsgOwnerLastName'] != null) { echo $_SESSION['MsgOwnerLastName']; $_SESSION['MsgOwnerLastName']=null; } ?>
</span>
</div>

<div class="row">
<span class="span2">
<input class="short" type="text" name="OwnerLabel" maxlength="40" size="30" value="<?php if (isset($_SESSION['OwnerLabel'])) echo $_SESSION['OwnerLabel']; else echo 'Account Name'; ?>">
</span>
<span class="span1 offset1">
<input class="short" type='text' name='OwnerOrganization' id="orgzninput" style="display: none;" maxlength='40' size='30' value="<?php if (isset($_SESSION['OwnerOrganization'])) echo $_SESSION['OwnerOrganization']; else echo 'Organization Name'; ?>">
</span>
</div>

<div class="row">
<span class="span6" id="acctnameexamplesspan">
<div class="muted" id="acctnameexamples" style="vertical-align: top;">Examples: &ldquo;Jane&rdquo;, &ldquo;The Doe Family&rdquo;, etc.<br /></div>
<?php if ($_SESSION['MsgOwnerLabel'] != null) { echo $_SESSION['MsgOwnerLabel']; $_SESSION['MsgOwnerLabel']=null; } ?>
</span>
<span class="span3 offset1">
<div id="orgznhint" style="display: none;" class='muted'>Example: &ldquo;XYZ Corp.&rdquo;<br /></div>
<?php if ($_SESSION['MsgOwnerOrganization'] != null) { echo $_SESSION['MsgOwnerOrganization']; $_SESSION['MsgOwnerOrganization']=null; } ?>
</span>
</div>

<br>
<div class="row">
<span class="span2">
<input class="short" type="email"  name="SignUpEmail" id="SignUpEmail" maxlength="40" size="30" value="<?php if (isset($_SESSION['SignUpEmail'])) echo $_SESSION['SignUpEmail']; else echo 'Email (Username)'; ?>" onBlur="return checkEmailOnly('SignUpEmail');">
</span>
<span class="span1 offset1" >
<input class="short" type="password" name="SignUpPassword" id="SignUpPassword" maxlength="30"  size="30" value="<?php if (isset($_SESSION['SignUpPassword'])) echo $_SESSION['SignUpPassword']; else echo 'Password'; ?>" onBlur="return checkPasswordOnly('SignUpPassword');">
</span>
</div>

<div class="row">
<span class="span2">
<div class="error" id="EmailError"><br>Your email address is invalid. Please try again.<br></div>
<?php if ($_SESSION['MsgSignUpEmail'] != null) { echo  $_SESSION['MsgSignUpEmail']; $_SESSION['MsgSignUpEmail']=null; } ?>
</span>
<span class="span3 offset1" >
<div class="muted">Include at least 1 number. 8-character min.</div>
<div class="error" id="PasswordError">You have chosen an invalid password. Please try again.<br></div>
<?php if ($_SESSION['MsgSignUpPassword'] != null) { echo $_SESSION['MsgSignUpPassword']; $_SESSION['MsgSignUpPassword']=null; }; ?>
</span>
</div>

<br>
<input type="submit" name="SignUpOwner" value="Register Me" class="btn btn-success pull-right" onclick="return checkForm(SignUpEmail, SignUpPassword);">
</form>
</div>  
</div> 

<script type="text/javascript">
// Dynamically assign values to the (hidden) VPwidth and VPheight fields in the id='SignUp' form.
document.forms['SignUp'].elements['VPwidthSignUp'].value = viewportwidth;
document.forms['SignUp'].elements['VPheightSignUp'].value = viewportheight;
</script>

<!-- End of modal 'Sign Up for Abridg' box -->
<?php
	}
?>

<!-- Start of the 'HelpModal' modal box, courtesy http://www.w3resource.com/twitter-bootstrap/modals-tutorial.php#firstexample -->
<div id="HelpModal" class="modal hide fade in wide">  

<div class="modal-body tall">  
<a class="close" data-dismiss="modal" onClick="document.getElementById('HelpModal').style.display = 'none';">&times;</a>  
<legend>The 30-Second Guide to <span class="red">Abridg</span></legend>

<div class="row">
<span class="span6">
Abridg lets you share anything with anyone. It&rsquo;s uniquely personal and private. This quick guide is all you need to get started.</span>
</div>

<div class="row">
<span class="span2">
<img src="/images/widgetpic3.png" alt="Friends Widget">
</span>
<span class="span4">
The 2-tab widget is the main control in the Media Gallery. Click <span style="color: #F04040">&lsquo;My Stuff&rsquo;</span> to view <em>your own</em> media. View stuff from your friends via the <span style="color: #F04040">&lsquo;Friends&rsquo; Stuff&rsquo;</span> tab. The bell <i class="icon-bell"></i> means a friend has new media for you. You can show or hide the widget via the <span style="color: #F04040;">Friends</span> button <img style="position: relative; top: 0px;" src="/images/friends-icon_new.jpg" alt="Friends Button" width="33" height="25">.
</span>
</div>

<div class="row" style="margin-top: 10px;">
<span class="span2">
<img src="/images/mini-icons-image_new.png" alt="Abridg Help, Director and Logout icons" width="139" height="46">
</span>
<span class="span4">
Review this guide anytime via the <span style="color: #F04040">Help</span> icon. Click the director&rsquo;s chair to enter <span style="color: #F04040">Abridg Director</span>, where you can upload and share anything. Log out via the <span style="color: #F04040">Door</span>.
</span>
</div>

<div class="row" style="margin-top: 10px;">
<span class="span2">
<img src="images/assignscreenicons_new.png">
</span>
<span class="span4">
Inside Abridg Director, a simple menu lets you do everything. First <span style="color: #F04040">Upload</span> media items and <span style="color: #F04040">Add</span> friends. Then <span style="color: #F04040">Assign</span> items to friends. You can also <span style="color: #F04040">Manage</span> account settings &mdash; for example, change your password or control when alerts get sent to friends after you&rsquo;ve assigned them items.
</span>
</div>

</div>  
</div> 
<!-- End of modal 'HelpModal for Abridg' box -->

<?php
/* Create short variable names */
$OwnerUsername = $_POST['OwnerUsername'];
$OwnerPassword = $_POST['OwnerPassword']; // Note that the short variable name and the POST'ed form element's name are given different names.


// If the $_SESSION['RegisteredOwnerViaAlert'] flag has been set to 'true', that means that index.php has just been reloaded after having initially cycled via a registered user's click inside an email alert that he/she received. In this case, we want to replace the $OwnerUsername and $OwnerPassword values that were assigned just above to POST-ed form submissions (actually, nothing was POST'ed in this case anyway), using instead the session variables that were defined below in index.php to act as pseudo-POST-ed form submissions. In this way, the pseudo-POST-ed values overwrite any conventionally POST-ed value.
if ($_SESSION['RegisteredOwnerViaAlert'] == 'true')
	{
	$OwnerUsername = $_SESSION['RegisteredOwnerUsernameViaAlert'];
	$OwnerPassword = $_SESSION['RegisteredOwnerPasswordViaAlert']; 
	unset($_SESSION['RegisteredOwnerViaAlert']); // unset this flag now.
	}
	
// If the $_SESSION['NonregisteredOwnerViaAlert'] flag has been set to 'true', that means that index.php has just been loaded by nonregisteredownerlogin_slave.php (a slave script of the nonregisteredownerloginform HTML form in index.php). (That form itself got presented to the user because he/she is an associate of a registered Owner but who is himself/herself a nonregistered Owner and who clicked inside an email alert that he/she following an assignment by a registered Owner.) In this case, we want to replace the $OwnerUsername and $OwnerPassword values that were assigned just above to POST-ed form submissions (actually, nothing was POST'ed in this case anyway), using instead the session variables that were defined in nonregisteredownerlogin_slave.php to act as pseudo-POST-ed form submissions. In this way, the pseudo-POST-ed values overwrite any conventionally POST-ed value.
if ($_SESSION['NonregisteredOwnerViaAlert'] == 'true')
	{
	$OwnerUsername = $_SESSION['NonregisteredOwnerUsernameViaAlert'];
	$OwnerPassword = $_SESSION['NonregisteredOwnerPasswordViaAlert']; 
	unset($_SESSION['NonregisteredOwnerViaAlert']); // unset this flag now.
	}
	
// If the user clicked a Register (i.e. sign up for an Abridg account) button in the footer on passwordreminder.php, then the submit button of name RegisterRequestViaPasswordReminder will be post'ed. I convert this into a session variable $_SESSION['RegisterRequestViaPasswordReminder'], which I use later below to control the display of the SignUpModal via Javascript.
if (isset($_POST['RegisterRequestViaPasswordReminder']))
	{
	$_SESSION['RegisterRequestViaPasswordReminder'] = 'true';
	unset($_POST['RegisterRequestViaPasswordReminder']);
	}

// Continue assigning other short variable names
$VPwidth = $_POST['VPwidth']; // Hidden form field (snuk in with the account holder "authenticationform" log in form) to obtain from browser's viewport dimensions (with help from viewportinfo.js).
$VPheight = $_POST['VPheight'];
$ContentConsumer = $_POST['ContentConsumer']; // Radio buttons (for content consumers) in the 2-tab selector panel (aka widget) thru which the logged in Owner can select a content consumer or a content producer vis a vis viewing content.
$ContentProducer = $_POST['ContentProducer']; // Radio buttons (for content producers) in the 2-tab selector panel (aka widget) thru which the logged in Owner can select a content consumer or a content producer vis a vis viewing content.
$galleryview = $_POST['galleryview']; // Form button submission for the 'Media Gallery' "link" (disguised as a form) in addassociate.php, editassociate.php, editmedia.php, upload.php, assign_slave.php, editmedia_slave.php, and upload_slave.php

// Sanitize account holder login submissions
$OwnerUsername = htmlspecialchars($OwnerUsername);
$OwnerPassword = htmlspecialchars($OwnerPassword);
if (!get_magic_quotes_gpc())
	{
	$OwnerUsername = mysql_real_escape_string($OwnerUsername); // More secure than addslashes according to: http://shiflett.org/blog/2006/jan/addslashes-versus-mysql-real-escape-string. Note that a MySQL connection is required (otherwise, the connection has no other purpose within addassociate.php) before using mysql_real_escape_string() otherwise an error of level E_WARNING is generated.
	$OwnerPassword = mysql_real_escape_string($OwnerPassword);
	}

// If user isn't already authenticated (i.e. $_SESSION['Authenticated'] is not 'true'), then check to see whether values have been POST'ed for OwnerUsername and OwnerPassword. If so, examine whether they are a legitimate login pair.
if (!empty($OwnerUsername) && !empty($OwnerPassword) && $_SESSION['Authenticated'] != 'true')
	{
	// Set session variables to retain (remember) dimensions of the viewport. But don't set them if they were already set in signup_slave.php or nonregisteredownerlogin_slave.php.
	if (!isset($_SESSION['VPwidth'])) $_SESSION['VPwidth'] = $VPwidth;
	if (!isset($_SESSION['VPheight'])) $_SESSION['VPheight'] = $VPheight;

	$query = "SELECT *, count(*) AS TheCount FROM owners_table WHERE OwnerUsername = '".$OwnerUsername."' AND OwnerPassword = '".$OwnerPassword."'";
	$result = mysql_query($query) or die('Query (select of count(*) for a matching OwnerUsername and OwnerPassword from owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	$row = mysql_fetch_assoc($result); 
	$count = $row['TheCount']; // $row['TheCount'] in $row array holds a count value of 0 (no match) or 1 if the $OwnerUsername and $OwnerPassword did match an OwnerUsername and OwnerPassword value in the owners_table.
	// The $OwnerUsername and $OwnerPassword typed by the user do match respective values in the owners_table.
	if ($count == 1)
		{
		$_SESSION['Authenticated'] = 'true';
		$_SESSION['KeepWidgetOpen'] = 'true'; // This session variable is initialized to false here, thereby ensuring that the selector panel/widget will be displayed by default initially on the main screen). Its value is reset inside widgetslave.php, the form processor for the 2-tab selector panel/widget. The display of the widget itself within index.php occurs via the <div id="popup1"> div.
		$_SESSION['TabNumber'] = 0; // $_SESSION['TabNumber'] gets used when invoking the Spry Tabbed Panel initialization javascript code below to set the default (i.e. displayed) tab. Initialize it to whichever tab number (0 or 1) is first displayed when the tabbed selector panel is invoked.
		$_SESSION['LoggedInOwnerID'] = $row['OwnerID']; // Once set, the value of $_SESSION['LoggedInOwnerID'] won't change all the while the Owner remains logged in.
		$_SESSION['OwnerID'] = $_SESSION['LoggedInOwnerID']; // Initially set $_SESSION['OwnerID'] to the logged in Owner's OwnerID. Its value will subsequently change (via "widgetslave.php") if/when the logged in user selects content producers (i.e. different owners) from the Content Producers tab within the div-based popup widget/selectorpanel.
	// Also store details of the account owner's OwnerLabel for use within the bar that goes across the top of the screen when the user has first logged in (and before he/she has selected any any radio button, etc. to view instead the media content assigned to him/her from other content producers.
		$_SESSION['OwnerLabel'] = $row['OwnerLabel'];
		$_SESSION['LoggedInOwnerLabel'] = $row['OwnerLabel']; // Keep a static copy of the OwnerLabel of the logged in user for use in the "... powered by Abridg" banner text, which gets reset by widgetslave.php.
	// And store details of the logged in owner's OwnerUsername, for use in determining (from a loop through the associates_table) who are content producers who distribute content to the Owner.
		$_SESSION['LoggedInOwnerUsername'] = $row['OwnerUsername'];  // Once set, the value of $_SESSION['LoggedInOwnerUsername'] won't change all the while the Owner remains logged in.
		$_SESSION['OwnerUsername'] = $_SESSION['LoggedInOwnerUsername']; // Initially set $_SESSION['OwnerUsername'] to the logged in Owner's OwnerUsername. Its value will subsequently change (via "widgetslave.php") if/when the logged in user selects content producers (i.e. different owners) from the Content Producers tab within the div-based popup widget/selectorpanel.
		$_SESSION['OwnerDofAdmission'] = $row['OwnerDofAdmission'];
		$_SESSION['ShowWelcomeMsg'] = $row['ShowWelcomeMsg']; // Used to control the show/hide of div = 'userguidebox'
		$_SESSION['WelcomeMsgShown'] = 'false'; // Initialize (it'll get changed to 'true' below after 'userguidebox' is shown).
		
		// Update the owners_table with the present date-time of this successful log in...
		$_SESSION['PreviousLogIn'] = $row['OwnerLastLogin']; // But first store the value of the previous login (as was recorded and placed in the OwnerLastLogin column of owners_table) and save it as the initial value of the session variable $_SESSION['PreviousLogIn'] for use in figuring out which uploaded items are "new" to this Owner according to their UploadDate date-time. The value of this session variable will be changed (via "widgetslave.php") when the logged in user clicks on the radio buttons under the "My Stuff/Content Consumers" tab in the div-based popup widget/selectorpanel. The vaule is a datetime such as "2011-09-18 16:07:39"
		$_SESSION['PreviousLogInOfLoggedInOwner'] = $row['OwnerLastLogin']; // Store the Previous Log In of the logged in owner as a separate session variable because his/her OwnerLastLogin column is about to be overwritten/updated in owners_table. 
		$query1 = "UPDATE owners_table SET OwnerLastLogin = NOW() WHERE OwnerID = ".$row['OwnerID'];
		$result1 = mysql_query($query1) or die('Query (update OwnerLastLogin in owners_table) failed: ' . mysql_error().' and the database query string was: '.$query1);
		
		// Initialize $_SESSION['AssociateID'] to the associate representing the logged in owner himself/herself so that, immediately upon logging in, this owner will see a view of whatever media items (if any) that he/she has assigned to himself/herself.
		$query = "SELECT AssociateID FROM associates_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID']." AND OwnerUsername = '".$_SESSION['LoggedInOwnerUsername']."'";
		$result = mysql_query($query) or die('Query (select AssociateID from associates_table corresponding to owner himself/herself) failed: ' . mysql_error().' and the database query string was: '.$query);
		$row = mysql_fetch_assoc($result);
		$_SESSION['AssociateID'] = $row['AssociateID'];
		$_SESSION['LoggedInOwnersOwnAssociate'] = $row['AssociateID'];

		// In the list of the Owner's content consumers (i.e. his/her friends -- the people with whom the Owner wishes to provide content), we'll also initialize that list so that the initially preselected radio button (content consumer or friend) is the the associate corresponding to the Owner himself/herself. Obtain the corresponding AssociateID from the following query and store it in $_SESSION['InitialAssociateIDcc'].
		$_SESSION['InitialAssociateIDcc'] = $row['AssociateID']; // $_SESSION['InitiaAssociateIDcc'] stores the initial AssociateID of a content consumer of the Owner, whose OwnerID is stored in $_SESSION['LoggedInOwnerID']
		 
		// In the list of the Owner's content producers (i.e. the people who provide content to the Owner), we'll initialize that list so that the initially preselected radio button (content producer) is the one whose OwnerLabel is alphabetically first. Obtain the corresponding AssociateID from the following query and store it in $_SESSION['InitialAssociateIDcp'].
		$query = "SELECT owners_table.OwnerLabel, associates_table.AssociateID FROM associates_table, owners_table WHERE associates_table.OwnerUsername = '".$_SESSION['LoggedInOwnerUsername']."' AND associates_table.OwnerID != ".$_SESSION['LoggedInOwnerID']." AND associates_table.OwnerID = owners_table.OwnerID ORDER BY owners_table.OwnerLabel ASC LIMIT 1";
		$result = mysql_query($query) or die('Query (select first alphabetical AssociateID, AssociateName from associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
		$row = mysql_fetch_assoc($result);
		$_SESSION['InitialAssociateIDcp'] = $row['AssociateID']; // $_SESSION['InitiaAssociateIDcp'] stores the initial AssociateID of a content producer to the Owner, whose OwnerID is stored in $_SESSION['LoggedInOwnerID']

		// Since, upon logging in, the Owner will immediately land on his/her own "My Gallery Favorites" page, he/she has essentially had the chance to view the associate associated with himself/herself as an Owner. For that reason, we should update AssociateLastLogin in associates_table to the present datetime.
		$query2 = "UPDATE associates_table SET AssociateLastLogin = NOW() WHERE AssociateID = ".$_SESSION['AssociateID'];
		$result2 = mysql_query($query2) or die('Query (update OwnerLastLogin in owners_table) failed: ' . mysql_error().' and the database query string was: '.$query2);
		
		// Determine whether the logged in Owner is fully registered (i.e. has a completed row in owners_table) or semiregistered (i.e. has provided OwnerUsername and OwnerPassword, but hasn't yet provided OwnerLabel, OwnerFirstName, and OwnerLastName). Then set $_SESSION['RegOwnerStatus'] accordingly.
		$query = "SELECT OwnerLabel, OwnerFirstName, OwnerLastName FROM owners_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
		$result = mysql_query($query) or die('Query (select OwnerLabel, OwnerFirstName, OwnerLastName from owners_table ) failed: ' . mysql_error().' and the database query string was: '.$query);
		$row = mysql_fetch_assoc($result);
		if (empty($row['OwnerLabel']) || empty($row['OwnerFirstName']) || empty($row['OwnerLastName'])) $_SESSION['RegOwnerStatus'] = 'semi';
		else $_SESSION['RegOwnerStatus'] = 'full';
		}
	else
		{
		unset($_SESSION['Authenticated']);
		// Authentication of this login attempt has failed. Redirect user to passwordreminder.php
		?>
		<script type='text/javascript' language='javascript'>window.location = '/passwordreminder.php';</script>
		<noscript>
		<?php
		if (isset($_SERVER['HTTP_REFERER']))
			header("Location: /passwordreminder.php");
		?>
		</noscript>
		<?php
		};
	}

if  ($_SESSION['Authenticated'] != 'true') /* Determine whether the browser's request for index.php appended a query string (which we'll refer to henceforth as crypto-string to distinguish it from the query string term that I've used in reference to linkshare links [e.g. http://www.abridg.com/q4cdp9nlk32ae], which aren't technically query strings in that they aren't preceded by a "?" after the filename). If we do find a crypto-string, that means the user has clicked a link in an alert email message. */
	{
	$cryptoString = $_SERVER['QUERY_STRING'];
	if (strlen($cryptoString) > 0)
		{
		// Legitimate crypto-strings have format specified in alertgenerator.php, where they are defined i.e. III-XXXXXXXX for non-registered Owners, and III-XXXXYYYY for registered Owners. Here, III is the non-hashed OwnerID of the associate who is trying to view content via the link in the Alert email; XXXX(XXXX) is the first four (eight) characters of a sha1() hash upon the associate's OwnerUsername; and YYYY is the first four characters of a sha1() hash upon the associate's OwnerPassword. (Note: in both cases, to thwart easy use of a rainbow table, OwnerUsername (for associates who are registered or non-registered) and OwnerPassword (pertains to associates who are registered only) are salted by prepending the sum of 1 + OwnerID to each before running them through the hash algorithm.) Obtain the OwnerID:
		$cryptoStringArray = explode('-', $cryptoString);
		$theID = $cryptoStringArray[0];
		// Now look up the Owner corresponding to this ID in owners_table. If the OwnerPassword column is blank, we know this isn't a registered Owner. (It may either be a non-registered Owner or the $cryptoString is a fake.)
		$query = "SELECT OwnerPassword, OwnerUsername, count(*) AS TheCount FROM owners_table WHERE OwnerID = ".$theID;
		$result = mysql_query($query) or die('Query (select of count * et al for a matching OwnerID from owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
		$row = mysql_fetch_assoc($result);
		$count = $row['TheCount']; // $row['TheCount'] in $row array holds a count value of 0 (no match) or 1 if the $theID did match an OwnerID value in the owners_table.
		if ($count == 1) // An Owner does exist with an OwnerID that matches the one supplied in the crypto-string.
			{
			if (empty($row['OwnerPassword'])) // The $cryptoString may correspond to a non-registered Owner (or be a fake).
				{
				// Presuming $cryptoString is legitimate, the eight characters after the hyphen will be the first eight characters of a sha1() version of the salted OwnerUsername.
				$theOwnerUsernameCandidate = $cryptoStringArray[1];
				// Salt the actual OwnerUsername obtained from the query above, get its sha1() hash, and compare with $theOwnerUsernameCandidate
				$salt = 1 + $theID;
				if ($theOwnerUsernameCandidate == substr(sha1($salt.$row['OwnerUsername']),0, 8))
					{
?>
				    <div class="navbar navbar-fixed-top">
				      <div class="navbar-inner">
				        <div class="container">
				          <!-- DO NOT REMOVE this anchor tag or enclosed spans! -->
						  <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
				            <span class="icon-bar"></span>
				            <span class="icon-bar"></span>
				            <span class="icon-bar"></span>
				          </a>
				          <a class="brand" href="index.php"><img src="images/AbridgLoginLogo.png" alt="Abridg Login Logo"></a>
				        </div>
				      </div>
				    </div>

<?php
					// Allow non-registered user to provide an OwnerPassword so that he/she can go from being a non-registered Owner to a registered Owner.
?>
					<div class="container">

				        <div id="login-wraper" style="height: 350px;">
						
						<form method="post" name="nonregisteredownerloginform" action="/scripts/nonregisteredownerlogin_slave.php" class="form login-form">
						<!-- Note how the hidden viewport width and height form fields have associated values attributes that are set by linked viewportinfo.js script.  -->
						<input type="hidden" name="VPwidthNonregOwner">
						<input type="hidden" name="VPheightNonregOwner">

		                <legend>Just one more step...</legend>
            
		                <div class="body">
        		            <label>Email</label>
                		    	<input type="email" autocapitalize="off" name="nonregOwnerUsername" id="nonregOwnerUsername" value="<?=$row['OwnerUsername']; ?>" onBlur="return checkEmailOnly('nonregOwnerUsername');" autofocus>
                    
								<div class="muted">Please provide an email address</div>
								<div class='error' id='EmailError'>Your email address is invalid. Please try again.<br></div><?php if ($_SESSION['MsgNonRegOwnerUsername'] != null) { echo  $_SESSION['MsgNonRegOwnerUsername']; $_SESSION['MsgNonRegOwnerUsername']=null; } ?><!-- In addition to prepopulating the OwnerUsername field with the non-registered Owner's OwnerUsername from owners_table, we also pass $theID (the OwnerID from owners_table) as a hidden field for identification purposes b/c the user may choose to replace (i.e. write over) this prepopulated email address with a different one. -->
								<input type="hidden" name="nonregOwnerID" value="<?=$theID; ?>">
								<!-- And we'll pass the crypto-string too b/c that'll be needed if there's a PHP form validation error detected by nonregisteredownerlogin_slave.php such that we need to go back to index.php to display that message on the 'nonregisteredownerloginform' form. -->
								<input type="hidden" name="cryptoString" value="<?=$cryptoString; ?>">
		                    <label>Password</label>
        		    	        <input type="password" name="nonregOwnerPassword" id="nonregOwnerPassword" onBlur="return checkPasswordOnly('nonregOwnerPassword');">
								<div class="muted">Choose a password</div>
								<div class='error' id='PasswordError'>Include at least one number. 8-character minimum.<br></div><?php if ($_SESSION['MsgNonRegOwnerPassword'] != null) { echo $_SESSION['MsgNonRegOwnerPassword']; $_SESSION['MsgNonRegOwnerPassword']=null; } ?>
								
			                <div class="footer">
								<button type="submit" class="btn btn-success" onclick="return checkForm('nonregOwnerUsername', 'nonregOwnerPassword');">Continue</button>
               				</div>
            
						</div>
			            </form>
						
		                </div>

					</div>
</body>
</html>
			


<?php
					exit;
					}
				else
					{
					// The failure of $theOwnerUsernameCandidate to match an actual OwnerUsername here means the crypto-string is a fake. Take no action.
					}
				}
			else  // The $row['OwnerPassword'] column isn't blank. $cryptoString may correspond to a registered Owner (or be a fake).
				{
				// Presuming $cryptoString is legitimate, the first four characters after the hyphen will be the first four characters of a sha1() version of the salted OwnerUsername, and the next four characters will be the first four characters of a sha1() version of the salted OwnerPassword.
				$theOwnerUsernameCandidate = substr($cryptoStringArray[1], 0, 4);
				$theOwnerPasswordCandidate = substr($cryptoStringArray[1], 4, 4);
				$salt = 1 + $theID;
				if ($theOwnerUsernameCandidate == substr(sha1($salt.$row['OwnerUsername']),0, 4) && $theOwnerPasswordCandidate == substr(sha1($salt.$row['OwnerPassword']),0, 4))
					{
					// Set $row['OwnerUsername'] and $row['OwnerPassword'] to session variables then reload index.php.  These session variables will then act as pseudo-POST-ed form submissions, as if the registered Owner had manually submitted them via a conventional login form. They'll then be processed by the rest of the code in index.php.
					$_SESSION['RegisteredOwnerUsernameViaAlert'] = $row['OwnerUsername'];
					$_SESSION['RegisteredOwnerPasswordViaAlert'] = $row['OwnerPassword'];
					$_SESSION['RegisteredOwnerViaAlert'] = 'true'; // This flag is set and used elsewhere in index.php to distinguish whether to assign $OwnerUsername and $OwnerPassword to true POST-ed form submissions or whether to assign them to these pseudo-POST session variables.
					
					// Reload index.php (with the $_SESSION['RegisteredOwnerUsernameViaAlert'] and $_SESSION['RegisteredOwnerPasswordViaAlert'] serving as pseudo-POST-ed form submissions)
					header("Location: /index.php");
					ob_flush();
					exit;
					}
				else
					{
					// The failure of $theOwnerUsernameCandidate and $theOwnerPasswordCandidate to match an actual OwnerUsername and OwnerPassword here means the crypto-string is a fake. Take no action.
					}
				}
			}
		else // The failure of $cryptoString to produce a $count == 1 proves the crypto-string is a fake.
			{
			// Take no action
			}
		}
	
	};

if  ($_SESSION['Authenticated'] == 'true')
	{
?>
<!-- Change the background image from the women on the bench that accompanies the login screens to the grey ligthly patterned background. -->
<script type="text/javascript">$("body").css('background-image','url(img/whiteBG.jpg)');</script>
<?php

	/* Create text to be shown across the top of the page if the user has already authenticated him/herself */
	// Derive a suitable nomen for inclusion as text inside the colored masthead bar across the top of the screen when an account user is logged in e.g. If the Owner is "Jack Jones" then the text may say "Jack's Media".
	if ($_SESSION['VPwidth'] > 800)  // Include "powered by Abridg(tm)" for larger viewport widths only
		{
		if (!empty($_SESSION['OwnerLabel'])) $titletext = $_SESSION['OwnerLabel'].'&rsquo;s Media | <span style="font-style: italic;">powered by Abridg</span><span style="font-size: 18px; vertical-align: super;">&trade;</span>';
		else $titletext = 'My Media | powered by Abridg&trade;';
		}
	else
		{
		if (!empty($_SESSION['OwnerLabel'])) $titletext = $_SESSION['OwnerLabel'].'&rsquo;s Media';
		else $titletext = 'My Media | powered by Abridg&trade;';
		}
?>
	<div class="navbar navbar-fixed-top">
		<div class="navbar-inner">
			<div class="container">
				<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse" style="display: none;">
					<span class="icon-bar"><!-- Clicking the Friends icon shows/hides the widget/selector panel. -->
				<a class="pointer" onClick="showHidePopupDiv('popup1'); return false;"><img src="images/friends-icon_new.jpg" alt="friends icon" border="0" style="position: absolute; top: 1px; right: 185px;" title="Show/Hide Friends"></a></span>
					<span class="icon-bar"><form method="post" action="/assign.php">
				    <input type="image" src="/images/director-icon_new.jpg" style="position: absolute; right: 140px; top: 4px;" value="Manage" title="Abridg Director">
				</form></span>
					<span class="icon-bar"><a class="pointer" onClick="$('#HelpModal').modal({show: true}); return false;"><img src="images/help-icon2_new.png" alt="help icon" border="0" style="position: absolute; top: 0px; right: 70px;" title="Help!"></a></span>
					<span class="icon-bar"><form method="post" action="/scripts/logout.php" style="">
					<input type="image" src="images/logout-icon_new.jpg" style="position: absolute; right: 30px; top: 2px;" onclick="document.getElementById('HelpModal').style.display = 'none';" value="Log Out" title="Log Out">
					<input type="hidden" name="LoggedOut" id="LoggedOut" value="true">
				</form></span>
				</a>
				<div class="brand"><span class="brand-text"><?=$titletext; ?></span></div>
			</div>
		</div>
	</div>
<?php

	/* When clicked in addassociate.php, editassociate.php, editmedia.php, upload.php, assign_slave.php, editmedia_slave.php, and upload_slave.php, the 'Media Gallery' link (actually, a form submission disguised as a link) takes the Administrator/Owner back to the index.php page. Since, for an already logged in Owner, index.php needs to know a value for $_SESSION['AssociateID'] (which may have been unset within one of those master and/or slave scripts), we need to detect any submissions of the "Media Gallery" link/form and set a value for $_SESSION['AssociateID']. We set it to whatever value it had before it was unset by one of those scripts. We can infer that value by first determining the current value of $_SESSION['TabNumber'] (which tells us which tab is currently displayed -- or about to be displayed when the widget is next shown) and then set $_SESSION['AssociateID'] to the current value of $_SESSION['InitialAssociateIDcc'] (if the Content Consumer tab is currently displayed or pending) or to the current value of $_SESSION['InitialAssociateIDcp'] (if the Content Producer tab is currently displayed or pending). Those session variables are continually updated by widgetslave.php every time the widget is clicked. */
	if (isset($galleryview))
		{
		unset($galleryview);
		if ($_SESSION['TabNumber'] == 0) // Content Consumers tab is active (or pending if the widget isn't currently displayed at all)
			{
			$_SESSION['AssociateID'] = $_SESSION['InitialAssociateIDcc'];
			}
		else if ($_SESSION['TabNumber'] == 1) // Content Producers tab is active (or pending if the widget isn't currently displayed at all)
			{
			$_SESSION['AssociateID'] = $_SESSION['InitialAssociateIDcp'];
			}
		}

	/* Count to see whether the logged in Owner has at least one Content Provider. If he/she doesn't, we don't want to submit the "contentproducersform" on click of the "Friends' Stuff" (aka Content Producers) tab. Ordinarily, when the Owner has at least one such content producer, the CPs show under that tab in a radio button list, with one such button preselected. Submission of the form on click of the tab ensures that action script widgetslave.php gets called and the index.php page gets refreshed according to the preselected CP. But when there are no such CPs, there will be no radio buttons and no value of a ContentProducer radio button, which would otherwise cause widgetslave.php to pass control back to index.php without displaying any content for the Content Producer tab. Hence the need to count the Owner's CPs and use that count to dynamically omit the onclick form submission on the "Content Producers" tab.  */
	$query = "SELECT COUNT(*) AS CPcount FROM associates_table WHERE OwnerUsername = '".$_SESSION['LoggedInOwnerUsername']."' AND OwnerID != ".$_SESSION['LoggedInOwnerID'];
	$result = mysql_query($query) or die('Query (select of count(*) as CPcount from associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	$row = mysql_fetch_assoc($result);
	$CPcount = (int)$row['CPcount'];
?>	

	<div class="container"><!-- We need to wrap the widget code inside a 'container' div -->
	<!-- The div-based pop (i.e. div of class="popup") is initially shown and gets hidden/shown on toggle of the 'Friends Icon' via showHidePopupDiv(). Note: the inclusion of a left: positioning value (via PHP) is an IE hack to prevent the widget from floating to the left in IE7 and earlier versions.  -->
	<div id="popup1" class="popup" style="display: <? if ($_SESSION['KeepWidgetOpen'] == 'true') echo 'block'; else echo 'none'; ?>; left: <?php echo $_SESSION['VPwidth'] - 300; ?>px;">
		<!--  <div class="popuptitle">Selector Title Here</div>  I DECIDE THAT THE POPUP DOESN'T NEED A TITLE -->
			<div class="popupbody">
			<!--Create the Tabbed Panel widget and assign classes to each element-->
				<div class="TabbedPanels" id="TabbedPanels1">
				<ul class="TabbedPanelsTabGroup">
				<li class="TabbedPanelsTab" onClick="document.getElementById('contentconsumersform').submit();">My Stuff</li> 
				<li class="TabbedPanelsTab" <?php if ($CPcount >= 1) echo 'onClick="document.getElementById(\'contentproducersform\').submit();"'; ?>>Friends&rsquo; Stuff</li> 
				</ul>
				<div class="TabbedPanelsContentGroup">
					<div class="TabbedPanelsContent">
					<form method="post" name="contentconsumersform" id="contentconsumersform" action="/scripts/widgetslave.php">
<?php
					/* In this "My Stuff" or "My Content Consumers" tab, we generate a radio-button list of content consumers. The first such consumer in the associates_table is actually the logged in Owner himself/herself, referred to as "My Gallery Favorites".  Thereafter, we present (under a "View As" subheader) all the other associates that pertain to the logged in Owner of OwnerID == $_SESSION['LoggedInOwnerID'], with the first AssociateName alphabetically preselected. */
					// First obtain and present the associate (Content Consumer) pertaining to the logged in Owner himself/herself.
					$query = "SELECT AssociateID, AssociateName FROM associates_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID']." AND OwnerUsername = '".$_SESSION['LoggedInOwnerUsername']."'";
					$result = mysql_query($query) or die('Query (select of AssociateID, AssociateName for logged in Owner himself from associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
					$line = mysql_fetch_assoc($result);
					($line['AssociateID'] == $_SESSION['InitialAssociateIDcc']) ? $mytext = ' CHECKED' : $mytext = '';
					echo '<input type="radio" name="ContentConsumer" value="'.$line['AssociateID'].'" style="padding-top: 6px;" onclick="document.contentconsumersform.submit()"'.$mytext.'>'.$line['AssociateName'].'&nbsp;&nbsp;<br />';

					// Then we generate a radio-button list of the other content consumers of OwnerID == $_SESSION['LoggedInOwnerID'] (excluding the associate that pertains to the logged in Owner himself/herself), with the first AssociateName alphabetically preselected.
					$query = "SELECT AssociateID, AssociateName FROM associates_table WHERE OwnerID = ".$_SESSION['LoggedInOwnerID']." AND OwnerUsername != '".$_SESSION['LoggedInOwnerUsername']."' ORDER BY AssociateName ASC";
					$result = mysql_query($query) or die('Query (select of AssociateID, AssociateName from associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
					$num_rows = mysql_num_rows($result);
					if ($num_rows > 0) echo '<div style="font-size: 14px; padding-bottom: 2px;"><br>View as:</div>';
					while ($line = mysql_fetch_assoc($result))
						{
						// Match one row of the result set to the value of $_SESSION['InitialAssociateIDcc'] (which was set in index.php during Owner log in verification) to determine which of the listed radio buttons should be preselected.
						($line['AssociateID'] == $_SESSION['InitialAssociateIDcc']) ? $mytext = ' CHECKED' : $mytext = '';
						echo '<input type="radio" name="ContentConsumer" value="'.$line['AssociateID'].'" style="padding-top: 3px;" onclick="document.contentconsumersform.submit()"'.$mytext.'>&nbsp;'.$line['AssociateName'].'&nbsp;&nbsp;<br />';
						}
?>
					</form>
					</div>

					<div class="TabbedPanelsContent">
					<form method="post" name="contentproducersform" id="contentproducersform" action="/scripts/widgetslave.php">
<?php
					// In this "Friends' Stuff" or "My Content Providers" tab, we generate a radio-button list of content providers to OwnerID == $_SESSION['LoggedInOwnerID'], with the first AssociateName alphabetically preselected.
					// Do this by a table join of associates table and owners_table, matching all rows of associates_table where OwnerUsername == $_SESSION['LoggedInOwnerUsername'], except don't bother with the match that occurs where associates_table.OwnerID equals the logged in Owner's own $_SESSION['LoggedInOwnerID']. Join tables on OwnerID. Select owners_table.OwnerLabel.
					// If there are no Content Providers for this logged in Owner, $CPcount (set above) will be zero, triggering a simple "You have no content providers message" in place of a radio-button list.
					$query = "SELECT associates_table.AssociateID, associates_table.AuthorizedFileIDs, owners_table.OwnerLabel FROM associates_table, owners_table WHERE associates_table.OwnerUsername = '".$_SESSION['LoggedInOwnerUsername']."' AND associates_table.OwnerID != ".$_SESSION['LoggedInOwnerID']." AND associates_table.OwnerID = owners_table.OwnerID";
					$result = mysql_query($query) or die('Query (select AssociateID, OwnerLabel from jointed associates_table and owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
					while ($line = mysql_fetch_assoc($result))
						{
						// Match one row of the result set to the value of $_SESSION['InitialAssociateIDcp'] (which was set in index.php during Owner log in verification) to determine which of the listed radio buttons should be preselected.
						($line['AssociateID'] == $_SESSION['InitialAssociateIDcp']) ? $mytext = ' CHECKED' : $mytext = '';
						echo '<input type="radio" name="ContentProducer" value="'.$line['AssociateID'].'" style="padding-top: 3px;" onclick="document.contentproducersform.submit()"'.$mytext.'>&nbsp;'.$line['OwnerLabel'];

						// Loop through the value of $line['AuthorizedFileIDs'] unless it's empty, obtained from the previous query, for each media item (belonging to the Content Provider and assigned to the logged in Owner) to see whether any of the authorized media files has an AssignDate (stored in assign_table) that is later than the logged in Owner's previous log in (stored above on login as $_SESSION['PreviousLogInOfLoggedInOwner']). If there are, display a bell icon next to the Content Producer's OwnerLabel.
						$authorizedFileIDsArray = explode(',', $line['AuthorizedFileIDs']); // Convert the string into an array.
						if (!empty($line['AuthorizedFileIDs'])) foreach ($authorizedFileIDsArray as $theFileID)
							{
							$queryBell = "SELECT COUNT(*) FROM assign_table WHERE FileID = ".$theFileID." AND AssociateID = ".$line['AssociateID']." AND TIMESTAMPDIFF(MINUTE, '".$_SESSION['PreviousLogInOfLoggedInOwner']."', AssignDate) >= 0";
							$resultBell = mysql_query($queryBell) or die('Query (select count from assign_table) failed: ' . mysql_error().' and the database query string was: '.$queryBell);
							$rowBell = mysql_fetch_row($resultBell); // $row array should have just one item, which holds either '0' or '1'
							$theFileCount = $rowBell[0];
							if ($theFileCount > 0)
								{
								// Show a bell icon next to the name of the Content Producer if there was at least one new media item assigned to the logged in Owner. Then break out of the foreach loop because there's no point in testing whether the UploadDate of the next media item in the $authorizedFileIDsArray array constitutes as new to this logged in Owner.
								echo '&nbsp;&nbsp;<i class="icon-bell icon-white"></i>';
								break;
								}
							}
						echo '&nbsp;&nbsp;<br />'; // The non-breaking spaces ensure that the "close X" doesn't abutt against the final letter of the friend's name.
						}
?>
					</form>
<?php
					if ($CPcount == 0) echo 'None of your friends have shared<br>anything with you just yet.';
?>
					</div>
				</div> 
			</div>

			<!--Initialize the Tabbed Panel widget object. See http://labs.adobe.com/technologies/spry/articles/tabbed_panel/ regarding setting which panel is open by default. -->
<?php
			if ($_SESSION['TabNumber'] == 0)
				{
?>
				<script type="text/javascript">
				var TabbedPanels1 = new Spry.Widget.TabbedPanels("TabbedPanels1", { defaultTab: 0 });
				</script> 
<?php
				}
			else if ($_SESSION['TabNumber'] == 1)
				{
?>
				<script type="text/javascript">
				var TabbedPanels1 = new Spry.Widget.TabbedPanels("TabbedPanels1", { defaultTab: 1 });
				</script> 
<?php
				}
?>
			<a class="pointer" style="position: absolute; bottom: 1px; right: 1px; padding: 0px; font-family: sans-serif; text-decoration: none; font-size: 16px; background-color: #FFFFFF; color: #4D8963;" onClick="showHidePopupDiv('popup1');">&times;</a>
		</div>
	</div>
<?php
	}

if  ((empty($OwnerUsername) || empty($OwnerPassword)) && $_SESSION['Authenticated'] != 'true')
	{
	// Account holder needs to authenticate by entering an OwnerUsername and OwnerPassword.
	?>
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

    <div class="container">

        <div id="login-wraper">
			<form method="post" name="authenticationform" id="authenticationform" class="form login-form" action="/index.php">
			<!-- Note how the hidden viewport width and height form fields (whose names are VPwidth and VPheight) have associated values attributes that are set by linked viewportinfo.js script.  -->
			<input type="hidden" name="VPwidth">
			<input type="hidden" name="VPheight">
                <legend>Sign in to <span class="red">Abridg</span></legend>
            
                <div class="body">
                    <label>Email</label>
                    	<input autocapitalize="off" id="givefocuslegacy" type="email" name="OwnerUsername" autofocus>
                    
                    <label>Password</label>
                    <input type="password" name="OwnerPassword">
                </div>
            
                <div class="footer">
                    <label class="checkbox inline">
                        <input type="checkbox" id="inlineCheckbox1" value="option1"> Remember me
                    </label>
   					<button type="submit" class="btn btn-success">Login</button>
                </div>
            
            </form>
			
			<!-- Now dynamically assign values to the (hidden) VPwidth and VPheight fields in the id='authenticationform' form. -->
			<script type='text/javascript'>
			document.forms['authenticationform'].elements['VPwidth'].value = viewportwidth;
			document.forms['authenticationform'].elements['VPheight'].value = viewportheight;
			</script>

        </div>

    </div>

    <footer class="white navbar-fixed-bottom">
      <!-- Here we invoke the bootstrap modal. See http://www.w3resource.com/twitter-bootstrap/modals-tutorial.php#firstexample -->
	  Don't have an account yet? <a class="btn btn-black" onClick="$('#SignUpModal').modal({show: true});">Register</a>
    </footer>

	<?php
	}
else
	{
	// See if the user has been authenticated by examining the $_SESSION['Authenticated'] session variable, which will have been set to 'true' or 'false' earlier in the script. (Note that PHP comparisons are case-sensitive [unlike MySQL query matches] and sha1 returns a lower-case result.) If the user is authenticated, show video/image media to which he/she has been granted access (as determined in the AuthorizedAssociateIDs field of the media_table) for the current values of $_SESSION['OwnerID'] and $_SESSION['AssociateID'] (which are initialized earlier above in index.php, but whose values will be changed every time the account Owner clicks one of the radio buttons in the div-based popup widget/selectorpanel).
	if ($_SESSION['Authenticated'] == 'true')
		{
		// Calculate an amount of horizontal space to leave (based on the viewport width) so that the ImageDisplayBox and the VideoDisplayBox don't obscure the image and video thumbnails and their titles, description text, filenames, etc. towards the left of the screen. Allow a greater $thumbnailspacer when the viewport is wider.
		switch ($_SESSION['VPwidth'] > 1000)
			{
			case true:
				$thumbnailspacer = 450;
				break;
			case false:
				$thumbnailspacer = 150;
				break;
			}
		?>
		<div id="ImageDisplayBox" style="display: none; position: absolute; top: 60px; left: <?=$thumbnailspacer; ?>px;">This is the contents of the ImageDisplayBox</div>
		<div id="OuterVideoContainer" style="position: absolute; top: 60px; left: <?=$thumbnailspacer; ?>px;"><div id="VideoDisplayBox" style="display: none;">Here is the contents of the VideoDisplayBox</div></div>

		<div style="height: 500px; margin-top: 0px; margin-left: 50px; margin-right: 0px;"> <!-- This div create left and right margin white space as well as some extra space so that the large background image doesn't get cut off when there is little other content on the page. -->

		<div id="NewMediaScreen" style="text-align: left; margin-left: 0px; display: none; min-height: 0px;">
		<?php
		// Retrieve from media_table and display only new media (in descending order of CaptureDate) whose AssignDate (from assign_table) is after $_SESSION['PreviousLogIn']. Obtain these media items via a join of media_table and assign_table where the OwnerID column of both tables is $_SESSION['OwnerID'] (initially set above during successful log in then potentially changed to a new value within widgetslave.php upon a click of one of the radio buttons in the two-tabbed panel selector/widget) and the associate's previous log in datetime is $_SESSION['PreviousLogIn']. Polarity psuedo-example: TIMESTAMPDIFF('MONTH', Feb, May) is 3.
		// Note: when I originally wrote the query for SELECT'ing New media items, I hadn't yet conceived the assign_table. Now that table exists, I have adapted the query to use a join between media_table and assign_table. In consequence, I don't think there's any virtue to examining the contents of $row['AuthorizedAssociateIDs'] for a match with $_SESSION['AssociateID'] since the query itself will now select on AssociateID, rendering the in_array() step superfluous.
		$query2 = "SELECT media_table.*, DATE_FORMAT(media_table.CaptureDate, '%M %e, %Y') as CaptureDateReformatted FROM media_table, assign_table WHERE media_table.FileID = assign_table.FileID AND media_table.OwnerID = ".$_SESSION['OwnerID']." AND assign_table.AssociateID = ".$_SESSION['AssociateID']." AND TIMESTAMPDIFF(MINUTE, '".$_SESSION['PreviousLogIn']."', assign_table.AssignDate) >= 0 ORDER BY media_table.UploadDate DESC";
		$result2 = mysql_query($query2) or die('Query (select new media from media_table for this OwnerID) failed: ' . mysql_error().' and the database query string was: '.$query2);
		$AtLeastOneNewMediaItem = false; // Initialize this flag to false. It will get set to true below if there's at least one new media item assigned to the Account Holder.
		while ($row = mysql_fetch_assoc($result2))
			{
			// Skip over this row in the resultset if the AssociateID (stored in $_SESSION['AssociateID'] isn't included in the AuthorizedAssociateIDs of the media_table.
			$AuthorizedAssociateIDsArray = explode(',', $row['AuthorizedAssociateIDs']);
			if (!in_array($_SESSION['AssociateID'], $AuthorizedAssociateIDsArray)) continue; // Here, 'continue' means skip to the next iteration of the while loop.
			$AtLeastOneNewMediaItem = true; // A new media item assigned to this account holder has been found, so we can set the flag to true.
			// Having not skipped the row, we now note that we will need to construct and work with the actual names of the media file and snapshot file that are stored on the server, which we construct via the following complex statements that just append _XXX onto $row['Filename'] and $row['Snapshot'] respectively, where XXX is the FileID. ...
			// ... but note that for MediaClass == 'video', the value in the Snapshot column will be blank and the value in the VideoSnapshot column will be 'jwplayerframe.png' unless the Owner chose to upload a snapshot image to accompany the video media item. Also note that for MediaClass == 'application' (i.e. document items), the value in the Snapshot column will be the actual name of a generic thumbnail image to represent that document (e.g. generic-document.png). We need to bear these possibilities in mind when deriving the file names $theSnapshotFile and $theVideoSnapshotFile.
			$theFilenameFile = substr($row['Filename'], 0, strrpos($row['Filename'], '.')).'_'.$row['FileID'].substr($row['Filename'], strrpos($row['Filename'], '.'));
			if ($row['MediaClass'] == 'video')
				{
				if (empty($row['Snapshot'])) $theSnapshotFile = ''; else $theSnapshotFile = substr($row['Snapshot'], 0, strrpos($row['Snapshot'], '.')).'_'.$row['FileID'].substr($row['Snapshot'], strrpos($row['Snapshot'], '.'));
				if ($row['VideoSnapshot'] == 'jwplayerframe.png') $theVideoSnapshotFile = 'jwplayerframe.png'; else $theVideoSnapshotFile = substr($row['VideoSnapshot'], 0, strrpos($row['VideoSnapshot'], '.')).'_'.$row['FileID'].substr($row['VideoSnapshot'], strrpos($row['VideoSnapshot'], '.'));
				}
			else if ($row['MediaClass'] == 'application' || $row['MediaClass'] == 'audio') // For audio items and documents such as Word, PowerPoint, TIFF, Photoshop, etc.
				{
				$theSnapshotFile = $row['Snapshot'];
				}			
			else
				{
				$theSnapshotFile = substr($row['Snapshot'], 0, strrpos($row['Snapshot'], '.')).'_'.$row['FileID'].substr($row['Snapshot'], strrpos($row['Snapshot'], '.'));
				$theVideoSnapshotFile = substr($row['VideoSnapshot'], 0, strrpos($row['VideoSnapshot'], '.')).'_'.$row['FileID'].substr($row['VideoSnapshot'], strrpos($row['VideoSnapshot'], '.')); // this line looks redundant?
				};
			?>
			<table style="font-size: 14px; font-family: Geneva, Arial, Helvetica, sans-serif; padding: 0px; border-spacing: 4px;">
			<tr>
			<td><?php if (!empty($row['Title'])) echo $row['Title'].'<br />'; ?><span style="font-size: 10px; color:#666666;">File: <?=str_replace('_', ' ', $row['Filename']); ?>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<?=$row['CaptureDateReformatted']; ?></span></td><!-- Replace underscores with spaces for a nicer version of the file name. -->
			</tr>
			<?php
			if (!empty($row['FileDescription']))
				{
				$thedescription = substr($row['FileDescription'], 0, 200); // Excerpt only the first 200 characters of the FileDescription
				if (strlen($row['FileDescription']) > 200) $thedescription .= '...'; // Append ellipsis to imply that this is an abbreviated version of the full FileDescription
				echo '<tr><td class="greytext">'.$thedescription.'</td></tr>';
				}
			switch($row['MediaClass'])
				{
				case 'video':
			?>
					<tr>
					<td style="padding-bottom: 16px;">
					<!-- Note below that I used urlencoding (javascript function encodeURI()) for the video file b/c IE would sometimes fail to find the file otherwise, displaying an exclamation point icon instead. Also note that  http://www.longtailvideo.com/support/forums/jw-player/setup-issues-and-embedding/1758/bad-xml forum suggested using encodeURIComponent() but I found that encodeURI() was the right choice! -->
					<a href="#" onclick="document.getElementById('NewMediaScreen').style.minHeight = '300px'; hideElement('ImageDisplayBox'); hideElement('OuterVideoContainer');	var fileurl = '/media/<?=$theFilenameFile; ?>'; jwplayer('VideoDisplayBox').setup({ flashplayer: '/jwplayer/player.swf', file:  encodeURI(fileurl), height: 324, width:571, skin: '/jwplayer/skins/newtubedark.zip', image: '/snapshots/<?=$theSnapshotFile; ?>', stretching: 'fill' }); showElement('OuterVideoContainer'); return false;"><img alt="Video Snapshot" border="0" src="/snapshots/<?=$theVideoSnapshotFile; ?>"></a>&nbsp;&nbsp;<a target="linkshare" href="http://www.abridg.com/<?=$row['QueryString']; ?>"><img style="vertical-align: top; border: 0;" src="/images/share-icon.png" alt="Sharelink Icon" title="Page link to share this file" style="position: relative; top: 0px; border: 0px;" width="15" height="15"></a>
					</td>
					</tr>
			<?php
					break;
				case 'image':
			?>
					<tr>
					<td style="padding-bottom: 16px;">
			<?php
					/* Before placing the full-size media file image inside the display box (div id="ImageDisplayBox") via Javascript's innerHTML property, use PHP to obtain and appropriately scale the width and height attributes of the image by reference to the viewport's width ($_SESSION['VPwidth']) and height ($_SESSION['VPheight']). For picture and sample data, see file = "Image Positioning & Scaling Calculations.xls" */
					// For landscape images, calculating fixed width as follows: Allowing 5% of screen width for left-hand margin (as set in stylesheet id="main") and 10% (discretionary) for right margin, and allowing 450px (i.e. $thumbnailspacer = 450) for thumbnails on the left side of the screen (when viewport width > 1000px) or 150px (i.e. $thumbnailspacer = 150 when viewport width <= 1000px), that means fixing the full-size media image file's width at VPwidth - $thumbnailspacer - 15%*VPwidth from the left i.e. at 0.85*VPwidth - 450px (or 150px).
					// For portrait images, calculating fixed height as follows: Allowing 100px from the top of the screen width (as set in stylesheet id="ImageDisplayBox") and a 4% (discretionary) for bottom margin, that means fixing the full-size media image file's height at VPheight - 100px - 4%*VPheight from the left i.e. at 0.96*VPheight - 100px.
					list($width, $height, $type, $attr) = getimagesize('/home/paulme6/public_html/abridg/media/'.$theFilenameFile);
					if ($width > $height) // landscape
						{
						$newWidth = 0.85*$_SESSION['VPwidth'] - $thumbnailspacer;
						$newHeight = $height * $newWidth/$width;
						}
					else // portrait
						{
						$newHeight = 0.96*$_SESSION['VPheight'] - 100;
						$newWidth = $width * $newHeight/$height;
						};
					// Now, having calculated $newWidth and $newHeight for landscape or portrait images, it's necesssary to check that neither value exceeds its respective available viewport dimension. (Remember, we don't know what shape the actual viewport is. It might be, say, a wide rectangle for which the above calculation may create a $newHeight that is too high for the available viewport when the image is landscape.) 
					// Pare back $newHeight if it's too big for the viewport as envisioned.
					if ($newHeight > ($_SESSION['VPheight'] - 100 - 0.04*$_SESSION['VPheight'])) // i.e. If $newHeight > available vertical space...
						{
						$newHeight = $_SESSION['VPheight'] - 100 - 0.04*$_SESSION['VPheight']; // Fix $newHeight to the maximum allowable ...
						$newWidth = $width * $newHeight/$height; // ... and rescale $newWidth proportionately.
						}
					// Pare back $newWidth if it's too big for the viewport as envisioned.
					if ($newWidth > ($_SESSION['VPwidth'] - $thumbnailspacer - 0.15*$_SESSION['VPwidth'])) // i.e. If $newHeight > available vertical space...
						{
						$newWidth = $_SESSION['VPwidth'] - $thumbnailspacer - 0.15*$_SESSION['VPwidth']; // Fix $newWidth to the maximum allowable ...
						$newHeight = $height * $newWidth/$width; // ... and rescale $newHeight proportionately.
					}
					// Round $newWidth and $newHeight to integer values.
					$newWidth = round($newWidth);
					$newHeight = round($newHeight);
				?>
					<a href="#" onclick="document.getElementById('NewMediaScreen').style.minHeight = '<?php echo $newHeight + 0; ?>px'; var DisplayBoxHTML; DisplayBoxHTML = '<img alt=\'Image loading - please wait\' src=\'/media/<?=$theFilenameFile; ?>\' width=\'<?=$newWidth; ?>\' height=\'<?=$newHeight; ?>\' style=\'width: <?=$newWidth; ?>px; height: <?=$newHeight; ?>px\'>'; hideElement('VideoDisplayBox'); hideElement('ImageDisplayBox'); ShowHideToggle('ImageDisplayBox'); document.getElementById('ImageDisplayBox').innerHTML = DisplayBoxHTML; return false;"><img alt="Thumbnail Image" border="0" src="/snapshots/<?=$theSnapshotFile; ?>"></a>&nbsp;&nbsp;<a target="linkshare" href="http://www.abridg.com/<?=$row['QueryString']; ?>"><img style="vertical-align: top; border: 0;" src="/images/share-icon.png" alt="Sharelink Icon" title="Page link to share this file" style="position: relative; top: 4px; border: 0px;" width="15" height="15"></a>
					</td>
					</tr>
			<?php
					break;
				case 'audio':
			?>
					<tr>
					<td style="padding-bottom: 16px;">
					<!-- Note below that I used urlencoding (javascript function encodeURI()) for the audio file b/c IE would sometimes fail to find the file otherwise, displaying an exclamation point icon instead. Also note that  http://www.longtailvideo.com/support/forums/jw-player/setup-issues-and-embedding/1758/bad-xml forum suggested using encodeURIComponent() but I found that encodeURI() was the right choice! -->
					<a href="#" onclick="document.getElementById('NewMediaScreen').style.minHeight = '300px'; hideElement('ImageDisplayBox'); hideElement('OuterVideoContainer');	document.getElementById('OuterVideoContainer').style.top = '170px'; document.getElementById('OuterVideoContainer').style.left = '45%'; var fileurl = '/media/<?=$theFilenameFile; ?>'; jwplayer('VideoDisplayBox').setup({ flashplayer: '/jwplayer/player.swf', file:  encodeURI(fileurl), height: 120, width:120, controlbar: 'none', skin: '/jwplayer/skins/simple.zip', image: '/snapshots/<?=$theSnapshotFile; ?>', stretching: 'fill' }); showElement('OuterVideoContainer'); return false;"><img alt="Audio Icon" border="0" width="60" height="60" src="/snapshots/<?=$theSnapshotFile; ?>"></a>&nbsp;&nbsp;<a target="linkshare" href="http://www.abridg.com/<?=$row['QueryString']; ?>"><img style="vertical-align: top; border: 0;" src="/images/share-icon.png" alt="Sharelink Icon" title="Page link to share this file" style="position: relative; top: 0px; border: 0px;" width="15" height="15"></a>
					</td>
					</tr>
			<?php
					break;
				case 'application':
			?>
					<tr>
					<td style="padding-bottom: 16px;">
					<a target="linkshare" href="/<?=$row['QueryString']; ?>" onclick="document.getElementById('NewMediaScreen').style.minHeight = '60px'; hideElement('VideoDisplayBox'); hideElement('ImageDisplayBox');"><img alt="Document Thumbnail" border="0" width="50" height="60" src="/snapshots/<?=$theSnapshotFile; ?>"></a>&nbsp;&nbsp;<a target="linkshare" href="http://www.abridg.com/<?=$row['QueryString']; ?>"><img style="vertical-align: top; border: 0;" src="/images/share-icon.png" alt="Sharelink Icon" title="Page link to share this file" style="position: relative; top: 4px; border: 0px;" width="15" height="15"></a>
					</td>

					</tr>
			<?php
					break;
				}
			?>
			</table>
			<?php
			}
	echo '<div style="height: 40px;">&nbsp;</div>'; // Create a gap of white space so that the last (bottom-most) thumbnail item towards the left of the screen doesn't get cut off by the white footer bar strip that contains the 'New', 'Videos', 'Images', 'Audio', and 'Documents' buttons.

	if ($AtLeastOneNewMediaItem == false) 
		{
		// Display a "No new media items" message. But first, reformat the $_SESSION['PreviousLogIn'] from its existing MySQL datetime format (e.g. 2012-01-13 12:12:40) into format of e.g. January 13, 2012
		$prevlogin = $_SESSION['PreviousLogIn'];
		$prevlogin = substr($prevlogin, 0, strpos($prevlogin, ' ')); // Extract just the part of the string up to (and excluding) the space between day and hour.
		$prevloginarray = explode('-', $prevlogin);
		$theYear = $prevloginarray[0];
		$theMonth = $prevloginarray[1]; $theMonth = (string)$theMonth;
		$theDay = $prevloginarray[2]; $theDay = (string)$theDay;
		// Strip a leading zero from $theDay.
		$theDay = trim($theDay, '0');
		// Now convert $theMonth from numeric to letter form
		switch($theMonth)
			{
			case '01' :
				$theMonth = 'January';
				break;
			case '02' :
				$theMonth = 'February';
				break;
			case '03' :
				$theMonth = 'March';
				break;
			case '04' :
				$theMonth = 'April';
				break;
			case '05' :
				$theMonth = 'May';
				break;
			case '06' :
				$theMonth = 'June';
				break;
			case '07' :
				$theMonth = 'July';
				break;
			case '08' :
				$theMonth = 'August';
				break;
			case '09' :
				$theMonth = 'September';
				break;
			case '10' :
				$theMonth = 'October';
				break;
			case '11' :
				$theMonth = 'November';
				break;
			case '12' :
				$theMonth = 'December';
				break;
			default :
				$theMonth = 'month unknown';
				break;
			}
		if ($theMonth == 'month unknown') // If for some bizzare reason we don't find a legitimate month, abort the idea of including an actual previous login date in the message.
			{
			$prevlogin = ''; // blank string
			}
		elseif ($theYear == '1900') // the person has never logged in before such that the OwnerLastLogin is still set to its initialization value of "1900-01-01 12:00:00" (i.e. year of '1900'), so we want to omit any reference to this date in the on-screen message
			{
			$prevlogin = '';
			}
		else
			{
			$prevlogin = ' on '.$theMonth.' '.$theDay.', '.$theYear;
			}
		// We need to determine whether the associate is actually the same person as the logged in Owner. If it is, then the associate is the "My Gallery Favorites" and the "No documents have been assigned to you ..." message should be reworded. Determine this via a MySQL query.
		$query = "SELECT OwnerID, OwnerUsername FROM associates_table WHERE AssociateID = ".$_SESSION['AssociateID'];
		$result = mysql_query($query) or die('Query (select OwnerID, OwnerUsername from associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
		$row = mysql_fetch_assoc($result);
		if ($row['OwnerUsername'] == $_SESSION['LoggedInOwnerUsername'] && $row['OwnerID'] == $_SESSION['LoggedInOwnerID'])
			{
			echo '<p class="text" style="padding-top: 20px;">You haven&rsquo;t assigned any items to &lsquo;My Gallery Favorites&rsquo; since your last login'.$prevlogin.'.</p>';
			}
		else
			{
			echo '<p class="text" style="padding-top: 20px;">No new media items have been added by '.$_SESSION['OwnerLabel'].' since your last login'.$prevlogin.'.</p>';
			}
		}
		?>
		</div>

		<div id="VideosScreen" style="text-align: left; margin-left: 0px; display: none; width: 95%;">
		<?php
		// Retrieve from media_table all videos (in descending order of CaptureDate) where the OwnerID column of media_table is $_SESSION['OwnerID'] (set above during successful log in and changeable via a radio button click in the Content Producers/Friends Stuff tab of div-based popup widget/selectorpanel).
		$query3 = "SELECT *, DATE_FORMAT(CaptureDate, '%M %e, %Y') as CaptureDateReformatted FROM media_table WHERE OwnerID = ".$_SESSION['OwnerID']." AND MediaClass = 'video' ORDER BY CaptureDate DESC";
		$result3 = mysql_query($query3) or die('Query (select all videos from media_table) failed: ' . mysql_error().' and the database query string was: '.$query3);
		$AtLeastOneVideoItem = false; // Initialize this flag to false. It will get set to true below if there's at least one video item assigned to the Account Holder.
		while ($row = mysql_fetch_assoc($result3))
			{
			// Skip over this row in the while loop resultset if the AssociateID (stored in $_SESSION['AssociateID'] isn't included in the AuthorizedAssociateIDs of the media_table.
			$AuthorizedAssociateIDsArray = explode(',', $row['AuthorizedAssociateIDs']);
			if (!in_array($_SESSION['AssociateID'], $AuthorizedAssociateIDsArray)) continue;
			$AtLeastOneVideoItem = true;
			?>
			<table style="font-size: 14px; font-family: Geneva, Arial, Helvetica, sans-serif; padding: 0px; border-spacing: 4px;">
			<tr>
			<td><?php if (!empty($row['Title'])) echo $row['Title'].'<br />'; ?><span style="font-size: 10px; color:#666666;">File: <?=str_replace('_', ' ', $row['Filename']); ?>&nbsp;&nbsp;&nbsp;<a target="linkshare" href="http://www.abridg.com/<?=$row['QueryString']; ?>"><img src="/images/share-icon.png" alt="Sharelink Icon" title="Page link to share this file" style="position: relative; top: 0px; border: 0px;" width="15" height="15"></a>&nbsp;&nbsp;&nbsp;<?=$row['CaptureDateReformatted']; ?></span></td><!-- Replace underscores with spaces for a nicer version of the file name. -->
			</tr>
			<?php
			if (!empty($row['FileDescription']))
				{
				$thedescription = substr($row['FileDescription'], 0, 200); // Excerpt only the first 200 characters of the FileDescription
				if (strlen($row['FileDescription']) > 200) $thedescription .= '...'; // Append ellipsis to imply that this is an abbreviated version of the full FileDescription
				echo '<tr><td class="greytext">'.$thedescription.'</td></tr>';
				}
			?>
			<tr>
			<td style="padding-bottom: 16px;">
			<?php
			// We need to construct the actual names of the media file and snapshot file that are stored on the server, which we do via the following complex statements that just append _XXX onto $row['Filename'] and $row['Snapshot'] respectively, where XXX is the FileID. However, if the Owner didn't bother to uplaod a snapshot image for this video media item, then $row['Snapshot'] will be blank and there will be no snapshot file_XXX associated with media item of FileID = XXX. In that case, just set $theSnapshotFile to ''.
			$theFilenameFile = substr($row['Filename'], 0, strrpos($row['Filename'], '.')).'_'.$row['FileID'].substr($row['Filename'], strrpos($row['Filename'], '.'));
			if (empty($row['Snapshot'])) $theSnapshotFile = ''; else $theSnapshotFile = substr($row['Snapshot'], 0, strrpos($row['Snapshot'], '.')).'_'.$row['FileID'].substr($row['Snapshot'], strrpos($row['Snapshot'], '.'));
			?>
			<div id="containerAllVids<?=$row['FileID']; ?>">Loading the player ...</div>
			<!-- Note below that I used urlencoding (javascript function encodeURI()) for the video file b/c IE would sometimes fail to find the file otherwise, displaying an exclamation point icon instead. Also note that  http://www.longtailvideo.com/support/forums/jw-player/setup-issues-and-embedding/1758/bad-xml forum suggested using encodeURIComponent() but I found that encodeURI() was the right choice! -->
			<script type="text/javascript">
			var fileurl = "/media/<?=$theFilenameFile; ?>";
			jwplayer("containerAllVids<?=$row['FileID']; ?>").setup({ flashplayer: "/jwplayer/player.swf", file: encodeURI(fileurl), height: 290, width:514, skin: "/jwplayer/skins/newtubedark.zip", image: "/snapshots/<?=$theSnapshotFile; ?>", stretching: "exactfit" }); // Actual video is 288 x 514 (16:9 widescreen); allow extra pixel on either edge. Also, note that I'd ideally have set the stretching variable to 'fill', but this caused unreliable reloading of the snapshot/splash image within IE, necessitating a page reload to ensure the snapshot images always displayed when the user went back to the 'Videos' screen.
			</script>
			</td>
			</tr>
			</table>
			<?php
			echo '<div style="height: 40px;">&nbsp;</div>'; // Create a gap of white space so that the last (bottom-most) thumbnail video item towards the left of the screen doesn't get cut off by the white footer bar strip that contains the 'New', 'Videos', 'Images', 'Audio', and 'Documents' buttons.
			}
		// Display a message if the Owner hasn't assigned the user access to any of his/her uploaded videos.
		if ($AtLeastOneVideoItem == false) 
			{
			// We need to determine whether the associate is actually the same person as the logged in Owner. If it is, then the associate is the "My Gallery Favorites" and the "No videos have been assigned to you ..." message should be reworded. Determine this via a MySQL query.
			$query = "SELECT OwnerID, OwnerUsername FROM associates_table WHERE AssociateID = ".$_SESSION['AssociateID'];
			$result = mysql_query($query) or die('Query (select OwnerID, OwnerUsername from associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
			$row = mysql_fetch_assoc($result);
			if ($row['OwnerUsername'] == $_SESSION['LoggedInOwnerUsername'] && $row['OwnerID'] == $_SESSION['LoggedInOwnerID'])
				{
				echo '<p class="text" style="padding-top: 20px;">You haven&rsquo;t yet assigned any of your videos to &lsquo;My Gallery Favorites&rsquo;.</p>';
				}
			else
				{
				echo '<p class="text" style="padding-top: 20px;">No videos have been assigned to you by '.$_SESSION['OwnerLabel'].' at this time.</p>';
				}
			}
		?>
		</div>

		<div id="ImagesScreen" style="text-align: left; margin-left: 0px; display: none; width: 95%;">
		<?php
		// Retrieve from media_table all images (in descending order of CaptureDate) where the OwnerID column of media_table is $_SESSION['OwnerID'] (set above during successful log in and changeable via a radio button click in the Content Producers/Friends Stuff tab of the div-based popup widget/selectorpanel)
		$query4 = "SELECT *, DATE_FORMAT(CaptureDate, '%M %e, %Y') as CaptureDateReformatted FROM media_table WHERE OwnerID = ".$_SESSION['OwnerID']." AND MediaClass = 'image' ORDER BY CaptureDate DESC";
		$result4 = mysql_query($query4) or die('Query (select all images from media_table) failed: ' . mysql_error().' and the database query string was: '.$query4);
		$AtLeastOneImageItem = false; // Initialize this flag to false. It will get set to true below if there's at least one image item assigned to the Account Holder.
		while ($row = mysql_fetch_assoc($result4))
			{
			// Skip over this row in the resultset if the AssociateID (stored in $_SESSION['AssociateID']) isn't included in the AuthorizedAssociateIDs of the media_table.
			$AuthorizedAssociateIDsArray = explode(',', $row['AuthorizedAssociateIDs']);
			if (!in_array($_SESSION['AssociateID'], $AuthorizedAssociateIDsArray)) continue;
			$AtLeastOneImageItem = true;
			?>
			<table style="font-size: 14px; font-family: Geneva, Arial, Helvetica, sans-serif; padding: 0px; border-spacing: 4px;">
			<tr>
			<td><?php if (!empty($row['Title'])) echo $row['Title'].'<br />'; ?><span style="font-size: 10px; color:#666666;">File: <?=str_replace('_', ' ', $row['Filename']); ?>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<?=$row['CaptureDateReformatted']; ?></span></td><!-- Replace underscores with spaces for a nicer version of the file name. -->
			</tr>
			<?php
			if (!empty($row['FileDescription']))
				{
				$thedescription = substr($row['FileDescription'], 0, 200); // Excerpt only the first 200 characters of the FileDescription
				if (strlen($row['FileDescription']) > 200) $thedescription .= '...'; // Append ellipsis to imply that this is an abbreviated version of the full FileDescription
				echo '<tr><td class="greytext">'.$thedescription.'</td></tr>';
				}
			?>
			<tr>
			<td style="padding-bottom: 16px;">
			<?php
			/* Before placing the full-size media file image inside the display box (div id="ImageDisplayBox") via Javascript's innerHTML property, use PHP to obtain and appropriately scale the width and height attributes of the image by reference to the viewport's width ($_SESSION['VPwidth']) and height ($_SESSION['VPheight']). For picture and sample data, see file = "Image Positioning & Scaling Calculations.xls" */
			// For landscape images, calculating fixed width as follows: Allowing 5% of screen width for left-hand margin (as set in stylesheet id="main") and 10% (discretionary) for right margin, and allowing 450px (i.e. $thumbnailspacer = 450) for thumbnails on the left side of the screen (when viewport width > 1000px) or 150px (i.e. $thumbnailspacer = 140 when viewport width <= 1000px), that means fixing the full-size media image file's width at VPwidth - $thumbnailspacer - 15%*VPwidth from the left i.e. at 0.85*VPwidth - 450px (or 140px).
			// For portrait images, calculating fixed height as follows: Allowing 100px from the top of the screen width and a 4% (discretionary) for bottom margin, that means fixing the full-size media image file's height at VPheight - 100px - 4%*VPheight from the top i.e. at 0.96*VPheight - 100px.
			// Note that we need to construct and work with the actual names of the media file and snapshot file that are stored on the server, which we do via the following complex statements that just append _XXX onto $row['Filename'] and $row['Snapshot'] respectively, where XXX is the FileID.
			$theFilenameFile = substr($row['Filename'], 0, strrpos($row['Filename'], '.')).'_'.$row['FileID'].substr($row['Filename'], strrpos($row['Filename'], '.'));
			$theSnapshotFile = substr($row['Snapshot'], 0, strrpos($row['Snapshot'], '.')).'_'.$row['FileID'].substr($row['Snapshot'], strrpos($row['Snapshot'], '.'));
			list($width, $height, $type, $attr) = getimagesize('/home/paulme6/public_html/abridg/media/'.$theFilenameFile);
			if ($width > $height) // landscape
				{
				$newWidth = 0.85*$_SESSION['VPwidth'] - $thumbnailspacer;
				$newHeight = $height * $newWidth/$width;
				}
			else // portrait
				{
				$newHeight = 0.96*$_SESSION['VPheight'] - 100;
				$newWidth = $width * $newHeight/$height;
				};
			// Now, having calculated $newWidth and $newHeight for landscape or portrait images, it's necesssary to check that neither value exceeds its respective available viewport dimension. (Remember, we don't know what shape the actual viewport is. It might be, say, a wide rectangle for which the above calculation may create a $newHeight that is too high for the available viewport when the image is landscape.) 
			// Pare back $newHeight if it's too big for the viewport as envisioned.
			if ($newHeight > ($_SESSION['VPheight'] - 100 - 0.04*$_SESSION['VPheight'])) // i.e. If $newHeight > available vertical space...
				{
				$newHeight = $_SESSION['VPheight'] - 100 - 0.04*$_SESSION['VPheight']; // Fix $newHeight to the maximum allowable ...
				$newWidth = $width * $newHeight/$height; // ... and rescale $newWidth proportionately.
				}
			// Pare back $newWidth if it's too big for the viewport as envisioned.
			if ($newWidth > ($_SESSION['VPwidth'] - $thumbnailspacer - 0.15*$_SESSION['VPwidth'])) // i.e. If $newHeight > available vertical space...
				{
				$newWidth = $_SESSION['VPwidth'] - $thumbnailspacer - 0.15*$_SESSION['VPwidth']; // Fix $newWidth to the maximum allowable ...
				$newHeight = $height * $newWidth/$width; // ... and rescale $newHeight proportionately.
				}

			// Round $newWidth and $newHeight to integer values.
			$newWidth = round($newWidth);
			$newHeight = round($newHeight);
			?>
			<a href="#" onclick="document.getElementById('ImagesScreen').style.minHeight = '<?php echo $newHeight + 0; ?>px'; var DisplayBoxHTML; DisplayBoxHTML = '<img alt=\'Image loading - please wait\' src=\'/media/<?=$theFilenameFile; ?>\' width=\'<?=$newWidth; ?>\' height=\'<?=$newHeight; ?>\' style=\'width: <?=$newWidth; ?>px; height: <?=$newHeight; ?>px\'>'; hideElement('ImageDisplayBox'); ShowHideToggle('ImageDisplayBox'); document.getElementById('ImageDisplayBox').innerHTML = DisplayBoxHTML; return false;"><img alt="Thumbnail Image" border="0" src="/snapshots/<?=$theSnapshotFile; ?>"></a>&nbsp;&nbsp;<a target="linkshare" href="http://www.abridg.com/<?=$row['QueryString']; ?>"><img style="vertical-align: top; border: 0;" src="/images/share-icon.png" alt="Sharelink Icon" title="Page link to share this file" style="position: relative; top: 4px; border: 0px;" width="15" height="15"></a>
			</td>
			</tr>
			</table>
			<?php
			}
		echo '<div style="height: 40px;">&nbsp;</div>'; // Create a gap of white space so that the thumbnail image doesn't get cut off by the white footer bar strip that contains the 'New', 'Videos', 'Images', 'Audio', and 'Documents' buttons.
		
		// Display a message if the Owner hasn't assigned the user access to any of his/her uploaded images.
		if ($AtLeastOneImageItem == false)
			{
			// We need to determine whether the associate is actually the same person as the logged in Owner. If it is, then the associate is the "My Gallery Favorites" and the "No images have been assigned to you ..." message should be reworded. Determine this via a MySQL query.
			$query = "SELECT OwnerID, OwnerUsername FROM associates_table WHERE AssociateID = ".$_SESSION['AssociateID'];
			$result = mysql_query($query) or die('Query (select OwnerID, OwnerUsername from associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
			$row = mysql_fetch_assoc($result);
			if ($row['OwnerUsername'] == $_SESSION['LoggedInOwnerUsername'] && $row['OwnerID'] == $_SESSION['LoggedInOwnerID'])
				{
				echo '<p class="text" style="padding-top: 20px;">You haven&rsquo;t yet assigned any of your images to &lsquo;My Gallery Favorites&rsquo;.</p>';
				}
			else
				{
				echo '<p class="text" style="padding-top: 20px;">No images have been assigned to you by '.$_SESSION['OwnerLabel'].' at this time.</p>';
				}
			}
		?>
		</div>

		<div id="AudioScreen" style="text-align: left; margin-left: 0px; display: none;">
		<?php
		// Retrieve from media_table all audio items (in descending order of CaptureDate) where the OwnerID column of media_table is $_SESSION['OwnerID'] (set above during successful log in and changeable via a radio button click in the Content Producers/Friends Stuff tab of the div-based popup widget/selectorpanel).
		$query3 = "SELECT *, DATE_FORMAT(CaptureDate, '%M %e, %Y') as CaptureDateReformatted FROM media_table WHERE OwnerID = ".$_SESSION['OwnerID']." AND MediaClass = 'audio' ORDER BY CaptureDate DESC";
		$result3 = mysql_query($query3) or die('Query (select all audio items from media_table) failed: ' . mysql_error().' and the database query string was: '.$query3);
		$AtLeastOneAudioItem = false; // Initialize this flag to false. It will get set to true below if there's at least one audio item assigned to the Account Holder.
		while ($row = mysql_fetch_assoc($result3))
			{
			// Skip over this row in the while loop resultset if the AssociateID (stored in $_SESSION['AssociateID'] isn't included in the AuthorizedAssociateIDs of the media_table.
			$AuthorizedAssociateIDsArray = explode(',', $row['AuthorizedAssociateIDs']);
			if (!in_array($_SESSION['AssociateID'], $AuthorizedAssociateIDsArray)) continue;
			$AtLeastOneAudioItem = true;
			?>
			<table style="font-size: 14px; font-family: Geneva, Arial, Helvetica, sans-serif; padding: 0px; border-spacing: 4px;">
			<tr>
			<td><?php if (!empty($row['Title'])) echo $row['Title'].'<br />'; ?><span style="font-size: 10px; color:#666666;">File: <?=str_replace('_', ' ', $row['Filename']); ?>&nbsp;&nbsp;&nbsp;<a target="linkshare" href="http://www.abridg.com/<?=$row['QueryString']; ?>"><img src="/images/share-icon.png" alt="Sharelink Icon" title="Page link to share this file" style="position: relative; top: 0px; border: 0px;" width="15" height="15"></a>&nbsp;&nbsp;&nbsp;<?=$row['CaptureDateReformatted']; ?></span></td><!-- Replace underscores with spaces for a nicer version of the file name. -->
			</tr>
			<?php
			if (!empty($row['FileDescription']))
				{
				$thedescription = substr($row['FileDescription'], 0, 200); // Excerpt only the first 200 characters of the FileDescription
				if (strlen($row['FileDescription']) > 200) $thedescription .= '...'; // Append ellipsis to imply that this is an abbreviated version of the full FileDescription
				echo '<tr><td class="greytext">'.$thedescription.'</td></tr>';
				}
			?>
			<tr>
			<td style="padding-bottom: 16px;">
			<?php
			// We need to construct the actual names of the media file and snapshot file that are stored on the server, which we do via the following complex statements that just append _XXX onto $row['Filename'] and $row['Snapshot'] respectively, where XXX is the FileID.
			$theFilenameFile = substr($row['Filename'], 0, strrpos($row['Filename'], '.')).'_'.$row['FileID'].substr($row['Filename'], strrpos($row['Filename'], '.'));
			$theSnapshotFile = $row['Snapshot']; // Audio items are assigned a generic snapshop "generic-audio.png"
			?>
			<div id="containerAllAudios<?=$row['FileID']; ?>">Loading the audio player ...</div>
			<!-- Note below that I used urlencoding (javascript function encodeURI()) for the video file b/c IE would sometimes fail to find the file otherwise, displaying an exclamation point icon instead. Also note that  http://www.longtailvideo.com/support/forums/jw-player/setup-issues-and-embedding/1758/bad-xml forum suggested using encodeURIComponent() but I found that encodeURI() was the right choice! -->
			<script type="text/javascript">
			var fileurl = "/media/<?=$theFilenameFile; ?>";
			jwplayer("containerAllAudios<?=$row['FileID']; ?>").setup({ flashplayer: "/jwplayer/player.swf", file: encodeURI(fileurl), height: 120, width:120, skin: "/jwplayer/skins/simple.zip", image: "/snapshots/<?=$theSnapshotFile; ?>", stretching: "exactfit" });
			</script>
			</td>
			</tr>
			</table>
			<?php
			}
		echo '<div style="height: 40px;">&nbsp;</div>'; // Create a gap of white space so that the last (bottom-most) thumbnail audio item towards the left of the screen doesn't get cut off by the white footer bar strip that contains the 'New', 'Videos', 'Images', 'Audio', and 'Documents' buttons.
		
		// Display a message if the Owner hasn't assigned the user access to any of his/her uploaded videos.
		if ($AtLeastOneAudioItem == false) 
			{
			// We need to determine whether the associate is actually the same person as the logged in Owner. If it is, then the associate is the "My Gallery Favorites" and the "No audio items have been assigned to you ..." message should be reworded. Determine this via a MySQL query.
			$query = "SELECT OwnerID, OwnerUsername FROM associates_table WHERE AssociateID = ".$_SESSION['AssociateID'];
			$result = mysql_query($query) or die('Query (select OwnerID, OwnerUsername from associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
			$row = mysql_fetch_assoc($result);
			if ($row['OwnerUsername'] == $_SESSION['LoggedInOwnerUsername'] && $row['OwnerID'] == $_SESSION['LoggedInOwnerID'])
				{
				echo '<p class="text" style="padding-top: 20px;">You haven&rsquo;t yet assigned any of your audio items to &lsquo;My Gallery Favorites&rsquo;.</p>';
				}
			else
				{
				echo '<p class="text" style="padding-top: 20px;">No audio items have been assigned to you by '.$_SESSION['OwnerLabel'].' at this time.</p>';
				}
			}
		?>
		</div>

		<div id="DocumentsScreen" style="text-align: left; margin-left: 0px; display: none;">
		<?php
		// Retrieve from media_table all documents (i.e. MediaClass == 'application') (in descending order of CaptureDate) where the OwnerID column of media_table is $_SESSION['OwnerID'] (set above during successful log in and changeable via a radio button click in the Content Producers/Friends Stuff tab of selectorpanel.php)
		$query5 = "SELECT *, DATE_FORMAT(CaptureDate, '%M %e, %Y') as CaptureDateReformatted FROM media_table WHERE OwnerID = ".$_SESSION['OwnerID']." AND MediaClass = 'application' ORDER BY CaptureDate DESC";
		$result5 = mysql_query($query5) or die('Query (select all documents from media_table) failed: ' . mysql_error().' and the database query string was: '.$query5);
		$AtLeastOneDocumentItem = false; // Initialize this flag to false. It will get set to true below if there's at least one document item assigned to the Account Holder.
		while ($row = mysql_fetch_assoc($result5))
			{
			// Skip over this row in the resultset if the AssociateID (stored in $_SESSION['AssociateID']) isn't included in the AuthorizedAssociateIDs of the media_table.
			$AuthorizedAssociateIDsArray = explode(',', $row['AuthorizedAssociateIDs']);
			if (!in_array($_SESSION['AssociateID'], $AuthorizedAssociateIDsArray)) continue;
			$AtLeastOneDocumentItem = true;
			?>
			<table style="font-size: 14px; font-family: Geneva, Arial, Helvetica, sans-serif; padding: 0px; border-spacing: 4px;">
			<tr>
			<td><?php if (!empty($row['Title'])) echo $row['Title'].'<br />'; ?><span style="font-size: 10px; color:#666666;">File: <?=str_replace('_', ' ', $row['Filename']); ?>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<?=$row['CaptureDateReformatted']; ?></span></td><!-- Replace underscores with spaces for a nicer version of the file name. -->
			</tr>
			<?php
			if (!empty($row['FileDescription']))
				{
				$thedescription = substr($row['FileDescription'], 0, 200); // Excerpt only the first 200 characters of the FileDescription
				if (strlen($row['FileDescription']) > 200) $thedescription .= '...'; // Append ellipsis to imply that this is an abbreviated version of the full FileDescription
				echo '<tr><td class="greytext">'.$thedescription.'</td></tr>';
				}
			?>
			<tr>
			<td style="padding-bottom: 16px;">
			<?php
			$theFilenameFile = substr($row['Filename'], 0, strrpos($row['Filename'], '.')).'_'.$row['FileID'].substr($row['Filename'], strrpos($row['Filename'], '.'));
			$theSnapshotFile = $row['Snapshot'];
			?>
			<a target="linkshare" href="/<?=$row['QueryString']; ?>" onclick="document.getElementById('NewMediaScreen').style.minHeight = '60px'; hideElement('VideoDisplayBox'); hideElement('ImageDisplayBox');"><img alt="Document Thumbnail" border="0" width="50" height="60" src="/snapshots/<?=$theSnapshotFile; ?>"></a>&nbsp;&nbsp;<a target="linkshare" href="http://www.abridg.com/<?=$row['QueryString']; ?>"><img style="vertical-align: top; border: 0;" src="/images/share-icon.png" alt="Sharelink Icon" title="Page link to share this file" style="position: relative; top: 4px; border: 0px;" width="15" height="15"></a>
			</td>
			</tr>
			</table>
			<?php
			}
		echo '<div style="height: 40px;">&nbsp;</div>'; // Create a gap of white space so that the last (bottom-most) thumbnail documents item towards the left of the screen doesn't get cut off by the white footer bar strip that contains the 'New', 'Videos', 'Images', 'Audio', and 'Documents' buttons.

		// Display a message if the Owner hasn't assigned the user access to any of his/her uploaded images.
		if ($AtLeastOneDocumentItem == false)
			{
			// We need to determine whether the associate is actually the same person as the logged in Owner. If it is, then the associate is the "My Gallery Favorites" and the "No documents have been assigned to you ..." message should be reworded. Determine this via a MySQL query.
			$query = "SELECT OwnerID, OwnerUsername FROM associates_table WHERE AssociateID = ".$_SESSION['AssociateID'];
			$result = mysql_query($query) or die('Query (select OwnerID, OwnerUsername from associates_table) failed: ' . mysql_error().' and the database query string was: '.$query);
			$row = mysql_fetch_assoc($result);
			if ($row['OwnerUsername'] == $_SESSION['LoggedInOwnerUsername'] && $row['OwnerID'] == $_SESSION['LoggedInOwnerID'])
				{
				echo '<p class="text" style="padding-top: 20px;">You haven&rsquo;t yet assigned any of your documents to &lsquo;My Gallery Favorites&rsquo;.</p>';
				}
			else
				{
				echo '<p class="text" style="padding-top: 20px;">No documents have been assigned to you by '.$_SESSION['OwnerLabel'].' at this time.</p>';
				}
			}
		?>
		</div>

		<?php
		}
	else
		{
		// Authentication of this account holder is denied. Redirect user to passwordreminder.php
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
	}

if  ($_SESSION['Authenticated'] == 'true') // Close two conditional divs, one for creating extra space and the other for showing the Abridg logo as a background image
	{
?>	
    </div><!-- Close div for creating extra space -->
	</div><!-- Close of div class=container -->
<?php
	}

if  ($_SESSION['Authenticated'] == 'true') // Create footer text to be shown across bottom of the page if the user has already authenticated him/herself
	{
?>
    <footer class="white navbar-fixed-bottom">
	
	<div class="btn-group" data-toggle="buttons-radio">
    <button id="newitemslink" class="btn btn-inverse" onClick="document.getElementById('VideosScreen').style.display = 'none'; document.getElementById('ImagesScreen').style.display = 'none'; document.getElementById('AudioScreen').style.display = 'none'; document.getElementById('DocumentsScreen').style.display = 'none'; hideElement('ImageDisplayBox'); hideElement('OuterVideoContainer'); document.getElementById('NewMediaScreen').style.display = 'block';"><i class="icon-bell icon-white"></i>&nbsp;New</button>
    <button id="videoitemslink" class="btn btn-inverse" onClick="document.getElementById('NewMediaScreen').style.display = 'none'; document.getElementById('ImagesScreen').style.display = 'none'; document.getElementById('AudioScreen').style.display = 'none'; document.getElementById('DocumentsScreen').style.display = 'none'; hideElement('ImageDisplayBox'); hideElement('OuterVideoContainer'); document.getElementById('VideosScreen').style.display = 'block';"><i class="icon-film icon-white"></i>&nbsp;Videos</button>
    <button id="imageitemslink" class="btn btn-inverse" onClick="document.getElementById('NewMediaScreen').style.display = 'none'; document.getElementById('VideosScreen').style.display = 'none'; document.getElementById('AudioScreen').style.display = 'none'; document.getElementById('DocumentsScreen').style.display = 'none'; hideElement('OuterVideoContainer'); document.getElementById('ImagesScreen').style.display = 'block';"><i class="icon-picture icon-white"></i>&nbsp;Images</button>
    <button id="audioitemslink" class="btn btn-inverse" onClick="document.getElementById('NewMediaScreen').style.display = 'none'; document.getElementById('VideosScreen').style.display = 'none'; document.getElementById('ImagesScreen').style.display = 'none'; document.getElementById('DocumentsScreen').style.display = 'none'; hideElement('ImageDisplayBox'); hideElement('OuterVideoContainer'); document.getElementById('AudioScreen').style.display = 'block';"><i class="icon-volume-up icon-white"></i>&nbsp;Audio</button>
    <button id="documentitemslink" class="btn btn-inverse" onClick="document.getElementById('NewMediaScreen').style.display = 'none'; document.getElementById('VideosScreen').style.display = 'none'; document.getElementById('ImagesScreen').style.display = 'none'; document.getElementById('AudioScreen').style.display = 'none'; hideElement('ImageDisplayBox'); hideElement('OuterVideoContainer'); document.getElementById('DocumentsScreen').style.display = 'block';"><i class="icon-folder-open icon-white"></i>&nbsp;Documents</button>
    <?php echo '$_SESSN[VPwidth] is: '.$_SESSION['VPwidth'].', and $_SESS[VPheight] is: '.$_SESSION['VPheight']; ?>
	</div>
	
    </footer>
			
	<!-- Make the New screen show by default when the page first loads for an authenticated Owner. -->
	<script type="text/javascript">document.getElementById('NewMediaScreen').style.display = 'block'; document.getElementById('newitemslink').focus();</script>
<?php
	}

if ($_SESSION['SignUpValidationError'] == 'true' || $_SESSION['RegisterRequestViaPasswordReminder'] == 'true')
	{
	// Make sure the SignUp form gets displayed if there are PHP validation errors returned by signup_slave.php, or if the user clicked the 'Register' button at the footer of any of the three screens displayed in passwordreminder.php.
?>
	<script type="text/javascript">document.getElementById('SignUpModal').style.display = 'block';</script>
<?php
	$_SESSION['SignUpValidationError'] = 'false';
	$_SESSION['RegisterRequestViaPasswordReminder'] = 'false';
	}

/* If the HelpModal has already been shown, we should hide it and update the ShowWelcomeMsg column for this logged in Owner, setting it to 0. */
if ($_SESSION['Authenticated'] == 'true' && $_SESSION['WelcomeMsgShown'] == 'true')
	{
	echo '<script type="text/javascript">document.getElementById("HelpModal").style.display = "none";</script>';
	
	// Update the ShowWelcomeMsg column for this logged in Owner, setting it to 0.
	$query = "UPDATE owners_table SET ShowWelcomeMsg = 0 WHERE OwnerID = ".$_SESSION['LoggedInOwnerID'];
	$result = mysql_query($query) or die('Query (update ShowWelcomeMsg in owners_table) failed: ' . mysql_error().' and the database query string was: '.$query);
	}

/* Decide whether to show HelpModal. If the ShowWelcomeMsg column of owners_table for the logged in Owner (as stored upon successful login in session variable $_SESSION['ShowWelcomeMsg'] is '1' (i.e. show it) AND the owner is authenticated (i.e. $_SESSION['Authenticated'] == 'true') AND the welcome message hasn't previously been closed by the user (i.e. $_SESSION['WelcomeMsgShown'] is not 'true') then show the Bluebert. */
if ($_SESSION['Authenticated'] == 'true' && $_SESSION['WelcomeMsgShown'] == 'false' && $_SESSION['ShowWelcomeMsg'] == 1)
	{
	echo '<script type="text/javascript">document.getElementById("HelpModal").style.display = "block";</script>';
	// The HelpModal will have been shown upon initial login, so now we can set $_SESSION['WelcomeMsgShown'] to true.
	$_SESSION['WelcomeMsgShown'] = 'true';
	}

ob_flush();
?>
</body>
</html>