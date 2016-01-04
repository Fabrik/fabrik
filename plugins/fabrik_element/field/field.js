/**
 * Field Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

function geolocateLoad () {
	if (document.body) {
		window.fireEvent('google.geolocate.loaded');
	} else {
		console.log('no body');
	}
}

var FbField = new Class({
	Extends: FbElement,
	initialize: function (element, options) {
		this.setPlugin('fabrikfield');
		this.parent(element, options);
		/*
		 * $$$ hugh - testing new masking feature, uses this jQuery widget:
		 * http://digitalbush.com/projects/masked-input-plugin/
		 */
		if (this.options.use_input_mask) {
			if (this.options.input_mask_definitions !== '') {
				var definitions = JSON.parse(this.options.input_mask_definitions);
				$H(definitions).each(function (v, k) {
					jQuery.mask.definitions[k] = v;
				});
			}
			jQuery('#' + element).mask(this.options.input_mask);
		}
		if (this.options.geocomplete) {
			this.gcMade = false;
			this.loadFn = function () {
				if (this.gcMade === false) {
					jQuery('#' + this.element.id).geocomplete();
					this.gcMade = true;
				}
			}.bind(this);
			window.addEvent('google.geolocate.loaded', this.loadFn);
			Fabrik.loadGoogleMap(false, 'geolocateLoad');
		}
	},

	select: function () {
		var element = this.getElement();
		if (element) {
			this.getElement().select();
		}
	},

	focus: function () {
		var element = this.getElement();
		if (element) {
			this.getElement().focus();
		}
	},
	
	cloned: function (c) {
		if (this.options.use_input_mask) {
			var element = this.getElement();
			if (element) {
				if (this.options.input_mask_definitions !== '') {
					var definitions = JSON.parse(this.options.input_mask_definitions);
					$H(definitions).each(function (v, k) {
						jQuery.mask.definitions[k] = v;
					});
				}
				jQuery('#' + element.id).mask(this.options.input_mask);
			}
		}
		if (this.options.geocomplete) {
			var element = this.getElement();
			if (element) {
				jQuery('#' + element.id).geocomplete();
			}
		}
		this.parent(c);
	}
	
});