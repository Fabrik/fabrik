/**
 * Admin Element List Editor
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var elementElement = new Class({

	Implements: [Options, Events],
	
	options: {
		'plugin': 'chart',
		'excludejoined': 0,
		'value': '',
		'highlightpk': 0
	},
	
	initialize: function (el, options) {
		this.el = el;
		this.setOptions(options);
		// if loading in a form plugin then the connect is not yet available in the dom
		if (!this.ready()) {
			this.cnnperiodical = this.getCnn.periodical(500, this);
		} else {
			this.setUp();
		}
	},

	ready: function () {
		if (typeOf(document.id(this.options.conn)) === 'null') {
			return false;
		}
		if (typeOf(FabrikAdmin.model.fields.fabriktable) === 'undefined') {
			return false;
		}
		if (Object.getLength(FabrikAdmin.model.fields.fabriktable) === 0) {
			return false;
		}
		if (Object.keys(FabrikAdmin.model.fields.fabriktable).indexOf(this.options.table) === -1) {
			return false;
		}
		return true;
	},

	getCnn: function () {
		if (!this.ready()) {
			return;
		}
		this.setUp();
		clearInterval(this.cnnperiodical);
	},

	setUp: function () {
		var s = this.el;
		this.el = document.id(this.el);
		if (typeOf(this.el) === 'null') {
			fconsole('element didnt find me, ', s);
		}
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
		FabrikAdmin.model.fields.fabriktable[this.options.table].registerElement(this);
	},

	/**
	 * If rendering with mode=gui then add button should insert selected element placeholder into 
	 * text area
	 */
	addPlaceHolder: function () {
		var list = this.el.getParent().getElement('select');
		this.insertTextAtCaret(this.el, '{' + list.get('value') + '}');
	},
	
	getOpts: function () {
		return $H({
			'calcs': this.options.include_calculations,
			'showintable': this.options.showintable,
			'published': this.options.published,
			'excludejoined': this.options.excludejoined,
			'highlightpk': this.options.highlightpk
		});
	},

	// only called from repeat viz admin interface i think
	cloned: function (newid, counter) {
		this.el = newid;
		var t = this.options.table.split('-');
		t.pop();
		this.options.table = t.join('-') + '-' + counter;
		this.setUp();
	},
	
	/**
	 * Start of text insertion code - taken from 
	 * http://stackoverflow.com/questions/3510351/how-do-i-add-text-to-a-textarea-at-the-cursor-location-using-javascript
	 */
	getSelectionBoundary: function (el, start) {
		var property = start ? "selectionStart" : "selectionEnd";
		var originalValue, textInputRange, precedingRange, pos, bookmark;

		if (typeof el[property] === "number") {
			return el[property];
		} else if (document.selection && document.selection.createRange) {
			el.focus();

			var range = document.selection.createRange();
			if (range) {
				// Collapse the selected range if the selection is not a caret
				if (document.selection.type === "Text") {
					range.collapse(!!start);
				}

				originalValue = el.value;
				textInputRange = el.createTextRange();
				precedingRange = el.createTextRange();
				pos = 0;

				bookmark = range.getBookmark();
				textInputRange.moveToBookmark(bookmark);

				if (originalValue.indexOf("\r\n") > -1) {
					// Trickier case where input value contains line breaks

					// Insert a character in the text input range and use that
					//as a marker
					textInputRange.text = " ";
					precedingRange.setEndPoint("EndToStart", textInputRange);
					pos = precedingRange.text.length - 1;

					// Executing an undo command deletes the character inserted
					// and prevents adding to the undo stack.
					document.execCommand("undo");
				} else {
					// Easier case where input value contains no line breaks
					precedingRange.setEndPoint("EndToStart", textInputRange);
					pos = precedingRange.text.length;
				}
				return pos;
			}
		}
		return 0;
	},

	offsetToRangeCharacterMove: function (el, offset) {
		return offset - (el.value.slice(0, offset).split("\r\n").length - 1);
	},

	setSelection: function (el, startOffset, endOffset) {
		var range = el.createTextRange();
		var startCharMove = this.offsetToRangeCharacterMove(el, startOffset);
		range.collapse(true);
		if (startOffset === endOffset) {
			range.move("character", startCharMove);
		} else {
			range.moveEnd("character", this.offsetToRangeCharacterMove(el, endOffset));
			range.moveStart("character", startCharMove);
		}
		range.select();
	},

	insertTextAtCaret: function (el, text) {
		var pos = this.getSelectionBoundary(el, false);
		var newPos = pos + text.length;
		var val = el.value;
		el.value = val.slice(0, pos) + text + val.slice(pos);
		this.setSelection(el, newPos, newPos);
	}
	
});