/**
 * Admin RepeatGroup Editor
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbRepeatGroup = new Class({

	Implements: [Options, Events],

	options: {
		repeatmin: 1
	},

	initialize: function (element, options) {
		this.element = document.id(element);
		this.setOptions(options);
		this.counter = this.getCounter();
		this.watchAdd();
		this.watchDelete();
	},

	repeatContainers: function () {
		return this.element.getElements('.repeatGroup');
	},

	watchAdd : function () {
		var newid;
		this.element.getElement('a[data-button=addButton]').addEvent('click', function (e) {
			e.stop();
			var div = this.repeatContainers().getLast();
			newc = this.counter + 1;
			var id = div.id.replace('-' + this.counter, '-' + newc);
			var c = new Element('div', {'class': 'repeatGroup', 'id': id}).set('html', div.innerHTML);
			c.inject(div, 'after');
			this.counter = newc;

			// Update params ids
			if (this.counter !== 0) {
				c.getElements('input, select').each(function (i) {
					var newPlugin = false;
					var newid = '';
					var oldid = i.id;
					if (i.id !== '') {
						var a = i.id.split('-');
						a.pop();
						newid = a.join('-') + '-' + this.counter;
						i.id = newid;
					}

					this.increaseName(i);
					$H(FabrikAdmin.model.fields).each(function (plugins, type) {
						var newPlugin = false;
						if (typeOf(FabrikAdmin.model.fields[type][oldid]) !== 'null') {
							var plugin = FabrikAdmin.model.fields[type][oldid];
							newPlugin = Object.clone(plugin);
							try {
								newPlugin.cloned(newid, this.counter);
							} catch (err) {
								fconsole('no clone method available for ' + i.id);
							}
						}
						if (newPlugin !== false) {
							FabrikAdmin.model.fields[type][i.id] = newPlugin;
						}
					}.bind(this));


				}.bind(this));

				c.getElements('img[src=components/com_fabrik/images/ajax-loader.gif]').each(function (i) {

					var a = i.id.split('-');
					a.pop();
					var newid = a.join('-') + '-' + this.counter + '_loader';
					i.id = newid;
				}.bind(this));
			}
		}.bind(this));
	},

	getCounter : function () {
		return this.repeatContainers().length;
	},

	watchDelete : function () {
		this.element.getElements('a[data-button=deleteButton]').removeEvents();
		this.element.getElements('a[data-button=deleteButton]').each(function (r, x) {
			r.addEvent('click', function (e) {
				e.stop();
				var count = this.getCounter();
				if (count > this.options.repeatmin) {
					var u = this.repeatContainers().getLast();
					u.destroy();
				}
				this.rename(x);
			}.bind(this));
		}.bind(this));
	},

	increaseName : function (i) {
		var namebits = i.name.split('][');
		var ref = namebits[2].replace(']', '').toInt() + 1;
		namebits.splice(2, 1, ref);
		i.name = namebits.join('][') + ']';
	},

	rename : function (x) {
		this.element.getElements('input, select').each(function (i) {
			i.name = this._decreaseName(i.name, x);
		}.bind(this));
	},

	_decreaseName: function (n, delIndex) {
		var namebits = n.split('][');
		var i = namebits[2].replace(']', '').toInt();
		if (i >= 1  && i > delIndex) {
			i --;
		}
		if (namebits.length === 3) {
			i = i + ']';
		}
		namebits.splice(2, 1, i);
		var r = namebits.join('][');
		return r;
	}
});