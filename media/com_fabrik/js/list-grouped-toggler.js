/**
 * Created by rob on 21/03/2016.
 */


define(['jquery'], function (jQuery) {
    /**
     * Toggle grouped data by click on the grouped headings icon
     */

    var FbGroupedToggler = new Class({
        Binds: [],

        Implements: [Options],

        options: {
            collapseOthers: false,
            startCollapsed: false,
            bootstrap     : false
        },

        initialize: function (container, options) {
            var rows, h, img, state;
            if (typeOf(container) === 'null') {
                return;
            }
            this.setOptions(options);
            this.container = container;
            this.toggleState = 'shown';
            if (this.options.startCollapsed && this.options.isGrouped) {
                this.collapse();
            }
            container.addEvent('click:relay(.fabrik_groupheading a.toggle)', function (e) {
                if (e.rightClick) {
                    return;
                }
                e.stop();
                e.preventDefault(); //should work according to http://mootools.net/blog/2011/09/10/mootools-1-4-0/

                if (this.options.collapseOthers) {
                    this.collapse();
                }
                h = e.target.getParent('.fabrik_groupheading');
                img = this.options.bootstrap ? h.getElement('*[data-role="toggle"]') : h.getElement('img');
                state = img.retrieve('showgroup', true);

                if (h.getNext() && h.getNext().hasClass('fabrik_groupdata')) {
                    // For div tmpl
                    rows = h.getNext();
                } else {
                    rows = h.getParent().getNext();
                }
                state ? jQuery(rows).hide() : jQuery(rows).show();
                this.setIcon(img, state);
                state = state ? false : true;
                img.store('showgroup', state);
                return false;
            }.bind(this));
        },

        setIcon: function (img, state) {
            if (this.options.bootstrap) {
                var expandIcon = img.get('data-expand-icon'),
                    collapsedIcon = img.get('data-collapse-icon');
                if (state) {
                    img.removeClass(expandIcon);
                    img.addClass(collapsedIcon);
                } else {
                    img.addClass(expandIcon);
                    img.removeClass(collapsedIcon);
                }
            } else {
                if (state) {
                    img.src = img.src.replace('orderasc', 'orderneutral');
                } else {
                    img.src = img.src.replace('orderneutral', 'orderasc');
                }
            }
        },

        collapse: function () {
            jQuery(this.container.getElements('.fabrik_groupdata')).hide();
            var selector = this.options.bootstrap ? '*[data-role="toggle"]' : 'img';
            var i = this.container.getElements('.fabrik_groupheading a ' + selector);
            if (i.length === 0) {
                i = this.container.getElements('.fabrik_groupheading ' + selector);
            }
            i.each(function (img) {
                img.store('showgroup', false);
                this.setIcon(img, true);
            }.bind(this));
        },

        expand: function () {
            jQuery(this.container.getElements('.fabrik_groupdata')).show();
            var selector = this.options.bootstrap ? '*[data-role="toggle"]' : 'img';
            var i = this.container.getElements('.fabrik_groupheading a ' + selector);
            if (i.length === 0) {
                i = this.container.getElements('.fabrik_groupheading ' + selector);
            }
            i.each(function (img) {
                img.store('showgroup', true);
                this.setIcon(img, false);
            }.bind(this));
        },

        toggle: function () {
            this.toggleState === 'shown' ? this.collapse() : this.expand();
            this.toggleState = this.toggleState === 'shown' ? 'hidden' : 'shown';
        }
    });

    return FbGroupedToggler;
});