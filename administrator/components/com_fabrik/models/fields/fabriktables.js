/**
 * Admin Fabrik Tables Editor
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $H:true,unescape:true,Asset:true */

FabrikAdmin.model.fields.fabriktable = FabrikAdmin.model.fields.fabriktable || {};
FabrikAdmin.model.cache              = FabrikAdmin.model.cache              || {};
FabrikAdmin.model.cache.fabriktable  = FabrikAdmin.model.cache.fabriktable  || {};

var fabriktablesElement = new Class({

	Implements: [Options, Events],

	options: {
		conn: null,
		connInRepeat: true,
	},

	initialize : function (el, options) {
		this.el = document.id(el);
		this.setOptions(options);
		this.elements = [];
		this.elementLists = $H({}); // keyed on specific element options
		this.waitingElements = $H({}); // keyed on specific element options
		// if loading in a form plugin then the connect is not yet available in the dom
		if (typeOf(document.id(this.options.conn)) === 'null') {
			this.periodical = this.getCnn.periodical(500, this);
		} else {
			this.setUp();
		}
	},

	getCnn : function () {
		if (typeOf(document.id(this.options.conn)) === 'null') {
			return;
		}
		this.setUp();
		clearInterval(this.periodical);
	},

	registerElement : function (el) {
		this.elements.push(el);
		this.updateElements();
	},

	setUp : function () {
		this.cnn = document.id(this.options.conn);
		if (this.cnn === 'null') {
			return;
		}
		this.loader = document.id(this.el.id + '_loader');

		if (this.cnn.hasClass('chzn-done')) {
			jQuery('#' + this.cnn.id).on('change', function (event) {
				document.id(this.cnn).fireEvent('change', new Event.Mock(document.id(this.cnn), 'change'));
			}.bind(this));
		}

		this.cnn.addEvent('change', function (e) {
			this.updateMe(e);
		}.bind(this));

		if (this.el.hasClass('chzn-done')) {
			jQuery('#' + this.el.id).on('change', function (event) {
				document.id(self.el.id).fireEvent('change', new Event.Mock(document.id(self.el.id), 'change'));
			});
		}

		this.el.addEvent('change', function (e) {
			this.updateElements(e);
		}.bind(this));

		// see if there is a connection selected
		var v = this.cnn.get('value');
		if (v !== '' && v !== -1) {
			this.updateMe();
		}
	},

	updateMe : function (e) {
		if (e) {
			e.stop();
		}
		var cid = this.cnn.get('value');
		// keep repeating the periodical until the cnn drop down is completed
		if (!cid) {
			return;
		}

		if (FabrikAdmin.model.cache.fabriktable[cid]) {
			this.updateMeSelect(FabrikAdmin.model.cache.fabriktable[cid]);
			return;
		}

		if (this.loader) {
			this.loader.show();
		}

		var myAjax = new Request({
			url : 'index.php',
			data: {
				'option': 'com_fabrik',
				'format': 'raw',
				'task': 'plugin.pluginAjax',
				'g': 'element',
				'plugin': 'field',
				'method': 'ajax_tables',
				'showf': '1',
				'cid': cid.toInt()
			},
			onSuccess : function (r) {
				var opts = JSON.parse(r);
				if (typeOf(opts) !== 'null') {
					if (opts.err) {
						alert(opts.err);
					} else {
						FabrikAdmin.model.cache.fabriktable[cid] = opts;
						this.updateMeSelect(opts);
						if (this.loader) {
							this.loader.hide();
						}
					}
				}
			}.bind(this),
			'onFailure': function (xhr) {
				console.log('fabriktables request failure', xhr.getResponseHeader('Status'));
			}.bind(this),
			'onException': function (headerName, value) {
				console.log('fabriktables request exception', headerName, value);
			}.bind(this)
		});

		/*
		 * Use Fabrik.requestQueue rather than myAjax.send()
		 * as it is polled on form save to ensure that elements are not in a loading state
		 */
		Fabrik.requestQueue.add(myAjax);
	},

	updateMeSelect : function(opts) {
		this.el.empty();
		opts.each(function (opt) {
			var o = {
				'value' : opt.id
			};
			if (opt.id === this.options.value) {
				o.selected = 'selected';
			}
			new Element('option', o).appendText(opt.label).inject(this.el);
		}.bind(this));
		if (this.el.hasClass('chzn-done')) {
			jQuery("#" + this.el.id).trigger("liszt:updated");
		}
		this.updateElements();
	},

	updateElements : function () {
		this.elements.each(function (element) {
			var opts = element.getOpts();
			var table = this.el.get('value');
			if (table === '') {
				// $$$ rob don't empty as this messes up parameter saving in paypal
				// plugin
				// element.el.empty();
				return;
			}
			if (this.loader) {
				this.loader.show();
			}
			var key = opts.getValues().toString() + ',' + table;
			if (!this.waitingElements.has(key)) {
				this.waitingElements[key] = $H({});
			}
			if (this.elementLists[key] !== undefined) {
				if (this.elementLists[key] === '') {
					// delay update
					this.waitingElements[key][element.el.id] = element;
				} else {
					// keyed on specific element options
					this.updateElementOptions(this.elementLists[key], element);
				}
			} else {

				var cid = this.cnn.get('value');
				this.elementLists.set(key, '');
				//var url = this.options.livesite + 'index.php?option=com_fabrik&format=raw&view=plugin&task=pluginAjax&g=visualization&plugin=chart&method=ajax_fields&k=2&t=' + table + '&cid=' + cid;

				var ajaxopts = {
					'option': 'com_fabrik',
					'format': 'raw',
					'task': 'plugin.pluginAjax',
					'g': 'element',
					'plugin': 'field',
					'method': 'ajax_fields',
					'cid': cid.toInt(),
					'showf': '1',
					'k': '2',
					't': table
				};
				opts.each(function (v, k) {
					ajaxopts[k] = v;
				});
				var myAjax = new Request({
					'url' : 'index.php',
					'data' : ajaxopts,
					onComplete : function (r) {
						this.elementLists.set(key, r);
						this.updateElementOptions(r, element);
						this.waitingElements.get(key).each(function (el, i) {
							this.updateElementOptions(r, el);
							this.waitingElements[key].erase(i);
						}.bind(this));
					}.bind(this),

					onFailure: function (r) {
						this.waitingElements.get(key).each(function (el, i) {
							this.updateElementOptions('[]', el);
							this.waitingElements[key].erase(i);
						}.bind(this));
						if (this.loader) {
							this.loader.hide();
						}
						alert(r.status + ': ' + r.statusText);
					}.bind(this)
				}).send();
			}
		}.bind(this));
	},

	updateElementOptions : function (r, element) {
		var target, dotValue;
		if (r === '') {
			return;
		}

		var table = document.id(this.el).get('value');
		var key = element.getOpts().getValues().toString() + ',' + table;
		var opts = eval(r);

		if (element.el.get('tag') === 'textarea') {
			target = element.el.getParent().getElement('select');
		} else {
			target = element.el;
		}
		target.empty();
		var o = {
			'value' : ''
		};
		if (element.options.value === '') {
			o.selected = 'selected';
		}
		new Element('option', o).appendText('-').inject(target);
		dotValue = element.options.value.replace('.', '___');
		opts.each(function (opt) {
			var v = opt.value.replace('[]', '');
			var o = {
				'value': v
			};

			if (v === element.options.value || v === dotValue) {
				o.selected = 'selected';
			}

			new Element('option', o).set('text', opt.label).inject(target);
		}.bind(this));
		if (this.loader) {
			this.loader.hide();
		}

	},

	// Called from back end repeatgroup and joomla subform-repeated
	cloned : function (newid, counter) {
		// need to update watch connection id?
		if (this.options.connInRepeat === true) {
			var fIdx = "-" + counter.toString();
			var jIdx = counter.toString() + '__';
			if (newid.indexOf(jIdx) !== -1) {
				// Joomla subForm
				this.options.conn = this.options.conn.replace('X__', jIdx);
			} else if (newid.slice(-fIdx.length) === fIdx) {
				// Fabrik repeatgroup
				var cid = this.options.conn.split('-');
				cid.pop();
				this.options.conn = cid.join('-') + fIdx;
			}
		}

		// Change this elements image id so that indication of ajax works.
		var img = document.id(newid).getParent().getElement('img');
		img.id = newid + '_loader';

		this.el = document.id(newid);
		this.elements = [];
		this.elementLists = $H({});
		this.waitingElements = $H({});
		this.setUp();
		FabrikAdmin.model.fields.fabriktable[this.el.id] = this;
	},

});
