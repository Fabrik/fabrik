/**
 * Admin Listfields Dropdown Editor
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var ListFieldsElement = new Class({

	Implements: [Options, Events],

	options: {
		conn: null,
		highlightpk: false
	},

	initialize: function (el, options) {
		this.strEl = el;
		this.el = el;
		this.setOptions(options);
		if (typeOf(document.id(this.options.conn)) === 'null') {
			this.cnnperiodical = this.getCnn.periodical(500, this);
		} else {
			this.setUp();
		}
	},

	/**
	 * Triggered when a fieldset is repeated (e.g. in googlemap viz where you can
	 * select more than one data set)
	 */
	cloned: function (newid, counter)
	{
		this.strEl = newid;
		this.el = document.id(newid);
		this._cloneProp('conn', counter);
		this._cloneProp('table', counter);
		this.setUp();
	},

	/**
	 * Helper method to update option HTML id's on clone()
	 */
	_cloneProp: function (prop, counter) {
		var bits = this.options[prop].split('-');
		bits = bits.splice(0, bits.length - 1);
		bits.push(counter);
		this.options[prop] = bits.join('-');
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
		document.id(this.options.conn).addEvent('change', function () {
			this.updateMe();
		}.bind(this));
		document.id(this.options.table).addEvent('change', function () {
			this.updateMe();
		}.bind(this));

		// See if there is a connection selected
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
			document.id(this.el.id + '_loader').show();
		}
		var cid = document.id(this.options.conn).get('value');
		var tid = document.id(this.options.table).get('value');
		if (!tid) {
			return;
		}
		clearInterval(this.periodical);
		var url = 'index.php?option=com_fabrik&format=raw&task=plugin.pluginAjax&g=element&plugin=field&method=ajax_fields&showall=1&cid=' + cid + '&t=' + tid;
		var myAjax = new Request({
			url: url,
			method: 'get',
			data: {
				'highlightpk': this.options.highlightpk,
				'k': 2
			},
			onComplete: function (r) {

				// Googlemap inside repeat group & modal repeat
				if (typeOf(document.id(this.strEl)) !== null) {
					this.el = document.id(this.strEl);
				}
				var els = document.getElementsByName(this.el.name);

				var opts = eval(r);
				this.el.empty();
				Array.each(els, function (el) {
					document.id(el).empty();
				});
				document.id(this.strEl).empty();
				opts.each(function (opt) {
					var o = {'value': opt.value};
					if (opt.value === this.options.value) {
						o.selected = 'selected';
					}
					Array.each(els, function (el) {
						new Element('option', o).set('text', opt.label).inject(el);
					});
				}.bind(this));
				if (document.id(this.el.id + '_loader')) {
					document.id(this.el.id + '_loader').hide();
				}
			}.bind(this)
		});
		Fabrik.requestQueue.add(myAjax);
	}
});