/**
 * Approvals Visualization
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var fbVisApprovals = new Class({
	Implements: [Options],
	options: {},
	initialize: function (el, options) {
		this.setOptions(options);
		this.el = document.id(el);
		document.addEvent('click:relay(a.approve)', function (e) {
			var el = e.target;
			e.stop();
			if (el.get('tag') !== 'a') {
				el = el.findUp('a');
			}
			new Request.HTML({'url': el.href, 
				'onSuccess': function () {
					el.getParent('tr').dispose();
				}
			}).send();

		});
		document.addEvent('click:relay(a.disapprove)', function (e) {
			var el = e.target;
			e.stop();
			if (el.get('tag') !== 'a') {
				el = el.findUp('a');
			}
			new Request.HTML({'url': el.href, 
				'onSuccess': function () {
					el.getParent('tr').dispose();
				}
			}).send();
			
		});

		new FloatingTips('.approvalTip', {
			position: 'right',
			content: function (e) {
				var r = e.getNext();
				r.store('trigger', e);
				return r;
			},
			hideOn: 'mousedown'
		});
	}
});