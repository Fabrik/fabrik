/**
 * Bootstrap Auto-Complete
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/*jshint mootools: true */
/*global fconsole:true, Joomla:true,  */

define(['jquery', 'fab/encoder', 'fab/fabrik', 'lib/debounce/jquery.ba-throttle-debounce'],
    function (jQuery, Encoder, Fabrik, debounce) {
    var AutoComplete = new Class({

        Implements: [Options, Events],

        options: {
            menuclass              : 'auto-complete-container dropdown',
            classes                : {
                'ul': 'dropdown-menu',
                'li': 'result'
            },
            url                    : 'index.php',
            max                    : 10,
            onSelection            : Class.empty,
            autoLoadSingleResult   : true,
            minTriggerChars        : 1,
            debounceDelay          : 500,
            storeMatchedResultsOnly: false // Only store a value if selected from picklist
        },

        initialize: function (element, options) {
            // not sure why we use domready, but causes issues in popups on second+ open, doesn't fire
            //window.addEvent('domready', function () {
                this.matchedResult = false;
                this.setOptions(options);
                element = element.replace('-auto-complete', '');
                this.options.labelelement = typeOf(document.id(element + '-auto-complete')) === 'null' ?
                     document.getElement(element + '-auto-complete') : document.id(element + '-auto-complete');
                this.cache = {};
                this.selected = -1;
                this.mouseinsde = false;
                document.addEvent('keydown', function (e) {
                    this.doWatchKeys(e);
                }.bind(this));
                this.element = typeOf(document.id(element)) === 'null' ?
                    document.getElement(element) : document.id(element);
                this.buildMenu();
                if (!this.getInputElement()) {
                    fconsole('autocomplete didn\'t find input element');
                    return;
                }
                this.getInputElement().setProperty('autocomplete', 'off');

                /*
                 this.getInputElement().addEvent('keyup', function (e) {
                 this.search(e);
                 }.bind(this));
                 */

                /**
                 * Using a 3rd party jQuery lib to 'debounce' the input, so the search doesn't fire until
                 * the user has stopped typing for more than X ms.  Don't use on() here, use bind(), otherwise
                 * we get multiple events when form popups are opened multiple times.
                 */
                var self = this;
                jQuery(this.getInputElement()).bind('keyup', debounce(this.options.debounceDelay, function (e) {
                    self.search(e);
                }));

                this.getInputElement().addEvent('blur', function (e) {
                    if (this.options.storeMatchedResultsOnly) {
                        if (!this.matchedResult) {
                            if (typeof(this.data) === 'undefined' ||
                                !(this.data.length === 1 && this.options.autoLoadSingleResult)) {
                                this.element.value = '';
                            }
                        }
                    }
                }.bind(this));
            //}.bind(this));
        },

        search: function (e) {
            var msg;
            /**
             * NOTE that because we use a jQuery event to trigger this, e is a jQuery event, so keyCode
             * instead of code, and e.preventDefault() instead of e.stop()
             */
            if (!this.isMinTriggerlength()) {
                return;
            }
            if (e.keyCode === 'tab' || e.keyCode === 'enter') {
                e.preventDefault();
                this.closeMenu();
                if (this.ajax) {
                    this.ajax.cancel();
                }
                this.element.fireEvent('change', new Event.Mock(this.element, 'change'), 500);
                return;
            }
            this.matchedResult = false;
            var v = this.getInputElement().get('value');
            if (v === '') {
                this.element.value = '';
            }
            if (v !== this.searchText && v !== '') {
                if (this.options.storeMatchedResultsOnly === false) {
                    this.element.value = v;
                }
                this.positionMenu();
                if (this.cache[v]) {
                    if (this.populateMenu(this.cache[v])) {
                        this.openMenu();
                    }
                } else {
                    if (this.ajax) {
                        this.closeMenu();
                        this.ajax.cancel();
                    }
                    this.ajax = new Request({
                        url      : this.options.url,
                        data     : {
                            value: v
                        },
                        onRequest: function () {
                            Fabrik.loader.start(this.getInputElement());
                        }.bind(this),
                        onCancel : function () {
                            Fabrik.loader.stop(this.getInputElement());
                            this.ajax = null;
                        }.bind(this),
                        onSuccess: function (e) {
                            Fabrik.loader.stop(this.getInputElement());
                            this.ajax = null;
                            if (typeOf(e) === 'null') {
                                fconsole('Fabrik autocomplete: Ajax response empty');
                                var elModel = Fabrik.getBlock(this.options.formRef).formElements.get(this.element.id);
                                msg = Joomla.JText._('COM_FABRIK_AUTOCOMPLETE_AJAX_ERROR');
                                elModel.setErrorMessage(msg, 'fabrikError', true);
                                return;
                            }
                            this.completeAjax(e, v);
                        }.bind(this),
                        onFailure: function (xhr) {
                            Fabrik.loader.stop(this.getInputElement());
                            this.ajax = null;
                            fconsole('Fabrik autocomplete: Ajax failure: Code ' + xhr.status + ': ' + xhr.statusText);
                            var elModel = Fabrik.getBlock(this.options.formRef).formElements.get(this.element.id);
                            msg  = Joomla.JText._('COM_FABRIK_AUTOCOMPLETE_AJAX_ERROR');
                            elModel.setErrorMessage(msg, 'fabrikError', true);
                        }.bind(this)
                    }).send();
                }
            }
            this.searchText = v;
        },

        completeAjax: function (r, v) {
            r = JSON.decode(r);
            this.cache[v] = r;
            if (this.populateMenu(r)) {
                this.openMenu();
            }
        },

        buildMenu: function () {
            this.menu = new Element('ul.dropdown-menu', {'role': 'menu', 'styles': {'z-index': 1056}});
            this.menu.inject(document.body);
            this.menu.addEvent('mouseenter', function () {
                this.mouseinsde = true;
            }.bind(this));
            this.menu.addEvent('mouseleave', function () {
                this.mouseinsde = false;
            }.bind(this));
            this.menu.addEvent('click:relay(a)', function (e, target) {
                this.makeSelection(e, target);
            }.bind(this));
        },

        getInputElement: function () {
            return this.options.labelelement ? this.options.labelelement : this.element;
        },

        positionMenu: function () {
            var coords = this.getInputElement().getCoordinates();
            // var pos = this.getInputElement().getPosition();
            this.menu.setStyles({'left': coords.left, 'top': (coords.top + coords.height) - 1, 'width': coords.width});
        },

        populateMenu: function (data) {
            // $$$ hugh - added decoding of things like &amp; in the text strings
            var li, a, form, elModel, blurEvent, pair;
            data.map(function (item, index) {
                item.text = Encoder.htmlDecode(item.text);
                return item;
            });
            this.data = data;
            var max = this.getListMax(),
                ul = this.menu;
            ul.empty();
            if (data.length === 1 && this.options.autoLoadSingleResult) {
                this.element.value = data[0].value;
                this.getInputElement().value = data[0].text;
                // $$$ Paul - The selection event is for text being selected in an input field not for a
                // link being selected
                this.closeMenu();
                this.fireEvent('selection', [this, this.element.value]);
                // $$$ hugh - need to fire change event, in case it's something like a join element
                // with a CDD that watches it.
                form = Fabrik.getBlock(this.options.formRef);
                if (form !== false) {
                    elModel = form.formElements.get(this.element.id);
                    blurEvent = elModel.getBlurEvent();
                    this.element.fireEvent(blurEvent, new Event.Mock(this.element, blurEvent), 700);
                }

                // $$$ hugh - fire a Fabrik event, just for good luck.  :)
                Fabrik.fireEvent('fabrik.autocomplete.selected', [this, this.element.value]);
                return false;
            }
            if (data.length === 0) {
                li = new Element('li').adopt(new Element('div.alert.alert-info')
                    .adopt(new Element('i').set('text', Joomla.JText._('COM_FABRIK_NO_RECORDS'))));
                li.inject(ul);
            }
            for (var i = 0; i < max; i++) {
                pair = data[i];
                a = new Element('a', {'href': '#', 'data-value': pair.value, tabindex: '-1'}).set('text', pair.text);
                li = new Element('li').adopt(a);
                li.inject(ul);
            }
            if (data.length > this.options.max) {
                new Element('li').set('text', '....').inject(ul);
            }
            return true;
        },

        makeSelection: function (e, li) {
            e.preventDefault();
            // $$$ tom - make sure an item was selected before operating on it.
            if (typeOf(li) !== 'null') {
                this.getInputElement().value = li.get('text');
                this.element.value = li.getProperty('data-value');
                this.closeMenu();
                this.fireEvent('selection', [this, this.element.value]);
                // $$$ hugh - need to fire change event, in case it's something like a join element
                // with a CDD that watches it.
                this.element.fireEvent('change', new Event.Mock(this.element, 'change'), 700);
                this.element.fireEvent('blur', new Event.Mock(this.element, 'blur'), 700);
                // $$$ hugh - fire a Fabrik event, just for good luck.  :)
                Fabrik.fireEvent('fabrik.autocomplete.selected', [this, this.element.value]);
            } else {
                /**
                 * $$$ Paul - The Fabrik event below makes NO sense.
                 * This is a code error condition not an event because typeOf(li) should never be null
                 **/
                    //  $$$ tom - fire a notselected event to let developer take appropriate actions.
                Fabrik.fireEvent('fabrik.autocomplete.notselected', [this, this.element.value]);
            }
        },

        closeMenu: function () {
            if (this.shown) {
                this.shown = false;
                // some templates seem to need a jQuery hide, something to do with webkit
                jQuery(this.menu).hide();
                this.selected = -1;
                document.removeEvent('click', this.doCloseEvent);
            }
        },

        openMenu: function () {
            if (!this.shown) {
                if (this.isMinTriggerlength()) {
                    this.menu.show();
                    this.shown = true;
                    this.doCloseEvent = this.doTestMenuClose.bind(this);
                    document.addEvent('click', this.doCloseEvent);
                    this.selected = 0;
                    this.highlight();
                }
            }
        },

        isMinTriggerlength: function () {
            var v = this.getInputElement().get('value');
            return v.length >= this.options.minTriggerChars;
        },

        doTestMenuClose: function () {
            if (!this.mouseinsde) {
                this.closeMenu();
            }
        },

        getListMax: function () {
            if (typeOf(this.data) === 'null') {
                return 0;
            }
            return this.data.length > this.options.max ? this.options.max : this.data.length;
        },

        doWatchKeys: function (e) {
            if (document.activeElement !== this.getInputElement()) {
                return;
            }
            var max = this.getListMax(), selected, selectEvnt;
            if (!this.shown) {
                // Stop enter from submitting when in in-line edit form.
                if (e.code.toInt() === 13) {
                    e.stop();
                }
                if (e.code.toInt() === 40) {
                    this.openMenu();
                }
            } else {
                if (!this.isMinTriggerlength()) {
                    e.stop();
                    this.closeMenu();
                }
                else {
                    if (e.key === 'enter' || e.key === 'tab') {
                        window.fireEvent('blur');
                    }
                    switch (e.code) {
                        case 40://down
                            if (!this.shown) {
                                this.openMenu();
                            }
                            if (this.selected + 1 <= max) {
                                this.selected++;
                            }
                            this.highlight();
                            e.stop();
                            break;
                        case 38: //up
                            if (this.selected - 1 >= -1) {
                                this.selected--;
                                this.highlight();
                            }
                            e.stop();
                            break;
                        case 13://enter
                        case 9://tab
                            e.stop();
                            selected = this.getSelected();
                            if (selected) {
                                selectEvnt = new Event.Mock(selected, 'click');
                                this.makeSelection(selectEvnt, selected);
                                this.closeMenu();
                            }
                            break;
                        case 27://escape
                            e.stop();
                            this.closeMenu();
                            break;
                    }
                }
            }
        },

        /**
         * Get the selected <a> tag
         *
         * @return  DOM Node <a>
         */
        getSelected: function () {
            var all = this.menu.getElements('li'),
                lis = all.filter(function (li, i) {
                    return i === this.selected;
                }.bind(this));

            if (typeOf(lis[0]) === 'element') {
                return lis[0].getElement('a');
            } else if (all.length > 0) {
                // Can occur if autocomplete generated but not clicked on / keyed into.
                return all[0].getElement('a');
            }

            return false;
        },

        highlight: function () {
            this.matchedResult = true;
            this.menu.getElements('li').each(function (li, i) {
                if (i === this.selected) {
                    li.addClass('selected').addClass('active');
                } else {
                    li.removeClass('selected').removeClass('active');
                }
            }.bind(this));
        }

    });

    return AutoComplete;
});