/**
 * Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $H:true,unescape:true,Asset:true */

var FbElement =  new Class({

	Implements: [Events, Options],

	options : {
		element: null,
		defaultVal: '',
		value: '',
		label: '',
		editable: false,
		isJoin: false,
		joinId: 0
	},

	/**
	 * Ini the element
	 *
	 * @return  bool  false if document.id(this.options.element) not found
	 */

	initialize: function (element, options) {
		this.setPlugin('');
		options.element = element;
		this.strElement = element;
		this.loadEvents = []; // need to store these for use if the form is reset
		this.events = $H({}); // was changeEvents
		this.setOptions(options);
		// If this element is a 'chosen' select, we need to relay the jQuery change event to Moo
		if (document.id(this.options.element + '_chzn')) {
			var changeEvent = this.getChangeEvent();
			jQuery('#' + this.options.element).on('change', {changeEvent: changeEvent}, function (event) {
				document.id(this.id).fireEvent(event.data.changeEvent, new Event.Mock(document.id(this.id), event.data.changeEvent));
			});
		}
		return this.setElement();
	},

	/**
	 * Called when form closed in ajax window
	 * Should remove any events added to Window or Fabrik
	 */
	destroy: function () {

	},

	setPlugin: function(plugin) {
		if (typeOf(this.plugin) === 'null' || this.plugin === '') {
			this.plugin = plugin;
		}
	},
	
	getPlugin: function() {
		return this.plugin;
	},
	
	setElement: function () {
		if (document.id(this.options.element)) {
			this.element = document.id(this.options.element);
			this.setorigId();
			return true;
		}
		return false;
	},

	get: function (v) {
		if (v === 'value') {
			return this.getValue();
		}
	},

	/**
	 * Sets the element key used in Fabrik.blocks.form_X.formElements
	 * Overwritten by any element which performs a n-n join (multi ajax fileuploads, dbjoins as checkboxes)
	 *
	 * @since   3.0.7
	 *
	 * @return  string
	 */
	getFormElementsKey: function (elId) {
		this.baseElementId = elId;
		return elId;
	},

	attachedToForm: function ()
	{
		this.setElement();
		if (Fabrik.bootstrapped) {
			this.alertImage = new Element('i.' + this.form.options.images.alert);
			this.successImage = new Element('i.icon-checkmark', {'styles': {'color': 'green'}});
		} else {
			this.alertImage = new Asset.image(this.form.options.images.alert);
			this.alertImage.setStyle('cursor', 'pointer');
			this.successImage = new Asset.image(this.form.options.images.action_check);
		}

		if (jQuery(this.form.options.images.ajax_loader).data('isicon')) {
			this.loadingImage = new Element('span').set('html', this.form.options.images.ajax_loader);
		} else {
			this.loadingImage = new Asset.image(this.form.options.images.ajax_loader);
		}
		//put ini code in here that can't be put in initialize()
		// generally any code that needs to refer to  this.form, which
		//is only set when the element is assigned to the form.
	},

	/** allows you to fire an array of events to element /  subelements, used in calendar to trigger js events when the calendar closes **/
	fireEvents: function (evnts) {
		if (this.hasSubElements()) {
			this._getSubElements().each(function (el) {
				Array.from(evnts).each(function (e) {
					el.fireEvent(e);
				}.bind(this));
			}.bind(this));
		} else {
			Array.from(evnts).each(function (e) {
				if (this.element) {
					this.element.fireEvent(e);
				}
			}.bind(this));
		}
	},

	getElement: function ()
	{
		//use this in mocha forms whose elements (such as database joins) aren't loaded
		//when the class is ini'd
		if (typeOf(this.element) === 'null') {
			this.element = document.id(this.options.element);
		}
		return this.element;
	},

	//used for elements like checkboxes or radio buttons
	_getSubElements: function () {
		var element = this.getElement();
		if (typeOf(element) === 'null') {
			return false;
		}
		this.subElements = element.getElements('.fabrikinput');
		return this.subElements;
	},

	hasSubElements: function () {
		this._getSubElements();
		if (typeOf(this.subElements) === 'array' || typeOf(this.subElements) === 'elements') {
			return this.subElements.length > 0 ? true : false;
		}
		return false;
	},

	unclonableProperties: function ()
	{
		return ['form'];
	},

	/**
	 * Set names/ids/elements etc. when the elements group is cloned
	 *
	 * @param   int  id  element id
	 * @since   3.0.7
	 */

	cloneUpdateIds: function (id) {
		this.element = document.id(id);
		this.options.element = id;
	},

	runLoadEvent: function (js, delay) {
		delay = delay ? delay : 0;
		//should use eval and not Browser.exec to maintain reference to 'this'
		if (typeOf(js) === 'function') {
			js.delay(delay);
		} else {
			if (delay === 0) {
				eval(js);
			} else {
				(function () {
					console.log('delayed calling runLoadEvent for ' + delay);
					eval(js);
				}.bind(this)).delay(delay);
			}
		}
	},

	/**
	 * called from list when ajax form closed
	 * fileupload needs to remove its onSubmit event
	 * otherwise 2nd form submission will use first forms event
	 */
	removeCustomEvents: function () {},

	/**
	 * Was renewChangeEvents() but don't see why change events should be treated
	 * differently to other events?
	 *
	 * @since 3.0.7
	 */
	renewEvents: function () {
		this.events.each(function (fns, type) {
			this.element.removeEvents(type);
			fns.each(function (js) {
				this.addNewEventAux(type, js);
			}.bind(this));
		}.bind(this));
	},

	addNewEventAux: function (action, js) {
		this.element.addEvent(action, function (e) {
			// Don't stop event - means fx's onchange events wouldn't fire.
			typeOf(js) === 'function' ? js.delay(0, this, this) : eval(js);
		}.bind(this));
	},

	addNewEvent: function (action, js) {
		if (action === 'load') {
			this.loadEvents.push(js);
			this.runLoadEvent(js);
		} else {
			if (!this.element) {
				this.element = document.id(this.strElement);
			}
			if (this.element) {
				if (!Object.keys(this.events).contains(action)) {
					this.events[action] = [];
				}
				this.events[action].push(js);
				this.addNewEventAux(action, js);
			}
		}
	},

	// Alias to addNewEvent.
	addEvent: function (action, js) {
		this.addNewEvent(action, js);
	},

	validate: function () {},

	//store new options created by user in hidden field
	addNewOption: function (val, label)
	{
		var a;
		var added = document.id(this.options.element + '_additions').value;
		var json = {'val': val, 'label': label};
		if (added !== '') {
			a = JSON.decode(added);
		} else {
			a = [];
		}
		a.push(json);
		var s = '[';
		for (var i = 0; i < a.length; i++) {
			s += JSON.encode(a[i]) + ',';
		}
		s = s.substring(0, s.length - 1) + ']';
		document.id(this.options.element + '_additions').value = s;
	},

	getLabel: function () {
		return this.options.label;
	},

	/**
	 * set the label (uses textContent attribute, prolly won't work on IE < 9)
	 */
	setLabel: function (label) {
		this.options.label = label;
		var c = this.getLabelElement();
		if (c) {
			c[0].textContent = label;
		}
	},

	//below functions can override in plugin element classes

	update: function (val) {
		//have to call getElement() - otherwise inline editor doesn't work when editing 2nd row of data.
		if (this.getElement()) {
			if (this.options.editable) {
				this.element.value = val;
			} else {
				this.element.innerHTML = val;
			}
		}
	},

	/**
	 * $$$ hugh - testing something for join elements, where in some corner cases,
	 * like reverse Geocoding in the map element, we need to update elements that might be
	 * joins, and all we have is the label (like "Austria" for country).  So am overriding this
	 * new function in the join element, with code that finds the first occurrence of the label,
	 * and sets the value accordingly.  But all we need to do here is make it a wrapper for update().
	 */
	updateByLabel: function (label) {
		this.update(label);
	},

	// Alias to update()
	set: function (val) {
		this.update(val);
	},

	getValue: function () {
		if (this.element) {
			if (this.options.editable) {
				return this.element.value;
			} else {
				return this.options.value;
			}
		}
		return false;
	},

	reset: function ()
	{
		this.resetEvents();
		if (this.options.editable === true) {
			this.update(this.options.defaultVal);
		}
	},

	resetEvents: function ()
	{
		this.loadEvents.each(function (js) {
			this.runLoadEvent(js, 100);
		}.bind(this));
	},

	clear: function ()
	{
		this.update('');
	},

	/**
	 * Called from FbFormSubmit
	 *
	 * @params   function  cb  Callback function to run when the element is in an
	 *                         acceptable state for the form processing to continue
	 *                         Should use cb(true) to allow for the form submission,
	 *                         cb(false) stops the form submission.
	 *
	 * @return  void
	 */
	onsubmit: function (cb) {
		if (cb) {
			cb(true);
		}
	},

	/**
	 * As ajax validations call onsubmit to get the correct date, we need to
	 * reset the date back to the display date when the validation is complete
	 */
	afterAjaxValidation: function () {

	},

	/**
	 * Run when the element is cloned in a repeat group
	 */
	cloned: function (c) {
		this.renewEvents();
		if (this.element.hasClass('chzn-done')) {
			this.element.removeClass('chzn-done');
			this.element.addClass('chzn-select');
			this.element.getParent().getElement('.chzn-container').destroy();
			jQuery('#' + this.element.id).chosen();
			var changeEvent = this.getChangeEvent();
			jQuery('#' + this.options.element).on('change', {changeEvent: changeEvent}, function (event) {
				document.id(this.id).fireEvent(event.data.changeEvent, new Event.Mock(event.data.changeEvent, document.id(this.id)));
			});
		}
	},

	/**
	 * Run when the element is decloled from the form as part of a deleted repeat group
	 */
	decloned: function (groupid) {
	},

	/**
	 * get the wrapper dom element that contains all of the elements dom objects
	 */
	getContainer: function ()
	{
		return typeOf(this.element) === 'null' ? false : this.element.getParent('.fabrikElementContainer');
	},

	/**
	 * get the dom element which shows the error messages
	 */
	getErrorElement: function ()
	{
		return this.getContainer().getElements('.fabrikErrorMessage');
	},

	/**
	 * get the dom element which contains the label
	 */
	getLabelElement: function ()
	{
		return this.getContainer().getElements('.fabrikLabel');
	},

	/**
	 * Get the fx to fade up/down element validation feedback text
	 */
	getValidationFx: function () {
		if (!this.validationFX) {
			this.validationFX = new Fx.Morph(this.getErrorElement()[0], {duration: 500, wait: true});
		}
		return this.validationFX;
	},

	/**
	 * Get all tips attached to the element
	 *
	 * @return array of tips
	 */
	tips: function () {
		return Fabrik.tips.elements.filter(function (t) {
			if (t === this.getContainer() || t.getParent() === this.getContainer()) {
				return true;
			}
		}.bind(this));
	},

	/**
	 * In 3.1 show error messages in tips - avoids jumpy pages with ajax validations
	 */
	addTipMsg: function (msg, klass) {
		// Append notice to tip
		klass = klass ? klass : 'error';
		var ul, a, t = this.tips();
		if (t.length === 0) {
			return;
		}
		t = jQuery(t[0]);

		if (t.attr(klass) === undefined) {
			t.data('popover').show();
			t.attr(klass, msg);
			a = t.data('popover').tip().find('.popover-content');

			var d = new Element('div');
			d.set('html', a.html());
			var li = new Element('li.' + klass);
			li.set('html', msg);
			new Element('i.' + this.form.options.images.alert).inject(li, 'top');
			d.getElement('ul').adopt(li);
			t.attr('data-content', unescape(d.get('html')));
			t.data('popover').setContent();
			t.data('popover').options.content = d.get('html');
			t.data('popover').hide();
		}
	},

	/**
	 * In 3.1 show/hide error messages in tips - avoids jumpy pages with ajax validations
	 */
	removeTipMsg: function (index) {
		var klass = klass ? klass : 'error',
		t = this.tips();
		t = jQuery(t[0]);
		if (t.attr(klass) !== undefined) {
			t.data('popover').show();
			a = t.data('popover').tip().find('.popover-content');
			var d = new Element('div');
			d.set('html', a.html());
			var li = d.getElement('li.error');
			if (li) {
				li.destroy();
			}
			t.attr('data-content', d.get('html'));
			t.data('popover').setContent();
			t.data('popover').options.content = d.get('html');
			t.data('popover').hide();
			t.removeAttr(klass);
		}
	},

	setErrorMessage: function (msg, classname) {
		var a, m;
		var classes = ['fabrikValidating', 'fabrikError', 'fabrikSuccess'];
		var container = this.getContainer();
		if (container === false) {
			console.log('Notice: couldn not set error msg for ' + msg + ' no container class found');
			return;
		}
		classes.each(function (c) {
			var r = classname === c ? container.addClass(c) : container.removeClass(c);
		});
		var errorElements = this.getErrorElement();
		errorElements.each(function (e) {
			e.empty();
		});
		switch (classname) {
		case 'fabrikError':
			Fabrik.loader.stop(this.element);
			if (Fabrik.bootstrapped) {
				this.addTipMsg(msg);
			} else {
				a = new Element('a', {'href': '#', 'title': msg, 'events': {
					'click': function (e) {
						e.stop();
					}
				}}).adopt(this.alertImage);

				Fabrik.tips.attach(a);
			}
			errorElements[0].adopt(a);

			container.removeClass('success').removeClass('info').addClass('error');

			// If tmpl has additional error message divs (e.g labels above) then set html msg there
			if (errorElements.length > 1) {
				for (i = 1; i < errorElements.length; i ++) {
					errorElements[i].set('html', msg);
				}
			}

			break;
		case 'fabrikSuccess':
			container.addClass('success').removeClass('info').removeClass('error');
			if (Fabrik.bootstrapped) {
				Fabrik.loader.stop(this.element);
				this.removeTipMsg();
			} else {

				errorElements[0].adopt(this.successImage);
				var delFn = function () {
					errorElements[0].addClass('fabrikHide');
					container.removeClass('success');
				};
				delFn.delay(700);
			}
			break;
		case 'fabrikValidating':
			container.removeClass('success').addClass('info').removeClass('error');
			//errorElements[0].adopt(this.loadingImage);
			Fabrik.loader.start(this.element, msg);
			break;
		}

		this.getErrorElement().removeClass('fabrikHide');
		var parent = this.form;
		if (classname === 'fabrikError' || classname === 'fabrikSuccess') {
			parent.updateMainError();
		}

		var fx = this.getValidationFx();
		switch (classname) {
		case 'fabrikValidating':
		case 'fabrikError':
			fx.start({
				'opacity': 1
			});
			break;
		case 'fabrikSuccess':
			fx.start({
				'opacity': 1
			}).chain(function () {
				// Only fade out if its still the success message
				if (container.hasClass('fabrikSuccess')) {
					container.removeClass('fabrikSuccess');
					this.start.delay(700, this, {
						'opacity': 0,
						'onComplete': function () {
							container.addClass('success').removeClass('error');
							parent.updateMainError();
							classes.each(function (c) {
								container.removeClass(c);
							});
						}
					});
				}
			});
			break;
		}
	},

	setorigId: function ()
	{
		// $$$ added inRepeatGroup option, as repeatCounter > 0 doesn't help
		// if element is in first repeat of a group
		//if (this.options.repeatCounter > 0) {
		if (this.options.inRepeatGroup) {
			var e = this.options.element;
			this.origId = e.substring(0, e.length - 1 - this.options.repeatCounter.toString().length);
		}
	},

	decreaseName: function (delIndex) {
		var element = this.getElement();
		if (typeOf(element) === 'null') {
			return false;
		}
		if (this.hasSubElements()) {
			this._getSubElements().each(function (e) {
				e.name = this._decreaseName(e.name, delIndex);
				e.id = this._decreaseId(e.id, delIndex);
			}.bind(this));
		} else {
			if (typeOf(this.element.name) !== 'null') {
				this.element.name = this._decreaseName(this.element.name, delIndex);
			}
		}
		if (typeOf(this.element.id) !== 'null') {
			this.element.id = this._decreaseId(this.element.id, delIndex);
		}
		return this.element.id;
	},

	/**
	 * @param	string	name to decrease
	 * @param	int		delete index
	 * @param	string	name suffix to keep (used for db join autocomplete element)
	 */

	_decreaseId: function (n, delIndex, suffix) {
		var suffixFound = false;
		suffix = suffix ? suffix : false;
		if (suffix !== false) {
			if (n.contains(suffix)) {
				n = n.replace(suffix, '');
				suffixFound = true;
			}
		}
		var bits = Array.from(n.split('_'));
		var i = bits.getLast();
		if (typeOf(i.toInt()) === 'null') {
			return bits.join('_');
		}
		if (i >= 1  && i > delIndex) {
			i --;
		}
		bits.splice(bits.length - 1, 1, i);
		var r = bits.join('_');
		if (suffixFound) {
			r += suffix;
		}
		this.options.element = r;
		return r;
	},

	/**
	 * @param	string	name to decrease
	 * @param	int		delete index
	 * @param	string	name suffix to keep (used for db join autocomplete element)
	 */

	_decreaseName: function (n, delIndex, suffix) {

		suffixFound = false;
		suffix = suffix ? suffix : false;
		if (suffix !== false) {
			if (n.contains(suffix)) {
				n = n.replace(suffix, '');
				suffixFound = true;
			}
		}
		var namebits = n.split('[');
		var i = namebits[1].replace(']', '').toInt();
		if (i >= 1  && i > delIndex) {
			i --;
		}
		i = i + ']';

		namebits[1] = i;
		var r = namebits.join('[');
		if (suffixFound) {
			r += suffix;
		}
		return r;
	},

	/**
	 * determine which duplicated instance of the repeat group the
	 * element belongs to, returns false if not in a repeat group
	 * other wise an integer
	 */
	getRepeatNum: function () {
		if (this.options.inRepeatGroup === false) {
			return false;
		}
		return this.element.id.split('_').getLast();
	},

	getBlurEvent: function () {
		return this.element.get('tag') === 'select' ? 'change' : 'blur';
	},

	getChangeEvent: function () {
		return 'change';
	},

	select: function () {},
	focus: function () {},

	hide: function () {
		var c = this.getContainer();
		if (c) {
			c.hide();
		}
	},

	show: function () {
		var c = this.getContainer();
		if (c) {
			c.show();
		}
	},

	toggle: function () {
		var c = this.getContainer();
		if (c) {
			c.toggle();
		}
	},

	/**
	 * Used to find element when form clones a group
	 * WYSIWYG text editor needs to return something specific as options.element has to use name
	 * and not id.
	 */
	getCloneName: function () {
		return this.options.element;
	},

	/**
	 * Testing some stuff to try and get maps to display properly when they are in the
	 * tab template.  If a map is in a tab which isn't selected on page load, the map
	 * will not render properly, and needs to be refreshed when the tab it is in is selected.
	 * NOTE that this stuff is very specific to the Fabrik tabs template, using J!'s tabs.
	 */

	doTab: function (event) {
		(function () {
			this.redraw();
			if (!Fabrik.bootstrapped) {
				this.options.tab_dt.removeEvent('click', function (e) {
					this.doTab(e);
				}.bind(this));
			}
		}.bind(this)).delay(500);
	},

	/**
	 * Tabs mess with element positioning - some element (googlemaps, file upload) need to redraw themselves
	 * when the tab is clicked
	 */
	watchTab: function () {
		var c = Fabrik.bootstrapped ? '.tab-pane' : '.current',
		a, tab_dl;
		var tab_div = this.element.getParent(c);
		if (tab_div) {
			if (Fabrik.bootstrapped) {
				a = document.getElement('a[href$=#' + tab_div.id + ']');
				tab_dl = a.getParent('ul.nav');
				tab_dl.addEvent('click:relay(a)', function (event, target) {
					this.doTab(event);
				}.bind(this));
			} else {
				tab_dl = tab_div.getPrevious('.tabs');
				if (tab_dl) {
					this.options.tab_dd = this.element.getParent('.fabrikGroup');
					if (this.options.tab_dd.style.getPropertyValue('display') === 'none') {
						this.options.tab_dt = tab_dl.getElementById('group' + this.groupid + '_tab');
						if (this.options.tab_dt) {
							this.options.tab_dt.addEvent('click', function (e) {
								this.doTab(e);
							}.bind(this));
						}
					}
				}
			}
		}
	},
	/**
	 * When a form/details view is updating its own data, then should we use the raw data or the html?
	 * Raw is used for cdd/db join elements
	 *
	 * @returns {boolean}
	 */
	updateUsingRaw: function () {
		return false;
	}
});

/**
 * @author Rob
 * contains methods that are used by any element which manipulates files/folders
 */


var FbFileElement = new Class({

	Extends: FbElement,
	ajaxFolder: function ()
	{
		this.folderlist = [];
		if (typeOf(this.element) === 'null') {
			return;
		}
		var el = this.element.getParent('.fabrikElement');
		this.breadcrumbs = el.getElement('.breadcrumbs');
		this.folderdiv = el.getElement('.folderselect');
		this.slider = new Fx.Slide(this.folderdiv, {duration: 500});
		this.slider.hide();
		this.hiddenField = el.getElement('.folderpath');
		el.getElement('.toggle').addEvent('click', function (e) {
			e.stop();
			this.slider.toggle();
		}.bind(this));
		this.watchAjaxFolderLinks();
	},


	watchAjaxFolderLinks: function ()
	{
		this.folderdiv.getElements('a').addEvent('click', function (e) {
			this.browseFolders(e);
		}.bind(this));
		this.breadcrumbs.getElements('a').addEvent('click', function (e) {
			this.useBreadcrumbs(e);
		}.bind(this));
	},


	browseFolders: function (e) {
		e.stop();
		this.folderlist.push(e.target.get('text'));
		var dir = this.options.dir + this.folderlist.join(this.options.ds);
		this.addCrumb(e.target.get('text'));
		this.doAjaxBrowse(dir);
	},

	useBreadcrumbs: function (e)
	{
		e.stop();
		var found = false;
		var c = e.target.className;
		this.folderlist = [];
		var res = this.breadcrumbs.getElements('a').every(function (link) {
			if (link.className === c) {
				return false;
			}
			this.folderlist.push(e.target.innerHTML);
			return true;
		}, this);

		var home = [this.breadcrumbs.getElements('a').shift().clone(),
		this.breadcrumbs.getElements('span').shift().clone()];
		this.breadcrumbs.empty();
		this.breadcrumbs.adopt(home);
		this.folderlist.each(function (txt) {
			this.addCrumb(txt);
		}, this);
		var dir = this.options.dir + this.folderlist.join(this.options.ds);
		this.doAjaxBrowse(dir);
	},

	doAjaxBrowse: function (dir) {

		var data = {'dir': dir,
			'option': 'com_fabrik',
			'format': 'raw',
			'task': 'plugin.pluginAjax',
			'plugin': 'fileupload',
			'method': 'ajax_getFolders',
			'element_id': this.options.id
		};
		new Request({ url: '',
			data: data,
			onComplete: function (r) {
				r = JSON.decode(r);
				this.folderdiv.empty();

				r.each(function (folder) {
					new Element('li', {'class': 'fileupload_folder'}).adopt(
					new Element('a', {'href': '#'}).set('text', folder)).inject(this.folderdiv);
				}.bind(this));
				if (r.length === 0) {
					this.slider.hide();
				} else {
					this.slider.slideIn();
				}
				this.watchAjaxFolderLinks();
				this.hiddenField.value =  '/' + this.folderlist.join('/') + '/';
				this.fireEvent('onBrowse');
			}.bind(this)
		}).send();
	},


	addCrumb: function (txt) {
		this.breadcrumbs.adopt(
		new Element('a', {'href': '#', 'class': 'crumb' + this.folderlist.length}).set('text', txt),
		new Element('span').set('text', ' / ')
		);
	}
});