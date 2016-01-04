/**
 * Colour Picker Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var SliderField = new Class({
	initialize : function (field, slider) {
		this.field = document.id(field);
		this.slider = slider;
		this.field.addEvent("change", function (e) {
			this.update(e);
		}.bind(this));
	},

	destroy : function () {
		this.field.removeEvent("change", function (e) {
			this.update(e);
		}.bind(this));
	},

	update : function () {
		if (!this.options.editable) {
			this.element.set('html', val);
			return;
		}
		this.slider.set(this.field.value.toInt());
	}
});

var ColourPicker = new Class({

	Extends: FbElement,

	options: {
		red: 0,
		green: 0,
		blue: 0,
		value: [0, 0, 0, 1],
		showPicker: true,
		swatchSizeWidth: '10px',
		swatchSizeHeight: '10px',
		swatchWidth: '160px'
	},

	initialize: function (element, options) {
		this.setPlugin('colourpicker');
		if (typeOf(options.value) === 'null' || options.value[0] === 'undefined') {
			options.value = [0, 0, 0, 1];
		}

		this.parent(element, options);
		options.outputs = this.outputs;
		this.element = document.id(element);
		this.ini();
	},
	
	ini: function () {
		this.options.callback = function (v, caller) {
			v = this.update(v);
			if (caller !== this.grad && this.grad) {
				this.grad.update(v);
			}
		}.bind(this);
		this.widget = this.element.getParent('.fabrikSubElementContainer').getElement('.colourpicker-widget');
		this.setOutputs();
		var d = new Drag.Move(this.widget, {'handle': this.widget.getElement('.draggable')});

		if (this.options.showPicker) {
			this.createSliders(this.strElement);
		}
		this.swatch = new ColourPickerSwatch(this.options.element, this.options, this);
		this.widget.getElement('#' + this.options.element + '-swatch').empty().adopt(this.swatch);
		this.widget.hide();

		if (this.options.showPicker) {
			this.grad = new ColourPickerGradient(this.options.element, this.options, this);
			this.widget.getElement('#' + this.options.element + '-picker').empty().adopt(this.grad.square);
		}
		this.update(this.options.value);

		var close = this.widget.getElement('.modal-header a');
		if (close) {
			close.addEvent('click', function (e) {
				e.stop();
				this.widget.hide();
			}.bind(this));
		}
	},

	cloned: function (c) {
		this.parent(c);
		
		// Recreate the tabs
		var widget = this.element.getParent('.fabrikSubElementContainer').getElement('.colourpicker-widget'),
		panes = widget.getElements('.tab-pane'),
		tabs = widget.getElements('a[data-toggle=tab]');
		tabs.each(function (tab) {
			var href = tab.get('href').split('-');
			var name = href[0].split('_');
			name[name.length - 1] = c;
			name = name.join('_');
			name += '-' + href[1];
			tab.href = name;
		});
		
		panes.each(function (tab) {
			var href = tab.get('id').split('-');
			var name = href[0].split('_');
			name[name.length - 1] = c;
			name = name.join('_');
			name += '-' + href[1];
			tab.id = name;
		});
		tabs.each(function (tab) {
			tab.addEvent('click', function (e) {
				e.stop();
				jQuery(tab).tab('show');
			});
		});
		
		// Initialize the widget
		this.ini();
	},

	setOutputs: function (output) {
		this.outputs = {};
		this.outputs.backgrounds = this.getContainer().getElements('.colourpicker_bgoutput');
		this.outputs.foregrounds = this.getContainer().getElements('.colourpicker_output');

		this.outputs.backgrounds.each(function (i) {

			// Copy group, delete group add group - set outputs seems to be called twice
			i.removeEvents('click');
			i.addEvent('click', function (e) {
				this.toggleWidget(e);
			}.bind(this));

		}.bind(this));

		this.outputs.foregrounds.each(function (i) {
			i.removeEvents('click');
			i.addEvent('click', function (e) {
				this.toggleWidget(e);
			}.bind(this));

		}.bind(this));
	},

	createSliders: function (element) {
		this.sliderRefs = [];

		// Create the table to hold the scroller
		this.table = new Element('table');
		this.tbody = new Element('tbody');
		this.createColourSlideHTML(element, 'red', 'Red:', this.options.red);
		this.createColourSlideHTML(element, 'green', 'Green:', this.options.green);
		this.createColourSlideHTML(element, 'blue', 'Blue:', this.options.blue);
		this.table.appendChild(this.tbody);
		this.widget.getElement('.sliders').empty().appendChild(this.table);

		Fabrik.addEvent('fabrik.colourpicker.slider', function (o, col, pos) {
			if (this.sliderRefs.contains(o.element.id)) {
				this.options.colour[col] = pos;
				this.update(this.options.colour.red + ',' + this.options.colour.green + ',' + this.options.colour.blue);
			}

		}.bind(this));
		// this makes the class update when someone enters a value into

		this.redField.addEvent("change", function (e) {
			this.updateFromField(e, 'red');
		}.bind(this));

		this.greenField.addEvent("change", function (e) {
			this.updateFromField(e, 'green');
		}.bind(this));

		this.blueField.addEvent("change", function (e) {
			this.updateFromField(e, 'blue');
		}.bind(this));
	},

	createColourSlideHTML: function (element, colour, label, value) {

		var sliderField = new Element('input.input-mini input ' + colour + 'SliderField', {
			'type': 'text',
			'id': element + colour + 'redField',
			'size': '3',
			'value': value
		});

		var tds = [new Element('td').set('text', label), new Element('td').adopt(sliderField)];
		var tr1 = new Element('tr').adopt(tds);

		this.tbody.appendChild(tr1);
		this[colour + "Field"] = sliderField;
	},

	updateAll : function (red, green, blue) {
		red = red ? red.toInt() : 0;
		green = green ? green.toInt() : 0;
		blue = blue ? blue.toInt() : 0;
		
		if (this.options.showPicker) {
			this.redField.value = red;
			this.greenField.value = green;
			this.blueField.value = blue;
		}
		
		this.options.colour.red = red;
		this.options.colour.green = green;
		this.options.colour.blue = blue;
		this.updateOutputs();
	},

	updateOutputs : function () {
		var c = new Color([this.options.colour.red, this.options.colour.green, this.options.colour.blue, 1]);
		this.outputs.backgrounds.each(function (output) {
			output.setStyle('background-color', c);
		});
		this.outputs.foregrounds.each(function (output) {
			output.setStyle('background-color', c);
		});
		if (c.red) {
			this.element.value = c.red + ',' + c.green + ',' + c.blue;
		} else {
			this.element.value = c.rgb.join(',');
		}
	},

	/**
	 * @param   mixed  val  RGB string or array
	 */
	update: function (val) {
		if (this.options.editable === false) {
			this.element.set('html', val);
			return;
		}
		if (typeOf(val) === 'null') {
			val = [0, 0, 0];
		} else {
			if (typeOf(val) === 'string') {
				val = val.split(",");
			}
		}
		this.updateAll(val[0], val[1], val[2]);
		return val;
	},

	updateFromField: function (evt, col) {
		var val = Math.min(255, evt.target.value.toInt());
		evt.target.value = val;
		if (isNaN(val)) {
			val = 0;
		} else {
			this.options.colour[col] = val;
			this.options.callback(this.options.colour.red + ',' + this.options.colour.green + ',' + this.options.colour.blue);
		}
	},

	toggleWidget: function (e) {
		e.stop();
		this.widget.toggle();
	}
});

var ColourPickerSwatch = new Class({

	Extends: Options,

	options: {},

	initialize : function (element, options) {


		this.element = document.id(element);
		this.setOptions(options);
		this.callback = this.options.callback;
		this.outputs = this.options.outputs;
		this.redField = null;
		this.widget = new Element('div');
		this.colourNameOutput = new Element('span', {'stlye': 'padding:3px'}).inject(this.widget);
		this.createColourSwatch(element);
		return this.widget;
	},

	createColourSwatch : function (element) {
		var j;
		var swatchDiv = new Element('div', {
			'styles': {
				'float': 'left',
				'margin-left': '5px',
				'class': 'swatchBackground'
			}
		});

		for (var i = 0; i < this.options.swatch.length; i++) {
			var swatchLine = new Element('div', {
				'styles': {
					'width': this.options.swatchWidth
				}
			});
			var line = this.options.swatch[i];
			j = 0;
			$H(line).each(function (colname, colour) {
				var swatchId = element + 'swatch-' + i + '-' + j;
				swatchLine.adopt(new Element('div', {
					'id': swatchId,
					'styles': {
						'float': 'left',
						'width': this.options.swatchSizeWidth,
						'cursor': 'crosshair',
						'height': this.options.swatchSizeHeight,
						'background-color': 'rgb(' + colour + ')'
					},
					'class': colname,
					'events': {
						'click': function (e) {
							this.updateFromSwatch(e);
						}.bind(this),
						'mouseenter': function (e) {
							this.showColourName(e);
						}.bind(this),
						'mouseleave': function (e) {
							this.clearColourName(e);
						}.bind(this)
					}
				}));
				j++;
			}.bind(this));

			swatchDiv.adopt(swatchLine);
		}
		this.widget.adopt(swatchDiv);
	},

	updateFromSwatch: function (e) {
		e.stop();
		var c = new Color(e.target.getStyle('background-color'));
		this.options.colour.red = c[0];
		this.options.colour.green = c[1];
		this.options.colour.blue = c[2];
		this.showColourName(e);
		this.callback(c, this);
	},

	showColourName: function (e) {
		this.colourName = e.target.className;
		this.colourNameOutput.set('text', this.colourName);
	},

	clearColourName: function (e) {
		this.colourNameOutput.set('text', '');
	}

});

var ColourPickerGradient = new Class({

	Extends: Options,

	options: {
		size: 125
	},

	initialize: function (id, opts) {
		this.brightness = 0;
		this.saturation = 0;
		this.setOptions(opts);
		this.callback = this.options.callback;
		this.container = document.id(id);
		if (typeOf(this.container) === 'null') {
			return;
		}
		this.offset = 0;

		// Distance between the colour square and the vertical strip
		this.margin = 10;

		this.borderColour = "rgba(155, 155, 155, 0.6)";

		// Width of the hue vertical strip
		this.hueWidth = 40;

		this.colour = new Color(this.options.value);

		this.square = new Element('canvas', {'width': (this.options.size + 65) + 'px', 'height': this.options.size + 'px'});
		this.square.inject(this.container);

		this.square.addEvent('click', function (e) {
			this.doIt(e);
		}.bind(this));

		this.down = false;
		this.square.addEvent('mousedown', function (e) {
			this.down = true;
		}.bind(this));
		this.square.addEvent('mouseup', function (e) {
			this.down = false;
		}.bind(this));
	/*	this.square.addEvent('mouseleave', function (e) {
			this.down = false;
		}.bind(this));*/
		document.addEvent('mousemove', function (e) {
			if (this.down) {
				this.doIt(e);
			}
		}.bind(this));

		this.drawCircle();
		this.drawHue();
		this.arrow = this.drawArrow();
		this.positionCircle(this.options.size, 0);

		this.update(this.options.value);
	},

	doIt: function (e) {
		var squareBound = {x: 0, y: 0, w: this.options.size, h: this.options.size};
		var containerPosition = this.square.getPosition();
		var x = e.page.x - containerPosition.x;
		var y = e.page.y - containerPosition.y;
		if (x < squareBound.w && y < squareBound.h) {
			this.setColourFromSquareSelection(x, y);
		} else if (x > this.options.size + this.margin && x <= this.options.size + this.hueWidth) {
			// Hue selection
			this.setHueFromSelection(x, y);
		}
	},

	update: function (c) {
		colour = new Color(c);

		// Store the brightness and saturation for positioning the circle picker in the square selector
		this.brightness = colour.hsb[2];
		this.saturation = colour.hsb[1];

		// Our this.colour is only interested in setting the hue from the update colour
		this.colour = this.colour.setHue(colour.hsb[0]);
		this.colour = this.colour.setSaturation(100);
		this.colour = this.colour.setBrightness(100);
		this.render();
		this.positionCircleFromColour(colour);
	},

	/**
	 * Poisition the circle based on a colour. As we are looking at HSB. saturation is defined on the x axis
	 * and brightness on the left axis (both defined as percentages)
	 *
	 * @param  Color  c
	 */
	positionCircleFromColour: function (c) {
		this.saturarion = c.hsb[1];
		this.brightness = c.hsb[2];
		var x = Math.floor(this.options.size  * (this.saturarion / 100));
		var y = Math.floor(this.options.size - (this.options.size * (this.brightness / 100)));
		this.positionCircle(x, y);
	},

	/**
	 * Draw the picker circle
	 */
	drawCircle: function () {
		this.circle = new Element('canvas', {'width': '10px', 'height': '10px'});
		var ctx = this.circle.getContext("2d");
		ctx.lineWidth = 1;
		ctx.beginPath();
		var x = this.circle.width / 2;
		var y = this.circle.width / 2;
		ctx.arc(x, y, 4.5, 0, Math.PI * 2, true);
		ctx.strokeStyle = '#000';
		ctx.stroke();
		ctx.beginPath();
		ctx.arc(x, y, 3.5, 0, Math.PI * 2, true);
		ctx.strokeStyle = '#FFF';
		ctx.stroke();
	},

	setHueFromSelection: function (x, y) {
		y = Math.min(1, y / this.options.size);
		y = Math.max(0, y);
		var hue = 360 - (y * 360);
		this.colour = this.colour.setHue(hue);
		this.render();
		this.positionCircle();

		// Apply the brightness/saturation to the color before sending the callback
		var c = this.colour;
		c = c.setBrightness(this.brightness);
		c = c.setSaturation(this.saturation);
		this.callback(c, this);
	},

	setColourFromSquareSelection: function (x, y) {
		var c = this.square.getContext('2d');
		this.positionCircle(x, y);
		var p = c.getImageData(x, y, 1, 1).data;
		var colour = new Color([p[0], p[1], p[2]]);

		// Store the brightness and saturation
		this.brightness = colour.hsb[2];
		this.saturation = colour.hsb[1];
		this.callback(colour, this);
	},

	positionCircle: function (x, y) {
		x = x ? x : this.circleX;
		this.circleX = x;
		y = y ? y : this.circleY;
		this.circleY = y;

		// Removes the old circle
		this.render();
		var ctx = this.square.getContext('2d');
		var offset = this.offset - 5;
		x = Math.max(-5, Math.round(x) + offset);
		y = Math.max(-5, Math.round(y) + offset);
		ctx.drawImage(this.circle, x, y);
	},

	drawHue: function () {

		// Drawing hue selector
		var ctx = this.square.getContext('2d');
		var left = this.options.size + this.margin + this.offset;
		var gradient = ctx.createLinearGradient(0, 0, 0, this.options.size + this.offset);
		gradient.addColorStop(0, "rgba(255, 0, 0, 1)");
		gradient.addColorStop(5 / 6, "rgba(255, 255, 0, 1)");
		gradient.addColorStop(4 / 6, "rgba(0, 255, 0, 1)");
		gradient.addColorStop(3 / 6, "rgba(0, 255, 255, 1)");
		gradient.addColorStop(2 / 6, "rgba(0, 0, 255, 1)");
		gradient.addColorStop(1 / 6, "rgba(255, 0, 255, 1)");
		gradient.addColorStop(1, "rgba(255, 0, 0, 1)");
		ctx.fillStyle = gradient;
		ctx.fillRect(left, this.offset, this.hueWidth - 10, this.options.size);

		// Drawing outer bounds
		ctx.strokeStyle = this.borderColour;
		ctx.strokeRect(left + 0.5, this.offset + 0.5, this.hueWidth - 11, this.options.size - 1);
	},

	render: function () {
		var ctx = this.square.getContext('2d');
		var offset = this.offset;
		ctx.clearRect(0, 0, this.square.width, this.square.height);
		var size = this.options.size;

		// Drawing color
		ctx.fillStyle = this.colour.hex;
		ctx.fillRect(offset, offset, size, size);

		// Overlaying saturation
		var gradient = ctx.createLinearGradient(offset, offset, size + offset, 0);
		gradient.addColorStop(0, "rgba(255, 255, 255, 1)");
		gradient.addColorStop(1, "rgba(255, 255, 255, 0)");
		ctx.fillStyle = gradient;
		ctx.fillRect(offset, offset, size, size);

		// Overlaying value
		gradient = ctx.createLinearGradient(0, offset, 0, size + offset);
		gradient.addColorStop(0.0, "rgba(0, 0, 0, 0)");
		gradient.addColorStop(1.0, "rgba(0, 0, 0, 1)");
		ctx.fillStyle = gradient;
		ctx.fillRect(offset, offset, size, size);

		// Drawing outer bounds
		ctx.strokeStyle = this.borderColour;
		ctx.strokeRect(offset + 0.5, offset + 0.5, size - 1, size - 1);

		this.drawHue();

		// Arrow-selection
		var y = ((360 - this.colour.hsb[0]) / 362) * this.options.size - 2;

		var arrowX = size + this.hueWidth + offset + 2;
		var arrowY = Math.max(0, Math.round(y) + offset - 1);
		ctx.drawImage(this.arrow, arrowX, arrowY);
		/*if (doAlpha) {
			var y = ((255 - colour.rgba[4]) / 255) * options.size - 2;
			ctx.drawImage(arrow, size + this.hueWidth * 2 + offset + 2, Math.round(y) + offset - 1);
		}*/

	},

	drawArrow: function () {
		var arrow = new Element('canvas');
		var ctx = arrow.getContext("2d");
		var size = 16;
		var width = size / 3;
		arrow.width = size;
		arrow.height = size;
		var top = -size / 4;
		var left = 0;
		for (var n = 0; n < 20; n++) { // multiply anti-aliasing
			ctx.beginPath();
			ctx.fillStyle = "#000";
			ctx.moveTo(left, size / 2 + top);
			ctx.lineTo(left + size / 4, size / 4 + top);
			ctx.lineTo(left + size / 4, size / 4 * 3 + top);
			ctx.fill();
		}
		ctx.translate(-width, -size);
		return arrow;
	}
});
