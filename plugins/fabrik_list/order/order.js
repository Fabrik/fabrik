/**
 * List Order
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
define(['jquery', 'fab/list-plugin'], function (jQuery, FbListPlugin) {
	var FbListOrder = new Class({
		Extends: FbListPlugin,

		initialize: function (options) {
			this.parent(options);

			//for iE?
			document.ondragstart = function () {
				return false;
			};

			this.sortables = {};
			this.origorder = {};
			this.neworder = {};

			var isGrouped = Fabrik.blocks['list_' + this.options.ref].options.isGrouped;
			var container = this.getList().list;

			if (!isGrouped) {
				container.setStyle('position', 'relative');
				if (typeOf(container.getElement('tbody')) !== 'null') {
					container = container.getElement('tbody');
				}
				this.makeSortable(container);
			} else {
				var containers = container.getElements('tbody.fabrik_groupdata');
				containers.each(function (container, x) {
					container.setProperty('data-order', x);
					console.log(container, x);
					this.makeSortable(container);
				}.bind(this));

			}

			if (this.options.handle !== false && container.getElements(this.options.handle).length === 0) {
				fconsole('order: handle selected (' + this.options.handle + ') but not found in container');
				return;
			}

			if (options.enabled === false) {
				fconsole('drag n drop reordering not enabled - need to order by ordering element');
			} else {
				if (this.options.handle) {
					container.getElements(this.options.handle).setStyle('cursor', 'move');
				} else {
					container.getChildren().setStyle('cursor', 'move');
				}
			}
		},

		makeSortable: function (container) {
			var sortable = new Sortables(container, {
				clone     : true,
				constrain : false,
				revert    : true,
				opacity   : 0.7,
				transition: 'elastic:out',

				'handle'  : this.options.handle,
				onComplete: function (element, clone) {
					clone ? clone.removeClass('fabrikDragSelected') : element.removeClass('fabrikDragSelected');
					//element.removeClass('fabrikDragSelected');

					var c = element.getParent('tbody');
					var sort = this.sortables[c.getProperty('data-order')];
					this.neworder[c] = this.getOrder(sort);

					Fabrik.loader.start('list_' + this.options.ref, 'sorting', true);
					new Request({
						url         : 'index.php',
						'data'      : {
							'option'   : 'com_fabrik',
							'format'   : 'raw',
							'task'     : 'plugin.pluginAjax',
							'plugin'   : 'order',
							'g'        : 'list',
							'listref'  : this.options.ref,
							'method'   : 'ajaxReorder',
							'order'    : this.neworder[c],
							'origorder': this.origorder[c],
							'dragged'  : this.getRowId(element),
							'listid'   : this.options.listid,
							'orderelid': this.options.orderElementId,
							'direction': this.options.direction
						},
						'onComplete': function (r) {
							Fabrik.loader.stop('list_' + this.options.ref, null, true);
							this.origorder[c] = this.neworder[c];
						}.bind(this)
					}).send();

				}.bind(this),
				onStart   : function (element, clone) {
					var c = element.getParent('tbody');
					var sort = this.sortables[c.getProperty('data-order')];
					this.origorder[c] = this.getOrder(sort);
					clone ? clone.addClass('fabrikDragSelected') : element.addClass('fabrikDragSelected');
				}.bind(this)
			});

			this.sortables[container.getProperty('data-order')] = sortable;
		},

		// Get the id from the fabrik row's html id

		getRowId: function (element) {
			return typeOf(element.getProperty('id')) === 'null' ? null : 
				element.getProperty('id').replace('list_' + this.options.ref + '_row_', '');
		},

		// Get the order of the sortable

		getOrder: function (sortable) {
			return (sortable.serialize(0, function (element) {
				return this.getRowId(element);
			}.bind(this))).clean();
		}
	});

	return FbListOrder;
});