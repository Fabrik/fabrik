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
		this.plugin = '';
		options.element = element;
		this.strElement = element;
		this.loadEvents = []; // need to store these for use if the form is reset
		this.events = $H({}); // was changeEvents
		this.setOptions(options);
		return this.setElement();
	},

	/**
	 * Called when form closed in ajax window
	 * Should remove any events added to Window or Fabrik
	 */
	destroy: function () {

	},

	setElement: function () {
		if (document.id(this.options.element)) {
			this.element = document.id(this.options.element);
			this.setOrigId();
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

	attachedToForm: function () {
		this.attachedToFormParent();
	},

	attachedToFormParent: function () {
		this.setElement();
		if (Fabrik.bootstrapped) {
			this.alertImage = new Element('i.' + this.form.options.images.alert);
			this.successImage = new Element('i.icon-checkmark', {'styles': {'color': 'green'}});
		} else {
			this.alertImage = new Asset.image(this.form.options.images.alert);
			this.alertImage.setStyle('cursor', 'pointer');
			this.successImage = new Asset.image(this.form.options.images.action_check);
		}

		if (this.form.options.images.ajax_loader.contains('<i')) {
			this.loadingImage = new Element('span').set('html', this.form.options.images.ajax_loader);
		} else {
			this.loadingImage = new Asset.image(this.form.options.images.ajax_loader);
		}
		//put ini code in here that can't be put in initialize()
		// generally any code that needs to refer to  this.form, which
		//is only set when the element is assigned to the form.
	},

	/**
	 * Allows you to fire an array of events to element /  subelements,
	 * used in calendar to trigger js events when the calendar closes
	 **/
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
		//use this in mocha forms whose elements (such as database jons) arent loaded
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
	 * Set names/ids/elements ect when the elements group is cloned
	 *
	 * @param   int  id  element id
	 * @since   3.0.7
	 */

	cloneUpdateIds: function (id) {
		this.element = document.id(id);
		this.options.element = id;
	},

	runLoadEvent : function (js, delay) {
		delay = delay ? delay : 0;
		//should use eval and not Browser.exec to maintain reference to 'this'
		if (typeOf(js) === 'function') {
			js.delay(delay);
		} else {
			if (delay === 0) {
				eval(js);
			} else {
				(function () {
					eval(js);
				}.bind(this)).delay(delay);
			}
		}
	},

	/**
	 * called from list when ajax form closed
	 * fileupload needs to remove its onSubmit event
	 * othewise 2nd form submission will use first forms event
	 */
	removeCustomEvents: function () {},

	/**
	 * Was renewChangeEvents() but dont see why change events should be treated
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
			// Don't stop event - means fx's onchange events wouldnt fire.
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

	// Alais to addNewEvent.
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

	onsubmit: function () {
		return true;
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
	getErrorElements: function ()
	{
		return this.getContainer().getElements('.fabrikErrorMessage');
	},

	/**
	 * Get the fx to fade up/down element validation feedback text
	 */
	getValidationFx: function () {
		if (!this.validationFX) {
			this.validationFX = new Fx.Morph(this.getErrorElements()[0], {duration: 500, wait: true});
		}
		return this.validationFX;
	},

	/**
	 * Get all tips attached to the element
	 *
	 * @return array of tips
	 */
	tips: function () {
		var container = this.getContainer();
		return Fabrik.tips.elements.filter(function (t) {
			if (!t.hasClass('fabrikElementContainer')) {
				t = t.getParent('.fabrikElementContainer');
			}
			if (t === container) {
				return true;
			}
		}.bind(this));
	},

	/**
	 * In 3.1 show error messages in tips - avoids jumpy pages with ajax validations
	 */
	addTipMsg: function (msg, klass) {
		var t = this.tips();
		if (t.length === 0) {
			return;
		}
		t = jQuery(t[0]);
		if (typeOf(t.data('popover-saved')) === 'null') {
			t.data('popover-saved',t.data('popover').options.content);
		}
		var li = new Element('li');
		li.set('html', '&nbsp;' + msg);
		klass = klass === null ? 'fabrikError' : klass;
		switch (klass) {
			case 'fabrikError':
				var colour = 'text-error.error';
				li.grab(this.alertImage.clone(), 'top');
				break;
			case 'fabrikSuccess':
				var colour = 'text-success.success';
				li.grab(this.successImage.clone(), 'top');
				break;
		}
		var ul = new Element('ul.validation-notices.' + colour).setProperty("style","list-style:none").adopt(li);
		t.data('popover').options.content = ul.outerHTML;
		t.data('popover').manualShow();
	},

	/**
	 * In 3.1 show/hide error messages in tips - avoids jumpy pages with ajax validations
	 */
	removeTipMsg: function () {
		var t = this.tips();
		if (t.length === 0) {
			return;
		}
		t = jQuery(t[0]);
		t.data('popover').manualHide();
		if (typeOf(t.data('popover-saved')) !== 'null') {
			t.data('popover').options.content = t.data('popover-saved');
			t.data('popover-saved',null);
		}
	},

	setErrorMessage: function (msg, classname, single) {
		single = typeOf(single) !== 'null' ? single : false;
		var container = this.getContainer();
		if (container === false) {
			fconsole('element.js: Could not display error msg for ' + msg + ' no container class found');
			return;
		}
		var errorElements = this.getErrorElements();
		errorElements.each(function (e) {
			e.empty();
			e.removeClass('fabrikHide').removeClass('text-error').removeClass('text-success');
		});
		if (this.successTimer !== null) {
			window.clearTimeout(this.successTimer);
			this.successTimer = null;
		}
		switch (classname) {
			case 'fabrikError':
				container
					.removeClass('success').addClass('error')
					.removeClass('fabrikSuccess').addClass('fabrikError');
				if (Fabrik.bootstrapped && single) {
					this.addTipMsg(msg, classname);
				} else {
					errorElements.each(function (e) {
						e.addClass('text-error');
						e.set('html', '&nbsp;' + msg);
						e.grab(this.alertImage.clone(), 'top');
					}.bind(this));
				}
				break;

			case 'fabrikSuccess':
				container
					.addClass('success').removeClass('error')
					.addClass('FabrikSuccess').removeClass('FabrikError');
				if (Fabrik.bootstrapped && single) {
					if (msg !== '') {
					this.addTipMsg(msg, classname);
					} else {
						this.removeTipMsg();
					}
				} else if (!single) {
					errorElements.each(function (e) {
						e.addClass('text-success');
						e.set('html', '&nbsp;' + msg);
						e.grab(this.successImage.clone(), 'top');
					}.bind(this));
				}
				var delfn = function () {
					this.successTimer = null;
					var container = this.getContainer();
					if (container.hasClass('fabrikSuccess')) {
						errorElements.each(function (e) {
							e.empty();
							e.removeClass('text-success').addClass('fabrikHide');
						});
						container.removeClass('success').removeClass('fabrikSuccess');
						this.removeTipMsg();
					}
				}.bind(this);
				this.successTimer = window.setTimeout(delfn, 5000);
				break;
		}

		/* The following code can be reintegrated into the above code if we want to fade in / out messages
		var fx = this.getValidationFx();
		switch (classname) {
			case 'fabrikError':
				var fx = this.getValidationFx();
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
								classes.each(function (c) {
									container.removeClass(c);
								});
							}
						});
					}
				});
				break;
		} */
	},

	setOrigId: function ()
	{
		// $$$ added inRepeatGroup option, as repeatCounter > 0 doesn't help
		// if element is in first repeat of a group
		//if (this.options.repeatCounter > 0) {
		if (this.options.inRepeatGroup) {
			var e = this.options.element;
			this.origid = e.substring(0, e.length - 1 - this.options.repeatCounter.toString().length);
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
	 * element belongs to, returns false if not in a repeat gorup
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

	/** Paul
	 * This has been moved from form.js in order to allow multiple single-field validations to
	 * run in parallel if user tabs through validated fields rapidly.
	 *
	 * Note: This method has been split in such a way that doValidation can be called by those
	 * elements which call doElementValidation explicitly (calc, date, password).
	 * Once this has been done for all elements, this can be cleaned up to remove replacetxt hacks.
	 * It should also have a better interface for dealing with elements such as date and dbjoin
	 * where the actual input (raw) field is hidden and a UI input field is presented to the user.
	 **/
	doValidation: function (e, subEl, id, spinId) {
		this.spinId = spinId;
		var d = $H(this.form.getFormData());
		d.set('task', 'form.ajax_validate');
		d.set('fabrik_ajax', '1');
		d.set('format', 'raw');

		d = this.form.prepareRepeatsForAjax(d);

		// $$$ hugh - nasty hack, because validate() in form model will always use _0 for
		// repeated id's
		var origid = id;
		el = this.form.formElements.get(id);
		if (el.origid) {
			origid = el.origid + '_0';
		}
		//var origid = el.origid ? el.origid : id;
		el.options.repeatCounter = el.options.repeatCounter ? el.options.repeatCounter : 0;
		var url = 'index.php?option=com_fabrik&form_id=' + this.form.id;
		Fabrik.fireEvent('fabrik.form.element.validation.start', [this.form, el, event]);
		if (this.form.result === false) {
			this.form.result = true;
			return;
		}
		if (this.ajax) {
			this.ajax.cancel();
		}
		this.ajax = new Request({
			url: url,
			data: d,

			onRequest: function(){
				Fabrik.loader.start(this.spinId, Joomla.JText._('COM_FABRIK_VALIDATING'));
			}.bind(this),

			onCancel: function(){
				Fabrik.loader.stop(this.spinId);
				this.ajax = null;
			}.bind(this),

			onComplete: function(){
				Fabrik.loader.stop(this.spinId);
				this.ajax = null;
			}.bind(this),

			onFailure: function(xhr){
				fconsole('Fabrik element::doValidation Ajax failure: Code ' + xhr.status + ': ' + xhr.statusText);
				this.setErrorMessage('Validation ajax call failed', 'fabrikError');
				this.form.formElements.each(function (el, key) {
					el.afterAjaxValidation();
				});
			}.bind(this),

			onSuccess: function (r) {
				if (typeOf(r) === 'null') {
					fconsole('Fabrik element::doValidation Ajax response empty.');
					this.setErrorMessage('Validation ajax call response empty', 'fabrikError');
					return;
				}
				this.form.formElements.each(function (el, key) {
					el.afterAjaxValidation();
				});
				r = JSON.decode(r);
				Fabrik.fireEvent('fabrik.form.element.validation.complete', [this.form, r, id, origid]);
				if (this.form.result === false) {
					this.form.result = true;
					return;
				}
				var el = this.form.formElements.get(id);
				if ((typeOf(r.modified[origid]) !== 'null')) {
					el.update(r.modified[origid]);
				}
				if (typeOf(r.errors[origid]) !== 'null') {
					var msg = r.errors[origid].flatten().join('<br />');
					if (msg !== '') {
						this.setErrorMessage(msg, 'fabrikError', true);
					} else {
						this.setErrorMessage('', 'fabrikSuccess', true);
					}
				} else {
					this.setErrorMessage('', 'fabrikSuccess', true);
				}
			}.bind(this)
		}).send();
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
			onSuccess: function (r) {
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