/**
 * Bootstrap Tooltips
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/**
 * Enable us to use the same class interface for tips.js but use Bootrap popovers (Joomla 3)
 */
var FloatingTips = new Class({
	Implements: [Options, Events],

	options: {
		fxProperties: {transition: Fx.Transitions.linear, duration: 500},
		position: 'top',
		'showOn': 'mouseenter',
		'hideOn': 'mouseleave',
		'content': 'title',
		'distance': 50,
		'tipfx': 'Fx.Transitions.linear',
		'heading': '',
		'duration': 500,
		'fadein': false,
		'notice': false,
		'html': true,
		showFn: function (e) {
			e.stop();
			return true;
		},
		hideFn: function (e) {
			e.stop();
			return true;
		},
		placement: function (tip, ele) {
			// Custom functions should return top, left, right, bottom to set the tip location
			// Return false to use the default location
			Fabrik.fireEvent('bootstrap.tips.place', [tip, ele]);
			var pos = Fabrik.eventResults.length === 0 ? false : Fabrik.eventResults[0];
			if (pos === false) {
				var opts = JSON.decode(ele.get('opts', '{}').opts);
				return opts && opts.position ? opts.position : 'top';
			} else {
				return pos;
			}
		}
	},

	initialize: function (elements, options) {
		this.setOptions(options);
		this.options.fxProperties = {transition: eval(this.options.tipfx), duration: this.options.duration};

		// Any tip (not necessarily in this instance has asked for all other tips to be hidden.
		window.addEvent('tips.hideall', function (e, element) {
			if (typeOf(element) === 'null') {
				this.hideAll();
			} else {
				this.hideOthers(element);
			}
		}.bind(this));
		if (elements) {
			this.attach(elements);
		}
	},

	attach: function (elements) {
		this.selector = elements;
		this.elements = $$(elements);
		this.elements.each(function (element) {
			var thisOpts = JSON.decode(element.get('opts', '{}').opts);
			thisOpts = thisOpts ? thisOpts : {};
			if (thisOpts.position) {
				thisOpts.defaultPos = thisOpts.position;
				delete(thisOpts.position);
			}
			var opts = Object.merge(Object.clone(this.options), thisOpts);
			if (opts.content === 'title') {
				opts.content = element.get('title');
				element.erase('title');
			} else if (typeOf(opts.content) === 'function') {
				var c = opts.content(element);
				opts.content = typeOf(c) === 'null' ? '' : c.innerHTML;
			}
			/**
			 * Should always use the default placement function which can then,
			 * via the Fabrik event, allow for custom tip placement
			 **/
			opts.placement = this.options.placement;
			opts.title = opts.heading;

			if (element.hasClass('tip-small')) {
				opts.title = opts.content;
				jQuery(element).tooltip(opts);
			} else {
				if (!opts.notice) {
					opts.title += '<button class="close" data-popover="' + element.id + '">&times;</button>';
				}
				jQuery(element).popoverex(opts);
			}

		}.bind(this));

	},

	addStartEvent: function (element, evnt) {

	},

	addEndEvent: function (element, evnt) {

	},

	getTipContent: function (element, evnt) {

	},

	show: function (element, evnt) {

	},

	hide: function (element, evnt) {

	},

	hideOthers: function (except) {
		this.elements.each(function (element) {
			element = jQuery(element);
			if (typeOf(element) !== 'null' && element !== except && typeOf(element.data('popover')) !== 'null') {
				element.data('popover').hide();
			}
		}.bind(this));
	},

	hideAll: function () {
		this.elements.each(function (element) {
			element = jQuery(element);
			if (typeOf(element) !== 'null' && typeOf(element.data('popover')) !== 'null') {
				element.data('popover').hide();
			}
		}.bind(this));
	}

});

/**
 * Extend Bootstrap tip class to allow for additional tip positioning
 */
(function ($) {
	var PopoverEx = function (element, options) {
		// Save trigger options
		this.triggers = options.trigger.split(' ');
		this.isManualShow = false;
		this.hasFocus = false;
		this.manualShowTimer = null;
		this.$element = jQuery(element);
		this.placement = '';
		this.position = {top: -1, left: -1};
		this.inside = null;
		// Reset trigger options
		options.trigger = 'manual';
		options.showOn = options.hideOn = null;
		this.init('popover', element, options);
		// Bind our own events - mouse events on the label, focus events on the field.
		jQuery(element)
			.mouseenter(this.mouseenter)
			.mouseleave(this.mouseleave)
			.click(this.click);
		jQuery(element).parents('.control-group')
			.on('focus', 'input[type!=hidden]', this.focus)
			.on('focus', 'select', this.focus)
			.on('blur', 'input[type!=hidden]', this.blur)
			.on('blur', 'select', this.blur);
	};
	PopoverEx.prototype = $.extend({}, $.fn.popover.Constructor.prototype, {

		constructor: PopoverEx,
		tip: function () {
			if (!this.$tip) {
				this.$tip = $(this.options.template);
				if (this.options.modifier) {
					this.$tip.addClass(this.options.modifier);
				}
			}
			return this.$tip;
		},

		show: function () {
			var $tip, inside, pos, actualWidth, actualHeight, placement, tp;
			if (this.hasContent() && this.enabled) {
				$tip = this.tip();

				placement = typeof this.options.placement === 'function' ? this.options.placement.call(this, $tip[0], this.$element[0]) : this.options.placement;
				inside = /in/.test(placement);
				placement = inside ? placement.split(' ')[1] : placement;
				place = placement.split('-')[0];

				if (!$tip.hasClass('in')) {
					this.setContent();
				}

				$tip.attr('for',this.$element.attr('for'));

				if (this.options.animation) {
					$tip.addClass('fade');
				}

				if (!$tip.hasClass('in') || this.inside !== inside) {
					$tip
						.detach()
						.appendTo(inside ? this.$element : document.body);
				}
				if (!$tip.hasClass('in') || this.placement !== placement) {
					$tip
						.removeClass('top bottom left right')
						.addClass(place)
						.css({ top: 0, left: 0, display: 'block' });
				}

				pos = this.getPosition(inside);

				actualWidth = $tip[0].offsetWidth;
				actualHeight = $tip[0].offsetHeight;

				switch (placement) {
				case 'bottom':
					tp = {top: pos.top + pos.height, left: pos.left + pos.width / 2 - actualWidth / 2};
					break;
				case 'bottom-left':
					tp = {top: pos.top + pos.height, left: pos.left};
					break;
				case 'bottom-right':
					tp = {top: pos.top + pos.height, left: pos.left + pos.width - actualWidth};
					break;
				case 'top':
					tp = {top: pos.top - actualHeight, left: pos.left + pos.width / 2 - actualWidth / 2};
					break;
				case 'top-left':
					tp = {top: pos.top - actualHeight, left: pos.left};
					break;
				case 'top-right':
					tp = {top: pos.top - actualHeight, left: pos.left + pos.width - actualWidth};
					break;
				case 'left':
					tp = {top: pos.top + pos.height / 2 - actualHeight / 2, left: pos.left - actualWidth};
					break;
				case 'right':
					tp = {top: pos.top + pos.height / 2 - actualHeight / 2, left: pos.left + pos.width};
					break;
				}

				if (!$tip.hasClass('in') || this.placement !== placement || this.position.top !== tp.top || this.position.left !== tp.left) {
					$tip
						.css(tp)
						.addClass('in');
				}
				this.placement = placement;
				this.position = tp;
				this.inside = inside;

				return this;
			}
		},

		manualShow: function(delay) {
			/**
			 * This is intended for use by single alement ajax validation - element.js addTipMsg
			 * Initially the tip on a timer (as fully sticky tips can hide other element)
			 * and during the timeout mouseleave is prevented from hiding it i.e. it is sticky.
			 * Manual tips are limited to one to prevent an epidemic of tips.
			 * Manual tips are also shown on focus and hidden on blur to provide help on correcting
			 * a field that has failed validation.
			 **/
			delay = typeOf(delay) !== 'null' ? delay : 5000;
			this.isManualShow = true;
			this.show();
			this.manualShowTimer = window.setTimeout(function () {
				this.manualShowTimeout();
			}.bind(this), delay)
			return this;
		},

		manualShowTimeout: function() {
			this.manualShowTimer = null;
			if (!this.isManualShow) {
				return;
			}
			this.hide();
		},

		manualShowCancel: function() {
			if (!this.isManualShow) {
				return;
			}
			if (this.manualShowTimer !== null) {
				window.clearTimeout(this.manualShowTimer);
				this.manualShowTimer = null;
			}
			this.hide();
			return this;
		},

		manualHide: function() {
			this.manualShowCancel();
			this.isManualShow = false;
			return this;
		},

		mouseenter: function () {
			if (typeOf(jQuery(this)) === 'null' || typeOf(jQuery(this).data('popover')) === 'null') {
				return;
			}
			$tip = jQuery(this).data('popover');
			if ($tip.triggers.indexOf("hover") < 0) {
				return;
			}
			$tip.show();
		},

		mouseleave: function () {
			if (typeOf(jQuery(this)) === 'null' || typeOf(jQuery(this).data('popover')) === 'null') {
				return;
			}
			$tip = jQuery(this).data('popover');
			if ($tip.triggers.indexOf("hover") < 0
			|| ($tip.isManualShow
					&& ($tip.manualShowTimer !== null || $tip.hasFocus))) {
				return;
			}
			$tip.hide();
		},

		focus: function () {
			$label = jQuery(this).parents('.control-group').find(Fabrik.tips.selector);
			if (typeOf($label) === 'null' || typeOf($label.data('popover')) === 'null') {
				return;
			}
			$tip = $label.data('popover');
			$tip.hasFocus = true;
			if ($tip.triggers.indexOf("focus") < 0 && !$tip.isManualShow) {
				return;
			}
			$tip.show();
		},

		blur: function () {
			$label = jQuery(this).parents('.control-group').find(Fabrik.tips.selector);
			if (typeOf($label) === 'null' || typeOf($label.data('popover')) === 'null') {
				return;
			}
			$tip = $label.data('popover');
			$tip.hasFocus = false;
			if ($tip.triggers.indexOf("focus") < 0 && !$tip.isManualShow) {
				return;
			}
			$tip.hide();
		},

		click: function (e) {
			if (typeOf(jQuery(this)) === 'null' || typeOf(jQuery(this).data('popover')) === 'null') {
				return;
			}
			$tip = jQuery(this).data('popover');
			if ($tip.triggers.indexOf("click") < 0) {
				return;
			}
			$tip.toggle();
		}

	});

	$.fn.popoverex = function (option) {
		return this.each(function () {
			var $this = $(this),
			data = $this.data('popover'),
			options = typeof option === 'object' && option;
			if (!data) {
				$this.data('popover', (data = new PopoverEx(this, options)));
			}
			if (typeof option === 'string') {
				data[option]();
			}
        });
    };
})(window.jQuery);