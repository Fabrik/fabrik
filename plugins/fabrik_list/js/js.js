/**
 * List JS
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
define(['jquery', 'fab/list-plugin'], function (jQuery, FbListPlugin) {
	var FbListJs = new Class({
		Extends: FbListPlugin,

		options: {
			'statusMsg': ''
		},

		initialize: function (options) {
			this.parent(options);
		},

		buttonAction: function () {
			var statusMsg;
			var chxs = this.list.getForm().getElements('input[name^=ids]').filter(function (i) {
				return i.checked;
			});

			var ids = chxs.map(function (chx) {
				return chx.get('value');
			});

			// Build rows object for ease of access to selected rows' data
			var rows = {};
			chxs.each(function (chx) {
				var id = chx.get('value');
				rows[id] = this.list.getRow(id);
			}.bind(this));

			if (this.options.js_code !== '') {
				if (eval(this.options.js_code) === false) {
					return;
				}
			}
			if (statusMsg === undefined) {
				statusMsg = this.options.statusMsg;
			}
			if (statusMsg !== '') {
				window.alert(statusMsg);
			}
		}
	});

	return FbListJs;
});