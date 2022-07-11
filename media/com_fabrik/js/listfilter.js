/**
 * List Filter
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'fab/fabrik', 'fab/advanced-search'], function (jQuery, Fabrik, AdvancedSearch) {
    var FbListFilter = new Class({

        Implements: [Events],

        Binds: [],

        options: {
            'container'     : '',
            'filters'       : [],
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
                e.preventDefault();
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

            advancedSearchButton = this.container.find('.advanced-search-link');
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
                        this.fitToContent(false);
                    }
                };
                Fabrik.getWindow(windowopts);
            });


            jQuery('.fabrik_filter.advancedSelect').on('change', {changeEvent: 'change'}, function (event) {
                this.fireEvent(event.data.changeEvent,
                    new Event.Mock(document.getElementById(this.id), event.data.changeEvent));
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
            if (this.filters.jdate) {
                jQuery.each(this.filters.jdate, function (key, f) {
                    f.onSubmit();
                });
            }
            this.showFilterState();
        },

        onUpdateData: function () {
            if (this.filters.date) {
                jQuery.each(this.filters.date, function (key, f) {
                    f.onUpdateData();
                });
            }
            if (this.filters.jdate) {
                jQuery.each(this.filters.jdate, function (key, f) {
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
            this.container.find('.fabrik_filter').each(function () {
                if (typeof jQuery(this).prop('id') !== 'undefined' && jQuery(this).prop('id').test(/value$/)) {
                    var key = jQuery(this).prop('id').match(/(\S+)value$/)[1];
                    // $$$ rob added check that something is select - possibly causes js
                    // error in ie
                    if (jQuery(this).prop('tagName') === 'SELECT' && this.selectedIndex !== -1) {
                        h[key] = jQuery(this.options[this.selectedIndex]).text();
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
                if (f.hasClass('advancedSelect'))
                {
                    f.trigger('liszt:updated');
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
                var currentTarget = e.event ? e.event.currentTarget : e.currentTarget,
                    key = jQuery(currentTarget).data('filter-clear'),
                    filters = jQuery('*[data-filter-name="' + key + '"]');

                filters.each(function (i, filter) {
                    self.clearAFilter(jQuery(filter));
                });

                self.submitClearForm();
                self.showFilterState();
            });
        },

	    /**
         * Used when filters are in a pop up window
         */
        showFilterState: function () {
            var label = jQuery(Fabrik.jLayouts['modal-state-label']),
                self = this, show = false,
                container = this.container.find('*[data-modal-state-display]'),
                clone, v, v2;
            if (container.length === 0) {
                return;
            }
            container.empty();
            jQuery.each(this.options.filters, function (key, filter) {
                var input = self.container.find('*[data-filter-name="' + filter.name + '"]');
                if (input.prop('tagName') === 'SELECT' && input[0].selectedIndex !== -1) {
                    v = jQuery(input[0].options[input[0].selectedIndex]).text();
                    v2 = input.val();
                } else {
                    v = v2 = input.val();
                }
                if (typeof v !== 'undefined' && v !== null && v !== '' && v2 !== '') {
                    show = true;
                    clone = label.clone();
                    clone.find('*[data-filter-clear]').data('filter-clear', filter.name);
                    clone.find('*[data-modal-state-label]').text(filter.label);
                    clone.find('*[data-modal-state-value]').text(v);
                    container.append(clone);
                }
            });
            if (show) {
                this.container.find('*[data-modal-state-container]').show();
            } else {
                this.container.find('*[data-modal-state-container]').hide();
            }
            this.watchClearOne();
        },

        /**
         * Update CSS after an AJAX filter
         */
        updateFilterCSS: function(data) {
            var c = this.container.find('.clearFilters');
            if (c) {
                if (data.hasFilters) {
                    c.addClass('hasFilters');
                }
                else {
                    c.removeClass('hasFilters');
                }
            }
        }

    });

    return FbListFilter;
});
