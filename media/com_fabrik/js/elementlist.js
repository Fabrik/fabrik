/**
 * @author Robert
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $A:true, $H:true,unescape:true,Asset:true */

var FbElementList =  new Class({
	
	Extends: FbElement,
	
	type: 'text', // Sub element type
	
	initialize: function (element, options) {
		this.parent(element, options);
		this.addSubClickEvents();
		this._getSubElements();
		if (this.options.allowadd === true && this.options.editable !== false) {
			this.watchAddToggle();
			this.watchAdd();
		}
	},
	
	// Get the sub element which are the checkboxes themselves
	
	_getSubElements: function () {
		var element = this.getElement();
		if (!element) {
			this.subElements = $A();
		} else {
			this.subElements = element.getElements('input');
		}
		return this.subElements;
	},
	
	addSubClickEvents: function () {
		this._getSubElements().each(function (el) {
			el.addEvent('click', function (e) {
				Fabrik.fireEvent('fabrik.element.click', [this, e]);
			});
		});
	},
	
	addNewEvent: function (action, js) {
		if (action === 'load') {
			this.loadEvents.push(js);
			this.runLoadEvent(js);
		} else {
			var subElsName = this.element.id;
			c = this.form.form;
			var delegate = action + ':relay(input[type=' + this.type + '])';
			c.addEvent(delegate, function (event, target) {
				var subEls = document.id(subElsName).getElements('input');
				if (subEls.contains(target)) {
					typeOf(js) === 'function' ? js.delay(0) : eval(js);
				}
			});
		}
	},
	
	checkEnter: function (e) {
		if (e.key === 'enter') {
			e.stop();
			this.startAddNewOption();
		}
	},
	
	startAddNewOption: function () {
		var c = this.getContainer();
		var l = c.getElement('input[name=addPicklistLabel]');
		var v = c.getElement('input[name=addPicklistValue]');
		var label = l.value;
		if (v) {
			val = v.value;
		} else {
			val = label;
		}
		if (val === '' || label === '') {
			alert(Joomla.JText._('PLG_ELEMENT_CHECKBOX_ENTER_VALUE_LABEL'));
		}
		else {
			var r = this.subElements.getLast().findUp('li').clone();
			var i = r.getElement('input');
			i.value = val;
			i.checked = 'checked';
			if (this.type === 'checkbox') {
				
				// Remove the last [*] from the checkbox sub option name (seems only these use incremental []'s)
				var name = i.name.replace(/^(.*)\[.*\](.*?)$/, '$1$2');
				i.name = name + '[' + (this.subElements.length) + ']';
			}
			r.getElement('span').set('text', label);
			r.inject(this.subElements.getLast().findUp('li'), 'after');
			
			var index = 0;
			if (this.type === 'radio') {
				index = this.subElements.length;
			}
			document.id(this.form.form).fireEvent("change", {
				target: $$('input[name='+i.name+']')[index]
			});
            
			this._getSubElements();
			if (v) {
				v.value = '';
			}
			l.value = '';
			this.addNewOption(val, label);
			if (this.mySlider) {
				this.mySlider.toggle();
			}
		}
	},
	
	watchAdd: function () {
		var val;
		if (this.options.allowadd === true && this.options.editable !== false) {
			var c = this.getContainer();
			c.getElements('input[name=addPicklistLabel], input[name=addPicklistValue]').addEvent('keypress', function (e) {
				this.checkEnter(e);
			}.bind(this));
			c.getElement('input[type=button]').addEvent('click', function (e) {
				e.stop();
				this.startAddNewOption();
			}.bind(this));
			document.addEvent('keypress', function (e) {
				if (e.key === 'esc' && this.mySlider) {
					this.mySlider.slideOut();
				}
			}.bind(this));
		}
	}
});