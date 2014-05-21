/**
 * Birthday Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbBirthday = new Class({
	Extends: FbElement,
	initialize: function (element, options) {
		this.plugin = 'birthday';
		this.default_sepchar = '-';
		this.parent(element, options);
	},

	getValue: function () {
		var v = [];
		if (!this.options.editable) {
			return this.options.value;
		}
		this.getElement();

		this._getSubElements().each(function (f) {
			v.push(f.get('value'));
		});
		return v;
	},

	update: function (val) {
		if (typeOf(val) === 'string') {
			var sepchar = this.options.separator;
			if (val.indexOf(sepchar) === -1) {
				sepchar = this.default_sepchar;
			}
			val = val.split(sepchar);
		}
		this._getSubElements().each(function (f, x) {
			f.value = val[x];
		});
	}
});
