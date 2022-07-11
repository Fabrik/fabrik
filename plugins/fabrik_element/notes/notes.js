/**
 * Notes Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/**
 watch another element for changes to its value, and send an ajax call to update
 this elements values
 */

define(['jquery', 'fab/element'], function (jQuery, FbElement) {
    window.FbNotes = new Class({

        options: {
			'rowid': 0,
			'primaryKey' : 0,
			'id'   : 0
        },

        Extends   : FbElement,
        initialize: function (element, options) {
            this.setPlugin('notes');
            this.parent(element, options);
            this.setUp();
        },

        setUp: function () {
            if (this.options.primaryKey > 0) {
                this.element.getElement('.button').addEvent('click', function (e) {
                    this.submit(e);
                }.bind(this));
                this.field = this.element.getElement('.fabrikinput');
                var msg = this.element.getElement('div');
                msg.makeResizable({
                    'modifiers': {x: false, y: 'height'},
                    'handle'   : this.element.getElement('.noteHandle')
                });
                this.element.getElement('.noteHandle').setStyle('cursor', 'all-scroll');
            }
        },

        submit: function (e) {
            e.stop();
            var label = this.field.get('value');
            if (label !== '') {
                Fabrik.loader.start(this.element);
                var data = {
                    'option'    : 'com_fabrik',
                    'format'    : 'raw',
                    'task'      : 'plugin.pluginAjax',
                    'plugin'    : 'notes',
                    'method'    : 'ajax_addNote',
                    'element_id': this.options.id,
                    'v'         : label,
                    'rowid'  : this.options.rowid,
                    'joinPkVal'  : this.options.joinPkVal,
                    'primaryKey' : this.options.primaryKey,
					'formid'    : this.form.id
                };
                this.myAjax = new Request.JSON({
                    'url'      : '',
                    'data'     : data,
                    onSuccess  : function (json) {
                        Fabrik.loader.stop(this.element);
                        if (this.options.j3) {
                            var rows = this.element.getElement('div');
                            var row = new Element('div', {'class': 'row-fluid'});
                            var inner_row = new Element('div', {'class': 'span12'}).set('html', json.label).inject(row);
                            inner_row.inject(rows);
                        }
                        else {
                            var ul = this.element.getElement('ul');
                            var c = 'oddRow' + ul.getElements('li').length % 2;
                            new Element('li', {'class': c}).set('html', json.label).inject(ul);
                        }
                        this.field.value = '';
                    }.bind(this),
                    'onError'  : function (text) {
                        Fabrik.loader.stop(this.element);
                        window.alert(text);
                    },
                    'onFailure': function (xhr) {
                        Fabrik.loader.stop(this.element);
                        window.alert('ajax failed');
                    },
                    'onCancel' : function () {
                        Fabrik.loader.stop(this.element);
                    }
                }).send();

            }
        },

        cloned: function (c) {
            Fabrik.fireEvent('fabrik.notes.update', this);
            this.parent(c);
        }
    });

    return window.FbNotes;
});