/**
 * List Filter
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbListFilter = new Class({

    Implements: [Events],

    options: {
        'container'     : '',
        'type'          : 'list',
        'id'            : '',
        'ref'           : '',
        'advancedSearch': {
            'controller': 'list'
        }
    },

    initialize: function (options) {
        var self = this,
            advancedSearchButton;
        this.filters = {};
        this.options = jQuery.extend(this.options, options);
        this.advancedSearch = false;
        this.container = jQuery('#' + this.options.container);
        this.filterContainer = this.container.find('.fabrikFilterContainer');
        this.filtersInHeadings = this.container.find('.listfilter');
        var b = this.container.find('.toggleFilters');
        b.on('click', function (e) {
            e.stopPropagation();
            self.filterContainer.toggle();
            self.filtersInHeadings.toggle();
        });

        if (b.length > 0) {
            this.filterContainer.hide();
            this.filtersInHeadings.toggle();
        }

        if (this.container.length === 0) {
            return;
        }
        this.getList();
        var c = this.container.find('.clearFilters');
        c.off();
        c.on('click', function (e) {
            e.preventDefault();

            // Reset the filter fields that contain previously selected values
            self.container.find('.fabrik_filter').each(function (i, f) {
                self.clearAFilter(jQuery(f));
            });
            self.clearPlugins();
            self.submitClearForm();
        });
        if (advancedSearchButton = this.container.find('.advanced-search-link')) {
            advancedSearchButton.on('click', function (e) {
                e.preventDefault();
                var a = jQuery(e.target), windowopts;
                if (a.prop('tagName') !== 'A') {
                    a = a.closest('a');
                }
                var url = a.prop('href');
                url += '&listref=' + self.options.ref;
                windowopts = {
                    id             : 'advanced-search-win' + self.options.ref,
                    modalId        : 'advanced-filter',
                    title          : Joomla.JText._('COM_FABRIK_ADVANCED_SEARCH'),
                    loadMethod     : 'xhr',
                    evalScripts    : true,
                    contentURL     : url,
                    width          : 710,
                    height         : 340,
                    y              : self.options.popwiny,
                    onContentLoaded: function () {
                        var list = Fabrik.blocks['list_' + self.options.ref];
                        if (list === undefined) {
                            list = Fabrik.blocks[self.options.container];
                            self.options.advancedSearch.parentView = self.options.container;
                        }
                        list.advancedSearch = new AdvancedSearch(self.options.advancedSearch);
                        mywin.fitToContent(false);
                    }
                };
                var mywin = Fabrik.getWindow(windowopts);
            });
        }

        this.filterContainer.find('.advancedSelect').each(function () {
            jQuery('#' + this.id).on('change', {changeEvent: 'change'}, function (event) {
                jQuery(this).fireEvent(event.data.changeEvent,
                    new Event.Mock(document.getElementById(this.id), event.data.changeEvent));
            });
        });
        this.watchClearOne();
    },

    getList: function () {
        this.list = Fabrik.blocks[this.options.type + '_' + this.options.ref];
        if (this.list === undefined) {
            this.list = Fabrik.blocks[this.options.container];
        }
        return this.list;
    },

    addFilter: function (plugin, f) {
        if (this.filters.hasOwnProperty(plugin) === false) {
            this.filters[plugin] = [];
        }
        this.filters[plugin].push(f);
    },

    onSubmit: function () {
        if (this.filters.date) {
            jQuery.each(this.filters.date, function (key, f) {
                f.onSubmit();
            });
        }
    },

    onUpdateData: function () {
        if (this.filters.date) {
            jQuery.each(this.filters.date, function (key, f) {
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
        this.container.find('.fabrik_filter').each(function (f) {
            if (jQuery(this).prop('id').test(/value$/)) {
                var key = $(this).prop('id').match(/(\S+)value$/)[1];
                // $$$ rob added check that something is select - possibly causes js
                // error in ie
                if (jQuery(this).prop('tagName') === 'SELECT' && this.selectedIndex !== -1) {
                    h[key] = jQuery('#' + this.options[this.selectedIndex]).text();
                } else {
                    h[key] = jQuery(this).val();
                }
                h[key + '_raw'] = jQuery(this).val();
            }
        });
        return h;
    },

    /**
     * Ask all filters to update themselves
     */
    update: function () {
        jQuery.each(this.filters, function (plugin, fs) {
            fs.each(function (f) {
                f.update();
            });
        });
    },

    /**
     * Clear a single filter
     * @param {jQuery} f
     */
    clearAFilter: function (f) {
        var sel;
        if (((f.prop('name').contains('[value]') || f.prop('name').contains('fabrik_list_filter_all'))) ||
            f.hasClass('autocomplete-trigger')) {
            if (f.prop('tagName') === 'SELECT') {
                sel = f.prop('multiple') ? -1 : 0;
                f.prop('selectedIndex', sel);
            } else {
                if (f.prop('type') === 'checkbox') {
                    f.prop('checked', false);
                } else {
                    f.val('');
                }
            }
        }
    },

    /**
     * Trigger a "clear filter" for any list plugin
     */
    clearPlugins: function () {
        var plugins = this.getList().plugins;
        if (plugins !== null) {
            plugins.each(function (p) {
                p.clearFilter();
            });
        }
    },

    /**
     * Submit the form as part of clearing filter(s)
     */
    submitClearForm: function () {
        var injectForm = this.container.prop('tagName') === 'FORM' ? this.container :
            this.container.find('form');
        jQuery('<input />').attr({
            'name' : 'resetfilters',
            'value': 1,
            'type' : 'hidden'
        }).appendTo(injectForm);
        if (this.options.type === 'list') {
            this.list.submit('list.clearfilter');
        } else {
            this.container.find('form[name=filter]').submit();
        }
    },

    /**
     * Watch any dom node which have been set up to clear a single filter
     */
    watchClearOne: function () {
        var self = this;
        this.container.find('*[data-filter-clear]').on('click', function (e) {
            e.stopPropagation();
            var key = jQuery(e.event.currentTarget).data('filter-clear'),
                filters = jQuery('*[data-filter-name="' + key + '"]');

            filters.each(function (i, filter) {
                self.clearAFilter(jQuery(filter));
            });

            self.submitClearForm();
        });
    }
});