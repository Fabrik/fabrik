/**
 * PickList Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbPicklist = new Class({
	Extends : FbElement,
	initialize : function (element, options) {
		this.plugin = 'fabrikpicklist';
		this.parent(element, options);
		if (this.options.allowadd === true) {
			this.watchAdd();
		}
		var from = document.id(this.options.element + '_fromlist');
		var to =  document.id(this.options.element + '_tolist');

		var dropcolour = from.getStyle('background-color');
		var that = this;
		this.sortable = new Sortables([ from, to ], {
			clone : true,
			revert : true,
			opacity : 0.7,
			hovercolor : '#ffddff',
			onComplete : function () {
				this.setData();
				that.fadeOut(from, dropcolour);
				that.fadeOut(to, dropcolour);
			}.bind(this),
			onSort: function (element, clone) {
				this.showNotices(element, clone);

			}.bind(this),


			onStart : function (element, clone) {
				this.drag.addEvent('onEnter', function (element, droppable) {
					if (this.lists.contains(droppable)) {
						that.fadeOut(droppable, this.options.hovercolor);
						if (this.lists.contains(this.drag.overed)) {
							this.drag.overed.addEvent('mouseleave', function () {
								that.fadeOut(from, dropcolour);
								that.fadeOut(to, dropcolour);
							}.bind(this));
						}
					}
				}.bind(this));
			}
		});

		var notices = [from.getElement('li.emptyplicklist'), to.getElement('li.emptyplicklist')];
		this.sortable.removeItems(notices);
		this.showNotices();
	},

	fadeOut: function (droppable, colour) {
		var hoverFx = new Fx.Tween(droppable, {
			wait : false,
			duration : 600
		});
		hoverFx.start('background-color', colour);
	},

	showNotices: function (element, clone) {
		if (element) {
			element = element.getParent('ul');
		}
		var limit, to, i;
		var lists = [this.options.element + '_tolist', this.options.element + '_fromlist'];
		for (i = 0; i < lists.length; i++) {
			to = document.id(lists[i]);
			limit = (to === element || typeOf(element) === 'null') ? 1 : 2;
			var notice = to.getElement('li.emptyplicklist');
			var lis = to.getElements('li');
			lis.length > limit ? notice.hide() : notice.show();
		}
	},

	setData: function () {

		var to = document.id(this.options.element + '_tolist');
		var lis = to.getElements('li');

		var v = lis.map(
				function (item, index) {
					return item.id
							.replace(this.options.element + '_value_', '');
				}.bind(this));
		this.element.value = JSON.encode(v);
	},

	watchAdd : function () {
		var id = this.element.id;
		if (!document.id(this.element.id + '_dd_add_entry')) {
			return;
		}
		document.id(this.element.id + '_dd_add_entry').addEvent(
				'click',
				function (e) {
					var val;
					var label = document.id(id + '_ddLabel').value;
					if (document.id(id + '_ddVal')) {
						val = document.id(id + '_ddVal').value;
					} else {
						val = label;
					}
					if (val === '' || label === '') {
						alert(Joomla.JText._('PLG_ELEMENT_PICKLIST_ENTER_VALUE_LABEL'));
					} else {

						var li = new Element('li', {
							'class' : 'picklist',
							'id' : this.element.id + '_value_' + val
						}).set('text', label);

						document.id(this.element.id + '_tolist').adopt(li);
						this.sortable.addItems(li);

						e.stop();
						if (document.id(id + '_ddVal')) {
							document.id(id + '_ddVal').value = '';
						}
						document.id(id + '_ddLabel').value = '';
						this.setData();
						this.addNewOption(val, label);
					}
				}.bind(this));
	}
});