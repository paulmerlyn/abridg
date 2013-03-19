/*
JS to create a div-based (cf. browser window-based) popup courtesy: http://www.astral-consultancy.co.uk/cgi-bin/hunbug/doco.cgi?11540. (Note: I originally tried to make this div movable/draggable via http://waseemblog.com/42/movable-div-using-javascript.html, but I couldn't get it to work reliably in IE7 and earlier.)
    The div encapsulates a selector panel comprising two tabs, which is itself constructed from third-party code courtesy Adobe Spry: http://labs.adobe.com/technologies/spry/articles/tabbed_panel and http://labs.adobe.com/technologies/spry/samples/tabbedpanels/tabbed_panel_sample.htm (although the CSS and JS for the 2-tab selector panel is stored separately in files SpryTabbedPanels.css and SpryTabbedPanels.js respectively.																																																																				    Note that widgetpopupdiv.css is accompanied by widgetpopup.js
*/

function showHidePopupDiv(id)
	{
	// id - Object to display.
	var obj, thedisp;
	obj = document.getElementById(id);
	thedisp = obj.style.display;
	if (thedisp == 'block') obj.style.display = 'none'; else obj.style.display = 'block';
	}