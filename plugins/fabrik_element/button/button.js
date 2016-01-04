/**
 * Button Element
 * 
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license: GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbButton = new Class({
	Extends: FbElement,
	initialize: function (element, options) {
		this.setPlugin('fabrikButton');
		this.parent(element, options);
	},

	addNewEventAux: function (action, js) {
		this.element.addEvent(action, function (e) {
			
			// Unlike element addNewEventAux we need to stop the event otherwise the form is submitted
			if (e) {
				e.stop();
			}
			typeOf(js) === 'function' ? js.delay(0, this, this) : eval(js);
		}.bind(this));
	}
});