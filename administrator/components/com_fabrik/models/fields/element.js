var elementElement = new Class({

	Implements: [Options, Events],
	
	options : {
		'plugin': 'chart',
		'excludejoined': 0,
		'value': ''
	},
	
	initialize : function (el, options) {
		this.el = el;
		this.setOptions(options);
		// this.updateMeEvent = this.updateMe.bindAsEventListener(this);
		// if loading in a form plugin then the connect is not yet avaiable in the
		// dom
		if (!this.ready()) {
			this.cnnperiodical = this.getCnn.periodical(500, this);
		} else {
			this.setUp();
		}
	},

	ready : function () {
		if (typeOf(document.id(this.options.conn)) === 'null') {
			return false;
		}
		if (typeOf(FabrikAdmin.model.fields.fabriktable) === 'undefined') {
			return false;
		}
		if (Object.getLength(FabrikAdmin.model.fields.fabriktable) === 0) {
			return false;
		}
		if (Object.keys(FabrikAdmin.model.fields.fabriktable).indexOf(this.options.table) === -1) {
			return false;
		}
		return true;
	},

	getCnn : function () {
		if (!this.ready()) {
			return;
		}
		this.setUp();
		clearInterval(this.cnnperiodical);
	},

	setUp : function () {
		var s = this.el;
		this.el = document.id(this.el);
		if (typeOf(this.el) === 'null') {
			fconsole('element didnt find me, ', s);
		}
		FabrikAdmin.model.fields.fabriktable[this.options.table].registerElement(this);
	},

	getOpts : function () {
		return $H({
			'calcs': this.options.include_calculations,
			'showintable': this.options.showintable,
			'published': this.options.published,
			'excludejoined': this.options.excludejoined
		});
	},

	// only called from repeat viz admin interface i think
	cloned : function (newid, counter) {
		this.el = newid;
		var t = this.options.table.split('-');
		t.pop();
		this.options.table = t.join('-') + '-' + counter;
		this.setUp();
	}
});