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

	watchAdd: function () {
		this.element.getElement('a[data-button=addButton]').addEvent('click', function (e) {
			e.stop();
			var div = this.repeatContainers().getLast();
			// repeatgroup div does not have an id - but leaving this in case one is added later
			var id = div.id.replace('-' + (this.counter - 1), '-' + this.counter);
			var c = new Element('div', {'class': 'repeatGroup', 'id': id}).set('html', div.innerHTML);
			c.inject(div, 'after');

			// Update params ids
			if (this.counter !== 0) {
				var chgEvent = new Event('change');
				c.getElements('input, select, textarea, label, button').each(function (i) {
					// Reset all select dropdowns to default for cloned item.
					if (i.get('tag') == 'select') {
						var opt = i.getElement('option');
						if (opt) {
							i.set('value', opt.get('value'));
							i.dispatchEvent(chgEvent);
						}
					}

					// All fabrik elements have ids - if no id we cannot do much.
					if (i.id === '') {
						return;
					}

					var oldId = i.id;
					newId = this._incrementId(i);

					c.getElements('[for="' + oldId + '"]').each(function (j) {
						j.set('for',newId);
					});

					if (i.name) {
						i.name = this._incrementName(i.name);
					}

					$H(FabrikAdmin.model.fields).each(function (plugins, type) {
						var newPlugin = false;
						if (typeOf(FabrikAdmin.model.fields[type][oldId]) !== 'null') {
							var newPlugin = Object.clone(FabrikAdmin.model.fields[type][oldId]);
							if (newPlugin !== false) {
								newPlugin.cloned(newId, this.counter);
								FabrikAdmin.model.fields[type][newId] = newPlugin;
							}
						}
					}.bind(this));
				}.bind(this));

				c.getElements('img[src=components/com_fabrik/images/ajax-loader.gif]').each(function (i) {
					if (i.id) {
						i.id = this._incrementId(i);
					}
				}.bind(this));

				// Replace data-showon counters
				this._incrementShowon(c);
				FabrikAdmin.reTip();
				document.dispatchEvent(new CustomEvent('fabrikadmin.repeatgroup.add', {'detail':c}));
			}

			this.counter++;
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
				if (this.getCounter() <= this.options.repeatmin) {
					return;
				}
				var u = this.repeatContainers().getLast();
				document.dispatchEvent(new CustomEvent('fabrikadmin.repeatgroup.remove', {'detail':u}));
				u.destroy();
				this.counter--;
			}.bind(this));
		}.bind(this));
	},

	_incrementId: function (target) {
		// Handle both standard repeat-group ids and fabrikmodalrepeat which have J! subform naming
		var id = target.id;
		var newC = this.counter;
		var oldC = newC - 1;
		if (id.indexOf(oldC + '__') >= 0) {
			var id = target.id = id.replace(oldC + '__', newC + "__");
		} else if (id.indexOf('-' + oldC) >= 0) {
			var id = target.id = id.replace('-' + oldC, '-' + newC);
		}
		return id;
	},

	_incrementName: function (n) {
		var namebits = n.split('][');
		var i = namebits[2].replace(']', '').toInt();
		i++;
		if (namebits.length === 3) {
			i = i + ']';
		}
		namebits.splice(2, 1, i);
		return namebits.join('][');
	},

	_incrementShowon: function(rg) {
		// Replace data-showon counters
		rg.getElements('div[data-showon]').each(function (div) {
			var showon = div.getProperty('data-showon');
			// showon is a string of an array of json.
			try {
				showon = JSON.parse(showon);
			}
			catch(error) {
				fconsole('Fabrik repeatgroup: Showon incorrect format:', showon);
				return;
			}
			if (!Array.isArray(showon)) {
				fconsole('Fabrik repeatgroup: Showon not array:', showon);
				return;
			}
			for (var j = 0; j < showon.length; j++) {
				if (showon[j].hasOwnProperty('field')) {
					showon[j].field = this._incrementName(showon[j].field);
				}
			}
			div.setAttribute('data-showon', JSON.stringify(showon));
		}.bind(this));

		if (typeof Joomla.setUpShowon === "function") {
			Joomla.setUpShowon(rg);
		}
	},
});
