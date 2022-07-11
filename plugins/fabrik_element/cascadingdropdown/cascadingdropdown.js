/**
 * Cascading Dropdown Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/**
 watch another element for changes to its value, and send an ajax call to update
 this elements values
 */

define(['jquery', 'element/databasejoin/databasejoin', 'fab/autocomplete-bootstrap-cdd'], function (jQuery, FbDatabasejoin, FabCddAutocomplete) {
    window.FbCascadingdropdown = new Class({

        options : {
            watchInSameGroup: true
        },

        Extends   : FbDatabasejoin,
        initialize: function (element, options) {
            this.ignoreAjax = false;
            this.setPlugin('cascadingdropdown');
            this.parent(element, options);
            if (document.id(this.options.watch)) {
                /**
                 * In order to be able to remove specific change event functions when we clone
                 * the element, we have to bind the call to a variable, can't use inline functions
                 */
                this.doChangeEvent = this.doChange.bind(this);
                document.id(this.options.watch).addEvent(this.options.watchChangeEvent, this.doChangeEvent);
            }
            if (this.options.showDesc === true) {
                this.element.addEvent('change', function (e) {
                    this.showDesc(e);
                }.bind(this));
            }
            if (typeOf(this.element) !== 'null') {
                this.spinner = new Spinner(this.element.getParent('.fabrikElementContainer'));
            }
        },

        attachedToForm: function () {
            // $$$ rob have to call update here otherwise all options can be shown
            //use this method as getValue on el wont work if el readonly
            // $$$ hugh - only do this if not editing an existing row, see ticket #725
            // $$$ hugh - ignoreAjax is set when duplicating a group, when we do need to change()
            // regardless of whether this is a new row or editing.
            if (this.ignoreAjax || (this.options.editable && !this.options.editing)) {
                this.ignoreAjax = false;
                var v = this.form.formElements.get(this.options.watch).getValue();
                this.change(v, document.id(this.options.watch).id);
            }
            this.parent();
        },

        dowatch: function (e) {
            var v = Fabrik.blocks[this.form.form.id].formElements[this.options.watch].getValue();
            this.change(v, e.target.id);
        },

        doChange: function (e) {
            if (this.options.displayType === 'auto-complete') {
                this.element.value = '';
                this.getAutoCompleteLabelField().value = '';
            }
            this.dowatch(e);
        },

        /**
         * Change
         * @param   v          Value of observed element
         * @param   triggerid  Observed element's HTML id
         */
        change: function (v, triggerid) {
            var k, s, i, formData, data;
            /* $$$ rob think this is obsolete:
             * http://fabrikar.com/forums/showthread.php?t=19675&page=2
             * $$$ hugh - nope, we still need it, with a slight modification to allow CDD to work in first group:
             * http://fabrikar.com/forums/showthread.php?p=109638#post109638
             */
            if (window.ie) {
                if (this.options.repeatCounter.toInt() === 0) {
                    // this is the original cdd element
                    s = triggerid.substr(triggerid.length - 2, 1);
                    i = triggerid.substr(triggerid.length - 1, 1);
                    // test for "_x" at end of trigger id where x is an int
                    if (s === '_' && typeOf(parseInt(i, 10)) === 'number' && i !== '0') {
                        //found so this is the bug where a third watch element incorrectly updates orig
                        return;
                    }
                }
            }
            this.spinner.show();
            // $$$ hugh testing new getFormElementData() method to include current form element values in data
            // so any custom 'where' clause on the cdd can use {placeholders}.  Can't use getFormData() because
            // it includes all QS from current page, including task=processForm, which screws up this AJAX call.
            formData = this.form.getFormElementData();

            data = {
                'option'                    : 'com_fabrik',
                'format'                    : 'raw',
                'task'                      : 'plugin.pluginAjax',
                'plugin'                    : 'cascadingdropdown',
                'method'                    : 'ajax_getOptions',
                'element_id'                : this.options.id,
                'v'                         : v,
                'formid'                    : this.form.id,
                'fabrik_cascade_ajax_update': 1,
                'lang'                      : this.options.lang
            };
            data = Object.append(formData, data);
            if (this.myAjax) {
                // $$$ rob stops ascyro behaviour when older ajax call might take longer than new
                // call and thus populate the dd with old data.
                this.myAjax.cancel();
            }
            this.myAjax = new Request({
                url        : '',
                method     : 'post',
                'data'     : data,
                onComplete : function () {
                    this.spinner.hide();
                }.bind(this),
                onSuccess  : function (json) {
                    var origValue = this.getValue(),
                        updateField,
                        c;
                    this.spinner.hide();

                    json = JSON.parse(json);
                    if (this.options.editable) {
                        this.destroyElement();
                    } else {
                        this.element.getElements('div').destroy();
                    }

                    if (this.options.showDesc === true) {
                        c = this.getContainer().getElement('.dbjoin-description');
                        c.empty();
                    }
                    this.myAjax = null;
                    var singleResult = json.length === 1;
                    if (!this.ignoreAjax) {
                        json.each(function (item) {
                            if (this.options.editable === false) {

                                // Pretify new lines to brs
                                item.text = item.text.replace(/\n/g, '<br />');
                                new Element('div').set('html', item.text).inject(this.element);
                            } else {
                                updateField = (item.value !== '' && item.value === origValue) || singleResult;
                                this.addOption(item.value, item.text, updateField);
                            }

                            if (this.options.showDesc === true && item.description) {
                                var className = this.options.showPleaseSelect ?
                                'notice description-' + (k) : 'notice description-' + (k - 1);
                                new Element('div', {
                                    styles : {display: 'none'},
                                    'class': className
                                }).set('html', item.description).inject(c);
                            }
                        }.bind(this));
                    } else {
                        if (this.options.showPleaseSelect && json.length > 0) {
                            var item = json.shift();
                            if (this.options.editable === false) {
                                new Element('div').set('text', item.text).inject(this.element);
                            } else {
                                updateField = (item.value !== '' && item.value === origValue) || singleResult;
                                this.addOption(item.value, item.text, updateField);
                                new Element('option', {
                                    'value'   : item.value,
                                    'selected': 'selected'
                                }).set('text', item.text).inject(this.element);
                            }
                        }
                    }
                    this.ignoreAjax = false;
                    // $$$ hugh - need to remove/add 'readonly' class ???
                    // Probably need to add/remove the readonly="readonly" attribute as well
                    //this.element.disabled = (this.element.options.length === 1 ? true : false);
                    if (this.options.editable && this.options.displayType === 'dropdown') {
                        if (this.element.options.length === 1) {
                            // SELECTS DON'T HAVE READONLY PROPERTIES
                            //this.element.setProperty('readonly', true);
                            this.element.addClass('readonly');
                        } else {
                            //this.element.readonly = false;
                            //this.element.removeProperty('readonly');
                            this.element.removeClass('readonly');
                        }
                    }
                    this.renewEvents();
                    // $$$ hugh - need to fire this CDD's 'change' event in case we have another CDD
                    // daisy chained on us.  We just don't need to do it if 'ignoreAjax' is true, because
                    // that means we're being added to the form, and everyone will get their change() method
                    // run anyway.  Note we have to supply the 'dowatch_event' we tucked away in dowatch()
                    // above.
                    if (!this.ignoreAjax) {
                        this.ingoreShowDesc = true;
                        this.element.fireEvent('change', new Event.Mock(this.element, 'change'));
                        this.ingoreShowDesc = false;
                    }
                    this.ignoreAjax = false;

                    var newV = [this.getValue()];
                    this.setValue(newV);

                    Fabrik.fireEvent('fabrik.cdd.update', this);
                }.bind(this),
                'onFailure': function (xhr) {
                    console.log(this.myAjax.getHeader('Status'));
                }.bind(this)
            }).send();
        },

        destroyElement: function () {
            switch (this.options.displayType) {
                case 'radio':
                /* falls through */
                case 'checkbox':
                    this.getContainer().getElements('*[data-role="suboption"]').destroy();
                    this.getContainer().getElements('*[data-role="fabrik-rowopts"]').destroy();
                    break;
                case 'dropdown':
                /* falls through */
                default:
                    this.element.empty();
                    break;
            }
        },

        cloned: function (c) {
            // c is the repeat group count
            this.myAjax = null;
            this.parent(c);
            this.spinner = new Spinner(this.element.getParent('.fabrikElementContainer'));
            // Cloned seems to be called correctly
            if (document.id(this.options.watch)) {
                if (this.options.watchInSameGroup === true) {
                    // $$$ hugh - nope, 'cos watch already has the _X appended to it!
                    // Should really work out base watch name (without _X) in PHP and put it in this.options.origWatch,
                    // but for now ... regex it ...
                    // this.options.watch = this.options.watch + '_' + c;
                    if (this.options.watch.test(/_(\d+)$/)) {
                        this.options.watch = this.options.watch.replace(/_(\d+)$/, '_' + c);
                    }
                    else {
                        this.options.watch = this.options.watch + '_' + c;
                    }
                }
                if (document.id(this.options.watch)) {
                    /**
                     * Remove the previously bound change event function, by name, then re-bind it and re-add it
                     */
                    /**
                     * Actually, we don't want to remove it, as this stops the element we got copied from
                     * being updated on a change.  This issue only surfaced when we changed this code to use
                     * a bound function, so it actually started removing the event, which it never did before
                     * when we referenced an inline function().
                     *
                     * Update ... if the watched element is in the repeat group, we do want to remove it,
                     * but if the watch is on the main form, we don't.  In other words, if the watch is on the main
                     * form, then every CDD in this repeat is watching it.  If it's in the repeat group,
                     * then each repeat CDD only watches the one in it's own group.
                     */
                    if (this.options.watchInSameGroup) {
                        document.id(this.options.watch).removeEvent(this.options.watchChangeEvent, this.doChangeEvent);
                    }
                    this.doChangeEvent = this.doChange.bind(this);
                    document.id(this.options.watch).addEvent(this.options.watchChangeEvent, this.doChangeEvent);
                }

            }
            if (this.options.watchInSameGroup === true) {
                this.element.empty();
                // Set ingoreAjax so that the ajax event that is fired when the element is added to the form manager
                // does not update the newly cloned drop-down
                this.ignoreAjax = true;
            }
            if (this.options.showDesc === true) {
                this.element.addEvent('change', function () {
                    this.showDesc();
                }.bind(this));
            }
            Fabrik.fireEvent('fabrik.cdd.update', this);
        },

        /**
         * Update auto-complete fields id and create new auto-completer object for duplicated element
         */
        cloneAutoComplete: function () {
            var f = this.getAutoCompleteLabelField();
            f.id = this.element.id + '-auto-complete';
            f.name = this.element.name.replace('[]', '') + '-auto-complete';
            document.id(f.id).value = '';
            new FabCddAutocomplete(this.element.id, this.options.autoCompleteOpts);
        },

        showDesc: function (e) {
            if (this.ingoreShowDesc === true) {
                return;
            }
            var v = document.id(e.target).selectedIndex,
                c = this.getContainer().getElement('.dbjoin-description'),
                show = c.getElement('.description-' + v),
                myFx;
            c.getElements('.notice').each(function (d) {
                if (d === show) {
                    myFx = new Fx.Style(show, 'opacity', {
                        duration  : 400,
                        transition: Fx.Transitions.linear
                    });
                    myFx.set(0);
                    d.show();
                    myFx.start(0, 1);
                } else {
                    d.hide();
                }
            }.bind(this));
        }
    });

    return window.FbCascadingdropdown;
});