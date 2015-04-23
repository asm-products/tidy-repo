function DVKSS() {

	function addEvent(element, eventName, callback) {
		if (element.addEventListener) {
			element.addEventListener(eventName, callback, false);
		} else {
			element.attachEvent("on" + eventName, callback);
		}
	}

	// init
	function init() {
		var links = document.querySelectorAll('.social a');
		for (var i = 0; i < links.length; i++) {
			DVKSS.addEvent(links[i], 'click', DVKSS.popup)
		}
	}

	// functions
	function openPopup(e) {

		var top = (screen.availHeight - 500) / 2;
		var left = (screen.availWidth - 500) / 2;

		var popup = window.open(
			this.href,
			'social',
			'width=550,height=420,left='+ left +',top='+ top +',location=0,menubar=0,toolbar=0,status=0,scrollbars=1,resizable=1'
		);

		if(popup) {
			popup.focus();
			e.preventDefault();
			return false;
		}

		return true;
	}

	// public stuff
	return {
		init: init,
		popup: openPopup,
		addEvent: addEvent
	}
}

var DVKSS = new DVKSS();
DVKSS.addEvent(window, 'load', DVKSS.init)