/**
 * Admin Listfields Dropdown Editor
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var ListFieldsElement = new Class({

	Implements: [Options, Events],

	addWatched: false,

	options: {
		conn: null,
		highlightpk: false,
		showAll: 1,
		showRaw: 0,
		mode: 'dropdown',
		defaultOpts: [],
		addBrackets: false
	},

	initialize: function (el, options) {
		this.strEl = el;
		var label, els;
		this.el = el;
		this.setOptions(options);

		if (this.options.defaultOpts.length > 0) {
			this.el = document.id(this.el);
			if (this.options.mode === 'gui') {
				this.select = this.el.getParent().getElement('select.elements');
				els = [this.select];
				if (typeOf(document.id(this.options.conn)) === 'null') {
					this.watchAdd();
				}
			} else {
				els = document.getElementsByName(this.el.name);
				this.el.empty();
				document.id(this.strEl).empty();
			}
			var opts = this.options.defaultOpts;

			Array.each(els, function (el) {
				document.id(el).empty();
			});

			opts.each(function (opt) {
				var o = {'value': opt.value};
				if (opt.value === this.options.value) {
					o.selected = 'selected';
				}
				Array.each(els, function (el) {
					label = opt.label ? opt.label : opt.text;
					new Element('option', o).set('text', label).inject(el);
				});
			}.bind(this));
		} else {
			if (typeOf(document.id(this.options.conn)) === 'null') {
				this.cnnperiodical = this.getCnn.periodical(500, this);
			} else {
				this.setUp();
			}
		}
	},

	/**
	 * Triggered when a fieldset is repeated (e.g. in googlemap viz where you can
	 * select more than one data set)
	 */
	cloned: function (newid, counter)
	{
		this.strEl = newid;
		this.el = document.id(newid);
		this._cloneProp('conn', counter);
		this._cloneProp('table', counter);
		this.setUp();
	},

	/**
	 * Helper method to update option HTML id's on clone()
	 */
	_cloneProp: function (prop, counter) {
		var bits = this.options[prop].split('-');
		bits = bits.splice(0, bits.length - 1);
		bits.push(counter);
		this.options[prop] = bits.join('-');
	},

	getCnn: function () {
		if (typeOf(document.id(this.options.conn)) === 'null') {
			return;
		}
		this.setUp();
		clearInterval(this.cnnperiodical);
	},

	setUp: function () {
		this.el = document.id(this.el);
		if (this.options.mode === 'gui') {
			this.select = this.el.getParent().getElement('select.elements');
		}

		document.id(this.options.conn).addEvent('change', function () {
			this.updateMe();
		}.bind(this));
		document.id(this.options.table).addEvent('change', function () {
			this.updateMe();
		}.bind(this));

		// See if there is a connection selected
		var v = document.id(this.options.conn).get('value');
		if (v !== '' && v !== -1) {
			this.periodical = this.updateMe.periodical(500, this);
		}
		this.watchAdd();
	},

	watchAdd: function () {
		if (this.addWatched === true) {
			return;
		}
		console.log('watch add', this);
		this.addWatched = true;
		var add = this.el.getParent().getElement('button');

		if (typeOf(add) !== 'null') {
			add.addEvent('mousedown', function (e) {
				e.stop();
				this.addPlaceHolder();
			}.bind(this));
			add.addEvent('click', function (e) {
				e.stop();
			});
		}
	},

	updateMe: function (e) {
		if (typeOf(e) === 'event') {
			e.stop();
		}
		if (document.id(this.el.id + '_loader')) {
			document.id(this.el.id + '_loader').show();
		}
		var conn = document.id(this.options.conn);
		if (!conn) {
			clearInterval(this.periodical);
			return;
		}
		var cid = document.id(this.options.conn).get('value');
		var tid = document.id(this.options.table).get('value');
		if (!tid) {
			return;
		}
		clearInterval(this.periodical);
		var url = 'index.php?option=com_fabrik&format=raw&task=plugin.pluginAjax&g=element&plugin=field&method=ajax_fields&showall=' + this.options.showAll + '&cid=' + cid + '&t=' + tid;
		var myAjax = new Request({
			url: url,
			method: 'get',
			data: {
				'highlightpk': this.options.highlightpk,
				'showRaw': this.options.showRaw,
				'k': 2
			},
			onComplete: function (r) {
				var els;

				// Googlemap inside repeat group & modal repeat
				if (typeOf(document.id(this.strEl)) !== null) {
					this.el = document.id(this.strEl);
				}
				if (this.options.mode === 'gui') {
					els = [this.select];
				} else {
					els = document.getElementsByName(this.el.name);
					this.el.empty();
					document.id(this.strEl).empty();
				}
				var opts = eval(r);

				Array.each(els, function (el) {
					document.id(el).empty();
				});

				opts.each(function (opt) {
					var o = {'value': opt.value};
					if (opt.value === this.options.value) {
						o.selected = 'selected';
					}
					Array.each(els, function (el) {
						new Element('option', o).set('text', opt.label).inject(el);
					});
				}.bind(this));
				if (document.id(this.el.id + '_loader')) {
					document.id(this.el.id + '_loader').hide();
				}
			}.bind(this)
		});
		Fabrik.requestQueue.add(myAjax);
	},

	/**
	 * If rendering with mode=gui then add button should insert selected element placeholder into
	 * text area
	 */
	addPlaceHolder: function () {
		var list = this.el.getParent().getElement('select');
		var v = list.get('value');
		if (this.options.addBrackets) {
			v = v.replace(/\./, '___');
			v = '{' + v + '}';
		}
		this.insertTextAtCaret(this.el, v);
	},

	/**
	 * Start of text insertion code - taken from
	 * http://stackoverflow.com/questions/3510351/how-do-i-add-text-to-a-textarea-at-the-cursor-location-using-javascript
	 */
	getInputSelection: function (el) {
		var start = 0, end = 0, normalizedValue, range,
		textInputRange, len, endRange;

		if (typeof el.selectionStart === 'number' && typeof el.selectionEnd === 'number') {
			start = el.selectionStart;
			end = el.selectionEnd;
		} else {
			range = document.selection.createRange();

			if (range && range.parentElement() === el) {
				len = el.value.length;
				normalizedValue = el.value.replace(/\r\n/g, '\n');

				// Create a working TextRange that lives only in the input
				textInputRange = el.createTextRange();
				textInputRange.moveToBookmark(range.getBookmark());

				// Check if the start and end of the selection are at the very end
				// of the input, since moveStart/moveEnd doesn't return what we want
				// in those cases
				endRange = el.createTextRange();
				endRange.collapse(false);

				if (textInputRange.compareEndPoints('StartToEnd', endRange) > -1) {
					start = end = len;
				} else {
					start = -textInputRange.moveStart('character', -len);
					start += normalizedValue.slice(0, start).split('\n').length - 1;

					if (textInputRange.compareEndPoints('EndToEnd', endRange) > -1) {
						end = len;
					} else {
						end = -textInputRange.moveEnd('character', -len);
						end += normalizedValue.slice(0, end).split('\n').length - 1;
					}
				}
			}
		}

		return {
			start: start,
			end: end
		};
	},

	offsetToRangeCharacterMove: function (el, offset) {
		return offset - (el.value.slice(0, offset).split('\r\n').length - 1);
	},

	setSelection: function (el, start, end) {
		if (typeof el.selectionStart === 'number' && typeof el.selectionEnd === 'number') {
			el.selectionStart = start;
			el.selectionEnd = end;
		} else if (typeof el.createTextRange !== 'undefined') {
			var range = el.createTextRange();
			var startCharMove = this.offsetToRangeCharacterMove(el, start);
			range.collapse(true);
			if (start === end) {
				range.move('character', startCharMove);
			} else {
				range.moveEnd('character', this.offsetToRangeCharacterMove(el, end));
				range.moveStart('character', startCharMove);
			}
			range.select();
		}
	},

	insertTextAtCaret: function (el, text) {
		var pos = this.getInputSelection(el).end;
		var newPos = pos + text.length;
		var val = el.value;
		el.value = val.slice(0, pos) + text + val.slice(pos);
		this.setSelection(el, newPos, newPos);
	}
});



/*
function insertTextAtCaret(el, text) {

}

var textarea = document.getElementById("your_textarea");
insertTextAtCaret(textarea, "[INSERTED]");*/