/**
 * Keynav
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $H:true,unescape:true */

var KeyNav = new Class({
	initialize : function () {
		window.addEvent('keypress', function (e) {
			switch (e.code) {
			case 37: //left
			case 38: //up
			case 39: //right
			case 40: //down
				Fabrik.fireEvent('fabrik.keynav', [e.code, e.shift]);
				e.stop();
				break;
			}
		});
	}
});

var FabrikKeyNav = new KeyNav();