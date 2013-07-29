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
		this.accordion = new Fx.Accordion([], [], {alwaysHide: true, display: -1});
		for (var i = 0; i < plugins.length; i ++) {
			this.addTop(plugins[i]);
		}
		this.periodical = this.iniAccordian.periodical(500, this);
		
		this.watchPluginSelect();
		this.watchDelete();
		this.watchAdd();
		
		document.id('plugins').addEvent('click:relay(h3.title)', function (e, target) {
			document.id('plugins').getElements('h3.title').each(function (h) {
				if (h !== target) {
					h.removeClass('pane-toggler-down');
				}
			});
			target.toggleClass('pane-toggler-down');
		});
	},
	
	iniAccordian: function () {
		if (this.pluginTotal === this.plugins.length) {
			this.accordion.display(1);
			clearInterval(this.periodical);
		}
	},
	
	canSaveForm: function () 
	{
		if (document.readyState !== 'complete') {
			return false;
		}
		return Fabrik.requestQueue.empty();
	},
	
	/**
	 * @TODO - now only used by element js code - would be nice to remove and use the same code as form/list/validation rule plugins
	 */
	
	_makeSel: function (c, name, pairs, sel) {
		var v, l;
		var opts = [];
		this.sel = sel;
		opts.push(new Element('option', {'value': ''}).appendText(Joomla.JText._('COM_FABRIK_PLEASE_SELECT')));
		if (typeOf(pairs) === 'object') {
			$H(pairs).each(function (group, key) {
				opts.push(new Element('optgroup', {'label': key}));
				group.each(function (pair) {
					opts = this._addSelOpt(opts, pair);
				}.bind(this));
			}.bind(this));
		} else {
			pairs.each(function (pair) {
				opts = this._addSelOpt(opts, pair);
			}.bind(this));
		}
		return new Element('select', {'class': c, 'name': name}).adopt(opts);
	},
	
	/**
	 * @TODO - now only used by element js code - would be nice to remove and use the same code as form/list/validation rule plugins
	 */
	
	_addSelOpt: function (opts, pair) {
		if (typeOf(pair) === 'object') {
			v = pair.value ? pair.value : pair.name; //plugin list should be keyed on plugin name
			l = pair.label ? pair.label : v;
		} else {
			v = l = pair;
		}
		if (v === this.sel) {
			opts.push(new Element('option', {'value': v, 'selected': 'selected'}).set('text', l));
		} else {
			opts.push(new Element('option', {'value': v}).set('text', l));
		}
		return opts;
	},
	
	watchDelete: function () {
		document.id('adminForm').addEvent('click:relay(a.removeButton)', function (event, target) {
			event.preventDefault();
			this.pluginTotal --;
			this.topTotal --;
			this.deletePlugin(event);
		}.bind(this));
	},
	
	watchAdd: function () {
		document.id('addPlugin').addEvent('click', function (e) {
			e.stop();
			this.accordion.display(-1);
			this.addTop();
		}.bind(this));
	},
	
	addTop: function (plugin) {
		var published;
		if (typeOf(plugin) === 'string') {
			published = 1;
			plugin = plugin ? plugin : '';
		} else {
			// Validation plugins 
			published = plugin ? plugin.published : 1;
			plugin = plugin ? plugin.plugin : '';
		}
		var div = new Element('div.actionContainer.panel');
		var toggler = new Element('h3.title.pane-toggler').adopt(new Element('a', {'href': '#'}).adopt(new Element('span').set('text', plugin)));
			
		div.adopt(toggler);
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
	
	/**
	 * Watch the plugin select list
	 */

	watchPluginSelect: function () {
		document.id('adminForm').addEvent('change:relay(select.elementtype)', function (event, target) {
			event.preventDefault();
			var plugin = target.get('value');
			var container = target.getParent('.adminform');
			target.getParent('.actionContainer').getElement('h3 a span').set('text', plugin);
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
				FabrikAdmin.reTip();
			}.bind(this)
		});
		this.pluginTotal ++;
		Fabrik.requestQueue.add(request);
	},

	deletePlugin: function (e) {
		if (e.target.findClassUp('adminform').id.test(/_\d+$/)) {
			var x = e.target.findClassUp('adminform').id.match(/_(\d+)$/)[1].toInt();
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