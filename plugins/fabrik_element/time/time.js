/**
 * Time Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbTime = new Class({
	Extends: FbElement,
	initialize: function (element, options) {
		this.setPlugin('time');
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
			val = val.split(this.options.separator);
		}
		this._getSubElements().each(function (f, x) {
			f.value = val[x];
		});
	}
});