<?php
session_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<meta NAME="description" CONTENT="Request for business plan">
<title>Abridg&trade; | Request for Business Plan</title>
<link href="abridg.css" rel="stylesheet" type="text/css">
<script type="text/javascript">
function checkEmailOnly(theEmailID)
{ 
	var re = /^\s*[\w\-\+_]+(\.[\w\-\+_]+)*\@[\w\-\+_]+\.[\w\-\+_]+(\.[\w\-\+_]+)*\s*$/;
	var theEmailID
	var emailValue = document.getElementById(theEmailID).value;
	var emailLength = emailValue.length;
    if (emailLength > 50 || !re.test(emailValue))
		{
		document.getElementById("EmailError").style.display = "inline"; // This element appears in editaccountdiv
		return false;
		}
	else
		{
		document.getElementById("EmailError").style.display = "none";
		return true;
		}
}
</script>
</head>

<body>
<div id="main" style="text-align: center; padding: 0px 0px 0px 0px;">

<div id="relwrapper">

<div style="width: 500px; margin: 40px auto 0px auto; text-align: left; border: 1px solid #E1B378; padding: 10px 5px 10px 10px;">

<h1 style="margin-top: 24px; text-align: center;">Request for Abridg Business Plan</h1>
<form method="post" onSubmit="if (!emailCheck(this.careeremail.value,'noalert')) return false; else { return true; }" action="/scripts/theplan_slave.php">
<table style="width: 438px; cellspacing: 0px; cellpadding: 0px; margin-left: auto; margin-right: auto;">
<tr>
<td style="text-align: left; width: 100px; height: 40px; vertical-align: bottom;"><label style="position: relative; bottom: 4px;">Name</label></td>
<td width="226" valign="bottom" style="text-align: left;">
<input type="text" name="name" id="name" size="30" maxlength="45" value="<?php if (isset($_SESSION['name'])) echo $_SESSION['name']; ?>" onFocus="this.style.background='#FFFF99'" onBlur="this.style.background='white'; document.getElementById('organization').focus();">
<span class="tangsup" style="font-size: 10px;">&nbsp;<b>* (required)</b></span>
<?php if ($_SESSION['MsgName'] != null) { echo $_SESSION['MsgName']; $_SESSION['MsgName']=null; } ?>
</td>
</tr>
<tr>
<td style="text-align: left; height: 48px; vertical-align: bottom;"><label style="position: relative; bottom: 4px;">Organization</label></td>
<td valign="bottom" style="text-align: left;">
<input type="text" name="organization" id="organization" size="30" maxlength="45" value="<?php if (isset($_SESSION['organization'])) echo $_SESSION['organization']; ?>" onFocus="this.style.background='#FFFF99'" onBlur="this.style.background='white'; document.getElementById('email').focus();">
<span class="tangsup">&nbsp;&nbsp;<b>*</b></span>
<?php if ($_SESSION['MsgOrganization'] != null) { echo $_SESSION['MsgOrganization']; $_SESSION['MsgOrganization']=null; } ?>
</td>
</tr>
<tr>
<td style="text-align: left; height: 80px; vertical-align: middle;"><label style="position: relative; bottom: 4px;">Email</label></td>
<td style="text-align: left; vertical-align: middle;">
<input type="text" name="email" id="email" size="30" maxlength="45" value="<?php if (isset($_SESSION['email'])) echo $_SESSION['email']; ?>" onFocus="this.style.background='#FFFF99'" onBlur="this.style.background='white'; return checkEmailOnly('email'); document.getElementById('submitbutton').focus();"><span class="tangsup">&nbsp;&nbsp;<b>*</b></span>
<div class="helptextsmall">Professional email address only, please</div>
<div class='error' id='EmailError'>Your email address is invalid. Please try again.<br></div>
<?php if ($_SESSION['MsgEmail'] != null) { echo $_SESSION['MsgEmail']; $_SESSION['MsgEmail']=null; } ?>
</td>
</tr>
<tr><td colspan="2" style="height: 40px; vertical-align: bottom; text-align: center;"><input name="submitbutton" id="submitbutton" type="submit" class="buttonstyle" value="Submit" onClick="return checkEmailOnly('email');"></td></tr>
</table>
</form>

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
