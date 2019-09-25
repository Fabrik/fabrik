/**
 * Bootstrap Tooltips
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/**
 * Enable us to use the same class interface for tips.js but use Bootstrap popovers (Joomla 3)
 */
define(['jquery', 'fab/fabrik'], function (jQuery, Fabrik) {
    var FloatingTips = new Class({
        Implements: [Events],

        Binds: [],

        options: {
            fxProperties: {transition: Fx.Transitions.linear, duration: 500},
            'position'  : 'top',
            'trigger'   : 'hover',
            'content'   : 'title',
            'distance'  : 50,
            'tipfx'     : 'Fx.Transitions.linear',
            'heading'   : '',
            'duration'  : 500,
            'fadein'    : false,
            'notice'    : false,
            'html'      : true,
            showFn      : function (e) {
                e.stop();
                return true;
            },
            hideFn      : function (e) {
                e.stop();
                return true;
            },
            placement   : function (tip, ele) {
                // Custom functions should return top, left, right, bottom to set the tip location
                // Return false to use the default location
                Fabrik.fireEvent('bootstrap.tips.place', [tip, ele]);
                var pos = Fabrik.eventResults.length === 0 ? false : Fabrik.eventResults[0];
                if (pos === false) {
                    var opts = JSON.parse(ele.get('opts', '{}').opts);
                    return opts && opts.position ? opts.position : 'top';
                } else {
                    return pos;
                }
            }
        },

        initialize: function (elements, options) {
            if (Fabrik.bootstrapVersion('modal') >= 3 || typeof(Materialize) === 'object') {
                // We should override any Fabrik3 custom tip settings with bootstrap3 data-foo attributes in JLayouts
                return;
            }
            this.options = jQuery.extend(this.options, options);
            this.options.fxProperties = {transition: eval(this.options.tipfx), duration: this.options.duration};

            // Any tip (not necessarily in this instance has asked for all other tips to be hidden.
            window.addEvent('tips.hideall', function (e, trigger) {
                this.hideOthers(trigger);
            }.bind(this));
            if (elements) {
                this.attach(elements);
            }
        },

        attach: function (elements) {
            if (Fabrik.bootstrapVersion('modal') >= 3 || typeof(Materialize) === 'object') {
                // We should override any Fabrik3 custom tip settings with bootstrap3 data-foo attributes in JLayouts
                this.elements = document.getElements(elements);
                this.elements.each(function (trigger) {
                    jQuery(trigger).popover({html: true});
                });
                return;
            }
            var thisOpts;
            this.elements = jQuery(elements);
            var self = this;
            this.elements.each(function () {
                try {
                    var o = JSON.parse(jQuery(this).attr('opts'));
                    thisOpts = jQuery.type(o) === 'object' ? o : {};
                } catch (e) {
                    thisOpts = {};
                }
                if (thisOpts.position) {
                    thisOpts.defaultPos = thisOpts.position;
                    delete(thisOpts.position);
                }
                var opts = jQuery.extend({}, self.options, thisOpts);
                if (opts.content === 'title') {
                    opts.content = jQuery(this).prop('title');
                    jQuery(this).removeAttr('title');
                } else if (jQuery.type(opts.content) === 'function') {
                    var c = opts.content(this);
                    opts.content = c === null ? '' : c.innerHTML;
                }
                // Should always use the default placement function which can then via the
                // Fabrik event allow for custom tip placement
                opts.placement = self.options.placement;
                opts.title = opts.heading;

                if (jQuery(this).hasClass('tip-small')) {
                    opts.title = opts.content;
                    jQuery(this).tooltip(opts);
                } else {
                    if (!opts.notice) {
                        opts.title += '<button class="close" data-popover="' + this.id + '">&times;</button>';
                    }
                    try {
                        jQuery(this).popoverex(opts);
                    } catch (err) {
                        // Issues loading tips in pop up wins
                        console.log('failed to apply popoverex tips');
                    }
                }
            });
        },

        addStartEvent: function (trigger, evnt) {

        },

        addEndEvent: function (trigger, evnt) {

        },

        getTipContent: function (trigger, evnt) {

        },

        show: function (trigger, evnt) {

        },

        hide: function (trigger, evnt) {

        },

        hideOthers: function (except) {

        },

        hideAll: function () {
            jQuery('.popover').remove();
        }

    });

    /**
     * Extend Bootstrap tip class to allow for additional tip positioning
     */
    (function ($) {
        var PopoverEx = function (element, options) {
            this.init('popover', element, options);
        };

        if ($.fn.popover === undefined) {
            console.log('Fabrik: cant load PopoverEx as jQuery popover not found ' +
                '- could be the J template has overwritten jQuery (and yes Im looking at your Warp themes!)');
            return;
        }
        PopoverEx.prototype = $.extend({}, $.fn.popover.Constructor.prototype, {

            constructor: PopoverEx,
            tip        : function () {
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
                    this.setContent();

                    if (this.options.animation) {
                        $tip.addClass('fade');
                    }
                    var p = this.options.placement;
                    placement = typeof p === 'function' ? p.call(this, $tip[0], this.$element[0]) : p;
                    inside = /in/.test(placement);

                    $tip
                        .remove()
                        .css({top: 0, left: 0, display: 'block'})
                        .appendTo(inside ? this.$element : document.body);

                    pos = this.getPosition(inside);

                    actualWidth = $tip[0].offsetWidth;
                    actualHeight = $tip[0].offsetHeight;

                    switch (inside ? placement.split(' ')[1] : placement) {
                        case 'bottom':
                            tp = {top: pos.top + pos.height, left: pos.left + pos.width / 2 - actualWidth / 2};
                            break;
                        case 'bottom-left':
                            tp = {top: pos.top + pos.height, left: pos.left};
                            placement = 'bottom';
                            break;
                        case 'bottom-right':
                            tp = {top: pos.top + pos.height, left: pos.left + pos.width - actualWidth};
                            placement = 'bottom';
                            break;
                        case 'top':
                            tp = {top: pos.top - actualHeight, left: pos.left + pos.width / 2 - actualWidth / 2};
                            break;
                        case 'top-left':
                            tp = {top: pos.top - actualHeight, left: pos.left};
                            placement = 'top';
                            break;
                        case 'top-right':
                            tp = {top: pos.top - actualHeight, left: pos.left + pos.width - actualWidth};
                            placement = 'top';
                            break;
                        case 'left':
                            tp = {top: pos.top + pos.height / 2 - actualHeight / 2, left: pos.left - actualWidth};
                            break;
                        case 'right':
                            tp = {top: pos.top + pos.height / 2 - actualHeight / 2, left: pos.left + pos.width};
                            break;
                    }

                    $tip
                        .css(tp)
                        .addClass(placement)
                        .addClass('in');
                }
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
    })(jQuery);

    return FloatingTips;
});