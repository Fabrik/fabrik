/**
 * Admin Visualization Editor
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/* jshint mootools: true */
/* global fconsole:true, FabrikAdmin:true, Fabrik:true, PluginManager:true, Joomla:true */

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
			document.id('plugin-container').empty()
				.adopt(new Element('span').set('text', Joomla.JText._('COM_FABRIK_LOADING')));
			var myAjax = new Request({
				url: 'index.php',
				'evalResponse': false,
				'evalScripts' : function (script, text) {
					// If Joomla subform-repeatable, we need to split out the template hidden as a script file
					var regex = /\s*<div[^>]*>(.|\r|\n)*<\/div>\s*/gi;
					this.templt = regex.exec(script);
					if (Array.isArray(this.templt)) {
						this.templt = this.templt.join('');
					}
					this.script = script.replace(regex, '');
				}.bind(this),
				'data': {
					'option': 'com_fabrik',
					'task'  : 'visualization.getPluginHTML',
					'format': 'raw',
					'plugin': e.target.get('value')
				},
				'update': document.id('plugin-container'),
				'onComplete': function (r) {
					document.id('plugin-container').set('html', r);
					// If Joomla subform-repeatable, we need to add back in the template hidden as a script file
					var subform = document.querySelector('div.subform-repeatable');
					if (subform) {
						if (subform && typeof this.templt === 'string' && this.templt !== "") {
							var sEl = document.createElement('script');
							sEl.className = "subform-repeatable-template-section";
							sEl.setAttribute('type', "text/subform-repeatable-template-section");
							sEl.textContent = this.templt;
							subform.appendChild(sEl);
						}
						jQuery('div.subform-repeatable').subformRepeatable();
					}
					Browser.exec(this.script);
					this.updateBootStrap();
					FabrikAdmin.reTip();
					if (Joomla.setUpShowon) {
						Joomla.setUpShowon();
					}
				}.bind(this),
			});
			Fabrik.requestQueue.add(myAjax);
		}
	});
	return AdminVisualization;
});
