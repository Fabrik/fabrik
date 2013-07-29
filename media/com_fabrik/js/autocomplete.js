/**
 * @author Robert
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $H:true,unescape:true */

var FbAutocomplete = new Class({
	
	Implements: [Options, Events],
	
	options: {
		menuclass: 'auto-complete-container',
		classes: {
			'ul': 'results',
			'li': 'result'
		},
		url: 'index.php',
		max: 10,
		onSelection: Class.empty,
		autoLoadSingleResult: true,
		storeMatchedResultsOnly: false // Only store a value if selected from picklist
	},

	initialize: function (element, options) {
		this.matchedResult = false;
		this.setOptions(options);
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
					if (!(this.data.length === 1 && this.options.autoLoadSingleResult)) {
						this.element.value = '';
					}
				}
			}
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
		this.menu = new Element('div', {'class': this.options.menuclass, 'styles': {'position': 'absolute'}}).adopt(new Element('ul', {'class': this.options.classes.ul}));
		this.menu.inject(document.body);
		this.menu.addEvent('mouseenter', function () {
			this.mouseinsde = true;
		}.bind(this));
		this.menu.addEvent('mouseleave', function () {
			this.mouseinsde = false;
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
		data.map(function (item, index) {
			item.text = Encoder.htmlDecode(item.text);
			return item;
		});
		this.data = data;
		var max = this.getListMax();
		var ul = this.menu.getElement('ul');
		ul.empty();
		if (data.length === 1 && this.options.autoLoadSingleResult) {
			this.matchedResult = true;
			this.element.value = data[0].value;
			this.fireEvent('selection', [this, this.element.value]);
		}
		for (var i = 0; i < max; i ++) {
			var pair = data[i];
			var li = new Element('li', {'data-value': pair.value, 'class': 'unselected ' + this.options.classes.li}).set('text', pair.text);
			li.inject(ul);
			li.addEvent('click', function (e) {
				this.makeSelection(e);
			}.bind(this));
		}
		if (data.length > this.options.max) {
			new Element('li').set('text', '....').inject(ul);
		}
	},
	
	makeSelection: function (e) {
		var li = e.target;
		// $$$ tom - make sure an item was selected before operating on it.
		if (typeOf(li) !== 'null') {
			this.getInputElement().value = li.get('text');
			this.element.value = li.getProperty('data-value');
			
			this.matchedResult = true;
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
			this.menu.fade('out');
			this.selected = -1;
			document.removeEvent('click', function (e) {
				this.doTestMenuClose(e);
			}.bind(this));
		}
	},
	
	openMenu: function () {
		if (!this.shown) {
			this.shown = true;
			this.menu.setStyle('visibility', 'visible').fade('in');
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
				if (this.selected + 1 < max) {
					this.selected ++;
					this.highlight();
				}
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
				this.makeSelection(selectEvnt);
				break;
			case 27://escape
				e.stop();
				this.matchedResult = false;
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
				li.addClass('selected');
			} else {
				li.removeClass('selected');
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
				
				// If you are observing a radio list then u need to get the Element js plugin value
				var obsValue = document.id(this.options.observerid).get('value');
				if (typeOf(obsValue) === 'null') {
					obsValue = Fabrik.blocks[this.options.formRef].formElements.get(this.options.observerid).get('value');
				}
					
				this.ajax = new Request({
					method: 'post',
					url : this.options.url,
					data: {
						value: v,
						fabrik_cascade_ajax_update: 1,
						v: obsValue
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