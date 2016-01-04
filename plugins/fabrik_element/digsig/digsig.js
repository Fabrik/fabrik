/**
 * Slider Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbDigsig = new Class({
	Extends: FbElement,
	initialize: function (element, options) {
		this.setPlugin('digsig');
		this.parent(element, options);
		if (typeof jQuery !== 'undefined') {
			jQuery.noConflict();
		}
		if (this.options.editable === true) {
			if (typeOf(this.element) === 'null') {
				fconsole('no element found for digsig');
				return;
			}
			var oc_options = {
				defaultAction: 'drawIt',
				lineTop: '100',
				output: '#' + this.options.sig_id,
				canvas: '#' + this.element.id + '_oc_pad',
				drawOnly: true
			};
			jQuery('#' + this.element.id).signaturePad(oc_options).regenerate(this.options.value); 
		}
		else {
			jQuery('#' + this.options.sig_id).signaturePad({displayOnly: true}).regenerate(this.options.value);		
		}
	},

	getValue: function () {
		return this.options.value;
	},


	addNewEvent: function (action, js) {
		if (action === 'load') {
			this.loadEvents.push(js);
			this.runLoadEvent(js);
			return;
		}
		if (action === 'change') {
			this.changejs = js;
		}
	}
});