/**
 * List Update Column
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/**
 * Simple store for element js objects
 * Need to be able to trigger onSave on things like date elements to get correct format
 */
UpdateColSelect = new Class({

	initialize: function () {
		this.updates = {};
	},

	/**
	 * As we are piggybacking on top of the advanced search code addFilter is called when the
	 * ajax request returns.
	 */
	addFilter: function (pluginType, filter) {
		if (!this.updates[pluginType]) {
			this.updates[pluginType] = [];
		}
		this.updates[pluginType].push(filter);
	},

	/**
	 * Ensure that date elements set themselves to the correct date format
	 */
	onSumbit: function () {
		if (this.updates.date) {
			this.updates.date.each(function (f) {
				f.onSubmit();
			});
		}
	}
});

var FbListUpdateCol = new Class({
	Extends : FbListPlugin,
	initialize: function (options) {
		this.parent(options);
		if (this.options.userSelect) {
			Fabrik['filter_update_col' + this.options.ref] = new UpdateColSelect();
			this.makeUpdateColWindow();
		}
	},

	buttonAction: function () {
		if (this.options.userSelect) {
			this.win.open();
		} else {
			this.list.submit('list.doPlugin');
		}
	},

	makeUpdateColWindow: function () {
		this.windowopts = {
			'id': 'update_col_win_' + this.options.ref,
			title: Joomla.JText._('PLG_LIST_UPDATE_COL_UPDATE'),
			loadMethod: 'html',
			content: this.options.form,
			width: 400,
			destroy: false,
			height: 300,
			onContentLoaded: function (win) {
				var form = document.id('update_col' + this.options.ref);

				// Add a row
				form.addEvent('click:relay(a.add)', function (e, target) {
					e.preventDefault();
					var tr;
					var thead = target.getParent('thead');
					if (thead) {
						tr = form.getElements('tbody tr').getLast();
					} else {
						tr = target.getParent('tr');
					}
					if (tr.getStyle('display') === 'none') {
						tr.show();
					} else {
						tr.clone().inject(tr, 'after');
					}

				});

				// Delete a row
				form.addEvent('click:relay(a.delete)', function (e, target) {
					e.preventDefault();
					var trs = form.getElements('tbody tr');
					if (trs.length === 1) {
						trs.getLast().hide();
					} else {
						target.getParent('tr').destroy();
					}
				});

				// Select an element plugin and load it
				form.addEvent('change:relay(select.key)', function (e, target) {
					var opt = target.options[target.selectedIndex];
					var row = target.getParent('tr');
					Fabrik.loader.start(row);
					var update = row.getElement('td.update_col_value');
					var v = target.get('value');
					var plugin = opt.get('data-plugin');
					var id = opt.get('data-id');
					var counter = 0;

					// Piggy backing on the list advanced search code to get an element and its js
					var url = "index.php?option=com_fabrik&task=list.elementFilter&format=raw";

					// It looks odd - but to get the element js code to load in correct we need to set the context to a visualization
					new Request.HTML({'url': url,
						'update': update,
						'data': {
							'element': v,
							'id': this.options.listid,
							'elid': id,
							'plugin': plugin,
							'counter': counter,
							'listref':  this.options.ref,
							'context': 'visualization',
							'parentView': 'update_col' + this.options.ref,
							'fabrikIngoreDefaultFilterVal': 1,
							'filterOverride': 'field'
						},
						'onComplete': function () {
							Fabrik.loader.stop(row);
						}
					}).send();
				}.bind(this));

				// Submit the update
				form.getElement('input[type=button]').addEvent('click', function (e) {
					e.stop();
					var i;
					Fabrik['filter_update_col'  + this.options.ref].onSumbit();

					var listForm = document.id('listform_' + this.options.ref);

					// Grab all the update settings and put them in a hidden field for later extraction within the update_col php code.
					i = new Element('input', {'type': 'hidden', 'value': form.toQueryString(), 'name': 'fabrik_update_col'});
					i.inject(listForm, 'bottom');
					this.list.submit('list.doPlugin');

				}.bind(this));
			}.bind(this)
		};
		this.win = Fabrik.getWindow(this.windowopts);
		this.win.close();
	}
});
