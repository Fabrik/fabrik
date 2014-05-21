/**
 * Fabrik Window
 *
 * @copyright: Copyright (C) 2005-2014, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/**
 * Window factory
 * 
 * @param   object  opts  Options
 * 
 * @return  Fabrik.Window
 */
Fabrik.getWindow = function (opts) {
	if (Fabrik.Windows[opts.id]) {
		if (opts.visible !== false) {
			Fabrik.Windows[opts.id].open();
		}
		Fabrik.Windows[opts.id].setOptions(opts);
		// Fabrik.Windows[opts.id].loadContent();
	} else {
		var type = opts.type ? opts.type : '';
		switch (type) {
		case 'redirect':
			Fabrik.Windows[opts.id] = new Fabrik.RedirectWindow(opts);
			break;
		case 'modal':
			Fabrik.Windows[opts.id] = new Fabrik.Modal(opts);
			break;
		case '':
			/* falls through */
		default:
			Fabrik.Windows[opts.id] = new Fabrik.Window(opts);
			break;
		}
	}
	return Fabrik.Windows[opts.id];
};


Fabrik.Window = new Class({

	Implements: [Events, Options],

	options: {
		id: 'FabrikWindow',
		title: '&nbsp;',
		container: false,
		loadMethod: 'html',
		contentURL: '',
		createShowOverLay: false,
		width: 300,
		height: 300,
		loadHeight: 100,
		expandable: true,
		offset_x: null,
		offset_y: null,
		visible: true,
		onClose: function () {},
		onOpen: function () {},
		onContentLoaded: function () {
			this.fitToContent(false);
		},
		destroy: true
	},

	modal: false,

	classSuffix: '',

	expanded: false,

	initialize: function (opts)
	{
		this.setOptions(opts);
		this.makeWindow();
	},

	/**
	 * Tabs can resize content area
	 */
	watchTabs: function () {
		this.window.getElements('.nav-tabs a').addEvent('mouseup', function () {
			this.fitToWidth();
			this.drawWindow();
		}.bind(this));
	},

	deleteButton: function () {
		var delClick = function (e) {
			this.close(e);
		}.bind(this);
		var del;
		if (Fabrik.bootstrapped) {
			del = new Element('a', {'href': '#', 'class': 'closeFabWin', 'events': {'click': delClick}});
			del.adopt(new Element('i.icon-cancel.icon-remove-sign'));
		} else {
			del = new Element('a', {'href': '#', 'class': 'close', 'events': {'click': delClick}});
			var art = Fabrik.iconGen.create(icon.cross);
			art.inject(del);
		}
		return del;
	},

	center: function () {
		var pxWidth = this.windowWidthInPx(),
		w = this.window.getStyle('width'),
		h = this.window.getStyle('height');
		w = (w === null || w === 'auto') ? pxWidth : this.window.getStyle('width');
		w = w.toInt();
		h = (h === null || h === 'auto') ? this.options.height + 10 : this.window.getStyle('height');
		h = h.toInt();
		var d = {'width': w + 'px', 'height': h + 'px'};
		this.window.setStyles(d);
		
		if (!(Fabrik.bootstrapped && this.modal)) {
			var yy = window.getSize().y / 2 + window.getScroll().y - (h / 2);
			d.top = typeOf(this.options.offset_y) !== 'null' ? window.getScroll().y + this.options.offset_y : yy;
			
			var xx = window.getSize().x / 2  + window.getScroll().x - w / 2;
			d.left = typeOf(this.options.offset_x) !== 'null' ? window.getScroll().x + this.options.offset_x : xx;
			
		} else {
			// Fileupload crop uses this
			var offset = (window.getSize().y - h) / 2;
			var xoffset = (window.getSize().x - w) / 2;
			d.top = offset < 0 ? window.getScroll().y : window.getScroll().y + offset;
			d.left = xoffset < 0 ? window.getScroll().x : window.getScroll().x + xoffset;
		}
		// Prototype J template css puts margin left on .modals
		d['margin-left'] = 0;
		this.window.setStyles(d);
	},

	/**
	 * Work out the window width either from px or % variable
	 *
	 * @deprecated use this.windowDimenionInPx('width') instead
	 *
	 * @return  int  Px width of window
	 */

	windowWidthInPx: function () {
		return this.windowDimenionInPx('width');
	},

	/**
	 * Work out the window width or height either from px or % variable
	 *
	 * @param   string  dir  Width or height.
	 *
	 * @return  int  Px width of window
	 */
	windowDimenionInPx: function (dir) {
		var coord = dir === 'height' ? 'y' : 'x';
		var dim = this.options[dir] + '';
		if (dim.indexOf('%') !== -1) {
			return Math.floor(window.getSize()[coord] * (dim.toFloat() / 100));
		}
		return dim.toInt();
	},

	/**
	 * Build the window HTML
	 */
	makeWindow: function ()
	{
		var draggerC, dragger, expandButton, expandIcon, resizeIcon, label, cw, ch, handleParts = [];
		this.window = new Element('div', {'id': this.options.id, 'class': 'fabrikWindow ' + this.classSuffix + ' modal'});
		
		// Set window dimensions before center - needed for fileupload crop
		this.window.setStyle('width', this.options.width);
		this.window.setStyle('height', this.options.height);
		this.center();
		this.contentWrapperEl = this.window;
		var del = this.deleteButton();

		var hclass = 'handlelabel';
		if (!this.modal) {
			hclass += ' draggable';
			var bClss = Fabrik.bootstrapped ? 'bottomBar BootStrapped' : 'bottomBar';
			draggerC = new Element('div', {'class': 'bottomBar modal-footer'});
			dragger = new Element('div', {'class': 'dragger'});
			if (Fabrik.bootstrapped) {
				resizeIcon = new Element('i.icon-expand');
			} else {
				resizeIcon = Fabrik.iconGen.create(icon.resize, {
					scale: 0.8,
					rotate: 0,
					shadow: {
						color: '#fff',
						translate: {x: 0, y: 1}
					},
					fill: {
						color: ['#999', '#666']
					}
				});
			}
			resizeIcon.inject(dragger);
			draggerC.adopt(dragger);
		}

		if (Fabrik.bootstrapped) {
			expandIcon = new Element('i.icon-out-2.icon-fullscreen');
			label = new Element('h3', {'class': hclass}).set('text', this.options.title);

		} else {
			expandIcon = Fabrik.iconGen.create(icon.expand, {scale: 0.4, fill: {
				color: ['#666666', '#999999']
			}});
			label = new Element('span', {'class': hclass}).set('text', this.options.title);
		}

		handleParts.push(label);
		if (this.options.expandable && this.modal === false) {
			expandButton = new Element('a', {'href': '#', 'class': 'expand', 'events': {
				'click': function (e) {
					this.expand(e);
				}.bind(this)
			}}).adopt(expandIcon);
			handleParts.push(expandButton);
		}

		handleParts.push(del);
		this.handle = this.getHandle().adopt(handleParts);

		var bottomBarHeight = 15;
		var topBarHeight = 15;
		var contentHeight = this.options.height - bottomBarHeight - topBarHeight;
		if (contentHeight < this.options.loadHeight) {
			contentHeight = this.options.loadHeight;
		}
		this.contentWrapperEl = new Element('div.contentWrapper', {
			'styles': {'height': contentHeight + 'px'}
		});
		var itemContent = new Element('div', {'class': 'itemContent'});
		this.contentEl = new Element('div', {'class': 'itemContentPadder'});
		itemContent.adopt(this.contentEl);
		this.contentWrapperEl.adopt(itemContent);
		cw = this.windowWidthInPx();
		ch = this.windowDimenionInPx('height');
		this.contentWrapperEl.setStyles({'height': ch, 'width': cw + 'px'});
		if (this.modal) {
			this.window.adopt([this.handle, this.contentWrapperEl]);
		} else {
			this.window.adopt([this.handle, this.contentWrapperEl, draggerC]);
			this.window.makeResizable({'handle': dragger,
				onDrag: function () {
					Fabrik.fireEvent('fabrik.window.resized', this.window);
					this.drawWindow();
				}.bind(this)
			});
			var dragOpts = {'handle': this.handle};
			dragOpts.onComplete = function () {
					Fabrik.fireEvent('fabrik.window.moved', this.window);
					this.drawWindow();
				}.bind(this);
			dragOpts.container = this.options.container ? document.id(this.options.container) : null;
			this.window.makeDraggable(dragOpts);
		}
		if (!this.options.visible) {
			this.window.fade('hide');
		}
		document.id(document.body).adopt(this.window);
		this.loadContent();
		this.center();
		//bad idea - means opening windows are hidden if other code calls another window to hide
		/*Fabrik.addEvent('fabrik.overlay.hide', function () {
			this.window.hide();
		}.bind(this));*/
	},

	/**
	 * toggle the window full screen
	 */
	expand: function (e) {
		e.stop();
		if (!this.expanded) {
			this.expanded = true;
			var w = window.getSize();
			this.unexpanded = this.window.getCoordinates();
			var scroll = window.getScroll();
			this.window.setPosition({'x': scroll.x, 'y': scroll.y}).setStyles({'width': w.x, 'height': w.y});
		} else {
			this.window.setPosition({'x': this.unexpanded.left, 'y': this.unexpanded.top}).setStyles({'width': this.unexpanded.width, 'height': this.unexpanded.height});
			this.expanded = false;
		}
		this.drawWindow();
	},

	getHandle: function () {
		var c = this.handleClass();
		return new Element('div', {'class': 'draggable ' + c});
	},

	handleClass: function () {
		return Fabrik.bootstrapped ? 'modal-header' : 'handle';
	},

	loadContent: function () {
		var u;
		window.fireEvent('tips.hideall');
		switch (this.options.loadMethod) {

		case 'html':
			if (typeOf(this.options.content) === 'null') {
				fconsole('no content option set for window.html');
				this.close();
				return;
			}
			if (typeOf(this.options.content) === 'element') {
				this.options.content.inject(this.contentEl.empty());
			} else {
				this.contentEl.set('html', this.options.content);
			}
			this.fireEvent('onContentLoaded', [this]);
			this.watchTabs();
			break;
		case 'xhr':
			u = this.window.getElement('.itemContent');
			u = this.contentEl;
			Fabrik.loader.start(u);
			new Request.HTML({
				'url': this.options.contentURL,
				'data': {'fabrik_window_id': this.options.id},
				'update': u,
				onSuccess: function () {
					Fabrik.loader.stop(u);
					this.fireEvent('onContentLoaded', [this]);
					this.watchTabs();
					
					// Needed for IE11
					this.center(); 
					// Ini any Fabrik JS code that was loaded with the ajax request
					// window.fireEvent('fabrik.loaded');
				}.bind(this)
			}).post();
			break;
		case 'iframe':
			var h = this.options.height - 40;
			var w = this.contentEl.getScrollSize().x + 40 < window.getWidth() ? this.contentEl.getScrollSize().x + 40 : window.getWidth();
			u = this.window.getElement('.itemContent');
			Fabrik.loader.start(u);

			if (this.iframeEl) {
				this.iframeEl.dispose();
			}
			this.iframeEl = new Element('iframe', {
				'id': this.options.id + '_iframe',
				'name': this.options.id + '_iframe',
				'class': 'fabrikWindowIframe',
				'src': this.options.contentURL,
				'marginwidth': 0,
				'marginheight': 0,
				'frameBorder': 0,
				'scrolling': 'auto',
				'styles': {
					'height': h + 'px',
					'width': w
				}
			}).inject(this.window.getElement('.itemContent'));
			this.iframeEl.hide();
			this.iframeEl.addEvent('load', function (e) {
				Fabrik.loader.stop(this.window.getElement('.itemContent'));
				this.iframeEl.show();
				this.fireEvent('onContentLoaded', [this]);
				this.watchTabs();
			}.bind(this));
			break;
		}
	},

	drawWindow: function () {
		var titleHeight = this.window.getElement('.' + this.handleClass());
		titleHeight = titleHeight ? titleHeight.getSize().y : 25;
		var footer = this.window.getElement('.bottomBar').getSize().y;
		this.contentWrapperEl.setStyle('height', this.window.getDimensions().height - (titleHeight + footer));
		this.contentWrapperEl.setStyle('width', this.window.getDimensions().width - 2);

		// Resize iframe when window is resized
		if (this.options.loadMethod === 'iframe') {
			this.iframeEl.setStyle('height', this.contentWrapperEl.offsetHeight - 40);
			this.iframeEl.setStyle('width', this.contentWrapperEl.offsetWidth - 10);
		}
	},

	fitToContent: function (scroll, center) {
		scroll = scroll === undefined ? true : scroll;
		center = center === undefined ? true : center;

		if (this.options.loadMethod !== 'iframe') {
			// As iframe content may not be on the same domain we CAN'T guarantee access to its body element to work out its dimensions
			this.fitToHeight();
			this.fitToWidth();
		}
		this.drawWindow();
		if (center) {
			this.center();
		}
		if (!this.options.offset_y && scroll) {
			var myfx = new Fx.Scroll(window).toElement(this.window);
		}
	},

	/**
	 * Fit the window height to the min of either its content height or the window height
	 */
	fitToHeight: function () {
		// Add the top and bottom bars to the content size
		var titleHeight = this.window.getElement('.' + this.handleClass());
		titleHeight = titleHeight ? titleHeight.getSize().y : 25;
		var footer = this.window.getElement('.bottomBar').getSize().y;
		var contentEl = this.window.getElement('.itemContent');
		var testH = contentEl.getScrollSize().y + titleHeight + footer;
		var h = testH < window.getHeight() ? testH : window.getHeight();
		this.window.setStyle('height', h);
	},

	/**
	 * Fit the window width to the min of either its content width or the window width
	 */
	fitToWidth: function () {
		var contentEl = this.window.getElement('.itemContent');
		var w = contentEl.getScrollSize().x + 25 < window.getWidth() ? contentEl.getScrollSize().x + 25 : window.getWidth();
		this.window.setStyle('width', w);
	},

	close: function (e)
	{
		if (this.modal) {
			//document.body.setStyle('overflow', 'auto');
		}
		if (e) {
			e.stop();
		}
		//this.options.destroy = true;

		// By default cant destroy as we want to be able to reuse them (see crop in fileupload element)
		if (this.options.destroy) {

			// However db join add (in repeating group) has a fit if we don't remove its content
			this.window.destroy();
			delete(Fabrik.Windows[this.options.id]);
		} else {
			this.window.fade('hide');
		}
		this.fireEvent('onClose', [this]);
	},

	open: function (e) {
		// Crop fileupload interface doesn't like window scrolling when open, so stop it
		if (this.modal) {
			//document.body.setStyle('overflow', 'hidden');
		}
		if (e) {
			e.stop();
		}
		this.window.fade('show');
		this.fireEvent('onOpen', [this]);
	}

});

Fabrik.Modal = new Class({
	Extends: Fabrik.Window,

	modal: true,

	classSuffix: 'fabrikWindow-modal',

	getHandle: function () {
		var c = this.handleClass();
		return new Element('div', {'class': c});
	}
});

Fabrik.RedirectWindow = new Class({
	Extends: Fabrik.Window,
	initialize: function (opts) {
		var opts2 = {
			'id': 'redirect',
			'title': opts.title ? opts.title : '',
			loadMethod: loadMethod,
			'width': opts.width ? opts.width : 300,
			'height': opts.height ? opts.height : 320,
			'minimizable': false,
			'collapsible': true

		};
		opts2.id = 'redirect';
		opts = Object.merge(opts2, opts);
		var loadMethod;
		//if its a site page load via xhr otherwise load as iframe
		opts.loadMethod = 'xhr';
		if (!opts.contentURL.contains(Fabrik.liveSite) && (opts.contentURL.contains('http://') || opts.contentURL.contains('https://'))) {
			opts.loadMethod = 'iframe';
		} else {
			if (!opts.contentURL.contains('tmpl=component')) {
				opts.contentURL += opts.contentURL.contains('?') ? '&tmpl=component' : '?tmpl=component';
			}
		}
		this.setOptions(opts);
		this.makeWindow();
	}
});
