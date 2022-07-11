/**
 * Element List
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $H:true,unescape:true,Asset:true */

define(['jquery', 'fab/element'], function (jQuery, FbElement) {
    window.FbElementList = new Class({

        Extends: FbElement,

        type: 'text', // Sub element type

        initialize: function (element, options) {
            this.parent(element, options);
            this.addSubClickEvents();
            this._getSubElements();
            if (this.options.allowadd === true && this.options.editable !== false) {
                this.watchAddToggle();
                this.watchAdd();
            }
        },

        // Get the sub element which are the checkboxes themselves

        _getSubElements: function () {
            var element = this.getElement();
            if (!element) {
                this.subElements = [];
            } else {
                this.subElements = element.getElements('input[type=' + this.type + ']');
            }
            return this.subElements;
        },

        addSubClickEvents: function () {
            this._getSubElements().each(function (el) {
                el.addEvent('click', function (e) {
                    Fabrik.fireEvent('fabrik.element.click', [this, e]);
                });
            });
        },

        /**
         * Get the dom selector that events should be attached to
         * @returns {string}
         */
        eventDelegate: function () {
            return 'input[type=' + this.type + '][name^=' + this.options.fullName + ']';
        },

        /**
         * Convert event actions on a per element basis.
         * @param {string} action
         * @returns {string}
         */
        checkEventAction: function (action) {
            return action;
        },

        /**
         * Add an event
         * @param {string} action
         * @param {string|function} js
         */
        addNewEvent: function (action, js) {
            var r, delegate, uid, c;
            action = this.checkEventAction(action);
            if (action === 'load') {
                this.loadEvents.push(js);
                this.runLoadEvent(js);
            } else {
                c = this.form.form;

                // Added name^= for http://fabrikar.com/forums/showthread.php?t=30563 (js events to show hide multiple groups)
                delegate = this.eventDelegate();
                if (typeOf(this.form.events[action]) === 'null') {
                    this.form.events[action] = {};
                }

                // Could be added via a custom js file.
                if (typeof(js) === 'function') {
                    uid = Math.random(100) * 1000;
                } else {
                    r = new RegExp('[^a-z|0-9]', 'gi');
                    uid = delegate + js.replace(r, '');
                }
                if (typeOf(this.form.events[action][uid]) === 'null') {
                    this.form.events[action][uid] = true;

                    jQuery(c).on(action, delegate, function (event) {
                        // Changed from preventDefault() to stopPropagation() as the former prevents radios from selecting
                        event.stopPropagation();
                        // Don't use the usual jQuery this, as we need to bind the plugin as 'this' to the event.
                        var target = jQuery(event.currentTarget), elid, that, subEls;
                        if (target.prop('tagName') === 'LABEL') {
                            target = target.find('input');
                        }
                        // As we are delegating the event, and reference to 'this' in the js will refer to the first element
                        // When in a repeat group we want to replace that with a reference to the current element.
                        elid = target.closest('.fabrikSubElementContainer').prop('id');
                        that = this.form.formElements[elid];
                        subEls = that._getSubElements();
                        if (target.length > 0 && subEls.contains(target[0])) {

                            // Replace this with that so that the js code runs on the correct element
                            if (typeof(js) !== 'function') {
                                js = js.replace(/\bthis\b/g, 'that');
                                eval(js);
                            } else {
                                js.delay(0);
                            }
                        }
                    }.bind(this));
                }
            }
        },

        checkEnter: function (e) {
            if (e.key === 'enter') {
                e.stop();
                this.startAddNewOption();
            }
        },

        startAddNewOption: function () {
            var c = this.getContainer(), val;
            var l = c.getElement('input[name=addPicklistLabel]');
            var v = c.getElement('input[name=addPicklistValue]');
            var label = l.value;
            if (v) {
                val = v.value;
            } else {
                val = label;
            }
            if (val === '' || label === '') {
                window.alert(Joomla.JText._('PLG_ELEMENT_CHECKBOX_ENTER_VALUE_LABEL'));
            }
            else {
                var r = this.subElements.getLast().findClassUp('fabrikgrid_' + this.type).clone();
                var i = r.getElement('input');
                i.value = val;
                i.checked = 'checked';
                if (this.type === 'checkbox') {

                    // Remove the last [*] from the checkbox sub option name (seems only these use incremental []'s)
                    var name = i.name.replace(/^(.*)\[.*\](.*?)$/, '$1$2');
                    i.name = name + '[' + (this.subElements.length) + ']';
                }
                r.getElement('.' + this.type + ' span').set('text', label);
                r.inject(this.subElements.getLast().findClassUp('fabrikgrid_' + this.type), 'after');

                var index = 0;
                if (this.type === 'radio') {
                    index = this.subElements.length;
                }
                var is = $$('input[name=' + i.name + ']');
                document.id(this.form.form).fireEvent('change', {target: is[index]});

                this._getSubElements();
                if (v) {
                    v.value = '';
                }
                l.value = '';
                this.addNewOption(val, label);
                if (this.mySlider) {
                    this.mySlider.toggle();
                }
            }
        },

        watchAdd: function () {
            if (this.options.allowadd === true && this.options.editable !== false) {
                var c = this.getContainer();
                c.getElements('input[name=addPicklistLabel], input[name=addPicklistValue]').addEvent('keypress', function (e) {
                    this.checkEnter(e);
                }.bind(this));
                c.getElement('input[type=button]').addEvent('click', function (e) {
                    e.stop();
                    this.startAddNewOption();
                }.bind(this));
                document.addEvent('keypress', function (e) {
                    if (e.key === 'esc' && this.mySlider) {
                        this.mySlider.slideOut();
                    }
                }.bind(this));
            }
        },

        /**
         * Get focus event
         * @returns {string}
         */
        getFocusEvent: function () {
            return 'click';
        }

    });

    return window.FbElementList;
});
