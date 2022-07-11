/**
 * Tabs
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var Tabs = new Class({
	initialize : function (el, tabs, editable) {
		this.editable = editable;
		this.iconGen = new IconGenerator({scale: 0.5});
		this.el = document.id(el);
		this.tabs = $H({});
		this.build(tabs);
	},
	
	build: function (tabs) {
		Fabrik.fireEvent('fabrik.history.off', this);
		if (this.editable) {
			
			var a = new Element('a', {
				'href': '#',
				'events': {
					'click': function (e) {
						this.addWindow(e);
					}.bind(this)
				}
			});
			
			art = this.iconGen.create(icon.plus, {fill: {color: ['#40B53E', '#378F36']}});
			art.inject(a);
			
			this.el.adopt(new Element('li', {
				'class': 'add',
				'events': {
					'click': function (e) {
						this.addWindow(e);
					}.bind(this)
				}
			}).adopt([new Element('span').set('text', 'add'), a]));
		}
		tabs.each(function (t) {
			this.add(t);
		}.bind(this));
		this.setActive(tabs[0]);
		var fn = function () {
			Fabrik.fireEvent('fabrik.history.on', this);
		};
		fn.delay(500);
	},

	remove: function (e) {
		var n;
		if (typeOf(e) === 'event') {
			n = e.target.getParent('li').getElement('span').get('text').trim();
			e.stop();
		} else {
			n = e;
		}
		if (confirm('Delete tab?')) {
			if (this.tabs.getLength() <= 1) {
				alert('you can not remove all tabs');
				return;
			}
			var t = this.tabs[n];
			Fabrik.fireEvent('fabrik.tab.remove', [ this, t ]);
			this.tabs.erase(n);
			t.destroy();
			var newkey = this.tabs.getKeys()[0];
			this.setActive(this.tabs[newkey]);
		}
	},

	addWindow: function (e) {
		var c = new Element('form');
		c.adopt(new Element('input', {
			'name' : 'label',
			'events': {
				'keydown': function (e) {
					if (e.key === 'enter') {
						e.stop();
					}
				}
			}
		}), new Element('br'), new Element('input', {
			'class' : 'button',
			'type' : 'button',
			'events' : {
				'click' : function (e) {
					var name = e.target.getParent().getElement('input[name=label]').get('value');
					if (name === '') {
						alert('please supply a tab label');
						return false;
					}
					this.add(name);
					Fabrik.Windows[this.windowopts.id].close();
				}.bind(this)
			},
			'value' : 'add'
		}));
		this.windowopts = {
			'id': 'addTab',
			'type': 'modal',
			title: 'Add',
			content: c,
			width: 200,
			height: 200,
			'minimizable': false,
			'collapsible': true
		};
		var mywin = Fabrik.getWindow(this.windowopts);
	},

	add: function (t) {
		var li = new Element('li', {
			
			'events': {
				'click': function (e) {
					this.setActive(li);
				}.bind(this),
				
				'mouseover': function (e) {
					Fabrik.fireEvent('fabrik.tab.hover', [ t ]);
				}
			}
		});
		li.adopt(new Element('span').set('text', t + ' '));
		
		var a = new Element('a', {
			'href': '#',
			'events': {
				'click': function (e) {
					this.remove(e);
				}.bind(this)
			}
		});
		
		if (this.editable) {
			art = this.iconGen.create(icon.cross);
			art.inject(a);
			li.adopt(a);
		}
		li.store('ref', t);
		if (this.editable) {
			var add = this.el.getElement('li.add');
			li.inject(add, 'before');
		} else {
			li.inject(this.el, 'inside');
		}
		this.setActive(li);
		this.tabs[t] = li;
		Fabrik.fireEvent('fabrik.history.add', [this, this.remove, t, this.add, t]);
		Fabrik.fireEvent('fabrik.tab.add', [this, t]);
	},

	setActive: function (a) {
		var tname = typeOf(a) === 'string' ? a : a.retrieve('ref');
		var active = a;
		Fabrik.fireEvent('fabrik.tab.click', tname);
		this.tabs.each(function (t) {
			t.removeClass('active');
			t.addClass('inactive');
			if (t.retrieve('ref') === tname) {
				active = t;
			}
		});
		active.addClass('active');
		active.removeClass('inactive');
	},

	reorder: function () {

	}
});