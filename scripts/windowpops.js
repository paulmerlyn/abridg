/*
Legend of parameters for window.open() method used in poptasticDIY() function:
height		Sets the height of the window in pixels. The minimum value is 150, and specifies the minimum height of the browser content area. 
width 		Specifies the width of the window in pixels.
top			Specifies the top position, in pixels. This value is relative to the upper-left corner of the screen. The value must be greater than or equal to 0.
screeny		This allows a new window to be created at a specified number of pixels from the top of the screen. (Netscape version of 'top')
left		Specifies the left position, in pixels. This value is relative to the upper-left corner of the screen. The value must be greater than or equal to 0.
screenx		This allows a new window to be created at a specified number of pixels from the left side of the screen. (Netscape version of 'left')
scrollbars 	Enable the scrollbars if the document is bigger than the window
status  	The status bar at the bottom of the window.
toolbar 	The standard browser toolbar, with buttons such as Back and Forward.
location 	The Location entry field where you enter the URL.
menubar 	The menu bar of the window
resizable 	Allow/Disallow the user to resize the window.
*/

var newwindow;

function wintasticsecond(url)
{
	newwindow=window.open(url,'secondwindow','height=500,width=800,top=50,left=60,resizable=yes,scrollbars=yes,toolbar=yes,menubar=yes,location=yes,status=yes');
	if (window.focus) {newwindow.focus()}
return;
}

function poptasticDIY(url, HT, WD, topPos, scrY, leftPos, scrX, sclBars) // This general-purpose window function gets used by mediationcareer.php to display waitlist.php and by ipwirescrossed_slave.php to display ipwirescrossedpreview.php.
{
	var myParams, url, HT, WD, topPos, scrY, leftPos, scrX, sclBars;
	myParams = 'height=' + HT + ',width=' + WD + ',top=' + topPos + ',screeny=' + scrY + ',left=' + leftPos + ',screenx=' + scrX + ',alwaysRaised=yes,scrollbars=' +sclBars + ',menubar=no,resizable=no,toolbar=no,location=no,status=no,titlebar=no,z-lock=yes';
	thepop=window.open(url,'showmeontop',myParams);
	if (window.focus) {thepop.focus()}
return;
}

function poptasticCloseIt()
{
	thepop = window.open("");
	thepop.close();
	return;
}
