/**
 * Admin Modal Repeat Editor
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

'use strict';

var FabrikModalRepeat = new Class({

    options: {
        j3: true
    },

    initialize: function (el, names, field, opts) {
        this.names = names;
        this.field = field;
        this.content = false;
        this.setup = false;
        this.elid = el;
        this.win = {};
        this.el = {};
        this.field = {};
        this.options = Object.append(this.options, opts);

        // If the parent field is inserted via js then we delay the loading until the html is present
        if (!this.ready()) {
            this.timer = this.testReady.periodical(500, this);
        } else {
            this.setUp();
        }
    },

    ready: function () {
        return typeOf(document.id(this.elid)) === 'null' ? false : true;
    },


    testReady: function () {
        if (!this.ready()) {
            return;
        }
        if (this.timer) {
            clearInterval(this.timer);
        }
        this.setUp();
    },

    setUp: function () {
        this.button = document.id(this.elid + '_button');
        this.mask = new Mask(document.body, {style: {'background-color': '#000', 'opacity': 0.4, 'z-index': 9998}});
        document.addEvent('click:relay(*[data-modal=' + this.elid + '])', function (e, target) {
            e.preventDefault();
            var tbl,
            // Correct when in repeating group
                id = target.getNext('input').id,
                c = target.getParent('li');
            this.field[id] = target.getNext('input');
            if (!c) {
                // Joomla 3
                c = target.getParent('div.control-group');
            }
            this.origContainer = c;
            tbl = c.getElement('table');
            if (typeOf(tbl) !== 'null') {
                this.el[id] = tbl;
            }
            this.openWindow(id);
        }.bind(this));
    },

    openWindow: function (target) {
        var makeWin = false;
        if (!this.win[target]) {
            makeWin = true;
            this.makeTarget(target);
        }
        this.el[target].inject(this.win[target], 'top');
        this.el[target].show();

        if (!this.win[target] || makeWin) {
            this.makeWin(target);
        }
        this.win[target].show();
        this.win[target].position();
        this.resizeWin(true, target);
        this.win[target].position();
        this.mask.show();
    },

    makeTarget: function (target) {
        this.win[target] = new Element('div', {
            'data-modal-content': target,
            'styles'            : {
                'padding'         : '5px',
                'background-color': '#fff',
                'display'         : 'none',
                'z-index'         : 9999
            }
        }).inject(document.body);

    },

    makeWin: function (target) {
        var close = new Element('button.btn.button.btn-primary').set('text', 'close');
        close.addEvent('click', function (e) {
            e.stop();
            this.store(target);
            this.el[target].hide();
            this.el[target].inject(this.origContainer);
            this.close();
        }.bind(this));
        var controls = new Element('div.controls.form-actions', {
            'styles': {
                'text-align'   : 'right',
                'margin-bottom': 0
            }
        }).adopt(close);

        this.win[target].adopt(controls);
        this.win[target].position();
        this.content = this.el[target];
        this.build(target);
        this.watchButtons(this.win[target], target);
    },

    resizeWin: function (setup, target) {
        Object.each(this.win, function (win, key) {
            var size = this.el[key].getDimensions(true),
                wsize = win.getDimensions(true);
            win.setStyles({'width': size.x + 'px'});
            if (typeof(Fabrik) !== 'undefined' && !Fabrik.bootstrapped) {
                var y = setup ? wsize.y : size.y + 30;
                win.setStyle('height', y + 'px');
            }
        }.bind(this));
    },

    close: function () {
        Object.each(this.win, function (win, key) {
            win.hide();
        });
        this.mask.hide();
    },

    _getRadioValues: function (target) {
        var radiovals = [], sel;
        this.getTrs(target).each(function (tr) {
            var v = (sel = tr.getElement('input[type=radio]:checked')) ? sel.get('value') : '';
            radiovals.push(v);
        });
        return radiovals;
    },

    _setRadioValues: function (radiovals, target) {
        // Reapply radio button selections
        var r;
        this.getTrs(target).each(function (tr, i) {
            if (r = tr.getElement('input[type=radio][value=' + radiovals[i] + ']')) {
                r.checked = 'checked';
            }
        });
    },

    /**
     * Add a new row of fields
     * @param target
     * @param srouce
     */
    addRow: function (target, source) {
        // Store radio button selections
        var radiovals = this._getRadioValues(target),
            body = source.getParent('table').getElement('tbody'),
            clone = this.tmpl.clone(true, true);
        clone.inject(body);
        this.stripe(target);

        this.fixUniqueAttributes(source, clone);

        // Reapply values as renaming radio buttons
        this._setRadioValues(radiovals, target);
        this.resetChosen(clone);
        this.resizeWin(false, target);
    },

    /**
     * Ensure that a new row has unique ids, names and label for properites
     *
     * @param source
     * @param row
     */
    fixUniqueAttributes: function (source, row) {
        var rowCount = source.getParent('table').getElements('tr').length - 1;

        row.getElements('*[name]').each(function (node) {
            node.name += '-' + rowCount;
        });
        row.getElements('*[id]').each(function (node) {
            node.id += '-' + rowCount;
        });
        row.getElements('label[for]').each(function (node) {
            node.label += '-' + rowCount;
        });
    },

    watchButtons: function (win, target) {
        var tr;
        win.addEvent('click:relay(a.add)', function (e) {
            if (tr = this.findTr(e)) {
                this.addRow(target, tr);
            }
            win.position();
            e.stop();
        }.bind(this));
        win.addEvent('click:relay(a.remove)', function (e) {

            if (tr = this.findTr(e)) {
                tr.dispose();
            }
            this.resizeWin(false, target);
            win.position();
            e.stop();
        }.bind(this));
    },

    resetChosen: function (clone) {
        if (!this.options.j3) {
            return;
        }
        if (jQuery && typeOf(jQuery('select').chosen) !== 'null') {

            // Chosen reset
            clone.getElements('select').removeClass('chzn-done').show();

            // Assign random id
            clone.getElements('select').each(function (c) {
                c.id = c.id + '_' + (Math.random() * 10000000).toInt();
            });
            clone.getElements('.chzn-container').destroy();

            jQuery(clone).find('select').chosen({
                disable_search_threshold: 10,
                allow_single_deselect   : true,
                width: '265px'
            });
        }
    },

    getTrs: function (target) {
        return this.win[target].getElement('tbody').getElements('tr');
    },

    /**
     * Stripe each of the list rows
     * @param target
     */
    stripe: function (target) {
        var trs = this.getTrs(target);
        for (var i = 0; i < trs.length; i++) {
            trs[i].removeClass('row1').removeClass('row0');
            trs[i].addClass('row' + i % 2);
        }
    },

    build: function (target) {
        if (!this.win[target]) {
            this.makeWin(target);
        }

        var a = JSON.decode(this.field[target].get('value'));
        if (typeOf(a) === 'null') {
            a = {};
        }
        var tr = this.win[target].getElement('tbody').getElement('tr'),
            keys = Object.keys(a),
            newrow = keys.length === 0 || a[keys[0]].length === 0 ? true : false,
            rowcount = newrow ? 1 : a[keys[0]].length;

        // Build the rows from the json object
        for (var i = 1; i < rowcount; i++) {
            var clone = tr.clone();
            this.fixUniqueAttributes(tr, clone);
            clone.inject(tr, 'after');
            this.resetChosen(clone);
        }
        this.stripe(target);
        var trs = this.getTrs(target);

        // Populate the cloned fields with the json values
        for (i = 0; i < rowcount; i++) {
            keys.each(function (k) {
                trs[i].getElements('*[name*=' + k + ']').each(function (f) {
                    if (f.get('type') === 'radio') {
                        if (f.value === a[k][i]) {
                            f.checked = true;
                        }
                    } else {
                        // Works for input,select and textareas
                        f.value = a[k][i];
                        if (f.get('tag') === 'select' && typeof jQuery !== 'undefined') {

                            // Manually fire chosen dropdown update
                            jQuery(f).trigger('liszt:updated');
                        }
                    }
                });
            });
        }
        this.tmpl = tr;
        if (newrow) {
            tr.dispose();
        }

    },

    findTr: function (e) {
        var tr = e.target.getParents().filter(function (p) {
            return p.get('tag') === 'tr';
        });
        return (tr.length === 0) ? false : tr[0];
    },

    store: function (target) {
        var c = this.content;
        c = this.el[target];

        // Get the current values
        var json = {};
        for (var i = 0; i < this.names.length; i++) {
            var n = this.names[i];
            var fields = c.getElements('*[name*=' + n + ']');
            json[n] = [];
            fields.each(function (field) {
                if (field.get('type') === 'radio') {
                    if (field.get('checked') === true) {
                        json[n].push(field.get('value'));
                    }
                } else {
                    json[n].push(field.get('value'));
                }
            }.bind(this));
        }
        // Store them in the parent field.
        this.field[target].value = JSON.encode(json);
        return true;
    }

});