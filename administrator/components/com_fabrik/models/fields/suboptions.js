var Suboptions = new Class({
	
	Implements: [Options],
	
	options: {
		sub_initial_selection: []
	},
	
	initialize: function (name, options) {
		this.setOptions(options);
		this.counter = 0;
		this.name = name;
		document.id('addSuboption').addEvent('click', this.addOption.bindWithEvent(this));
		this.options.sub_values.each(function (v, x) {
			var chx = this.options.sub_initial_selection.indexOf(v) === -1 ? '' : "checked='checked'";
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
	
	addSubElement: function (sValue, sText, sCurChecked) {
		sValue = sValue ? sValue : '';
		sText = sText ? sText : '';
		var chx = "<input class=\"inputbox sub_initial_selection\" type=\"checkbox\" value=\"" + sValue + "\" name='" + this.name + "[sub_initial_selection][]' id=\"sub_checked_" + this.counter + "\" " + sCurChecked + " />";
		var li = new Element('li', {id: 'sub_content_' + this.counter}).adopt([
			new Element('table',  {width: '100%'}).adopt([
				new Element('tbody').adopt([
					new Element('tr').adopt([
						new Element('td', {'rowspan': 2, 'class': 'handle subhandle'}),
						new Element('td', {width: '30%'}).adopt(
							new Element('input', {
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
							})),

							new Element('td', {width: '30%'}).adopt(
								new Element('input', {
									'class': 'inputbox sub_labels',
									type: 'text',
									name: this.name + '[sub_labels][]',
									id: 'sub_text_' + this.counter,
									size : 20,
									value : sText
								})),
								new Element('td', {width: '10%'}).set('html',
									chx
								),
								new Element('td', {width: '20%'}).adopt(
									new Element('a', {
										'class': 'removeButton',
										href: '#',
										id: 'sub_delete_' + this.counter
									}).set('text', 'Delete')
								)
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
				if (dd.value === '') {
					alert(Joomla.JText._('COM_FABRIK_SUBOPTS_VALUES_ERROR'));
					ret = false;
				}
				values.push(dd.value);
			});
		}
		$$('.sub_initial_selection').each(function (dd, c) {
			dd.value = values[c];
		});
		return ret;
	}
});