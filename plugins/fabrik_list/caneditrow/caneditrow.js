/**
 * List Can Edit Row
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbListCanEditRow = new Class({
	Extends: FbListPlugin,

	initialize: function (options) {
		this.parent(options);
		Fabrik.addEvent('onCanEditRow', function (list, args) {
			this.onCanEditRow(list, args);
		}.bind(this));
	},

	onCanEditRow: function (list, rowid) {
		rowid = rowid[0];
		list.result = this.options.acl[rowid];
	}
});