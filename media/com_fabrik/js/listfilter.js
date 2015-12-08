/**
 * List Filter
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbListFilter = new Class({

	Implements: [Options, Events],

	options: {
		'container': '',
		'type': 'list',
		'id': '',
		'advancedSearch': {
			'controller': 'list'
		}
	},

	initialize: function (options) {
		this.filters = $H({});
		this.setOptions(options);
		this.advancedSearch = false;
		this.container = document.id(this.options.container);
		this.filterContainer = this.container.getElements('.fabrikFilterContainer');
		this.filtersInHeadings = this.container.getElements('.listfilter');
		var b = this.container.getElement('.toggleFilters');
		if (typeOf(b) !== 'null') {
			b.addEvent('click', function (e) {
				var dims = b.getPosition();
				e.stop();
				var x = dims.x - this.filterContainer.getWidth();
				var y = dims.y + b.getHeight();
				this.filterContainer.toggle();
				this.filtersInHeadings.toggle();
			}.bind(this));

			if (typeOf(this.filterContainer) !== 'null') {
				this.filterContainer.hide();
				this.filtersInHeadings.toggle();
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

				// Reset the filter fields that contain previously selected values
				this.container.getElements('.fabrik_filter').each(function (f) {
					if (f.name.contains('[value]') || f.name.contains('fabrik_list_filter_all') || f.hasClass('autocomplete-trigger')) { 
						if (f.get('tag') === 'select') {
							f.selectedIndex = f.get('multiple') ? -1 : 0;
						} else {
							if (f.get('type') === 'checkbox') {
								f.checked = false;
							} else {
								f.value = '';
							}
						}
					}
				});
				plugins = this.getList().plugins;
				if (typeOf(plugins) !== 'null') {
					plugins.each(function (p) {
						p.clearFilter();
					});
				}
				var injectForm = this.container.get('tag') === 'form' ? this.container : this.container.getElement('form');
				new Element('input', {
					'name': 'resetfilters',
					'value': 1,
					'type': 'hidden'
				}).inject(injectForm);
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
					width: 710,
					height: 340,
					y: this.options.popwiny,
					onContentLoaded: function (win) {
						var list = Fabrik.blocks['list_' + this.options.ref];
						if (typeOf(list) === 'null') {
							list = Fabrik.blocks[this.options.container];
							this.options.advancedSearch.parentView = this.options.container;
						}
						list.advancedSearch = new AdvancedSearch(this.options.advancedSearch);
						mywin.fitToContent(false);
					}.bind(this)
				};
				var mywin = Fabrik.getWindow(this.windowopts);
			}.bind(this));
		}
		
		if (this.filterContainer[0]) {
			this.filterContainer[0].getElements('.advancedSelect').each(function (f) {
				jQuery('#' + f.id).on('change', {changeEvent: 'change'}, function (event) {
					document.id(this.id).fireEvent(event.data.changeEvent, new Event.Mock(document.id(this.id), event.data.changeEvent));
				});
			});
		}
	},

	getList: function () {
		this.list = Fabrik.blocks[this.options.type + '_' + this.options.ref];
		if (typeOf(this.list) === 'null') {
			this.list = Fabrik.blocks[this.options.container];
		}
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
				// $$$ rob added check that something is select - possibly causes js
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