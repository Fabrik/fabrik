/**
 * JS Periodical Element
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'fab/element'], function (jQuery, FbElement) {
    window.FbJSPeriodical = new Class({
        Extends: FbElement,
        options: {
            code  : '',
            period: 1000
        },

        initialize: function (element, options) {
            this.setPlugin('fabrikPeriodical');
            this.parent(element, options);
            var periodical;

            this.fx = function () {
                eval(this.options.code);
            }.bind(this);
            this.fx();
            periodical = this.fx.periodical(this.options.period, this);
        }
    });

    return window.FbJSPeriodical;
});