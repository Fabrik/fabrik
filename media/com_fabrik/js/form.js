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
		'showLoader': false,
		'customJsAction': '',
		'plugins': [],
		'ajaxmethod': 'post',
		'inlineMessage': true,
		'print': false,
		'toggleSubmit': false,
		'mustValidate': false,
		'lang': false,
		'images': {
			'alert': '',
			'action_check': '',
			'ajax_loader': ''
		}
	},

	initialize: function (id, options) {
		// $$$ hugh - seems options.rowid can be null in certain corner cases, so defend against that
		if (typeOf(options.rowid) === 'null') {
			options.rowid = '';
		}
		this.id = id;
		this.result = true; //set this to false in window.fireEvents to stop current action (e.g. stop form submission)
		this.setOptions(options);
		this.plugins = this.options.plugins;
		this.options.pages = $H(this.options.pages);
		this.subGroups = $H({});
		this.currentPage = this.options.start_page;
		this.formElements = $H({});
		this.hasErrors = $H({});
		this.mustValidateEls = $H({});
		this.elements = this.formElements;
		this.duplicatedGroups = $H({});

		this.fx = {};
		this.fx.elements = [];
		this.fx.validations = {};
		this.setUpAll();
		this._setMozBoxWidths();
		(function () {
			this.duplicateGroupsToMin();
		}.bind(this)).delay(1000);

		// Delegated element events
		this.events = {};

		this.submitBroker = new FbFormSubmit();

		Fabrik.fireEvent('fabrik.form.loaded', [this]);
	},

	_setMozBoxWidths: function () {
		if (Browser.firefox && this.getForm()) {
			//as firefox treats display:-moz-box as display:-moz-box-inline we have to programatically set their widths
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

	setUpAll: function () {
		this.setUp();
		this.winScroller = new Fx.Scroll(window);
		if (this.form) {
			if (this.options.ajax || this.options.submitOnEnter === false) {
				this.stopEnterSubmitting();
			}
			this.watchAddOptions();
		}

		$H(this.options.hiddenGroup).each(function (v, k) {
			if (v === true && typeOf(document.id('group' + k)) !== 'null') {
				var subGroup = document.id('group' + k).getElement('.fabrikSubGroup');
				this.subGroups.set(k, subGroup.cloneWithIds());
				this.hideLastGroup(k, subGroup);
			}
		}.bind(this));

		// get an int from which to start incrementing for each repeated group id
		// don't ever decrease this value when deleting a group as it will cause all sorts of
		// reference chaos with cascading dropdowns etc.
		this.repeatGroupMarkers = $H({});
		if (this.form) {
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
			this.watchGoBackButton();
		}

		this.watchPrintButton();
		this.watchPdfButton();
	},

	/**
	 * Print button action - either open up the print preview window - or print if already opened
	 */
	watchPrintButton: function () {
		document.getElements('a[data-fabrik-print]').addEvent('click', function (e) {
			e.stop();
			if (this.options.print) {
				window.print();
			} else {
				// Build URL as we could have changed the rowid via ajax pagination
				var url = 'index.php?option=com_' + Fabrik.package + '&view=details&tmpl=component&formid=' + this.id + '&listid=' + this.options.listid + '&rowid=' + this.options.rowid + '&iframe=1&print=1';
				if (this.options.lang !== false)
				{
					url += '&lang=' + this.options.lang;
				}
				window.open(url, 'win2', 'status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=400,height=350,directories=no,location=no;');
			}
		}.bind(this));
	},

	/**
	 * PDF button action.
	 */
	watchPdfButton: function () {
		document.getElements('*[data-role="open-form-pdf"]').addEvent('click', function (e) {
			e.stop();
			// Build URL as we could have changed the rowid via ajax pagination.
			var url = e.event.currentTarget.href.replace(/(rowid=\d*)/, 'rowid=' + this.options.rowid);
			if (this.options.lang !== false)
			{
				url += '&lang=' + this.options.lang;
			}
			window.location = url;
		}.bind(this))
	},

	/**
	 * Go back button in ajax pop up window should close the window
 	 */
	watchGoBackButton: function () {
		if (this.options.ajax) {
			var goback = this._getButton('Goback');
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
				duration : 500
			});
			mySlider.hide();
			a.addEvent('click', function (e) {
				e.stop();
				mySlider.toggle();
			});
		});
	},

	setUp: function () {
		this.form = this.getForm();
		this.watchGroupButtons();
		// Submit can appear in confirmation plugin even when readonly
		this.watchSubmit();
		this.createPages();
		this.watchClearSession();
	},

	getForm: function () {
		if (typeOf(this.form) === 'null') {
			this.form = document.id(this.getBlock());
		}

		return this.form;
	},

	getBlock: function () {
		if (typeOf(this.block) === 'null') {
			this.block = this.options.editable === true ? 'form_' + this.id : 'details_' + this.id;
			if (this.options.rowid !== '') {
				this.block += '_' + this.options.rowid;
			}
		}

		return this.block;
	},

	/**
	 * Attach an effect to an elements
	 *
	 * @param   string  id      Element or group to apply the fx TO, triggered from another element
	 * @param   string  method  JS event which triggers the effect (click,change etc.)
	 *
	 * @return false if no element found or element fx
	 */
	addElementFX: function (id, method) {
		var c, k, fxdiv;
		id = id.replace('fabrik_trigger_', '');
		// Paul - add sanity checking and error reporting
		if (id.slice(0, 6) === 'group_') {
			id = id.slice(6, id.length);
			k = id;
			c = document.id(id);

			if (!c) {
				fconsole('Fabrik form::addElementFX: Group "' + id + '" does not exist.');
				return false;
			}
		} else if (id.slice(0, 8) === 'element_') {
			id = id.slice(8, id.length);
			k = 'element' + id;
			c = document.id(id);
			if (!c) {
				fconsole('Fabrik form::addElementFX: Element "' + id + '" does not exist.');
				return false;
			}
			c = c.getParent('.fabrikElementContainer');
			if (!c) {
				fconsole('Fabrik form::addElementFX: Element "' + id + '.fabrikElementContainer" does not exist.');
				return false;
			}
		} else {
			fconsole('Fabrik form::addElementFX: Not an element or group: ' + id);
			return false;
		}
		if (c) {
			// c will be the <li> element - you can't apply fx's to this as it makes the
			// DOM squiffy with multi column rows, so get the li's content and put it
			// inside a div which is injected into c
			// apply fx to div rather than li - damn I'm good
			var tag = (c).get('tag');
			if (tag === 'li' || tag === 'td') {
				fxdiv = new Element('div', {'style': 'width:100%'}).adopt(c.getChildren());
				c.empty();
				fxdiv.inject(c);
			} else {
				fxdiv = c;
			}

			var opts = {
				duration : 800,
				transition : Fx.Transitions.Sine.easeInOut
			};
			if (typeOf(this.fx.elements[k]) === 'null') {
				this.fx.elements[k] = {};
			}

			this.fx.elements[k].css = new Fx.Morph(fxdiv, opts);

			if (typeOf(fxdiv) !== 'null' && (method === 'slide in' || method === 'slide out' || method === 'slide toggle')) {
				this.fx.elements[k].slide = new Fx.Slide(fxdiv, opts);
			}

			return this.fx.elements[k];
		}
		return false;
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
		var target = this.formElements.get(id.replace('fabrik_trigger_element_', '')),
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
			// weird fix?
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
	 * Get a group's tab, if it exists
	 * 
	 * These tab funcions are currently just helpers for user scripts
	 * 
	 * @param groupId
	 * 
	 * @return tab | false
	 */
	getGroupTab: function(groupid) {
		if (document.id('group' + groupid).getParent().hasClass('tab-pane')) {
			var tabid = document.id('group' + groupid).getParent().id;
			var tab_anchor = this.form.getElement('a[href=#' + tabid + ']');
			return tab_anchor.getParent();
		}
		return false;
	},
	
	/**
	 * Get a group's tab, if it exists
	 * 
	 * These tab funcions are currently just helpers for user scripts
	 * 
	 * @param groupId
	 * 
	 * @return tab | false
	 */
	getGroupTab: function(groupid) {
		if (document.id('group' + groupid).getParent().hasClass('tab-pane')) {
			var tabid = document.id('group' + groupid).getParent().id;
			var tab_anchor = this.form.getElement('a[href=#' + tabid + ']');
			return tab_anchor.getParent();
		}
		return false;
	},
	
	/**
	 * Hide a group's tab, if it exists
	 * 
	 * @param groupId
	 */
	hideGroupTab: function(groupid) {
		var tab = this.getGroupTab(groupid);
		if (tab !== false) {
			tab.hide();
			if (tab.hasClass('active')) {
				if (tab.getPrevious()) {
					jQuery(tab.getPrevious().getFirst()).tab('show');
				}
				else if (tab.getNext()) {
					jQuery(tab.getNext().getFirst()).tab('show');
				}
			}
		}
	},

	/**
	 * Hide a group's tab, if it exists
	 * 
	 * @param groupId
	 */
	selectGroupTab: function(groupid) {
		var tab = this.getGroupTab(groupid);
		if (tab !== false) {
			if (!tab.hasClass('active')) {
				jQuery(tab.getFirst()).tab('show');
			}
		}	
	},
	
	/**
	 * Hide a group's tab, if it exists
	 * 
	 * @param groupId
	 */
	showGroupTab: function(groupid) {
		var tab = this.getGroupTab(groupid);
		if (tab !== false) {
			tab.show();
		}
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

	createPages: function () {
		var submit, p, firstGroup, tabDiv;
		if (this.isMultiPage()) {

			// Wrap each page in its own div
			this.options.pages.each(function (page, i) {
				p = new Element('div', {
					'class' : 'page',
					'id' : 'page_' + i
				});
				firstGroup = document.id('group' + page[0]);
				if (typeOf(firstGroup) !== 'null') {

					// Paul - Don't use pages if this is a bootstrap_tab form
					tabDiv = firstGroup.getParent('div');
					if (typeOf(tabDiv) === 'null' || tabDiv.hasClass('tab-pane')) {
						return;
					}
					p.inject(firstGroup, 'before');
					page.each(function (group) {
						p.adopt(document.id('group' + group));
					});
				}
			});
			submit = this._getButton('Submit');
			if (submit && this.options.rowid === '') {
				submit.disabled = "disabled";
				submit.setStyle('opacity', 0.5);
			}
			if (typeOf(document.getElement('.fabrikPagePrevious')) !== 'null') {
				this.form.getElement('.fabrikPagePrevious').disabled = "disabled";
				this.form.getElement('.fabrikPagePrevious').addEvent('click', function (e) {
					this._doPageNav(e, -1);
				}.bind(this));
			}
			if (typeOf(document.getElement('.fabrikPagePrevious')) !== 'null') {
				this.form.getElement('.fabrikPageNext').addEvent('click', function (e) {
					this._doPageNav(e, 1);
				}.bind(this));
			}
			this.setPageButtons();
			this.hideOtherPages();
		}
	},

	isMultiPage: function () {
		return this.options.pages.getKeys().length > 1;
	},

	/**
	 * Move forward/backwards in multipage form
	 *
	 * @param   event  e
	 * @param   int    dir  1/-1
	 */
	_doPageNav: function (e, dir) {
		if (this.options.editable) {
			this.form.getElement('.fabrikMainError').addClass('fabrikHide');

			// If tip shown at bottom of long page and next page shorter we need to move the tip to
			// the top of the page to avoid large space appearing at the bottom of the page.
			if (typeOf(document.getElement('.tool-tip')) !== 'null') {
				document.getElement('.tool-tip').setStyle('top', 0);
			}
			// Don't prepend with Fabrik.liveSite, as it can create cross origin browser errors if you are on www and livesite is not on www.
			var url = 'index.php?option=com_fabrik&format=raw&task=form.ajax_validate&form_id=' + this.id;
			if (this.options.lang !== false)
			{
				url += '&lang=' + this.options.lang;
			}

			Fabrik.loader.start(this.getBlock(), Joomla.JText._('COM_FABRIK_VALIDATING'));

			// Only validate the current groups elements, otherwise validations on
			// other pages cause the form to show an error.

			var groupId = this.options.pages.get(this.currentPage.toInt());

			var d = $H(this.getFormData());
			d.set('task', 'form.ajax_validate');
			d.set('fabrik_ajax', '1');
			d.set('format', 'raw');

			d = this._prepareRepeatsForAjax(d);

			var myAjax = new Request({
				'url': url,
				method: this.options.ajaxmethod,
				data: d,
				onComplete: function (r) {
					Fabrik.loader.stop(this.getBlock());
					r = JSON.decode(r);

					// Don't show validation errors if we are going back a page
					if (dir === -1 || this._showGroupError(r, d) === false) {
						this.changePage(dir);
						this.saveGroupsToDb();
					}
					new Fx.Scroll(window).toElement(this.form);
				}.bind(this)
			}).send();
		}
		else {
			this.changePage(dir);
		}
		e.stop();
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
		var orig = this.form.getElement('input[name=format]').value;
		var origprocess = this.form.getElement('input[name=task]').value;
		this.form.getElement('input[name=format]').value = 'raw';
		this.form.getElement('input[name=task]').value = 'form.savepage';

		var url = 'index.php?option=com_fabrik&format=raw&page=' + this.currentPage;
		if (this.options.lang !== false)
		{
			url += '&lang=' + this.options.lang;
		}
		Fabrik.loader.start(this.getBlock(), 'saving page');
		var data = this.getFormData();
		data.fabrik_ajax = 1;
		new Request({
			url: url,
			method: this.options.ajaxmethod,
			data: data,
			onComplete : function (r) {
				Fabrik.fireEvent('fabrik.form.groups.save.completed', [this]);
				if (this.result === false) {
					this.result = true;
					return;
				}
				this.form.getElement('input[name=format]').value = orig;
				this.form.getElement('input[name=task]').value = origprocess;
				if (this.options.ajax) {
					Fabrik.fireEvent('fabrik.form.groups.save.end', [this, r]);
				}
				Fabrik.loader.stop(this.getBlock());
			}.bind(this)
		}).send();
	},

	changePage: function (dir) {
		this.changePageDir = dir;
		Fabrik.fireEvent('fabrik.form.page.change', [this, dir]);
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
		this._setMozBoxWidths();
		this.hideOtherPages();
		Fabrik.fireEvent('fabrik.form.page.chage.end', [this, dir]);
		Fabrik.fireEvent('fabrik.form.page.change.end', [this, dir]);
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
		var submit = this._getButton('Submit');
		var prev = this.form.getElement('.fabrikPagePrevious');
		var next = this.form.getElement('.fabrikPageNext');
		if (typeOf(next) !== 'null') {
			if (this.currentPage === this.options.pages.getKeys().length - 1) {
				if (typeOf(submit) !== 'null') {
					submit.disabled = "";
					submit.setStyle('opacity', 1);
				}
				next.disabled = "disabled";
				next.setStyle('opacity', 0.5);
			} else {
				if (typeOf(submit) !== 'null' && (this.options.rowid === '' || this.options.rowid.toString() === '0')) {
					submit.disabled = "disabled";
					submit.setStyle('opacity', 0.5);
				}
				next.disabled = "";
				next.setStyle('opacity', 1);
			}
		}
		if (typeOf(prev) !== 'null') {
			if (this.currentPage === 0) {
				prev.disabled = "disabled";
				prev.setStyle('opacity', 0.5);
			} else {
				prev.disabled = "";
				prev.setStyle('opacity', 1);
			}
		}
	},

	destroyElements: function () {
		this.formElements.each(function (el) {
			el.destroy();
		});
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
					// Paul - check that element exists before adding it http://fabrikar.com/forums/index.php?threads/ajax-validation-never-ending-in-forms.36907
					if (typeOf(document.id(el[1])) === 'null') {
						fconsole('Fabrik form::addElements: Cannot add element "' + el[1] + '" because it does not exist in HTML.');
						return;
					}
					var oEl = new window[el[0]](el[1], el[2]);
					added.push(this.addElement(oEl, el[1], gid));
				}
				else if (typeOf(el) === 'object') {
					// Paul - check that element exists before adding it http://fabrikar.com/forums/index.php?threads/ajax-validation-never-ending-in-forms.36907
					if (typeOf(document.id(el.options.element)) === 'null') {
						fconsole('Fabrik form::addElements: Cannot add element "' + el.options.element + '" because it does not exist in HTML.');
						return;
					}
					added.push(this.addElement(el, el.options.element, gid));
				}
				else if (typeOf(el) !== 'null') {
					fconsole('Fabrik form::addElements: Cannot add unknown element: ' + el);
				}
				else {
					fconsole('Fabrik form::addElements: Cannot add null element.');
				}
			}.bind(this));
		}.bind(this));
		// $$$ hugh - moved attachedToForm calls out of addElement to separate loop, to fix forward reference issue,
		// i.e. calc element adding events to other elements which come after itself, which won't be in formElements
		// yet if we do it in the previous loop ('cos the previous loop is where elements get added to formElements)
		for (i = 0; i < added.length; i ++) {
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
		this.submitBroker.addElement(elId, oEl);
		return oEl;
	},

	/**
	 * Dispatch an event to an element
	 *
	 * @param   string  elementType  Deprecated
	 * @param   string  elementId    Element key to look up in this.formElements
	 * @param   string  action       Event change/click etc.
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
		if (!el) {
			fconsole('Fabrik form::dispatchEvent: Cannot find element to add ' + action + ' event to: ' + elementId);
		}
		else if (js !== '') {
			el.addNewEvent(action, js);
		}
		else if (Fabrik.debug) {
			fconsole('Fabrik form::dispatchEvent: Javascript empty for ' + action + ' event on: ' + elementId);
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
	 * @param   string  id            Element id to observe
	 * @param   string  triggerEvent  Event type to add
	 */

	watchValidation: function (id, triggerEvent) {
		if (this.options.ajaxValidation === false) {
			return;
		}
		var el = document.id(id);
		if (typeOf(el) === 'null') {
			fconsole('Fabrik form::watchValidation: Could not add ' + triggerEvent + ' event because element "' + id + '" does not exist.');
			return;
		}
		if (el.className === 'fabrikSubElementContainer') {
			// check for things like radio buttons & checkboxes
			el.getElements('.fabrikinput').each(function (i) {
				i.addEvent(triggerEvent, function (e) {
					this.doElementValidation(e, true);
				}.bind(this));
			}.bind(this));
			return;
		}
		el.addEvent(triggerEvent, function (e) {
			this.doElementValidation(e, false);
		}.bind(this));
	},

	// as well as being called from watchValidation can be called from other
	// element js actions, e.g. date picker closing
	doElementValidation: function (e, subEl, replacetxt) {
		var id;
		if (this.options.ajaxValidation === false) {
			return;
		}
		replacetxt = typeOf(replacetxt) === 'null' ? '_time' : replacetxt;
		if (typeOf(e) === 'event' || typeOf(e) === 'object' || typeOf(e) === 'domevent') { // type object in
			id = e.target.id;
			// for elements with subelements e.g. checkboxes radiobuttons
			if (subEl === true) {
				id = document.id(e.target).getParent('.fabrikSubElementContainer').id;
			}
		} else {
			// hack for closing date picker where it seems the event object isn't
			// available
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
			id = id.replace(replacetxt, '');
			el = this.formElements.get(id);
			if (!el) {
				return;
			}
		}
		Fabrik.fireEvent('fabrik.form.element.validation.start', [this, el, e]);
		if (this.result === false) {
			this.result = true;
			return;
		}
		el.setErrorMessage(Joomla.JText._('COM_FABRIK_VALIDATING'), 'fabrikValidating');

		var d = $H(this.getFormData());
		d.set('task', 'form.ajax_validate');
		d.set('fabrik_ajax', '1');
		d.set('format', 'raw');

		d = this._prepareRepeatsForAjax(d);

		// $$$ hugh - nasty hack, because validate() in form model will always use _0 for
		// repeated id's
		var origid = id;
		if (el.origId) {
			origid = el.origId + '_0';
		}
		//var origid = el.origId ? el.origId : id;
		el.options.repeatCounter = el.options.repeatCounter ? el.options.repeatCounter : 0;
		var url = 'index.php?option=com_fabrik&form_id=' + this.id;
		if (this.options.lang !== false)
		{
			url += '&lang=' + this.options.lang;
		}
		var myAjax = new Request({
			url: url,
			method: this.options.ajaxmethod,
			data: d,
			onComplete: function (e) {
				this._completeValidaton(e, id, origid);
			}.bind(this)
		}).send();
	},

	_completeValidaton: function (r, id, origid) {
		r = JSON.decode(r);
		if (typeOf(r) === 'null') {
			this._showElementError(['Oups'], id);
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
			this._showElementError(r.errors[origid][el.options.repeatCounter], id);
		} else {
			this._showElementError([], id);
		}
		
		if (this.options.toggleSubmit)
		{
			if (this.options.mustValidate)
			{
				if (!this.hasErrors.has(id) || !this.hasErrors.get(id)) {
					this.mustValidateEls[id] = false;
				}
				if (!this.mustValidateEls.hasValue(true)) {
					this.toggleSubmit(true);
				}
			}
			else
			{
				this.toggleSubmit(this.hasErrors.getKeys().length === 0);
			}
		}
	},

	_prepareRepeatsForAjax : function (d) {
		this.getForm();
		//ensure we are dealing with a simple object
		if (typeOf(d) === 'hash') {
			d = d.getClean();
		}
		//data should be keyed on the data stored in the elements name between []'s which is the group id
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

	_showGroupError : function (r, d) {
		var tmperr;
		var gids = Array.from(this.options.pages.get(this.currentPage.toInt()));
		var err = false;
		$H(d).each(function (v, k) {
			k = k.replace(/\[(.*)\]/, '').replace(/%5B(.*)%5D/, '');// for dropdown validations
			if (this.formElements.has(k)) {
				var el = this.formElements.get(k);
				if (gids.contains(el.groupid.toInt())) {
					if (r.errors[k]) {
					// prepare error so that it only triggers for real errors and not success
					// msgs

						var msg = '';
						if (typeOf(r.errors[k]) !== 'null') {
							msg = r.errors[k].flatten().join('<br />');
						}
						if (msg !== '') {
							tmperr = this._showElementError(r.errors[k], k);
							if (err === false) {
								err = tmperr;
							}
						} else {
							el.setErrorMessage('', '');
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

		return err;
	},

	_showElementError : function (r, id) {
		// r should be the errors for the specific element, down to its repeat group
		// id.
		var msg = '';
		if (typeOf(r) !== 'null') {
			msg = r.flatten().join('<br />');
		}
		var classname = (msg === '') ? 'fabrikSuccess' : 'fabrikError';
		if (msg === '') {
			delete this.hasErrors[id];
			msg = Joomla.JText._('COM_FABRIK_SUCCESS');
		}
		else {
			this.hasErrors.set(id, true);
		}
		msg = '<span> ' + msg + '</span>';
		this.formElements.get(id).setErrorMessage(msg, classname);
		return (classname === 'fabrikSuccess') ? false : true;
	},

	updateMainError: function () {
		var myfx, activeValidations;
		var mainEr = this.form.getElement('.fabrikMainError');
		mainEr.set('html', this.options.error);
		activeValidations = this.form.getElements('.fabrikError').filter(
				function (e, index) {
			return !e.hasClass('fabrikMainError');
		});
		if (activeValidations.length > 0 && mainEr.hasClass('fabrikHide')) {
			this.showMainError(this.options.error);
		}
		if (activeValidations.length === 0) {
			this.hideMainError();
		}
	},

	hideMainError: function () {
		var mainEr = this.form.getElement('.fabrikMainError');
		myfx = new Fx.Tween(mainEr, {property: 'opacity',
				duration: 500,
				onComplete: function () {
					mainEr.addClass('fabrikHide');
				}
			}).start(1, 0);
	},

	showMainError: function (msg) {
		// If we are in j3 and ajax validations are on - don't show main error as it makes the form 'jumpy'
		if (Fabrik.bootstrapped && this.options.ajaxValidation) {
			return;
		}
		var mainEr = this.form.getElement('.fabrikMainError');
		mainEr.set('html', msg);
		mainEr.removeClass('fabrikHide');
		myfx = new Fx.Tween(mainEr, {property: 'opacity',
			duration: 500
		}).start(0, 1);
	},

	/** @since 3.0 get a form button name */
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

	watchSubmit: function () {
		var submit = this._getButton('Submit');
		if (!submit) {
			return;
		}
		var apply = this._getButton('apply'),
		del = this._getButton('delete'),
		copy = this._getButton('Copy');
		if (del) {
			del.addEvent('click', function (e) {
				if (confirm(Joomla.JText._('COM_FABRIK_CONFIRM_DELETE_1'))) {
					var res = Fabrik.fireEvent('fabrik.form.delete', [this, this.options.rowid]).eventResults;
					if (typeOf(res) === 'null' || res.length === 0 || !res.contains(false)) {
						// Task value is the same for front and admin
						this.form.getElement('input[name=task]').value = 'form.delete';
						this.doSubmit(e, del);
					} else {
						e.stop();
						return false;
					}

				} else {
					return false;
				}
			}.bind(this));
		}
		var submits = this.form.getElements('button[type=submit]').combine([apply, submit, copy]);
		submits.each(function (btn) {
			if (typeOf(btn) !== 'null') {
				btn.addEvent('click', function (e) {
					this.doSubmit(e, btn);
				}.bind(this));
			}
		}.bind(this));

		this.form.addEvent('submit', function (e) {
			this.doSubmit(e);
		}.bind(this));
	},

	mockSubmit: function() {
		var btn = this._getButton('Submit');
		if (!btn) {
			btn = new Element('button', {'name':'Submit','type':'submit'});
		}
		this.doSubmit(new Event.Mock(btn, 'click'), btn);
	},
	
	doSubmit: function (e, btn) {
		if (this.submitBroker.enabled()) {
			e.stop();
			return false;
		}
		this.submitBroker.submit(function () {
			if (this.options.showLoader) {
				Fabrik.loader.start(this.getBlock(), Joomla.JText._('COM_FABRIK_LOADING'));
			}
			Fabrik.fireEvent('fabrik.form.submit.start', [this, e, btn]);
			if (this.result === false) {
				this.result = true;
				e.stop();
				Fabrik.loader.stop(this.getBlock());
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
					// if showLoader is enabled (for non AJAX submits) the loader will already have been shown up there ^^
					if (!this.options.showLoader) {
						Fabrik.loader.start(this.getBlock(), Joomla.JText._('COM_FABRIK_LOADING'));
					}

					// Get all values from the form
					var data = $H(this.getFormData());
					data = this._prepareRepeatsForAjax(data);
					data[btn.name] = btn.value;
					if (btn.name === 'Copy') {
						data.Copy = 1;
						e.stop();
					}
					data.fabrik_ajax = '1';
					data.format = 'raw';
					var myajax = new Request.JSON({
						'url': this.form.action,
						'data': data,
						'method': this.options.ajaxmethod,
						onError: function (text, error) {
							fconsole(text + ": " + error);
							this.showMainError(error);
							Fabrik.loader.stop(this.getBlock(), 'Error in returned JSON');
						}.bind(this),

						onFailure: function (xhr) {
							fconsole(xhr);
							Fabrik.loader.stop(this.getBlock(), 'Ajax failure');
						}.bind(this),
						onComplete: function (json, txt) {
							if (typeOf(json) === 'null') {
								// Stop spinner
								Fabrik.loader.stop(this.getBlock(), 'Error in returned JSON');
								fconsole('error in returned json', json, txt);
								return;
							}
							// Process errors if there are some
							var errfound = false;
							if (json.errors !== undefined) {

								// For every element of the form update error message
								$H(json.errors).each(function (errors, key) {
									if (this.formElements.has(key) && errors.flatten().length > 0) {
										errfound = true;
										if (this.formElements[key].options.inRepeatGroup) {
											for (e = 0; e < errors.length; e++) {
												if (errors[e].flatten().length  > 0) {
													var this_key = key.replace(/(_\d+)$/, '_' + e);
													this._showElementError(errors[e], this_key);
												}
											}
										}
										else {
											this._showElementError(errors, key);
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
								Fabrik.loader.stop(this.getBlock());
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
										if (!json.suppressMsg) {
											alert(savedMsg);
										}
									}
								} else {
									clear_form = json.reset_form !== undefined ? json.reset_form : clear_form;
									if (!json.suppressMsg) {
										alert(savedMsg);
									}
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
					e.stop();
					Fabrik.fireEvent('fabrik.form.ajax.submit.end', [this]);
				} else {
					// Inject submit button name/value.
					if (typeOf(btn) !== 'null') {
						new Element('input', {type: 'hidden', name: btn.name, value: btn.value}).inject(this.form);
						this.form.submit();
					} else {
						// Regular button pressed which seems to be triggering form.submit() method.
						e.stop();
					}
				}
			}
		}.bind(this));
		e.stop();
	},

	/**
	 * Used to get the querystring data and
	 * for any element overwrite with its own data definition
	 * required for empty select lists which return undefined as their value if no
	 * items available
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
		var elKeys = this.formElements.getKeys();
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

	// $$$ hugh - added this, so far only used by cascading dropdown JS
	// to populate 'data' for the AJAX update, so custom cascade 'where' clauses
	// can use {placeholders}. Initially tried to use getFormData for this, but because
	// it adds ALL the query string args from the page, the AJAX call from cascade ended
	// up trying to submit the form. So, this func does what the commented out code in
	// getFormData used to do, and only fetches actual form element data.

	getFormElementData : function () {
		var h = {};
		this.formElements.each(function (el, key) {
			if (el.element) {
				h[key] = el.getValue();
				h[key + '_raw'] = h[key];
			}
		}.bind(this));
		return h;
	},

	watchGroupButtons : function () {

		this.form.addEvent('click:relay(.deleteGroup)', function (e, target) {
			e.preventDefault();
			var group = e.target.getParent('.fabrikGroup'),
				subGroup = e.target.getParent('.fabrikSubGroup');
			this.deleteGroup(e, group, subGroup);
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

	/**
	 * When editing a new form and when min groups set we need to duplicate each group
	 * by the min repeat value.
	 */
	duplicateGroupsToMin: function () {
		if (!this.form) {
			return;
		}

		Fabrik.fireEvent('fabrik.form.group.duplicate.min', [this]);

		Object.each(this.options.group_repeats, function (canRepeat, groupId) {

			if (typeOf(this.options.minRepeat[groupId]) === 'null') {
				return;
			}

			if (canRepeat.toInt() !== 1) {
				return;
			}

			var repeat_counter = this.form.getElement('#fabrik_repeat_group_' + groupId + '_counter'),
			repeat_rows, repeat_real, add_btn, deleteButton, i, repeat_id_0, deleteEvent;

			if (typeOf(repeat_counter) === 'null') {
				return;
			}

			repeat_rows = repeat_real = repeat_counter.value.toInt();

			if (repeat_rows === 1) {
				repeat_id_0 = this.form.getElement('#' + this.options.group_pk_ids[groupId] + '_0');

				if (typeOf(repeat_id_0) !== 'null' && repeat_id_0.value === '') {
					repeat_real = 0;
				}
			}

			var min = this.options.minRepeat[groupId].toInt();

			/**
			 * $$$ hugh - added ability to override min count
			 * http://fabrikar.com/forums/index.php?threads/how-to-initially-show-repeat-group.32911/#post-170147
			 * $$$ hugh - trying out min of 0 for Troester
			 * http://fabrikar.com/forums/index.php?threads/how-to-start-a-new-record-with-empty-repeat-group.34666/#post-175408
			 * $$$ paul - fixing min of 0 for Jaanus
			 * http://fabrikar.com/forums/index.php?threads/couple-issues-with-protostar-template.35917/
			 **/
			if (min === 0 && repeat_real === 0) {

				// Create mock event
				deleteButton = this.form.getElement('#group' + groupId + ' .deleteGroup');
				deleteEvent = typeOf(deleteButton) !== 'null' ? new Event.Mock(deleteButton, 'click') : false;
				var group = this.form.getElement('#group' + groupId),
				subGroup = group.getElement('.fabrikSubGroup');
				// Remove only group
				this.deleteGroup(deleteEvent, group, subGroup);

			}
			else if (repeat_rows < min) {
				// Create mock event
				add_btn = this.form.getElement('#group' + groupId + ' .addGroup');
				if (typeOf(add_btn) !== 'null') {
					var add_e = new Event.Mock(add_btn, 'click');

					// Duplicate group
					for (i = repeat_rows; i < min; i ++) {
						this.duplicateGroup(add_e);
					}
				}
			}
		}.bind(this));
	},

	/**
	 * Delete an repeating group
	 *
	 * @param e
	 * @param group
	 */
	deleteGroup: function (e, group, subGroup) {
		Fabrik.fireEvent('fabrik.form.group.delete', [this, e, group]);
		if (this.result === false) {
			this.result = true;
			return;
		}
		if (e) {
			e.stop();
		}

		// Find which repeat group was deleted
		var delIndex = 0;
		group.getElements('.deleteGroup').each(function (b, x) {
			if (b.getElement('img') === e.target || b.getElement('i') === e.target || b === e.target) {
				delIndex = x;
			}
		}.bind(this));
		var i = group.id.replace('group', '');

		var repeats = document.id('fabrik_repeat_group_' + i + '_counter').get('value').toInt();
		if (repeats <= this.options.minRepeat[i] && this.options.minRepeat[i] !== 0) {
			if (this.options.minMaxErrMsg[i] !== '')
			{
				var errorMessage = this.options.minMaxErrMsg[i];
				errorMessage = errorMessage.replace(/\{min\}/, this.options.minRepeat[i]);
				errorMessage = errorMessage.replace(/\{max\}/, this.options.maxRepeat[i]);
				alert(errorMessage);
			}
			return;
		}

		delete this.duplicatedGroups.i;
		if (document.id('fabrik_repeat_group_' + i + '_counter').value === '0') {
			return;
		}
		var subgroups = group.getElements('.fabrikSubGroup');

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
								delete this.formElements[k];
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
		// $$$ hugh - no, mustn't decrement this!  See comment in setupAll
		this.repeatGroupMarkers.set(i, this.repeatGroupMarkers.get(i) - 1);
		this.setRepeatGroupIntro(group, i);
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

	isFirstRepeatSubGroup: function (group) {
		var subgroups = group.getElements('.fabrikSubGroup');
		return subgroups.length === 1 && group.getElement('.fabrikNotice');
	},

	getSubGroupToClone: function (groupid) {
		var group = document.id('group' + groupid);
		var subgroup = group.getElement('.fabrikSubGroup');
		if (!subgroup) {
			subgroup = this.subGroups.get(groupid);
		}

		var clone = null;
		var found = false;
		if (this.duplicatedGroups.has(groupid)) {
			found = true;
		}
		if (!found) {
			clone = subgroup.cloneNode(true);
			this.duplicatedGroups.set(groupid, clone);
		} else {
			if (!subgroup) {
				clone = this.duplicatedGroups.get(groupid);
			} else {
				clone = subgroup.cloneNode(true);
			}
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
		Fabrik.fireEvent('fabrik.form.group.duplicate', [this, e]);
		if (this.result === false) {
			this.result = true;
			return;
		}
		if (e) {
			e.stop();
		}
		var i = e.target.getParent('.fabrikGroup').id.replace('group', '');
		var group_id = i.toInt();
		var group = document.id('group' + i);
		var c = this.repeatGroupMarkers.get(i);
		var repeats = document.id('fabrik_repeat_group_' + i + '_counter').get('value').toInt();
		if (repeats >= this.options.maxRepeat[i] && this.options.maxRepeat[i] !== 0) {
			if (this.options.minMaxErrMsg[i] !== '')
			{
				var errorMessage = this.options.minMaxErrMsg[i];
				errorMessage = errorMessage.replace(/\{min\}/, this.options.minRepeat[i]);
				errorMessage = errorMessage.replace(/\{max\}/, this.options.maxRepeat[i]);
				alert(errorMessage);
			}
			return;
		}
		document.id('fabrik_repeat_group_' + i + '_counter').value = repeats + 1;

		if (this.isFirstRepeatSubGroup(group)) {
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
			return;
		}

		var clone = this.getSubGroupToClone(i);
		var tocheck = this.repeatGetChecked(group);

		// Check for table style group, which may or may not have a tbody in it
		var groupTable = group.getElement('table.repeatGroupTable');
		if (groupTable) {
			if (groupTable.getElement('tbody')) {
				groupTable = groupTable.getElement('tbody');
			}
			groupTable.appendChild(clone);
		} else {
			group.appendChild(clone);
		}

		tocheck.each(function (i) {
			i.setProperty('checked', true);
		});

		this.subelementCounter = 0;
		// Remove values and increment ids
		var newElementControllers = [],
			hasSubElements = false,
			inputs = clone.getElements('.fabrikinput'),
			lastinput = null;
		this.formElements.each(function (el) {
			var formElementFound = false;
			subElementContainer = null;
			var subElementCounter = -1;
			inputs.each(function (input) {

				hasSubElements = el.hasSubElements();

				container = input.getParent('.fabrikSubElementContainer');
				var testid = (hasSubElements && container) ? container.id : input.id;
				var cloneName = el.getCloneName();

				// Test ===, plus special case for join rendered as auto-complete
				if (testid === cloneName || testid === cloneName + '-auto-complete') {
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
						// Note: Radio's etc. now have their events delegated from the form - so no need to duplicate them

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

				// This seems to be wrong, as it'll set origId to the repeat ID with the _X appended.
				//newEl.origId = origelid;

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
			if (!this.options.group_copy_element_values[group_id] || (this.options.group_copy_element_values[group_id] && newEl.element.name && newEl.element.name.test(pk_re))) {
				// Call reset method that resets both events and value back to default.
				newEl.reset();
			}
			else {
				// Call reset method that only resets the events, not the value
				newEl.resetEvents();
			}
		}.bind(this));
		var o = {};
		o[i] = newElementControllers;
		this.addElements(o);

		// Only scroll the window if the new element is not visible
		var win_size = window.getHeight(),
			win_scroll = document.id(window).getScroll().y,
			obj = clone.getCoordinates();
		// If the bottom of the new repeat goes below the bottom of the visible
		// window,
		// scroll up just enough to show it.
		if (obj.bottom > (win_scroll + win_size)) {
			var new_win_scroll = obj.bottom - win_size;
			this.winScroller.start(0, new_win_scroll);
		}

		var myFx = new Fx.Tween(clone, { 'property' : 'opacity',
			duration: 500
		}).set(0);

		clone.fade(1);
		// $$$ hugh - added groupid (i) and repeatCounter (c) as args
		// note I commented out the increment of c a few lines above//duplicate
		Fabrik.fireEvent('fabrik.form.group.duplicate.end', [this, e, i, c]);

		this.setRepeatGroupIntro(group, i);
		this.repeatGroupMarkers.set(i, this.repeatGroupMarkers.get(i) + 1);
	},

	/**
	 * Set the repeat group intro text
	 * @param group
	 * @param groupId
	 */
	setRepeatGroupIntro: function (group, groupId) {
		var intro = this.options.group_repeat_intro[groupId],
			tmpIntro = '',
			targets = group.getElements('*[data-role="group-repeat-intro"]');

		targets.each(function (target, i) {
			tmpIntro = intro.replace('{i}', i + 1);
			// poor man's parseMsgForPlaceholder ... ignore elements in joined groups.
			this.formElements.each(function (el) {
				if (!el.options.inRepeatGroup) {
					var re = new RegExp('\{' + el.element.id + '\}');
					// might should do a match first, to avoid always calling getValue(), just not sure which is more overhead!
					tmpIntro = tmpIntro.replace(re, el.getValue());
				}
			});
			target.set('html', tmpIntro);
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

	reset : function () {
		this.addedGroups.each(function (subgroup) {
			var group = document.id(subgroup).findClassUp('fabrikGroup');
			var i = group.id.replace('group', '');
			document.id('fabrik_repeat_group_' + i + '_counter').value = document.id('fabrik_repeat_group_' + i + '_counter').get('value').toInt() - 1;
			subgroup.remove();
		});
		this.addedGroups = [];
		Fabrik.fireEvent('fabrik.form.reset', [this]);
		if (this.result === false) {
			this.result = true;
			return;
		}
		this.formElements.each(function (el, key) {
			el.reset();
		}.bind(this));
	},

	showErrors : function (data) {
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

	/** add additional data to an element - e.g database join elements */
	appendInfo : function (data) {
		this.formElements.each(function (el, key) {
			if (el.appendInfo) {
				el.appendInfo(data, key);
			}
		}.bind(this));
	},

	clearForm : function () {
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

	stopEnterSubmitting: function () {
		var inputs = this.form.getElements('input.fabrikinput');
		inputs.each(function (el, i) {
			el.addEvent('keypress', function (e) {
				if (e.key === 'enter') {
					e.stop();
					if (inputs[i + 1]) {
						inputs[i + 1].focus();
					}
					//last one?
					if (i === inputs.length - 1) {
						this._getButton('Submit').focus();
					}
				}
			}.bind(this));
		}.bind(this));
	},

	getSubGroupCounter: function (group_id)
	{

	},
	
	addMustValidate: function (el)
	{
		if (this.options.ajaxValidation && this.options.toggleSubmit) {
			this.mustValidateEls.set(el.element.id, el.options.mustValidate);
			if (el.options.mustValidate) {
				this.options.mustValidate = true;
				this.toggleSubmit(false);
			}
		}
	},
	
	toggleSubmit: function (on)
	{
		var submit = this._getButton('Submit');
		if (typeOf(submit) !== 'null') {
			if (on === true) {
				submit.disabled = "";
				submit.setStyle('opacity', 1);
			}
			else {
				submit.disabled = "disabled";
				submit.setStyle('opacity', 0.5);				
			}
		}
	}
});
