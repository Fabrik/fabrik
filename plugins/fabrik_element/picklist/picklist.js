var FbPicklist = new Class({
	Extends : FbElement,
	initialize : function (element, options) {
		this.plugin = 'fabrikpicklist';
		this.parent(element, options);
		if (this.options.allowadd === true) {
			this.watchAdd();
		}
		// hovercolor: this.options.bghovercolour,
		var dropcolour = document.id(this.options.element + '_fromlist').getStyle(
				'background-color');
		this.sortable = new Sortables([ '#' + this.element.id + '_fromlist',
				'#' + this.element.id + '_tolist' ], {
			clone : true,
			revert : true,
			opacity : 0.7,
			hovercolor : '#ffddff',
			onComplete : function () {
				// alert(this.serialize());
				this.setData();
			}.bind(this),
			onStart : function () {
				this.drag.addEvent('onEnter', function (element, droppable) {
					if (this.lists.contains(droppable)) {
						var hoverFx = new Fx.Tween(droppable, {
							wait : false,
							duration : 600
						});
						hoverFx.start('background-color', this.options.hovercolor);

						if (this.lists.contains(this.drag.overed)) {
							this.drag.overed.addEvent('mouseleave', function () {
								var hoverFx = new Fx.Tween(droppable, {
									wait : false,
									duration : 600
								});
								hoverFx.start('background-color', dropcolour);
							});
						}
					}
				}.bind(this));
			}
		});
	},

	setData : function () {
		var v = document.id(this.options.element + '_tolist').getElements('li').map(
				function (item, index) {
					return item.id
							.replace(this.options.element + '_value_', '');
				}.bind(this));
		//this.element.value = v.join(this.options.splitter);
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
						// this.showEmptyMsg(document.id(this.options.element +
						// '_tolist'));
						this.setData();
						this.addNewOption(val, label);
					}
				}.bind(this));
	}
});