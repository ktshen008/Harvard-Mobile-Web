var currentTab;

function showTab(strID, objTrigger) {
// Displays the tab with ID strID
	var objTab = document.getElementById(strID);
	if(objTab) {
		show(strID);
		if(currentTab && (currentTab != objTab)) {
			hide(currentTab.id);
			currentTab.style.display = "none";
		}
	}
	currentTab = objTab; // Remember which is the currently displayed tab
	
	// Set the clicked tab to look current
	var objTabs = document.getElementById("tabs");
	var arrTabs = objTabs.getElementsByTagName("li");
	if(objTrigger) {
		for(var i=0; i<arrTabs.length; i++) {
			arrTabs[i].className="";
		}
		var objTriggerTab = objTrigger.parentNode;
		if(objTriggerTab) {
			objTriggerTab.className="active";
		}
	} 
	onDOMChange();
}

function rotateScreen() {
  // Switch stylesheet and viewport based on screen orientation
	switch(window.orientation) {
		case 0:
		case 180:
        setOrientation('portrait');
		    break;

		case -90:
		case 90:
        setOrientation('landscape');
		    break;

    default: 
        setOrientation('portrait');
		    break;
	}
	setTimeout(scrollToTop, 500);
}

function setOrientation(orientation) {
    document.getElementsByTagName("body")[0].className = orientation;
}


function showLoadingMsg(strID) {
// Show a temporary loading message in the element with ID strID
	var objToStuff = document.getElementById(strID);
	if(objToStuff) {
		objToStuff.style.height = objToStuff.offsetHeight + "px";
		objToStuff.innerHTML = "<div class=\"loading\"><img src=\"../Webkit/images/loading.gif\" width=\"27\" height=\"21\" alt=\"\" align=\"absmiddle\" />Loading data...</div >";
	}
	onDOMChange();
}

function hide(strID) {
// Hides the object with ID strID 
	var objToHide = document.getElementById(strID);
	if(objToHide) {
		objToHide.style.display = "none";
	}
	
	onDOMChange();
}

function show(strID) {
// Displays the object with ID strID 
	var objToHide = document.getElementById(strID);
	if(objToHide) {
		objToHide.style.display = "block";
	}
	
	onDOMChange();
}

function showHideFull(objContainer) {
	var strClass = objContainer.className;
	if(strClass.indexOf("collapsed") > -1) {
		strClass = strClass.replace("collapsed","expanded");
	} else {
		strClass = strClass.replace("expanded","collapsed");
	}
	objContainer.className = strClass;
	objContainer.blur();
	
	onDOMChange();
}

function clearField(objField,strDefault) {
// Clears the placeholder text in an input field if it matches the default string - fixes a bug in Android
	if((objField.value==strDefault) || (objField.value=="")) {
		objField.value="";
	}
}

// Android doesn't respond to onfocus="clearField(...)" until the 
// input field loses focus
function androidPlaceholderFix(searchbox) {
    // this forces the search box to display the empty string
    // instead of the place holder when the search box takes focus
    if (searchbox.value == "") {
        searchbox.value = "";
    }
}

function getCookie(name) {
  var cookie = document.cookie;
  var result = "";
  var start = cookie.indexOf(name + "=");
  if (start > -1) {
    start += name.length + 1;
    var end = cookie.indexOf(";", start);
    if (end < 0) {
      end = cookie.length;
    }
    result = unescape(cookie.substring(start, end));
  }
  return result;
}

function setCookie(name, value, expireseconds, path) {
  var exdate = new Date();
  exdate.setTime(exdate.getTime() + (expireseconds * 1000));
  var exdateclause = (expireseconds == 0) ? "" : "; expires=" + exdate.toGMTString();
  var pathclause = (path == null) ? "" : "; path=" + path;
  document.cookie = name + "=" + value + exdateclause + pathclause;
}

function getCookieArrayValue(name) {
  var value = getCookie(name);
  if (value && value.length) {
    return value.split(',');
  } else {
    return new Array();
  }
}

function setCookieArrayValue(name, values, expireseconds, path) {
  var value = '';
  if (values && values.length) {
    value = values.join(',');
  }
  setCookie(name, value, expireseconds, path);
}

// Share-related functions
function showShare() {
	document.getElementById("sharesheet").style.display="block";
	document.addEventListener('touchmove', doNotScroll, true);
}
function hideShare() {
	document.getElementById("sharesheet").style.display="none";
	document.removeEventListener('touchmove', doNotScroll, true);
}
function doNotScroll( event ) {
	event.preventDefault(); event.stopPropagation();
}
