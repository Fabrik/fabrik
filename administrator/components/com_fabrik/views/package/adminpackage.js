/**
 * Controller object for admin interface
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

AdminPackage = new Class({
	Extends: Canvas,

	initialize: function (opts) {
		this.simpleUI();
		return;
		/*opts.editable = true;
		this.parent(opts);
		this.setup();
		opts.editable = true;
		this.selectWindows = {}; //windows to select viz/list/forms
		// which active blockes have been selected
		// only used to store newly added blocks
		this.blocks = this.options.blocks;//{'form':[], 'list':[], 'visualization':[]};
		this.makeBlockMenu();
		Fabrik.addEvent('fabrik.tab.add', function (e) {
			this.setDrops(e);
		}.bind(this));
		this.setDrops();
		this.setDrags();
		Fabrik.addEvent('fabrik.package.item.selected', function (e) {
			this.addItem(e);
		}.bind(this));
		Fabrik.addEvent('fabrik.page.block.delete', function (e) {
			this.deleteItem(e);
		}.bind(this));*/
		//this.history = new History('undo', 'redo');
	},

	simpleUI: function () {
		var source = document.id('list-pick');
		var target = document.id('blockslist');
		var addBtn = document.id('add-list');
		var removeBtn = document.id('remove-list');

		this._swaplistIni(addBtn, removeBtn, source, target);

		source = document.id('form-pick');
		target = document.id('blocksform');
		addBtn = document.id('add-form');
		removeBtn = document.id('remove-form');
		this._swaplistIni(addBtn, removeBtn, source, target);

	},

	_swaplistIni: function (addBtn, removeBtn, source, target) {
		addBtn.addEvent('click', function (e) {
			e.stop();
			this._swaplist(source, target);
		}.bind(this));

		removeBtn.addEvent('click', function (e) {
			e.stop();
			this._swaplist(target, source);
		}.bind(this));
	},

	_swaplist: function (source, target) {
		var sel = source.getElements('option').filter(function (o) {
			return o.selected;
		});
		sel.each(function (o) {
			o.clone().inject(target);
			o.destroy();
		});
	},

	makeBlockMenu : function () {
		var c = new Element('ul', {
			'id' : 'typeList'
		}).adopt([
			new Element('li', {'class' : 'draggable typeList-list'}).adopt([
				new Element('img', {'src': 'components/com_fabrik/images/header/fabrik-list.png', title: 'Drag this list icon onto a page'}),
				new Element('div').set('text', 'List')
			]).store('type', 'list'),
			new Element('li', {'class': 'draggable typeList-form'}).adopt([
				new Element('img', {'src': 'components/com_fabrik/images/header/fabrik-form.png', title: 'Drag this form icon onto a page'}),
				new Element('div').set('text', 'Form')
			]).store('type', 'form'),
			new Element('li', {'class': 'draggable typeList-visualization'}).adopt([
				new Element('img', {'src': 'components/com_fabrik/images/header/fabrik-visualization.png', title: 'Drag this visualization icon onto a page'}),
				new Element('div').set('text', 'Visualization')
			]).store('type', 'visualization')
		]);

		c.inject(document.id('packagemenu'), 'before');
	},

	insertPage : function (page, id, label, type, dimensions) {
		var del, art;
		if (dimensions.width === 0) {
			dimensions.width = 50;
		}
		if (dimensions.height === 0) {
			dimensions.height = 50;
		}
		dimensions['z-index'] = 100;
		var c = new Element('div', {'id': id, 'class': 'fabrikWindow itemPlaceHolder itemPlaceHolder-' + type}).setStyles(dimensions);
		if (page.editable) {
			var delClick = function (e) {
				page.removeItem(e, page, id);
			}.bind(this);
			art = this.iconGen.create(icon.cross);
			var delopts = {'href': '#', 'class': 'close', 'events': {'click': delClick}};
			del = new Element('a', delopts);
			art.inject(del);
		} else {
			del = null;
		}
		label = new Element('span', {'class': 'handlelabel'}).set('text', label);
		var handle = new Element('div', {'class': 'handle'}).adopt([label, del]);

		var dragger = new Element('div', {'class': 'dragger'});

		var content = new Element('div', {'class': 'itemContent'});
		var listid = id.split('_')[1];
		content.adopt(new Element('iframe', {'width': '100%', 'height': '90%', 'src': 'index.php?option=com_fabrik&task=package.listform&iframe=1&tmpl=component&id=' +  listid}));

		c.adopt([handle, content, dragger]);
		if (page.editable) {
			c.makeResizable({'handle': dragger,
				onComplete: function () {
					Fabrik.fireEvent('fabrik.item.resized', c);
				}
			});
			c.makeDraggable({'handle': handle, 'container': document.id('packagepages')});
		}

		c.addEvent('mousedown', function (e) {
			Fabrik.fireEvent('fabrik.page.add', [c]);
		});
		page.page.adopt(c);
	},

	openListWindow : function (type) {
		this.activeType = type;
		var id = 'typeWindow-' + type;
		if (this.selectWindows[id]) {
			this.selectWindows[id].open();
		} else {
			var url = 'index.php?option=com_fabrik&task=package.dolist&format=raw&list=' + type + '&selected=' + this.blocks[type].join(',');
			opts = {
				'id': id,
				'type': 'modal',
				title: 'Select a ' + type,
				contentType: 'xhr',
				loadMethod: 'xhr',
				contentURL: url,
				width: 200,
				height: 250,
				x: 300,
				'minimizable': false,
				'collapsible': true,
				'onClose': function () {
					delete this.selectWindows[id];
				}.bind(this)
			};
			this.selectWindows[id] = Fabrik.getWindow(opts);
		}
	},

	addItem: function (e) {
		e.stop();
		var label = e.target.get('text');
		this.blocks[this.activeType].push(e.target.id);
		var id = this.activeType + '_' + e.target.id;
		this.insertLocation.height = '200px';
		this.insertLocation.width = '400px';
		this.pages.getActivePage().insert(id, label, this.activeType, this.insertLocation);
		this.selectWindows['typeWindow-' + this.activeType].close();
	},

	deleteItem: function (id) {
		id = id.split('_');
		this.blocks[id[0]].erase(id[1]);
	},

	prepareSave: function () {
		return;
		/*var o = {};
		o.layout = this.pages.toJSON();
		o.blocks = this.blocks;
		var t = [];
		this.tabs.tabs.each(function (tab) {
			t.push(tab.get('text').trim());
		});
		o.tabs = t;
		return o;*/
	}

});