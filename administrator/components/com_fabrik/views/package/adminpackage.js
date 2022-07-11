/**
 * Controller object for admin interface
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

AdminPackage = new Class({

	initialize: function (opts) {
		this.simpleUI();
	},

	simpleUI: function () {
		var source = document.id('list-pick'),
		target = document.id('blockslist'),
		addBtn = document.id('add-list'),
		removeBtn = document.id('remove-list');

		this._swaplistIni(addBtn, removeBtn, source, target);

		source = document.id('form-pick');
		target = document.id('blocksform');
		addBtn = document.id('add-form');
		removeBtn = document.id('remove-form');
		this._swaplistIni(addBtn, removeBtn, source, target);

	},

	_swaplistIni: function (addBtn, removeBtn, source, target) {
		addBtn.addEvent('click', function (e) {
			e.stop();
			this._swaplist(source, target);
		}.bind(this));

		removeBtn.addEvent('click', function (e) {
			e.stop();
			this._swaplist(target, source);
		}.bind(this));
	},

	_swaplist: function (source, target) {
		var sel = source.getElements('option').filter(function (o) {
			return o.selected;
		});
		sel.each(function (o) {
			o.clone().inject(target);
			o.destroy();
		});
	},
	
	prepareSave: function () {
		
		// Ensure all added options are selected
		document.getElements('#blockslist option').each(function (opt) {
			opt.selected = true;
		});
		
		document.getElements('#blocksform option').each(function (opt) {
			opt.selected = true;
		});
		
		return true;
	}
});