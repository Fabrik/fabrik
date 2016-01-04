/**
 * Folder Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbFolder = new Class({
	Extends: FbElement,
	initialize: function (element, options) {
		this.setPlugin('fabrikfolder');
		this.parent(element, options);
	}
});