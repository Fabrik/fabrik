/**
 * Mootools extensions
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

function CloneObject(what, recursive, asreference) {
	if (typeOf(what) !== 'object') {
		return what;
	}
	var h = $H(what);
	h.each(function (v, k) {
		if (typeOf(v) === 'object' && recursive === true && !asreference.contains(k)) {
			this[k] = new CloneObject(v, recursive, asreference);
		} else {
			this[k] = v;
		}
	}.bind(this));
	return this;
}

String.implement({

	toObject: function ()
	{
		var o = {};
		this.split('&').each(function (pair) {
			var b = pair.split('=');
			o[b[0]] = b[1];
		});
		return o;
	}
});

var mHide = Element.prototype.hide;
var mShow = Element.prototype.show;
var mSlide = Element.prototype.slide;

Element.implement({

	findClassUp: function (classname) {
		if (this.hasClass(classname)) {
			return this;
		}
		var el = document.id(this);
		while (el && !el.hasClass(classname)) {
			if (typeOf(el.getParent()) !== 'element') {
				return false;
			}
			el = el.getParent();
		}
		return el;
	},

	up: function (index) {
		index = index ? index : 0;
		var el = this;
		for (var i = 0; i <= index; i ++) {
			el = el.getParent();
		}
		return el;
	},

	within: function (p) {
		var parenttest = this;
		while (parenttest.parentNode !== null) {
			if (parenttest === p) {
				return true;
			}
			parenttest = parenttest.parentNode;
		}
		return false;
	},

	cloneWithIds: function (c) {
		return this.clone(c, true);
	},

	down: function (expression, index) {
		var descendants = this.getChildren();
		if (arguments.length === 0) {
			return descendants[0];
		}
		return descendants[index];
	},

	findUp: function (tag) {
		if (this.get('tag') === tag) {
			return this;
		}
		var el = this;
		while (el && el.get('tag') !== tag) {
			el = el.getParent();
		}
		return el;
	},

	//x, y = mouse location
	mouseInside: function (x, y) {
		var coords = this.getCoordinates();
		var elLeft = coords.left;
		var elRight = coords.left + coords.width;
		var elTop = coords.top;
		var elBottom = coords.bottom;
		if (x >= elLeft && x <= elRight) {
			if (y >= elTop && y <= elBottom) {
				return true;
			}
		}
		return false;
	},

	getValue: function () {
		return this.get('value');
	},

	/*
	 * These are needed to get some of the JQuery bootstrap built in effects working,
	 * like the carousel, and require you to add the 'mootools-noconflict' class to
	 * containers you want to use those effect with, like ...
	 * <div class="carousel slide mootools-noconflict'>
	 */

	hide: function () {
		if (Fabrik.bootstrapVersion('modal') === '3.x') {
			return;
		}
		if (this.hasClass("mootools-noconflict")) {
			return this;
		}
		mHide.apply(this, arguments);
	},

	show: function (v) {
		if (this.hasClass("mootools-noconflict")) {
			return this;
		}
		mShow.apply(this, v);
	},

	slide: function (v) {
		if (this.hasClass("mootools-noconflict")) {
			return this;
		}
		mSlide.apply(this, v);
	}
});

/**
 * Misc. functions, nothing to do with Mootools ... we just needed
 * some common js include to put them in!
 */

function fconsole(thing) {
	if (typeof(window.console) !== "undefined") {
		console.log(thing);
	}
}