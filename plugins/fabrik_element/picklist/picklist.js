/**
 * PickList Element
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'fab/element'], function (jQuery, FbElement) {
    window.FbPicklist = new Class({
        Extends   : FbElement,
        initialize: function (element, options) {
            this.setPlugin('fabrikpicklist');
            this.parent(element, options);
            if (this.options.allowadd === true) {
                this.watchAddToggle();
                this.watchAdd();
            }
            this.makeSortable();
        },

        /**
         * Ini the sortable object
         */
        makeSortable: function () {
            if (this.options.editable) {
                var c = this.getContainer();
                var from = c.getElement('.fromList'),
                    to = c.getElement('.toList'),
                    dropcolour = from.getStyle('background-color'),
                    that = this;
                this.sortable = new Sortables([from, to], {
                    clone     : true,
                    revert    : true,
                    opacity   : 0.7,
                    hovercolor: '#ffddff',
                    onComplete: function (element) {
                        this.setData();
                        this.showNotices(element);
                        that.fadeOut(from, dropcolour);
                        that.fadeOut(to, dropcolour);
                    }.bind(this),
                    onSort    : function (element, clone) {
                        this.showNotices(element, clone);

                    }.bind(this),


                    onStart: function (element, clone) {
                        this.drag.addEvent('onEnter', function (element, droppable) {
                            if (this.lists.contains(droppable)) {
                                that.fadeOut(droppable, this.options.hovercolor);
                                if (this.lists.contains(this.drag.overed)) {
                                    this.drag.overed.addEvent('mouseleave', function () {
                                        that.fadeOut(from, dropcolour);
                                        that.fadeOut(to, dropcolour);
                                    }.bind(this));
                                }
                            }
                        }.bind(this));
                    }
                });
                var notices = [from.getElement('li.emptypicklist'), to.getElement('li.emptypicklist')];
                this.sortable.removeItems(notices);
                this.showNotices();
            }
        },

        fadeOut: function (droppable, colour) {
            var hoverFx = new Fx.Tween(droppable, {
                wait    : false,
                duration: 600
            });
            hoverFx.start('background-color', colour);
        },

        /**
         * Show empty notices
         *
         * @param  DOMNode  element  Li being dragged
         *
         */
        showNotices: function (element, clone) {
            if (element) {
                // Get list
                element = element.getParent('ul');
            }
            var c = this.getContainer(),
                limit, to, i;
            var lists = [c.getElement('.fromList'), c.getElement('.toList')];
            for (i = 0; i < lists.length; i++) {
                to = lists[i];
                limit = (to === element || typeOf(element) === 'null') ? 1 : 2;
                var notice = to.getElement('li.emptypicklist');
                var lis = to.getElements('li');
                lis.length > limit ? notice.hide() : notice.show();
            }
        },

        setData: function () {
            var c = this.getContainer(),
                to = c.getElement('.toList'),
                lis = to.getElements('li[class!=emptypicklist]'),
                v = lis.map(
                    function (item, index) {
                        return item.id
                            .replace(this.options.element + '_value_', '');
                    }.bind(this));
            this.element.value = JSON.encode(v);
        },

        watchAdd: function () {
            var id = this.element.id,
                c = this.getContainer(),
                to = c.getElement('.toList'),
                btn = c.getElement('input[type=button]');

            if (typeOf(btn) === 'null') {
                return;
            }
            btn.addEvent(
                'click',
                function (e) {
                    var val;
                    value = c.getElement('input[name=addPicklistValue]'),
                        labelEl = c.getElement('input[name=addPicklistLabel]'),
                        label = labelEl.get('value');
                    if (typeOf(value) !== 'null') {
                        val = value.value;
                    } else {
                        val = label;
                    }
                    if (val === '' || label === '') {
                        alert(Joomla.JText._('PLG_ELEMENT_PICKLIST_ENTER_VALUE_LABEL'));
                    } else {

                        var li = new Element('li', {
                            'class': 'picklist',
                            'id'   : this.element.id + '_value_' + val
                        }).set('text', label);

                        to.adopt(li);
                        this.sortable.addItems(li);

                        e.stop();
                        if (typeOf(value) === 'element') {
                            value.value = '';
                        }
                        labelEl.value = '';
                        this.setData();
                        this.addNewOption(val, label);
                        this.showNotices();
                    }
                }.bind(this));
        },

        unclonableProperties: function () {
            return ['form', 'sortable'];
        },

        watchAddToggle: function () {
            var c = this.getContainer();
            var d = c.getElement('div.addoption');
            var a = c.getElement('.toggle-addoption');
            if (this.mySlider) {
                // Copied in repeating group so need to remove old slider html first
                var clone = d.clone();
                var fe = c.getElement('.fabrikElement');
                d.getParent().destroy();
                fe.adopt(clone);
                d = c.getElement('div.addoption');
                d.setStyle('margin', 0);
            }
            this.mySlider = new Fx.Slide(d, {
                duration: 500
            });
            this.mySlider.hide();
            a.addEvent('click', function (e) {
                e.stop();
                this.mySlider.toggle();
            }.bind(this));
        },

        cloned: function (c) {
            delete this.sortable;
            if (this.options.allowadd === true) {
                this.watchAddToggle();
                this.watchAdd();
            }
            this.makeSortable();
            this.parent(c);
        }
    });

    return window.FbPicklist;
});