<?php
header("Cache-Control: no-store, no-cache, must-revalidate");
/* This page allows a prospective investor to explore various parameter values of proliferation factor n and time interval i for user adoption and dynamically generate a chart of user adoption (using the JpGraph library - see http://jpgraph.net/ or manual at http://jpgraph.net/download/manuals/chunkhtml/index.html). Note I orginally tried to do this using the installed PECL extension gdChart but found it to be dismal. The chart is generated and stored on the server (as /images/projection.png) then displayed by model_almost2.php. */

// Start a session
session_start();

ob_start(); // Used in conjunction with ob_flush() [see www.tutorialized.com/view/tutorial/PHP-redirect-page/27316], this allows me to postpone issuing output to the screen until after the header has been sent.

// Create short variable names
$Prolif_n = $_POST['Prolif_n'];
$Interval_i = $_POST['Interval_i'];
$scale = $_POST['scale'];
$DrawChart = $_POST['DrawChart'];

// Store $Prolif_n, $Interval_i, and $scale as session variables for use in prepopulating the form fields
if (!empty($Prolif_n)) $_SESSION['Prolif_n'] = $Prolif_n;
if (!empty($Interval_i)) $_SESSION['Interval_i'] = $Interval_i;
if (!empty($scale)) $_SESSION['scale'] = $scale;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Simplified User Adoption Model</title>
<link href="/abridg.css" rel="stylesheet" type="text/css">
<script type="text/javascript">
function checkProlif_n()
{
// Validate Proliferation factor n
var prolifnValue = document.getElementById("Prolif_n").value;
var prolifnLength = prolifnValue.length;
illegalCharSet = /[^0-9\.]+/; // Reject everything that isn't a period or a digit.
reqdCharSet = /[0-9]{1,}/;  // At least one numerics.
if (illegalCharSet.test(prolifnValue) || !reqdCharSet.test(prolifnValue))
	{
	document.getElementById("ProlifnError").style.display = "inline";
	return false;
	} 
else
	{
	return true;
	}
}

function checkInterval_i()
{
// Validate Proliferation factor n
var intervaliValue = document.getElementById("Interval_i").value;
var intervaliLength = intervaliValue.length;
illegalCharSet = /[^0-9\.]+/; // Reject everything that isn't a period or a digit.
reqdCharSet = /[0-9]{1,}/;  // At least one numerics.
if (illegalCharSet.test(intervaliValue) || !reqdCharSet.test(intervaliValue))
	{
	document.getElementById("IntervaliError").style.display = "inline";
	return false;
	} 
else
	{
	return true;
	}
}

function hideAllErrors()
{
document.getElementById("ProlifnError").style.display = "none";
document.getElementById("IntervaliError").style.display = "none";
return true;
}

function checkForm() 
{
hideAllErrors();
if (!checkProlif_n() || !checkInterval_i())
	{
	return false; // return false if any one of the individual field validation functions returned a false ...
	} 
else return true;
}

function FocusFirst()
{
	if (document.forms.length > 0 && document.getElementById('chartmodelform') && document.getElementById('chartmodelform').elements.length > 0)
		{
		document.forms[0].elements[0].focus();
		document.forms[0].elements[0].style.background = "#FFFF97";
		};
}
</script>
</head>
<body onLoad="FocusFirst();">

<div style="width: 800px; margin: 20px auto 20px auto; text-align: left; border: 1px solid #E1B378; padding: 10px 15px 20px 20px;">

<h1 style="margin-top: 12px; margin-bottom: 36px; font-size: 22px; color: #9F0251; text-align: center; font-family: 'Century Gothic', Geneva, Arial, sans-serif;">Simplified User Adoption Model</h1>
<div class="gloss" style="font-weight: bold; font-size: 16px; margin-top: 16px; margin-bottom: 24px; color: #E1B378;">Instructions</div>
<p class="text">Abridg employs a proprietary method to proliferate the number of users (accounts) through content distribution. This page allows you to explore the speed of account proliferation according to your assumptions about the behavior of an average Abridg user.
</p>
<p class="text">Example: Peter signs up for an Abridg account. During the next two weeks, he shares content with Paul and Mary. By viewing Peter&rsquo;s content, Paul and Mary become account holders also. Now there are three account holders, and the two additional accounts (Paul and Mary) may each share their own content with other people who will likewise become Abridg members.
</p>
<p class="text">
This kind of proliferation can be modeled simply using two parameters: a proliferation factor <em>n</em>, and a time interval <em>i</em>. The proliferation factor <em>n</em> is the average number of new members (friends) &ldquo;recruited&rdquo; by each existing member in time interval <em>i</em>.
</p>
<p class="text">
In the example above, <strong>n = 2 friends</strong>, and <strong>i = 2 weeks</strong>. Clearly, higher <em>n</em> and lower <em>i</em> produce faster adoption.
</p>
<p class="text">
A more sophisticated model would integrate distribution functions for <em>n</em> and <em>i</em>. It would also reflect the structural dimension of human relationships and would adjust for saturation effects. However, the simplified model, with its scalar (single point) values for <em>n</em> and <em>i</em>, will still be useful if those values are well chosen:
</p>
<form id="chartmodelform" method="post" action="/model_almost2.php" style="margin-top: 40px;">
<table align="center">
<tr style="height: 60px;">
<td style="width: 160px; vertical-align: top; padding-top: 4px;"><label>Proliferation factor <em>n</em></label></td>
<td style="width: 280px; vertical-align: top;">
<input name="Prolif_n" id="Prolif_n" class="textfield" maxlength="5" size="4" onFocus="this.style.background='#FFFF97'" onBlur="this.style.background='white'; document.getElementById('Interval_i').style.background='#FFFF97';" value="<?php if (!empty($_SESSION['Prolif_n'])) echo $_SESSION['Prolif_n']; ?>">&nbsp;<label>friends</label>
<div class="error" id="ProlifnError"><br>Enter an integer or a fractional value as a decimal e.g 1.5<br></div>
<?php if ($_SESSION['MsgProlifn'] != null) { echo $_SESSION['MsgProlifn']; $_SESSION['MsgProlifn']=null; } ?>
<div class="greytextsmall">Enter an integer or fractional value e.g. 1.5</div>
</td>
</tr>
<tr style="height: 40px;">
<td style="vertical-align: top; padding-top: 4px;"><label>Time interval <em>i</em></label></td>
<td style="vertical-align: top;">
<input name="Interval_i" id="Interval_i" class="textfield" maxlength="4" size="4" onFocus="this.style.background='#FFFF97'" onBlur="this.style.background='white';" value="<?php if (!empty($_SESSION['Interval_i'])) echo $_SESSION['Interval_i']; ?>">&nbsp;<label>weeks</label>
<div class="error" id="IntervaliError"><br>Enter an integer or a fractional value as a decimal e.g 1.5<br></div>
<?php if ($_SESSION['MsgIntervali'] != null) { echo $_SESSION['MsgIntervali']; $_SESSION['MsgIntervali']=null; } ?>
<div class="greytextsmall">Enter an integer or fractional value e.g. 1.5</div>
</td>
</tr>
<tr style="vertical-align: middle; height: 50px;">
<td colspan="2" style="text-align: center;">
<label>Linear Scale</label>&nbsp;<input type="radio" name="scale" value="linear" <?php if (!isset($_SESSION['scale']) || $_SESSION['scale'] != 'log') echo 'checked'; ?>>&nbsp;&nbsp;&nbsp;
<input type="radio" name="scale" value="log" <?php if ($_SESSION['scale'] == 'log') echo 'checked'; ?>>&nbsp;<label>Log Scale</label>
</td>
</tr>
<tr>
<td colspan="2" style="text-align: center;"><br>
<input style="margin-bottom: 24px;" type="submit" name="DrawChart" value="Draw Chart" class="buttonstyle" onClick="hideAllErrors(); return checkForm();">
</td>
</tr>
</table>
</form>

<?php
if (isset($DrawChart))
	{
	unset($DrawChart);
	
	/* Begin PHP form validation */

	// Create a session variable for the PHP form validation flag, and initialize it to 'false' i.e. assume it's valid.
	$_SESSION['phpinvalidflag'] = false;

	// Create session variables to hold inline error messages, and initialize them to blank.
	$_SESSION['MsgProlifn'] = null;
	$_SESSION['MsgIntervali'] = null;

	// Seek to validate $Prolif_n
	$illegalCharSet = '/[^0-9\.]+/'; // Reject everything that is neither a digit nor a period.
	$reqdCharSet = '/[0-9]{1,}/';  // At least one numeric.
	if (preg_match($illegalCharSet, $Prolif_n) || !preg_match($reqdCharSet, $Prolif_n))
		{
		$_SESSION['MsgProlifn'] = '<span class="errorphp"><br>Enter an integer or a fractional value as a decimal e.g 1.5</span>';
		$_SESSION['phpinvalidflag'] = true; 
		}

	// Seek to validate $Interval_i
	$illegalCharSet = '/[^0-9\.]+/'; // Reject everything that is neither a digit nor a period.
	$reqdCharSet = '/[0-9]{1,}/';  // At least one numeric.
	if (preg_match($illegalCharSet, $Interval_i) || !preg_match($reqdCharSet, $Interval_i))
		{
		$_SESSION['MsgIntervali'] = '<span class="errorphp"><Enter an integer or a fractional value as a decimal e.g 1.5</span>';
		$_SESSION['phpinvalidflag'] = true; 
		}

	//Now go back to the previous page (createowner.php) and show any PHP inline validation error messages if the $_SESSION['phpinvalidflag'] has been set to true. ... otherwise, proceed to draw the chart using the user's form data.
	if ($_SESSION['phpinvalidflag'])
		{
?>
		<script type='text/javascript' language='javascript'>window.location = '/model_almost2.php';</script>
		<noscript>
<?php
		header("Location: /model_almost2.php");
?>
		</noscript>
		</div>
		</body>
		</html>
<?php
		ob_flush;
		exit;
		}
	else
		{
		// Include the necessary JpGraph files (always jpgraph.php and at least one other for the desired line type [e.g. jpgraph_line.php for a line graph)
		include ("/home/paulme6/public_html/abridg/scripts/jpgraph/src/jpgraph.php");
		include ("/home/paulme6/public_html/abridg/scripts/jpgraph/src/jpgraph_line.php");
		include ("/home/paulme6/public_html/abridg/scripts/jpgraph/src/jpgraph_log.php");

		// Initialize the graph
		$graph = new Graph(800,300,"auto");
		
		// If the user selected the "Log Scale" radio button, set the scale to logarithmic
		if ($scale == 'log') $graph->SetScale("textlog");
		else $graph->SetScale("textlin");


		// Add X-axis labels
		// Create an $xValuesArray and $yValuesArray
		
		// Initialize the value arrays
		$xValuesArray = array(); // initialize the array
		$yValuesArray = array(); // initialize the array
		$xValuesArray[0] = 0;
		$yValuesArray[0] = 1;
		
		$NofXPoints = ceil(52/$Interval_i);
		// Adjust the interval between x-axis labels according to $NofXPoints
		switch (true)
			{
			case ($NofXPoints > 80) :
				$theLabelInterval = 16;
				break;
			case ($NofXPoints > 50 && $NofXPoints <= 80) :
				$theLabelInterval = 8;
				break;
			case ($NofXPoints > 20 && $NofXPoints <= 50) :
				$theLabelInterval = 4;
				break;
			default :
				$NofXPoints = 20; // Bump up $NofXPoints to at least 20. (It could otherwise be smaller if $Interval_i is large e.g. 4 weeks)
				$theLabelInterval = 4;
			}

		for ($count = 1; $count <= $NofXPoints; $count++)
			{
			array_push($xValuesArray, $count * $Interval_i);
			$yValuesArray[$count] = $yValuesArray[$count - 1] + pow($Prolif_n, $count);
			$yValuesArray[$count] = ltrim($yValuesArray[$count]);
			}
			
		$graph->xaxis->SetTextLabelInterval($theLabelInterval);
		$graph->xaxis->SetTickLabels($xValuesArray);
		
		// Add some styling
		$graph->img->SetMargin(150,60,20,40);
		$graph->xaxis->title->Set("Weeks");
//		$graph->yaxis->title->Set("Abridg Users");$graph->title->SetFont(FF_FONT1,FS_BOLD);
		$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
		$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);
		
		$lineplot=new LinePlot($yValuesArray);
		$lineplot->mark->SetType(MARK_IMG_DIAMOND,'red',0.33);

		// Add the plot to the graph
		$graph->Add($lineplot);
		
		// If changing the line's color and/or width, do so after Add'ing the $lineplot (see: http://stackoverflow.com/questions/8299840/jpgraph-lineplot-setweight-will-not-work)
		$lineplot->SetWeight(2); // Two pixels wide ... but I can't get the SetWeight() method to work regardless (and neither can others!)
		$lineplot->SetColor("red");
		
		// Generate a random integer between 1 and 1000, which we'll append to the chart image's filename. We use this trick b/c without it IE (and potentially other browsers) will cache the image and present an "old" version of it rather than the latest version. Using a random number between 1 and 1000, there's a 1/1000 chance that the trick won't work on any given occasion. (No big deal -- user can simply click the 'Create Chart' button again.) Why not make it, say, 1/1000,000? Because that could ultimately lead to 1 million projectionXXXXX.png images getting stored on the server -- which seems an unnecessary amount of storage. Courtesy: http://www.webmasterworld.com/php/3146706.htm
		$rannum = rand(1, 1000);
				
		// Store the graph
		$graph->Stroke("/home/paulme6/public_html/abridg/images/projection$rannum.png");

		// Display the stored image on the screen
?>		
		<div style="text-align: center; margin-top: 40px;">
		<div class="gloss" style="font-variant: small-caps; font-weight: bold; margin-bottom: 12px;">Number of Abridg Accounts Through First <?= $NofXPoints * $Interval_i; ?> Weeks</div>
		<img alt="User Adoption Model" src="/images/projection<?=$rannum; ?>.png">
		</div>
		
		<script type="text/javascript">window.scrollTo(0,1000);</script>
<?php
		}
	}
?>
</div>
</body>
</html>
<?php
ob_end_flush();
?>
