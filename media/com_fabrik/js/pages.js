var Pages = new Class({
	initialize: function (container, editable) {
		this.editable = editable;
		document.addEvent('mousedown', this.clearActive.bindWithEvent(this));
		Fabrik.addEvent('fabrik.page.add', this.makeActive.bindWithEvent(this));
		this.pages = $H({});
		this.activePage = null;
		this.container = document.id(container);
		Fabrik.addEvent('fabrik.tab.add', this.add.bindWithEvent(this));
		Fabrik.addEvent('fabrik.tab.click', this.show.bindWithEvent(this));
		Fabrik.addEvent('fabrik.tab.remove', this.remove.bindWithEvent(this));
		Fabrik.addEvent('fabrik.keynav', this.moveItem.bindWithEvent(this));
		Fabrik.addEvent('fabrik.inline.save', this.updateTabKey.bindWithEvent(this));
	},
	
	/* todo perhaps makecive and clearActive should be a mixin? */
	makeActive: function (c) {
		this.clearActive();
		c.addClass('active');
		this.active = c;
		var zindexes = document.getElements('.itemPlaceHolder').getStyle('z-index').sort();
		var max = zindexes.getLast().toInt() + 1;
		document.getElements('.itemPlaceHolder').each(function (i) {
			i.setStyle('zindex', i.getStyle('z-index').toInt() - 1); 
		});
		c.setStyle('z-index', max);
	},
	
	clearActive: function () {
		delete this.active;
		document.getElements('.itemPlaceHolder').removeClass('active');
	},
	
	moveItem: function (k, shift) {
		if (this.active && this.editable) {
			shift = shift ? 10 : 0;
			var p = this.active.getCoordinates(this.getActivePage().page);
			switch (k) {
			case 37: //left
				this.active.setStyle('left', p.left - 2 - shift);
				break;
			case 38: //up
				this.active.setStyle('top', p.top - 2 - shift);
				break;
			case 39: //right
				this.active.setStyle('left', p.left + 1 + shift);
				break;
			case 40: //down
				this.active.setStyle('top', p.top + 1 + shift);
				break;
			}
		}
	},
	
	add: function (tabs, t) {
		var page = new Page(t, this.editable);
		this.container.adopt(page.page);
		page.show();
		this.pages[t] = page;
		this.show();
	},
	
	remove: function (tabs, t) {
		t = t.retrieve('ref');
		//this.pages[t].remove();
		delete this.pages.t;
		this.pages.erase(t);
	},
	
	show: function (tab) {
		this.pages.each(function (page) {
			page.hide();
		});
		try {
			this.pages[tab].show();
			this.activePage = tab;
		} catch (err) {
			var k = this.pages.getKeys();
			if (k.length > 0) {
				tab = k[0];
				this.pages[tab].show();
				this.activePage = tab;
			}
		}
	},
	
	getHTMLPages: function () {
		var r = [];
		this.pages.each(function (p) {
			r.push(p.page);
		});
		return r;
	},
	
	getActivePage: function () {
		if (!this.activePage) {
			this.activePage = 0;
		}
		return this.pages[this.activePage];
	},
	
	fromJSON: function (layout) {
		$H(layout).each(function (items, page) {
			if (this.pages[page]) {
				$H(items).each(function (item, id) {
					this.pages[page].insert(item.id, item.label, item.type, item.dimensions);
				}.bind(this));
			}
		}.bind(this));
	},
	
	toJSON: function () {
		var r = {};
		this.pages.each(function (p, k) {
			var o = {};
			p.page.getElements('.itemPlaceHolder').each(function (e) {
				p.page.show(); //needed to get coords
				var type = e.id.split('_')[0];
				var label = e.getElement('.handlelabel').get('text');
				o[e.id] = {'dimensions': e.getCoordinates(p.page), 'label': label, 'type': type, 'id': e.id};
			});
			r[k.trim()] = o;
		});
		return r;
	},
	
	/**
	 * called when tab label is changed
	 */
	updateTabKey: function (editor) {
		var origKey = editor.retrieve('origValue').trim();
		var orig = this.pages[origKey];
		this.pages[editor.get('text').trim()] = orig;
		delete this.pages[origKey];
		this.pages.erase(origKey);
	}
});

Page = new Class({
	initialize: function (t, editable) {
		this.editable = editable;
		this.page = new Element('div', {'class': 'page', 'styles': {'display': 'none'}});
		if (this.editable) {
			Fabrik.addEvent('fabrik.item.resized', this.saveCoords.bindWithEvent(this));
			Fabrik.addEvent('fabrik.item.moved', this.saveCoords.bindWithEvent(this));
		}
	},
	
	show: function () {
		this.page.show();
	},
	
	hide: function () {
		this.page.hide();
	},
	
	remove: function () {
		this.page.destroy();
	},
	
	removeItem: function (e, id) {
		e.stop();
		if (confirm('Do you really want to delete')) {
			document.id(id).destroy();
			Fabrik.fireEvent('fabrik.page.block.delete', [id]);
		}
	},
	
	insert: function (id, label, type, dimensions) {
		Fabrik.fireEvent('fabrik.page.insert', [this, id, label, type, dimensions]);
	},
	
	saveCoords: function (e) {
	}

});