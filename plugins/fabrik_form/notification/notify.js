/**
 * List Notification
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var Notify = new Class({

	initialize: function (el, options) {
		this.options = options;
		var target = document.id(el);
		if (target.getStyle('display') === 'none') {
			target = target.getParent();
		}
		target.addEvent('mouseup', function (e) {
			Fabrik.startLoading(this.options.senderBlock);
			var myAjax = new Request({
				url: 'index.php?option=com_fabrik&task=plugin.pluginAjax&plugin=notification&method=toggleNotification',
				data: {
					g: 'form',
					format: 'raw',
					fabrik_notification: 1,
					listid: this.options.listid,
					formid: this.options.fabrik,
					rowid: this.options.rowid,
					notify: document.id(el).checked
				},
				onComplete : function (r) {
						Fabrik.stopLoading(this.options.senderBlock, r);
					}.bind(this)
			}).send();

		}.bind(this));
	}
});