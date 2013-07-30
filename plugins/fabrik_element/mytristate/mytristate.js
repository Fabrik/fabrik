var FbMytristate = new Class({
	Extends: FbElement,
	initialize: function (element, options) {
		this.parent(element, options);
		this.plugin = 'Mytristate';
	},
	
	changeValue: function ()
	{
		location.reload(true);
	},
	
	select: function () {
		this.element.select();
	},
	
	focus: function () {
		this.element.focus();
	}
});
