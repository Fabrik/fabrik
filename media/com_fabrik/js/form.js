 /**
 * @author Robert
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
		'delayedEvents': false,
		'updatedMsg': 'Form saved',
		'pages': [],
		'start_page': 0,
		'ajaxValidation': false,
		'customJsAction': '',
		'plugins': [],
		'ajaxmethod': 'post',
		'inlineMessage': true,
		'images': {
			'alert': '',
			'action_check': '',
			'ajax_loader': ''
		}
	},
	
	initialize: function (id, options) {
		// $$$ hugh - seems options.rowid can be null in certain corner cases, so defend against that
		if (typeOf(options.rowid === 'null')) {
			options.rowid = '';
		}
		this.id = id;
		this.result = true; //set this to false in window.fireEvents to stop current action (eg stop form submission)
		this.setOptions(options);
		this.plugins = this.options.plugins;
		this.options.pages = $H(this.options.pages);
		this.subGroups = $H({});
		this.currentPage = this.options.start_page;
		this.formElements = $H({});
		this.bufferedEvents = [];
		this.duplicatedGroups = $H({});
	
		this.fx = {};
		this.fx.elements = [];
		this.fx.validations = {};
		this.setUpAll();
		this._setMozBoxWidths();
	},
	
	_setMozBoxWidths: function () {
		if (Browser.firefox) {
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
		if (this.options.ajax || this.options.submitOnEnter === false) {
			this.stopEnterSubmitting();
		}
		this.watchAddOptions();
		$H(this.options.hiddenGroup).each(function (v, k) {
			if (v === true && typeOf(document.id('group' + k)) !== 'null') {
				var subGroup = document.id('group' + k).getElement('.fabrikSubGroup');
				this.subGroups.set(k, subGroup.cloneWithIds());
				this.hideLastGroup(k, subGroup);
			}
		}.bind(this));

		// get an int from which to start incrementing for each repeated group id
		// dont ever decrease this value when deleteing a group as it will cause all sorts of
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

		// IE8 if rowid isnt set here its most likely because you are rendering as a J article plugin and have done:
		// <p>{fabrik view=form id=1}</p> 
		// form block level elements should not be encased in <p>'s
		
		// testing prev/next buttons
		var v = this.options.editable === true ? 'form' : 'details';
		var rowInput = this.form.getElement('input[name=rowid]');
		var rowId = typeOf(rowInput) === 'null' ? '' : rowInput.value;
		var editopts = {
			option : 'com_fabrik',
			'view' : v,
			'controller' : 'form',
			'fabrik' : this.id,
			'rowid' : rowId,
			'format' : 'raw',
			'task' : 'paginate',
			'dir' : 1
		};
		[ '.previous-record', '.next-record' ].each(function (b, dir) {
			editopts.dir = dir;
			if (this.form.getElement(b)) {

				var myAjax = new Request({
					url : 'index.php',
					method : this.options.ajaxmethod,
					data : editopts,
					onComplete : function (r) {
						Fabrik.loader.stop('form_' + this.id);
						r = JSON.decode(r);
						this.update(r);
						this.form.getElement('input[name=rowid]').value = r.post.rowid;
					}.bind(this)
				});

				this.form.getElement(b).addEvent('click', function (e) {
					myAjax.options.data.rowid = this.form.getElement('input[name=rowid]').value;
					e.stop();
					Fabrik.loader.start('form_' + this.id, Joomla.JText._('COM_FABRIK_LOADING'));
					myAjax.send();
				}.bind(this));
			}
		}.bind(this));
	},

	watchAddOptions : function () {
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

	setUp : function () {
		this.form = this.getForm();
		this.watchGroupButtons();
		//if (this.options.editable) { //submit can appear in confirmation plugin even when readonly
		this.watchSubmit();
		//}
		this.createPages();
		this.watchClearSession();
	},

	getForm : function () {
		this.form = document.id(this.getBlock());
		return this.form;
	},

	getBlock : function () {
		return this.options.editable === true ? 'form_' + this.id : 'details_' + this.id;
	},

	// id is the element or group to apply the fx TO, triggered from another
	// element
	addElementFX : function (id, method) {
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
				return;
			}
			c = document.id(id).getParent('.fabrikElementContainer');
		}
		if (c) {
			// c will be the <li> element - you can't apply fx's to this as it makes the
			// DOM squiffy with
			// multi column rows, so get the li's content and put it inside a div which
			// is injected into c
			// apply fx to div rather than li - damn im good
			if ((c).get('tag') === 'li') {
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
			this.fx.elements[k] = {};
			//'opacity',
			this.fx.elements[k].css = new Fx.Morph(fxdiv, opts);
			if (typeOf(fxdiv) !== 'null' && (method === 'slide in' || method === 'slide out' || method === 'slide toggle')) {
				this.fx.elements[k].slide = new Fx.Slide(fxdiv, opts);
			} else {
				this.fx.elements[k].slide = null;
			}
		}
	},

	doElementFX : function (id, method) {
		var k, groupfx, fx, fxElement;
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
		fx = this.fx.elements[k];
		if (!fx) {
			return;
		}
		fxElement = groupfx ? fx.css.element : fx.css.element.getParent('.fabrikElementContainer');
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

	watchClearSession : function () {
		if (this.form && this.form.getElement('.clearSession')) {
			this.form.getElement('.clearSession').addEvent('click', function (e) {
				e.stop();
				this.form.getElement('input[name=task]').value = 'removeSession';
				this.clearForm();
				this.form.submit();
			}.bind(this));
		}
	},

	createPages : function () {
		if (this.options.pages.getKeys().length > 1) {
			// wrap each page in its own div
			this.options.pages.each(function (page, i) {
				var p = new Element('div', {
					'class' : 'page',
					'id' : 'page_' + i
				});
				p.inject(document.id('group' + page[0]), 'before');
				page.each(function (group) {
					p.adopt(document.id('group' + group));
				});
			});
			var submit = this._getButton('submit');
			if (submit && this.options.rowid === '') {
				submit.disabled = "disabled";
				submit.setStyle('opacity', 0.5);
			}
			this.form.getElement('.fabrikPagePrevious').disabled = "disabled";
			this.form.getElement('.fabrikPageNext').addEvent('click', function (e) {
				this._doPageNav(e, 1);
			}.bind(this));
			this.form.getElement('.fabrikPagePrevious').addEvent('click', function (e) {
				this._doPageNav(e, -1);
			}.bind(this));
			this.setPageButtons();
			this.hideOtherPages();
		}
	},

	/**
	 * Move forward/backwards in multipage form
	 * 
	 * @param   event  e
	 * @param   int    dir  1/-1
	 */
	_doPageNav : function (e, dir) {
		if (this.options.editable) {
			this.form.getElement('.fabrikMainError').addClass('fabrikHide');
			
			// If tip shown at bottom of long page and next page shorter we need to move the tip to
			// the top of the page to avoid large space appearing at the bottom of the page.
			if (typeOf(document.getElement('.tool-tip')) !== 'null') {
				document.getElement('.tool-tip').setStyle('top', 0);
			}
			var url = Fabrik.liveSite + 'index.php?option=com_fabrik&format=raw&task=form.ajax_validate&form_id=' + this.id;
			Fabrik.loader.start('form_' + this.id, Joomla.JText._('COM_FABRIK_VALIDATING'));
	
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
					Fabrik.loader.stop('form_' + this.id);
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

		var url = Fabrik.liveSite + 'index.php?option=com_fabrik&format=raw&page=' + this.currentPage;
		Fabrik.loader.start('form_' + this.id, 'saving page');
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
				Fabrik.loader.stop('form_' + this.id);
			}.bind(this)
		}).send();
	},

	changePage: function (dir) {
		this.changePageDir = dir;
		Fabrik.fireEvent('fabrik.form.page.change', [this]);
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
		Fabrik.fireEvent('fabrik.form.page.chage.end', [this]);
		if (this.result === false) {
			this.result = true;
			return;
		}
	},

	pageGroupsVisible: function () {
		var visible = false;
		this.options.pages.get(this.currentPage).each(function (gid) {
			if (document.id('group' + gid).getStyle('display') !== 'none') {
				visible = true;
			}
		});
		return visible;
	},

	/**
	 * hide all groups except those in the active page
	 */
	hideOtherPages : function () {
		this.options.pages.each(function (gids, i) {
			if (i.toInt() !== this.currentPage.toInt()) {
				document.id('page_' + i).setStyle('display', 'none');
			}
		}.bind(this));
	},

	setPageButtons : function () {
		var submit = this._getButton('submit');
		var prev = this.form.getElement('.fabrikPagePrevious');
		var next = this.form.getElement('.fabrikPageNext');
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
		if (this.currentPage === 0) {
			prev.disabled = "disabled";
			prev.setStyle('opacity', 0.5);
		} else {
			prev.disabled = "";
			prev.setStyle('opacity', 1);
		}
	},

	addElements: function (a) {
		a = $H(a);
		a.each(function (elements, gid) {
			elements.each(function (el) {
				if (typeOf(el) !== 'null') {
					this.addElement(el, el.options.element, gid);
				}
			}.bind(this));
		}.bind(this));
		// $$$ hugh - moved attachedToForm calls out of addElement to separate loop, to fix forward reference issue,
		// i.e. calc element adding events to other elements which come after itself, which won't be in formElements
		// yet if we do it in the previous loop ('cos the previous loop is where elements get added to formElements)
		a.each(function (elements) {
			elements.each(function (el) {
				if (typeOf(el) !== 'null') {
					try {
						el.attachedToForm();
					} catch (err) {
						fconsole(el.options.element + ' attach to form:' + err);
					}
				}
			}.bind(this));
		}.bind(this));
		Fabrik.fireEvent('fabrik.form.elements.added', [this]);
	},

	addElement : function (oEl, elId, gid) {
		elId = elId.replace('[]', '');
		var ro = elId.substring(elId.length - 3, elId.length) === '_ro';
		oEl.form = this;
		oEl.groupid = gid;
		this.formElements.set(elId, oEl);
		Fabrik.fireEvent('fabrik.form.element.added', [this, elId, oEl]);
		// $$$ hugh - moved this to addElements, see comment above
		/*
		try {
			oEl.attachedToForm();
		} catch (err) {
			fconsole(elId + ' attach to form:' + err );
		}
		*/
		if (ro) {
			elId = elId.substr(0, elId.length - 3);
			this.formElements.set(elId, oEl);
		}
	},

	// we have to buffer the events in a pop up window as
	// the dom inserted when the window loads appears after the ajax evalscripts

	dispatchEvent : function (elementType, elementId, action, js) {
		if (!this.options.delayedEvents) {
			var el = this.formElements.get(elementId);
			if (el && js !== '') {
				el.addNewEvent(action, js);
			}
		} else {
			this.bufferEvent(elementType, elementId, action, js);
		}
	},

	bufferEvent : function (elementType, elementId, action, js) {
		this.bufferedEvents.push([ elementType, elementId, action, js ]);
	},

	// call this after the popup window has loaded
	processBufferEvents : function () {
		this.setUp();
		this.options.delayedEvents = false;
		this.bufferedEvents.each(function (r) {
			// refresh the element ref
			var elementId = r[1];
			var el = this.formElements.get(elementId);
			el.element = document.id(elementId);
			this.dispatchEvent(r[0], elementId, r[2], r[3]);
		}.bind(this));
	},

	action : function (task, el) {
		var oEl = this.formElements.get(el);
		Browser.exec('oEl.' + task + '()');
	},

	triggerEvents : function (el) {
		this.formElements.get(el).fireEvents(arguments[1]);
	},

	/**
	 * @param string element id to observe
	 * @param string event type to add
	 */
	
	watchValidation : function (id, triggerEvent) {
		if (this.options.ajaxValidation === false) {
			return;
		}
		var el = document.id(id);
		if (typeOf(el) === 'null') {
			fconsole('watch validation failed, could not find element ' + id);
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
			// for elements with subelements eg checkboxes radiobuttons
			if (subEl === true) {
				id = document.id(e.target).getParent('.fabrikSubElementContainer').id;
			}
		} else {
			// hack for closing date picker where it seems the event object isnt
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
		Fabrik.fireEvent('fabrik.form.element.validaton.start', [this, el, e]);
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
		var url = Fabrik.liveSite + 'index.php?option=com_fabrik&form_id=' + this.id;
		var myAjax = new Request({
			url: url,
			method: this.options.ajaxmethod,
			data: d,
			onComplete: function (e) {
				this._completeValidaton(e, id, origid);
			}.bind(this)
		}).send();
	},

	_completeValidaton : function (r, id, origid) {
		r = JSON.decode(r);
		this.formElements.each(function (el, key) {
			el.afterAjaxValidation();
		});
		Fabrik.fireEvent('fabrik.form.elemnet.validation.complete', [this, r, id, origid]);
		if (this.result === false) {
			this.result = true;
			return;
		}
		var el = this.formElements.get(id);
		if ((r.modified[origid] !== undefined)) {
			el.update(r.modified[origid]);
		}
		if (typeOf(r.errors[origid]) !== 'null') {
			this._showElementError(r.errors[origid][el.options.repeatCounter], id);
		} else {
			this._showElementError([], id);
		}
	},

	_prepareRepeatsForAjax : function (d) {
		this.getForm();
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
					// prepare error so that it only triggers for real errors and not sucess
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
			msg = Joomla.JText._('COM_FABRIK_SUCCESS');
		}
		msg = '<span>' + msg + '</span>';
		this.formElements.get(id).setErrorMessage(msg, classname);
		return (classname === 'fabrikSuccess') ? false : true;
	},

	updateMainError : function () {
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
		var mainEr = this.form.getElement('.fabrikMainError');
		mainEr.set('html', msg);
		mainEr.removeClass('fabrikHide');
		myfx = new Fx.Tween(mainEr, {property: 'opacity',
			duration: 500
		}).start(0, 1);
	},
	
	/** @since 3.0 get a form button name */
	_getButton : function (name) {
		var b = this.form.getElement('input[type=button][name=' + name + ']');
		if (!b) {
			b = this.form.getElement('input[type=submit][name=' + name + ']');
		} 
		return b;
	},

	watchSubmit : function () {
		var submit = this._getButton('submit');
		if (!submit) {
			return;
		}
		var apply = this._getButton('apply');
		if (this.form.getElement('input[name=delete]')) {
			this.form.getElement('input[name=delete]').addEvent('click', function (e) {
				if (confirm(Joomla.JText._('COM_FABRIK_CONFIRM_DELETE'))) {
					this.form.getElement('input[name=task]').value = this.options.admin ? 'form.delete' : 'delete';
				} else {
					return false;
				}
			}.bind(this));
		}
		if (this.options.ajax) {
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
	},

	doSubmit : function (e, btn) {
		Fabrik.fireEvent('fabrik.form.submit.start', [this, e, btn]);
		this.elementsBeforeSubmit(e);
		if (this.result === false) {
			this.result = true;
			e.stop();
			// update global status error
			this.updateMainError();
		}
		//insert a hidden element so we can reload the last page if validation vails
		if (this.options.pages.getKeys().length > 1) {
			this.form.adopt(new Element('input', {'name': 'currentPage', 'value': this.currentPage.toInt(), 'type': 'hidden'}));
		}
		if (this.options.ajax) {
			//do ajax val only if onSubmit val ok
			if (this.form) {
				Fabrik.loader.start('form_' + this.id, Joomla.JText._('COM_FABRIK_LOADING'));
				// $$$ hugh - we already did elementsBeforeSubmit() this at the start of this func?
				// (and we're going to call it again in getFormData()!)
				//this.elementsBeforeSubmit(e);
				// get all values from the form
				var data = $H(this.getFormData());
				data = this._prepareRepeatsForAjax(data);
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
						Fabrik.loader.stop('form_' + this.id, 'Error in returned JSON');
					}.bind(this),
					
					onFailure: function (xhr) {
						fconsole(xhr);
						Fabrik.loader.stop('form_' + this.id, 'Ajax failure');
					}.bind(this),
					onComplete : function (json, txt) {
						if (typeOf(json) === 'null') {
							// stop spinner
							Fabrik.loader.stop('form_' + this.id, 'Error in returned JSON');
							fconsole('error in returned json', json, txt);
							return;
						}
						// process errors if there are some
						var errfound = false;
						if (json.errors !== undefined) {
							// for every element of the form update error message
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
						// update global status error
						this.updateMainError();

						if (errfound === false) {
							var clear_form = btn.name !== 'apply';
							Fabrik.loader.stop('form_' + this.id);
							var saved_msg = json.msg !== undefined ? json.msg :Joomla.JText._('COM_FABRIK_FORM_SAVED');
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
									alert(saved_msg);
								}
							} else {
								clear_form = json.reset_form !== undefined ? json.reset_form : clear_form;
								alert(saved_msg);
							}
							//query the list to get the updated data
							Fabrik.fireEvent('fabrik.form.submitted', [this, json]);
							if (btn.name !== 'apply') {
								if (clear_form) {
									this.clearForm();
								}
								//if the form was loaded in a Fabrik.Window close the window.
								if (Fabrik.Windows[this.options.fabrik_window_id]) {
									Fabrik.Windows[this.options.fabrik_window_id].close();
								}
							}
						} else {
							Fabrik.fireEvent('fabrik.form.submit.failed', [this, json]);
							// stop spinner
							Fabrik.loader.stop('form_' + this.id, Joomla.JText._('COM_FABRIK_VALIDATION_ERROR'));
						}
					}.bind(this)
				}).send();
			}
		}
		Fabrik.fireEvent('fabrik.form.submit.end', [this]);
		if (this.result === false) {
			this.result = true;
			e.stop();
			// update global status error
			this.updateMainError();
		} else {
			/**
			 * enables the list to clean up the form and custom events
			 */
			if (this.options.ajax) {
				Fabrik.fireEvent('fabrik.form.ajax.submit.end', [this]);
			}
		}
	},

	elementsBeforeSubmit : function (e) {
		this.formElements.each(function (el, key) {
			if (!el.onsubmit()) {
				e.stop();
			}
		});
	},

	// used to get the querystring data and
	// for any element overwrite with its own data definition
	// required for empty select lists which return undefined as their value if no
	// items
	// available

	getFormData : function () {
		this.formElements.each(function (el, key) {
			el.onsubmit();
		});
		this.getForm();
		var s = this.form.toQueryString();
		var h = {};
		s = s.split('&');
		var arrayCounters = $H({});
		s.each(function (p) {
			p = p.split('=');
			var k = p[0];
			// $$$ rob deal with checkboxes
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
	// getFormData used to do, and only fecthes actual form element data.

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
		this.form.getElements('.deleteGroup').each(function (g, i) {
			g.addEvent('click', function (e) {
				this.deleteGroup(e);
			}.bind(this));
		}.bind(this));
		
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

	deleteGroup: function (e) {
		Fabrik.fireEvent('fabrik.form.group.delete', [this, e]);
		if (this.result === false) {
			this.result = true;
			return;
		}
		e.stop();
		var group = e.target.getParent('.fabrikGroup');
		// find which repeat group was deleted
		var delIndex = 0;
		group.getElements('.deleteGroup').each(function (b, x) {
			if (b.getElement('img') === e.target) {
				delIndex = x;
			}
		}.bind(this));
		var i = group.id.replace('group', '');
		delete this.duplicatedGroups.i;
		if (document.id('fabrik_repeat_group_' + i + '_counter').value === '0') {
			return;
		}
		var subgroups = group.getElements('.fabrikSubGroup');

		var subGroup = e.target.getParent('.fabrikSubGroup');
		this.subGroups.set(i, subGroup.clone());
		if (subgroups.length <= 1) {
			this.hideLastGroup(i, subGroup);

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
					// minus the removed group
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
		// update the hidden field containing number of repeat groups
		document.id('fabrik_repeat_group_' + i + '_counter').value = document.id('fabrik_repeat_group_' + i + '_counter').get('value').toInt() - 1;
		// $$$ hugh - no, musn't decrement this!  See comment in setupAll
		this.repeatGroupMarkers.set(i, this.repeatGroupMarkers.get(i) - 1);
		Fabrik.fireEvent('fabrik.form.group.delete.end', [this, e, i, delIndex]);

	},

	hideLastGroup : function (groupid, subGroup) {
		var sge = subGroup.getElement('.fabrikSubGroupElements');
		var notice = new Element('div', {'class': 'fabrikNotice'}).appendText(Joomla.JText._('COM_FABRIK_NO_REPEAT_GROUP_DATA'));
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

	isFirstRepeatSubGroup : function (group) {
		var subgroups = group.getElements('.fabrikSubGroup');
		return subgroups.length === 1 && group.getElement('.fabrikNotice');
	},

	getSubGroupToClone : function (groupid) {
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

	repeatGetChecked : function (group) {
		// /stupid fix for radio buttons loosing their checked value
		var tocheck = [];
		group.getElements('.fabrikinput').each(function (i) {
			if (i.type === 'radio' && i.getProperty('checked')) {
				tocheck.push(i);
			}
		});
		return tocheck;
	},

	/* duplicates the groups sub group and places it at the end of the group */

	duplicateGroup : function (e) {
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
		
		var cloneFromRepeatCount = '0';
		if (e) {
			var pk_id = this.options.group_pk_ids[group_id];
			var pk_el = e.target.findClassUp('fabrikSubGroup').getElement("[name*=[" + pk_id + "]]");
			var re = new RegExp('join\\[\\d+\\]\\[' + pk_id + '\\]\\[(\\d+)\\]');
			if (typeOf(pk_el) !== 'null' && pk_el.name.test(re)) {
				cloneFromRepeatCount = pk_el.name.match(re)[1];
			}
		}
		var clone = this.getSubGroupToClone(i);
		var tocheck = this.repeatGetChecked(group);

		if (group.getElement('table.repeatGroupTable')) {
			group.getElement('table.repeatGroupTable').appendChild(clone);
		} else {
			group.appendChild(clone);
		}
		
		tocheck.each(function (i) {
			i.setProperty('checked', true);
		});
		// remove values and increment ids
		var newElementControllers = [];
		this.subelementCounter = 0;
		var hasSubElements = false;
		var inputs = clone.getElements('.fabrikinput');
		var lastinput = null;
		this.formElements.each(function (el) {
			var formElementFound = false;
			subElementContainer = null;
			var subElementCounter = -1;
			inputs.each(function (input) {

				hasSubElements = el.hasSubElements();

				// for all instances of the call to findClassUp use el.element rather
				// than input (HMM SEE LINE 912 - PERHAPS WE CAN REVERT TO USING INPUT
				// NOW?)
				container = input.getParent('.fabrikSubElementContainer');
				var testid = (hasSubElements && container) ? container.id : input.id;
				var cloneName = el.getCloneName();
				if (cloneName === testid) {
					lastinput = input;
					formElementFound = true;

					if (hasSubElements) {
						subElementCounter++;
						subElementContainer = input.getParent('.fabrikSubElementContainer');
						// clone the first inputs event to all subelements
						// $$$ hugh - sanity check in case we have an element which has no input
						if (document.id(testid).getElement('input')) {
							input.cloneEvents(document.id(testid).getElement('input'));
						}
						// Note: Radio's etc now have their events delegated from the form - so no need to duplicate them
						
					} else {
						input.cloneEvents(el.element);

						// update the element id use el.element.id rather than input.id as
						// that may contain _1 at end of id
						var bits = Array.from(el.element.id.split('_'));
						bits.splice(bits.length - 1, 1, c);
						input.id = bits.join('_');

						// update labels for non sub elements
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
			var pk_re = new RegExp('\\[' + this.options.group_pk_ids[group_id] + '\\]');
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
		var win_size = window.getHeight();
		var win_scroll = document.id(window).getScroll().y;
		var obj = clone.getCoordinates();
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
		this.repeatGroupMarkers.set(i, this.repeatGroupMarkers.get(i) + 1);
	},

	update : function (o) {
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
						this._getButton('submit').focus(); 
					}
				}
			}.bind(this));
		}.bind(this));
	},
	
	getSubGroupCounter: function (group_id)
	{
		
	}
});
