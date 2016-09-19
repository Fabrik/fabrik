/**
 * List Update Column
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/**
 * Simple store for element js objects
 * Need to be able to trigger onSave on things like date elements to get correct format
 */
define(['jquery', 'fab/list-plugin'], function (jQuery, FbListPlugin) {
	var UpdateColSelect = new Class({

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

	var FbListUpdate_col = new Class({
		Extends : FbListPlugin,
		initialize: function (options) {
			this.parent(options);
			if (this.options.userSelect) {
				var k = 'filter_update_col' + this.options.ref + '_' + this.options.renderOrder;
				Fabrik[k] = new UpdateColSelect();
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
			var tds, tr_clone, i, self = this;
			self.windowopts = {
				'id': 'update_col_win_' + self.options.ref + '_' + self.options.renderOrder,
				title: Joomla.JText._('PLG_LIST_UPDATE_COL_UPDATE'),
				loadMethod: 'html',
				content: self.options.form,
				width: 400,
				destroy: false,
				height: 300,
				onOpen: function () {
					this.fitToContent(false);
				},
				onContentLoaded: function (win) {
					var form = document.id('update_col' + self.options.ref + '_' + self.options.renderOrder);

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
							tds = tr.getElements('td');
							tds[0].getElement('select').selectedIndex = 0;
							tds[1].empty();
							tr.show();
						} else {
							tr_clone = tr.clone();
							tds = tr_clone.getElements('td');
							tds[0].getElement('select').selectedIndex = 0;
							tds[1].empty();
							tr_clone.inject(tr, 'after');
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
						var els = target.getParent('tbody').getElements('.update_col_elements');
						for (i = 0; i < els.length; i++) {
							if (els[i] === target) {
								continue;
							}
							if (els[i].selectedIndex === target.selectedIndex) {
								// @TODO language
								window.alert('This element has already been selected!');
								return;
							}
						}
						var opt = target.options[target.selectedIndex];
						var row = target.getParent('tr');
						Fabrik.loader.start(row);
						var update = row.getElement('td.update_col_value');
						var v = target.get('value');
						var plugin = opt.get('data-plugin');
						var id = opt.get('data-id');
						var counter = 0;

						// Piggy backing on the list advanced search code to get an element and its js
						var url = 'index.php?option=com_fabrik&task=list.elementFilter&format=raw';

						// It looks odd - but to get the element js code to load in correct we need to set the context
						// to a visualization
						new Request.HTML({'url': url,
							'update': update,
							'data': {
								'element': v,
								'id': self.options.listid,
								'elid': id,
								'plugin': plugin,
								'counter': counter,
								'listref':  self.options.ref,
								'context': 'visualization',
								'parentView': 'update_col' + self.options.ref + '_' + self.options.renderOrder,
								'fabrikIngoreDefaultFilterVal': 1
							},
							'onComplete': function () {
								Fabrik.loader.stop(row);
								self.win.fitToContent(false);
							}
						}).send();
					});

					// Submit the update
					form.getElement('input[type=button]').addEvent('click', function (e) {
						e.stop();
						var i;
						Fabrik['filter_update_col'  + self.options.ref + '_' + self.options.renderOrder].onSumbit();

						var listForm = document.id('listform_' + self.options.ref);

						// Grab all the update settings and put them in a hidden field for
						// later extraction within the update_col php code.
						i = new Element('input', {'type': 'hidden', 'value': form.toQueryString(),
							'name': 'fabrik_update_col'});
						i.inject(listForm, 'bottom');
						self.list.submit('list.doPlugin');

					});
				}
			};
			self.win = Fabrik.getWindow(self.windowopts);
			self.win.close();
		}
	});

	return FbListUpdate_col;
});

