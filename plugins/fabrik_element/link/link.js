/**
 * Link Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

FbLink = new Class({

	Extends: FbElementList,
	initialize: function (element, options) {
		this.setPlugin('fabrikLink');
		this.parent(element, options);
		this.subElements = this._getSubElements();
	},

	update: function (val) {
		this.getElement();
		var subs = this.element.getElements('.fabrikinput');
		if (typeOf(val) === 'object') {
			subs[0].value = val.label;
			subs[1].value = val.link;
		} else {
			subs.each(function (i) {
				i.value = val;
			});
		}
	},

	getValue : function () {
		var s = this._getSubElements();
		var a = [];
		s.each(function (v) {
			a.push(v.get('value'));
		});
		return a;
	}

});
