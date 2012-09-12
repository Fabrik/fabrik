var FbDropdown = new Class({
	Extends: FbElement,
	initialize: function (element, options) {
		this.plugin = 'fabrikdropdown';
		this.parent(element, options);
		if (this.options.allowadd === true && this.options.editable !== false) {
			this.watchAddToggle();
			this.watchAdd();
		}
	},
	
	watchAddToggle : function () {
		var c = this.getContainer();
		var d = c.getElement('div.addoption');
	
		var a = c.getElement('.toggle-addoption');
		if (this.mySlider) {
			//copied in repeating group so need to remove old slider html first
			var clone = d.clone();
			var fe = c.getElement('.fabrikElement');
			d.getParent().destroy();
			fe.adopt(clone);
			d = c.getElement('div.addoption');
			d.setStyle('margin', 0);
		}
		this.mySlider = new Fx.Slide(d, {
			duration: 500
		});
		this.mySlider.hide();
		a.addEvent('click', function (e) {
			e.stop();
			this.mySlider.toggle();
		}.bind(this));
	},
	
	watchAdd: function () {
		var val;
		if (this.options.allowadd === true && this.options.editable !== false) {
			var id = this.element.id;
			var c = this.getContainer();
			c.getElement('input[type=button]').addEvent('click', function (e) {
				var l = c.getElement('input[name=addPicklistLabel]');
				var v = c.getElement('input[name=addPicklistValue]');
				var label = l.value;
				if (v) {
					val = v.value;
				} else {
					val = label;
				}
				if (val === '' || label === '') {
					alert(Joomla.JText._('PLG_ELEMENT_DROPDOWN_ENTER_VALUE_LABEL'));
				}
				else {
					var opt = new Element('option', {
						'selected': 'selected',
						'value': val
					}).set('text', label).inject(document.id(this.element.id));
					e.stop();
					if (v) {
						v.value = '';
					}
					l.value = '';
					this.addNewOption(val, label);
					document.id(this.element.id).fireEvent('change', {stop:$empty});
					if (this.mySlider) {
						this.mySlider.toggle();
					}
				}
			}.bind(this));
		}
	},
	
	getValue: function () {
		if (!this.options.editable) {
			return this.options.value;
		}
		if (typeOf(this.element.get('value')) === 'null') {
			return '';
		}
		return this.element.get('value');
	},
	
	reset: function ()
	{
		var v = this.options.defaultVal;
		this.update(v);
	},
	
	update: function (val) {
		var opts = [];
		if  ((typeOf(val) === 'string') && (JSON.validate(val))) {
			val = JSON.decode(val);
		}
		if (typeOf(val) === 'null') {
			val = [];
		}
		
		this.getElement();
		if (typeOf(this.element) === 'null') {
			return;
		}
		this.options.element = this.element.id;
		if (!this.options.editable) {
			this.element.set('html', '');
			var h = $H(this.options.data);
			val.each(function (v) {
				this.element.innerHTML += h.get(v) + "<br />";	
			}.bind(this));
			return;
		}
		opts = this.element.getElements('option');
		if (typeOf(val) === 'number') {
			//numbers dont have indexOf() methods so ensure they are strings
			val = val.toString(); 
		}
		for (var i = 0; i < opts.length; i++) {
			if (val.indexOf(opts[i].value) !== -1) {
				opts[i].selected = true;
			} else {
				opts[i].selected = false;
			}
		}
		this.watchAdd();
	},
	
	cloned : function ()
	{
		if (this.options.allowadd === true && this.options.editable !== false) {
			this.watchAddToggle();
			this.watchAdd();
		}
	}
	
});