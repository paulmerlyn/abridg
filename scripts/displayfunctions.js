/* These functions -- which control the display of HTML elements (typically div's) -- have been adapted into a toggle capability using functions originally deployed in the nrmedlic site within file dynamicformutility.js */

// Copyright © 2000 by Apple Computer, Inc., All Rights Reserved.
// You may incorporate this Apple sample code into your own code
// without restriction. This Apple sample code has been provided "AS IS"
// and the responsibility for its operation is yours. You may redistribute
// this code, but you are not permitted to redistribute it as
// "Apple sample code" after having made changes.
// Reference http://developer.apple.com/internet/webcontent/dynamicforms.html

function hideElement(elementID)
{
	var elementID;
	changeDiv(elementID, "none");
}

//Function to show (i.e. set the display style property to 'block') of an element (e.g. a div that is referenced by its ID).
function showElement(elementID)
{
	var elementID;
	changeDiv(elementID, "block");
}

function ShowHideToggle(elementID)
{
	var elementID, currentDisplayProperty;
	currentDisplayProperty = getDisplayProperty(elementID);
	if (currentDisplayProperty == 'block') changeDiv(elementID, 'none');
	else changeDiv(elementID, 'block');
}

function changeDiv(the_div,the_change)
{
  var the_style = getStyleObject(the_div);
  if (the_style != false)
  {
    the_style.display = the_change;
  }
}

function getStyleObject(objectId) {
  if (document.getElementById && document.getElementById(objectId)) {
    return document.getElementById(objectId).style;
  } else if (document.all && document.all(objectId)) {
    return document.all(objectId).style;
  } else {
    return false;
  }
}

function getDisplayProperty(objectId)
{
// How to obtain an object's display property, courtesy: http://www.codingforums.com/archive/index.php/t-128247.html
obj=document.getElementById(objectId);
if(obj.currentStyle) return obj.currentStyle.display; // For IE and Opera
else return getComputedStyle(obj,'').getPropertyValue('display'); // For Firefox
}