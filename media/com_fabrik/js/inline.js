/**
 * Simple Inline Editor
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/**
 * simple inline editor, double click nodes which match the selector to toggle to a field
 * esc to revert
 * enter to save
 *
 */
var inline = new Class({

	Implements: Options,

	options: {

	},

	initialize: function (selector, options)
	{
		this.setOptions(options);
		document.addEvent('dblclick:relay(' + selector + ')', function (e, target) {
			var editor;
			target.hide();
			target.store('origValue', target.get('text'));
			if (!target.retrieve('inline')) {
				editor = new Element('input');
				editor.addEvent('keydown', function (e) {
					this.checkKey(e, target);
				}.bind(this));
				editor.inject(target, 'after').focus();
				editor.hide();
				target.store('inline', editor);
			} else {
				editor = target.retrieve('inline');
			}
			editor.set('value', target.get('text')).toggle().focus();
			editor.select();
		}.bind(this));
	},

	checkKey: function (e, target) {
		if (e.key === 'enter' || e.key === 'esc' || e.key === 'tab') {
			target.retrieve('inline').hide();
			target.show();
		}
		if (e.key === 'enter' || e.key === 'tab') {
			target.set('text', e.target.get('value'));
			Fabrik.fireEvent('fabrik.inline.save', [target, e]);
		}
	}
});
