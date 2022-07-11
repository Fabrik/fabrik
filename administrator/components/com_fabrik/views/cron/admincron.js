/**
 * Admin Cron Editor
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
define(['jquery', 'admin/pluginmanager'], function (jQuery, PluginManager) {
	var CronAdmin = new Class({

		Extends: PluginManager,

		Implements: [Options, Events],

		options: {
			plugin: ''
		},

		initialize: function (options) {
			var plugins = [];
			this.parent(plugins);
			this.setOptions(options);
			this.watchSelector();
		},

		watchSelector: function () {
			if (typeof(jQuery) !== 'undefined') {
				jQuery('#jform_plugin').bind('change', function (e) {
					this.changePlugin(e);
				}.bind(this));
			}

			document.id('jform_plugin').addEvent('change', function (e) {
				e.stop();
				this.changePlugin(e);
			}.bind(this));
		},

		changePlugin: function (e) {
			var myAjax = new Request.HTML({
				url         : 'index.php',
				'data'      : {
					'option': 'com_fabrik',
					'task'  : 'cron.getPluginHTML',
					'format': 'raw',
					'plugin': e.target.get('value')
				},
				'update'    : document.id('plugin-container'),
				'onComplete': function () {
					this.updateBootStrap();
				}.bind(this)

			}).send();
		}
	});

	return CronAdmin;
});