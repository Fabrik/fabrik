var FbTextarea = new Class({
	Extends: FbElement,
	initialize: function (element, options) {
		
		this.plugin = 'fabriktextarea';
		this.parent(element, options);
		
		// $$$ rob need to slightly delay this as if lots of js loaded (eg maps)
		// before the editor then the editor may not yet be loaded 
		
		this.periodFn = function () {
			
			// Seems that tinyMCE isn't created if FbLike element published in form
			this.getTextContainer();
			if (typeof tinyMCE !== 'undefined') {
				if (this.container !== false) {
					this.watchTextContainer();
					clearInterval(this.periodFn);
				}
			} else {
				this.watchTextContainer();
				clearInterval(this.periodFn);
			}
		};
		this.periodFn.periodical(200, this);
	},
	
	unclonableProperties: function ()
	{
		var props = this.parent();
		props.push('container');
		return props;
	},
	
	/**
	 * Set names/ids/elements ect when the elements group is cloned
	 * 
	 * @param   int  id  element id
	 * @since   3.0.7
	 */

	cloneUpdateIds: function (id) {
		this.element = document.id(id);
		this.options.element = id;
		this.options.htmlId = id;
	},
	
	watchTextContainer: function ()
	{
		if (typeOf(this.element) === 'null') {
			this.element = document.id(this.options.element);
		}
		if (typeOf(this.element) === 'null') {
			this.element = document.id(this.options.htmlId);
			if (typeOf(this.element) === 'null') {
				// Can occur when element is part of hidden first group
				return;
			}
		}
		if (this.options.editable === true) {
			var c = this.getContainer();
			if (c === false) {
				fconsole('no fabrikElementContainer class found for textarea');
				return;
			}
			var element = c.getElement('.fabrik_characters_left');
			
			if (typeOf(element) !== 'null') {
				this.warningFX = new Fx.Morph(element, {duration: 1000, transition: Fx.Transitions.Quart.easeOut});
				this.origCol = element.getStyle('color');
				if (this.options.wysiwyg && typeof(tinymce) !== 'undefined') {
					tinymce.dom.Event.add(this.container, 'keyup', function (e) {
						this.informKeyPress(e);
					}.bind(this));
				} else {
					if (typeOf(this.container) !== 'null') {
						this.container.addEvent('keydown', function (e) {
							this.informKeyPress(e);
						}.bind(this));
					}
				}
			}
		}
	},
	
	/**
	 * Used to find element when form clones a group
	 * WYSIWYG text editor needs to return something specific as options.element has to use name 
	 * and not id.
	 */
	getCloneName: function () {
		var name = this.options.isGroupJoin ? this.options.htmlId : this.options.element;
		return name;
	},
	
	/**
	 * Run when element cloned in repeating group
	 * 
	 * @param   int  c  repeat group counter
	 */
	
	cloned: function (c) {
		if (this.options.wysiwyg) {
			var p = this.element.getParent('.fabrikElement');
			var txt = p.getElement('textarea').clone(true, true);
			var charLeft = p.getElement('.fabrik_characters_left');
			p.empty();
			p.adopt(txt);
			if (typeOf(charLeft) !== 'null') {
				p.adopt(charLeft.clone());
			}
			txt.removeClass('mce_editable');
			txt.setStyle('display', '');
			this.element = txt;
			var id = this.options.isGroupJoin ? this.options.htmlId : this.options.element;
			tinyMCE.execCommand('mceAddControl', false, id);
		}
		this.getTextContainer();
		this.watchTextContainer();
		this.parent(c);
	},
	
	/**
	 * run when the element is decloled from the form as part of a deleted repeat group
	 */
	decloned: function (groupid) {
		if (this.options.wysiwyg) {
			var id = this.options.isGroupJoin ? this.options.htmlId : this.options.element;
			tinyMCE.execCommand('mceFocus', false, id);                    
			tinyMCE.execCommand('mceRemoveControl', false, id);
		}
	},
	
	getTextContainer: function ()
	{
		if (this.options.wysiwyg && this.options.editable) {
			var name = this.options.isGroupJoin ? this.options.htmlId : this.options.element;
			document.id(name).addClass('fabrikinput');
			var instance = typeof(tinyMCE) !== 'undefined' ? tinyMCE.get(name) : false;
			if (instance) {
				this.container = instance.getDoc();
			} else {
				this.contaner = false;
			}
		} else {
			// Regrab the element for inline editing (otherwise 2nd col you edit doesnt pickup the textarea.
			this.element = document.id(this.options.element);
			this.container = this.element;
		}
		return this.container;
	},
	
	getContent: function ()
	{
		if (this.options.wysiwyg) {
			return tinyMCE.activeEditor.getContent().replace(/<\/?[^>]+(>|$)/g, "");
		} else {
			return this.container.value;
		}
	},
	
	setContent: function (c)
	{
		if (this.options.wysiwyg) {
			var r = tinyMCE.getInstanceById(this.element.id).setContent(c);
			this.moveCursorToEnd();
			return r;
		} else {
			this.getTextContainer();
			if (typeOf(this.container) !== 'null') {
				this.container.value = c;
			}
		}
		return null;
	},
	
	/**
	 * For tinymce move the cursor to the end
	 */
	moveCursorToEnd: function () {
		var inst = tinyMCE.getInstanceById(this.element.id);
		inst.selection.select(inst.getBody(), true); 
		inst.selection.collapse(false);
	},
	
	informKeyPress: function ()
	{
		var charsleftEl = this.getContainer().getElement('.fabrik_characters_left');
		var content = this.getContent();
		charsLeft = this.itemsLeft();
		if (this.limitReached()) {
			this.limitContent();
			this.warningFX.start({'opacity': 0, 'color': '#FF0000'}).chain(function () {
				this.start({'opacity': 1, 'color': '#FF0000'}).chain(function () {
					this.start({'opacity': 0, 'color': this.origCol}).chain(function () {
						this.start({'opacity': 1});
					});
				});
			});
		} else {
			charsleftEl.setStyle('color', this.origCol);
		}
		charsleftEl.getElement('span').set('html', charsLeft);
	},
	
	/**
	 * How many content items left (e.g 1 word, 100 characters)
	 * 
	 * @return int
	 */
	
	itemsLeft: function () {
		var i = 0;
		var content = this.getContent();
		if (this.options.maxType === 'word') {
			i = this.options.max - (content.split(' ').length) + 1;
		} else {
			i = this.options.max - (content.length + 1);
		}
		if (i < 0) {
			i = 0;
		}
		return i;
	},
	
	/**
	 * Limit the content based on maxType and max e.g. 100 words, 2000 characters
	 */
	
	limitContent: function () {
		var c;
		var content = this.getContent();
		if (this.options.maxType === 'word') {
			c = content.split(' ').splice(0, this.options.max);
			c = c.join(' ');
			c += (this.options.wysiwyg) ?  '&nbsp;' : ' ';
		} else {
			c = content.substring(0, this.options.max);
		}
		this.setContent(c);
	},
	
	/**
	 * Has the max content limit been reached?
	 * 
	 * @return bool
	 */
	
	limitReached: function () {
		var content = this.getContent();
		if (this.options.maxType === 'word') {
			var words = content.split(' ');
			return words.length > this.options.max;
		} else {
			var charsLeft = this.options.max - (content.length + 1);
			return charsLeft < 0 ? true : false;
		}
	},
	
	reset: function ()
	{
		this.update(this.options.defaultVal);
	},
	
	update: function (val) {
		this.getElement();
		this.getTextContainer();
		if (!this.options.editable) {
			this.element.set('html', val);
			return;
		}
		this.setContent(val);
	}
});