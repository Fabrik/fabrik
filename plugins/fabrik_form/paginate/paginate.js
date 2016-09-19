/**
 * List Paginate
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
define(['jquery', 'fab/fabrik'], function (jQuery, Fabrik) {
	'use strict';
	var Paginate = new Class({

		initialize: function (form, options) {
			this.form = form;
			this.options = {
				liveSite: ''
			};
			this.options = jQuery.extend(this.options, options);
			var f = this.form.getForm(),
				formId = this.form.id;
			this.pkfield = f.getElement('input[name=rowid]');
			this.view = this.form.options.editable === true ? 'form' : 'details';
			this.url = this.options.liveSite + 'index.php?option=com_fabrik&format=raw' +
				'&view=plugin&g=form&task=pluginAjax&plugin=paginate&method=xRecord&formid=' +
				formId + '&mode=' + this.options.view + '&rowid=';
			this.watchButtons();
		},

		doUpdate: function (json) {
			var o = JSON.decode(json),
				r = this.view === 'form' ? o.data : o.html,
				s;
			this.options.ids = o.ids;
			this.form.formElements.each(function (oEl, key) {
				if (key.substr(-3) !== '_ro') {
					s = r[key];
					try {
						if (typeOf(s) !== 'null') {
							if (oEl.updateUsingRaw()) {
								oEl.update(o.data[key]);
							} else {
								this.view === 'form' ? oEl.update(s) : oEl.update(Encoder.htmlDecode(s));
							}
						} else {
							oEl.update('');
						}
					} catch (err) {
						console.log(oEl, s, err);
					}
				}
			}.bind(this));
			if (this.view === 'form') {
				this.pkfield.value = r[this.options.pkey];
			}

			this.form.options.rowid = o.data[this.options.pkey];
			this.reScan();
			window.fireEvent('fabrik.form.refresh', [o.post.rowid]);
			Fabrik.loader.stop(this.form.getBlock());
		},

		reScan: function () {
			var form, dir;
			if (typeof(Slimbox) !== 'undefined') {
				Slimbox.scanPage();
			}
			if (typeof(Lightbox) !== 'undefined') {
				Lightbox.init();
			}
			if (typeof(Mediabox) !== 'undefined') {
				Mediabox.scanPage();
			}

			form = this.form.getForm();

			form.getElements('*[data-paginate]').each(function (el) {
				dir = el.get('data-paginate');
				switch (dir) {
					case 'first':
					/* falls through */
					case 'prev':
						if (this.options.ids.index === 0) {
							el.addClass('active');
						} else {
							el.removeClass('active');
						}
						break;
					case 'next':
					/* falls through */
					case 'last':
						if (this.options.ids.index === this.options.ids.lastKey) {
							el.addClass('active');
						} else {
							el.removeClass('active');
						}
						break;
				}
			}.bind(this));
		},

		doNav: function (target) {
			var dir = target.get('data-paginate'),
				ok = true, rowid;
			switch (dir) {
				case 'first':
					if (this.options.ids.index === 0) {
						ok = false;
					}
					rowid = this.options.ids.first;
					break;
				case 'last':
					if (this.options.ids.index === this.options.ids.lastKey) {
						ok = false;
					}
					rowid = this.options.ids.last;
					break;
				case 'prev':
					if (this.options.ids.index === 0) {
						ok = false;
					}
					rowid = this.options.ids.prev;
					break;
				case 'next':
					if (this.options.ids.index === this.options.ids.lastKey) {
						ok = false;
					}
					rowid = this.options.ids.next;
					break;
			}
			if (!ok) {
				return;
			}
			Fabrik.loader.start(this.form.getBlock());
			var pageNav = new Request({
				'url'      : this.url + rowid,
				evalScripts: true,
				onComplete : function (json) {
					this.doUpdate(json);
				}.bind(this)
			}).send();
		},

		watchButtons: function () {
			var form = this.form.getForm();
			form.addEvent('click:relay(*[data-paginate])', function (e, target) {
				e.preventDefault();
				this.doNav(target);
			}.bind(this));
		}
	});

	return Paginate;
});