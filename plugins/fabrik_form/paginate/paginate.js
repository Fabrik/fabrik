/**
 * List Paginate
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

FabRecordSet = new Class({

	initialize: function (form, options) {
		this.form = form;
		this.options = {};
		Object.extend(this.options, options);
		var f = this.form.getForm();
		var tableId = f.getElement('input[name=listid]').get('value');
		this.pkfield = f.getElement('input[name=rowid]');
		var formId = this.form.id;
		this.view = this.form.options.editable === true ? 'form':'details';
		this.url = this.options.liveSite + 'index.php?option=com_fabrik&format=raw&controller=plugin&g=form&task=pluginAjax&plugin=paginate&method=xRecord&formid=' + formId + '&mode=' + this.options.view + '&rowid=';
		this.watchButtons();
	},

	doUpdate: function (json) {
		var o = Json.evaluate(json.stripScripts());
		this.options.ids = o.ids;
		var r = this.view === 'form' ? o.data : o.html;
		this.form.formElements.each(function (oEl, key) {
			var s = r[key];
			try {
				if (typOf(s) !== 'null') {
					this.view === 'form' ? oEl.update(s) : oEl.set('html', Encoder.htmlDecode(s));
				} else {
					this.view === 'form' ? oEl.update('') : oEl.set('html', '');
				}
			} catch (err) {
				console.log(oEl, s, err);
			}
		}.bind(this));
		this.pkfield.value = r[this.options.pkey];
		if (typeof(Slimbox) !== 'undefined') {
			Slimbox.scanPage();
		}
		if (typeof(Lightbox) !== 'undefined') {
			Lightbox.init();
		}
		if (typeof(Mediabox) !== 'undefined') {
			Mediabox.scanPage();
		}
		window.fireEvent('fabrik.form.refresh', [o.post.rowid]);
		oPackage.stopLoading(this.form.getBlock());
	},

	doNav: function (e, dir) {
		e.stop();
		var ok = true;
		switch (dir) {
		case 0:
			if (this.options.ids.index === 0 || this.options.ids.index === 1) {
				ok = false;
			}
			rowid = this.options.ids.first;
			break;
		case 2:
			if (this.options.ids.index === this.options.ids.lastKey) {
				ok = false;
			}
			rowid = this.options.ids.last;
			break;
		case -1:
			if (this.options.ids.index === 0 || this.options.ids.index === 1) {
				ok = false;
			}
			rowid = this.options.ids.prev;
			break;
		case 1:
			if (this.options.ids.index === this.options.ids.lastKey) {
				ok = false;
			}
			rowid = this.options.ids.next;
			break;
		}
		if (!ok) {
			return;
		}
		oPackage.startLoading(this.form.getBlock());
		var pageNav = new Request({
			'url': this.url + rowid,
			evalScripts: true,
			onComplete: function (json) {
				this.doUpdate(json);
			}.bind(this)
		}).send();
	},

	watchButtons: function () {
		var n, form;
		form = this.form.getForm();
		n = form.getElement('ul.pagination');
		n.getElement('.paginateNext').addEvent('click', function (e) {
			this.doNav(e, 1);
		}.bind(this));

		n.getElement('.paginatePrevious').addEvent('click', function (e) {
			this.doNav(e, -1);
		}.bind(this));

		n.getElement('.paginateLast').addEvent('click', function (e) {
			this.doNav(e, 2);
		}.bind(this));

		n.getElement('.paginateFirst').addEvent('click', function (e) {
			this.doNav(e, 0);
		}.bind(this));
	}
});