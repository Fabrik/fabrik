/**
 * List Inline Edit
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
define(['jquery', 'fab/list-plugin', 'fab/fabrik'], function (jQuery, FbListPlugin, Fabrik) {
	var FbListInlineedit = new Class({
		Extends: FbListPlugin,

		initialize: function (options) {
			this.parent(options);
			this.defaults = {};
			this.editors = {};
			this.inedit = false;
			this.saving = false;

			// Assigned in list.js fabrik3
			if (typeOf(this.getList().getForm()) === 'null') {
				return false;
			}
			this.listid = this.options.listid;
			this.setUp();

			Fabrik.addEvent('fabrik.list.clearrows', function () {
				this.cancel();
			}.bind(this));

			Fabrik.addEvent('fabrik.list.inlineedit.stopEditing', function () {
				this.stopEditing();
			}.bind(this));

			Fabrik.addEvent('fabrik.list.updaterows', function () {
				this.watchCells();
			}.bind(this));

			Fabrik.addEvent('fabrik.list.ini', function () {
				var table = this.getList();
				var formData = table.form.toQueryString().toObject();
				formData.format = 'raw';
				formData.listref = this.options.ref;
				var myFormRequest = new Request.JSON({
					'url'        : '',
					data         : formData,
					onComplete   : function () {
						console.log('complete');
					},
					onSuccess    : function (json) {
						json = Json.evaluate(json.stripScripts());
						table.options.data = json.data;
					}.bind(this),
					'onFailure'  : function (xhr) {
						console.log('ajax inline edit failure', xhr);
					},
					'onException': function (headerName, value) {
						console.log('ajax inline edit exception', headerName, value);
					}
				}).send();
			}.bind(this));

			// Check for a single element whose click value should trigger the save (ie radio buttons)
			Fabrik.addEvent('fabrik.element.click', function () {
				if (Object.getLength(this.options.elements) === 1 && this.options.showSave === false) {
					this.save(null, this.editing);
				}
			}.bind(this));

			Fabrik.addEvent('fabrik.list.inlineedit.setData', function () {
				if (typeOf(this.editOpts) === 'null') {
					return;
				}
				$H(this.editOpts.plugins).each(function (fieldid) {
					var e = Fabrik['inlineedit_' + this.editOpts.elid].elements[fieldid];
					delete e.element;
					e.update(this.editData[fieldid]);
					e.select();
				}.bind(this));
				this.watchControls(this.editCell);
				this.setFocus(this.editCell);
			}.bind(this));

			// Click outside list clears down selection
			window.addEvent('click', function (e) {
				if (!e.target.hasClass('fabrik_element') && this.td) {
					this.td.removeClass(this.options.focusClass);
					this.td = null;
				}
			}.bind(this));
		},

		setUp: function () {
			if (typeOf(this.getList().getForm()) === 'null') {
				return;
			}
			this.scrollFx = new Fx.Scroll(window, {
				'wait': false
			});
			this.watchCells();
			document.addEvent('keydown', function (e) {
				this.checkKey(e);
			}.bind(this));
		},

		watchCells: function () {
			var firstLoaded = false;
			this.getList().getForm().getElements('.fabrik_element').each(function (td, x) {

				if (this.canEdit(td)) {
					if (!firstLoaded && this.options.loadFirst) {
						firstLoaded = this.edit(null, td);
						if (firstLoaded) {
							this.select(null, td);
						}
					}
					if (!this.isEditable(td)) {
						return;
					}
					this.setCursor(td);
					td.removeEvents();
					td.addEvent(this.options.editEvent, function (e) {
						this.edit(e, td);
					}.bind(this));
					td.addEvent('click', function (e) {
						this.select(e, td);
					}.bind(this));

					td.addEvent('mouseenter', function (e) {
						if (!this.isEditable(td)) {
							td.setStyle('cursor', 'pointer');
						}
					}.bind(this));
					td.addEvent('mouseleave', function (e) {
						td.setStyle('cursor', '');
					});
				}
			}.bind(this));
		},

		checkKey: function (e) {
			var nexttds, row, index;
			if (typeOf(this.td) !== 'element') {
				return;
			}
			switch (e.code) {
				case 39:
					//right
					if (this.inedit) {
						return;
					}
					if (typeOf(this.td.getNext()) === 'element') {
						e.stop();
						this.select(e, this.td.getNext());
					}
					break;
				case 9:
					//tab - don't navigate with tab - moofs form field tab ordering if we do
					if (this.inedit) {
						if (this.options.tabSave) {
							if (typeOf(this.editing) === 'element') {
								this.save(e, this.editing);
							} else {
								this.edit(e, this.td);
							}
						}
						return;
					}
					break;
				case 37: //left
					if (this.inedit) {
						return;
					}
					if (typeOf(this.td.getPrevious()) === 'element') {
						e.stop();
						this.select(e, this.td.getPrevious());
					}
					break;
				case 40:
					//down
					if (this.inedit) {
						return;
					}
					row = this.td.getParent();
					if (typeOf(row) === 'null') {
						return;
					}
					index = row.getElements('td').indexOf(this.td);
					if (typeOf(row.getNext()) === 'element') {
						e.stop();
						nexttds = row.getNext().getElements('td');
						this.select(e, nexttds[index]);
					}
					break;
				case 38:
					//up
					if (this.inedit) {
						return;
					}
					row = this.td.getParent();
					if (typeOf(row) === 'null') {
						return;
					}
					index = row.getElements('td').indexOf(this.td);
					if (typeOf(row.getPrevious()) === 'element') {
						e.stop();
						nexttds = row.getPrevious().getElements('td');
						this.select(e, nexttds[index]);
					}
					break;
				case 27:
					//escape
					e.stop();
					if (!this.inedit) {
						this.td.removeClass(this.options.focusClass);
						this.td = null;
					} else {
						this.select(e, this.editing);
						this.cancel(e);
					}

					break;
				case 13:
					//enter

					// Already editing or no cell selected
					if (this.inedit || typeOf(this.td) !== 'element') {
						return;
					}
					e.stop();
					if (typeOf(this.editing) === 'element') {
						// stop textarea elements from submitting when you press enter
						if (this.editors[this.activeElementId].contains('<textarea')) {
							return;
						}
						this.save(e, this.editing);
					} else {
						this.edit(e, this.td);
					}
					break;
			}
		},

		select: function (e, td) {
			if (!this.isEditable(td)) {
				return;
			}
			var element = this.getElementName(td);
			var opts = this.options.elements[element];
			if (typeOf(opts) === false) {
				return;
			}
			if (typeOf(this.td) === 'element') {
				this.td.removeClass(this.options.focusClass);
			}
			this.td = td;
			if (typeOf(this.td) === 'element') {
				this.td.addClass(this.options.focusClass);
			}
			if (typeOf(this.td) === 'null') {
				return;
			}
			if (e && (e.type !== 'click' && e.type !== 'mouseover')) {
				//if using key nav scroll the cell into view
				var p = this.td.getPosition();
				var x = p.x - (window.getSize().x / 2) - (this.td.getSize().x / 2);
				var y = p.y - (window.getSize().y / 2) + (this.td.getSize().y / 2);
				this.scrollFx.start(x, y);
			}
		},

		/**
		 * Parse the td class name to grab the element name
		 *
		 * @param   DOM node  td  Cell to parse.
		 *
		 * @return  string  Element name
		 */
		getElementName: function (td) {
			var c = td.className.trim().split(' ').filter(function (item, index) {
				return item !== 'fabrik_element' && item !== 'fabrik_row' && !item.contains('hidden');
			});
			var element = c[0].replace('fabrik_row___', '');
			return element;
		},

		setCursor: function (td) {
			var element = this.getElementName(td);
			var opts = this.options.elements[element];
			if (typeOf(opts) === 'null') {
				return;
			}
			td.addEvent('mouseover', function (e) {
				if (this.isEditable(e.target)) {
					e.target.setStyle('cursor', 'pointer');
				}
			});
			td.addEvent('mouseleave', function (e) {
				if (this.isEditable(e.target)) {
					e.target.setStyle('cursor', '');
				}
			});
		},

		isEditable: function (cell) {
			var res, rowid;
			if (cell.hasClass('fabrik_uneditable') || cell.hasClass('fabrik_ordercell') ||
				cell.hasClass('fabrik_select') || cell.hasClass('fabrik_actions')) {
				return false;
			}
			rowid = this.getRowId(cell.getParent('.fabrik_row'));
			res = this.getList().firePlugin('onCanEditRow', rowid);
			return res;
		},

		getPreviousEditable: function (active) {
			var found = false;
			var tds = this.getList().getForm().getElements('.fabrik_element');
			for (var i = tds.length; i >= 0; i--) {
				if (found) {
					if (this.canEdit(tds[i])) {
						return tds[i];
					}
				}
				if (tds[i] === active) {
					found = true;
				}
			}
			return false;
		},

		getNextEditable: function (active) {
			var found = false;
			var next = this.getList().getForm().getElements('.fabrik_element').filter(function (td, i) {
				if (found) {
					if (this.canEdit(td)) {
						found = false;
						return true;
					}
				}
				if (td === active) {
					found = true;
				}
				return false;
			}.bind(this));
			return next.getLast();
		},

		canEdit: function (td) {
			if (!this.isEditable(td)) {
				return false;
			}
			var element = this.getElementName(td);
			var opts = this.options.elements[element];
			if (typeOf(opts) === 'null') {
				return false;
			}
			return true;
		},

		edit: function (e, td) {
			if (this.saving) {
				return;
			}
			Fabrik.fireEvent('fabrik.plugin.inlineedit.editing');

			// Only one field can be edited at a time
			if (this.inedit) {
				// If active event is mouse over - close the current editor
				if (this.options.editEvent === 'mouseover') {
					if (td === this.editing) {
						return;
					}
					this.select(e, this.editing);
					this.cancel();
				} else {
					return;
				}
			}
			if (!this.canEdit(td)) {
				return false;
			}
			if (typeOf(e) !== 'null') {
				e.stop();
			}
			var element = this.getElementName(td);
			var rowid = this.getRowId(td);
			var opts = this.options.elements[element];
			if (typeOf(opts) === 'null') {
				return;
			}
			this.inedit = true;
			this.editing = td;
			this.activeElementId = opts.elid;
			this.defaults[rowid + '.' + opts.elid] = td.innerHTML;

			var data = this.getDataFromTable(td);

			if (typeOf(this.editors[opts.elid]) === 'null' || typeOf(Fabrik['inlineedit_' + opts.elid]) === 'null') {
				// Need to load on parent otherwise in table td size gets monged
				Fabrik.loader.start(td.getParent());
				var inline = this.options.showSave ? 1 : 0;

				var editRequest = new Request({
					'evalScripts' : function (script, text) {
						this.javascript = script;
					}.bind(this),
					'evalResponse': false,
					'url'         : '',
					'data'        : {
						'element'     : element,
						'elid'        : opts.elid,
						'elementid'   : Object.values(opts.plugins),
						'rowid'       : rowid,
						'listref'     : this.options.ref,
						'formid'      : this.options.formid,
						'listid'      : this.options.listid,
						'inlinesave'  : inline,
						'inlinecancel': this.options.showCancel,
						'option'      : 'com_fabrik',
						'task'        : 'form.inlineedit',
						'format'      : 'raw'
					},

					'onSuccess': function (r) {
						// Need to load on parent otherwise in table td size gets monged
						Fabrik.loader.stop(td.getParent());

						//don't use evalScripts = true as we reuse the js when tabbing to the next element.
						// so instead set evalScripts to a function to store the js in this.javascript.
						//Previously js was wrapped in delay
						//but now we want to use it with and without the delay

						//delay the script to allow time for the dom to be updated
						(function () {
							window.Browser.exec(this.javascript);

							Fabrik.tips.attach('.fabrikTip');
						}.bind(this)).delay(100);
						td.empty().set('html', r);

						// IE selection wierdness
						this.clearSelection();
						r = r + '<script type="text/javascript">' + this.javascript + '</script>';
						this.editors[opts.elid] = r;
						this.watchControls(td);
						this.setFocus(td);
					}.bind(this),

					'onFailure': function (xhr) {
						this.saving = false;
						this.inedit = false;
						Fabrik.loader.stop(td.getParent());
						window.alert(editRequest.getHeader('Status'));
					}.bind(this),

					'onException': function (headerName, value) {
						this.saving = false;
						this.inedit = false;
						Fabrik.loader.stop(td.getParent());
						window.alert('ajax inline edit exception ' + headerName + ':' + value);
					}.bind(this)

				}).send();
			} else {

				// Re-use old form
				var html = this.editors[opts.elid].stripScripts(function (script) {
					this.javascript = script;
				}.bind(this));
				td.empty().set('html', html);

				// Make a new instance of the element js class which will use the new html
				eval(this.javascript);
				this.clearSelection();
				Fabrik.tips.attach('.fabrikTip');

				// Set some options for use in 'fabrik.list.inlineedit.setData'
				this.editOpts = opts;
				this.editData = data;
				this.editCell = td;
			}
			return true;
		},

		clearSelection: function () {
			if (document.selection) {
				document.selection.empty();
			} else {
				window.getSelection().removeAllRanges();
			}
		},

		getDataFromTable: function (td) {
			var groupedData = this.getList().options.data;
			var element = this.getElementName(td);
			var ref = td.getParent('.fabrik_row').id;
			var v = {};
			this.vv = [];
			// $$$rob $H needed when group by applied
			if (typeOf(groupedData) === 'object') {
				groupedData = $H(groupedData);
			}
			//$H(groupedData).each(function (data) {
			groupedData.each(function (data) {
				//groued by data in forecasting slotenweb app. Where groupby table plugin applied to data.
				if (typeOf(data) === 'array') {
					for (var i = 0; i < data.length; i++) {
						if (data[i].id === ref) {
							this.vv.push(data[i]);
						}
					}
				} else {
					var vv = data.filter(function (row) {
						return row.id === ref;
					});
				}
			}.bind(this));
			var opts = this.options.elements[element];
			if (this.vv.length > 0) {
				$H(opts.plugins).each(function (elid, elementName) {
					v[elid] = this.vv[0].data[elementName + '_raw'];
				}.bind(this));
			}
			return v;
		},

		setTableData: function (row, element, val) {
			var ref = row.id,
				groupedData = this.getList().options.data;
			// $$$rob $H needed when group by applied
			if (typeOf(groupedData) === 'object') {
				groupedData = $H(groupedData);
			}
			groupedData.each(function (data, gkey) {
				data.each(function (tmpRow, dkey) {
					if (tmpRow.id === ref) {
						tmpRow.data[element + '_raw'] = val;
						this.currentRow = tmpRow;
					}
				}.bind(this));
			}.bind(this));
		},

		setFocus: function (td) {

			// See http://www.fabrikar.com/forums/index.php?threads/inline-edit-dialog-window-shows-highlight-in-ie.31732/page-2#post-167922
			if (window.Browser.ie) {
				return;
			}
			var el = td.getElement('.fabrikinput');
			if (typeOf(el) !== 'null') {
				var fn = function () {
					if (typeOf(el) !== 'null') {
						el.focus();
					}
				};
				fn.delay(1000);
			}
		},

		watchControls: function (td) {
			if (typeOf(td.getElement('.inline-save')) !== 'null') {
				td.getElement('.inline-save').removeEvents('click').addEvent('click', function (e) {
					this.save(e, td);
				}.bind(this));
			}
			if (typeOf(td.getElement('.inline-cancel')) !== 'null') {
				td.getElement('.inline-cancel').removeEvents('click').addEvent('click', function (e) {
					this.cancel(e, td);
				}.bind(this));
			}
		},

		save: function (e, td) {
			var saveRequest,
				element = this.getElementName(td),
				opts = this.options.elements[element],
				row = this.editing.getParent('.fabrik_row'),
				rowid = this.getRowId(row),
				currentRow = {},
				eObj = {},
				data = {};

			if (!this.editing) {
				return;
			}
			this.saving = true;
			this.inedit = false;
			if (e) {
				e.stop();
			}

			eObj = Fabrik['inlineedit_' + opts.elid];
			if (typeOf(eObj) === 'null') {
				fconsole('issue saving from inline edit: eObj not defined');
				this.cancel(e);
				return false;
			}

			// Need to load on parent otherwise in table td size gets monged
			Fabrik.loader.start(td.getParent());

			// Set package id to return js string
			data = {
				'option'                 : 'com_fabrik',
				'task'                   : 'form.process',
				'format'                 : 'raw',
				'packageId'              : 1,
				'fabrik_ajax'            : 1,
				'element'                : element,
				'listref'                : this.options.ref,
				'elid'                   : opts.elid,
				'plugin'                 : opts.plugin,
				'rowid'                  : rowid,
				'listid'                 : this.options.listid,
				'formid'                 : this.options.formid,
				'fabrik_ignorevalidation': 1
			};
			data.fabrik_ignorevalidation = 0;
			data.join = {};
			$H(eObj.elements).each(function (el) {

				el.getElement();
				var v = el.getValue();
				var jid = el.options.joinId;
				this.setTableData(row, el.options.element, v);
				if (el.options.isJoin) {
					if (typeOf(data.join[jid]) !== 'object') {
						data.join[jid] = {};
					}
					data.join[jid][el.options.elementName] = v;
				} else {
					data[el.options.element] = v;
				}

			}.bind(this));
			$H(this.currentRow.data).each(function (v, k) {
				if (k.substr(k.length - 4, 4) === '_raw') {
					currentRow[k.substr(0, k.length - 4)] = v;
				}
			});
			// Post all the rows data to form.process
			data = Object.append(currentRow, data);
			data[eObj.token] = 1;

			data.toValidate = this.options.elements[data.element].plugins;
			this.saveRequest = new Request({
				url          : '',
				'data'       : data,
				'evalScripts': true,
				'onSuccess'  : function (r) {
					//td.removeClass(this.options.focusClass);
					td.empty();
					td.empty().set('html', r);

					// Need to load on parent otherwise in table td size gets monged
					Fabrik.loader.stop(td.getParent());
					Fabrik.fireEvent('fabrik.list.updaterows');
					this.stopEditing();
					this.saving = false;
				}.bind(this),

				'onFailure': function (xhr) {
					// Inject error message from header (created by JError::raiseError()...)
					var err = td.getElement('.inlineedit .fabrikMainError');
					if (typeOf(err) === 'null') {
						err = new Element('div.fabrikMainError.fabrikError.alert.alert-error');
						err.inject(td.getElement('form'), 'top');
					}
					this.saving = false;
					Fabrik.loader.stop(td.getParent());
					var headerStatus = xhr.statusText;
					if (typeOf(headerStatus) === 'null') {
						headerStatus = 'uncaught error';
					}
					err.set('html', headerStatus);

				}.bind(this),

				'onException': function (headerName, value) {
					Fabrik.loader.stop(td.getParent());
					this.saving = false;
					window.alert('ajax inline edit exception ' + headerName + ':' + value);
				}.bind(this)

			}).send();
		},

		stopEditing: function (e) {
			var td = this.editing;
			if (td !== false) {
				//td.removeClass(this.options.focusClass);
			}
			this.editing = null;
			this.inedit = false;
		},

		cancel: function (e) {
			if (e) {
				e.stop();
			}
			if (typeOf(this.editing) !== 'element') {
				return;
			}
			var row = this.editing.getParent('.fabrik_row');
			if (row === false) {
				return;
			}
			var rowid = this.getRowId(row);
			var td = this.editing;
			if (td !== false) {
				var element = this.getElementName(td);
				var opts = this.options.elements[element];
				var c = this.defaults[rowid + '.' + opts.elid];
				td.set('html', c);
			}
			this.stopEditing();
		}
	});

	return FbListInlineedit;
});