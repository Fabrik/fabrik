/**
 * Admin Visualization Editor
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'admin/pluginmanager'], function (jQuery, PluginManager) {
    var AdminVisualization = new Class({

        Extends: PluginManager,

        Implements: [Options, Events],

        options: {},

        initialize: function (options, lang) {
            this.setOptions(options);
            this.watchSelector();
        },

        watchSelector: function () {
            if (typeof(jQuery) !== 'undefined') {
                jQuery('#jform_plugin').bind('change', function (e) {
                    this.changePlugin(e);
                }.bind(this));
            } else {
                document.id('jform_plugin').addEvent('change', function (e) {
                    e.stop();
                    this.changePlugin(e);
                }.bind(this));
            }
        },

        changePlugin: function (e) {
            var myAjax = new Request({
                url           : 'index.php',
                'evalResponse': false,
                'evalScripts' : function (script, text) {
                    this.script = script;
                }.bind(this),
                'data'        : {
                    'option': 'com_fabrik',
                    'task'  : 'visualization.getPluginHTML',
                    'format': 'raw',
                    'plugin': e.target.get('value')
                },
                'update'      : document.id('plugin-container'),
                'onComplete'  : function (r) {
                    document.id('plugin-container').set('html', r);
                    Browser.exec(this.script);
                    this.updateBootStrap();
                }.bind(this)
            }).send();
        }
    });
    return AdminVisualization;
});