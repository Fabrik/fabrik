var FbBirthday = new Class({
	Extends: FbElement,
	initialize: function (element, options) {
		this.plugin = 'birthday';
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
