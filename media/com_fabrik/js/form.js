/**
 * Form
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $H:true,unescape:true */

var FbForm = new Class({

	Implements: [Options, Events],

	options: {
		'rowid': '',
		'admin': false,
		'ajax': false,
		'primaryKey': null,
		'error': '',
		'submitOnEnter': false,
		'updatedMsg': 'Form saved',
		'pages': [],
		'start_page': 0,
		'ajaxValidation': false,
		'customJsAction': '',
		'plugins': [],
		'inlineMessage': true,
		'images': {
			'alert': '',
			'action_check': '',
			'ajax_loader': ''
		}
	},

//***********************************************************
// Initialisation code
//***********************************************************/

	initialize: function (id, options) {
		this.id = id;
		// $$$ hugh - seems options.rowid can be null in certain corner cases, so defend against that
		// IE8 if rowid isn't set here its most likely because you are rendering as a J article plugin
		// and have done:
		//     <p>{fabrik view=form id=1}</p>
		// Form block level elements should NOT be encased in <p>'s
		if (typeOf(options.rowid) === 'null') {
			options.rowid = '';
		}
		this.result = true; //set this to false in window.fireEvents to stop current action (eg stop form submission)
		this.setOptions(options);
		this.plugins = this.options.plugins;
		this.options.pages = $H(this.options.pages);
		this.subGroups = $H({});
		this.currentPage = this.options.start_page;
		this.formElements = $H({});
		this.elements = this.formElements;
		this.duplicatedGroups = $H({});
		this.fx = {};
		this.fx.elements = [];
		this.fx.validations = {};
		// Delegated element events
		this.events = {};

		this.setupAll();
	},

	setupAll: function () {
		if (!this.getForm()) {
			return;
		}
		this.watchSubmit();
		if (this.options.ajax || this.options.submitOnEnter === false) {
			this.stopEnterSubmitting();
		}
		this.watchClearSession();
		this.watchGoBackButton();
		this.watchAddOptions();
		// Enable Save/Apply for new page only when all pages / tabs visited
		// Prevent switch to new Page / Tab if errors on this one.
		if (this.isTabbed())
		{
			this.setupTabs();
		} else {
			this.setSubmitApplyStatus();
			this.setupPages();
		}
		this.winScroller = new Fx.Scroll(window);
		this.setStartHiddenGroups();
		this.setRepeatGroupMarkers();
		// Prev/Next RECORD buttons (as opposed to PAGE) appears obsolete as the classes
		// referred to (.previous-record and .next-record) are not now used anywhere in Fabrik.
		// this.testPrevNext();
		this.setMozBoxWidths();
		this.watchGroupButtons();
		this.duplicateGroupsToMin();
	},

	watchSubmit: function () {
		var submit = this._getButton('submit');
		if (!submit) {
			return;
		}

		if (this.options.ajax) {
			var apply = this._getButton('apply');
			var copy = this._getButton('Copy');
			([apply, submit, copy]).each(function (btn) {
				if (typeOf(btn) !== 'null') {
					btn.addEvent('click', function (e) {
						this.doSubmit(e, btn);
					}.bind(this));
				}
			}.bind(this));
		} else {
			this.form.addEvent('submit', function (e) {
				this.doSubmit(e);
			}.bind(this));
		}

		var del = this._getButton('delete');
		if (del) {
			del.addEvent('click', function (e) {
				if (confirm(Joomla.JText._('COM_FABRIK_CONFIRM_DELETE_1'))) {

					var res = Fabrik.fireEvent('fabrik.form.delete', [this, this.options.rowid]).eventResults;
					if (typeOf(res) === 'null' || res.length === 0 || !res.contains(false)) {
						this.form.getElement('input[name=task]').value = this.options.admin ? 'form.delete' : 'delete';
					} else {
						e.stop();
						return false;
					}

				} else {
					return false;
				}
			}.bind(this));
		}
	},

	stopEnterSubmitting: function () {
		var inputs = this.form.getElements('input.fabrikinput[type!=hidden]');
		inputs.each(function (el, i) {
			el.addEvent('keypress', function (e) {
				if (e.key === 'enter') {
					e.stop();
					if (inputs[i + 1]) {
						inputs[i + 1].focus();
					}
					//last one?
					if (i === inputs.length - 1) {
						this._getButton('submit').focus();
					}
				}
			}.bind(this));
		}.bind(this));
	},

	watchClearSession: function () {
		if (this.form && this.form.getElement('.clearSession')) {
			this.form.getElement('.clearSession').addEvent('click', function (e) {
				e.stop();
				this.form.getElement('input[name=task]').value = 'removeSession';
				this.clearForm();
				this.form.submit();
			}.bind(this));
		}
	},

	/**
	 * Go back button in ajax pop up window should close the window
	 **/
	watchGoBackButton: function () {
		if (this.options.ajax) {
			var goback = this.getForm().getElement('input[name=Goback]');
			if (typeOf(goback) === 'null') {
				return;
			}
			goback.addEvent('click', function (e) {
				e.stop();
				if (Fabrik.Windows[this.options.fabrik_window_id]) {
					Fabrik.Windows[this.options.fabrik_window_id].close();
				}
				else {
					// $$$ hugh - http://fabrikar.com/forums/showthread.php?p=166140#post166140
					window.history.back();
				}
			}.bind(this));
		}
	},

	watchAddOptions: function () {
		this.fx.addOptions = [];
		this.getForm().getElements('.addoption').each(function (d) {
			var a = d.getParent('.fabrikElementContainer').getElement('.toggle-addoption');
			var mySlider = new Fx.Slide(d, {
				duration: 500
			});
			mySlider.hide();
			a.addEvent('click', function (e) {
				e.stop();
				mySlider.toggle();
			});
		});
	},

	isTabbed: function() {
		// Check if this is a bootstrap tabbed form
		this.tabbed = false;
		if (this.form) {
			if (this.form.getElement('ul.nav-tabs')) {
				this.tabbed = true;
			}
		}
		return this.tabbed;
	},

	setupTabs: function () {
		this.form.getElement('.nav-tabs').getElements('a').each(function (a) {
			jQuery(a).on('show', function (e) {
				this.tabValidate(e, e.target, e.relatedTarget);
			}.bind(this));
		}.bind(this));
	},

	setupPages: function () {
		var submit, p, firstGroup;
		if (this.options.pages.getKeys().length > 1) {

			// Wrap each page in its own div
			this.options.pages.each(function (page, i) {
				p = new Element('div', {
					'class': 'page',
					'id': 'page_' + i
				});
				firstGroup = document.id('group' + page[0]);
				if (typeOf(firstGroup) !== 'null') {
					p.inject(firstGroup, 'before');
					page.each(function (group) {
						p.adopt(document.id('group' + group));
					});
				}
			});
			if (typeOf(document.getElement('.fabrikPagePrevious')) !== 'null') {
				this.form.getElement('.fabrikPagePrevious').disabled = "disabled";
				this.form.getElement('.fabrikPagePrevious').addEvent('click', function (e) {
					this.doPageNav(e, -1);
				}.bind(this));
			}
			if (typeOf(document.getElement('.fabrikPageNext')) !== 'null') {
				this.form.getElement('.fabrikPageNext').addEvent('click', function (e) {
					this.doPageNav(e, 1);
				}.bind(this));
			}
			this.setPageButtons();
			this.hideOtherPages();
		}
	},

	setSubmitApplyStatus: function () {
			if (this.options.rowid === '') {
				this.disableSubmitApply();
			}
	},

	setStartHiddenGroups: function() {
		$H(this.options.hiddenGroup).each(function (v, k) {
			if (v === true && typeOf(document.id('group' + k)) !== 'null') {
				var subGroup = document.id('group' + k).getElement('.fabrikSubGroup');
				this.subGroups.set(k, subGroup.cloneWithIds());
				this.hideLastGroup(k, subGroup);
			}
		}.bind(this));
	},

	setRepeatGroupMarkers: function() {
		// get an int from which to start incrementing for each repeated group id
		// don't ever decrease this value when deleting a group as it will cause all sorts of
		// reference chaos with cascading dropdowns etc
		this.repeatGroupMarkers = $H({});
		this.form.getElements('.fabrikGroup').each(function (group) {
			var id = group.id.replace('group', '');
			var c = group.getElements('.fabrikSubGroup').length;
			//if no joined repeating data then c should be 0 and not 1
			if (c === 1) {
				if (group.getElement('.fabrikSubGroupElements').getStyle('display') === 'none') {
					c = 0;
				}
			}
			this.repeatGroupMarkers.set(id, c);
		}.bind(this));
	},

	setMozBoxWidths: function () {
		if (Browser.firefox && this.form) {
			// firefox treats display:-moz-box as display:-moz-box-inline we have to explicitly set widths
			this.getForm().getElements('.fabrikElementContainer > .displayBox').each(function (b) {
				var computed = b.getParent().getComputedSize();
				var x = b.getParent().getSize().x - (computed.computedLeft + computed.computedRight); //remove margins/paddings from width
				var w = b.getParent().getSize().x === 0 ? 400 : x;
				b.setStyle('width', w + 'px');
				var e = b.getElement('.fabrikElement');
				if (typeOf(e) !== 'null') {
					x = 0;
					b.getChildren().each(function (c) {
						if (c !== e) {
							x += c.getSize().x;
						}
					});
					e.setStyle('width', w - x - 10 + 'px');
				}
			});
		}
	},

	watchGroupButtons: function () {

		this.form.addEvent('click:relay(.deleteGroup)', function (e, target) {
			e.preventDefault();
			this.deleteGroup(e);
		}.bind(this));

		this.form.addEvent('click:relay(.addGroup)', function (e, target) {
			e.preventDefault();
			this.duplicateGroup(e);
		}.bind(this));

		this.form.addEvent('click:relay(.fabrikSubGroup)', function (e, subGroup) {
			var r = subGroup.getElement('.fabrikGroupRepeater');
			if (r) {
				subGroup.addEvent('mouseenter', function (e) {
					r.fade(1);
				});
				subGroup.addEvent('mouseleave', function (e) {
					r.fade(0.2);
				});
			}
		}.bind(this));
	},

//***********************************************************
// Called from web-page code
//***********************************************************/

	/**
	 * @param   string  id            Element id to observe
	 * @param   string  triggerEvent  Event type to add
	 */
	watchValidation: function (id, triggerEvent) {
		this.options.ajaxValidation === true ? this.addValidationEvents(id, triggerEvent) : this.addClearErrorEvents(id, triggerEvent);
	},

	addValidationEvents: function (id, triggerEvent) {
		var el = document.id(id);
		if (typeOf(el) === 'null') {
			fconsole('form.js:watchValidation: Could not find element ' + id);
			return;
		}
		if (el.className === 'fabrikSubElementContainer') {
			// check for things like radio buttons & checkboxes
			el.getElements('.fabrikinput').each(function (i) {
					i.addEvent(triggerEvent, function (e) {
						this.doElementValidation.delay(250, this, e, true);
					}.bind(this));
			}.bind(this));
		} else {
			el.addEvent(triggerEvent, function (e) {
				this.doElementValidation.delay(250, this, e, false);
			}.bind(this));
		}
	},

	addClearErrorEvents: function (id, triggerEvent) {
		var el = document.id(id);
		if (typeOf(el) === 'null') {
			fconsole('form.js:watchValidation: Could not find element ' + id);
			return;
		}
		if (el.className === 'fabrikSubElementContainer') {
			// check for things like radio buttons & checkboxes
			el.getElements('.fabrikinput').each(function (i) {
				i.addEvent('change', function (e) {
					this.doElementClearError(e);
				}.bind(this));
			}.bind(this));
			return;
		}
		el.addEvent('change', function (e) {
			this.doElementClearError(e);
		}.bind(this));
	},

	/**
	 * Add elements into the form
	 *
	 * @param  Hash  a  Elements to add.
	 */
	addElements: function (a) {
		/*
		 * Store the newly added elements so we can call attachedToForm only on new elements. Avoids issue with cdd in repeat groups
		 * resetting themselves when you add a new group
		 */
		var added = [], i = 0;
		a = $H(a);
		a.each(function (elements, gid) {
			elements.each(function (el) {
				if (typeOf(el) === 'array') {
					var oEl = new window[el[0]](el[1], el[2]);
					added.push(this.addElement(oEl, el[1], gid));
				}
				else if (typeOf(el) !== 'null') {
					added.push(this.addElement(el, el.options.element, gid));
				}
			}.bind(this));
		}.bind(this));
		// $$$ hugh - moved attachedToForm calls out of addElement to separate loop, to fix forward reference issue,
		// i.e. calc element adding events to other elements which come after itself, which won't be in formElements
		// yet if we do it in the previous loop ('cos the previous loop is where elements get added to formElements)
		for (i = 0; i < added.length; i++) {
			if (typeOf(added[i]) !== 'null') {
				try {
					added[i].attachedToForm();
				} catch (err) {
					fconsole(added[i].options.element + ' attach to form:' + err);
				}
			}
		}
		Fabrik.fireEvent('fabrik.form.elements.added', [this]);
	},

	addElement: function (oEl, elId, gid) {
		//var oEl = new window[element[0]](element[1], element[2]);
		//elId = element[1];
		elId = oEl.getFormElementsKey(elId);
		elId = elId.replace('[]', '');

		var ro = elId.substring(elId.length - 3, elId.length) === '_ro';
		oEl.form = this;
		oEl.groupid = gid;
		this.formElements.set(elId, oEl);
		Fabrik.fireEvent('fabrik.form.element.added', [this, elId, oEl]);
		if (ro) {
			elId = elId.substr(0, elId.length - 3);
			this.formElements.set(elId, oEl);
		}
		return oEl;
	},

	/**
	 * An element state has changed, so lets run any associated effects
	 *
	 * @param   string  id            Element id to run the effect on
	 * @param   string  method        Method to run
	 * @param   object  elementModel  The element JS object which is calling the fx, this is used to work ok which repeat group the fx is applied on
	 */
	doElementFX: function (id, method, elementModel) {
		var k, groupfx, fx, fxElement;

		// Could be the source element is in a repeat group but the target is not.
		var target = this.formElements.get(id.replace('fabrik_trigger_element_', ''));
		targetInRepeat = true;
		if (target) {
			targetInRepeat = target.options.inRepeatGroup;
		}

		// Update the element id that we will apply the fx to to be that of the calling elementModels group (if in a repeat group)
		if (elementModel && targetInRepeat) {
			if (elementModel.options.inRepeatGroup) {
				var bits = id.split('_');
				bits[bits.length - 1] = elementModel.options.repeatCounter;
				id = bits.join('_');
			}
		}
		// Create the fx key
		id = id.replace('fabrik_trigger_', '');
		if (id.slice(0, 6) === 'group_') {
			id = id.slice(6, id.length);
			// wierd fix?
			if (id.slice(0, 6) === 'group_') {
				id = id.slice(6, id.length);
			}
			k = id;
			groupfx = true;
		} else {
			groupfx = false;
			id = id.slice(8, id.length);
			k = 'element' + id;
		}

		// Get the stored fx
		fx = this.fx.elements[k];
		if (!fx) {
			// A group was duplicated but no element FX added, lets try to add it now
			fx = this.addElementFX('element_' + id, method);

			// If it wasn't added then lets get out of here
			if (!fx) {
				return;
			}
		}
		// Seems dropdown element fx.css.element is already the container
		if (groupfx || fx.css.element.hasClass('fabrikElementContainer')) {
			fxElement = fx.css.element;
		} else {
			fxElement = fx.css.element.getParent('.fabrikElementContainer');
		}

		// For repeat groups rendered as tables we cant apply fx on td so get child
		if (fxElement.get('tag') === 'td') {
			fxElement = fxElement.getChildren()[0];
		}
		switch (method) {
		case 'show':
			fxElement.fade('show').removeClass('fabrikHide');
			if (groupfx) {
				// strange fix for ie8
				// http://fabrik.unfuddle.com/projects/17220/tickets/by_number/703?cycle=true
				document.id(id).getElements('.fabrikinput').setStyle('opacity', '1');
			}
			break;
		case 'hide':
			fxElement.fade('hide').addClass('fabrikHide');
			break;
		case 'fadein':
			fxElement.removeClass('fabrikHide');
			if (fx.css.lastMethod !== 'fadein') {
				fx.css.element.show();
				fx.css.start({'opacity': [0, 1]});
			}
			break;
		case 'fadeout':
			if (fx.css.lastMethod !== 'fadeout') {
				fx.css.start({'opacity': [1, 0]}).chain(function () {
					fx.css.element.hide();
					fxElement.addClass('fabrikHide');
				});
			}
			break;
		case 'slide in':
			fx.slide.slideIn();
			break;
		case 'slide out':
			fx.slide.slideOut();
			fxElement.removeClass('fabrikHide');
			break;
		case 'slide toggle':
			fx.slide.toggle();
			break;
		case 'clear':
			this.formElements.get(id).clear();
			break;
		}
		fx.lastMethod = method;
		Fabrik.fireEvent('fabrik.form.doelementfx', [this]);
	},

	/**
	 * Attach an effect to an elements
	 *
	 * @param   string  id      Element or group to apply the fx TO, triggered from another element
	 * @param   string  method  JS event which triggers the effect (click,change etc)
	 *
	 * @return false if no element found or element fx
	 */
	addElementFX: function (id, method) {
		var c, k, fxdiv;
		id = id.replace('fabrik_trigger_', '');
		if (id.slice(0, 6) === 'group_') {
			id = id.slice(6, id.length);
			k = id;
			c = document.id(id);
		} else {
			id = id.slice(8, id.length);
			k = 'element' + id;
			if (!document.id(id)) {
				return false;
			}
			c = document.id(id).getParent('.fabrikElementContainer');
		}
		if (c) {
			// c will be the <li> element - you can't apply fx's to this as it makes the
			// DOM squiffy with
			// multi column rows, so get the li's content and put it inside a div which
			// is injected into c
			// apply fx to div rather than li - damn im good
			var tag = (c).get('tag');
			if (tag === 'li' || tag === 'td') {
				fxdiv = new Element('div', {'style': 'width:100%'}).adopt(c.getChildren());
				c.empty();
				fxdiv.inject(c);
			} else {
				fxdiv = c;
			}

			var opts = {
				duration: 800,
				transition: Fx.Transitions.Sine.easeInOut
			};
			this.fx.elements[k] = {};
			//'opacity',
			this.fx.elements[k].css = new Fx.Morph(fxdiv, opts);
			if (typeOf(fxdiv) !== 'null' && (method === 'slide in' || method === 'slide out' || method === 'slide toggle')) {
				this.fx.elements[k].slide = new Fx.Slide(fxdiv, opts);
			} else {
				this.fx.elements[k].slide = null;
			}
			return this.fx.elements[k];
		}
		return false;
	},

//***********************************************************
// Called from other js functions
//***********************************************************/

	/**
	 * Called from fabrik.js
	 **/
	destroyElements: function () {
		this.formElements.each(function (el) {
			el.destroy();
		});
	},

	/**
	 * Used to get the querystring data and for any element overwrite with its own data definition
	 * Required for empty select lists which return undefined as their value if no items available
	 * Currently called from element, calc, CDD, dbjoin
	 *
	 * @param  bool  submit  Should we run the element onsubmit() methods - set to false in calc element
	 */
	getFormData: function (submit) {
		submit = typeOf(submit) !== 'null' ? submit : true;
		if (submit) {
			this.formElements.each(function (el, key) {
				el.onsubmit();
			});
		}
		this.getForm();
		var s = this.form.toQueryString();
		var h = {};
		s = s.split('&');
		var arrayCounters = $H({});
		s.each(function (p) {
			p = p.split('=');
			var k = p[0];
			// $$$ rob deal with checkboxes
			// Ensure [] is not encoded
			k = decodeURI(k);
			if (k.substring(k.length - 2) === '[]') {
				k = k.substring(0, k.length - 2);
				if (!arrayCounters.has(k)) {
					// rob for ajax validation on repeat element this is required to be set to 0
					arrayCounters.set(k, 0);
				} else {
					arrayCounters.set(k, arrayCounters.get(k) + 1);
				}
				k = k + '[' + arrayCounters.get(k) + ']';
			}
			h[k] = p[1];
		});

		// toQueryString() doesn't add in empty data - we need to know that for the
		// validation on multipages
		// Paul following variable is never used.
		// var elKeys = this.formElements.getKeys();
		this.formElements.each(function (el, key) {
			//fileupload data not included in querystring
			if (el.plugin === 'fabrikfileupload') {
				h[key] = el.get('value');
			}
			if (typeOf(h[key]) === 'null') {
				// search for elementname[*] in existing data (search for * as datetime
				// elements aren't keyed numerically)
				var found = false;
				$H(h).each(function (val, dataKey) {
					dataKey = unescape(dataKey); // 3.0 ajax submission [] are escaped
					dataKey = dataKey.replace(/\[(.*)\]/, '');
					if (dataKey === key) {
						found = true;
					}
				}.bind(this));
				if (!found) {
					h[key] = '';
				}
			}
		}.bind(this));
		return h;
	},

	/**
	 * $$$ hugh - added this, so far only used by calc and cascading dropdown JS
	 * to populate 'data' for the AJAX update, so custom cascade 'where' clauses
	 * can use {placeholders}. Initially tried to use getFormData for this, but because
	 * it adds ALL the query string args from the page, the AJAX call from cascade ended
	 * up trying to submit the form. So, this func does what the commented out code in
	 * getFormData used to do, and only fetches actual form element data.
	 **/
	getFormElementData: function () {
		var h = {};
		this.formElements.each(function (el, key) {
			if (el.element) {
				h[key] = el.getValue();
				h[key + '_raw'] = h[key];
			}
		}.bind(this));
		return h;
	},

//***********************************************************
// Button click code
//***********************************************************/

	/**
	 * Enable / Disable elements
	 *
	 * @param  Elements to enable / disable
	 */
	enableSubmitApply: function () {
		var submit = this._getButton('submit');
		if (submit) {
			this.enableElement(submit);
		}
		var apply = this._getButton('apply');
		if (apply) {
			this.enableElement(apply);
		}
	},

	disableSubmitApply: function () {
		var submit = this._getButton('submit');
		if (submit) {
			this.disableElement(submit);
		}
		var apply = this._getButton('apply');
		if (apply) {
			this.disableElement(apply);
		}
	},

	doSubmit: function (e, btn) {
		Fabrik.fireEvent('fabrik.form.submit.start', [this, e, btn]);
		this.elementsBeforeSubmit(e);
		if (this.result === false) {
			this.result = true;
			e.stop();
			// Update global status error
			this.updateMainError();

			// Return otherwise ajax upload may still occur.
			return;
		}
		// Insert a hidden element so we can reload the last page if validation fails
		if (this.options.pages.getKeys().length > 1) {
			this.form.adopt(new Element('input', {'name': 'currentPage', 'value': this.currentPage.toInt(), 'type': 'hidden'}));
		}
		if (this.options.ajax) {
			// Do ajax val only if onSubmit val ok
			if (this.form) {
				// $$$ hugh - we already did elementsBeforeSubmit() this at the start of this func?
				// (and we're going to call it again in getFormData()!)
				//this.elementsBeforeSubmit(e);
				// get all values from the form
				var data = $H(this.getFormData());
				data = this.prepareRepeatsForAjax(data);
				data.fabrik_ajax = '1';
				data.format = 'raw';
				if (btn.name === 'Copy') {
					data.Copy = 1;
					e.stop();
				}
				data.fabrik_ajax = '1';
				data.format = 'raw';
				var myajax = new Request.JSON({
					'url': this.form.action,
					'data': data,

					onRequest: function(){
						Fabrik.loader.start(this.getBlock(), Joomla.JText._('COM_FABRIK_SAVING'));
					}.bind(this),

					onCancel: function(){
						Fabrik.loader.stop(this.getBlock());
					}.bind(this),

					onComplete: function(){
						Fabrik.loader.stop(this.getBlock());
					}.bind(this),

					onError: function (text, error) {
						fconsole('Fabrik form::doSubmit Ajax JSON error: ' + error + ": " + text);
						this.showMainError('Ajax error on partial save: ' + error);
					}.bind(this),

					onFailure: function (xhr) {
						fconsole('Fabrik form::doSubmit Ajax failure: Code ' + xhr.status + ': ' + xhr.statusText);
						this.showMainError('Ajax failure on partial save.');
					}.bind(this),

					onSuccess: function (json, txt) {
						if (typeOf(json) === 'null') {
							fconsole('Fabrik form::doSubmit Ajax response empty.');
							this.showMainError('Ajax response empty on partial save.');
							return;
						}
						// Process errors if there are some
						var errfound = false;
						if (json.errors !== undefined) {

							// For every element of the form update error message
							$H(json.errors).each(function (errors, key) {
								// $$$ hugh - nasty hackery alert!
								// validate() now returns errors for joins in join___id___label format,
								// but if repeated, will be an array under _0 name.
								// replace join[id][label] with join___id___label
								// key = key.replace(/(\[)|(\]\[)/g, '___').replace(/\]/, '');
								if (this.formElements.has(key) && errors.flatten().length > 0) {
									errfound = true;
									if (this.formElements[key].options.inRepeatGroup) {
										for (e = 0; e < errors.length; e++) {
											if (errors[e].flatten().length  > 0) {
												var this_key = key.replace(/(_\d+)$/, '_' + e);
												this.showElementError(errors[e].flatten().join('<br />'), this_key);
											}
										}
									}
									else {
										this.showElementError(errors.flatten().join('<br />'), key);
									}
								}
							}.bind(this));
						}
						// Update global status error
						this.updateMainError();

						if (errfound === false) {
							var clear_form = false;
							if (this.options.rowid === '' && btn.name !== 'apply') {
								// We're submitting a new form - so always clear
								clear_form = true;
							}
							var savedMsg = (typeOf(json.msg) !== 'null' && json.msg !== undefined && json.msg !== '') ? json.msg : Joomla.JText._('COM_FABRIK_FORM_SAVED');
							if (json.baseRedirect !== true) {
								clear_form = json.reset_form;
								if (json.url !== undefined) {
									if (json.redirect_how === 'popup') {
										var width = json.width ? json.width : 400;
										var height = json.height ? json.height : 400;
										var x_offset = json.x_offset ? json.x_offset : 0;
										var y_offset = json.y_offset ? json.y_offset : 0;
										var title = json.title ? json.title : '';
										Fabrik.getWindow({'id': 'redirect', 'type': 'redirect', contentURL: json.url, caller: this.getBlock(), 'height': height, 'width': width, 'offset_x': x_offset, 'offset_y': y_offset, 'title': title});
									}
									else {
										if (json.redirect_how === 'samepage') {
											window.open(json.url, '_self');
										}
										else if (json.redirect_how === 'newpage') {
											window.open(json.url, '_blank');
										}
									}
								} else {
									alert(savedMsg);
								}
							} else {
								clear_form = json.reset_form !== undefined ? json.reset_form : clear_form;
								alert(savedMsg);
							}
							// Query the list to get the updated data
							Fabrik.fireEvent('fabrik.form.submitted', [this, json]);
							if (btn.name !== 'apply') {
								if (clear_form) {
									this.clearForm();
								}
								// If the form was loaded in a Fabrik.Window close the window.
								if (Fabrik.Windows[this.options.fabrik_window_id]) {
									Fabrik.Windows[this.options.fabrik_window_id].close();
								}
							}
						} else {
							Fabrik.fireEvent('fabrik.form.submit.failed', [this, json]);
							// Stop spinner
							Fabrik.loader.stop(this.getBlock(), Joomla.JText._('COM_FABRIK_VALIDATION_ERROR'));
						}
					}.bind(this)
				}).send();
			}
		}
		Fabrik.fireEvent('fabrik.form.submit.end', [this]);
		if (this.result === false) {
			this.result = true;
			e.stop();
			// Update global status error
			this.updateMainError();
		} else {
			// Enables the list to clean up the form and custom events
			if (this.options.ajax) {
				Fabrik.fireEvent('fabrik.form.ajax.submit.end', [this]);
			}
		}
	},

	elementsBeforeSubmit: function (e) {
		this.formElements.each(function (el, key) {
			if (!el.onsubmit()) {
				e.stop();
			}
		});
	},

	clearForm: function () {
		this.getForm();
		if (!this.form) {
			return;
		}
		this.formElements.each(function (el, key) {
			if (key === this.options.primaryKey) {
				this.form.getElement('input[name=rowid]').value = '';
			}
			el.update('');
		}.bind(this));
		// reset errors
		this.form.getElements('.fabrikError').empty();
		this.form.getElements('.fabrikError').addClass('fabrikHide');
	},

	reset: function () {
		Fabrik.fireEvent('fabrik.form.reset', [this]);
		if (this.result === false) {
			this.result = true;
			return;
		}
		this.addedGroups.each(function (subgroup) {
			var group = document.id(subgroup).findClassUp('fabrikGroup');
			var i = group.id.replace('group', '');
			document.id('fabrik_repeat_group_' + i + '_counter').value = document.id('fabrik_repeat_group_' + i + '_counter').get('value').toInt() - 1;
			subgroup.remove();
		});
		this.addedGroups = [];
		this.formElements.each(function (el, key) {
			el.reset();
		}.bind(this));
	},

//***********************************************************
// Shared code
//***********************************************************/

	/**
	 * Validate the form by ajax
	 *
	 * @return false if errors found, true if OK
	 *
	 */
	validateByAjax: function (target) {
		this.hideMainError();

		// If tip shown at bottom of long page and next page shorter we need to move the tip to
		// the top of the page to avoid large space appearing at the bottom of the page.
		if (typeOf(document.getElement('.tool-tip')) !== 'null') {
			document.getElement('.tool-tip').setStyle('top', 0);
		}

		var data = $H(this.getFormData());
		data = this.prepareRepeatsForAjax(data);
		data.fabrik_ajax = '1';
		data.format = 'raw';
		data.task = 'form.ajax_validate';


		// Don't prepend with Fabrik.liveSite, as it can create cross origin browser errors
		// if you are on www and livesite is not on www.
		var url = 'index.php?option=com_fabrik&format=raw&task=form.ajax_validate&form_id=' + this.id;

		if (this.ajax) {
			this.ajax.cancel();
		}
		this.Ajax = new Request({
			'url': url,
			'data': data,

			onRequest: function(){
				Fabrik.loader.start(this.getBlock(), Joomla.JText._('COM_FABRIK_VALIDATING'));
			}.bind(this),

			onCancel: function(){
				Fabrik.loader.stop(this.getBlock());
				this.ajax = null;
			}.bind(this),

			onComplete: function(){
				Fabrik.loader.stop(this.getBlock());
				this.ajax = null;
			}.bind(this),

			onFailure: function(xhr){
				console.log('Fabrik form::doSubmit Ajax failure: Code ' + xhr.status + ': ' + xhr.statusText);
				this.showMainError('Validation ajax call failed');
				this.formElements.each(function (el, key) {
					el.afterAjaxValidation();
				});
			}.bind(this),

			onSuccess: function (r) {
				if (typeOf(r) === 'null') {
					fconsole('Fabrik form::doSubmit Ajax response empty.');
					this.showMainError('Validation ajax response empty');
					return;
				}
				// new Fx.Scroll(window).toElement(this.form);
				this.formElements.each(function (el, key) {
					el.afterAjaxValidation();
				});
				var formPosition = this.form.getPosition();
				if (this.options.admin) {
					document.id(window).scrollTo(formPosition.x, formPosition.y - 70); // J3 has fixed nav bars 60px tall
				} else {
					document.id(window).scrollTo(formPosition.x, formPosition.y - 10); // Allow a 10px top margin displayed.
				}
				r = JSON.decode(r);
				// Show error fields
				var validationError = this.showGroupError(r, data);
				if (validationError) {
					this.disableSubmitApply();
					if (this.tabbed) {
					} else {
						// Next only if no errors, Prev regardless
						if (target === -1) {
							this.changePage(target);
						}
					}
				} else {
					this.saveGroupsToDb();
					if (this.tabbed) {
						this.tabShow(target); // Show the tab.
					} else {
						this.changePage(target);
					}
				}
				this.ajax = null;
			}.bind(this)
		}).send();
	},

	showGroupError: function (r, d) {
		// Only validate the current groups elements, otherwise validations on
		// other pages cause the form to show an error.
		if (this.tabbed) {
			var currentTab = this.form.getElement('.tab-pane.active').id;
			var gids = this.options.pages.get(currentTab.replace('group-tab','').toInt());
		} else {
			var gids = Array.from(this.options.pages.get(this.currentPage.toInt()));
		}
		var err = false;
		$H(d).each(function (v, k) {
			k = k.replace(/\[(.*)\]/, '').replace(/%5B(.*)%5D/, '');// for dropdown validations
			if (this.formElements.has(k)) {
				var el = this.formElements.get(k);
				if (gids.contains(el.groupid.toInt())) {
					if (r.errors[k]) {
						// prepare error so that it only triggers for real errors and not success msgs
						if (typeOf(r.errors[k]) !== 'null') {
							var msg = r.errors[k].flatten().join('<br />');
							if (msg !== '') {
								err = this.showElementError(msg, k) || err;
							} else {
								this.showElementError('', k);
							}
						} else {
							this.showElementError('', k);
						}
					}
					if (r.modified[k]) {
						if (el) {
							el.update(r.modified[k]);
						}
					}
				}
			}
		}.bind(this));
		this.updateMainError();
		return err;
	},

	saveGroupsToDb: function () {
		if (this.options.multipage_save === 0) {
			return;
		}
		Fabrik.fireEvent('fabrik.form.groups.save.start', [this]);
		if (this.result === false) {
			this.result = true;
			return;
		}

		var data = $H(this.getFormData());
		data = this.prepareRepeatsForAjax(data);
		data.fabrik_ajax = '1';
		data.format = 'raw';
		data.task = 'form.savepage';

		var url = 'index.php?option=com_fabrik&format=raw&page=' + this.currentPage;
		new Request({
			url: url,
			data: data,

			onRequest: function(){
				Fabrik.loader.start(this.getBlock(), 'COM_FABRIK_SAVING');
			}.bind(this),

			onCancel: function(){
				Fabrik.loader.stop(this.getBlock());
			}.bind(this),

			onComplete: function(){
				Fabrik.loader.stop(this.getBlock());
			}.bind(this),

			onFailure: function(xhr){
				console.log('Fabrik form::saveGroupsToDb Ajax failure: Code ' + xhr.status + ': ' + xhr.statusText);
				this.showMainError('Partial save ajax call failed');
				this.formElements.each(function (el, key) {
					el.afterAjaxValidation();
				});
			}.bind(this),

			onSuccess: function (r) {
				this.formElements.each(function (el, key) {
					el.afterAjaxValidation();
				});
				Fabrik.fireEvent('fabrik.form.groups.save.completed', [this]);
				if (this.result === false) {
					this.result = true;
					return;
				}
				if (this.options.ajax) {
					Fabrik.fireEvent('fabrik.form.groups.save.end', [this, r]);
				}
			}.bind(this)
		}).send();
	},

	/**
	 * as well as being called from watchValidation can be called from other
	 * element js actions, e.g. date picker closing
	 **/
	doElementValidation: function (e, subEl, replacetxt) {
		if (this.options.ajaxValidation === false) {
			return;
		}

		// $$$ Paul - We should not assume that replacetxt comes from date.js - date.js should provide replacement
		// text explicitly.
		replacetxt = typeOf(replacetxt) === 'null' ? '_time' : replacetxt;
		if (typeOf(e) === 'event' || typeOf(e) === 'object' || typeOf(e) === 'domevent') { // type object in
			// In case validation field is not displayed field (e.g. autocomplete),
			// we want spinner to be shown against displayed field.
			var spinId = id = e.target.id;
			// Check for dbjoin autocomplete label field and replace with value field
			if (e.target.hasClass('autocomplete-trigger')) {
				id = id.replace('-auto-complete','');
			}
			// for elements with subelements eg checkboxes radiobuttons
			if (subEl === true) {
				id = document.id(e.target).getParent('.fabrikSubElementContainer').id;
			}
		} else {
			// hack for closing date picker where it seems the event object isn't available
			// $$$ Paul - date.js should use mock events (see autocomplete*.js for example
			id = e;
		}
		if (typeOf(document.id(id)) === 'null') {
			return;
		}
		if (document.id(id).getProperty('readonly') === true || document.id(id).getProperty('readonly') === 'readonly') {
			// stops date element being validated
			// return;
		}
		var el = this.formElements.get(id);
		if (!el) {
			//silly catch for date elements you cant do the usual method of setting the id in the
			//fabrikSubElementContainer as its required to be on the date element for the calendar to work
			// Paul - To Do - Now that the actual validation is done in element.js (which is extended for each
			// element plugin), tweaks to the data can be done as overrides within the specific plugin js.
			id = id.replace(replacetxt, '');
			el = this.formElements.get(id);
			if (!el) {
				return;
			}
		}

		this.formElements.get(id).doValidation(e, subEl, id, spinId);

		/**
		 * Paul - In order to be able to do multiple single validations in parallel,
		 * the following code has been moved inside element.js which is called above.

		var d = $H(this.getFormData());
		d.set('task', 'form.ajax_validate');
		d.set('fabrik_ajax', '1');
		d.set('format', 'raw');

		d = this.prepareRepeatsForAjax(d);

		// $$$ hugh - nasty hack, because validate() in form model will always use _0 for
		// repeated id's
		var origid = id;
		if (el.origid) {
			origid = el.origid + '_0';
		}
		//var origid = el.origid ? el.origid : id;
		el.options.repeatCounter = el.options.repeatCounter ? el.options.repeatCounter : 0;
		var url = 'index.php?option=com_fabrik&form_id=' + this.id;
		Fabrik.fireEvent('fabrik.form.element.validation.start', [this, el, e]);
		if (this.result === false) {
			this.result = true;
			return;
		}
		Fabrik.loader.start(spinId, Joomla.JText._('COM_FABRIK_VALIDATING'));
		var myAjax = new Request({
			url: url,
			method: this.options.ajaxmethod,
			data: d,
			onComplete: function (r) {
				Fabrik.loader.stop(spinId);
				r = JSON.decode(r);
				if (typeOf(r) === 'null') {
					this.showElementError('Validation ajax call failed', id);
					this.result = true;
					return;
				}
				this.formElements.each(function (el, key) {
					el.afterAjaxValidation();
				});
				Fabrik.fireEvent('fabrik.form.element.validation.complete', [this, r, id, origid]);
				if (this.result === false) {
					this.result = true;
					return;
				}
				var el = this.formElements.get(id);
				if ((typeOf(r.modified[origid]) !== 'null')) {
					el.update(r.modified[origid]);
				}
				if (typeOf(r.errors[origid]) !== 'null') {
					this.showElementError(r.errors[origid][el.options.repeatCounter].flatten().join('<br />'), id, true);
				} else {
					this.showElementError('', id, true);
				}
			}.bind(this)
		}).send();
		**/
	},

	doElementClearError: function (e) {
		// If not doing ajax validation, then clear error messages for a field on same events
		this.showElementError('', e.target.id, true);
		this.updateMainError();
	},

	prepareRepeatsForAjax: function (d) {
		this.getForm();
		if (!this.form) {
			return;
		}
		//ensure we are dealing with a simple object
		if (typeOf(d) === 'hash') {
			d = d.getClean();
		}
		//data should be key'd on the data stored in the elements name between []'s which is the group id
		this.form.getElements('input[name^=fabrik_repeat_group]').each(
				function (e) {
					// $$$ hugh - had a client with a table called fabrik_repeat_group, which was hosing up here,
					// so added a test to narrow the element name down a bit!
					if (e.id.test(/fabrik_repeat_group_\d+_counter/)) {
						var c = e.name.match(/\[(.*)\]/)[1];
						d['fabrik_repeat_group[' + c + ']'] = e.get('value');
					}
				}
		);
		return d;
	},

	showElementError: function (msg, id, single) {
		// Optional parameter single=true is used to avoid displaying success messages for single fields
		// in order to avoid unsightly layout jumps as messages are inserted and removed on a timer.
		single = typeOf(single) !== 'null' ? single : false;
		// msg should be the errors for the specific element, down to its repeat group id.
		var classname = (msg === '') ? 'fabrikSuccess' : 'fabrikError';
		this.formElements.get(id).setErrorMessage(msg, classname, single);
		return (classname === 'fabrikSuccess') ? false : true;
	},

	updateMainError: function () {
		var mainErr = this.form.getElement('.fabrikMainError');
		var activeValidations = this.form.getElements('.fabrikError').filter(
				function (e, index) {
			return !e.hasClass('fabrikMainError');
		});
		if (activeValidations.length > 0) {
			if (mainErr.hasClass('fabrikHide')) {
				this.showMainError(this.options.error);
			}
		} else {
			this.hideMainError();
		}
	},

	showMainError: function (msg) {
		// If we are in j3 and ajax validations are on - dont show main error as it makes the form 'jumpy'
		// Paul - rather than avoid displaying - we now avoid calling
		/* if (Fabrik.bootstrapped && this.options.ajaxValidation) {
			return;
		} */
		var mainErr = this.form.getElement('.fabrikMainError');
		mainErr.getChildren('span').each( function (e) {
			e.destroy();
			});
		mainErr.grab(new Element('span').set('html', msg));
		mainErr.removeClass('fabrikHide');
		myfx = new Fx.Tween(mainErr, {property: 'opacity',
			duration: 500
		}).start(0, 1);
	},

	hideMainError: function () {
		var mainErr = this.form.getElement('.fabrikMainError');
		if (mainErr.hasClass('fabrikHide')) {
			return;
		}
		myfx = new Fx.Tween(mainErr, {property: 'opacity',
				duration: 500,
				onComplete: function () {
					mainErr.addClass('fabrikHide');
				}
			}).start(1, 0);
	},

//***********************************************************
// Utility functions
//***********************************************************/

	getForm: function () {
		this.form = document.id(this.getBlock());
		return this.form;
	},

	getBlock: function () {
		var block = this.options.editable === true ? 'form_' + this.id : 'details_' + this.id;
		if (this.options.rowid !== '') {
			block += '_' + this.options.rowid;
		}
		return block;
	},

	enableElement: function (el) {
		el.disabled = "";
		el.setStyle('opacity', 1.0);
	},

	disableElement: function (el) {
		el.disabled = "disabled";
		el.setStyle('opacity', 0.5);
	},

	/**
	 * Dispatch an event to an element
	 *
	 * @param   string  elementType  Deprecated
	 * @param   string  elementId    Element key to look up in this.formElements
	 * @param   string  action       Event change/click etc
	 * @param   mixed   js           String or function
	 */
	dispatchEvent: function (elementType, elementId, action, js) {
		if (typeOf(js) === 'string') {
			js = Encoder.htmlDecode(js);
		}
		var el = this.formElements.get(elementId);
		if (!el) {
			// E.g. db join rendered as chx
			var els = Object.each(this.formElements, function (e) {
				if (elementId === e.baseElementId) {
					el = e;
				}
			});
		}
		if (el && js !== '') {
			el.addNewEvent(action, js);
		}
	},

	action: function (task, el) {
		var oEl = this.formElements.get(el);
		Browser.exec('oEl.' + task + '()');
	},

	triggerEvents: function (el) {
		this.formElements.get(el).fireEvents(arguments[1]);
	},

	/**
	 * @since 3.0 get a form button name
	 **/
	_getButton: function (name) {
		if (!this.getForm()) {
			return;
		}
		var b = this.form.getElement('input[type=button][name=' + name + ']');
		if (!b) {
			b = this.form.getElement('input[type=submit][name=' + name + ']');
		}
		if (!b) {
			b = this.form.getElement('button[type=button][name=' + name + ']');
		}
		if (!b) {
			b = this.form.getElement('button[type=submit][name=' + name + ']');
		}
		return b;
	},

//***********************************************************
// Paged template code
//***********************************************************/

	/**
	 * Move forward/backwards in multipage form
	 *
	 * @param   event  e
	 * @param   int    dir  1/-1
	 */
	doPageNav: function (e, dir) {
		e.stop();
		if (this.options.editable) {
			this.validateByAjax(dir);
		} else {
			this.changePage(dir);
		}
	},

	changePage: function (dir) {
		Fabrik.fireEvent('fabrik.form.page.change.start', [this]);
		if (this.result === false) {
			this.result = true;
			return;
		}
		this.currentPage = this.currentPage.toInt();
		if (this.currentPage + dir >= 0 && this.currentPage + dir < this.options.pages.getKeys().length) {
			this.currentPage = this.currentPage + dir;
			if (!this.pageGroupsVisible()) {
				this.changePage(dir);
			}
		}

		this.setPageButtons();
		document.id('page_' + this.currentPage).setStyle('display', '');
		this.setMozBoxWidths();
		this.hideOtherPages();
		Fabrik.fireEvent('fabrik.form.page.change.end', [this]);
		if (this.result === false) {
			this.result = true;
			return;
		}
	},

	pageGroupsVisible: function () {
		var visible = false;
		this.options.pages.get(this.currentPage).each(function (gid) {
			var group = document.id('group' + gid);
			if (typeOf(group) !== 'null') {
				if (group.getStyle('display') !== 'none') {
					visible = true;
				}
			}
		});
		return visible;
	},

	/**
	 * Hide all groups except those in the active page
	 */
	hideOtherPages: function () {
		var page;
		this.options.pages.each(function (gids, i) {
			if (i.toInt() !== this.currentPage.toInt()) {
				page = document.id('page_' + i);
				if (typeOf(page) !== 'null') {
					page.hide();
				}
			}
		}.bind(this));
	},

	setPageButtons: function () {
		var prev = this.form.getElement('.fabrikPagePrevious');
		var next = this.form.getElement('.fabrikPageNext');
		if (typeOf(next) !== 'null') {
			if (this.currentPage === this.options.pages.getKeys().length - 1) {
				this.enableSubmitApply();
				this.disableElement(next);
			} else {
				this.enableElement(next);
			}
		}
		if (typeOf(prev) !== 'null') {
			if (this.currentPage === 0) {
				this.disableElement(prev);
			} else {
				this.enableElement(prev);
			}
		}
	},

	/************************************************************
	 * Tabbed template code
	 ************************************************************/

	tabValidate: function (e, targetTo, targetFrom) {
		// Get current tab div and validate it with ajax
		if (this.options.editable) {
			e.preventDefault();
			this.validateByAjax(targetTo);
		}
		// If not editable click does the tab change anyway.
	},

	tabShow: function (targetTab) {
		var currentTab =  this.form.getElement('.nav-tabs').getElement('li.active');
		var currentPage = this.form.getElement('.tab-pane.active');
		var targetPage = document.id(targetTab.getProperty('href').substring(1));
		var targetTab = targetTab.getParent('li');
		currentTab.removeClass('active');
		currentPage.removeClass('active');
		targetTab.addClass('active');
		targetPage.addClass('active');
	},

//***********************************************************
// Repeat group code
//***********************************************************/

	/**
	 * When editing a new form and when min groups set we need to duplicate each group
	 * by the min repeat value.
	 */
	duplicateGroupsToMin: function () {
		if (!this.form) {
			return;
		}
		// Check for new form
		if (this.options.rowid === '') {
			// $$$ hugh - added ability to override min count
			// http://fabrikar.com/forums/index.php?threads/how-to-initially-show-repeat-group.32911
			Fabrik.fireEvent('fabrik.form.group.duplicate.min', [this]);
			Object.each(this.options.minRepeat, function (min, groupId) {
				// $$$ hugh - trying out min of 0 for Troester
				// http://fabrikar.com/forums/index.php?threads/how-to-start-a-new-record-with-empty-repeat-group.34666
				if (min === 0) {
					// Create mock event
					var del_btn = this.form.getElement('#group' + groupId + ' .deleteGroup');
					if (typeOf(del_btn) !== 'null') {
						var del_e = new Event.Mock(del_btn, 'click');

						// Remove group
						this.deleteGroup(del_e);
					}
				}
				else {
					// Create mock event
					var add_btn = this.form.getElement('#group' + groupId + ' .addGroup');
					if (typeOf(add_btn) !== 'null') {
						var add_e = new Event.Mock(add_btn, 'click');

						// Duplicate group
						for (var i = 1; i < min; i ++) {
							this.duplicateGroup(add_e);
						}
					}
				}
			}.bind(this));
		}
	},

	deleteGroup: function (e) {
		Fabrik.fireEvent('fabrik.form.group.delete.start', [this, e]);
		if (this.result === false) {
			this.result = true;
			return;
		}
		e.stop();
		var group = e.target.getParent('.fabrikGroup');

		// Find which repeat group was deleted
		var delIndex = 0;
		group.getElements('.deleteGroup').each(function (b, x) {
			if (b.getElement('img') === e.target || b.getElement('i') === e.target || b === e.target) {
				delIndex = x;
			}
		}.bind(this));

var delIndex2 = e.target.getParent('.fabrikSubGroup');

		var i = group.id.replace('group', '');

		var repeats = document.id('fabrik_repeat_group_' + i + '_counter').get('value').toInt();
		if (repeats <= this.options.minRepeat[i] && this.options.minRepeat[i] !== 0) {
			return;
		}

		delete this.duplicatedGroups.i;
		if (document.id('fabrik_repeat_group_' + i + '_counter').value === '0') {
			return;
		}
		var subgroups = group.getElements('.fabrikSubGroup');

		var subGroup = e.target.getParent('.fabrikSubGroup');
		this.subGroups.set(i, subGroup.clone());
		if (subgroups.length <= 1) {
			this.hideLastGroup(i, subGroup);
			Fabrik.fireEvent('fabrik.form.group.delete.end', [this, e, i, delIndex]);
		} else {
			var toel = subGroup.getPrevious();
			var myFx = new Fx.Tween(subGroup, {'property': 'opacity',
				duration: 300,
				onComplete: function () {
					if (subgroups.length > 1) {
						subGroup.dispose();
					}

					this.formElements.each(function (e, k) {
						if (typeOf(e.element) !== 'null') {
							if (typeOf(document.id(e.element.id)) === 'null') {
								e.decloned(i);
								delete this.formElements.k;
							}
						}
					}.bind(this));

					// Minus the removed group
					subgroups = group.getElements('.fabrikSubGroup');
					var nameMap = {};
					this.formElements.each(function (e, k) {
						if (e.groupid === i) {
							nameMap[k] = e.decreaseName(delIndex);
						}
					}.bind(this));
					// ensure that formElements' keys are the same as their object's ids
					// otherwise delete first group, add 2 groups - ids/names in last
					// added group are not updated
					$H(nameMap).each(function (newKey, oldKey) {
						if (oldKey !== newKey) {
							this.formElements[newKey] = this.formElements[oldKey];
							delete this.formElements[oldKey];
						}
					}.bind(this));
					Fabrik.fireEvent('fabrik.form.group.delete.end', [this, e, i, delIndex]);
				}.bind(this)
			}).start(1, 0);
			if (toel) {
				// Only scroll the window if the previous element is not visible
				var win_scroll = document.id(window).getScroll().y;
				var obj = toel.getCoordinates();
				// If the top of the previous repeat goes above the top of the visible
				// window,
				// scroll down just enough to show it.
				if (obj.top < win_scroll) {
					var new_win_scroll = obj.top;
					this.winScroller.start(0, new_win_scroll);
				}
			}
		}
		// Update the hidden field containing number of repeat groups
		document.id('fabrik_repeat_group_' + i + '_counter').value = document.id('fabrik_repeat_group_' + i + '_counter').get('value').toInt() - 1;
		// $$$ hugh - no, musn't decrement this!  See comment in setupAll
		this.repeatGroupMarkers.set(i, this.repeatGroupMarkers.get(i) - 1);
	},

	hideLastGroup: function (groupid, subGroup) {
		var sge = subGroup.getElement('.fabrikSubGroupElements');
		var notice = new Element('div', {'class': 'fabrikNotice alert'}).appendText(Joomla.JText._('COM_FABRIK_NO_REPEAT_GROUP_DATA'));
		if (typeOf(sge) === 'null') {
			sge = subGroup;
			var add = sge.getElement('.addGroup');
			var lastth = sge.getParent('table').getElements('thead th').getLast();
			if (typeOf(add) !== 'null') {
				add.inject(lastth);
			}
		}
		sge.setStyle('display', 'none');
		notice.inject(sge, 'after');
	},

	getClone: function (groupid, subgroup) {
		var group = document.id('group' + groupid);
		if (!subgroup) {
			subgroup = this.subGroups.get(groupid);
		}

		var clone = null;
		var found = false;
		if (this.duplicatedGroups.has(groupid)) {
			if (!subgroup) {
				clone = this.duplicatedGroups.get(groupid);
			} else {
				clone = subgroup.cloneNode(true);
			}
		} else {
			clone = subgroup.cloneNode(true);
			this.duplicatedGroups.set(groupid, clone);
		}
		return clone;
	},

	repeatGetChecked: function (group) {
		// /stupid fix for radio buttons loosing their checked value
		var tocheck = [];
		group.getElements('.fabrikinput').each(function (i) {
			if (i.type === 'radio' && i.getProperty('checked')) {
				tocheck.push(i);
			}
		});
		return tocheck;
	},

	/**
	 * Duplicates the groups sub group and places it at the end of the group
	 *
	 * @param   event  e  Click event
	 */
	duplicateGroup: function (e) {
		var subElementContainer, container;
		Fabrik.fireEvent('fabrik.form.group.duplicate.start', [this, e]);
		if (this.result === false) {
			this.result = true;
			return;
		}
		if (e) {
			e.stop();
		}

		var subgroup = e.target.getParent('.fabrikSubGroup');
		var group = e.target.getParent('.fabrikGroup');
		var group_id = group.id.replace('group', '').toInt();
		var repeats = document.id('fabrik_repeat_group_' + group_id + '_counter').get('value').toInt();
		if (this.options.maxRepeat[group_id] !== 0 && repeats >= this.options.maxRepeat[group_id]) {
			return;
		}
		document.id('fabrik_repeat_group_' + group_id + '_counter').value = repeats + 1;

		if (this.isFirstRepeatSubGroup(group)) {
			this.showFirstSubGroup(group);
			return;
		}

		var clone = this.getClone(group_id, subgroup);
		var tocheck = this.repeatGetChecked(group);

		if (group.getElement('table.repeatGroupTable')) {
			group.getElement('table.repeatGroupTable').appendChild(clone);
		} else {
			group.appendChild(clone);
		}

		tocheck.each(function (i) {
			i.setProperty('checked', true);
		});

		// Increment ids, remove primary key and (if not copy) remove values
		var newElementControllers = [];
		this.subelementCounter = 0;
		var hasSubElements = false;
		var inputs = clone.getElements('.fabrikinput');
		var lastinput = null;
		var c = this.repeatGroupMarkers.get(group_id);
		this.formElements.each(function (el) {
			var formElementFound = false;
			subElementContainer = null;
			var subElementCounter = -1;
			inputs.each(function (input) {

				hasSubElements = el.hasSubElements();

				container = input.getParent('.fabrikSubElementContainer');
				var testid = (hasSubElements && container) ? container.id : input.id;
				var cloneName = el.getCloneName();

				// Looser test than previous === to catch db join rendered as checkbox
				if (testid.contains(cloneName)) {
					lastinput = input;
					formElementFound = true;

					if (hasSubElements) {
						subElementCounter++;
						subElementContainer = input.getParent('.fabrikSubElementContainer');

						// Clone the first inputs event to all subelements
						// $$$ hugh - sanity check in case we have an element which has no input
						if (document.id(testid).getElement('input')) {
							input.cloneEvents(document.id(testid).getElement('input'));
						}
						// Note: Radio's etc now have their events delegated from the form - so no need to duplicate them

					} else {
						input.cloneEvents(el.element);

						// Update the element id use el.element.id rather than input.id as
						// that may contain _1 at end of id
						var bits = Array.from(el.element.id.split('_'));
						bits.splice(bits.length - 1, 1, c);
						input.id = bits.join('_');

						// Update labels for non sub elements
						var l = input.getParent('.fabrikElementContainer').getElement('label');
						if (l) {
							l.setProperty('for', input.id);
						}
					}
					if (typeOf(input.name) !== 'null') {
						input.name = input.name.replace('[0]', '[' + c + ']');
					}
				}
			}.bind(this));

			if (formElementFound) {
				if (hasSubElements && typeOf(subElementContainer) !== 'null') {
					// if we are checking subelements set the container id after they have all
					// been processed
					// otherwise if check only works for first subelement and no further
					// events are cloned

					// $$$ rob fix for date element
					var bits = Array.from(el.options.element.split('_'));
					bits.splice(bits.length - 1, 1, c);
					subElementContainer.id = bits.join('_');
				}
				var origelid = el.options.element;
				// clone js element controller, set form to be passed by reference and
				// not cloned
				var ignore = el.unclonableProperties();
				var newEl = new CloneObject(el, true, ignore);

				newEl.container = null;
				newEl.options.repeatCounter = c;
				newEl.origId = origelid;

				if (hasSubElements && typeOf(subElementContainer) !== 'null') {
					newEl.element = document.id(subElementContainer);
					newEl.cloneUpdateIds(subElementContainer.id);
					newEl.options.element = subElementContainer.id;
					newEl._getSubElements();
				} else {
					newEl.cloneUpdateIds(lastinput.id);
				}
				//newEl.reset();
				newElementControllers.push(newEl);
			}
		}.bind(this));

		this.duplicateResetElements(newElementControllers, group_id);
		var o = {};
		o[group_id] = newElementControllers;
		this.addElements(o);

		this.duplicateScrollToClone(clone);

		var myFx = new Fx.Tween(clone, { 'property': 'opacity',
			duration: 500
		}).set(0);
		clone.fade(1);

		// $$$ hugh - added groupid (i) and repeatCounter (c) as args
		// note I commented out the increment of c a few lines above//duplicate
		Fabrik.fireEvent('fabrik.form.group.duplicate.end', [this, e, i, c]);
		this.repeatGroupMarkers.set(i, this.repeatGroupMarkers.get(i) + 1);
	},

	isFirstRepeatSubGroup: function (group) {
		var subgroups = group.getElements('.fabrikSubGroup');
		return subgroups.length === 1 && group.getElement('.fabrikNotice');
	},

	showFirstSubGroup: function (group) {
		var subgroups = group.getElements('.fabrikSubGroup');
		// user has removed all repeat groups and now wants to add it back in
		// remove the 'no groups' notice

		var sub = subgroups[0].getElement('.fabrikSubGroupElements');
		if (typeOf(sub) === 'null') {
			group.getElement('.fabrikNotice').dispose();
			sub = subgroups[0];

			// Table group
			var add = group.getElement('.addGroup');
			add.inject(sub.getElement('td.fabrikGroupRepeater'));
			sub.setStyle('display', '');
		} else {
			subgroups[0].getElement('.fabrikNotice').dispose();
			subgroups[0].getElement('.fabrikSubGroupElements').show();
		}
		this.repeatGroupMarkers.set(i, this.repeatGroupMarkers.get(i) + 1);
	},

	duplicateScrollToClone: function (clone) {
		// Only scroll the window if the new element is not visible
		var win_size = window.getHeight();
		var win_scroll = document.id(window).getScroll().y;
		var obj = clone.getCoordinates();
		// If the bottom of the new repeat goes below the bottom of the visible window,
		// scroll up just enough to show it.
		if (obj.bottom > (win_scroll + win_size)) {
			var new_win_scroll = obj.bottom - win_size;
			this.winScroller.start(0, new_win_scroll);
		}
	},

	duplicateResetElements: function (newElementControllers, group_id) {
		// Reset either events or both events and data depending on whether repeat group is set to copy or not.
		newElementControllers.each(function (newEl) {
			newEl.cloned(c);
			// $$$ hugh - moved reset() from end of loop above, otherwise elements with un-cloneable object
			// like maps end up resetting the wrong map to default values.  Needs to run after element has done
			// whatever it needs to do with un-cloneable object before resetting.
			// $$$ hugh - adding new option to allow copying of the existing element values when copying
			// a group, instead of resetting to default value.  This means knowing what the group PK element
			// is, do we don't copy that value.  hence new group_pk_ids[] array, which gives us the PK element
			// name in regular full format, which we need to test against the join string name.
			//var pk_re = new RegExp('\\[' + this.options.group_pk_ids[group_id] + '\\]');
			var pk_re = new RegExp(this.options.group_pk_ids[group_id]);
			if (!this.options.group_copy_element_values[group_id]
			|| (this.options.group_copy_element_values[group_id] && newEl.element.name && newEl.element.name.test(pk_re))) {
				// Call reset method that resets both events and value back to default.
				newEl.reset();
			}
			else {
				// Call reset method that only resets the events, not the value
				newEl.resetEvents();
			}
		}.bind(this));
	},

//***********************************************************
// Deprecated / Obsolete code
//***********************************************************/

	/**
	 * Paul - There is no reference to this function anywhere in Fabrik 3.0 or 3.1
	 * so it looks like this code is obsolete and has been replaced by showGroupError, showMainError etc.
	 **/
	showErrors: function (data) {
		var d = null;
		if (data.id === this.id) {
			// show errors
			var errors = new Hash(data.errors);
			if (errors.getKeys().length > 0) {
				if (typeOf(this.form.getElement('.fabrikMainError')) !== 'null') {
					this.form.getElement('.fabrikMainError').set('html', this.options.error);
					this.form.getElement('.fabrikMainError').removeClass('fabrikHide');
				}
				errors.each(function (a, key) {
					if (typeOf(document.id(key + '_error')) !== 'null') {
						var e = document.id(key + '_error');
						var msg = new Element('span');
						for (var x = 0; x < a.length; x++) {
							for (var y = 0; y < a[x].length; y++) {
								d = new Element('div').appendText(a[x][y]).inject(e);
							}
						}
					} else {
						fconsole(key + '_error' + ' not found (form show errors)');
					}
				});
			}
		}
	},

	/**
	 * Paul/Rob - this may be obsolete. Cannot find any other reference to classes
	 * .previous-record/.next-record anywhere else in Fabrik.
	 **/
	testPrevNext: function() {
		// Testing prev/next RECORD buttons
		var v = this.options.editable === true ? 'form' : 'details';
		var rowInput = this.form.getElement('input[name=rowid]');
		var rowId = typeOf(rowInput) === 'null' ? '' : rowInput.value;
		var editopts = {
			option: 'com_fabrik',
			'view': v,
			'controller': 'form',
			'fabrik': this.id,
			'rowid': rowId,
			'format': 'raw',
			'task': 'paginate',
			'dir': 1
		};
		[ '.previous-record', '.next-record' ].each(function (b, dir) {
			editopts.dir = dir;
			if (this.form.getElement(b)) {

				var myAjax = new Request({
					url: 'index.php',
					data: editopts,
					onComplete: function (r) {
						Fabrik.loader.stop(this.getBlock());
						r = JSON.decode(r);
						this.update(r);
						this.form.getElement('input[name=rowid]').value = r.post.rowid;
					}.bind(this)
				});

				this.form.getElement(b).addEvent('click', function (e) {
					myAjax.options.data.rowid = this.form.getElement('input[name=rowid]').value;
					e.stop();
					Fabrik.loader.start(this.getBlock(), Joomla.JText._('COM_FABRIK_LOADING'));
					myAjax.send();
				}.bind(this));
			}
		}.bind(this));
	},

	update: function (o) {
		Fabrik.fireEvent('fabrik.form.update', [this, o.data]);
		if (this.result === false) {
			this.result = true;
			return;
		}
		var leaveEmpties = arguments[1] || false;
		var data = o.data;
		this.getForm();
		if (this.form) { // test for detailed view in module???
			var rowidel = this.form.getElement('input[name=rowid]');
			if (rowidel && data.rowid) {
				rowidel.value = data.rowid;
			}
		}
		this.formElements.each(function (el, key) {
			// if updating from a detailed view with prev/next then data's key is in
			// _ro format
			if (typeOf(data[key]) === 'null') {
				if (key.substring(key.length - 3, key.length) === '_ro') {
					key = key.substring(0, key.length - 3);
				}
			}
			// this if stopped the form updating empty fields. Element update()
			// methods
			// should test for null
			// variables and convert to their correct values
			// if (data[key]) {
			if (typeOf(data[key]) === 'null') {
				// only update blanks if the form is updating itself
				// leaveEmpties set to true when this form is called from updateRows
				if (o.id === this.id && !leaveEmpties) {
					el.update('');
				}
			} else {
				el.update(data[key]);
			}
		}.bind(this));
	},

	/**
	 * add additional data to an element - e.g database join elements
	 *
	 * Paul - This appears to be deprecated - the only element with an appendInfo method is databasejoin
	 * and that method is only called from here and this method is never called.
	 **/
	appendInfo: function (data) {
		this.formElements.each(function (el, key) {
			if (el.appendInfo) {
				el.appendInfo(data, key);
			}
		}.bind(this));
	},

});
