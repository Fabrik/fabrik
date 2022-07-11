/**
 * FilterToggle
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $H:true,unescape:true */

/* can be used to hide filters and show then when the list title is clicked
 * also puts the clear filter and go button underneath the focused filter
 */
FabFilterToggle = new Class({
	initialize: function (ref) {
		var list = document.id('list_' + ref);
		var form = document.id('listform_' + ref);
		Fabrik.addEvent('fabrik.list.update', function (l) {
			if (l.id === ref) {
				list.getElements('.fabrik___heading span.filter').hide();
			}
			return true;
		});

		list.getElements('span.heading').each(function (h) {
			var f = h.getNext();
			if (f) {
				h.addClass('filtertitle');
				h.setStyle('cursor', 'pointer');
				if (i = f.getElement('input')) {
					i.set('placeholder', h.get('text'));
				}
				f.hide();
			}
		});
		list.addEvent('click:relay(span.heading)', function (e) {
			var f = e.target.getNext();
			if (f) {
				f.toggle();
				var i = form.getElement('.fabrikFilterContainer');
				var offsetP = list.getOffsetParent() ? list.getOffsetParent() : document.body;
				var p = f.getPosition(offsetP);
				i.setPosition({'x': p.x - 5, 'y': p.y + f.getSize().y});
				if (f.getStyle('display') === 'none') {
					i.hide();
				} else {
					i.show();
				}
			}
		});

		var c = form.getElement('.clearFilters');
		if (typeOf(c) !== 'null') {
			c.addEvent('click', function () {
				form.getElement('.fabrikFilterContainer').hide();
				form.getElements('.fabrik___heading .filter').hide();
			});
		}
		var s = form.getElement('.fabrik_filter_submit');
		if (typeOf(s) !== 'null') {
			s.addEvent('click', function () {
				form.getElement('.fabrikFilterContainer').hide();
				form.getElements('.fabrik___heading .filter').hide();
			});
		}
	}
});