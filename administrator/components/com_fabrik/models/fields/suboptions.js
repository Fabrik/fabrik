/**
 * Admin SubOptions Editor
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var Suboptions = new Class({

	Implements: [Options],

	options: {
		sub_initial_selection: [],
		j3: false,
		defaultMax: 0
	},

	initialize: function (name, options) {
		this.setOptions(options);
		this.element = document.id(this.options.id);

		if (typeOf(this.element) === 'null') {
			if (confirm('oh dear - somethings gone wrong with loading the sub-options, do you want to reload?')) {

				// Force reload from server
				location.reload(true);
			}
		}
		this.watchButtons();
		this.watchDefaultCheckboxes();
		this.counter = 0;
		this.name = name;
		Object.each(this.options.sub_values, function (v, x) {
			var chx = Object.contains(this.options.sub_initial_selection, v) ? "checked='checked'" : '';
			this.addSubElement(v, this.options.sub_labels[x], chx);
		}.bind(this));

		if (this.options.sub_values.length === 0) {
			this.addSubElement('', '', false);
		}
		// $$$ rob - could probably do this better with firing an event from the main element page but for now this will do
		Joomla.submitbutton = function (pressbutton) {
			if (pressbutton !== 'element.cancel' && !this.onSave()) {
				return false;
			}
			Joomla.submitform(pressbutton);
		}.bind(this);

	},

	// For radio buttons we only want to have one default selected at a time
	watchDefaultCheckboxes: function () {
		this.element.addEvent('click:relay(input.sub_initial_selection)', function (e) {
			if (this.options.defaultMax === 1) {
				this.element.getElements('input.sub_initial_selection').each( function (el) {
					if (el !== e.target) {
						el.checked = false;
					}
				});
			}
		}.bind(this));
	},

	watchButtons: function () {
		if (this.options.j3) {
			this.element.addEvent('click:relay(a[data-button="addSuboption"])', function (e) {
				e.preventDefault();
				this.addSubElement();
			}.bind(this));

			this.element.addEvent('click:relay(a[data-button="deleteSuboption"])', function (e, target) {
				e.preventDefault();
				var trs = this.element.getElements('tbody tr');
				if (trs.length > 1) {
					target.getParent('tr').dispose();
				}
			}.bind(this));
			var x = this.element.getElements('a[data-button="addSuboption"]');
		} else {
			document.id('addSuboption').addEvent('click', function (e) {
				this.addOption(e);
			}.bind(this));
		}
	},

	addOption: function (e) {
		this.addSubElement();
		e.stop();
	},

	removeSubElement: function (e) {
		var id = e.target.id.replace('sub_delete_', '');
		if (document.id('sub_subElementBody').getElements('li').length > 1) {
			document.id('sub_content_' + id).dispose();
		}
		e.stop();
	},

	addJ3SubElement: function (sValue, sText, sCurChecked) {
		var chx = this._chx(sValue, sCurChecked);
		var delButton = this._deleteButton();
		var tr = new Element('tr').adopt([
			new Element('td', {'class': 'handle subhandle'}),
			new Element('td', {width: '30%'}).adopt(this._valueField(sValue)),

			new Element('td', {width: '30%'}).adopt(this._labelField(sText)),
			new Element('td', {width: '10%'}).set('html',
				chx
			),
			delButton
		]);
		var tbody = this.element.getElement('tbody');
		tbody.adopt(tr);

		if (!this.sortable) {
			this.sortable = new Sortables(tbody, {'handle': '.subhandle'});
		} else {
			this.sortable.addItems(tr);
		}
		this.counter++;

	},

	_valueField: function (sValue) {
		return new Element('input', {
			'class': 'inputbox sub_values',
			type: 'text',
			name: this.name + '[sub_values][]',
			id: 'sub_value_' + this.counter,
			size: 20,
			value: sValue,
			events: {
				'change': function (e) {
						fconsole('need to set this chb boxes value to the value field if selected, or set to blank');
					}
			}
		});
	},

	_labelField: function (sText) {
		return new Element('input', {
			'class': 'inputbox sub_labels',
			type: 'text',
			name: this.name + '[sub_labels][]',
			id: 'sub_text_' + this.counter,
			size : 20,
			value : sText
		});
	},

	_chx: function (sValue, sCurChecked) {
		return "<input class=\"inputbox sub_initial_selection\" type=\"checkbox\" value=\"" + sValue + "\" name='" + this.name + "[sub_initial_selection][]' id=\"sub_checked_" + this.counter + "\" " + sCurChecked + " />";
	},

	_deleteButton: function () {
		return new Element('td', {width: '20%'}).set('html', this.options.delButton);
	},

	addSubElement: function (sValue, sText, sCurChecked) {
		if (this.options.j3) {
			return this.addJ3SubElement(sValue, sText, sCurChecked);
		}
		sValue = sValue ? sValue : '';
		sText = sText ? sText : '';
		var chx = this._chx(sValue, sCurChecked);
		var delButton = this._deleteButton();
		delButton.getElement('a').id = 'sub_delete_' + this.counter;
		var li = new Element('li', {id: 'sub_content_' + this.counter}).adopt([
			new Element('table',  {width: '100%'}).adopt([
				new Element('tbody').adopt([
					new Element('tr').adopt([
						new Element('td', {'rowspan': 2, 'class': 'handle subhandle'}),
						new Element('td', {width: '30%'}).adopt(this._valueField(sValue)),
						new Element('td', {width: '30%'}).adopt(this._labelField(sText)),
						new Element('td', {width: '10%'}).set('html', chx),
						delButton
					])
				])
			])
		]);
		var oldLi = document.id('sub_subElementBody').getElement('li');
		if (typeOf(oldLi) !== 'null' && oldLi.innerHTML === '') {
			li.replaces(oldLi);
		} else {
			li.inject(document.id('sub_subElementBody'));
		}
		document.id('sub_delete_' + this.counter).addEvent('click', function (e) {
			this.removeSubElement(e);
		}.bind(this));

		if (!this.sortable) {
			this.sortable = new Sortables('sub_subElementBody', {'handle': '.subhandle'});
		} else {
			this.sortable.addItems(li);
		}
		this.counter++;
	},

	onSave: function () {
		var values = [],
		ret = true,
		intial_selection = [],
		evalPop = document.id('jform_params_dropdown_populate'),
		evalAdded = false;
		if (typeOf(evalPop) !== 'null' && evalPop.get('value') !== '') {
			evalAdded = true;
		}
		if (!evalAdded) {
			$$('.sub_values').each(function (dd) {
				/*
				if (dd.value === '') {
					alert(Joomla.JText._('COM_FABRIK_SUBOPTS_VALUES_ERROR'));
					ret = false;
				}
				*/
				values.push(dd.value);
			});
		}
		$$('.sub_initial_selection').each(function (dd, c) {
			dd.value = values[c];
		});
		return ret;
	}
});