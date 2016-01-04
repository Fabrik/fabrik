/**
 * YouTube Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbSpotify = new Class({
	Extends: FbElement,
	initialize: function (element, options) {
		this.setPlugin('spotify');
		this.parent(element, options);
	}
});