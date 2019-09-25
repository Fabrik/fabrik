/**
 * Fabrik Window
 *
 * @copyright: Copyright (C) 2005-2014, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/**
 * Window factory
 *
 * @param   object  opts  Options
 *
 * @return  Fabrik.Window
 */

define(['jquery', 'fab/fabrik', 'jQueryUI', 'fab/utils'], function (jQuery, Fabrik, draggable) {
    Fabrik.getWindow = function (opts) {
        if (Fabrik.Windows[opts.id]) {
            if (opts.visible !== false) {
                Fabrik.Windows[opts.id].open();
            }
            Fabrik.Windows[opts.id].setOptions(opts);
            // Fabrik.Windows[opts.id].loadContent();
        } else {
            var type = opts.type ? opts.type : '';
            switch (type) {
                case 'redirect':
                    Fabrik.Windows[opts.id] = new Fabrik.RedirectWindow(opts);
                    break;
                case 'modal':
                    Fabrik.Windows[opts.id] = new Fabrik.Modal(opts);
                    jQuery(window).on('resize', function () {
                        if (opts.id in Fabrik.Windows) {
                            Fabrik.Windows[opts.id].fitToContent(false);
                        }
                    });
                    break;
                case '':
                /* falls through */
                default:
                    Fabrik.Windows[opts.id] = new Fabrik.Window(opts);
                    break;
            }
        }
        return Fabrik.Windows[opts.id];
    };


    Fabrik.Window = new Class({

        Implements: [Events, Options],

        options: {
            id               : 'FabrikWindow',
            data             : {},
            title            : '&nbsp;',
            container        : false,
            loadMethod       : 'html',
            contentURL       : '',
            createShowOverLay: false,
            width            : 300,
            height           : 300,
            loadHeight       : 100,
            expandable       : true,
            offset_x         : null,
            offset_y         : null,
            visible          : true,
            modalId          : '',
            onClose          : function () {
            },
            onOpen           : function () {
            },
            onContentLoaded  : function () {
                this.fitToContent(false);
            },
            destroy          : true
        },

        modal: false,

        classSuffix: '',

        expanded: false,

        initialize: function (options) {
            this.options = jQuery.extend(this.options, options);
            this.makeWindow();
        },

        /**
         * Tabs can resize content area
         */
        watchTabs: function () {
            var self = this;
            jQuery('.nav-tabs a').on('mouseup', function () {
                self.fitToWidth();
                self.drawWindow();
            });
        },

        /**
         * Create a close button
         * @returns {DomNode}
         */
        deleteButton: function () {
            return jQuery(Fabrik.jLayouts['modal-close'])[0];
        },

        /**
         * Get the window's content height
         * @returns {number}
         */
        contentHeight: function () {
            if (this.options.loadMethod === 'iframe') {
                return this.contentWrapperEl.find('iframe').height();
            }
            var w = this.window.find('.contentWrapper');
            // Reset height so we calculate it rather than taking the css value
            w.css('height', 'auto');

            // The mootools getDimensions actually works (jQuery height() is incorrect)
            return w[0].getDimensions(true).height;
        },

        /**
         * Center the modal window
         */
        center: function () {
            var source = this.window,
                pxWidth = this.windowDimensionInPx('width'),
                pxHeight = this.windowDimensionInPx('height'),
                w = source.width(),
                h = source.height(),
                d = {}, yy, xx;
            w = (w === null || w === 'auto') ? pxWidth : w;
            h = (h === null || h === 'auto') ? pxHeight : h;
            w = parseInt(w, 10);
            h = parseInt(h, 10);


            yy = jQuery(window).height() / 2 - (h / 2);

            if ( jQuery.inArray(jQuery(source).css('position'),['fixed','static']) === -1) {
                yy += window.getScroll().y;
            }
            //yy = (window.getSize().y / 2) - (h / 2);
            d.top = this.options.offset_y !== null ? window.getScroll().y + this.options.offset_y : yy;
            //d.top = this.options.offset_y !== null ? this.options.offset_y : yy;

            xx = jQuery(window).width() / 2 + window.getScroll().x - w / 2;
            //xx = (window.getSize().x / 2) - (w / 2);
            d.left = this.options.offset_x !== null ? window.getScroll().x + this.options.offset_x : xx;
            //d.left = this.options.offset_x !== null ? this.options.offset_x : xx;

            // Prototype J template css puts margin left on .modals
            d['margin-left'] = 0;
            source.css(d);
        },

        /**
         * Work out the modal/window width or height either from px or % variable
         *
         * @param   string  dir  Width or height.
         *
         * @return  int  Px width of window
         */
        windowDimensionInPx: function (dir) {
            var coord = dir === 'height' ? 'y' : 'x',
                dim = this.options[dir] + '';
            if (dim.indexOf('%') !== -1) {
                // @TODO fix
                if (dir === 'height') {
                    return Math.floor(jQuery(window).height() * (dim.toFloat() / 100));
                }
                else {
                    return Math.floor(jQuery(window).width() * (dim.toFloat() / 100));
                }
            }
            return parseInt(dim, 10);
        },

        /**
         * Build the window HTML, inject it into the document body
         */
        makeWindow: function () {
            var self = this, cw, ch;
            if (Fabrik.jLayouts[this.options.modalId]) {
                this.window = this.buildWinFromLayout();
                this.window.find('*[data-role="title"]').text(this.options.title);
            } else {
                this.window = this.buildWinViaJS();
            }

            // use fabrikHide to prevent the window displaying momentarily as page loads
            if (!this.options.visible) {
                this.window.addClass('fabrikHide');
            }

            jQuery(document.body).append(this.window);
            this.loadContent();

            if (!this.options.visible) {
                this.window.hide();
                this.window.removeClass('fabrikHide');
            }

            jQuery(this.window).find('*[data-role="close"]').on('click', function (e) {
                e.preventDefault();
                self.close();
            });

            this.window.find('*[data-role="expand"]').on('click', function (e) {
                e.preventDefault();
                self.expand();
            });

            cw = this.windowDimensionInPx('width');
            ch = this.contentHeight();

            this.contentWrapperEl.css({'height': ch, 'width': cw + 'px'});
            var handle = this.window.find('*[data-role="title"]');
            // Bauer asks... why?
            // Rob - because modals should not be resizeable or movable - those are Windows (uncommenting)
            if (!this.options.modal) {

                // Needed for UIKIt overrides as window root is actually its mask
                var source = this.window.find('*[data-draggable]').length === 0 ? this.window : this.window.find('*[data-draggable]');
                source.draggable(
                    {
                        'handle': handle,
                        drag    : function () {
                            Fabrik.fireEvent('fabrik.window.resized', self.window);
                            self.drawWindow();
                        }
                    }
                );

                source.resizable({
                    containment: self.options.container ? jQuery('#' + self.options.container) : null,
                    handles    : {
                        'n' : '.ui-resizable-n',
                        'e' : '.ui-resizable-e',
                        's' : '.ui-resizable-s',
                        'w' : '.ui-resizable-w',
                        'ne': '.ui-resizable-ne',
                        'se': '.ui-resizable-se',
                        'sw': '.ui-resizable-sw',
                        'nw': '.ui-resizable-nw'
                    },

                    resize: function () {
                        Fabrik.fireEvent('fabrik.window.resized', self.window);
                        self.drawWindow();
                    }
                });
            }

            // Rob - removed this caused any form with a file upload in it to be unscrollable - as we load the window
            // in the background.
            /* Prevent browser window from being scrolled */
           /* jQuery('body').css({'height':'100%','overflow':'hidden'});

            /!* Allow browser window to be scrolled again when modal is released from DOM *!/
            jQuery('div.modal').on('remove', function () {
                jQuery('body').css({'height':'initial','overflow':'initial'});
            });*/

            /* Use form title if modal handlelabel is blank
            * $$$ Rob - this is not going to work with UIKit for example - try not to rely on the DOM classes/markup
            * for this type of thing - assign data-foo attributes to the layouts instead */
            if (jQuery('div.modal-header .handlelabel').text().length === 0) {
                if (jQuery('div.itemContentPadder form').context.title.length) {
                    jQuery('div.modal-header .handlelabel').text(jQuery('div.itemContentPadder form').context.title);
                }
            }

            // Set window dimensions before center - needed for fileupload crop
            this.window.css('width', this.options.width);
            this.window.css('height', parseInt(this.options.height) + this.window.find('*[data-role="title"]').height());

            if (this.options.modal) {
                this.fitToContent(false);
            } else {
                this.center();
            }

            if (this.options.visible) {
                this.open();
            }
        },

        /**
         * Build the window from a JLayout file. Note to ensure that content is unique you must create
         * a unique $modalId in your PHP: FabrikHelperHTML::jLayoutJs($modalId, 'fabrik-modal')
         *
         * @return {jQuery}
         */
        buildWinFromLayout: function () {
            var window = jQuery(Fabrik.jLayouts[this.options.modalId]);
            this.contentEl = window.find('.itemContentPadder');
            this.contentWrapperEl = window.find('div.contentWrapper');

            return window;
        },

        /**
         * Create Window via JS.
         * @deprecated
         * @returns {*}
         */
        buildWinViaJS: function () {
            var draggerC, dragger, expandButton, expandIcon, resizeIcon, label, handleParts = [], self = this,
                directions, i;
            this.window = new Element('div', {
                'id'   : this.options.id,
                'class': 'fabrikWindow ' + this.classSuffix + ' modal'
            });
            var del = this.deleteButton();
            jQuery(del).on('click', function () {
                self.close();
            });
            var hclass = 'handlelabel';
            if (!this.options.modal) {
                hclass += ' draggable';
                draggerC = jQuery('<div />').addClass('bottomBar modal-footer');
                dragger = jQuery('<div />').addClass('dragger');
                // not really compatible with using jQuery resizeable()
                //resizeIcon = jQuery(Fabrik.jLayouts['icon-expand']);
                //resizeIcon.prependTo(dragger);
                draggerC.append(dragger);
            }

            expandIcon = jQuery(Fabrik.jLayouts['icon-full-screen']);
            label = jQuery('<h3 />').addClass(hclass).text(this.options.title);
            jQuery(label).data('role', 'title');
            // turns out you can find() data attrs added with data()
            jQuery(label).attr('data-role', 'title');

            handleParts.push(label);
            if (this.options.expandable && this.options.modal === false) {
                expandButton = jQuery('<a />').addClass('expand').attr({
                    'href': '#'
                }).append(expandIcon);
                handleParts.push(expandButton);
            }

            handleParts.push(del);
            this.handle = this.getHandle().append(handleParts);

            var bottomBarHeight = 0;
            var topBarHeight = 15;
            var contentHeight = parseInt(this.options.height) - bottomBarHeight - topBarHeight;
            if (contentHeight < this.options.loadHeight) {
                contentHeight = this.options.loadHeight;
            }
            this.contentWrapperEl = jQuery('<div />').addClass('contentWrapper').css({
                'height': contentHeight + 'px'
            });
            var itemContent = jQuery('<div />').addClass('itemContent');
            this.contentEl = jQuery('<div />').addClass('itemContentPadder');
            itemContent.append(this.contentEl);
            this.contentWrapperEl.append(itemContent);

            this.window = jQuery(this.window);
            if (this.options.modal) {
                this.window.append([this.handle, this.contentWrapperEl]);
            } else {
                this.window.append([this.handle, this.contentWrapperEl, draggerC]);
                directions = ['n', 'e', 's', 'w', 'nw', 'ne', 'se', 'sw'];
                for (i = 0; i < directions.length; i ++) {
                    this.window.append(jQuery('<div class="ui-resizable-' + directions[i] + ' ui-resizable-handle"></div>'));
                }
            }

            return this.window;
        },

        /**
         * Toggle the window full screen
         */
        expand: function () {
            if (!this.expanded) {
                this.expanded = true;
                this.unexpanded = jQuery.extend({}, this.window.position(),
                    {'width': this.window.width(), 'height': this.window.height()});//this.window.getCoordinates();
                var scroll = window.getScroll();
                this.window.css({'left': scroll.x + 'px', 'top': scroll.y + 'px'});
                this.window.css({'width': jQuery(window).width(), 'height': jQuery(window).height()});
            } else {
                this.window.css({
                    'left': this.unexpanded.left + 'px',
                    'top' : this.unexpanded.top + 'px'
                });
                this.window.css({'width': this.unexpanded.width, 'height': this.unexpanded.height});
                this.expanded = false;
            }
            this.drawWindow();
        },

        getHandle: function () {
            var c = this.handleClass();
            return jQuery('<div />').addClass('draggable ' + c);
        },

        handleClass: function () {
            return 'modal-header';
        },

        loadContent: function () {
            var u, self = this;
            window.fireEvent('tips.hideall');
            switch (this.options.loadMethod) {

                case 'html':
                    if (this.options.content === undefined) {
                        fconsole('no content option set for window.html');
                        this.close();
                        return;
                    }
                    if (typeOf(this.options.content) === 'element') {
                        jQuery(this.options.content).appendTo(this.contentEl);
                    } else {
                        this.contentEl.html(this.options.content);
                    }
                    this.options.onContentLoaded.apply(this);
                    this.watchTabs();

                    break;
                case 'xhr':
                    self.window.width(self.options.width);
                    self.window.height(self.options.height);
                    // for some biaarre reason the onCContentLoaded option sometimes disappears
                    self.onContentLoaded = self.options.onContentLoaded;
                    Fabrik.loader.start(self.contentEl);
                    new jQuery.ajax({
                        'url'   : this.options.contentURL,
                        'data'  : jQuery.extend(this.options.data, {'fabrik_window_id': this.options.id}),
                        'method': 'post',
                    }).success(function (r) {
                        Fabrik.loader.stop(self.contentEl);
                        self.contentEl.append(r);
                        self.watchTabs();
                        self.center();
                        self.onContentLoaded.apply(self);
                        Joomla.loadOptions();
                    });
                    break;
                // Deprecated - causes all sorts of issues with window resizing.
                case 'iframe':
                    var h = parseInt(this.options.height, 10) - 40,
                        scrollX = this.contentEl[0].scrollWidth,
                        w = scrollX + 40 < jQuery(window).width() ? scrollX + 40 : jQuery(window).width();
                    u = this.window.find('.itemContent');
                    Fabrik.loader.start(u);

                    if (this.iframeEl) {
                        this.iframeEl.remove();
                    }
                    this.iframeEl = jQuery('<iframe />').addClass('fabrikWindowIframe').attr({
                        'id'          : this.options.id + '_iframe',
                        'name'        : this.options.id + '_iframe',
                        'class'       : 'fabrikWindowIframe',
                        'src'         : this.options.contentURL,
                        'marginwidth' : 0,
                        'marginheight': 0,
                        'frameBorder' : 0,
                        'scrolling'   : 'auto',
                    }).css({
                        'height': h + 'px',
                        'width' : w
                    }).appendTo(u);
                    this.iframeEl.hide();
                    this.iframeEl.on('load', function () {
                        Fabrik.loader.stop(self.window.find('.itemContent'));
                        self.iframeEl.show();
                        jQuery(self).trigger('onContentLoaded', [self]);
                        self.watchTabs();
                    });
                    break;
            }
        },

        /**
         * Calculate the window title height
         * @returns {number}
         */
        titleHeight: function () {
            var titleHeight = this.window.find('.' + this.handleClass());
            titleHeight = titleHeight.length > 0 ? titleHeight.outerHeight() : 25;
            if (isNaN(titleHeight)) {
                titleHeight = 0;
            }

            return titleHeight;
        },

        /**
         * Calculate the window footer height
         * @returns {Number}
         */
        footerHeight: function () {
            var h = parseInt(this.window.find('.bottomBar').outerHeight(), 10);
            if (isNaN(h)) {
                h = 0;
            }
            return h;
        },

        /**
         * Draw the window
         */
        drawWindow: function () {
            var titleHeight = this.titleHeight(),
                footer = this.footerHeight(),
                h = this.contentHeight();

            // Needed for UIKIt overrides as window root is actually its mask
            var source = this.window.find('*[data-draggable]').length === 0 ? this.window : this.window.find('*[data-draggable]');
            var w = source.width();

            // If content larger than window - set it to the window (minus footer/title)
            if (h > this.window.height()) {
                h = this.window.height() - titleHeight - footer;
            }

            this.contentWrapperEl.css('height', h);
            this.contentWrapperEl.css('width', w - 2);

            // Resize iframe when window is resized
            if (this.options.loadMethod === 'iframe') {
                this.iframeEl.css('height', this.contentWrapperEl[0].offsetHeight);
                this.iframeEl.css('width', this.contentWrapperEl[0].offsetWidth - 10);
            }
        },

        fitToContent: function (scroll, center) {
            scroll = scroll === undefined ? true : scroll;
            center = center === undefined ? true : center;

            if (this.options.loadMethod !== 'iframe') {
                // As iframe content may not be on the same domain we CAN'T
                // guarantee access to its body element to work out its dimensions
                this.fitToHeight();
                this.fitToWidth();
            }
            this.drawWindow();
            if (center) {
                this.center();
            }
            if (!this.options.offset_y && scroll) {
                //new Fx.Scroll(window).toElement(this.window);
                jQuery('body').scrollTop(this.window.offset().top);
            }
        },

        /**
         * Fit the window height to the min of either its content height or the window height
         */
        fitToHeight: function () {
            var testH = this.contentHeight() + this.footerHeight() + this.titleHeight(),
                winHeight = jQuery(window).height(),
                h = testH < winHeight ? testH : winHeight;
            this.window.css('height', h);
        },

        /**
         * Fit the window width to the min of either its content width or the window width
         */
        fitToWidth: function () {
            var contentEl = this.window.find('.itemContent'),
                winWidth = jQuery(window).width(),
                x = contentEl[0].scrollWidth;
            var w = x + 25 < winWidth ? x + 25 : winWidth;
            this.window.css('width', w);
        },

        /**
         * Close the window
         * @param {boolean} destroy window.
         */
        close: function (destroy) {
            destroy = destroy ? destroy : false;
            // By default cant destroy as we want to be able to reuse them (see crop in fileupload element)
            if (this.options.destroy || destroy) {

                // However db join add (in repeating group) has a fit if we don't remove its content
                this.window.remove();
                delete(Fabrik.Windows[this.options.id]);
            } else {
                this.window.fadeOut({duration: 0});
            }
            Fabrik.tips.hideAll();
            //this.fireEvent('onClose', [this]);
            this.options.onClose.apply(this);
            Fabrik.fireEvent('fabrik.window.close', [this]);
        },

        /**
         * Open the window
         * @param {event} e
         */
        open: function (e) {
            if (e) {
                e.stopPropagation();
            }
            //this.window.fadeIn({duration: 0});
            this.window.show();
            //this.fireEvent('onOpen', [this]);
            this.options.onOpen.apply(this);
        }
    });

    Fabrik.Modal = new Class({
        Extends: Fabrik.Window,

        modal: true,

        classSuffix: 'fabrikWindow-modal',

        getHandle: function () {
            return jQuery('<div />').addClass(this.handleClass());
        },

        fitToHeight: function () {

            var testH = this.contentHeight() + this.footerHeight() + this.titleHeight(),
                winHeight = jQuery(window).height(),
                h = testH < winHeight ? testH : winHeight;
            this.window.css('height', Math.max(parseInt(this.options.height), h));
        },

        /**
         * Fit the window width to the min of either its content width or the window width
         */
        fitToWidth: function () {
            this.window.css('width', this.options.width);
        },
    });

    Fabrik.RedirectWindow = new Class({
        Extends   : Fabrik.Window,
        initialize: function (opts) {
            var opts2 = {
                'id'         : 'redirect',
                'title'      : opts.title ? opts.title : '',
                loadMethod   : loadMethod,
                'width'      : opts.width ? opts.width : 300,
                'height'     : opts.height ? opts.height : 320,
                'minimizable': false,
                'collapsible': true,
                'contentURL' : opts.contentURL ? opts.contentURL : ''
            };
            opts2.id = 'redirect';
            opts = jQuery.merge(opts2, opts);
            var loadMethod, url = opts.contentURL;
            //if its a site page load via xhr otherwise load as iframe
            opts.loadMethod = 'xhr';
            if (!url.contains(Fabrik.liveSite) && (url.contains('http://') || url.contains('https://'))) {
                opts.loadMethod = 'iframe';
            } else {
                if (!url.contains('tmpl=component')) {
                    opts.contentURL += url.contains('?') ? '&tmpl=component' : '?tmpl=component';
                }
            }
            this.options = jQuery.extend(this.options, opts);
            this.makeWindow();
        }
    });

    return Fabrik.Window;
});

