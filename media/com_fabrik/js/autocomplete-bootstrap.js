/**
 * Bootstrap Auto-Complete
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
			this.matchedResult = false;
			this.setOptions(options);
			element = element.replace('-auto-complete', '');
			this.options.labelelement = typeOf(document.id(element + '-auto-complete')) === "null" ? document.getElement(element + '-auto-complete') : document.id(element + '-auto-complete');
			this.cache = {};
			this.selected = -1;
			this.mouseinsde = false;
			document.addEvent('keydown', function (e) {
				this.doWatchKeys(e);
			}.bind(this));
			this.element = typeOf(document.id(element)) === "null" ? document.getElement(element) : document.id(element);
			this.buildMenu();
			if (!this.getInputElement()) {
				fconsole('autocomplete didnt find input element');
				return;
			}
			this.getInputElement().setProperty('autocomplete', 'off');
			this.getInputElement().addEvent('keyup', function (e) {
				this.search(e);
			}.bind(this));

			this.getInputElement().addEvent('blur', function (e) {
				if (this.options.storeMatchedResultsOnly) {
					if (!this.matchedResult) {
						if (typeof(this.data) === 'undefined' || !(this.data.length === 1 && this.options.autoLoadSingleResult)) {
							this.element.value = '';
						}
					}
				}
			}.bind(this));
		}.bind(this));
	},

	search: function (e) {
		if (e.key === 'tab' || e.key === 'enter') {
			e.stop();
			this.closeMenu();
			return;
		}
		this.matchedResult = false;
		var v = this.getInputElement().get('value');
		if (v === '') {
			this.element.value = '';
		}
		if (v !== this.searchText && v !== '') {
			this.element.value = v;
			this.positionMenu();
			if (this.cache[v]) {
				this.populateMenu(this.cache[v]);
				this.openMenu();
			} else {
				Fabrik.loader.start(this.getInputElement());
				if (this.ajax) {
					this.closeMenu();
					this.ajax.cancel();
				}
				this.ajax = new Request({url: this.options.url,
					data: {
						value: v
					},
					onComplete: function (e) {
						Fabrik.loader.stop(this.getInputElement());
						this.completeAjax(e, v);
					}.bind(this)
				}).send();
			}
		}
		this.searchText = v;
	},

	completeAjax: function (r, v) {
		r = JSON.decode(r);
		this.cache[v] = r;
		Fabrik.loader.stop(this.getInputElement());
		this.populateMenu(r);
		this.openMenu();
	},

	buildMenu: function ()
	{
		this.menu = new Element('ul.dropdown-menu', {'role': 'menu'});
		this.menu.inject(document.body);
		this.menu.addEvent('mouseenter', function () {
			this.mouseinsde = true;
		}.bind(this));
		this.menu.addEvent('mouseleave', function () {
			this.mouseinsde = false;
		}.bind(this));
		this.menu.addEvent('click:relay(a)', function (e, target) {
			this.makeSelection(e, target);
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

	populateMenu: function (data) {
		// $$$ hugh - added decoding of things like &amp; in the text strings
		var li, a;
		data.map(function (item, index) {
			item.text = Encoder.htmlDecode(item.text);
			return item;
		});
		this.data = data;
		var max = this.getListMax();
		var ul = this.menu;
		ul.empty();
		if (data.length === 1 && this.options.autoLoadSingleResult) {
			this.element.value = data[0].value;
			this.fireEvent('selection', [this, this.element.value]);
		}
		if (data.length === 0) {
			li = new Element('li').adopt(new Element('div.alert.alert-info').adopt(new Element('i').set('text', Joomla.JText._('COM_FABRIK_NO_RECORDS'))));
			li.inject(ul);
		}
		for (var i = 0; i < max; i ++) {
			var pair = data[i];
			a = new Element('a', {'href': '#', 'data-value': pair.value, tabindex: '-1'}).set('text', pair.text);
			li = new Element('li').adopt(a);
			li.inject(ul);
		}
		if (data.length > this.options.max) {
			new Element('li').set('text', '....').inject(ul);
		}
	},

	makeSelection: function (e, li) {
		e.preventDefault();
		// $$$ tom - make sure an item was selected before operating on it.
		if (typeOf(li) !== 'null') {
			this.getInputElement().value = li.get('text');
			this.element.value = li.getProperty('data-value');
			this.closeMenu();
			this.fireEvent('selection', [this, this.element.value]);
			// $$$ hugh - need to fire change event, in case it's something like a join element
			// with a CDD that watches it.
			this.element.fireEvent('change', new Event.Mock(this.element, 'change'), 700);
			// $$$ hugh - fire a Fabrik event, just for good luck.  :)
			Fabrik.fireEvent('fabrik.autocomplete.selected', [this, this.element.value]);
		} else {
			//  $$$ tom - fire a notselected event to let developer take appropriate actions.
            Fabrik.fireEvent('fabrik.autocomplete.notselected', [this, this.element.value]);
		}
	},

	closeMenu: function () {
		if (this.shown) {
			this.shown = false;
			this.menu.hide();
			this.selected = -1;
			document.removeEvent('click', function (e) {
				this.doTestMenuClose(e);
			}.bind(this));
		}
	},

	openMenu: function () {
		if (!this.shown) {
			this.menu.show();
			this.shown = true;
			document.addEvent('click', function (e) {
				this.doTestMenuClose(e);
			}.bind(this));
			this.selected = 0;
			this.highlight();
		}
	},

	doTestMenuClose: function () {
		if (!this.mouseinsde) {
			this.closeMenu();
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
			if (e.code.toInt() === 40 && document.activeElement === this.getInputElement()) {
				this.openMenu();
			}
		} else {
			if (e.key === 'enter' || e.key === 'tab') {
				window.fireEvent('blur');
			}
			switch (e.code) {
			case 40://down
				if (!this.shown) {
					this.openMenu();
				}
				if (this.selected + 1 <= max) {
					this.selected ++;
				}
				this.highlight();
				e.stop();
				break;
			case 38: //up
				if (this.selected - 1 >= -1) {
					this.selected --;
					this.highlight();
				}
				e.stop();
				break;
			case 13://enter
			case 9://tab
				e.stop();
				var selectEvnt = new Event.Mock(this.getSelected(), 'click');
				this.makeSelection(selectEvnt, this.getSelected());
				this.closeMenu();
				break;
			case 27://escape
				e.stop();
				this.closeMenu();
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
		this.matchedResult = true;
		this.menu.getElements('li').each(function (li, i) {
			if (i === this.selected) {
				li.addClass('selected').addClass('active');
			} else {
				li.removeClass('selected').removeClass('active');
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
		if (v !== this.searchText && v !== '') {
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
			this.positionMenu();
			if (this.cache[key]) {
				this.populateMenu(this.cache[key]);
				this.openMenu();
			} else {
				Fabrik.loader.start(this.getInputElement());
				if (this.ajax) {
					this.closeMenu();
					this.ajax.cancel();
				}
				this.ajax = new Request({
					url : this.options.url,
					data: {
						value: v,
						fabrik_cascade_ajax_update: 1,
						v: observer.get('value')
					},

					onSuccess: function (e) {
						this.completeAjax(e);
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
		this.searchText = v;
	}
});