/**
 * Form Autofill
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
define(['jquery', 'fab/fabrik'], function (jQuery, Fabrik) {
	'use strict';
	var Autofill = new Class({

		Implements: [Events],

		options: {
			'observe'            : '',
			'trigger'            : '',
			cnn                  : 0,
			table                : 0,
			map                  : '',
			editOrig             : false,
			fillOnLoad           : false,
			confirm              : true,
			autofill_lookup_field: 0,
			showNotFound         : false,
			notFoundMsg          : ''
		},

		/**
		 * Initialize
		 * @param {object} options
		 */
		initialize: function (options) {
			var self = this;
			this.options = jQuery.extend(this.options, options);
			this.attached = [];
			this.newAttach = [];
			this.setupDone = false;

			/*
			 * elements.added may or may not have fired, so give it the old college try first, which
			 * will work if the form is ready.  But also add an element.added event, in case the
			 * form isn't ready yet.
			 */

			this.setUp(Fabrik.getBlock('form_' + this.options.formid));
			Fabrik.addEvent('fabrik.form.elements.added', function (form) {
				self.setUp(form);
			});
		},

		/**
		 * Get the observable element
		 *
		 * @param   {int}  repeatNum  if element to observe is in a repeat group which index'd element should be returned
		 *
		 * @return {object} element
		 */
		getElement: function (repeatNum) {
			var testE = false,
				self = this,
				e = this.form.formElements.get(this.options.observe),
				k, repeatCount = 0;

			// If its a joined element
			if (!e) {
				k = Object.keys(this.form.formElements);
				k.each(function (i) {
					if (i.contains(self.options.observe)) {
						testE = self.form.formElements.get(i);
						if (!self.attached.contains(testE.options.element)) {
							// We havent previously observed this element, add it to this.attached
							// so that in the future we don't re-add it.
							self.attached.push(testE.options.element);
							//e = testE;
						}
						if (typeOf(repeatNum) === 'null' || repeatNum === repeatCount) {
							e = testE;
						}
						repeatCount++;
					}
				});
			} else {
				this.attached.push(e.options.element);
			}
			return e;
		},

		doLookup: function(e) {
			this.lookUp(e);
		},

		/**
		 *
		 * @param {object} form
		 */
		setUp: function (form) {
			var self = this;
			if (this.setupDone) {
				return;
			}
			if (form === undefined) {
				return;
			}
			try {
				this.form = form;
			} catch (err) {
				// form_x not found (detailed view perhaps)
				return;
			}

			this.doLookupEvent = this.doLookup.bind(this);
			var testE = false;
			var e = this.form.formElements.get(this.options.observe);

			// If its a joined element
			if (!e) {
				var repeatCount = 0,
					k = Object.keys(this.form.formElements);
				k.each(function (i) {
					if (i.contains(self.options.observe)) {
						testE = self.form.formElements.get(i);
						if (!self.attached.contains(testE.options.element)) {
							// We havent previously observed this element, add it to this.attached
							// so that in the future we don't re-add it.
							self.attached.push(testE.options.element);
                            self.newAttach.push(testE.options.element);
							//e = testE;
						}
						var repeatNum = parseInt(testE.getRepeatNum(), 10);
						if (isNaN(repeatNum) || repeatNum === repeatCount) {
							e = testE;
						}
						repeatCount++;
					}
				});
			}
			else {
				this.attached.push(e.options.element);
                self.newAttach.push(e.options.element);
			}

			this.element = e;
			if (this.options.trigger === '') {
				if (!this.element) {
					fconsole('autofill - couldnt find element to observe');
				} else {
					var elEvnt = this.element.getBlurEvent();
					this.newAttach.each(function (el) {
						var e = self.form.formElements.get(el);
						self.form.dispatchEvent('', el, elEvnt, self.doLookupEvent);
                        if (self.options.fillOnLoad) {
                            self.form.dispatchEvent('', el, 'load', self.doLookupEvent);
                        }
					});
				}
			} else {
				this.form.dispatchEvent('', this.options.trigger, 'click', this.doLookupEvent);
                if (this.options.fillOnLoad) {
                    this.form.dispatchEvent('', this.options.trigger, 'load', this.doLookupEvent);
                }
			}


			this.setupDone = true;
			this.newAttach = [];
		},

		/**
		 * Perform ajax lookup when the observer element is blurred
		 * @param {object Element
 		 */
		lookUp: function (el) {
			if (!this.options.trigger) {
				this.element = el;
			}

			if (this.options.confirm === true) {
				if (!window.confirm(Joomla.JText._('PLG_FORM_AUTOFILL_DO_UPDATE'))) {
					return;
				}
			}
			Fabrik.loader.start('form_' + this.options.formid, Joomla.JText._('PLG_FORM_AUTOFILL_SEARCHING'));

			if (!this.element) {
				this.element = this.getElement(0);
			}
			var v = this.element.getValue(),
				formid = this.options.formid,
				observe = this.options.observe,
				self = this;

			jQuery.ajax({
				url     : 'index.php',
				method  : 'post',
				dataType: 'json',
				'data'  : {
					'option'               : 'com_fabrik',
					'format'               : 'raw',
					'task'                 : 'plugin.pluginAjax',
					'plugin'               : 'autofill',
					'method'               : 'ajax_getAutoFill',
					'g'                    : 'form',
					'v'                    : v,
					'formid'               : formid,
					'observe'              : observe,
					'cnn'                  : this.options.cnn,
					'table'                : this.options.table,
					'map'                  : this.options.map,
					'autofill_lookup_field': this.options.autofill_lookup_field
				}

			}).always(function () {
					Fabrik.loader.stop('form_' + self.options.formid);
				})
				.fail(function (jqXHR, textStatus, errorThrown) {
					window.alert(textStatus);
				})
				.done(function (json) {
					self.updateForm(json);
				});
		},

		// Update the form from the ajax request returned data
		updateForm: function (json) {
			this.json = json;
			Fabrik.fireEvent('fabrik.form.autofill.update.start', [this, json]);

			var repeatNum = this.element.getRepeatNum(),
				key, val, k2, origKey;

			if (jQuery.isEmptyObject(this.json)) {
				if (this.options.showNotFound) {
					var msg = this.options.notFoundMsg === '' ? Joomla.JText._('PLG_FORM_AUTOFILL_NORECORDS_FOUND') : this.options.notFoundMsg;
					window.alert(msg);
				}
				return;
			}

			for (key in this.json) {
				if (this.json.hasOwnProperty(key)) {
					val = this.json[key];
					k2 = key.substr(key.length - 4, 4);
					if (k2 === '_raw') {
						key = key.replace('_raw', '');
						origKey = key;
						if (!this.tryUpdate(key, val)) {
							key = this.updateRepeats(key, val, repeatNum, origKey);
						}
					}
				}
			}
			if (this.options.editOrig === true) {
				this.form.getForm().getElement('input[name=rowid]').value = this.json.__pk_val;
			}
			Fabrik.fireEvent('fabrik.form.autofill.update.end', [this, json]);
		},

		/**
		 * If the val is an object, then the target element is intended to be a repeat.
		 * So whip round the val's, seeing if we have a matching _X repeat of the target.
		 * For now, this is just a simple minded attempt to fill out existing repeats, we're
		 * not going to create new groups.  Implementing this for a specific client setup.  Maybe
		 * come back later and make this smarter.
		 * @param {string} key
		 * @param {object} val
		 * @param {number} repeatNum
		 * @param {string} origKey
		 * @returns {string}
		 */
		updateRepeats: function (key, val, repeatNum, origKey) {
			var k, k2;
			if (typeof val === 'object') {
				for (k in val) {
					if (val.hasOwnProperty(k)) {
						k2 = key + '_' + k;
						this.tryUpdate(k2, val[k]);
					}
				}
			} else {
				key += repeatNum ? '_' + repeatNum : '_0';
				if (!this.tryUpdate(key, val)) {
					// See if the user has used simply the full element name rather than the full element name with
					// the join string
					key = 'join___' + this.element.options.joinid + '___' + key;

					// Perhaps element is in main group and update element in repeat group :S
					this.tryUpdate(origKey, val, true);
				}
			}

			return key;
		},
		/**
		 * Try to update an element
		 *
		 * @param   {string}  key         Form.formElements key to update
		 * @param   {string}  value       Value to update to
		 * @param   {boolean}    looseMatch  Should we test if the key is contained within any of Form.formElements keys?
		 *
		 * @return  {boolean}  True if update occurred
		 */
		tryUpdate    : function (key, val, looseMatch) {
			var m, self = this, el;
			looseMatch = looseMatch ? true : false;
			if (!looseMatch) {
				el = this.form.elements[key];
				if (el !== undefined) {
					// $$$ hugh - nasty little hack to get auto-complete joins to properly update, if we don't set
					// el.activePopUp, the displayed label value won't get updated properly in the join's update() processing
					if (el.options.displayType === 'auto-complete') {
						el.activePopUp = true;
					}
					el.update(val);

					if (el.baseElementId !== this.element.baseElementId) {
						// Trigger change events to automatically fire anything watching this element
						el.element.fireEvent(el.getBlurEvent(), new Event.Mock(el.element, el.getBlurEvent()));
						if (el.getBlurEvent() !== el.getChangeEvent()) {
							el.element.fireEvent(el.getChangeEvent(), new Event.Mock(el.element, el.getChangeEvent()));
						}					}
					return true;
				}
			} else {
				m = Object.keys(this.form.formElements).filter(function (k, v) {
					return k.contains(key);
				});
				if (m.length > 0) {
					m.each(function (key) {
						el = self.form.elements[key];
						el.update(val);

						if (el.baseElementId !== self.element.baseElementId) {
							// Trigger change events to automatically fire anything watching this element
							el.element.fireEvent(el.getBlurEvent(), new Event.Mock(el.element, el.getBlurEvent()));
							if (el.getBlurEvent() !== el.getChangeEvent()) {
								el.element.fireEvent(el.getChangeEvent(), new Event.Mock(el.element, el.getChangeEvent()));
							}
						}
					});
					return true;
				}
			}
			return false;
		}
	});

	return Autofill;
});