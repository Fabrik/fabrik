/**
 * Admin Plugin Manager
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var PluginManager = new Class({

	pluginTotal: 0,

	topTotal: -1,

	initialize: function (plugins, id, type) {
		if (typeOf(plugins) === 'string') {
			plugins = [plugins];
		}
		this.id = id;
		this.plugins = plugins;
		this.type = type;
		window.addEvent('domready', function () {
			this.accordion = new Fx.Accordion([], [], {alwaysHide: true, display: -1});
			for (var i = 0; i < plugins.length; i ++) {
				this.addTop(plugins[i]);
			}
			this.periodical = this.iniAccordian.periodical(500, this);

			this.watchPluginSelect();
			this.watchDelete();
			this.watchAdd();

			var pluginArea = document.id('plugins');
			if (typeOf(pluginArea) !== 'null') {
				pluginArea.addEvent('click:relay(h3.title)', function (e, target) {
					document.id('plugins').getElements('h3.title').each(function (h) {
						if (h !== target) {
							h.removeClass('pane-toggler-down');
						}
					});
					target.toggleClass('pane-toggler-down');
				});
			}
		}.bind(this));

	},

	iniAccordian: function () {
		if (this.pluginTotal === this.plugins.length) {
			this.accordion.display(1);
			clearInterval(this.periodical);
		}
	},

	/**
	 * Has the form finished loading and are there any outstanding ajax requests
	 *
	 * @return  bool
	 */
	canSaveForm: function ()
	{
		if (document.readyState !== 'complete') {
			return false;
		}
		return Fabrik.requestQueue.empty();
	},

	watchDelete: function () {
		document.id('adminForm').addEvent('click:relay(a.removeButton, a[data-button=removeButton])', function (event, target) {
			event.preventDefault();
			this.pluginTotal --;
			this.topTotal --;
			this.deletePlugin(event);
		}.bind(this));
	},

	watchAdd: function () {
		var addPlugin = document.id('addPlugin');
		if (typeOf(addPlugin) !== 'null') {
			addPlugin.addEvent('click', function (e) {
				e.stop();
				this.accordion.display(-1);
				this.addTop();
			}.bind(this));
		}
	},

	addTop: function (plugin) {
		var published;
		if (typeOf(plugin) === 'string') {
			published = 1;
			plugin = plugin ? plugin : '';
			show_icon = false;
		} else {
			// Validation plugins
			published = plugin ? plugin.published : 1;
			show_icon = plugin ? plugin.show_icon : 1;
			plugin = plugin ? plugin.plugin : '';
		}
		var div = new Element('div.actionContainer.panel.accordion-group');
		var a = new Element('a.accordion-toggle', {'href': '#'}).adopt(new Element('span.pluginTitle').set('text', plugin));
		var toggler = new Element('div.title.pane-toggler.accordion-heading').adopt(
		new Element('strong').adopt(a));

		div.adopt(toggler);
		div.adopt(new Element('div.accordion-body'));
		div.inject(document.id('plugins'));
		var append = document.id('plugins').getElements('.actionContainer').getLast();
		var tt_temp = this.topTotal; //added temp variable

		// Ajax request to load the first part of the plugin form (do[plugin] in, on)
		var request = new Request.HTML({
			url: 'index.php',
			data: {
				'option': 'com_fabrik',
				'view': 'plugin',
				'task': 'top',
				'format': 'raw',
				'type': this.type,
				'plugin': plugin,
				'plugin_published': published,
				'show_icon': show_icon,
				'c': this.topTotal,
				'id': this.id
			},
			append: append,
			onSuccess: function (res) {

				if (plugin !== '') {
					// Sent temp variable as c to addPlugin, so they are aligned properly
					this.addPlugin(plugin, tt_temp + 1);
				}
				this.accordion.addSection(toggler, div.getElement('.pane-slider'));
				this.updateBootStrap();
				FabrikAdmin.reTip();
			}.bind(this),
			onFailure: function (xhr) {
				console.log('fail', xhr);
			},
			onException: function (headerName, value) {
				console.log('excetiprion', headerName, value);
			}
		});
		this.topTotal ++;
		Fabrik.requestQueue.add(request);
	},

	// Bootstrap specific

	updateBootStrap: function () {
		document.getElements('.radio.btn-group label').addClass('btn');

		document.getElements(".btn-group input[checked=checked]").each(function (el) {
			if (el.get('value') === '') {
				document.getElement("label[for=" + el.get('id') + "]").addClass('active btn-primary');
			} else if (el.get('value') === '0') {
				document.getElement("label[for=" +  el.get('id') + "]").addClass('active btn-danger');
			} else {
				document.getElement("label[for=" +  el.get('id') + "]").addClass('active btn-success');
			}
			if (typeof(jQuery) !== 'undefined') {
				jQuery('*[rel=tooltip]').tooltip();
			}

		});

		document.getElements('.hasTip').each(function (el) {
			var title = el.get('title');
			if (title) {
				var parts = title.split('::', 2);
				el.store('tip:title', parts[0]);
				el.store('tip:text', parts[1]);
			}
		});
		var JTooltips = new Tips($$('.hasTip'), {maxTitleChars: 50, fixed: false});
	},

	/**
	 * Watch the plugin select list
	 */

	watchPluginSelect: function () {
		document.id('adminForm').addEvent('change:relay(select.elementtype)', function (event, target) {
			event.preventDefault();
			var plugin = target.get('value');
			var container = target.getParent('.pluginContainer');
			target.getParent('.actionContainer').getElement('span.pluginTitle').set('text', plugin);
			var c = container.id.replace('formAction_', '').toInt();
			this.addPlugin(plugin, c);
		}.bind(this));
	},

	addPlugin: function (plugin, c) {
		c = typeOf(c) === 'number' ? c : this.pluginTotal;
		if (plugin === '') {
			document.id('plugins').getElements('.actionContainer')[c].getElement('.pluginOpts').empty();
			return;
		}

		// Ajax request to load the plugin contennt
		var request = new Request.HTML({
			url: 'index.php',
			data: {
				'option': 'com_fabrik',
				'view': 'plugin',
				'format': 'raw',
				'type': this.type,
				'plugin': plugin,
				'c': c,
				'id': this.id
			},
			update: document.id('plugins').getElements('.actionContainer')[c].getElement('.pluginOpts'),
			onComplete: function () {
				this.updateBootStrap();
				FabrikAdmin.reTip();
			}.bind(this)
		});
		this.pluginTotal ++;
		Fabrik.requestQueue.add(request);
	},

	deletePlugin: function (e) {
		var c = e.target.getParent('.pluginContainer');
		if (typeOf(c) === 'null') {
			return;
		}
		if (c.id.test(/_\d+$/)) {
			// var x = e.target.findClassUp('adminform').id.match(/_(\d+)$/)[1].toInt();
			var x = e.target.getParent('fieldset').id.match(/_(\d+)$/)[1].toInt();
			document.id('plugins').getElements('input, select, textarea').each(function (i) {
				var s = i.name.match(/\[[0-9]+\]/);
				if (s) {
					var c = s[0].replace('[', '').replace(']', '').toInt();
					if (c > x) {
						c = c - 1;
						i.name = i.name.replace(/\[[0-9]+\]/, '[' + c + ']');
					}
				}
			});
			document.id('plugins').getElements('.adminform').each(function (i) {
				if (i.id.match(/formAction_\d+$/)) {
					var c = i.id.match(/formAction_(\d+)$/)[1].toInt();
					if (c > x) {
						c = c - 1;
						i.id = i.id.replace(/(formAction_)(\d+)$/, '$1' + c);
					}
				}
			});
		}
		e.stop();
		document.id(e.target).getParent('.actionContainer').dispose();
		this.counter --;
	}

});

fabrikAdminPlugin = new Class({

	Implements: [Options],
	options: {},
	initialize: function (name, label, options)
	{
		this.name = name;
		this.label = label;
		this.setOptions(options);
	},

	cloned: function () {

	}

});