/*
JS files from two different sources to create a div-based (cf. browser window-based) popup that is also movable: 
1. Div-based popup code courtesy: http://www.astral-consultancy.co.uk/cgi-bin/hunbug/doco.cgi?11540
2. Movable div-based code courtesy: http://waseemblog.com/42/movable-div-using-javascript.html (note: "testdiv" in the third-party code is renamed "popup1" to conform to the div id given in (1.) above.
    The div encapsulates a selector panel comprising two tabs, which is itself constructed from third-party code courtesy Adobe Spry: http://labs.adobe.com/technologies/spry/articles/tabbed_panel and http://labs.adobe.com/technologies/spry/samples/tabbedpanels/tabbed_panel_sample.htm (although the CSS and JS for the 2-tab selector panel is stored separately in files SpryTabbedPanels.css and SpryTabbedPanels.js respectively.																																																																				    Note that movablepopupdiv.css is accompanied by movablepopup.js
*/

// First we have the JS for the div-based popup:
function showHidePopupDiv(id)
	{
	// id - Object to display.
	var obj, thedisp;
	obj = document.getElementById(id);
	thedisp = obj.style.display;
	if (thedisp == 'block') obj.style.display = 'none'; else obj.style.display = 'block';
	}

// Second we have the JS for making a div movable:
function getID(id)
{
	return document.getElementById(id);
}

// Global object to hold drag information.
var dragObj = new Object();

function dragStart(event, id) {
  var x, y;
  dragObj.elNode = getID(id);
  // Get cursor position with respect to the page.
  try {
    x = window.event.clientX + document.documentElement.scrollLeft
      + document.body.scrollLeft;
    y = window.event.clientY + document.documentElement.scrollTop
      + document.body.scrollTop;
  }
  catch (e) {
    x = event.clientX + window.scrollX;
    y = event.clientY + window.scrollY;
  }
// Save starting positions of cursor and element.
 dragObj.cursorStartX = x; // Formerly, the number was replaced by "x" (no quotes!)
  dragObj.cursorStartY = y; // Formerly, the number was replaced by "y" (no quotes!)
  dragObj.elStartLeft  = parseInt(dragObj.elNode.style.left, 10);
  dragObj.elStartTop   = parseInt(dragObj.elNode.style.top,  10);
  if (isNaN(dragObj.elStartLeft)) dragObj.elStartLeft = 0;
  if (isNaN(dragObj.elStartTop))  dragObj.elStartTop  = 0;
  // Capture mousemove and mouseup events on the page.
  try {
    document.attachEvent("onmousemove", dragGo);
    document.attachEvent("onmouseup",   dragStop);
    window.event.cancelBubble = true;
    window.event.returnValue = false;
  }
  catch (e) {
    document.addEventListener("mousemove", dragGo,   true);
    document.addEventListener("mouseup",   dragStop, true);
    event.preventDefault();
  }
}
function dragGo(event) {
 var x, y;
// Get cursor position with respect to the page.
try  {
    x = window.event.clientX + document.documentElement.scrollLeft
      + document.body.scrollLeft;
    y = window.event.clientY + document.documentElement.scrollTop
      + document.body.scrollTop;
  }
  catch (e) {
    x = event.clientX + window.scrollX;
    y = event.clientY + window.scrollY;
  }
  // Move drag element by the same amount the cursor has moved.
  var drLeft = (dragObj.elStartLeft + x - dragObj.cursorStartX);
  var drTop = (dragObj.elStartTop  + y - dragObj.cursorStartY);
  if (drLeft > 0)
  {
     dragObj.elNode.style.left = drLeft  + "px";
  }
  else
  {
	dragObj.elNode.style.left = "1px";
  }
  if (drTop > 0)
  {
     dragObj.elNode.style.top  = drTop + "px";
  }
  else
  {
	dragObj.elNode.style.top  = "1px";
  }
  try {
    window.event.cancelBubble = true;
    window.event.returnValue = false;
  }
  catch(e){
    event.preventDefault();
  }
}
function dragStop(event) {
  // Stop capturing mousemove and mouseup events.
  try {
    document.detachEvent("onmousemove", dragGo);
    document.detachEvent("onmouseup",   dragStop);
  }
  catch (e) {
    document.removeEventListener("mousemove", dragGo,   true);
    document.removeEventListener("mouseup",   dragStop, true);
  }
}