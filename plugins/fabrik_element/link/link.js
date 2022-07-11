/**
 * Link Element
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'fab/elementlist'], function (jQuery, FbElementList) {
    window.FbLink = new Class({

        Extends   : FbElementList,
        initialize: function (element, options) {
            this.setPlugin('fabrikLink');
            this.parent(element, options);
            this.subElements = this._getSubElements();
        },

        update: function (val) {
            this.getElement();
            var subs = this.element.getElements('.fabrikinput');
            if (typeOf(val) === 'object') {
                subs[0].value = val.label;
                subs[1].value = val.link;
            } else {
                subs.each(function (i) {
                    i.value = val;
                });
            }
        },

        getValue: function () {
            if (!this.options.editable) {
                return this.options.value;
            }

            var s = this._getSubElements();
            var a = [];
            s.each(function (v) {
                a.push(v.get('value'));
            });
            return a;
        }

    });

    return window.FbLink;
});
