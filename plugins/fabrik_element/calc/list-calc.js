/**
 * Calc Element - List
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbCalcList = new Class({

	options: {
	},

	Implements: [Events, Options],

	initialize: function (id, options) {
		options.element = id;
		this.setOptions(options);
		this.col = $$('.' + id);
		this.list = Fabrik.blocks[this.options.listRef];
		Fabrik.addEvent('fabrik.list.updaterows', function () {
			this.update();
		}.bind(this));
	},

	update: function () {
		var data = {
				'option': 'com_fabrik',
				'format': 'raw',
				'task': 'plugin.pluginAjax',
				'plugin': 'calc',
				'g': 'element',
				'listid': this.options.listid,
				'formid': this.options.formid,
				'method': 'ajax_listUpdate',
				'element_id': this.options.elid,
				'rows' : this.list.getRowIds(),
				'elementname' : this.options.elid
			};

		new Request.JSON({
			url: '',
			data: data,
			onSuccess: function (json) {
				$H(json).each(function (html, id) {
					var cell = this.list.list.getElement('#' + id + ' .' + this.options.element);
					if (typeOf(cell) !== 'null' && html !== false) {
						cell.set('html', html);
					}
				}.bind(this));
			}.bind(this)
		}).send();
	}

});