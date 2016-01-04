/**
 * Count Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbCount = new Class({
	Extends: FbElement,
	initialize: function (element, options) {
		this.setPlugin('fabrikcount');
		this.parent(element, options);
	}
});