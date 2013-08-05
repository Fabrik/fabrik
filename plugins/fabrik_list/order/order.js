/**
 * List Order
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbListOrder = new Class({
	Extends: FbListPlugin,

	initialize: function (options) {
		this.parent(options);

		//for iE?
		document.ondragstart = function () {
			return false;
		};
		var container = this.getList().list;
		container.setStyle('position', 'relative');
		if (typeOf(container.getElement('tbody')) !== 'null') {
			container = container.getElement('tbody');
		}

		if (this.options.handle !== false && container.getElements(this.options.handle).length === 0) {
			fconsole('order: handle selected (' + this.options.handle + ') but not found in container');
			return;
		}

		this.sortable = new Sortables(container, {
			clone: true,
			constrain: false,
			revert: true,
			opacity: 0.7,
			transition: 'elastic:out',

			'handle' : this.options.handle,
			onComplete : function (element, clone) {
				clone ? clone.removeClass('fabrikDragSelected') : element.removeClass('fabrikDragSelected');
				//element.removeClass('fabrikDragSelected');
				this.neworder = this.getOrder();

				Fabrik.loader.start('list_' + this.options.ref, 'sorting', true);
				new Request({
					url: 'index.php',
					'data': {
						'option': 'com_fabrik',
						'format': 'raw',
						'task': 'plugin.pluginAjax',
						'plugin': 'order',
						'g': 'list',
						'listref': this.options.ref,
						'method': 'ajaxReorder',
						'order': this.neworder,
						'origorder': this.origorder,
						'dragged': this.getRowId(element),
						'listid': this.options.listid,
						'orderelid': this.options.orderElementId,
						'direction': this.options.direction
					},
					'onComplete': function (r) {
						Fabrik.loader.stop('list_' + this.options.ref, null, true);
						this.origorder = this.neworder;
					}.bind(this)
				}).send();

			}.bind(this),
			onStart: function (element, clone) {
				this.origorder = this.getOrder();
				clone ? clone.addClass('fabrikDragSelected') : element.addClass('fabrikDragSelected');
			}.bind(this)
		});

		if (options.enabled === false) {
			fconsole('drag n drop reordering not enabled - need to order by ordering element');
			this.sortable.detach();
		} else {
			if (this.options.handle) {
				container.getElements(this.options.handle).setStyle('cursor', 'move');
			} else {
				container.getChildren().setStyle('cursor', 'move');
			}
		}
	},

	// get the id from the fabrik row's html id

	getRowId : function (element) {
		return typeOf(element.getProperty('id')) === 'null' ? null : element.getProperty('id').replace('list_' + this.options.ref + '_row_', '');
	},

	//get the order of the sortable

	getOrder : function () {
		return (this.sortable.serialize(0, function (element) {
			return this.getRowId(element);
		}.bind(this))).clean();
	}
});