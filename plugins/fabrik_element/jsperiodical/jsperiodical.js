/**
 * JS Periodical Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

FbJSPeriodical = new Class({
	Extends: FbElement,
	options: {
		code : '',
		period : 1000
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