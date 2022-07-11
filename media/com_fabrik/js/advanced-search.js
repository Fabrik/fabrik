/**
 * Advanced Search
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'fab/fabrik'], function (jQuery, Fabrik) {

    var AdvancedSearch = new Class({

        options: {
            ajax            : false,
            controller      : 'list',
            parentView      : '',
            defaultStatement: '=',
            conditionList   : '',
            elementList     : '',
            elementMap      : {},
            statementList   : ''
        },

        /**
         * Initialize
         * @param {object} options
         */
        initialize: function (options) {
            this.options = jQuery.extend(this.options, options);
            this.form = jQuery('form.advancedSearch_' + this.options.listref);
            var add = this.form.find('.advanced-search-add'),
                clearAll = this.form.find('.advanced-search-clearall'),
                self = this;
            if (add.length > 0) {
                add.off('click');
                add.on('click', function (e) {
                    e.preventDefault();
                    self.addRow();
                });
                clearAll.off('click');
                clearAll.on('click', function (e) {
                    self.resetForm(e);
                });
            }

            this.form.on('click', 'tr', function () {
                self.form.find('tr').removeClass('fabrikRowClick');
                jQuery(this).addClass('fabrikRowClick');
            });
            this.watchDelete();
            this.watchApply();
            this.watchElementList();
            Fabrik.trigger('fabrik.advancedSearch.ready', this);
        },

        /**
         * Watch the apply filters button
         */
        watchApply: function () {
            var self = this;
            this.form.find('.advanced-search-apply').on('click', function (e) {
                Fabrik.fireEvent('fabrik.advancedSearch.submit', self);
                var filterManager = Fabrik['filter_' + self.options.parentView];

                // Format date advanced search fields to db format before posting
                if (filterManager !== undefined) {
                    filterManager.onSubmit();
                }
                /* Ensure that we clear down other advanced searches from the session.
                 * Otherwise, filter on one element and submit works, but changing the filter element and value
                 * will result in 2 filters applied (not one)
                 * @see http://fabrikar.com/forums/index.php?threads/advanced-search-remembers-value-of-last-dropdown-after-element-change.34734/#post-175693
                 */
                var list = self.getList();
                jQuery(document.createElement('input')).attr({
                    'name' : 'resetfilters',
                    'value': 1,
                    'type' : 'hidden'
                }).appendTo(self.form);

                if (!self.options.ajax) {
                    return;
                }

                e.preventDefault();
                list.submit(self.options.controller + '.filter');
            });
        },

        /**
         * Get the Fabrik list js model that relates to this advanced search instance
         * @returns {*}
         */
        getList: function () {
            var list = Fabrik.blocks['list_' + this.options.listref];
            if (list === undefined) {
                list = Fabrik.blocks[this.options.parentView];
            }
            return list;
        },

        /**
         * Create a delegated event to watch the delete row button and trigger the
         * removeRow() method
         */
        watchDelete: function () {
            var self = this;
            this.form.on('click', '.advanced-search-remove-row', function (e) {
                e.preventDefault();
                self.removeRow(jQuery(this).closest('tr'));
            });
        },

        /**
         * Create a delegated event to watch the select list and trigger the
         * updateValueInput() method
         */
        watchElementList: function () {
            var self = this;
            this.form.on('change', 'select.key', function (e) {
                e.preventDefault();
                var row = jQuery(this).closest('tr'),
                    v = jQuery(this).val();
                self.updateValueInput(row, v);
            });
        },

        /**
         * Called when you choose an element from the filter drop-down list
         * should run ajax query that updates value field to correspond with selected
         * element
         * @param {jQuery} row TR
         * @param {string} v   Selected value
         */
        updateValueInput: function (row, v) {
            var url = Fabrik.liveSite + 'index.php?option=com_fabrik&task=list.elementFilter&format=raw',
                elData;
            Fabrik.loader.start(row[0]);
            var update = jQuery(row.find('td')[3]);
            if (v === '') {
                update.html('');
                return;
            }
            elData = this.options.elementMap[v];
            jQuery.ajax({
                'url' : url,
                'data': {
                    'element'   : v,
                    'id'        : this.options.listid,
                    'elid'      : elData.id,
                    'plugin'    : elData.plugin,
                    'counter'   : this.options.counter,
                    'listref'   : this.options.listref,
                    'context'   : this.options.controller,
                    'parentView': this.options.parentView
                }
            }).done(function (r) {
                update.html(r);
                Fabrik.loader.stop(row[0]);
            });
        },

        /**
         * Add a row to the filter table
         */
        addRow: function () {
            this.options.counter++;
            var tr = this.form.find('.advanced-search-list').find('tbody').find('tr').last();
            var clone = tr.clone();
            clone.removeClass('oddRow1').removeClass('oddRow0').addClass('oddRow' + this.options.counter % 2);
            tr.after(clone);
            clone.find('td').first().empty().html(this.options.conditionList);
            var tds = clone.find('td'),
                firstTd = jQuery(tds[1]);
            firstTd.empty().html(this.options.elementList);
            firstTd.append([
                jQuery(document.createElement('input')).attr({
                    'type' : 'hidden',
                    'name' : 'fabrik___filter[list_' + this.options.listref + '][search_type][]',
                    'value': 'advanced'
                }),
                jQuery(document.createElement('input')).attr({
                    'type' : 'hidden',
                    'name' : 'fabrik___filter[list_' + this.options.listref + '][grouped_to_previous][]',
                    'value': '0'
                })
            ]);
            jQuery(tds[2]).empty().html(this.options.statementList);
            jQuery(tds[3]).empty();
            Fabrik.trigger('fabrik.advancedSearch.row.added', this);
        },

        /**
         * Remove a row
         * @param {jQuery} tr
         */
        removeRow: function (tr) {
            if (this.form.find('.advanced-search-remove-row').length > 1) {
                this.options.counter--;

                tr.animate({
                        'height' : 0,
                        'opacity': 0
                    }, 800,
                    function () {
                        tr.remove();
                    }
                );
            }
            Fabrik.trigger('fabrik.advancedSearch.row.removed', this);
        },

        /**
         * Removes all rows except for the first one, whose values are reset to empty
         */
        resetForm: function () {
            var table = this.form.find('.advanced-search-list'),
                self = this;
            if (!table) {
                return;
            }
            table.find('tbody tr').each(function (i) {
                if (i >= 1) {
                    jQuery(this).remove();
                }
                if (i === 0) {
                    jQuery(this).find('.inputbox').each(function () {
                        if (this.id.test(/condition$/)) {
                            this.value = self.options.defaultStatement;
                        }
                        else {
                            this.selectedIndex = 0;
                        }
                    });
                    jQuery(this).find('input').each(function () {
                        jQuery(this).val('');
                    });
                }
            });
            Fabrik.trigger('fabrik.advancedSearch.reset', this);
        },

        /**
         * Delete filter option
         * @deprecated - not used?
         * @param {object} event
         */
        deleteFilterOption: function (event) {
            var self = this;
            jQuery(event.target).off('click', function (e) {
                self.deleteFilterOption(e);
            });
            var tr = jQuery(event.target).parent().parent();
            var table = tr.parent();
            table.removeChild(tr);
            event.preventDefault();
        }

    });

    return AdvancedSearch;
});