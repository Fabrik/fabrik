/**
 * Various Fabrik JS classes
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/**
 * Create the Fabrik name space
 */
define(['jquery', 'fab/loader', 'fab/requestqueue'], function (jQuery, Loader, RequestQueue) {

    var doc = jQuery(document);

    document.addEvent('click:relay(.popover button.close)', function (event, target) {
        var popover = '#' + target.get('data-popover'),
            pEl = document.getElement(popover);
        jQuery(popover).popover('hide');

        if (typeOf(pEl) !== 'null' && pEl.get('tag') === 'input') {
            pEl.checked = false;
        }
    });
    var Fabrik = {
        events: {}
    };

    /**
     * Get the bootstrap version. Returns either 2.x of 3.x
     * @param {string} pluginName Optional plugin name to search fof VERSION property
     * @returns {*}
     */
    Fabrik.bootstrapVersion = function (pluginName) {
        // try the function provided first, punt to 'tooltip' if it doesn't exist (some plugins override 'modal')
        var i, pluginNames = [pluginName || 'modal', 'tooltip'], len = pluginNames.length;
        for (i=0; i<len; ++i) {
            var pluginFn = jQuery.fn[pluginNames[i]];
            if (pluginFn) {
                if (pluginFn.VERSION) {
                    return pluginFn.VERSION.match(/(\d+)\./)[0].toInt();
                }
                else if (pluginFn.Constructor && pluginFn.Constructor.VERSION)
                {
                    return pluginFn.Constructor.VERSION.match(/(\d+)\./)[0].toInt();
                }
            }
        }

        // if we got this far, it's not 3 or 4, so either 2, or not Bootstrap!
        return 2;
    };

    Fabrik.Windows = {};
    Fabrik.loader = new Loader();
    Fabrik.blocks = {};
    Fabrik.periodicals = {};
    Fabrik.addBlock = function (blockid, block) {
        Fabrik.blocks[blockid] = block;
        Fabrik.fireEvent('fabrik.block.added', [block, blockid]);
    };

    /**
     * Search for a block
     *
     * @param {string}  blockid Block id
     * @param {boolean} exact Exact match - default false. When false, form_8 will
     *            match form_8 & form_8_1
     * @param {function} cb Call back function - if supplied a periodical check is set
     *            to find the block and once found then the cb() is run, passing
     *            the block back as an parameter
     *
     * @return mixed false if not found | Fabrik block
     */
    Fabrik.getBlock = function (blockid, exact, cb) {
        cb = cb ? cb : false;
        if (cb) {
            Fabrik.periodicals[blockid] = Fabrik._getBlock.periodical(500, this, [blockid, exact, cb]);
        }
        return Fabrik._getBlock(blockid, exact, cb);
    };

    /**
     * Private Search for a block
     *
     * @param {string} blockid Block id
     * @param {boolean} exact Exact match - default false. When false, form_8 will
     *            match form_8 & form_8_1
     * @param {function} cb Call back function - if supplied a periodical check is set
     *            to find the block and once found then the cb() is run, passing
     *            the block back as an parameter
     *
     * @return {boolean|object} false if not found | Fabrik block
     */
    Fabrik._getBlock = function (blockid, exact, cb) {
        var foundBlockId;
        exact = exact ? exact : false;
        if (Fabrik.blocks[blockid] !== undefined) {

            // Exact match
            foundBlockId = blockid;
        } else {
            if (exact) {
                return false;
            }
            // Say we're editing a form (blockid = form_1_2) - but have simply
            // asked for form_1
            var keys = Object.keys(Fabrik.blocks), i = keys.searchFor(blockid);
            if (i === -1) {
                return false;
            }
            foundBlockId = keys[i];
        }
        if (cb) {
            clearInterval(Fabrik.periodicals[blockid]);
            cb(Fabrik.blocks[foundBlockId]);
        }
        return Fabrik.blocks[foundBlockId];
    };

    doc.on('click', '.fabrik_delete a, .fabrik_action a.delete, .btn.delete', function (e) {
        if (e.rightClick) {
            return;
        }
        Fabrik.watchDelete(e, this);
    });
    doc.on('click', '.fabrik_edit a, a.fabrik_edit', function (e) {
        if (e.rightClick) {
            return;
        }
        Fabrik.watchEdit(e, this);
    });
    doc.on('click', '.fabrik_view a, a.fabrik_view', function (e) {
        if (e.rightClick) {
            return;
        }
        Fabrik.watchView(e, this);
    });

    // Related data links
    document.addEvent('click:relay(*[data-fabrik-view])', function (e, target) {
        if (e.rightClick) {
            return;
        }
        var url, a, title;
        e.preventDefault();
        if (e.target.get('tag') === 'a') {
            a = e.target;
        } else {
            a = typeOf(e.target.getElement('a')) !== 'null' ? e.target.getElement('a') : e.target.getParent('a');
        }

        url = a.get('href');
        url += url.contains('?') ? '&tmpl=component&ajax=1' : '?tmpl=component&ajax=1';
        url += '&format=partial';
        
        // Only one edit window open at the same time.
        $H(Fabrik.Windows).each(function (win, key) {
            win.close();
        });
        title = a.get('title');
        if (!title) {
            title = Joomla.JText._('COM_FABRIK_VIEW');
        }

        var winOpts = {
            'id'        : 'view.' + url,
            'title'     : title,
            'loadMethod': 'xhr',
            'contentURL': url
        };
        Fabrik.getWindow(winOpts);
    });

    Fabrik.removeEvent = function (type, fn) {
        if (Fabrik.events[type]) {
            var index = Fabrik.events[type].indexOf(fn);
            if (index !== -1) {
                delete Fabrik.events[type][index];
            }
        }
    };

    // Events test: replacing window.addEvents as they are reset when you reload
    // mootools in ajax window.
    // need to load mootools in ajax window otherwise Fabrik classes don't
    // correctly load
    Fabrik.addEvent = Fabrik.on = function (type, fn) {
        if (!Fabrik.events[type]) {
            Fabrik.events[type] = [];
        }
        if (!Fabrik.events[type].contains(fn)) {
            Fabrik.events[type].push(fn);
        }
    };

    Fabrik.addEvents = function (events) {
        var event;
        for (event in events) {
            if (events.hasOwnProperty(event)) {
                Fabrik.addEvent(event, events[event]);
            }
        }
        return this;
    };

    Fabrik.fireEvent = Fabrik.trigger = function (type, args, delay) {
        var events = Fabrik.events;

        // An array of returned values from all events.
        this.eventResults = [];
        if (!events || !events[type]) {
            return this;
        }
        args = Array.mfrom(args);
        events[type].each(function (fn) {
            if (delay) {
                this.eventResults.push(fn.delay(delay, this, args));
            } else {
                this.eventResults.push(fn.apply(this, args));
            }
        }, this);
        return this;
    };

    Fabrik.requestQueue = new RequestQueue();

    Fabrik.cbQueue = {
        'google': []
    };

    /**
     * Load the google maps API once
     *
     * @param {boolean|string} k API key
     * @param {function|string} cb Callback method function or function name (assigned to window)
     */
    Fabrik.loadGoogleMap = function (k, cb) {

        var prefix = document.location.protocol === 'https:' ? 'https:' : 'http:';
        var src = prefix + '//maps.googleapis.com/maps/api/js?libraries=places,visualization&callback=Fabrik.mapCb';
        
        if (k !== false) {
            src += '&key=' + k;
        }

        // Have we previously started to load the Googlemaps script?
        var gmapScripts = Array.mfrom(document.scripts).filter(function (f) {
            return f.src === src;
        });

        if (gmapScripts.length === 0) {
            // Not yet loaded so create a script dom node and inject it into the
            // page.
            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = src;
            document.body.appendChild(script);

            // Store the callback into the cbQueue, which will be processed
            // after gmaps is loaded.
            Fabrik.cbQueue.google.push(cb);
        } else {
            // We've already added the Google maps js script to the document
            if (Fabrik.googleMap) {
                window[cb]();

                // $$$ hugh - need to fire these by hand, otherwise when
                // re-using a map object, like
                // opening a popup edit for the second time, the map JS will
                // never get these events.

                // window.fireEvent('google.map.loaded');
                // window.fireEvent('google.radius.loaded');

            } else {
                // We've started to load the Google Map code but the callback
                // has not been fired.
                // Cache the call back (it will be fired when Fabrik.mapCb is
                // run.
                Fabrik.cbQueue.google.push(cb);

            }
        }
    };

    /**
     * Called once the google maps script has loaded, will run through any
     * queued callback methods and fire them.
     */
    Fabrik.mapCb = function () {
        Fabrik.googleMap = true;
        var fn, i;
        for (i = 0; i < Fabrik.cbQueue.google.length; i++) {
            fn = Fabrik.cbQueue.google[i];
            if (typeOf(fn) === 'function') {
                fn();
            } else {
                window[fn]();
            }
        }
        Fabrik.cbQueue.google = [];
    };

    /**
     * Globally observe delete links
     * @param {event} e
     * @param {Dom} target
     */
    Fabrik.watchDelete = function (e, target) {
        var l, ref, r;
        r = e.target.getParent('.fabrik_row');
        if (!r) {
            r = Fabrik.activeRow;
        }
        if (r) {
            var chx = r.getElement('input[type=checkbox][name*=id]');
            if (typeOf(chx) !== 'null') {
                chx.checked = true;
            }
            ref = r.id.split('_');
            ref = ref.splice(0, ref.length - 2).join('_');
            l = Fabrik.blocks[ref];
        } else {
            // CheckAll
            ref = e.target.getParent('.fabrikList');
            if (typeOf(ref) !== 'null') {
                // Embedded in list
                ref = ref.id;
                l = Fabrik.blocks[ref];
            } else {
                // Floating
                var wrapper = target.getParent('.floating-tip-wrapper');
                if (wrapper) {
                    var refList = wrapper.retrieve('list');
                    ref = refList.id;
                } else {
                    ref = target.get('data-listRef');
                }

                l = Fabrik.blocks[ref];
                // Deprecated in 3.1 // should only check all for floating tips
                if (l !== undefined && l.options.actionMethod === 'floating' && !this.bootstrapped) {
                    l.form.getElements('input[type=checkbox][name*=id], input[type=checkbox][name=checkAll]')
                        .each(function (c) {
                            c.checked = true;
                        });
                }
            }
        }
        // Get correct list block
        if (!l.submit('list.delete')) {
            e.preventDefault();
        }
    };

    /**
     * Globally watch list edit links
     *
     * @param {event}  e relayed click event
     * @param {Node} target <a> link
     *
     * @since 3.0.7
     */
    Fabrik.watchEdit = function (e, target) {
        Fabrik.openSingleView('form', e, target);
    };

    /**
     * Globally watch list view links
     *
     * @param {event} e relayed click event
     * @param {Node} target <a> link
     *
     * @since 3.0.7
     */
    Fabrik.watchView = function (e, target) {
        Fabrik.openSingleView('details', e, target);
    };

    /**
     * Open a single details/form view
     * @param {string} view - details or form
     * @param {event} e relayed click event
     * @param {Node} target <a> link
     */
    Fabrik.openSingleView = function (view, e, target) {
        var url, loadMethod, a, title, rowId, row, winOpts,
            listRef = jQuery(target).data('list'),
            list = Fabrik.blocks[listRef];

        if (jQuery(target).data('isajax') !== 1) {
            return;
        }

        if (list) {
            if (!list.options.ajax_links) {
                return;
            }

            row = list.getActiveRow(e);
            if (!row || row.length === 0) {
                return;
            }
            list.setActive(row);
            rowId = row.prop('id').split('_').pop();
        }
        else {
            rowId = jQuery(target).data('rowid');
        }

        e.preventDefault();

        if (jQuery(e.target).prop('tagName') === 'A') {
            a = jQuery(e.target);
        } else {
            a = jQuery(e.target).find('a').length > 0 ? jQuery(e.target).find('a') : jQuery(e.target).closest('a');
        }
        url = a.prop('href');

        // if it's a custom link, don't add our junk 'n' stuff
        if (jQuery(target).data('iscustom') !== 1) {
            url += url.contains('?') ? '&tmpl=component&ajax=1' : '?tmpl=component&ajax=1';
            url += '&format=partial';
        }

        title = a.prop('title');
        loadMethod = a.data('loadmethod');
        if (loadMethod === undefined) {
            loadMethod = 'xhr';
        }

        // Only one edit window open at the same time.
        jQuery.each(Fabrik.Windows, function (key, win) {
            win.close();
        });

        winOpts = {
            modalId   : 'ajax_links',
            id        : listRef + '.' + rowId,
            title     : title,
            loadMethod: loadMethod,
            contentURL: url,
            onClose   : function () {
                var k = view + '_' + list.options.formid + '_' + rowId;
                try {
                    Fabrik.blocks[k].destroyElements();
                    Fabrik.blocks[k].formElements = null;
                    Fabrik.blocks[k] = null;
                    delete (Fabrik.blocks[k]);
                    var evnt = (view === 'details') ? 'fabrik.list.row.view.close' : 'fabrik.list.row.edit.close';
                    Fabrik.fireEvent(evnt, [listRef, rowId, k]);
                } catch (e) {
                    console.log(e);
                }
            }
        };

        if (list) {
            // Only set width/height if specified, otherwise default to window defaults
            if (list.options.popup_width !== '') {
                winOpts.width = list.options.popup_width;
            }
            if (list.options.popup_height !== '') {
                winOpts.height = list.options.popup_height;
            }
            winOpts.id = view === 'details' ? 'view.' + winOpts.id : 'add.' + winOpts.id;
            if (list.options.popup_offset_x !== null) {
                winOpts.offset_x = list.options.popup_offset_x;
            }
            if (list.options.popup_offset_y !== null) {
                winOpts.offset_y = list.options.popup_offset_y;
            }
        }
        Fabrik.getWindow(winOpts);
    };

    /**
     * Needed for the wickedpicker time picker, to figure out which element the picker is attached to when closing
     *
     * @param  element  the DOM element the time picker is attached to
     * @param  picker  the picker DOM element
     */
    Fabrik.timePickerClose = function (element, picker) {
        if (element) {
            // find the form
            var form = jQuery(element).closest('form');

            if (form.length > 0) {
                // get our block
                form = Fabrik.getBlock(form[0].id);

                if (form) {
                    // find element container
                    var container = jQuery(element).closest('.fabrikSubElementContainer');

                    if (container.length > 0) {
                        // get the element object
                        var el = form.formElements.get(container[0].id);

                        if (el) {
                            // fire the hideTime method
                            el.hideTime(element, picker);
                        }
                    }
                }
            }
        }
    };

    Fabrik.Array = {
        chunk: function (array, chunk) {
            var i, j, result = [];
            for (i = 0, j = array.length; i < j; i += chunk) {
                result.push(array.slice(i, i + chunk));
                // do whatever
            }
            return result;
        }
    };

    window.fireEvent('fabrik.loaded');
    window.Fabrik = Fabrik;
    return Fabrik;
});
