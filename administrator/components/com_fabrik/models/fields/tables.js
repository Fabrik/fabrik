/**
 * Admin Table Editor
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var tablesElement = new Class({

	Implements: [Options, Events],

	options: {
		conn: null
	},

	initialize: function (el, options) {
		this.el = el;
		this.setOptions(options);
		// If loading in a form plugin then the connect is not yet available in the dom
		if (typeOf(document.id(this.options.conn)) === 'null') {
			this.periodical = this.getCnn.periodical(500, this);
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
		clearInterval(this.periodical);
	},

	setUp: function () {
		this.el = document.id(this.el);
		this.cnn = document.id(this.options.conn);
		this.loader = document.id(this.el.id + '_loader');
		this.cnn.addEvent('change', function (e) {
			this.updateMe();
		}.bind(this));
		// See if there is a connection selected
		var v = this.cnn.get('value');
		if (v !== '' && v !== -1) {
			this.updateMe();
		}
	},

	updateMe: function (e) {
		if (e) {
			e.stop();
		}
		if (this.loader) {
			this.loader.show();
		}
		var cid = this.cnn.get('value');
		// $$$ rob 09/09/2011 changed to call admin page, seems better to not cross call between admin and front end for this
		//var url = this.options.livesite + 'index.php?option=com_fabrik&format=raw&view=plugin&task=pluginAjax&g=element&plugin=field&method=ajax_tables&cid=' + cid;
		var url = 'index.php';
		// $$$ hugh - changed this to 'get' method, because some servers barf (Length Required) if
		// we send it a POST with no postbody.
		var myAjax = new Request({
			url: url,
			data: {
				'option': 'com_fabrik',
				'format': 'raw',
				'task': 'plugin.pluginAjax',
				'g': 'element',
				'plugin': 'field',
				'method': 'ajax_tables',
				'cid': cid.toInt()
			},
			onComplete: function (r) {
				var opts = JSON.parse(r);
				if (typeOf(opts) !== 'null') {
					if (opts.err) {
						alert(opts.err);
					} else {
						this.el.empty();
						opts.each(function (opt) {
							//var o = {'value':opt.value};//wrong for calendar
							var o = {'value': opt};
							if (opt === this.options.value) {
								o.selected = 'selected';
							}
							if (this.loader) {
								this.loader.hide();
							}
							new Element('option', o).set('text', opt).inject(this.el);
						}.bind(this));
					}
				}
			}.bind(this),
			onFailure: function (r) {
				this.el.empty();
				if (this.loader) {
					this.loader.hide();
				}
				alert(r.status + ': ' + r.statusText);
			}.bind(this)
		});
		Fabrik.requestQueue.add(myAjax);
	}
});