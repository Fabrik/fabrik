/**
 * Auto-Complete
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $H:true,unescape:true */

var FbAutocomplete = new Class({

	Implements: [Options, Events],

	options: {
		menuclass: 'auto-complete-container dropdown',
		classes: {
			'ul': 'dropdown-menu',
			'li': 'result'
		},
		url: 'index.php',
		max: 10,
		onSelection: Class.empty,
		autoLoadSingleResult: true,
		storeMatchedResultsOnly: false // Only store a value if selected from picklist
	},

	initialize: function (element, options) {
		window.addEvent('domready', function () {
			this.setOptions(options);
			element = element.replace('-auto-complete', '');
			this.options.labelelement = typeOf(document.id(element + '-auto-complete')) === "null" ? document.getElement(element + '-auto-complete') : document.id(element + '-auto-complete');
			this.cache = {};
			this.selected = -2;
			this.searchText = '';
			this.element = typeOf(document.id(element)) === "null" ? document.getElement(element) : document.id(element);
			this.buildMenu();
			if (!this.getInputElement()) {
				fconsole('autocomplete didn\'t find input element');
				return;
			}
			this.getInputElement().setProperty('autocomplete', 'off');
			this.getInputElement().addEvent('keydown', function (e) {
				this.doWatchKeys(e);
			}.bind(this));
			this.getInputElement().addEvent('keyup', function (e) {
				this.search(e);
			}.bind(this));
			// We need to prevent blur propagation so that menu clicks don't trigger other blur events
			this.getInputElement().addEvent('blur', function (e) {
				e.stop();
			}.bind(this));
		}.bind(this));
	},

	search: function (e) {
		if (this.ajax) {
			this.ajax.cancel();
		}
		var v = this.getInputElement().get('value');
		if (e.key === 'tab' || e.key === 'enter') {
			this.searchText = v.toLowerCase();
			this.menuClose();
			this.element.fireEvent('change', new Event.Mock(this.element, 'change'), 500);
			return;
		}
		if (v === '') {
			this.menuClose();
			this.searchText = this.element.value = '';
		} else if (v.toLowerCase() !== this.searchText) {
			if (this.options.storeMatchedResultsOnly) {
				this.element.value = '';
			} else {
				this.element.value = v;
			}
			if (this.cache['$' + v.toLowerCase() + '$']) {
				this.searchText = v.toLowerCase();
				this.menuPopulateOpen(this.cache['$' + v.toLowerCase() + '$']);
			} else if (this.searchText !== '' && v.indexOf(' ') < 0 && v.indexOf(this.searchText) >= 0) {
				/**
				 * If this search doesn't have any spaces in ...
				 * And if last search is a subset of this search then ...
				 * The results for this search will be a subset of the results of the last search
				 * In which case we can simply loop through the results of the last search and
				 * delete results where this search is not inside. And we can then cache this.
				 **/
				r = this.cache['$' + this.searchText + '$'];
				u = v.toLowerCase();
				for (i=r.length - 1; i >= 0; i--) {
					if (r[i].text.toLowerCase().indexOf(u) < 0) {
						r.splice(i, 1);
					}
				}
				this.completeAjax(r, v);
			} else {
				this.ajax = new Request({
					url: this.options.url,
					data: {
						value: v
					},

					onRequest: function(){
						Fabrik.loader.start(this.getInputElement());
					}.bind(this),

					onCancel: function(){
						Fabrik.loader.stop(this.getInputElement());
						this.ajax = null;
					}.bind(this),

					onComplete: function(){
						Fabrik.loader.stop(this.getInputElement());
						this.ajax = null;
					}.bind(this),

					onFailure: function(xhr){
						console.log('Fabrik autocomplete: Ajax failure: Code ' + xhr.status + ': ' + xhr.statusText);
						elModel = Fabrik.blocks[this.options.formRef].formElements.get(this.element.id);
						elModel.setErrorMessage(Joomla.JText._('COM_FABRIK_AUTOCOMPLETE_AJAX_ERROR'), 'fabrikError', true);
					}.bind(this),

					onSuccess: function (r) {
						if (typeOf(r) === 'null') {
							console.log('Fabrik autocomplete: Ajax response empty');
							elModel = Fabrik.blocks[this.options.formRef].formElements.get(this.element.id);
							elModel.setErrorMessage(Joomla.JText._('COM_FABRIK_AUTOCOMPLETE_AJAX_ERROR'), 'fabrikError', true);
							return;
						}
						r = JSON.decode(r);
						this.completeAjax(r, v);
					}.bind(this)
				}).send();
			}
		}
	},

	completeAjax: function (r, v) {
		this.searchText = u = v.toLowerCase();
		this.cache['$' + u + '$'] = r;
		if (r.length === 1) {
			// Cache additional v with remaining characters appended one by one because we know the answer already
			// e.g. user types abr on a 'contains' dbjoin and only returned word is fabrik, then we cache
			// additionally abri and abrik. Since abr is unique, then so also is fabr as well as abri and abrik.
			var w = r[0].text.toLowerCase();
			this.searchText = w.substr(0, w.indexOf(u) + u.length);
			var x = w.substr(w.indexOf(u));
			while (x.length > u.length) {
				this.cache['$' + w + '$'] = r;
				w = w.substr(0, w.length - 1);
				this.cache['$' + x + '$'] = r;
				x = x.substr(0, x.length - 1);
			}
		} else {
			// Cache each item in the list so if we select one and tab back we don't ajax again.
			r.each(function(s) {
				this.cache['$' + s.text.toLowerCase() + '$'] = Array(s);
			}.bind(this));
		}
		this.menuPopulateOpen(r);
	},

	buildMenu: function ()
	{
		if (Fabrik.bootstrapped) {
			this.menu = new Element('ul.' + this.options.classes.ul, {'role': 'menu', 'styles': {'z-index': 1056}});
			this.ul = this.menu;
		} else {
			this.menu = new Element('div', {'class': this.options.menuclass, 'styles': {'position': 'absolute'}}).adopt(new Element('ul.' + this.options.classes.ul));
			this.ul = this.menu.getElement('ul');
		}
		this.menu.inject(document.body);
		this.menu.addEvent('click:relay(a)', function (e, target) {
			this.makeSelection(e, target);
		}.bind(this));
		// Paul Make mouseover select the li like a down key would.
		this.menu.addEvent('mouseenter:relay(li)', function (e, target) {
			this.selectLi(e, target);
		}.bind(this));
	},

	getInputElement: function () {
		return this.options.labelelement ? this.options.labelelement : this.element;
	},

	positionMenu: function () {
		var coords = this.getInputElement().getCoordinates();
		var pos = this.getInputElement().getPosition();
		this.menu.setStyles({ 'left': coords.left, 'top': (coords.top + coords.height) - 1, 'width': coords.width});
	},

	menuPopulateOpen: function (data) {
		// $$$ hugh - added decoding of things like &amp; in the text strings
		data.map(function (item, index) {
			item.text = Encoder.htmlDecode(item.text);
			return item;
		});
		this.data = data;
		var max = this.getListMax();
		var ul = this.ul;
		ul.empty();
		if (data.length === 0) {
			new Element('li').adopt(new Element('div.alert.alert-info').adopt(new Element('i').set('text', Joomla.JText._('COM_FABRIK_NO_RECORDS')))).inject(ul);
		}
		for (var i = 0; i < max; i ++) {
			var pair = data[i];
			var a = new Element('a', {'href': '#', 'data-value': pair.value, tabindex: '-1'}).set('text', pair.text);
			if (!Fabrik.bootstrapped) {
				li.addClass('unselected ' + this.options.classes.li);
			}
			var li = new Element('li').adopt(a);
			li.inject(ul);
		}
		if (data.length > this.options.max) {
			new Element('li').set('text', '....').inject(ul);
		}
		if (data.length === 1 && this.options.autoLoadSingleResult) {
			var v = this.getInputElement().get('value');
			r = data[0].text;
			i = r.toLowerCase().indexOf(v.toLowerCase());
			if (i >=0)
			{
				var ie = this.getInputElement();
				ie.value = r;
				ie.selectRange(i + v.length, r.length);
				this.element.value = data[0].value;
				this.menuClose();
				// $$$ Paul - The selection event is for text being selected in an input field not for a link being selected
				// this.fireEvent('selection', [this, this.element.value]);
				return;
			}
		}
		this.menuOpen();
	},

	selectLi: function (e, mouseLi) {
		if (typeOf(mouseLi) !== 'null') {
			this.menu.getElements('li').each(function (li, i) {
				if (li === mouseLi) {
					this.selected = i;
					this.highlight();
				}
			}.bind(this));
		}
	},

	makeSelection: function (e, li) {
		e.stop();
		this.getInputElement().focus();
		// $$$ tom - make sure an item was selected before operating on it.
		if (typeOf(li) !== 'null') {
			/**
			 * Blur is triggered by mouse click (which takes focus from input element)
			 * and triggers form ajax element validation events.
			 * We have added a delay of 500ms to validation events in form.js to allow the fields to be set here
			 * however if validation fails because it is triggered before fields are set, then increase the delay.
			 **/
			this.element.value = li.getProperty('data-value');
			this.getInputElement().value = li.get('text');
			this.menuClose();
			// $$$ Paul - The selection event is for text being selected in an input field not for a link being selected
			// this.fireEvent('selection', [this, this.element.value]);
			// $$$ hugh - fire a Fabrik event, just for good luck.  :)
			Fabrik.fireEvent('fabrik.autocomplete.selected', [this, this.element.value]);
			// $$$ hugh - fire a change event in case it's e.g. a join element with a CDD watching it.
			this.element.fireEvent('change', new Event.Mock(this.element, 'change'), 100);
		} else {
			/**
			 * $$$ Paul - The Fabrik event below makes NO sense.
			 * This is a code error condition not an event because typeOf(li) should never be null
			 **/
			// $$$ tom - fire a notselected event to let developer take appropriate actions.
			Fabrik.fireEvent('fabrik.autocomplete.notselected', [this, this.element.value]);
		}
	},

	menuClose: function () {
		if (this.shown) {
			this.shown = false;
			if (Fabrik.bootstrapped) {
				this.menu.hide();
			} else {
				this.menu.fade('out');
			}
			this.selected = -2;
		}
	},

	menuOpen: function () {
		if (!this.shown) {
			this.shown = true;
			this.positionMenu();
			if (Fabrik.bootstrapped) {
				this.menu.show();
			} else {
				this.menu.setStyle('visibility', 'visible').fade('in');
			}
			if (this.data.length > 0) {
				this.selected = -1;
				this.highlight();
			} else {
				this.selected = -2;
			}
		}
	},

	getListMax: function () {
		if (typeOf(this.data) === 'null') {
			return 0;
		}
		return this.data.length > this.options.max ? this.options.max : this.data.length;
	},

	doWatchKeys: function (e) {
		var max = this.getListMax();
		if (!this.shown) {
			if (e.code.toInt() === 40 && this.getInputElement().get('value') !== '') {
				this.menuOpen();
			}
		} else {
			switch (e.code) {
				case 40: // down
					e.stop();
					if (this.selected >= -1 && this.selected < max - 1) {
						this.selected ++;
						this.highlight();
					}
					break;
				case 38: // up
					e.stop();
					if (this.selected > -1) {
						this.selected --;
						this.highlight();
					}
					break;
				case 13: // enter
					e.stop();
				case 9:  // tab
					if (this.shown) {
						this.makeSelection(new Event.Mock(this.getSelected(), 'click'), this.getSelected());
						this.menuClose();
					}
					break;
				case 27: // escape
					e.stop();
					this.menuClose();
					break;
			}
		}
	},

	getSelected: function () {
		var a = this.menu.getElements('li').filter(function (li, i) {
			return i === this.selected;
		}.bind(this));
		return a[0];
	},

	highlight: function () {
		this.menu.getElements('li').each(function (li, i) {
			if (Fabrik.bootstrapped) {
				if (i === this.selected) {
					li.addClass('selected').addClass('active');
				} else {
					li.removeClass('selected').removeClass('active');
				}
			} else {
				if (i === this.selected) {
					li.addClass('selected').addClass('active');
				} else {
					li.removeClass('selected').removeClass('active');
				}
			}
		}.bind(this));
	}
});

var FabCddAutocomplete = new Class({

	Extends: FbAutocomplete,

	search: function (e) {
		var key;
		var v = this.getInputElement().get('value');
		if (v === '') {
			this.element.value = '';
		}
		if (v.toLowerCase() !== this.searchText && v !== '') {
			var observer = document.id(this.options.observerid);
			if (typeOf(observer) !== 'null') {
				if (this.options.formRef) {
					observer = Fabrik.blocks[this.options.formRef].formElements[this.options.observerid];
				}
				key = observer.get('value') + '.' + v;
			} else {
				this.parent(e);
				return;
			}
			this.searchText = v.toLowerCase();
			if (this.cache['$' + key + '$']) {
				this.menuPopulateOpen(this.cache['$' + key + '$']);
			} else {
				// If you are observing a radio list then u need to get the Element js plugin value
				var obsValue = document.id(this.options.observerid).get('value');
				if (typeOf(obsValue) === 'null') {
					obsValue = Fabrik.blocks[this.options.formRef].formElements.get(this.options.observerid).get('value');
				}

				Fabrik.loader.start(this.getInputElement());
				if (this.ajax) {
					Fabrik.loader.stop(this.getInputElement());
					this.menuClose();
					this.ajax.cancel();
				}
				this.ajax = new Request({
					url : this.options.url,
					method: this.options.ajaxmethod,
					data: {
						value: v,
						fabrik_cascade_ajax_update: 1,
						v: observer.get('value')
					},

					onSuccess: function (r) {
						Fabrik.loader.stop(this.getInputElement());
						r = JSON.decode(r);
						this.completeAjax(r, v);
					}.bind(this),

					onError: function (text, error) {
						console.log(text, error);
					},
					onFailure: function (xhr) {
						console.log(xhr);
					}
				}).send();
			}
		}
	}
});