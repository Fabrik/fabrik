/**
 * ViewLevel Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbViewlevel = new Class({

	Extends: FbElement,

	initialize: function (element, options) {
		this.setPlugin('fabrikviewlevel');
		this.parent(element, options);
	}
});