/**
 * List Toggle
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery'], function (jQuery) {

    var FbListToggle = new Class({

        initialize: function (form) {

            // Stop dropdown closing on click
            jQuery('#' + form.id + ' .togglecols .dropdown-menu a, #' + form.id
                + ' .togglecols .dropdown-menu li').click(function (e) {
                e.stopPropagation();
            });

            // Set up toggle events for elements
            form.addEvent('mouseup:relay(a[data-toggle-col])', function (e, btn) {
                var state = jQuery(btn).data('toggle-state');
                var col = jQuery(btn).data('toggle-col');
                this.toggleColumn(col, state, btn);
            }.bind(this));

            // Toggle events for groups (toggles all elements in group)
            var groups = form.getElements('a[data-toggle-group]');
            form.addEvent('mouseup:relay(a[data-toggle-group])', function (e, group) {
                var state = jQuery(group).data('toggle-state'), muted,
                    groupName = jQuery(group).data('toggle-group'),
                    links = document.getElements('a[data-toggle-parent-group=' + groupName + ']');

                links.each(function (btn) {
                    var col = jQuery(btn).data('toggle-col');
                    this.toggleColumn(col, state, btn);
                }.bind(this));

                state = state === 'open' ? 'close' : 'open';
                muted = state === 'open' ? '' : ' muted';
                jQuery(group).find('*[data-isicon]')
                    .removeClass()
                    .addClass('icon-eye-' + state + muted);
                jQuery(group).data('toggle-state', state);

            }.bind(this));
        },

        /**
         * Toggle column
         *
         * @param col   Element name
         * @param state Open/closed
         * @param btn   Button/link which initiated the toggle
         */
        toggleColumn: function (col, state, btn) {
            var muted;
            state = state === 'open' ? 'close' : 'open';

            if (state === 'open') {
                jQuery('.fabrik___heading .' + col).show();
                jQuery('.fabrikFilterContainer .' + col).show();
                jQuery('.fabrik_row  .' + col).show();
                jQuery('.fabrik_calculations  .' + col).show();
                muted = '';
            } else {
                jQuery('.fabrik___heading .' + col).hide();
                jQuery('.fabrikFilterContainer .' + col).hide();
                jQuery('.fabrik_row  .' + col).hide();
                jQuery('.fabrik_calculations  .' + col).hide();
                muted = ' muted';
            }

            jQuery(btn).find('*[data-isicon]')
                .removeClass()
                .addClass('icon-eye-' + state + muted);
            jQuery(btn).data('toggle-state', state);
        }
    });

    return FbListToggle;
});