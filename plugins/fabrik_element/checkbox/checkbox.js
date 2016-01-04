/**
 * Checkbox Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

FbCheckBox = new Class({
	Extends: FbElementList,

	type: 'checkbox', // Sub element type

	initialize: function (element, options) {
		this.setPlugin('fabrikcheckbox');
		this.parent(element, options);
		this._getSubElements();
	},

	watchAddToggle: function () {
		var c = this.getContainer();
		var d = c.getElement('div.addoption');
		var a = c.getElement('.toggle-addoption');
		if (this.mySlider) {
			// Copied in repeating group so need to remove old slider html first
			var clone = d.clone();
			var fe = c.getElement('.fabrikElement');
			d.getParent().destroy();
			fe.adopt(clone);
			d = c.getElement('div.addoption');
			d.setStyle('margin', 0);
		}
		this.mySlider = new Fx.Slide(d, {
			duration : 500
		});
		this.mySlider.hide();
		a.addEvent('click', function (e) {
			e.stop();
			this.mySlider.toggle();
		}.bind(this));
	},

	getValue: function () {
		if (!this.options.editable) {
			return this.options.value;
		}
		var ret = [];
		if (!this.options.editable) {
			return this.options.value;
		}
		this._getSubElements().each(function (el) {
			if (el.checked) {
				ret.push(el.get('value'));
			}
		});
		return ret;
	},

	numChecked: function () {
		return this._getSubElements().filter(function (c) {
			return c.checked;
		}).length;
	},

	update: function (val) {
		this.getElement();
		if (typeOf(val) === 'string') {
			val = val === '' ? [] : JSON.decode(val);
		}
		if (!this.options.editable) {
			this.element.innerHTML = '';
			if (val === '') {
				return;
			}
			var h = $H(this.options.data);
			val.each(function (v) {
				this.element.innerHTML += h.get(v) + "<br />";
			}.bind(this));
			return;
		}
		this._getSubElements();
		this.subElements.each(function (el) {
			var chx = false;
			val.each(function (v) {
				if (v === el.value) {
					chx = true;
				}
			}.bind(this));
			el.checked = chx;
		}.bind(this));
	},

	cloned: function (c) {
		if (this.options.allowadd === true && this.options.editable !== false) {
			this.watchAddToggle();
			this.watchAdd();
		}
		this.parent(c);
	}

});
