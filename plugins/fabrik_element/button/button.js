/**
 * Button Element
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license: GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'fab/element'], function (jQuery, FbElement) {
	window.FbButton = new Class({
		Extends   : FbElement,
		initialize: function (element, options) {
			this.setPlugin('fabrikButton');
			this.parent(element, options);
		},

		addNewEventAux: function (action, js) {
			var self = this;
			jQuery(this.element).on(action, function (e) {

				// Unlike element addNewEventAux we need to stop the event otherwise the form is submitted
				if (e) {
					e.stopPropagation();
				}
				if (jQuery.type(js) === 'function') {
					js.delay(0, self, self);
				}
				else {
					js = js.replace(/\bthis\b/g, 'self');
					eval(js);
				}
			});
		}
	});

	return window.FbButton;
});