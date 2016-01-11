/**
 * Form Autofill
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var Autofill = new Class({

	Implements: [Events, Options],

	options: {
		'observe': '',
		'trigger': '',
		cnn: 0,
		table: 0,
		map: '',
		editOrig: false,
		fillOnLoad: false,
		confirm: true,
		autofill_lookup_field: 0
	},

	initialize: function (options) {
		this.setOptions(options);
		this.attached = [];
		/*if (Browser.ie) {
			this.setUp(Fabrik.blocks['form_' + this.options.formid]);
		} else {
			Fabrik.addEvent('fabrik.form.elements.added', function (form) {
				this.setUp(form);
			}.bind(this));
		}*/
		this.setupDone = false;

		/*
		 * elements.added may or may not have fired, so give it the old college try first, which
		 * will work if the form is ready.  But also add an element.added event, in case the
		 * form isn't ready yet.
		 */

		this.setUp(Fabrik.getBlock('form_' + this.options.formid));
		Fabrik.addEvent('fabrik.form.elements.added', function (form) {
			this.setUp(form);
		}.bind(this));

		Fabrik.addEvent('fabrik.form.element.added', function (form, elId, oEl) {
			if (!this.element) {
				// if we are on the form load then this.element not set so return
				return;
			}
			// A group has been duplicated
			if (oEl.strElement === this.element.strElement) {
				// The element is a clone of our observable element
				this.element = false;
				this.setupDone = false;
				this.setUp(form);
			}
		}.bind(this));
	},

	/**
	 * get the observable element
	 *
	 * @param   int  repeatNum  if element to observe is in a repeat group which index'd element should be returned
	 *
	 * @return element object
	 */
	getElements: function (repeatNum) {
		var testE = false;
		var e = this.form.formElements.get(this.options.observe);

		// If its a joined element
		if (!e) {
			var repeatCount = 0;
			var k = Object.keys(this.form.formElements);
			var ii = k.each(function (i) {
				if (i.contains(this.options.observe)) {
					testE = this.form.formElements.get(i);
					if (!this.attached.contains(testE.options.element)) {
						// We havent previously observed this element, add it to this.attached
						// so that in the future we don't re-add it.
						this.attached.push(testE.options.element);
						//e = testE;
					}
					if (typeOf(repeatNum) === 'null' || repeatNum === repeatCount) {
						e = testE;
					}
					repeatCount ++;
				}
			}.bind(this));
		}
		else {
			this.attached.push(e.options.element);
		}
		return e;
	},

	setUp: function (form) {
		if (this.setupDone) {
			return;
		}
		if (typeOf(form) === 'null') {
			return;
		}
		try {
			this.form = form;
		} catch (err) {
			// form_x not found (detailed view perhaps)
			return;
		}
		
		/*
		var e = this.getElement();
		if (!e) {
			return false;
		}
		*/
		
		var evnt = function (e) {
			// Fabrik Trigger element object so don't use as this.element or lookup value will be wrong
			this.lookUp(e);
		}.bind(this);

		var testE = false;
		var e = this.form.formElements.get(this.options.observe);

		// If its a joined element
		if (!e) {
			var repeatCount = 0;
			var k = Object.keys(this.form.formElements);
			var ii = k.each(function (i) {
				if (i.contains(this.options.observe)) {
					testE = this.form.formElements.get(i);
					if (!this.attached.contains(testE.options.element)) {
						// We havent previously observed this element, add it to this.attached
						// so that in the future we don't re-add it.
						this.attached.push(testE.options.element);
						//e = testE;
					}
					repeatNum = testE.getRepeatNum();
					if (typeOf(repeatNum) === 'null' || repeatNum === repeatCount) {
						e = testE;
					}
					repeatCount ++;
				}
			}.bind(this));
		}

		this.element = e;
		if (this.options.trigger === '') {
			if (!this.element) {
				fconsole('autofill - couldnt find element to observe');
			} else {
				var elEvnt = this.element.getBlurEvent();
				this.form.dispatchEvent('', this.element.options.element, elEvnt, function (e) {

					// Fabrik element object that triggered the event
					// this.element = e;
					this.lookUp(e);
				}.bind(this));
			}
		} else {
			this.form.dispatchEvent('', this.options.trigger, 'click', evnt);
		}
		if (this.options.fillOnLoad) {
			var t = this.options.trigger === '' ? this.element.strElement : this.options.trigger;
			this.form.dispatchEvent('', t, 'load', evnt);
		}

		this.setupDone = true;
	},

	// perform ajax lookup when the observer element is blurred

	lookUp: function (el) {
		if (this.options.trigger) {
			// work out observed event
		}
		else {
			this.element = el;
		}
		
		if (this.options.confirm === true) {
			if (!confirm(Joomla.JText._('PLG_FORM_AUTOFILL_DO_UPDATE'))) {
				return;
			}
		}
		Fabrik.loader.start('form_' + this.options.formid, Joomla.JText._('PLG_FORM_AUTOFILL_SEARCHING'));

		if (!this.element) {
			this.element = this.getElement(0);
		}
		var v = this.element.getValue();
		var formid = this.options.formid;
		var observe = this.options.observe;

		var myAjax = new Request.JSON({
			'evalScripts': true,
			'data': {
				'option': 'com_fabrik',
				'format': 'raw',
				'task': 'plugin.pluginAjax',
				'plugin': 'autofill',
				'method': 'ajax_getAutoFill',
				'g': 'form',
				'v': v,
				'formid': formid,
				'observe': observe,
				'cnn': this.options.cnn,
				'table': this.options.table,
				'map': this.options.map,
				'autofill_lookup_field': this.options.autofill_lookup_field
			},
			onCancel: function () {
				Fabrik.loader.stop('form_' + this.options.formid);
			}.bind(this),

			onFailure: function (xhr) {
				Fabrik.loader.stop('form_' + this.options.formid);
				alert(this.getHeader('Status'));
			},
			onError: function (text, error) {
				Fabrik.loader.stop('form_' + this.options.formid);
				fconsole(text + ' ' + error);
			}.bind(this),
			onSuccess: function (json, responseText) {
				Fabrik.loader.stop('form_' + this.options.formid);
				this.updateForm(json);
			}.bind(this)
		}).send();
	},

	// Update the form from the ajax request returned data
	updateForm: function (json) {
		this.json = $H(json);
		Fabrik.fireEvent('fabrik.form.autofill.update.start', [this, json]);

		var repeatNum = this.element.getRepeatNum();

		if (this.json.length === 0) {
			alert(Joomla.JText._('PLG_FORM_AUTOFILL_NORECORDS_FOUND'));
		}

		this.json.each(function (val, key) {
			var k2 = key.substr(key.length - 4, 4);
			if (k2 === '_raw') {
				key = key.replace('_raw', '');
				var origKey = key;
				if (!this.tryUpdate(key, val)) {
					/**
					 * If the val is an object, then the target element is intended to be a repeat.
					 * So whip round the val's, seeing if we have a matching _X repeat of the target.
					 * For now, this is just a simple minded attempt to fill out existing repeats, we're
					 * not going to create new groups.  Implementing this for a specific client setup.  Maybe
					 * come back later and make this smarter.
					 */
					if (typeof val === 'object') {
						val = $H(val);
						val.each(function (v, k) {
							k2 = key + '_' + k;
							this.tryUpdate(k2, v);
						}.bind(this));
					}
					else {
						if (repeatNum) {
							key += '_' + repeatNum;
						} else {
							key += '_0';
						}
						if (!this.tryUpdate(key, val)) {
							// See if the user has used simply the full element name rather than the full element name with
							// the join string
							key = 'join___' + this.element.options.joinid + '___' + key;
	
							// Perhaps element is in main group and update element in repeat group :S
							if (!this.tryUpdate(origKey, val, true)) {
							}
						}
					}
				}
			}
		}.bind(this));
		if (this.options.editOrig === true) {
			this.form.getForm().getElement('input[name=rowid]').value = this.json.__pk_val;
		}
		Fabrik.fireEvent('fabrik.form.autofill.update.end', [this, json]);
	},

	/**
	 * Try to update an element
	 *
	 * @param   string  key         Form.formElements key to update
	 * @param   string  value       Value to update to
	 * @param   bool    looseMatch  Should we test if the key is contained within any of Form.formElements keys?
	 *
	 * @return  bool  True if update occured
	 */
	tryUpdate: function (key, val, looseMatch) {
		looseMatch = looseMatch ? true : false;
		if (!looseMatch) {
			var el = this.form.formElements.get(key);
			if (typeOf(el) !== 'null') {
				// $$$ hugh - nasty litte hack to get auto-complete joins to properly update, if we don't set
				// el.activePopUp, the displayed label value won't get updated properly in the join's update() processing
				if (typeOf(el.options.displayType !== 'null') && el.options.displayType === 'auto-complete') {
					el.activePopUp = true;
				}
				el.update(val);
				
				// Trigger change events to automatcially fire any other chained auto-fill form plugins
				el.element.fireEvent(el.getBlurEvent(), new Event.Mock(el.element, el.getBlurEvent()));
				return true;
			}
		} else {
			var m = Object.keys(this.form.formElements).filter(function (k, v) {
				return k.contains(key);
			});
			if (m.length > 0) {
				m.each(function (key) {
					var el = this.form.formElements.get(key);
					el.update(val);
					
					// Trigger change events to automatcially fire any other chained auto-fill form plugins
					el.element.fireEvent(el.getBlurEvent(), new Event.Mock(el.element, el.getBlurEvent()));
				}.bind(this));
				return true;
			}
		}
		return false;
	}

});