/**
 * Admin Plugin Manager
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license: GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/* jshint mootools: true */
/*
 * global Fabrik:true, Joomla:true, fconsole:true, FabrikAdmin:true,
 * fabrikAdminPlugin:true
 */
define(['jquery', 'fab/fabrik'], function (jQuery, Fabrik) {
    var PluginManager = new Class({

        pluginTotal: 0,

        topTotal: -1,

        initialize: function (plugins, id, type) {
            if (typeOf(plugins) === 'string') {
                plugins = [plugins];
            }
            this.id = id;
            this.plugins = plugins;
            this.type = type;
            window.addEvent('domready', function () {
                var i;
                this.accordion = new Fx.Accordion([], [], {
                    alwaysHide: true,
                    display   : -1,
                    duration  : 'short'
                });
                for (i = 0; i < plugins.length; i++) {
                    this.addTop(plugins[i]);
                }
                this.periodical = this.iniAccordion.periodical(250, this);

                this.watchPluginSelect();
                this.watchDelete();
                this.watchAdd();

                var pluginArea = document.id('plugins');
                if (typeOf(pluginArea) !== 'null') {
                    pluginArea.addEvent('click:relay(h3.title)', function (e, target) {
                        document.id('plugins').getElements('h3.title').each(function (h) {
                            if (h !== target) {
                                h.removeClass('pane-toggler-down');
                            }
                        });
                        target.toggleClass('pane-toggler-down');
                    });

                    this.watchDescriptions(pluginArea);
                }
            }.bind(this));

        },

        watchDescriptions: function (pluginArea) {
            pluginArea.addEvent('keyup:relay(input[name*=plugin_description])', function (e, target) {
                var container = target.getParent('.actionContainer'),
                    title = container.getElement('.pluginTitle'),
                    plugin = container.getElement('select[name*=plugin]').getValue(),
                    desc = target.getValue();
                title.set('text', plugin + ': ' + desc);
            });
        },

        iniAccordion: function () {
            if (this.pluginTotal === this.plugins.length) {
                if (this.plugins.length === 1) {
                    this.accordion.display(0);
                } else {
                    this.accordion.display(-1);
                }
                clearInterval(this.periodical);
            }
        },

        /**
         * Has the form finished loading and are there any outstanding ajax requests
         *
         * @return bool
         */
        canSaveForm: function () {
            if (document.readyState !== 'complete') {
                return false;
            }
            return Fabrik.requestQueue.empty();
        },

        watchDelete: function () {
            document.id('adminForm').addEvent('click:relay(a.removeButton, a[data-button=removeButton])',
                function (event, target) {
                event.preventDefault();
                this.pluginTotal--;
                this.topTotal--;
                this.deletePlugin(event);
            }.bind(this));
        },

        watchAdd: function () {
            var addPlugin = document.id('addPlugin');
            if (typeOf(addPlugin) !== 'null') {
                addPlugin.addEvent('click', function (e) {
                    e.stop();
                    this.accordion.display(-1);
                    this.addTop();
                }.bind(this));
            }
        },

        addTop: function (plugin) {
            var published, show_icon, validate_in, validation_on, must_validate, validate_hidden;
            if (typeOf(plugin) === 'string') {
                published = 1;
                show_icon = false;
                must_validate = false;
                validate_hidden = true;
                plugin = plugin ? plugin : '';
                validate_in = '';
                validation_on = '';
            } else {
                // Validation plugins
                published = plugin ? plugin.published : 1;
                show_icon = plugin ? plugin.show_icon : 1;
                must_validate = plugin ? plugin.must_validate : 0;
                validate_hidden = plugin ? plugin.validate_hidden : 1;
                validate_in = plugin ? plugin.validate_in : 'both';
                validation_on = plugin ? plugin.validation_on : 'both';
                plugin = plugin ? plugin.plugin : '';
            }

            var div = new Element('div.actionContainer.panel.accordion-group');
            var a = new Element('a.accordion-toggle', {
                'href': '#'
            });
            a.adopt(new Element('span.pluginTitle')
                .set('text', plugin !== '' ? plugin + ' ' +
                Joomla.JText._('COM_FABRIK_LOADING').toLowerCase() : Joomla.JText._('COM_FABRIK_LOADING')));
            var toggler = new Element('div.title.pane-toggler.accordion-heading')
                .adopt(new Element('strong').adopt(a));
            var body = new Element('div.accordion-body');

            div.adopt(toggler);
            div.adopt(body);
            div.inject(document.id('plugins'));
            this.accordion.addSection(toggler, body);
            var tt_temp = this.topTotal + 1; //added temp variable

            // Ajax request to load the first part of the plugin form (do[plugin]
            // in, on)

            var d = {
                'option'          : 'com_fabrik',
                'view'            : 'plugin',
                'task'            : 'top',
                'format'          : 'raw',
                'type'            : this.type,
                'plugin'          : plugin,
                'plugin_published': published,
                'show_icon'       : show_icon,
                'must_validate'   : must_validate,
                'validate_hidden' : validate_hidden,
                'validate_in'     : validate_in,
                'validation_on'   : validation_on,
                'c'               : this.topTotal,
                'id'              : this.id
            };

            var request = new Request.HTML({
                url        : 'index.php',
                data       : d,
                update     : body,
                onRequest  : function () {
                    if (Fabrik.debug) {
                        fconsole('Fabrik pluginmanager: Adding', this.type, 'entry', tt_temp.toString());
                    }
                }.bind(this),
                onSuccess  : function (res) {
                    if (plugin !== '') {
                        // Sent temp variable as c to addPlugin, so they are aligned properly
                        this.addPlugin(plugin, tt_temp);
                    } else {
                        toggler.getElement('span.pluginTitle').set('text', Joomla.JText._('COM_FABRIK_PLEASE_SELECT'));
                    }
                    this.updateBootStrap();
                    FabrikAdmin.reTip();
                }.bind(this),
                onFailure  : function (xhr) {
                    fconsole('Fabrik pluginmanager addTop ajax failed:', xhr);
                },
                onException: function (headerName, value) {
                    fconsole('Fabrik pluginmanager addTop ajax exception:', headerName, value);
                }
            });
            this.topTotal++;

            Fabrik.requestQueue.add(request);
        },

        // Bootstrap specific

        updateBootStrap: function () {
            document.getElements('.radio.btn-group label').addClass('btn');

            document.getElements('.btn-group input[checked=checked]').each(function (el) {
                if (el.get('value') === '') {
                    document.getElement('label[for=' + el.get('id') + ']').addClass('active btn-primary');
                } else if (el.get('value') === '0') {
                    document.getElement('label[for=' + el.get('id') + ']').addClass('active btn-danger');
                } else {
                    document.getElement('label[for=' + el.get('id') + ']').addClass('active btn-success');
                }
                if (typeof (jQuery) !== 'undefined') {
                    jQuery('*[rel=tooltip]').tooltip();
                }

            });

            document.getElements('.hasTip').each(function (el) {
                var title = el.get('title');
                if (title) {
                    var parts = title.split('::', 2);
                    el.store('tip:title', parts[0]);
                    el.store('tip:text', parts[1]);
                }
            });
            var JTooltips = new Tips($$('.hasTip'), {
                maxTitleChars: 50,
                fixed        : false
            });
        },

        /**
         * Watch the plugin select list
         */

        watchPluginSelect: function () {
            document.id('adminForm').addEvent('change:relay(select.elementtype)', function (event, target) {
                event.preventDefault();
                var plugin = target.get('value');
                var container = target.getParent('.pluginContainer');
                var pluginName = plugin !== '' ? plugin + ' ' +
                Joomla.JText._('COM_FABRIK_LOADING').toLowerCase() : Joomla.JText._('COM_FABRIK_PLEASE_SELECT');
                target.getParent('.actionContainer').getElement('span.pluginTitle').set('text', pluginName);
                var c = container.id.replace('formAction_', '').toInt();
                this.addPlugin(plugin, c);
            }.bind(this));
        },

        addPlugin: function (plugin, c) {
            c = typeOf(c) === 'number' ? c : this.pluginTotal;
            if (plugin === '') {
                document.id('plugins').getElements('.actionContainer')[c].getElement('.pluginOpts').empty();
                return;
            }

            // Ajax request to load the plugin content
            var request = new Request.HTML({
                url        : 'index.php',
                data       : {
                    'option': 'com_fabrik',
                    'view'  : 'plugin',
                    'format': 'raw',
                    'type'  : this.type,
                    'plugin': plugin,
                    'c'     : c,
                    'id'    : this.id
                },
                update     : document.id('plugins').getElements('.actionContainer')[c].getElement('.pluginOpts'),
                onRequest  : function () {
                    if (Fabrik.debug) {
                        fconsole('Fabrik pluginmanager: Loading', this.type, 'type', plugin, 'for entry', c.toString());
                    }
                }.bind(this),
                onSuccess  : function () {
                    var container = document.id('plugins').getElements('.actionContainer')[c];
                    var title = container.getElement('span.pluginTitle'),
                        heading = plugin,
                        desc = container.getElement('input[name*=plugin_description]');
                    if (desc) {
                        heading += ': ' + desc.getValue();
                    }
                    title.set('text', heading);
                    this.pluginTotal++;
                    this.updateBootStrap();
                    FabrikAdmin.reTip();
                    // trigger event to init any 'showon' handling for fields in J! cms JS
                    //Joomla.setUpShowon(container);
                }.bind(this),
                onFailure  : function (xhr) {
                    fconsole('Fabrik pluginmanager addPlugin ajax failed:', xhr);
                },
                onException: function (headerName, value) {
                    fconsole('Fabrik pluginmanager addPlugin ajax exception:', headerName, value);
                }
            });
            Fabrik.requestQueue.add(request);
        },

        deletePlugin: function (e) {
            var c = e.target.getParent('fieldset.pluginContainer');
            if (typeOf(c) === 'null') {
                return;
            }
            if (Fabrik.debug) {
                fconsole('Fabrik pluginmanager: Deleting', this.type, 'entry', c.id, 'and renaming later entries');
            }
            /**
             * The following code reduces the index in ids, names and <label for=id>
             * for all entries after the entry that is being deleted. Paul 20131102
             * Extended to handle more field types and ids in all tags not just
             * fieldset This code handles the following tags:
             * fieldset.pluginContainer id='formAction_x' label id='id-x(stuff)-lbl'
             * for='name-x(stuff)-lbl' select id='id-x' name='name[x]' fieldset
             * id='id-x-' class='radio btn-group' input type='radio'
             * id='id-x(stuff)' label for='name-x(stuff)-lbl' class='btn' input
             * type='text' id='id-x' name='name[x]' textarea id='id-x'
             * name='name[x]'
             */
            if (c.id.match(/_\d+$/)) {
                var x = c.id.match(/_(\d+)$/)[1].toInt();
                document.id('plugins').getElements('input, select, textarea, label, fieldset').each(function (i) {
                    // Get index from name or id
                    var s = i.name ? i.name.match(/\[(\d+)\]/) : null;
                    if (!s && i.id) {
                        s = i.id.match(/-(\d+)/);
                    }
                    if (!s && i.get('tag').toLowerCase() === 'label' && i.get('for')) {
                        s = i.get('for').match(/-(\d+)/);
                    }
                    if (s) {
                        var c = s[1].toInt();
                        if (c > x) {
                            // fconsole('tag:',i.get('tag'),'id:',i.id,'name:',i.name,'s:',s[1]);
                            c--;
                            if (i.name) {
                                // fconsole(' Replacing name',i.name, 'with',
                                // i.name.replace(/(\[)(\d+)(\])/, '[' + c + ']'));
                                i.name = i.name.replace(/(\[)(\d+)(\])/, '[' + c + ']');
                            }
                            if (i.id) {
                                // fconsole(' Replacing id',i.id, 'with',
                                // i.id.replace(/(-)(\d+)/, '-' + c));
                                i.id = i.id.replace(/(-)(\d+)/, '-' + c);
                            }
                            if (i.get('tag').toLowerCase() === 'label' && i.get('for')) {
                                // fconsole(' Replacing for', i.get('for'), 'with',
                                // i.get('for').replace(/(-)(\d+)/, '-' + c));
                                i.set('for', i.get('for').replace(/(-)(\d+)/, '-' + c));
                            }
                        }
                    }
                });
                document.id('plugins').getElements('fieldset.pluginContainer').each(function (i) {
                    if (i.id.match(/formAction_\d+$/)) {
                        var c = i.id.match(/formAction_(\d+)$/)[1].toInt();
                        if (c > x) {
                            c = c - 1;
                            i.id = i.id.replace(/(formAction_)(\d+)$/, '$1' + c);
                        }
                    }
                });
            }
            e.stop();
            e.target.getParent('.actionContainer').dispose();
        }

    });

    return PluginManager;
});

/*

fabrikAdminPlugin = new Class({

    Implements: [Options],
    options   : {},
    initialize: function (name, label, options) {
        this.name = name;
        this.label = label;
        this.setOptions(options);
    },

    cloned: function () {

    }

});*/
