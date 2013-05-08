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
		blue: 0
	},
	
	initialize : function (element, options) {
		this.plugin = 'colourpicker';
		this.parent(element, options);
		this.element = document.id(element);
		this.widget = this.element.getParent('.fabrikSubElementContainer').getElement('.colourpicker-widget');
		this.setOutputs();
		this.redField = null;
		this.showSwatch = true;
		this.showCloseButton = true;
		this.showCloseIcon = true;
		// create the table to hold the scroller
		this.table = new Element('table', {
			'styles': {
				'float': 'right',
				'margin-right': '2px'
			}
		});
		this.tbody = new Element('tbody');
		var aColours = ['red', 'green', 'blue'];
		
		if (this.showCloseIcon) {
			var closeIcon = this.createCloseIcon(element);
			this.widget.appendChild(closeIcon);
		} else {
			new Element('div', {'class': 'handle', 'styles': {'width': '375px', 'text-align': 'right', 'clear': 'both'}}).inject(this.widget);
		}
		if (this.showSwatch) {
			this.createColourSwatch(element);
		}

		this.createColourSlideHTML(element, 'red', 'Red:', this.options.red);
		this.createColourSlideHTML(element, 'green', 'Green:', this.options.green);
		this.createColourSlideHTML(element, 'blue', 'Blue:', this.options.blue);
		this.table.appendChild(this.tbody);
		this.widget.appendChild(this.table);
		this.sliderRefs = [];
		for (var i = 0; i < aColours.length; i++) {
			var col = aColours[i];
			var opts = {
				steps: 255,
				'color': col,
				max: 255,
				offset: 1,
				onChange: function (pos) {
					Fabrik.fireEvent('fabrik.colourpicker.slider', [this, this.options.color, pos]);
				}
			};
			this.sliderRefs.push(element + col + 'track');
			this[col + "Slider"] = new Slider(document.id(element + col + 'track'), document.id(element + col + 'handle'), opts);
		}
		Fabrik.addEvent('fabrik.colourpicker.slider', function (o, col, pos) {
			if (this.sliderRefs.contains(o.element.id)) {
				this.options.colour[col] = pos;
				this.update(this.options.colour.red + ',' + this.options.colour.green + ',' + this.options.colour.blue);
			}
			
		}.bind(this));
		this.widget.hide();
		// this makes the class update when someone enters a value into
		
		this.redField.addEvent("change", function (e) {
			this.updateFromField(e, 'red');
		}.bind(this));
		
		this.greenField.addEvent("change", function (e) {
			this.updateFromField(e, 'green');
		}.bind(this));
		
		this.blueField.addEvent("change", function (e) {
			this.updateFromFiel(e, 'blue');
		}.bind(this));

		if (this.showCloseButton) {
			var closeButton = this.createCloseButton(element, 'Close');
			this.widget.appendChild(closeButton);
		}
		var d = new Drag.Move(this.widget, {'handle': this.widget.getElement('.handle')});
		this.update(this.options.value);
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
					'width': '160px'
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
						'width': '10px',
						'cursor': 'crosshair',
						'height': '10px',
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

	updateFromSwatch : function (e) {
		e.stop();
		var c = new Color(e.target.getStyle('background-color'));
		this.options.colour.red = c.red;
		this.options.colour.green = c.green;
		this.options.colour.blue = c.blue;
		this.showColourName(e);
		this.updateAll(this.options.colour.red, this.options.colour.green, this.options.colour.blue);
	},
	
	showColourName: function (e) {
		this.colourName = e.target.className;
		e.target.getParent('.colourpicker-widget').getElement('.colourName').set('text', this.colourName);
	},
	
	clearColourName: function (e) {
		e.target.getParent('.colourpicker-widget').getElement('.colourName').set('text', '');
	},

	updateOutputs : function () {
		var c = new Color([this.options.colour.red, this.options.colour.green, this.options.colour.blue]);
		this.outputs.backgrounds.each(function (output) {
			output.setStyle('background-color', c);
		});
		this.outputs.foregrounds.each(function (output) {
			output.setStyle('background-color', c);
		});
		this.element.value = c.red + ',' + c.green + ',' + c.blue;
	},

	update : function (val) {
		if (this.options.editable === false) {
			this.element.set('html', val);
			return;
		}
		if (typeOf(val) === 'null') {
			val = [0, 0, 0];
		} else {
			val = val.split(",");
		}
		this.updateAll(val[0], val[1], val[2]);
	},

	updateAll : function (red, green, blue) {
		red = red ? red.toInt() : 0;
		green = green ? green.toInt() : 0;
		blue = blue ? blue.toInt() : 0;
		this.redSlider.set(red);
		this.redField.value = red;
		this.options.colour.red = red;
		this.greenSlider.set(green);
		this.greenField.value = green;
		this.options.colour.green = green;
		this.blueSlider.set(blue);
		this.blueField.value = blue;
		this.options.colour.blue = blue;
		this.updateOutputs();
	},

	setOutputs : function (output) {
		this.outputs = {};
		this.outputs.backgrounds = this.getContainer().getElements('.colourpicker_bgoutput');
		this.outputs.foregrounds = this.getContainer().getElements('.colourpicker_output');
		
		this.outputs.backgrounds.each(function (i) {
			
			i.addEvent('click', function (e) {
				this.toggleWidget(e);
			}.bind(this));
			
		}.bind(this));
		
		this.outputs.foregrounds.each(function (i) {
			
			i.addEvent('click', function (e) {
				this.toggleWidget(e);
			}.bind(this));
			
		}.bind(this));
	},
	
	toggleWidget: function (e) {
		e.stop();
		this.widget.toggle();
	},
	
	updateFromField: function (evt, col) {
		var val = evt.target.value.toInt();
		if (isNaN(val)) {
			val = 0;
		} else {
			this.options.colour[col] = val;
			this.update(this.options.colour.red + ',' + this.options.colour.green + ',' + this.options.colour.blue);
		}
	},

	createCloseButton : function (picker, sClose) {
		var div = new Element('div', {
			'styles': {
				'width': '375px',
				'text-align': 'right',
				'clear': 'right'
			}
		});
		div.adopt(new Element('span', {'class': 'colourName', 'styles': {'padding-right': '20px'}}));
		picker = document.id(picker);
		var input = new Element('input', {
			'class': 'button',
			'value': sClose,
			'type': 'button',
			'events': {
				'click': function () {
					this.widget.toggle();
				}.bind(this)
			}
		});
		div.appendChild(input);
		return div;
	},

	createCloseIcon : function (picker) {
		//todo replace clos img with art svn icon
		var div = new Element('div', {'class': 'handle',
			'styles': {
				'margin': '0 0 10px 0',
				'background-color': '#333333',
				'cursor': 'move',
				'text-align': 'right',
				'clear': 'both',
				'height': '13px'
			}
		}).adopt(new Element('img', {
				'src': this.options.closeImage,
				'styles': {
					'float': 'right',
					'cursor': 'pointer'
				},
				'events': {
					'click': function (e) {
						e.target.getParent().parentNode.hide();
						e.stop();
					}
				}
			})
		);
		return div;
	},

	createColourSlideHTML : function (element, colour, label, value) {

		var Track = new Element('div', {
			'id': element + colour + 'track',
			'styles': {
				'height': '5px',
				'width': '123px',
				'background': 'url("' + this.options.trackImage + '") repeat-x scroll center center transparent'
			}
		});

		var sliderDiv = new Element('div', {
			'id': element + colour + 'handle',
			'styles': {
				'width': '11px',
				'height': '21px',
				'top': '-15px'
			}
		}).adopt(new Element('img', {
			'src' : this.options.handleImage
		}));

		var sliderField = new Element('input', {
			'type': 'text',
			'id': element + colour + 'redField',
			'size': '3',
			'class': 'input ' + colour + 'SliderField',
			'value': value
		});

		var tr1 = new Element('tr').adopt([new Element('td').appendText(label), new Element('td').adopt([Track.adopt(sliderDiv)]),
				new Element('td').adopt(sliderField) ]);

		this.tbody.appendChild(tr1);
		this[colour + "Field"] = sliderField;
	}
});
