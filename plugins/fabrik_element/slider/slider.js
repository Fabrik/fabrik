/**
 * Slider Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbSlider = new Class({
	Extends: FbElement,
	initialize: function (element, options) {
		this.setPlugin('slider');
		this.parent(element, options);
		this.makeSlider();
	},
	
	makeSlider: function () {
		var isNull = false;
		if (typeOf(this.options.value) === 'null' || this.options.value === '') {
			this.options.value = '';
			isNull = true;
		}
		this.options.value = this.options.value === '' ? '' : this.options.value.toInt();
		var v = this.options.value;
		if (this.options.editable === true) {
			if (typeOf(this.element) === 'null') {
				fconsole('no element found for slider');
				return;
			}
			this.output = this.element.getElement('.fabrikinput');
			this.output2 = this.element.getElement('.slider_output');
	
			this.output.value = this.options.value;
			this.output2.set('text', this.options.value);
	
			this.mySlide = new Slider(
				this.element.getElement('.fabrikslider-line'),
				this.element.getElement('.knob'),
				{
					onChange: function (pos) {
						this.output.value = pos;
						this.options.value = pos;
						this.output2.set('text', pos);
						this.output.fireEvent('blur', new Event.Mock(this.output, 'blur'));
						this.callChange();
					}.bind(this),
					onComplete: function (pos) {
						// Fire for validations
						this.output.fireEvent('blur', new Event.Mock(this.output, 'change'));
						this.element.fireEvent('change', new Event.Mock(this.element, 'change'));
					}.bind(this),
					steps : this.options.steps
				}
			).set(v);
	
			if (isNull) {
				this.output.value = '';
				this.output2.set('text', '');
				this.options.value = '';
			}
			this.watchClear();
		}
	},
	
	watchClear: function () {
		this.element.addEvent('click:relay(.clearslider)', function (e, target) {
			e.preventDefault();
			this.mySlide.set(0);
			this.output.value = '';
			this.output.fireEvent('blur', new Event.Mock(this.output, 'change'));
			this.output2.set('text', '');
		}.bind(this));
	},

	getValue: function () {
		return this.options.value;
	},

	callChange: function () {
		typeOf(this.changejs) === 'function' ? this.changejs.delay(0) :	eval(this.changejs);
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
	},
	
	cloned: function (c) {
		delete this.mySlide;
		this.makeSlider();
		this.parent(c);
	}

});
