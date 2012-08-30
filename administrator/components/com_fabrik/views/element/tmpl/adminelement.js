//this array contains all the javascript element plugin objects 
var pluginControllers = [];

var fabrikAdminElement = new Class({
	
	Extends: PluginManager,
	
	Implements: [Options, Events],
	
	options: {
		id: 0,
		parentid: 0,
		jsevents: []
	},

	initialize: function (plugins, options, id) {
		this.parent(plugins, id, 'validationrule');
		this.setOptions(options);
		this.watchPluginDd();
		this.setParentViz();
		
		this.jsCounter = 0;
		this.jsactions = ['focus', 'blur', 'abort', 'click', 'change', 'dblclick', 'keydown', 'keypress', 'keyup', 'mouseup', 'mousedown', 'mouseover', 'select', 'load', 'unload'];
		this.eEvents = ['hide', 'show', 'fadeout', 'fadein', 'slide in', 'slide out', 'slide toggle'];
		this.eTrigger = this.options.elements;
		this.eConditions = ['<', '<=', '==', '>=', '>', '!='];
		if (typeOf(document.id('addJavascript')) === false) {
			fconsole('add js button not found');
		} else {
			document.id('addJavascript').addEvent('click', function (e) {
				e.stop();
				this.addJavascript();
			}.bind(this));
		}
		this.options.jsevents.each(function (opt) {
			this.addJavascript(opt);
		}.bind(this));
		
		document.id('jform_plugin').addEvent('change', function (e) {
			e.stop();
			this.changePlugin(e);
		}.bind(this));
	},
	
	changePlugin: function (e) {
		document.id('plugin-container').empty().adopt(
		new Element('span').set('text', 'Loading....')
		);
		var myAjax = new Request({
			url: 'index.php',
			'evalResponse': false,
			'evalScripts' : function (script, text) {
					this.script = script;
				}.bind(this),
			'data': {
				'option': 'com_fabrik',
				'id': this.options.id,
				'task': 'element.getPluginHTML',
				'format': 'raw',
				'plugin': e.target.get('value')
			},
			'update': document.id('plugin-container'),
			'onComplete': function (r) {
				document.id('plugin-container').set('html', r);
				$exec(this.script);
			}.bind(this)
		}).send();
	},
	
	deleteJS: function (e) {
		e.stop();
		e.target.up(3).dispose();
	},
	
	addJavascript: function (opt) {
		if (typeOf(opt) !== 'object') {
			opt = {'params': {
				js_code: '',
				js_action: '',
				js_e_event: '',
				js_e_trigger: '',
				js_e_condition: '',
				js_e_value: '',
				code: ''
			}};
		}
		opt.code = opt.code ? opt.code : '';
		code = new Element('textarea', {
			'rows': 8,
			'cols': 40,
			'name': 'jform[js_code][]',
			'class': 'inputbox'
		}).set('text', opt.code);
		action = this._makeSel(this.jsCounter, 'jform[js_action][]', this.jsactions, opt.action);
		var evs = this._makeSel(this.jsCounter, 'js_e_event[]', this.eEvents, opt.params.js_e_event, Joomla.JText._('COM_FABRIK_SELECT_DO'));
		var triggers = this._makeSel(this.jsCounter, 'js_e_trigger[]', this.eTrigger, opt.params.js_e_trigger, Joomla.JText._('COM_FABRIK_SELECT_ON'));
		var condition = this._makeSel(this.jsCounter, 'js_e_condition[]', this.eConditions, opt.params.js_e_condition, Joomla.JText._('COM_FABRIK_IS'));
		
		var content = new Element('table', {
			'class': 'paramlist admintable adminform',
			'id': 'jsAction_' + this.jsCounter
		}).adopt(
			new Element('tbody', {'class': 'adminform', 'id': 'jsAction_' + this.jsCounter}).adopt([
				new Element('tr').adopt(new Element('td', {'colspan': 2})),
				new Element('tr').adopt([new Element('td', {'class': 'paramlist_key'}).appendText(Joomla.JText._('COM_FABRIK_ACTION')), new Element('td').adopt(action)]),
				new Element('tr').adopt([new Element('td', {'class': 'paramlist_key'}).appendText(Joomla.JText._('COM_FABRIK_CODE')), new Element('td').adopt(code)]),
				new Element('tr').adopt(new Element('td', {
					'colspan': 2,
					'class': 'paramlist_key',
					'styles': {
						'text-align': 'left'
					}
				}).appendText(Joomla.JText._('COM_FABRIK_OR'))),
				new Element('tr').adopt(new Element('td', {'colspan': 2}).adopt([
					evs, triggers,
					new Element('input', {
						'value': Joomla.JText._('COM_FABRIK_WHERE_THIS'),
						'class': 'readonly',
						'disabled': 'disabled',
						'size': Joomla.JText._('COM_FABRIK_WHERE_THIS').length
					}),
					condition,
					new Element('input', {
						'name': 'js_e_value[]',
						'class': 'inputbox',
						'value': opt.params.js_e_value
					}) 
				])),
				new Element('tr').adopt(new Element('td', {'colspan': 2}).adopt(new Element('a', {
					'href': '#',
					'class': 'removeButton',
					'events': {
						'click': function (e) {
							this.deleteJS(e);
						}.bind(this)
					}
				}).appendText(Joomla.JText._('COM_FABRIK_DELETE'))))
			])
		);
		var div = new Element('div');
		content.inject(div);
		div.inject(document.id('javascriptActions'));
		this.jsCounter ++;
	},
	
	watchPluginDd: function () {
		document.id('jform_plugin').addEvent('change', function (e) {
			e.stop();
			var opt = e.target.get('value');
			$$('.elementSettings').each(function (tab) {
				if (opt === tab.id.replace('page-', '')) {
					tab.setStyles({display: 'block'});
				} else {
					tab.setStyles({display: 'none'}); 
				}
			});
		});
		if (document.id('page-' + this.options.plugin)) {
			document.id('page-' + this.options.plugin).setStyles({display: 'block'});
		}
	},
	
	setParentViz: function () {
		if (this.options.parentid.toInt() !== 0) {
			myFX = new Fx.Tween('elementFormTable', {property: 'opacity', duration: 500, wait: false}).set(0);
			document.id('unlink').addEvent('click', function (e) {
				var s = (this.checked) ? "" : "readonly";
				if (this.checked) {
					myFX.start(0, 1);
				}
				else {
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
	},
	
	getPluginTop: function (plugin, opts) {
		return new Element('tr').adopt(
			new Element('td').adopt([
				new Element('input', {'value': Joomla.JText._('COM_FABRIK_ACTION'), 'size': 3, 'readonly': true, 'class': 'readonly'}),
				this._makeSel('inputbox elementtype', 'jform[validationrule][plugin][]', this.plugins, plugin)
			])
		
		);
	}
});

function setAllCheckBoxes(elName, val) {
	var els = document.getElementsByName(elName);
	var c = els.length; 
	for (var i = 0; i < c; i++) {
		els[i].checked = val;	
	}
}	

function setAllDropDowns(elName, selIndex) {
	els = document.getElementsByName(elName);
	c = els.length; 
	for (var i = 0; i < c; i++) {
		els[i].selectedIndex = selIndex;	
	}		
}		

function setAll(t, elName) {
	els = document.getElementsByName(elName);
	c = els.length;
	for (var i = 0; i < c; i++) {
		els[i].value = t;
	}		
}

function deleteSubElements(sTagId) {
	var oNode = document.id(sTagId);
	oNode.parentNode.removeChild(oNode);
}
