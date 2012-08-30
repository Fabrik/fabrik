/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $A:true, $H:true,unescape:true,Asset:true,FloatingTips:true,head:true,IconGenerator:true */

/**
 *  this class is temporarily requied until this patch:
 *  https://github.com/joomla/joomla-platform/pull/1209/files
 *  makes it into the CMS code. Its purpose is to queue ajax requests so they are not
 *  all fired at the same time - which result in db session errors.
 *   
 *  Currently this is called from:
 *  fabriktables.js
 *  
 */
RequestQueue = new Class({
	
	queue: {}, // object of xhr objects
	
	initialize: function () {
		this.periodical = this.processQueue.periodical(500, this);
	},
	
	add: function (xhr) {
		var k = xhr.options.url + Object.toQueryString(xhr.options.data) + Math.random();
		if (!this.queue[k]) {
			this.queue[k] = xhr;
		}
	},
	
	processQueue: function () {
		if (Object.keys(this.queue).length === 0) {
			return;
		}
		var xhr = {},
		running = false;

		// Remove successfuly completed xhr
		$H(this.queue).each(function (xhr, k) {
			if (xhr.isSuccess()) {
				delete(this.queue[k]);
				running = false;
			}
		}.bind(this));
		
		// Find first xhr not run and completed to run
		$H(this.queue).each(function (xhr, k) {
			if (!xhr.isRunning() && !xhr.isSuccess() && !running) {
				xhr.send();
				running = true;
			}
		});
	},
	
	empty: function () {
		return Object.keys(this.queue).length === 0;
	}
});

Request.HTML = new Class({

	Extends: Request,
	
	options: {
		update: false,
		append: false,
		evalScripts: true,
		filter: false,
		headers: {
			Accept: 'text/html, application/xml, text/xml, */*'
		}
	},
	success: function (text) {
		
		var options = this.options, response = this.response;
		var srcs = text.match(/<script[^>]*>([\s\S]*?)<\/script>/gi);
		var urls = [];
		if (typeOf(srcs) !== 'null') {
			for (var x = 0; x < srcs.length; x++) {
				if (srcs[x].contains('src="')) {
					var m = srcs[x].match(/src=\"([\s\S]*?)\"/);
					if (m[1]) {
						urls.push(m[1]);
					}
				}
			}
			var scriptadd = "head.js('" + urls.join("','") + "');\n";
			Browser.exec(scriptadd);
		}
		response.html = text.stripScripts(function (script) {
			response.javascript = script;
		});
		
		var match = response.html.match(/<body[^>]*>([\s\S]*?)<\/body>/i);
		if (match) {
			response.html = match[1];
		}
		var temp = new Element('div').set('html', response.html);

		response.tree = temp.childNodes;
		response.elements = temp.getElements('*');

		if (options.filter) {
			response.tree = response.elements.filter(options.filter);
		}
		if (options.update) {
			document.id(options.update).empty().set('html', response.html);
		}
		else if (options.append) {
			document.id(options.append).adopt(temp.getChildren());
		}
		if (options.evalScripts) {
			//response.javascript = "(function () {"+response.javascript+"}).delay(6000)";
			Browser.exec(response.javascript);
		}

		this.onSuccess(response.tree, response.elements, response.html, response.javascript);
	}
});

/**
 * keeps the element posisiton in the center even when scroll/resizing
 */

Element.implement({
	keepCenter: function () {
		this.makeCenter();
		window.addEvent('scroll', function () {
			this.makeCenter();
		}.bind(this));
		window.addEvent('resize', function () {
			this.makeCenter();
		}.bind(this));
	},
	makeCenter: function () {
		var l = window.getWidth() / 2 - this.getWidth() / 2;
		var t = window.getScrollTop() + (window.getHeight() / 2 - this.getHeight() / 2);
		this.setStyles({left: l, top: t});
	}
});

/**
 * loading aninimation class, either inline next to an element or 
 * full screen
 */

var Loader = new Class({
	
	initialize: function (options) {
		this.spinners = {};			
	},
	
	getSpinner: function (inline, msg) {
		msg = msg ? msg : 'loading';
		if (typeOf(document.id(inline)) === 'null') {
			inline = false;
		}
		inline = inline ? inline : false;
		var target = inline ? inline : document.body;
		if (!this.spinners[inline]) {
			this.spinners[inline] = new Spinner(target, {'message': msg});
		}
		return this.spinners[inline];
	},
	
	start: function (inline, msg) {
		this.getSpinner(inline, msg).position().show();
	},
	
	stop: function (inline, msg, keepOverlay) {
		var s = this.getSpinner(inline, msg);
		//dont keep the spinner once stop is called - causes issue when loading ajax form for 2nd time
		if (Browser.ie && Browser.version < 9) {
			//well ok we have to in ie8 ;( otherwise it give a js error somewhere in FX
			s.clearChain(); // tried this to remove FX but didnt seem to achieve anything
			s.hide();
		} else {
			s.destroy();
		}
		delete this.spinners[inline];
	}
});

/**
 * create the Fabrik name space
 */
(function () {
	if (typeof(Fabrik) === "undefined") {
		
		Fabrik = {};
		Fabrik.events = {};
		Fabrik.Windows = {};
		Fabrik.loader = new Loader();
		Fabrik.blocks = {};
		Fabrik.addBlock = function (blockid, block) {
			Fabrik.blocks[blockid] = block;
			Fabrik.fireEvent('fabrik.block.added', block);
		};
		document.addEvent('click:relay(.fabrik_delete a)', function (e, target) {
			Fabrik.watchDelete(e, target);
		});
		//was in head.ready but that cause js error for fileupload in admin when it wanted to 
		//build its window.
		Fabrik.iconGen = new IconGenerator({scale: 0.5});
		
		Fabrik.removeEvent = function (type, fn) {
			if (Fabrik.events[type]) {
				var index = Fabrik.events[type].indexOf(fn);
				if (index !== -1) {
					delete Fabrik.events[type][index];
				}
			}
		};
		
		//events test: replacing window.addEvents as they are reset when you reload mootools in ajax window.
		// need to load mootools in ajax window otherwise Fabrik classes dont correctly load
		Fabrik.addEvent = function (type, fn) {
			if (!Fabrik.events[type]) {
				Fabrik.events[type] = [];
			}
			if (!Fabrik.events[type].contains(fn)) {
				Fabrik.events[type].push(fn);
			}
		};
		
		Fabrik.addEvents = function (events) {
			for (var event in events) {
				Fabrik.addEvent(event, events[event]);
			}
			return this;
		};
		
		Fabrik.fireEvent = function (type, args, delay) {
			var events = Fabrik.events;
			if (!events || !events[type]) {
				return this;
			}
			args = Array.from(args);
	
			events[type].each(function (fn) {
				if (delay) {
					fn.delay(delay, this, args);
				} else {
					fn.apply(this, args);
				}
			}, this);
			return this;
		};
		
		Fabrik.requestQueue = new RequestQueue();
		
		/** globally observe delete links **/
		
		Fabrik.watchDelete = function (e, target) {
			var l, ref, r;
			r = e.target.getParent('.fabrik_row');
			if (!r) {
				r = Fabrik.activeRow;
			}
			if (r) {
				var chx = r.getElement('input[type=checkbox][name*=id]');
				if (typeOf(chx) !== 'null') {
					chx.checked = true;
				}
				ref = r.id.split('_');
				ref = ref.splice(0, ref.length - 2).join('_');
				l = Fabrik.blocks[ref];
			} else {
				// checkAll
				ref = e.target.getParent('.fabrikList');
				if (typeOf(ref) !== 'null') {
					//embedded in list
					ref = ref.id;
					l = Fabrik.blocks[ref];
				} else {
					//floating
					ref = target.getParent('.floating-tip-wrapper').retrieve('list').id;
					l = Fabrik.blocks[ref];
					if (l.options.actionMethod === 'floating') { // should only check all for floating tips
						l.form.getElements('input[type=checkbox][name*=id], input[type=checkbox][name=checkAll]').each(function (c) {
							c.checked = true;
						});
					}
				}
			}
			//get correct list block
			if (!l.submit('list.delete')) {
				e.stop();
			}
		};
		
		
		window.fireEvent('fabrik.loaded');
	}
}());

