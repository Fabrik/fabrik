/**
 * IP Element
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'fab/element'], function (jQuery, FbElement) {
    window.FbTotal = new Class({
        Extends   : FbElement,
        observeGroupIds: [],
        observeElementGroups: [],

        initialize: function (element, options) {
            this.setPlugin('FbTotal');
            this.parent(element, options);
            this.observeGroupIds = [];
        },

        attachedToForm: function () {
            this.options.observe.each(function (o) {
                this.addObserveEvent(o);
            }.bind(this));

            if (this.options.totalOnLoad) {
                this.calc();
            }

            /**
             * CDD which have subelements (radio, checkbox) will destroy their subelements
             * and recreate them on update, so we need to recreate the observe events on a CDD update
             */
            Fabrik.addEvent('fabrik.cdd.update', function (el) {
                if (el.hasSubElements()) {
                    if (jQuery.inArray(el.baseElementId, this.options.observe) !== -1) {
                        this.addObserveEvent(el.baseElementId);
                    }
                }
            }.bind(this));

            Fabrik.addEvent('fabrik.form.group.duplicate.end', function(form, event, groupId) {
                if (jQuery.inArray(groupId, this.observeGroupIds) !== -1) {
                    this.calc();
                }
            }.bind(this));

            Fabrik.addEvent('fabrik.form.group.delete.end', function(form, event, groupId) {
                if (jQuery.inArray(groupId, this.observeGroupIds) !== -1) {
                    this.calc();
                }
            }.bind(this));

            this.parent();
        },

        addObserveEvent: function (o) {
            var o2, v2;
            if (o === '') {
                return;
            }
            if (this.form.formElements[o]) {
                this.form.formElements[o].addNewEventAux(this.form.formElements[o].getChangeEvent(), function (e) {
                    this.calc(e);
                }.bind(this));
            }
            else {
                if (this.options.canRepeat) {
                    o2 = o + '_' + this.options.repeatCounter;
                    if (this.form.formElements[o2]) {
                        this.form.formElements[o2].addNewEventAux(this.form.formElements[o2].getChangeEvent(),
                            function (e) {
                                this.calc(e);
                            }.bind(this));
                    }
                }
                else {
                    this.form.repeatGroupMarkers.each(function (v, k) {
                        o2 = '';
                        for (v2 = 0; v2 < v; v2++) {
                            o2 = o + '_' + v2;
                            if (this.form.formElements[o2]) {
                                this.form.formElements[o2].addNewEvent(
                                    this.form.formElements[o2].getChangeEvent(),
                                    function (e) {
                                        this.calc(e);
                                    }.bind(this)
                                );
                                if (jQuery.inArray(k, this.observeGroupIds) === -1) {
                                    this.observeGroupIds.push(k);
                                    this.observeElementGroups[o] = k;
                                }
                            }
                        }
                    }.bind(this));
                }
            }
        },

        calc: function () {
            var self = this;
            var total = parseFloat(this.options.startValue);

            switch (this.options.method) {
                case 'sum_repeat':
                    jQuery.each(this.options.observe, function (i, o) {
                        var numRepeats = self.form.repeatGroupMarkers[self.observeElementGroups[o]];

                        for (var repeat = 0; repeat < numRepeats; repeat++) {
                            var el = self.form.formElements.get(o + '_' + repeat);
                            if (el) {
                                var v = el.getValue();

                                if (jQuery.isNumeric(v)) {
                                    var op = self.options.operands[i];
                                    v = parseFloat(v);
                                    switch (op) {
                                        case 'add':
                                            total += v;
                                            break;
                                        case 'subtract':
                                            total -= v;
                                            break;
                                        case 'multiply':
                                            total *= v;
                                            break;
                                        case 'divide':
                                            if (v !== 0) {
                                                total = total / v;
                                            }
                                            break;
                                    }
                                }
                            }
                        }
                    });
                    break;
                case 'sum_multiple':
                    jQuery.each(this.options.observe, function (i, o) {
                        if (self.options.canRepeat) {
                            o = o + '_' + self.options.repeatCounter;
                        }

                        var el = self.form.formElements.get(o);

                        if (el) {
                            var v = el.getValue();

                            if (jQuery.isNumeric(v)) {
                                var op = self.options.operands[i];
                                v = parseFloat(v);
                                switch (op) {
                                    case 'add':
                                        total += v;
                                        break;
                                    case 'subtract':
                                        total -= v;
                                        break;
                                    case 'multiply':
                                        total *= v;
                                        break;
                                    case 'divide':
                                        if (v !== 0) {
                                            total = total / v;
                                        }
                                        break;
                                }

                            }
                        }
                    });
                    break;
            }

            this.update(total.toFixed(self.options.fixed));
        }

    });

    return window.FbTotal;
});