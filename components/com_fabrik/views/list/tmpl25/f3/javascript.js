/**
 * List helper
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FabrikGrid = new Class({

	resizeCol : null,
	resizing: false,
	inActiveArea:false,
	chxBoxWidth:17,
	maxWidth:150,
	iconSettings:{
		rotate:0,
		fill:{
			color:['#378F36', '#46B040']
		}},

	Implements: Options,

	options: {
		'listref' : ''
	},

	initialize : function(options) {
		this.setOptions(options);
		//this.container = document.getElement('.f3main');
		this.container = document.id(this.options.listref);
		Fabrik.addEvent('fabrik.list.updaterows', function(){
			this.resizeCells();
			this.scaleScrollDivs();
			this.watchResizeCols();
		}.bind(this));

		this.resizeCells();
		this.scaleScrollDivs();
		this.watchResizeCols();

		this.injectIcon('plus', '.addbutton');
		this.injectIcon('export', '.csvExportButton', {rotate:-45, fill:{color:['#1A4D66', '#2E91C2']}});
		this.injectIcon('import', '.csvImportButton', {rotate:90, fill:{color:['#DC80D3', '#B040A5']}});
		this.injectIcon('search', '.fabrik_filter_submit');
		document.getElement('.scroll-x').setStyle('height',document.getElement('.scroll-y').getHeight()+'px');
		this.addVertScrollBar();
	},

	addVertScrollBar:function(){
		var h = this.container.getElement('.scroll-x').getStyle('height').toInt() - 17;
		var headingsOffset = this.container.getElements('ul.fabrik___heading').getHeight().sum() + 18;
		h = h - headingsOffset;
		this.scrollX = this.container.getElement('.scroll-x');
		this.scrollY = this.container.getElement('.scroll-y');
		this.fxScroll = new Fx.Scroll(this.scrollY);
		this.yScroller = new Element('div', {
			'class':'yscroller',
			'events':{
				'scroll':function(e){
						var y = this.container.getElement('.yscroller').getScroll().y;
						var x = this.scrollX.getScroll().x;
						this.fxScroll.set(x, y);
				}.bind(this)
			},
			'styles':{
			'height':h+'px',
			'position':'absolute',
			'top':headingsOffset+'px',
			'right':'-17px',
			'overflow':'scroll',
			'width':'17px'
		}}).adopt(
			new Element('div', {'styles':{'height':this.container.getElement('.fabrikList').getStyle('height')}})
		).inject(document.getElement('.scroll-x'));

	//watch the mouse scroll in the scrollY div and move the yscroller's scroll pos
	this.fxScrollMirror = new Fx.Scroll(this.yScroller);
	this.scrollY.addEvent('mousewheel', function(e){
		var y = this.scrollY.scrollTop  + (e.wheel * -120);
		new Fx.Scroll(this.scrollY, {'onComplete':function(){
			this.fxScrollMirror.set(this.scrollX.getScroll().x, y);
		}.bind(this)
		}).start(0, y);
		e.stop();
	}.bind(this))
	},

	injectIcon:function(name, to, options)
	{
		if(typeOf(options) !== 'object'){
			options = {};
		}
		var opts = Object.clone(this.iconSettings);
		Object.append(opts, options);
		var i = Fabrik.iconGen.create(icon[name], opts);
		var button = this.container.getElement(to);
		if(typeOf(button) == 'element'){
			i.inject(button);
		}
	},

	watchResizeCols : function() {
		document.addEvent('mousemove', function(e) {
			if (typeOf(this.resizeCol) !== 'null') {
				if (e.page.x - 5 <= this.resizeCol.getPosition().x) {
					if(this.inActiveArea == true){
						return;
					}
					this.inActiveArea = true;
					this.resizeCol.setStyle('cursor', 'col-resize');
					this.toResize = this.resizeCol.getPrevious();
					this.resizeCol.addEvent('mousedown', function (e) {
						this.startResize(e);
					}.bind(this));
					document.addEvent('mouseup', function (e) {
						this.endResize(e);
					}.bind(this));
				} else {
					this.inActiveArea = false;
				}
			}
		}.bind(this));
		var h = Array.from(this.container.getElements('.fabrik_ordercell').splice(0, 1));
		h.each(function (r) {
			r.addEvent('mouseover', function(e) {
				this.resizeCol = e.target;
			}.bind(this));

			r.addEvent('mouseleave', function(e) {
				this.resizeCol = null;
			}.bind(this));
		}.bind(this));

	},

	doResize:function(e){
		if(!this.resizing || typeOf(this.toResize) == 'null'){
			return;
		}
		var diff = e.page.x - this.startX;
		if (diff == 0){
			return;
		}
		this.startX = e.page.x;
		var c = this.toResize.get('class').split(' ')[1];
		var els = this.container.getElements('.'+c);
		var startWidth = this.toResize.retrieve('start-width', this.toResize.getStyle('min-width').toInt());
		var min = this.toResize.getStyle('min-width').toInt();

		var w = min + diff;
		//if (w > this.maxWidth) { w = this.maxWidth; }
		var origWidths = els.getStyle('width');
		els.setStyles({'min-width':w+'px', 'width':w+'px'});

		if (min + diff < startWidth) {
			//ensure we don't resize down too much
			var widths = els.getWidth();
			if (widths.max() !== widths.min()) {
				var index = widths.indexOf(widths.max());
				w = origWidths[index].toInt();
				els.setStyles({'min-width':w+'px', 'width':w+'px'});
			}
		}
	},

	startResize : function(e) {
		if(this.resizing){
			return;
		}
		this.container.setStyle('-moz-user-select', 'none');
		this.container.addEvent('onselectstart', function (e) {
			this.stopIETextSelect(e);
		}.bind(this));
		document.addEvent('mousemove', function (e) {
			this.doResize(e);
		}.bind(this);
		this.startX = e.page.x;
		this.resizing = true;
	},

	endResize : function() {
		if(this.resizeCol){
			this.resizeCol.setStyle('cursor', '');
		}
		this.resizing = false;
		this.resizeCol = null;

		this.inActiveArea = false;
		this.container.setStyle('-moz-user-select', 'auto');
		this.container.removeEvent('onselectstart', function (e) {
			this.stopIETextSelect(e);
		}.bind(this));
		//this.container.getElement('.scroll-y').setStyle('width', this.container.getElement('li.heading').getWidth() + 'px');
	},

	stopIETextSelect:function(){
		return false;
	},

	resizeCells : function() {
		var hs = this.container.getElement('.fabrik___heading').getElements('span').get('class');
		var fs = [];
		hs.each(function(h) {
			fs.push(h.split(' ')[1]);
		});
		for (i = 0; i < fs.length; i++) {
			if (fs[i] !== 'fabrik_element') {
				var cells = $$('.' + fs[i]);

				// don't ask why but when heading has a long title and no content in cells
				// have to do this three times before all cells have the same width!?!?!
				var max = cells.getWidth().max();
				if (max > this.maxWidth) { max = this.maxWidth; }
				cells.setStyles({'min-width': max + 'px', 'width':max+'px'});

				var max = cells.getWidth().max();
				if (max > this.maxWidth) { max = this.maxWidth; }
				cells.setStyles({'min-width': max + 'px', 'width':max+'px'});

				var max = cells.getWidth().max();
				if (max > this.maxWidth) { max = this.maxWidth; }
				cells.setStyles({'min-width': max + 'px', 'width':max+'px'});
			}
		}
	},

	scaleScrollDivs : function() {
		var y = this.container.getElement('.scroll-y');
		var x = this.container.getElement('.scroll-x');
		var w = x.getScrollSize().x + this.chxBoxWidth;
		y.setStyle('width', w + 'px');
		this.container.getElement('.fabrik___headings').setStyle('width', w+'px');
	}

});

var col = null;

