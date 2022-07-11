/**
 * List JS
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
define(['jquery', 'fab/list-plugin'], function (jQuery, FbListPlugin) {
	var FbListLink = new Class({
		Extends: FbListPlugin,

		options: {
			'link': '',
			'fabrikLink': false,
			'newTab': false,
			'windowTitle': ''
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

			if (this.options.link !== '') {
				// Only one custom window open at the same time.
				jQuery.each(Fabrik.Windows, function (key, win) {
					if (key.test(/^custom\./)) {
						win.close();
					}
				});

				var thisLink = this.options.link;
				jQuery.each(rows[ids[0]], function (key, value){
					if (key === '__pk_val') {
						key = 'rowid';
					}
					var re = new RegExp('\{' + key + '\}', 'g');
					thisLink = thisLink.replace(re, value);
				});
				if (this.options.fabrikLink) {
					var loadMethod = 'xhr';
					thisLink += thisLink.contains('?') ? '&' : '?';
					thisLink += 'tmpl=component&ajax=1';
					thisLink += '&format=partial';
					var winOpts = {
						'id'        : 'custom.' + this.list.id,
						'title'     : this.options.windowTitle,
						'loadMethod': loadMethod,
						'contentURL': thisLink,
						'width'     : this.list.options.popup_width,
						'height'    : this.list.options.popup_height
					};
					if (this.list.options.popup_offset_x !== null) {
						winOpts.offset_x = this.list.options.popup_offset_x;
					}
					if (this.list.options.popup_offset_y !== null) {
						winOpts.offset_y = this.options.popup_offset_y;
					}
					Fabrik.getWindow(winOpts);
				}
				else {
					if (this.options.newTab) {
						window.open(thisLink, '_blank');
					}
					else {
						window.location = thisLink;
					}
				}
			}
		}
	});

	return FbListLink;
});