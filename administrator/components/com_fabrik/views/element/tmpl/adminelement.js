/**
 * Admin Element Editor
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license: GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/* jshint mootools: true */
/* global fconsole:true, FabrikAdmin:true, Fabrik:true, PluginManager:true, Joomla:true */

define (['jquery', 'admin/pluginmanager'], function (jQuery, PluginManager) {
	var fabrikAdminElement = new Class({

		Extends: PluginManager,

		Implements: [Options, Events],

		options: {
			id: 0,
			parentid: 0,
			jsevents: [],
			jsTotal: 0,
			deleteButton: 'removeButton'
		},

		jsCounter: -1,
		jsAjaxed: 0,

		initialize: function (plugins, options, id) {
			if (Fabrik.debug) {
				fconsole('Fabrik adminelement.js: Initialising', plugins, options, id);
			}
			this.parent(plugins, id, 'validationrule');
			this.setOptions(options);
			this.setParentViz();

			this.jsAccordion = new Fx.Accordion([], [], {
				alwaysHide: true,
				display: -1,
				duration: 'short'
			});
			window.addEvent('domready', function () {
				if (typeOf(document.id('addJavascript')) === 'null') {
					fconsole('Fabrik adminelement.js: javascript tab Add button not found');
				} else {
					document.id('addJavascript').addEvent('click', function (e) {
						e.stop();
						this.jsAccordion.display(-1);
						this.addJavascript();
					}.bind(this));
				}

				this.watchLabel();
				this.watchGroup();
				this.options.jsevents.each(function (opt) {
					this.addJavascript(opt);
				}.bind(this));

				this.jsPeriodical = this.iniJsAccordion.periodical(250, this);

				document.id('jform_plugin').addEvent('change', function (e) {
					this.changePlugin(e.target.get('value'));
				}.bind(this));

				if (document.getElement('input[name=name_orig]').value === '') {
					this.changePlugin('field');
				}

				document.id('javascriptActions')
					.addEvent('click:relay(a[data-button=removeButton])', function (e, target) {
					e.stop();
					this.deleteJS(target);
				}.bind(this));

				document.id('javascriptActions')
					.addEvent('change:relay(select[id^="jform_action-"],select[id^="jform_js_e_event-"],select[id^="jform_js_e_trigger-"],select[id^="jform_js_e_condition-"],input[id^="jform_js_e_value-"])', function (e, target) {
					this.setAccordionHeader(target.getParent('.actionContainer'));
				}.bind(this));

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
				}

			}.bind(this));

		},

		/**
		 * Automatically fill in the db table name from the label if no
		 * db table name existed when the form loaded and when the user has not
		 * edited the db table name.
		 */
		watchLabel: function () {
			this.autoChangeDbName = jQuery('#jform_name').val() === '';
			jQuery('#jform_label').on('keyup', function (e) {
				if (this.autoChangeDbName) {
					var label = jQuery('#jform_label').val().trim().toLowerCase();
					label = label.replace(/\W+/g, '_');
					jQuery('#jform_name').val(label);
				}
			}.bind(this));

			jQuery('#jform_name').on('keyup', function () {
				this.autoChangeDbName = false;
			}.bind(this));
		},

		/**
		 * Set the last selected group as a cookie value.
		 * Then on page load if no group set, set to the cookie value.
		 */
		watchGroup: function ()  {
			var cookieName = 'fabrik_element_group';
			if (jQuery('#jform_group_id').val() === '') {
				var keyValue = document.cookie.match('(^|;) ?' + cookieName + '=([^;]*)(;|$)');
				var val = keyValue ? keyValue[2] : null;
				jQuery('#jform_group_id').val(val);
			}

			jQuery('#jform_group_id').on('change', function () {
				var value = jQuery('#jform_group_id').val();
				var date = new Date();
				date.setTime(date.getTime() + (1 * 24 * 60 * 60 * 1000));
				var expires = '; expires=' + date.toGMTString();
				document.cookie = cookieName + '=' + encodeURIComponent(value) + expires;
			});
		},

		iniJsAccordion: function () {
			if (this.jsAjaxed === this.options.jsevents.length) {
				if (this.options.jsevents.length === 1) {
					this.jsAccordion.display(0);
				} else {
					this.jsAccordion.display(-1);
				}
				clearInterval(this.jsPeriodical);
			}
		},

		changePlugin: function (e) {
			document.id('plugin-container').empty()
				.adopt(new Element('span').set('text', Joomla.JText._('COM_FABRIK_LOADING')));
			var myAjax = new Request({
				url: 'index.php',
				'evalResponse': false,
				'evalScripts': function (script, text) {
					this.script = script;
				}.bind(this),
				'data': {
					'option': 'com_fabrik',
					'id': this.options.id,
					'task': 'element.getPluginHTML',
					'format': 'raw',
					'plugin': e
				},
				'update': document.id('plugin-container'),
				'onComplete': function (r) {
					document.id('plugin-container').set('html', r);
					Browser.exec(this.script);
					this.updateBootStrap();
					FabrikAdmin.reTip();
				}.bind(this)
			});
			Fabrik.requestQueue.add(myAjax);
		},

		deleteJS: function (target) {
			var c = target.getParent('div.actionContainer');
			if (Fabrik.debug) {
				fconsole('Fabrik adminelement.js: Deleting JS entry: ', c.id);
			}
			c.dispose();
			this.jsAjaxed--;
		},

		addJavascript: function (opt) {
			var jsId = opt && opt.id ? opt.id : 0;
			// Ajax request to load the first part of the plugin form
			// (do[plugin] in, on)
			var div = new Element('div.actionContainer.panel.accordion-group');
			var a = new Element('a.accordion-toggle', {
				'href': '#'
			});
			a.adopt(new Element('span.pluginTitle').set('text', Joomla.JText._('COM_FABRIK_LOADING')));
			var toggler = new Element('div.title.pane-toggler.accordion-heading').adopt(new Element('strong').adopt(a));
			var body = new Element('div.accordion-body');

			div.adopt(toggler);
			div.adopt(body);
			this.jsAccordion.addSection(toggler, body);
			div.inject(document.id('javascriptActions'));
			var c = this.jsCounter;
			var request = new Request.HTML({
				url: 'index.php',
				data: {
					'option': 'com_fabrik',
					'view': 'plugin',
					'task': 'top',
					'format': 'raw',
					'type': 'elementjavascript',
					'plugin': null,
					'plugin_published': true,
					'c': c,
					'id': jsId,
					'elementid': this.id
				},
				update: body,
				onRequest: function () {
					if (Fabrik.debug) {
						fconsole('Fabrik adminelement.js: Adding JS entry', (c + 1).toString());
					}
				},
				onComplete: function (res) {
					body.getElement('textarea[id^="jform_code-"]').addEvent('change', function (e, target) {
						this.setAccordionHeader(e.target.getParent('.actionContainer'));
					}.bind(this));
					this.setAccordionHeader(div);
					this.jsAjaxed++;
					this.updateBootStrap();
					FabrikAdmin.reTip();
				}.bind(this),
				onFailure: function (xhr) {
					fconsole('Fabrik adminelement.js addJavascript: ajax failure: ', xhr);
				},
				onException: function (headerName, value) {
					fconsole('Fabrik adminelement.js addJavascript: ajax exception: ', headerName, value);
				}
			});
			this.jsCounter++;
			Fabrik.requestQueue.add(request);
			this.updateBootStrap();
			FabrikAdmin.reTip();
		},

		setAccordionHeader: function (c) {
			/**
			 * Sets accordion header as follows:
			 *
			 * 1. If action is '' use COM_FABRIK_PLEASE_SELECT, otherwise use "On"
			 * followed by action text
			 *
			 * 2. If code is set, append either comment text from first line if it
			 * exists or "Javascript Inline Code"
			 *
			 * 3. If code is NOT set, append the event, trigger, condition and value
			 * fields
			 **/
			if (typeOf(c) === 'null') {
				return;
			}
			var header = c.getElement('span.pluginTitle');
			var action = c.getElement('select[id^="jform_action-"]');
			if (action.value === '') {
				header.set('html', '<span style="color:red;">' + Joomla.JText._('COM_FABRIK_JS_SELECT_EVENT') + '</span>');
				return;
			}
			var s = 'on ' + action.getSelected()[0].text + ' : ';
			var code = c.getElement('textarea[id^="jform_code-"]');
			var event = c.getElement('select[id^="jform_js_e_event-"]');
			var trigger = c.getElement('select[id^="jform_js_e_trigger-"]');
			var name = document.id('jform_name');
			var value = c.getElement('input[id^="jform_js_e_value-"]');
			var condition = c.getElement('select[id^="jform_js_e_condition-"]');
			var t = '';
			if (code.value.clean() !== '') {
				var first = code.value.split('\n')[0].trim();
				var comment = first.match(/^\/\*(.*)\*\//);
				if (comment) {
					t = comment[1];
				} else {
					t = Joomla.JText._('COM_FABRIK_JS_INLINE_JS_CODE');
				}
				if (code.value.replace(/(['"]).*?[^\\]\1/g, '').test('//')) {
					t += ' &nbsp; <span style="color:red;font-weight:bold;">';
					t += Joomla.JText._('COM_FABRIK_JS_INLINE_COMMENT_WARNING').replace(/ /g, '&nbsp;');
					t += '</span>';
				}
			} else if (event.value && trigger.value && name.value) {
				t  = Joomla.JText._('COM_FABRIK_JS_WHEN_ELEMENT') + ' "' + name.value + '" ';
				if (condition.getSelected()[0].text.test(/hidden|shown/)) {
					t += Joomla.JText._('COM_FABRIK_JS_IS') + ' ';
					t += condition.getSelected()[0].text + ', ';
				} else {
					t += condition.getSelected()[0].text + ' "' + value.value.trim() + '", ';
				}
				var trigtype = trigger.getSelected().getParent('optgroup').get('label')[0].toLowerCase();
				t += event.getSelected()[0].text + ' ' + trigtype.substring(0, trigtype.length - 1);
				t += ' "' + trigger.getSelected()[0].text + '"';
			} else {
				s += '<span style="color:red;">' + Joomla.JText._('COM_FABRIK_JS_NO_ACTION') + '</span>';
			}
			if (t !== '') {
				s += '<span style="font-weight:normal">' + t + '</span>';
			}
			header.set('html', s);
		},

		setParentViz: function () {
			if (this.options.parentid.toInt() !== 0) {
				var myFX = new Fx.Tween('elementFormTable', {
					property: 'opacity',
					duration: 500,
					wait: false
				}).set(0);
				document.id('unlink').addEvent('click', function (e) {
					if (this.checked) {
						myFX.start(0, 1);
					} else {
						myFX.start(1, 0);
					}
				});
			}
			if (document.id('swapToParent')) {
				document.id('swapToParent').addEvent('click', function (e) {
					var f = document.adminForm;
					f.task.value = 'element.parentredirect';
					var to = e.target.className.replace('element_', '');
					f.redirectto.value = to;
					f.submit();
				});
			}
		}
	});
	return fabrikAdminElement;
});
