/**
 * List helper
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var TableRowSelect = new Class({
	initialize:function(triggerEl, formid){
		this.triggerEl = triggerEl;
		this.formid = formid;
		head.ready(function() {
			this.setUp();
		}.bind(this));
	},

	setUp : function() {
		document.getElements('.fabrikList').each(function(tbl) {
			if (!tbl.hasClass('filtertable')) {
				this.listid = tbl.id.replace('list_', '');

				tbl.addEvent('mouseover:relay(.fabrik_row)', function (e, r) {
					if (r.hasClass('oddRow0') || r.hasClass('oddRow1')) {
						r.addClass('fabrikHover');
					}
				});

				tbl.addEvent('mouseout:relay(.fabrik_row)', function (e, r) {
					r.removeClass('fabrikHover');
				});

				tbl.addEvent('click:relay(.fabrik_row)', function (e, r) {
					var d = Array.from(r.id.split('_'));
					var data = {};
					data[this.triggerEl] = d.getLast();
					var json = {
							'errors' : {},
							'data' : data,
							'rowid': d.getLast(),
							formid:this.formid
						};
					Fabrik.fireEvent('fabrik.list.row.selected', json);
				}.bind(this));
			}
		}.bind(this));
	}
});