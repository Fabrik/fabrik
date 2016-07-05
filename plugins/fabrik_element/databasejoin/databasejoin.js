/**
 * Database Join Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'fab/element', 'fab/encoder', 'fab/fabrik', 'fab/autocomplete-bootstrap'],
    function (jQuery, FbElement, Encoder, Fabrik, AutoComplete) {
    window.FbDatabasejoin = new Class({
        Extends: FbElement,

        options: {
            'id'                : 0,
            'formid'            : 0,
            'key'               : '',
            'label'             : '',
            'windowwidth'       : 360,
            'displayType'       : 'dropdown',
            'popupform'         : 0,
            'listid'            : 0,
            'listRef'           : '',
            'joinId'            : 0,
            'isJoin'            : false,
            'canRepeat'         : false,
            'fullName'          : '',
            'show_please_select': false,
            'allowadd'          : false,
            'autoCompleteOpts'  : null,
            'observe'           : []
        },

        initialize: function (element, options) {
            this.activePopUp = false;
            this.activeSelect = false;
            this.setPlugin('databasejoin');
            this.parent(element, options);
            this.init();
        },

        watchAdd: function () {
            var self = this, c, b;
            if (c = this.getContainer()) {
                b = c.getElement('.toggle-addoption');

                // If duplicated remove old events

                b.removeEvent('click', this.watchAddEvent);

                this.watchAddEvent = this.start.bind(this);

                b.addEvent('click', this.watchAddEvent);
            }
        },

        /**
         * Add option via a popup form. Opens a window with the related form
         * inside
         * @param {Event} e
         * @param {boolean} force
         */
        start: function (e, force) {
            if (!this.options.editable) {
                return;
            }

            var visible, destroy,
                c = this.getContainer();

            force = force ? true : false;

            // First time loading - auto close the hidden loaded popup.
            var onContentLoaded = function () {
                this.close();
            };
            visible = false;
            if (e) {
                // Loading from click
                e.stop();
                onContentLoaded = function () {
                    this.fitToContent(false);
                };

                // @FIXME - if set to true, then click addrow, click select rows, click add row => can't submit the form
                // if set to false then there's an issue with loading data in repeat groups: see window.close()
                //destroy = true;
                visible = true;
                this.activePopUp = true;
            }

            destroy = true;

            if (force === false && (this.options.popupform === 0 || this.options.allowadd === false)) {
                return;
            }

            if (this.element === null || c === null) {
                return;
            }
            var a = c.getElement('.toggle-addoption'),
                url = typeOf(a) === 'null' ? e.target.get('href') : a.get('href');

            url += '&format=partial';

            var id = this.element.id + '-popupwin';
            this.windowopts = {
                'id'             : id,
                'title'          : Joomla.JText._('PLG_ELEMENT_DBJOIN_ADD'),
                'contentType'    : 'xhr',
                'loadMethod'     : 'xhr',
                'contentURL'     : url,
                'height'         : 320,
                'minimizable'    : false,
                'collapsible'    : true,
                'visible'        : visible,
                modalId          : this.options.modalId,
                'onContentLoaded': onContentLoaded,
                destroy          : destroy
            };
            var winWidth = this.options.windowwidth;
            if (winWidth !== '') {
                this.windowopts.width = winWidth;
                this.windowopts.onContentLoaded = onContentLoaded;
            }

            this.win = Fabrik.getWindow(this.windowopts);
        },

        getBlurEvent: function () {
            if (this.options.displayType === 'auto-complete') {
                return 'change';
            }
            return this.parent();
        },

        /**
         * Removes an option from the db join element
         *
         * @param {string} v Option value
         * @return  void
         */
        removeOption: function (v, sel) {
            var el = document.id(this.element.id);
            switch (this.options.displayType) {
                case 'dropdown':
                /* falls through */
                case 'multilist':
                    sel = jQuery.isArray(this.options.value) ? this.options.value : [this.options.value];
                    var options = el.options;
                    for (var i = 0; i < options.length; i++) {
                        if (options[i].value === v) {
                            el.remove(i);
                            if (sel) {
                                el.selectedIndex = 0;
                            }
                            if (this.options.advanced) {
                                jQuery('#' + this.element.id).trigger('liszt:updated');
                            }
                            break;
                        }
                    }
                    break;
            }
        },

        /**
         * Adds an option to the db join element, for drop-downs and radio buttons
         * (where only one selection is possible from a visible list of options)
         * the new option is only selected if its value = this.options.value
         *
         * @param {string} v Option value
         * @param {string} l Option label
         * @param {bool} autoCompleteUpdate  Should the auto-complete element set its
         * current label/value to the option
         * being added - set to false in updateFromServer if not the active element.
         *
         * @return  void
         */
        addOption: function (v, l, autoCompleteUpdate) {
            var opt, rowOpt, selected, labelField;
            l = Encoder.htmlDecode(l);
            autoCompleteUpdate = typeof(autoCompleteUpdate) !== 'undefined' ? autoCompleteUpdate : true;

            switch (this.options.displayType) {
                case 'dropdown':
                /* falls through */
                case 'multilist':
                    var sel = jQuery.isArray(this.options.value) ? this.options.value : [this.options.value];
                    selected = sel.contains(v) ? 'selected' : '';
                    opt = new Element('option', {'value': v, 'selected': selected}).set('text', l);
                    document.id(this.element.id).adopt(opt);
                    if (this.options.advanced) {
                        jQuery('#' + this.element.id).trigger('liszt:updated');
                    }
                    break;
                case 'auto-complete':
                    if (autoCompleteUpdate) {
                        labelField = this.element.getParent('.fabrikElement').getElement('input[name*=-auto-complete]');
                        this.element.value = v;
                        labelField.value = l;
                    }
                    break;
                case 'checkbox':
                    opt = this.getCheckboxTmplNode().clone();
                    rowOpt = jQuery(Fabrik.jLayouts['fabrik-element-' + this.getPlugin() + '-form-rowopts'])[0];
                    this._addOption(opt, l, v, rowOpt);
                    break;
                case 'radio':
                /* falls through */
                default:
                    opt = jQuery(Fabrik.jLayouts['fabrik-element-' + this.getPlugin() +
                    '-form-radio' + '_' + this.strElement])[0];
                    rowOpt = jQuery(Fabrik.jLayouts['fabrik-element-' + this.getPlugin() + '-form-rowopts'])[0];
                    this._addOption(opt, l, v, rowOpt);
                    break;
            }
        },

        /**
         * Adds an option to radio or checkbox
         *
         * @param {object}  opt    DOM object layout for the option
         * @param {string}  v      Option value
         * @param {string}  l      Option label
         * @param {object}  rowOpt DOM object layout for the option row container
         *
         * @return  void
         */
        _addOption: function (opt, l, v, rowOpt) {
            var sel = typeOf(this.options.value) === 'array' ?
                    this.options.value : Array.from(this.options.value),
                i = opt.getElement('input'),
                subOpts = this.getSubOptions(),
                subOptsRows = this.getSubOptsRow(),
                checked = sel.contains(v) ? true : false,
                nameIterator = this.options.displayType === 'radio' ? '' : subOpts.length;

            if (this.options.canRepeat) {
                i.name = this.options.fullName + '[' + this.options.repeatCounter + '][' + nameIterator + ']';
            } else {
                i.name = this.options.fullName + '[' + nameIterator + ']';
            }

            // stuff the value and label into the opt
            opt.getElement('span').set('html', l);
            opt.getElement('input').set('value', v);

            // if no row containers yet, inject one
            if (subOptsRows.length === 0) {
                rowOpt.inject(this.element, 'bottom');
            }

            // get the last row container
            var lastRow = jQuery(this.element).children('div[data-role=fabrik-rowopts]').last()[0];
            // get the opts in the last container
            var lastRowOpts = jQuery(lastRow).children('div[data-role=suboption]');

            // if last row is full, inject another one
            if (lastRowOpts.length >= this.options.optsPerRow) {
                rowOpt.inject(this.element, 'bottom');
                lastRow = jQuery(this.element).children('div[data-role=fabrik-rowopts]').last()[0];
            }

            // inject the new opt into the last row
            opt.inject(lastRow, 'bottom');
            // check it
            opt.getElement('input').checked = checked;
        },

        hasSubElements: function () {
            var d = this.options.displayType;
            if (d === 'checkbox' || d === 'radio') {
                return true;
            }
            return this.parent();
        },

        /**
         * As cdd elements clear out the sub options before repopulating we need
         * to grab a copy of one of the checkboxes to use as a template node when recreating
         * the list
         *
         * @return  dom node(visible checkbox)
         */
        getCheckboxTmplNode: function () {
            if (Fabrik.bootstrapped) {
                this.chxTmplNode = jQuery(
                    Fabrik.jLayouts['fabrik-element-' + this.getPlugin() + '-form-checkbox' + '_' + this.strElement]
                )[0];
                if (!this.chxTmplNode && this.options.displayType === 'checkbox') {
                    var chxs = this.element.getElements('> .fabrik_subelement');
                    if (chxs.length === 0) {
                        this.chxTmplNode = this.element.getElement('.chxTmplNode').getChildren()[0].clone();
                        this.element.getElement('.chxTmplNode').destroy();
                    } else {
                        this.chxTmplNode = chxs.getLast().clone();
                    }
                }
            }

            return this.chxTmplNode;
        },

        /**
         * As cdd elements clear out the sub options before repopulating we need
         * to grab a copy of one of the checkboxes to use as a template node when recreating
         * the list
         *
         * @return {domNode} (visible checkbox)
         */
        getCheckboxRowOptsNode: function () {
            if (Fabrik.bootstrapped) {
                this.chxTmplNode = jQuery(Fabrik.jLayouts['fabrik-element-' + this.getPlugin() + '-form-rowopts'])[0];
            } else {
                if (!this.chxTmplNode && this.options.displayType === 'checkbox') {
                    var chxs = this.element.getElements('> .fabrik_subelement');
                    if (chxs.length === 0) {
                        this.chxTmplNode = this.element.getElement('.chxTmplNode').getChildren()[0].clone();
                        this.element.getElement('.chxTmplNode').destroy();
                    } else {
                        this.chxTmplNode = chxs.getLast().clone();
                    }
                }
            }

            return this.chxTmplNode;
        },

        /**
         * Send an ajax request to re-query the element options and update the element if new options found
         *
         * @param {string} v (optional) additional value to get the updated value for (used in select)
         */
        updateFromServer: function (v) {
            var formdata = this.form.getFormElementData(),
                self = this,
                data = {
                    'option'    : 'com_fabrik',
                    'format'    : 'raw',
                    'task'      : 'plugin.pluginAjax',
                    'plugin'    : 'databasejoin',
                    'method'    : 'ajax_getOptions',
                    'element_id': this.options.id,
                    'formid'    : this.options.formid
                };
            data = Object.append(formdata, data);

            // $$$ hugh - don't think we need to fetch values if auto-complete
            // and v is empty, otherwise we'll just fetch every row in the target table,
            // and do nothing with it in onComplete?  So just set it blank now.
            if (this.options.displayType === 'auto-complete' && v === '') {
                this.addOption('', '', true);
                this.element.fireEvent('change', new Event.Mock(this.element, 'change'));
                this.element.fireEvent('blur', new Event.Mock(this.element, 'blur'));
                return;
            }
            if (v) {
                data[this.strElement + '_raw'] = v;

                // Joined elements strElement isnt right so use fullName as well
                data[this.options.fullName + '_raw'] = v;
            }

            Fabrik.loader.start(this.element.getParent(), Joomla.JText._('COM_FABRIK_LOADING'));

            new Request.JSON({
                url      : '',
                method   : 'post',
                'data'   : data,
                onSuccess: function (json) {
                    Fabrik.loader.stop(self.element.getParent());
                    var sel, changed = false, existingValues = self.getOptionValues();

                    // If duplicating an element in a repeat group when its auto-complete
                    // we dont want to update its value
                    if (self.options.displayType === 'auto-complete' && v === '' &&
                        existingValues.length === 0) {
                        return;
                    }

                    var jsonValues = [];
                    json.each(function (o) {
                        jsonValues.push(o.value);
                        if (!existingValues.contains(o.value) && o.value !== null) {
                            sel = self.options.value === o.value;
                            self.addOption(o.value, o.text, sel);
                            changed = true;
                        }
                    });

                    existingValues.each(function (ev) {
                        if (!jsonValues.contains(ev)) {
                            sel = self.options.value === ev;
                            self.removeOption(ev, sel);
                            changed = true;
                        }
                    });

                    if (changed) {
                        self.element.fireEvent('change', new Event.Mock(self.element, 'change'));
                        self.element.fireEvent('blur', new Event.Mock(self.element, 'blur'));
                    }

                    self.activePopUp = false;
                }
            }).post();
        },

        getSubOptions: function () {
            var o;
            switch (this.options.displayType) {
                case 'dropdown':
                /* falls through */
                case 'multilist':
                    o = this.element.getElements('option');
                    break;
                case 'checkbox':
                    o = this.element.getElements('[data-role=suboption] input[type=checkbox]');
                    break;
                case 'radio':
                /* falls through */
                default:
                    o = this.element.getElements('[data-role=suboption] input[type=radio]');
                    break;
            }
            return o;
        },

        getSubOptsRow: function () {
            var o;
            switch (this.options.displayType) {
                case 'dropdown':
                /* falls through */
                case 'multilist':
                /* falls through */
                default:
                    break;
                case 'checkbox':
                case 'radio':
                    o = this.element.getElements('[data-role=fabrik-rowopts]');
                    break;
            }
            return o;

        },

        getOptionValues: function () {
            var o = this.getSubOptions(),
                values = [];
            o.each(function (o) {
                values.push(o.get('value'));
            });
            return values.unique();
        },

        appendInfo: function (data) {
            var rowId = data.rowid,
                self = this,
                url = 'index.php?option=com_fabrik&view=form&format=raw',
                post = {
                    'formid': this.options.popupform,
                    'rowid' : rowId
                };
            new Request.JSON({
                url      : url,
                'data'   : post,
                onSuccess: function (r) {
                    var v = r.data[self.options.key];
                    var l = r.data[self.options.label];

                    switch (self.options.displayType) {
                        case 'dropdown':
                        /* falls through */
                        case 'multilist':
                            var o = self.element.getElements('option').filter(function (o, x) {
                                if (o.get('value') === v) {
                                    self.options.displayType === 'dropdown' ?
                                        self.element.selectedIndex = x : o.selected = true;
                                    return true;
                                }
                            });
                            if (o.length === 0) {
                                self.addOption(v, l);
                            }
                            break;
                        case 'auto-complete':
                            self.addOption(v, l);
                            break;
                        case 'checkbox':
                            self.addOption(v, l);
                            break;
                        case 'radio':
                        /* falls through */
                        default:
                            o = self.element.getElements('.fabrik_subelement').filter(function (o, x) {
                                if (o.get('value') === v) {
                                    o.checked = true;
                                    return true;
                                }
                            });
                            if (o.length === 0) {
                                self.addOption(v, l);
                            }
                            break;
                    }

                    if (typeOf(self.element) === 'null') {
                        return;
                    }
                    // $$$ hugh - fire change blur event, so things like auto-fill will pick up change
                    self.element.fireEvent('change', new Event.Mock(self.element, 'change'));
                    self.element.fireEvent('blur', new Event.Mock(self.element, 'blur'));
                }
            }).send();
        },

        watchSelect: function () {
            var c, winId,
                self = this;
            if (c = this.getContainer()) {
                var sel = c.getElement('.toggle-selectoption');
                if (typeOf(sel) !== 'null') {
                    sel.addEvent('click', function (e) {
                        self.selectRecord(e);
                    });
                    Fabrik.addEvent('fabrik.list.row.selected', function (json) {
                        if (self.options.listid.toInt() === json.listid.toInt() && self.activeSelect) {
                            self.update(json.rowid);
                            winId = self.element.id + '-popupwin-select';
                            if (Fabrik.Windows[winId]) {
                                Fabrik.Windows[winId].close();
                            }
                        }
                    });

                    // Used for auto-completes in repeating groups to stop all fields updating when a record
                    // is selected
                    this.unactiveFn = function () {
                        self.activeSelect = false;
                    };
                    window.addEvent('fabrik.dbjoin.unactivate', this.unactiveFn);
                    this.selectThenAdd();
                }
                this.selectThenAdd();
            }
        },

        /**
         * Watch the list load so that its add button will close the window and open the db join add window
         *
         * @return void
         */
        selectThenAdd: function () {
            Fabrik.addEvent('fabrik.block.added', function (block, blockid) {
                if (blockid === 'list_' + this.options.listid + this.options.listRef) {
                    block.form.addEvent('click:relay(.addbutton)', function (event, target) {
                        event.preventDefault();
                        var id = this.selectRecordWindowId();
                        Fabrik.Windows[id].close();
                        this.start(event, true);
                    }.bind(this));
                }
            }.bind(this));
        },

        /**
         * Called when form closed in ajax window
         * Should remove any events added to Window or Fabrik
         */
        destroy: function () {
            window.removeEvent('fabrik.dbjoin.unactivate', this.unactiveFn);
        },

        selectRecord: function (e) {
            window.fireEvent('fabrik.dbjoin.unactivate');
            this.activeSelect = true;
            e.stop();
            var id = this.selectRecordWindowId();
            var url = this.getContainer().getElement('a.toggle-selectoption').href;
            url += '&format=partial';
            url += '&triggerElement=' + this.element.id;
            url += '&resetfilters=1';
            url += '&c=' + this.options.listRef;

            this.windowopts = {
                'id'             : id,
                modalId          : 'db_join_select',
                'title'          : Joomla.JText._('PLG_ELEMENT_DBJOIN_SELECT'),
                'contentType'    : 'xhr',
                'loadMethod'     : 'xhr',
                'evalScripts'    : true,
                'contentURL'     : url,
                'width'          : this.options.windowwidth,
                'height'         : 320,
                'minimizable'    : false,
                'collapsible'    : true,
                'onContentLoaded': function () {
                    this.fitToContent();
                }
            };
            Fabrik.getWindow(this.windowopts);
        },

        /**
         * Get the window id for the 'select record' window
         *
         * @return  string
         */
        selectRecordWindowId: function () {
            return this.element.id + '-popupwin-select';
        },

        numChecked: function () {
            if (this.options.displayType !== 'checkbox') {
                return null;
            }
            return this._getSubElements().filter(function (c) {
                return c.value !== '0' ? c.checked : false;
            }).length;
        },

        update: function (val) {
            this.getElement();
            if (typeOf(this.element) === 'null') {
                return;
            }
            if (!this.options.editable) {
                this.element.set('html', '');
                if (val === '') {
                    return;
                }
                if (typeOf(val) === 'string') {
                    val = JSON.decode(val);
                }
                var h = this.form.getFormData();
                if (typeOf(h) === 'object') {
                    h = $H(h);
                }
                val.each(function (v) {
                    if (typeOf(h.get(v)) !== 'null') {
                        this.element.innerHTML += h.get(v) + '<br />';
                    } else {
                        //for detailed view prev/next pagination v is set via elements
                        //getROValue() method and is thus in the correct format - not sure that
                        // h.get(v) is right at all but leaving in in case i've missed another scenario
                        this.element.innerHTML += v + '<br />';
                    }
                }.bind(this));
                return;
            }
            this.setValue(val);
        },

        setValue: function (val) {
            var found = false;
            if (typeOf(this.element.options) !== 'null') { //needed with repeat group code
                for (var i = 0; i < this.element.options.length; i++) {
                    if (this.element.options[i].value === val) {
                        this.element.options[i].selected = true;
                        found = true;
                        break;
                    }
                }
            }
            if (!found) {
                if (this.options.displayType === 'auto-complete') {
                    this.element.value = val;
                    this.updateFromServer(val);
                } else {
                    if (this.options.displayType === 'dropdown') {
                        if (this.options.show_please_select) {
                            this.element.options[0].selected = true;
                        }
                    } else {
                        if (typeOf(val) === 'string') {
                            val = val === '' ? [] : JSON.decode(val);
                        }
                        if (typeOf(val) !== 'array') {
                            val = [val];
                        }
                        this._getSubElements();
                        this.subElements.each(function (el) {
                            var chx = false;
                            val.each(function (v) {
                                if (v.toString() === el.value) {
                                    chx = true;
                                }
                            }.bind(this));
                            el.checked = chx;
                        }.bind(this));
                    }
                }
            }
            this.options.value = val;
            if (this.options.advanced) {
                jQuery('#' + this.element.id).trigger('liszt:updated');
            }
        },

        /**
         * $$$ hugh - testing being able to set a drop-down join by label rather than value,
         * needed in corner cases like reverse geocoding in the map element, where (say) the
         * 'country' element might be a join / CDD, but obviously we only get a label ("Austria")
         * back from Google.  For now, VERY limited support, only for simple drop-down type.
         */
        updateByLabel: function (label) {
            this.getElement();
            if (typeOf(this.element) === 'null') {
                return;
            }
            // If it's not editable or not a drop-down, just punt to a normal update()
            if (!this.options.editable || this.options.displayType !== 'dropdown') {
                this.update(label);
            }
            // OK, it's an editable drop-down, so let's see if we can find a matching option text
            var options = this.element.getElements('option');
            options.some(function (option) {
                if (option.text === label) {
                    this.update(option.value);
                    return true;
                }
                else {
                    return false;
                }
            }.bind(this));
        },

        /**
         * Optionally show a description which is another field from the joined table.
         */

        showDesc: function (e) {
            var v = e.target.selectedIndex;
            var c = this.getContainer().getElement('.dbjoin-description');
            var show = c.getElement('.description-' + v);
            c.getElements('.notice').each(function (d) {
                if (d === show) {
                    var myfx = new Fx.Tween(show, {
                        'property'  : 'opacity',
                        'duration'  : 400,
                        'transition': Fx.Transitions.linear
                    });
                    myfx.set(0);
                    d.setStyle('display', '');
                    myfx.start(0, 1);
                } else {
                    d.setStyle('display', 'none');
                }
            });
        },

        getValue: function () {
            var v = null;
            this.getElement();
            if (!this.options.editable) {
                return this.options.value;
            }
            if (typeOf(this.element) === 'null') {
                return '';
            }
            switch (this.options.displayType) {
                case 'dropdown':
                /* falls through */
                default:
                    if (typeOf(this.element.get('value')) === 'null') {
                        return '';
                    }
                    return this.element.get('value');
                case 'multilist':
                    var r = [];
                    this.element.getElements('option').each(function (opt) {
                        if (opt.selected) {
                            r.push(opt.value);
                        }
                    });
                    return r;
                case 'auto-complete':
                    return this.element.value;
                case 'radio':
                    v = '';
                    this._getSubElements().each(function (sub) {
                        if (sub.checked) {
                            v = sub.get('value');
                            return v;
                        }
                        return null;
                    });
                    return v;
                case 'checkbox':
                    v = [];
                    this.getChxLabelSubElements().each(function (sub) {
                        if (sub.checked) {
                            v.push(sub.get('value'));
                        }
                    });
                    return v;
            }
        },

        /**
         * When rendered as a checkbox - the joined to tables values are stored in the visible checkboxes,
         * for getValue() to get the actual values we only want to select these subElements and not the hidden
         * ones which if we did would add the lookup lists's ids into the values array.
         *
         * @return  array
         */
        getChxLabelSubElements: function () {
            var subs = this._getSubElements();
            return subs.filter(function (sub) {
                if (!sub.name.contains('___id')) {
                    return true;
                }
            });
        },

        /**
         * Used to find element when form clones a group
         * WYSIWYG text editor needs to return something specific as options.element has to use name
         * and not id.
         */
        getCloneName: function () {
            // Testing for issues with cdd rendered as chx in repeat group when observing auto-complete db
            // join element in main group
            /*if (this.options.isGroupJoin && this.options.isJoin) {
             return this.options.elementName;
             }*/
            return this.options.element;
        },

        getValues: function () {
            var v = [];
            var search = (this.options.displayType !== 'dropdown') ? 'input' : 'option';
            document.id(this.element.id).getElements(search).each(function (f) {
                v.push(f.value);
            });
            return v;
        },

        cloned: function (c) {
            //c is the repeat group count
            this.activePopUp = false;
            this.parent(c);
            this.init();
            this.watchSelect();
            if (this.options.displayType === 'auto-complete') {
                this.cloneAutoComplete();
            }
        },

        /**
         * Update auto-complete fields id and create new auto-completer object for duplicated element
         */
        cloneAutoComplete: function () {
            var f = this.getAutoCompleteLabelField();
            f.id = this.element.id + '-auto-complete';
            f.name = this.element.name.replace('[]', '') + '-auto-complete';
            document.id(f.id).value = '';
            new AutoComplete(this.element.id, this.options.autoCompleteOpts);
        },

        watchObserve: function () {
            var v2, o2;
            this.options.observe.each(function (o) {
                if (o === '') {
                    return;
                }
                if (this.form.formElements[o]) {
                    this.form.formElements[o].addNewEventAux(this.form.formElements[o].getChangeEvent(), function (e) {
                        this.updateFromServer();
                    }.bind(this));
                }
                else {
                    if (this.options.canRepeat) {
                        o2 = o + '_' + this.options.repeatCounter;
                        if (this.form.formElements[o2]) {
                            this.form.formElements[o2].addNewEventAux(this.form.formElements[o2].getChangeEvent(),
                                function (e) {
                                    this.updateFromServer();
                                }.bind(this));
                        }
                    }
                    else {
                        this.form.repeatGroupMarkers.each(function (v, k) {
                            o2 = '';
                            for (v2 = 0; v2 < v; v2++) {
                                o2 = 'join___' + this.form.options.group_join_ids[k] + '___' + o + '_' + v2;
                                if (this.form.formElements[o2]) {
                                    // $$$ hugh - think we can add this one as sticky ...
                                    this.form.formElements[o2].addNewEvent(this.form.formElements[o2].getChangeEvent(),
                                        function (e) {
                                            this.updateFromServer();
                                        }.bind(this));
                                }
                            }
                        }.bind(this));
                    }
                }
            }.bind(this));
        },

        attachedToForm: function () {
            if (this.options.editable) {
                this.watchObserve();
            }
            this.parent();
        },

        init: function () {
            // Could be in a popup add record form, in which case we don't
            // want to ini on a main page load
            if (typeOf(this.element) === 'null') {
                return;
            }
            if (this.options.editable) {
                this.getCheckboxTmplNode();
            }

            // If users can add records to the database join drop down
            if (this.options.allowadd === true && this.options.editable !== false) {
                this.watchAddEvent = this.start.bind(this);
                this.watchAdd();
                Fabrik.addEvent('fabrik.form.submitted', function (form, json) {

                    // Fired when form submitted - enables element to update itself
                    // with any new submitted data
                    if (this.options.popupform === form.id) {

                        // Only set the value if this element has triggered the pop up
                        // (ie could not be if in a repeat group)
                        if (this.activePopUp) {
                            this.options.value = json.rowid;
                        }
                        // rob previously we we doing appendInfo() but that didnt get the concat
                        // labels for the database join
                        if (this.options.displayType === 'auto-complete') {
                            if (this.activePopup) {
                                // Need to get v if auto-complete and updating from posted popup form
                                // as we only want to get ONE
                                // option back inside update();
                                new Request.JSON({
                                    'url'      : 'index.php?option=com_fabrik&view=form&format=raw',
                                    'data'     : {
                                        'formid': this.options.popupform,
                                        'rowid' : json.rowid
                                    },
                                    'onSuccess': function (json) {
                                        this.update(json.data[this.options.key]);
                                    }.bind(this)
                                }).send();
                            }
                        } else {
                            this.updateFromServer();
                        }
                    }
                }.bind(this));
            }

            if (this.options.editable) {
                this.watchSelect();
                if (this.options.showDesc === true) {
                    this.element.addEvent('change', function (e) {
                        this.showDesc(e);
                    }.bind(this));
                }
            }
        },

        getAutoCompleteLabelField: function () {
            var p = this.element.getParent('.fabrikElement');
            var f = p.getElement('input[name*=-auto-complete]');
            if (typeOf(f) === 'null') {
                f = p.getElement('input[id*=-auto-complete]');
            }
            return f;
        },

        addNewEventAux: function (action, js) {
            switch (this.options.displayType) {
                case 'dropdown':
                /* falls through */
                default:
                    if (this.element) {
                        this.element.addEvent(action, function (e) {
                            if (e) {
                                e.stop();
                            }
                            (typeOf(js) === 'function') ? js.delay(0, this, this) : eval(js);
                        }.bind(this));
                    }
                    break;
                case 'checkbox':
                /* falls through */
                case 'radio':
                    this._getSubElements();
                    this.subElements.each(function (el) {
                        el.addEvent(action, function () {
                            (typeOf(js) === 'function') ? js.delay(0, this, this) : eval(js);
                        }.bind(this));
                    }.bind(this));
                    break;
                case 'auto-complete':
                    var f = this.getAutoCompleteLabelField();
                    if (typeOf(f) !== 'null') {
                        f.addEvent(action, function (e) {
                            if (e) {
                                e.stop();
                            }
                            (typeOf(js) === 'function') ? js.delay(700, this, this) : eval(js);
                        }.bind(this));
                    }
                    if (this.element) {
                        this.element.addEvent(action, function (e) {
                            if (e) {
                                e.stop();
                            }
                            (typeOf(js) === 'function') ? js.delay(0, this, this) : eval(js);
                        }.bind(this));
                    }
                    break;
            }
        },

        decreaseName: function (delIndex) {
            if (this.options.displayType === 'auto-complete') {
                var f = this.getAutoCompleteLabelField();
                if (typeOf(f) !== 'null') {
                    f.name = this._decreaseName(f.name, delIndex, '-auto-complete');
                    f.id = this._decreaseId(f.id, delIndex, '-auto-complete');
                }
            }
            return this.parent(delIndex);
        },

        /**
         * When a form/details view is updating its own data, then should we use the raw data or the html?
         * Raw is used for cdd/db join elements
         *
         * @returns {boolean}
         */
        updateUsingRaw: function () {
            return true;
        }
    });

    return window.FbDatabasejoin;
});