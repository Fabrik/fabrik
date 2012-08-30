var ListFieldsElement = new Class({
	
	Implements: [Options, Events],
	
	options: {
		conn: null
	},
	
	initialize: function (el, options) {
		this.el = el;
		this.setOptions(options);
		this.updateMeEvent = this.updateMe.bindWithEvent(this);
		if (typeOf(document.id(this.options.conn)) === 'null') {
			this.cnnperiodical = this.getCnn.periodical(500, this);
		} else {
			this.setUp();
		}
	},
	
	cloned: function ()
	{
	},
	
	getCnn: function () {
		if (typeOf(document.id(this.options.conn)) === 'null') {
			return;
		}
		this.setUp();
		clearInterval(this.cnnperiodical);
	},
	
	setUp: function () {
		this.el = document.id(this.el);
		document.id(this.options.conn).addEvent('change', this.updateMeEvent);
		document.id(this.options.table).addEvent('change', this.updateMeEvent);
			
		//see if there is a connection selected
		var v = document.id(this.options.conn).get('value');
		if (v !== '' && v !== -1) {
			this.periodical = this.updateMe.periodical(500, this);
		}
	},
	
	updateMe: function (e) {
		if (typeOf(e) === 'event') {
			e.stop();
		}
		if (document.id(this.el.id + '_loader')) {
			document.id(this.el.id + '_loader').setStyle('display', 'inline');
		}
		var cid = document.id(this.options.conn).get('value');
		var tid = document.id(this.options.table).get('value');
		if (!tid) {
			return;
		}
		clearInterval(this.periodical);
		//var url = this.options.livesite + 'index.php?option=com_fabrik&format=raw&view=plugin&task=pluginAjax&g=element&plugin=field&method=ajax_fields&showall=true&cid=' + cid + '&t=' + tid;
		var url = 'index.php?option=com_fabrik&format=raw&task=plugin.pluginAjax&g=element&plugin=field&method=ajax_fields&showall=true&cid=' + cid + '&t=' + tid;
		var myAjax = new Request({
			url: url,
			method: 'get', 
			onComplete: function (r) {
				var opts = eval(r);
				this.el.empty();
				opts.each(function (opt) {
					var o = {'value': opt.value};
					if (opt.value === this.options.value) {
						o.selected = 'selected';
					}
					
					new Element('option', o).appendText(opt.label).inject(this.el);
				}.bind(this));
				if (document.id(this.el.id + '_loader')) {
					document.id(this.el.id + '_loader').setStyle('display', 'none');
				}
			}.bind(this)
		}).send();
	}
});