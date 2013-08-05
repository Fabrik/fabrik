/**
 * List Filter View
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbListFilterView = new Class({
	Extends : FbListPlugin,
	initialize : function (options) {
		this.parent(options);
		this.groupbyMenus = {};
		document.getElement('.filter_view').getElements('ul.floating-tip').each(function (ul) {
			var c = ul.clone();
			c.fade('hide');
			c.inject(document.body);
			c.setStyles({'position': 'absolute'});
			var trigger = ul.getPrevious();
			trigger.store('target', c);
			trigger.addEvent('click', function (e) {
				e.stop();
				var c = trigger.retrieve('target');
				c.setStyle('top', trigger.getTop());
				c.setStyle('left', trigger.getLeft() + trigger.getWidth() / 1.5);
				c.fade('toggle');
			});
			ul.dispose();
		});
		document.getElements('.fabrik_filter_view').addEvent('click:relay(a)', function (e) {
			var href = e.target.get('href');
		});
	}
});