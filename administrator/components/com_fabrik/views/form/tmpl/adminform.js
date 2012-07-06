fabrikAdminForm = new Class({
	
	Extends: PluginManager,
		
	initialize: function (plugins, lang) {
		this.parent(plugins, lang);
		this.opts.actions = [{
				'value': 'front',
				'label': Joomla.JText._('COM_FABRIK_FRONT_END')
			}, {
				'value': 'back',
				'label': Joomla.JText._('COM_FABRIK_BACK_END')
			}, {
				'value': 'both',
				'label': Joomla.JText._('COM_FABRIK_BOTH')
			}];
		this.opts.when = [{
				'value': 'new',
				'label': Joomla.JText._('COM_FABRIK_NEW')
			}, {
				'value': 'edit',
				'label': Joomla.JText._('COM_FABRIK_EDIT')
			}, {
				'value': 'both',
				'label': Joomla.JText._('COM_FABRIK_BOTH')
			}];
		this.opts.type = 'form';
	},
	
	getPluginTop: function (plugin, opts) {
		var yesno = this.getPublishedYesNo(opts);
		var s = this._makeSel('inputbox events', 'jform[plugin_events][]', this.opts.when, opts.event);
	
		var tr1 = new Element('tr').adopt(
			new Element('td').adopt([
				new Element('input', {
					'value': Joomla.JText._('COM_FABRIK_SELECT_DO'),
					'size': 4,
					'readonly': true,
					'class': 'readonly'
				}),
				this._makeSel('inputbox elementtype', 'jform[plugin][]', this.plugins, plugin),
				new Element('input', {
					'value': Joomla.JText._('COM_FABRIK_IN'),
					'size': 1,
					'readonly': true,
					'class': 'readonly'
				}),
				this._makeSel('inputbox elementtype', 'jform[plugin_locations][]', this.opts.actions, opts.location),
				new Element('input', {
					'value': Joomla.JText._('COM_FABRIK_ON'),
					'size': 1,
					'readonly': true,
					'class': 'readonly'
				}),
				s
			])
		);
		var tr2 = new Element('tr').adopt(
				new Element('td').set('html', yesno)
		);
		return new Element('table').adopt(new Element('tbody').adopt([tr2, tr1]));
	}
});
