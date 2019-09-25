/**
 * Lockrow Element
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license: GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'fab/element'], function (jQuery, FbElement) {
	window.FbLockrow = new Class({

		Extends: FbElement,

		initialize: function (element, options) {
			this.parent(element, options);
			this.plugin = 'FbLockrow';
			this.setOptions(element, options);
		}
	});

	return window.FbLockrow;
});