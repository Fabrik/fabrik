/**
 * List Can Edit Row
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
define(['jquery', 'fab/list-plugin', 'fab/fabrik'], function (jQuery, FbListPlugin, Fabrik) {
	var FbListcaneditrow = new Class({
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
	return FbListcaneditrow;
});
