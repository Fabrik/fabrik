/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, $H:true, FbForm:true , define:true */

/**
 * Console.log wrapper
 *
 * Enhanced by Sophist to:
 *   Create grouped multi-line output, with objects on separate lines
 *   Clone objects before logging so that display is object state at the time of logging not (standard console.log) current version at time of viewing.
 *   Include a stack trace if Joomla Debug is set.
 */
function fconsole() {
	if (typeof (window.console) !== 'undefined') {

		var output = [], current = null, i;
		for (i = 0; i < arguments.length; i++) {
			var arg = arguments[i];
			if (current === null) {
				current = arg;
			} else if (typeof current === 'object') {
				if (current instanceof Element || current instanceof HTMLDocument) {
					output.push(current.cloneNode(true));
				} else if (current instanceof Array) {
					output.push(current.slice(0));
				} else {
					output.push(fDeepClone(current));
				}
				current = arg;
			} else if (typeof arg === 'object') {
				output.push(current);
				current = arg;
			} else { // both are strings or can be implicitly converted to strings
				current += ' ' + arg;
			}
		}
		if (typeof current === 'object') {
			if (current instanceof Element || current instanceof HTMLDocument) {
				output.push(current.cloneNode(true));
			} else if (current instanceof Array) {
				output.push(current.slice(0));
			} else {
				output.push(fDeepClone(current));
			}
		} else {
			output.push(current);
		}
		if (output.length === 1 && !Fabrik.debug) {
			console.log(output[0]);
		} else {
			console.groupCollapsed(output[0]);
			for (i = 1; i < output.length; i++) {
				console.log(output[i]);
			}
			if (Fabrik.debug) {
			  console.trace('Stack trace');
			}
			console.groupEnd();
		}
	}
}

function fdebug() {
	if (typeof Fabrik !== 'undefined' && Fabrik.debug) {
		fconsole.apply(window, arguments);
	}
}

function fDeepClone(obj, higher) {
	higher = (typeof higher !== 'undefined') ?  higher : [];
	try {
		if (obj === null || typeof obj !== 'object') return obj;
		if (obj.constructor === Date || obj.constructor == RegExp || obj.constructor == Function ||
			obj.constructor === String || obj.constructor == Number || obj.constructor == Boolean)
			return new obj.constructor(obj);

		var result = new obj.constructor();
		higher.push(obj);

		for (var name in obj)
		{
			if (obj.hasOwnProperty(name)) {
				if (higher.includes(obj[name])) {
					result[name] = obj[name];
				} else {
					result[name] = fDeepClone(obj[name], higher);
				}
			}
		}

		return result;
	}
	catch(error) {
		return obj;
	}
}


Request.HTML = new Class({

	Extends: Request,

	options: {
		update	 : false,
		append	 : false,
		evalScripts: true,
		filter	 : false,
		headers	: {
			Accept: 'text/html, application/xml, text/xml, */*'
		}
	},

	success: function (text) {
		var options = this.options, response = this.response;

		response.html = text.stripScripts(function (script) {
			response.javascript = script;
		});

		var match = response.html.match(/<body[^>]*>([\s\S]*?)<\/body>/i);
		if (match) {
			response.html = match[1];
		}
		var temp = new Element('div').set('html', response.html);

		response.tree = temp.childNodes;
		response.elements = temp.getElements(options.filter || '*');

		if (options.filter) {
			response.tree = response.elements;
		}
		if (options.update) {
			var update = document.id(options.update).empty();
			if (options.filter) {
				update.adopt(response.elements);
			} else {

				update.set('html', response.html);
			}
		} else if (options.append) {
			var append = document.id(options.append);
			if (options.filter) {
				response.elements.reverse().inject(append);
			} else {
				append.adopt(temp.getChildren());
			}
		}
		if (options.evalScripts) {
			Browser.exec(response.javascript);
		}

		this.onSuccess(response.tree, response.elements, response.html, response.javascript);
	}
});

/**
 * Keeps the element position in the centre even when scroll/resizing
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
		var l = jQuery(window).width() / 2 - this.getWidth() / 2;
		var t = window.getScrollTop() + (jQuery(window).height() / 2 - this.getHeight() / 2);
		this.setStyles({
			left: l,
			top : t
		});
	}
});

/**
 * Extend the Array object
 *
 * @param candid
 *			The string to search for
 * @returns Returns the index of the first match or -1 if not found
 */
Array.prototype.searchFor = function (candid) {
	var i;
	for (i = 0; i < this.length; i++) {
		if (this[i].indexOf(candid) === 0) {
			return i;
		}
	}
	return -1;
};

/**
 * Object.keys polyfill for IE8
 */
if (!Object.keys) {
	Object.keys = function (obj) {
		return jQuery.map(obj, function (v, k) {
			return k;
		});
	};
}
