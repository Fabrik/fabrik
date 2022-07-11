/**
 * Birthday Element
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'fab/element'], function (jQuery, FbElement) {
    window.FbBirthday = new Class({
        Extends   : FbElement,
        initialize: function (element, options) {
            this.setPlugin('birthday');
            this.default_sepchar = '-';
            this.parent(element, options);
        },

        /**
         * Get focus event
         * @returns {string}
         */
        getFocusEvent: function () {
            return 'click';
        },
        
        getValue: function () {
            var v = [];
            if (!this.options.editable) {
                return this.options.value;
            }
            this.getElement();

            this._getSubElements().each(function (f) {
                v.push(jQuery(f).val());
            });
            return v;
        },

        update: function (val) {
            var sepChar;
            if (typeof(val) === 'string') {
                sepChar = this.options.separator;
                if (val.indexOf(sepChar) === -1) {
                    sepChar = this.default_sepchar;
                }
                val = val.split(sepChar);
            }
            this._getSubElements().each(function (f, x) {
                f.value = val[x];
            });
        }
    });

    return window.FbBirthday;
});
