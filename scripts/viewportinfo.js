/*
This Javascript code simply obtains the width and height of the viewport and assigns those values to variables viewportwidth and viewportheight respectively. It's linked to by index.php, which places those values into hidden form fields as part of the account holder authentication form. Upon posting to the server (index.php is its own action script), they then get stored as session variables $_SESSION['VPwidth'] and $_SESSION['VPheight'].
	Note: for a terrific two-part tutorial on viewports (desktop and mobile), see: http://www.quirksmode.org/mobile/viewports.html
*/
var viewportwidth;
var viewportheight;
 
// the more standards compliant browsers (mozilla/netscape/opera/IE7) use window.innerWidth and window.innerHeight
 
if (typeof window.innerWidth != 'undefined')
	{
	viewportwidth = window.innerWidth,
	viewportheight = window.innerHeight
	}
 
// IE6 in standards compliant mode (i.e. with a valid doctype as the first line in the document)

else if (typeof document.documentElement != 'undefined' && typeof document.documentElement.clientWidth != 'undefined' && document.documentElement.clientWidth != 0)
	{
	viewportwidth = document.documentElement.clientWidth,
	viewportheight = document.documentElement.clientHeight
	}
 
// older versions of IE
 
else
	{
	viewportwidth = document.getElementsByTagName('body')[0].clientWidth,
	viewportheight = document.getElementsByTagName('body')[0].clientHeight
	}
//-->
