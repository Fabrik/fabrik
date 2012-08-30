var FbDateTime = new Class({
	Extends: FbElement,
	
	/**
	 * master date/time stored in this.cal (the js widget)
	 * upon save we get a db formatted version of this date and put it into the date field
	 * this dramitcally simplifies storing dates (no longer have to take account of formatting rules and/or
	 * translations on the server side, as the widget has already handled it for us
	 */
	options: {
		'dateTimeFormat': '', 
		'calendarSetup': {
			'eventName': 'click',
			'ifFormat': "%Y/%m/%d",
			'daFormat': "%Y/%m/%d",
			'singleClick': true,
			'align': "Br",
			'range': [1900, 2999],
			'showsTime': false,
			'timeFormat': '24',
			'electric': true,
			'step': 2,
			'cache': false,
			'showOthers': false,
			'advanced': false
		}
	},
	
	initialize: function (element, options) {
		this.parent(element, options);
		this.hour = '0';
		this.plugin = 'fabrikdate';
		this.minute = '00';
		this.buttonBg = '#ffffff';
		this.buttonBgSelected = '#88dd33';
		this.startElement = element;
		this.setUpDone = false;
		this.setUp();
	},
	
	setUp: function () {
		if (this.options.editable && !this.options.hidden) {
			this.watchButtons();
			if (this.options.typing === false) {
				this.disableTyping();
			} else {
				this.getDateField().addEvent('blur', function (e) {
					var date_str = this.getDateField().value;
					if (date_str !== '') {
						var d;
						//this is the calendar native parseDate call, but it doesnt take into account seconds
						// $$$ hugh - yup, but if we don't use parseDate() with the iFormat, a simple Date.parse()
						// hoses up anything but standard 'db' format.  So we HAVE to use parseDate() here.
						if (this.options.advanced) {
							d = Date.parseExact(date_str, Date.normalizeFormat(this.options.calendarSetup.ifFormat));
						}
						else {
							d = Date.parseDate(date_str, this.options.calendarSetup.ifFormat);
						}
						this.setTimeFromField(d);
						this.update(d);
					}
					else {
						this.options.value = '';
					}
				}.bind(this));
			}
			this.makeCalendar();
			//chrome wierdness where we need to delay the hiding if the date picker is hidden
			var h = function () { 
				this.cal.hide();
			};
			h.delay(100, this);
			this.element.getElement('img.calendarbutton').addEvent('click', function (e) {
				if (!this.cal.params.position) {
					this.cal.showAtElement(this.cal.params.button || this.cal.params.displayArea || this.cal.params.inputField, this.cal.params.align);
				} else {
					this.cal.showAt(this.cal.params.position[0], params.position[1]);
				}
				this.cal.show();
			}.bind(this));
			Fabrik.addEvent('fabrik.form.submit.failed', function (form, json) {
				//fired when form failed after AJAX submit
				this.afterAjaxValidation();
			}.bind(this));
		}
	},
	
	/**
	 * run when calendar poped up - goes over each date and should return true if you dont want the date to be 
	 * selectable 
	 */
	dateSelect: function (date)
	{
		var fn = this.options.calendarSetup.dateAllowFunc;
		if (typeOf(fn) !== 'null' && fn !== '') {
			eval(fn);
			return result;
		}
		// 2.0 fall back 
		try {
			return disallowDate(this.cal, date);
		} catch (err) {
			//fconsole(err);
		}
	},
	
	calSelect: function (calendar, date) {
		var d = this.setTimeFromField(calendar.date);
		this.update(d.format('db'));
		if (this.cal.dateClicked) {
			this.cal.callCloseHandler();
		}
		window.fireEvent('fabrik.date.select', this);
		Fabrik.fireEvent('fabrik.date.select', this);
	},
	
	calClose: function (calendar) {
		this.cal.hide();
		window.fireEvent('fabrik.date.close', this);
		if (this.options.validations) {
			//if we have a validation on the element run it when the calendar closes itself
			//this ensures that alert messages are removed if the new data meets validation criteria
			this.form.doElementValidation(this.options.element);
		}
	},

	onsubmit: function () {
		//convert the date back into mysql format before submitting - saves all sorts of shenanigans 
		//processing dates on the server.
		var v = this.getValue();
		if (v !== '') {
			// $$$ hugh - pretty sure we don't need to call update(), as getValue() is already returning
			// in MySQL format.  If we call update(), it fires a 'change' event, which puts us in an
			// infinite loop in some situations, like on a calc element update.
			// So just setting the date field to v should be enough.
			//this.update(v);
			if (this.options.editable) {
				this.getDateField().value = v;
			}
		}
		return true;
	},
	
	/**
	 * As ajax validations call onsubmit to get the correct date, we need to
	 * reset the date back to the display date when the validation is complete
	 */
	afterAjaxValidation: function () {
		this.update(this.getValue());
	},
	
	makeCalendar: function () {
		if (this.cal) {
			this.cal.show();
			return;
		}
		var mustCreate = false;
		this.addEventToCalOpts();
		var params = this.options.calendarSetup;
		var tmp = ["displayArea", "button"];
		
		// for (var i in tmp) {
		for (i = 0; i < tmp.length; i++) {
			if (typeof params[tmp[i]] === "string") {
				params[tmp[i]] = document.getElementById(params[tmp[i]]);
			}
		}
	
		params.inputField = this.getDateField();
		var dateEl = params.inputField || params.displayArea;
		var dateFmt = params.inputField ? params.ifFormat : params.daFormat;
		this.cal = null;//Fabrik.calendar;
		if (dateEl) {
			if (this.options.advanced) {
				params.date = Date.parseExact(dateEl.value || dateEl.innerHTML, Date.normalizeFormat(dateFmt));
			}
			else {
				params.date = Date.parseDate(dateEl.value || dateEl.innerHTML, dateFmt);
			}
			//params.date = Date.parseDate(dateEl.value || dateEl.innerHTML, dateFmt);
		}
		
		this.cal = new Calendar(params.firstDay,
			params.date,
			params.onSelect,
			params.onClose);
	
		this.cal.setDateStatusHandler(params.dateStatusFunc);
		this.cal.setDateToolTipHandler(params.dateTooltipFunc);
		this.cal.showsTime = params.showsTime;
		this.cal.time24 = (params.timeFormat.toString() === "24");
		this.cal.weekNumbers = params.weekNumbers;
		
		if (params.multiple) {
			cal.multiple = {};
			for (i = params.multiple.length; --i >= 0;) {
				var d = params.multiple[i];
				var ds = d.print("%Y%m%d");
				this.cal.multiple[ds] = d;
			}
		}
		this.cal.showsOtherMonths = params.showOthers;
		this.cal.yearStep = params.step;
		this.cal.setRange(params.range[0], params.range[1]);
		this.cal.params = params;
		
		this.cal.getDateText = params.dateText;
		this.cal.setDateFormat(dateFmt);
		this.cal.create();
		this.cal.refresh();
		this.cal.hide();
		/*
		if (!params.position) {
			this.cal.showAtElement(params.button || params.displayArea || params.inputField, params.align);
		} else {
			this.cal.showAt(params.position[0], params.position[1]);
		}
		*/
	},

	disableTyping : function () {
		if (typeOf(this.element) === 'null') {
			fconsole(element + ': not date element container - is this a custom template with a missing $element->containerClass div/li surrounding the element?');
			return;
		}
		// yes we really can set the none existant 'readonly' property of the
		// subelement container
		// and get it when checking the validations - cool or what?
		this.element.setProperty('readonly', 'readonly');
		this.element.getElements('.fabrikinput').each(function (f) {
			f.addEvent('focus', function (e) {
				if (typeOf(e) === 'null') {
					return;
				}
				if (e.target.hasClass('timeField')) {
					this.getContainer().getElement('.timeButton').fireEvent('click');
				} else {
					this.options.calendarSetup.inputField = e.target.id;
					this.options.calendarSetup.button = this.element.id + "_img";
					this.addEventToCalOpts();
					
				}
			}.bind(this));
		}.bind(this));
	},

	/** 
	 * returns the date and time in mySQL formatted string
	 */
	getValue: function () {
		var v;
		if (!this.options.editable) {
			return this.options.value;
		}
		this.getElement();
		if (this.cal) {
			if (this.getDateField().value === '') {
				return '';
			}
			v = this.cal.date;
		} else {
			if (this.options.value === '' || this.options.value === null) {
				return '';
			}
			v = new Date.parse(this.options.value);
		}
		v = this.setTimeFromField(v);
		return v.format('db');
	},
	
	hasSeconds: function () {
		if (this.options.showtime === true && this.timeElement) {
			if (this.options.dateTimeFormat.contains('%S')) {
				return true;
			}
			if (this.options.dateTimeFormat.contains('%T')) {
				return true;
			}
		}
		return false;
	},
	
	setTimeFromField: function (d) {
		if (this.options.showtime === true && this.timeElement) {
			var time = this.timeElement.get('value').toUpperCase();
			var afternoon = time.contains('PM') ? true : false;
			time = time.replace('PM', '').replace('AM', '');	
			
			var t = time.split(':');
			var h = t[0] ? t[0].toInt() : 0;
			if (afternoon) {
				h += 12;
			}
			var m = t[1] ? t[1].toInt() : 0;
			
			d.setHours(h);
			d.setMinutes(m);
			
			if (t[2] && this.hasSeconds()) {
				var s = t[2] ? t[2].toInt() : 0;
				d.setSeconds(s);
			} else {
				d.setSeconds(0);
			}
			
		} else {
			//hidden fields should still keep their times
			//d.setHours(0);
			//d.setMinutes(0);
			//d.setSeconds(0);
		}
		return d;
	},

	watchButtons : function () {
		if (this.options.showtime & this.options.editable) {
			this.getTimeField();
			this.getTimeButton();
			if (this.timeButton) {
				this.timeButton.removeEvents('click');
				this.timeButton.addEvent('click', function () {
					this.showTime();
				}.bind(this));
				if (!this.setUpDone) {
					if (this.timeElement) {
						this.dropdown = this.makeDropDown();
						this.setAbsolutePos(this.timeElement);
						this.setUpDone = true;
					}
				}
			}
		}
	},

	addNewEventAux : function (action, js) {
		if (action === 'change') {
			Fabrik.addEvent('fabrik.date.select', function () {
				var e = 'fabrik.date.select';
				typeOf(js) === 'function' ? js.delay(0) : eval(js);
			});
		}
		this.element.getElements('input').each(function (i) {
			i.addEvent(action, function (e) {
				if (typeOf(e) === 'event') {
					e.stop();
				}
				typeOf(js) === 'function' ? js.delay(0) : eval(js);
			});
		}.bind(this));	
	},
	
	addNewEvent : function (action, js) {
		if (action === 'load') {
			this.loadEvents.push(js);
			this.runLoadEvent(js);
		} else {
			if (!this.element) {
				this.element = document.id(this.strElement);
			}
			if (action === 'change') {
				this.changeEvents.push(js);
			}
			this.addNewEventAux(action, js);
		}
	},

	/**
	 * takes a date object or string
	 */
	update: function (val) {
		this.getElement();
		if (val === 'invalid date') {
			fconsole(this.element.id + ': date not updated as not valid');
			return;
		}
		var date;
		if (typeOf(val) === 'string') {
			// $$$ hugh - if val is empty string, like from a clearForm(), the Date.parse() is
			// going to return null, swhich will then blow up in a few lines.
			date = Date.parse(val);
			if (date === null) {
				return;
			}
		} else {
			date = val;
		}
		var f = this.options.calendarSetup.ifFormat;
		if (this.options.dateTimeFormat !== '' && this.options.showtime) {
			f += ' ' + this.options.dateTimeFormat;
		}
		
		this.fireEvents([ 'change' ]);
		if (typeOf(val) === 'null' || val === false) {
			return;
		}
		if (!this.options.editable) {
			if (typeOf(this.element) !== 'null') {
				//this.element.set('html', val);
				this.element.set('html', date.format(f));
			}
			return;
		}
		
		if (this.options.hidden) {
			//if hidden but form set to show time format dont split up the time as we don't 
			// have a time field to put it into
			date = date.format(f);
			this.getDateField().value = date;
			return;
		} else {
			// have to reset the time element as update is called (via reset) in
			// duplicate group code
			// before cloned() method called
			this.getTimeField();
			this.hour = date.get('hours');
			this.minute = date.get('minutes');
			this.second = date.get('seconds');
			this.stateTime();
		}
		this.cal.date = date;
		this.getDateField().value = date.format(this.options.calendarSetup.ifFormat);
	},
	
	/**
	 * get the date field input
	 */
	getDateField: function () {
		return this.element.getElement('.fabrikinput');
	},
	
	/**
	 * get time time field input
	 */
	getTimeField: function () {
		this.timeElement = this.getContainer().getElement('.timeField');
		return this.timeElement;
	},
	
	/**
	 * get time time button img
	 */
	getTimeButton: function () {
		this.timeButton = this.getContainer().getElement('.timeButton');
		return this.timeButton;
	},

	showCalendar : function (format, e) {
		/*if (window.ie) {
			// when scrolled down the page the offset of the calendar is wrong - this
			// fixes it
			var calHeight = document.id(window.calendar.element).getStyle('height').toInt();
			var u = ie ? event.clientY + document.documentElement.scrollTop : e.pageY;
			u = u.toInt();
			document.id(window.calendar.element).setStyles({
				'top' : u - calHeight + 'px'
			});
		}*/
	},

	getAbsolutePos : function (el) {
		var r = {
			x : el.offsetLeft,
			y : el.offsetTop
		};
		if (el.offsetParent) {
			var tmp = this.getAbsolutePos(el.offsetParent);
			r.x += tmp.x;
			r.y += tmp.y;
		}
		return r;
	},

	setAbsolutePos : function (el) {
		var r = this.getAbsolutePos(el);
		this.dropdown.setStyles({
			position : 'absolute',
			left : r.x,
			top : r.y + 30
		});
	},

	makeDropDown : function () {
		var h = null;
		var handle = new Element('div', {
			styles : {
				'height' : '20px',
				'curor' : 'move',
				'color' : '#dddddd',
				'padding' : '2px;',
				'background-color' : '#333333'
			},
			'id' : this.startElement + '_handle'
		}).appendText(this.options.timelabel);
		var d = new Element('div', {
			'className' : 'fbDateTime',
			'styles' : {
				'z-index' : 999999,
				display : 'none',
				cursor : 'move',
				width : '264px',
				height : '125px',
				border : '1px solid #999999',
				backgroundColor : '#EEEEEE'
			}
		});

		d.appendChild(handle);
		for (var i = 0; i < 24; i++) {
			h = new Element('div', {
				styles: {
					width: '20px',
					'float': 'left',
					'cursor': 'pointer',
					'background-color': '#ffffff',
					'margin': '1px',
					'text-align': 'center'
				}
			});
			h.innerHTML = i;
			h.className = 'fbdateTime-hour';
			d.appendChild(h);
			document.id(h).addEvent('click', function (e) {
				this.hour = e.target.innerHTML;
				this.stateTime();
				this.setActive();
			}.bind(this));
			document.id(h).addEvent('mouseover', function (e) {
				if (this.hour !== e.target.innerHTML) {
					e.target.setStyles({
						background : '#cbeefb'
					});
				}
			}.bind(this));
			document.id(h).addEvent('mouseout', function (e) {
				if (this.hour !== e.target.innerHTML) {
					h.setStyles({
						background : this.buttonBg
					});
				}
			}.bind(this));
		}
		var d2 = new Element('div', {
			styles : {
				clear : 'both',
				paddingTop : '5px'
			}
		});
		for (i = 0; i < 12; i++) {
			h = new Element('div', {
				styles : {
					width : '41px',
					'float' : 'left',
					'cursor' : 'pointer',
					'background' : '#ffffff',
					'margin' : '1px',
					'text-align' : 'center'
				}
			});
			h.setStyles();
			h.innerHTML = ':' + (i * 5);
			h.className = 'fbdateTime-minute';
			d2.appendChild(h);
			document.id(h).addEvent('click', function (e) {
				this.minute = this.formatMinute(e.target.innerHTML);
				this.stateTime();
				this.setActive();
			}.bind(this));
			h.addEvent('mouseover', function (e) {
				var h = e.target;
				if (this.minute !== this.formatMinute(h.innerHTML)) {
					e.target.setStyles({
						background : '#cbeefb'
					});
				}
			}.bind(this));
			h.addEvent('mouseout', function (e) {
				var h = e.target;
				if (this.minute !== this.formatMinute(h.innerHTML)) {
					e.target.setStyles({
						background : this.buttonBg
					});
				}
			}.bind(this));
		}
		d.appendChild(d2);

		document.addEvent('click', function (e) {
			if (this.timeActive) {
				var t = e.target;
				if (t !== this.timeButton && t !== this.timeElement) {
					if (!t.within(this.dropdown)) {
						this.hideTime();
					}
				}
			}
		}.bind(this));
		d.injectInside(document.body);
		var mydrag = new Drag.Move(d);
		return d;
	},

	toggleTime : function () {
		if (this.dropdown.style.display === 'none') {
			this.doShowTime();
		} else {
			this.hideTime();
		}
	},

	doShowTime : function () {
		this.dropdown.setStyles({
			'display' : 'block'
		});
		this.timeActive = true;
		Fabrik.fireEvent('fabrik.date.showtime', this);
	},

	hideTime: function () {
		this.timeActive = false;
		this.dropdown.hide();
		if (this.options.validations !== false) {
			this.form.doElementValidation(this.element.id);
		}
		Fabrik.fireEvent('fabrik.date.hidetime', this);
		Fabrik.fireEvent('fabrik.date.select', this);
		window.fireEvent('fabrik.date.select', this);
	},

	formatMinute: function (m) {
		m = m.replace(':', '');
		m.pad('2', '0', 'left');
		return m;
	},

	stateTime: function () {
		if (this.timeElement) {
			var newv = this.hour.toString().pad('2', '0', 'left') + ':' + this.minute.toString().pad('2', '0', 'left');
			if (this.second) {
				newv += ':' + this.second.toString().pad('2', '0', 'left');	
			}
			var changed = this.timeElement.value !== newv;
			this.timeElement.value = newv;
			if (changed) {
				this.fireEvents([ 'change' ]);
			}
		}
	},

	showTime: function () {
		this.setAbsolutePos(this.timeElement); // need to recall if using tabbed form
		this.toggleTime();
		this.setActive();
	},

	setActive: function () {
		var hours = this.dropdown.getElements('.fbdateTime-hour');
		hours.each(function (e) {
			e.setStyles({
				backgroundColor: this.buttonBg
			});
		}, this);
		var mins = this.dropdown.getElements('.fbdateTime-minute');
		mins.each(function (e) {
			e.setStyles({
				backgroundColor: this.buttonBg
			});
		}, this);
		hours[this.hour.toInt()].setStyles({
			backgroundColor: this.buttonBgSelected
		});
		if (typeOf(mins[this.minute / 5]) !== 'null') {
			mins[this.minute / 5].setStyles({
				backgroundColor: this.buttonBgSelected
			});
		}
	},
	
	addEventToCalOpts: function () {
		this.options.calendarSetup.onSelect = function (calendar, date) {
			this.calSelect(calendar, date);
		}.bind(this);
		
		this.options.calendarSetup.dateStatusFunc = function (date) {
			return this.dateSelect(date);
		}.bind(this);
		
		this.options.calendarSetup.onClose = function (calendar) {
			this.calClose(calendar);
		}.bind(this);

		
	},

	cloned : function (c) {
		this.setUpDone = false;
		this.hour = 0;
		delete this.cal;
		var button = this.element.getElement('img');
		if (button) {
			button.id = this.element.id + "_cal_img";
		}
		var datefield = this.element.getElement('input');
		datefield.id = this.element.id + "_cal";
		this.options.calendarSetup.inputField = datefield.id;
		this.options.calendarSetup.button = this.element.id + "_img";

		this.makeCalendar();
		this.cal.hide();
		this.setUp();
	}
});

/// you can add custom events with:
	/*
	 * Fabrik.addEvent('fabrik.date.select', function () {
		console.log('trigger custom date event');
	})
 */