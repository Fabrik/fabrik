var FbListFilter = new Class({

	Implements: [Options, Events],

	options: {
		'container': '',
		'type': 'list',
		'id': '',
		'advancedSearch': {}
	},

	initialize: function (options) {
		this.filters = $H({});
		this.setOptions(options);
		this.advancedSearch = false;
		this.container = document.id(this.options.container);
		this.filterContainer = this.container.getElement('.fabrikFilterContainer');
		var b = this.container.getElement('.toggleFilters');
		if (typeOf(b) !== 'null') {
			b.addEvent('click', function (e) {
				var dims = b.getPosition();
				e.stop();
				var x = dims.x - this.filterContainer.getWidth();
				var y = dims.y + b.getHeight();
				this.filterContainer.toggle();
			}.bind(this));

			if (typeOf(this.filterContainer) !== 'null') {
				this.filterContainer.toggle();
			}
		}

		if (typeOf(this.container) === 'null') {
			return;
		}
		this.getList();
		var c = this.container.getElement('.clearFilters');
		if (typeOf(c) !== 'null') {
			c.removeEvents();
			c.addEvent('click', function (e) {
				var plugins;
				e.stop();
				this.container.getElements('.fabrik_filter').each(function (f) {
					if (f.get('tag') === 'select') {
						f.selectedIndex = 0;
					} else {
						f.value = '';
					}
				});
				plugins = this.getList().plugins;
				if (typeOf(plugins) !== 'null') {
					plugins.each(function (p) {
						p.clearFilter();
					});
				}
				new Element('input', {
					'name': 'resetfilters',
					'value': 1,
					'type': 'hidden'
				}).inject(this.container);
				if (this.options.type === 'list') {
					this.list.submit('list.clearfilter');
				} else {
					this.container.getElement('form[name=filter]').submit();
				}
			}.bind(this));
		}
		if (advancedSearchButton = this.container.getElement('.advanced-search-link')) {
			advancedSearchButton.addEvent('click', function (e) {
				e.stop();
				var a = e.target;
				//var url = Fabrik.liveSite + "index.php?option=com_fabrik&view=list&tmpl=component&layout=_advancedsearch&listid=" + this.options.id;
				if (a.get('tag') !== 'a') {
					a = a.getParent('a');
				}
				var url = a.href;
				url += '&listref=' + this.options.ref;
				this.windowopts = {
					'id': 'advanced-search-win' + this.options.ref,
					title: Joomla.JText._('COM_FABRIK_ADVANCED_SEARCH'),
					loadMethod: 'xhr',
					evalScripts: true,
					contentURL: url,
					width: 690,
					height: 300,
					y: this.options.popwiny,
					onContentLoaded: function (win) {
						var list = Fabrik.blocks['list_' + this.options.ref];
						list.advancedSearch = new AdvancedSearch(this.options.advancedSearch);
					}.bind(this)
				};
				var mywin = Fabrik.getWindow(this.windowopts);
			}.bind(this));
		}
	},

	getList: function () {
		this.list = Fabrik.blocks[this.options.type + '_' + this.options.ref];
		return this.list;
	},

	addFilter: function (plugin, f) {
		if (this.filters.has(plugin) === false) {
			this.filters.set(plugin, []);
		}
		this.filters.get(plugin).push(f);
	},
	
	onSubmit: function () {
		if (this.filters.date) {
			this.filters.date.each(function (f) {
				f.onSubmit();
			});
		}
	},
	
	onUpdateData: function () {
		if (this.filters.date) {
			this.filters.date.each(function (f) {
				f.onUpdateData();
			});
		}
	},

	// $$$ hugh - added this primarily for CDD element, so it can get an array to
	// emulate submitted form data
	// for use with placeholders in filter queries. Mostly of use if you have
	// daisy chained CDD's.
	getFilterData: function () {
		var h = {};
		this.container.getElements('.fabrik_filter').each(function (f) {
			if (f.id.test(/value$/)) {
				var key = f.id.match(/(\S+)value$/)[1];
				// $$$ rob added check that something is select - possbly causes js
				// error in ie
				if (f.get('tag') === 'select' && f.selectedIndex !== -1) {
					h[key] = document.id(f.options[f.selectedIndex]).get('text');
				} else {
					h[key] = f.get('value');
				}
				h[key + '_raw'] = f.get('value');
			}
		}.bind(this));
		return h;
	},

	update: function () {
		this.filters.each(function (fs, plugin) {
			fs.each(function (f) {
				f.update();
			}.bind(this));
		}.bind(this));
	}
});