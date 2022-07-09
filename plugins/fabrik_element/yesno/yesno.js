/**
 * Yes/No Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'element/radiobutton/radiobutton'], function (jQuery, FbRadio) {
    window.FbYesno = new Class({
        Extends   : FbRadio,
        initialize: function (element, options) {
            this.setPlugin('fabrikyesno');
            this.parent(element, options);
        },

        /**
         * Convert event actions on a per element basis.
         * @param {string} action
         * @returns {string}
         */
        checkEventAction: function (action) {
            // Change events wont fire on labels.
            if (action === 'change') {
                action = 'click';
            }

            return action;
        },

        getChangeEvent: function () {
            return this.options.changeEvent;
        },

        setButtonGroupCSS: function (input) {
            var label;
            if (input.id !== '') {
                label = document.getElement('label[for=' + input.id + ']');
            }
            if (typeOf(label) === 'null') {
                label = input.getParent('label.btn');
            }
            var v = input.get('value');

            if (label) {
                var parent = label.getParent('.btn-group');
                // some templates (JoomlArt) remove the brn-group class!
                if (!parent) {
                    parent = label.getParent('.btn-radio');
                }
                if (parent) {
                    parent.getElements('label').removeClass('active').removeClass('btn-success')
                        .removeClass('btn-danger').removeClass('btn-primary');
                }
                if (v === '') {
                    label.addClass('active btn-primary');
                } else if (v.toInt() === 0) {
                    label.addClass('active btn-danger');
                } else {
                    label.addClass('active btn-success');
                }
            }
        }

    });

    return window.FbYesno;
});