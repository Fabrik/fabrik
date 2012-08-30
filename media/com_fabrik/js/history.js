/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $A:true, $H:true,unescape:true */

var History = new Class({
	initialize: function (undobutton, redobutton) {
		this.recording = true;
		this.pointer = -1;
		if (document.id(undobutton)) {
			document.id(undobutton).addEvent('click', this.undo.bindWithEvent(this));
		}
		if (document.id(redobutton)) {
			document.id(redobutton).addEvent('click', this.redo.bindWithEvent(this));
		}
		Fabrik.addEvent('fabrik.history.on', this.on.bindWithEvent(this));
		Fabrik.addEvent('fabrik.history.off', this.off.bindWithEvent(this));
		Fabrik.addEvent('fabrik.history.add', this.add.bindWithEvent(this));
		this.history = $A([]);
	},

	undo : function () {
		if (this.pointer > -1) {
			this.off();
			var h = this.history[this.pointer];
			var f = h.undofunc;
			var p = h.undoparams;
			var res = f.attempt(p, h.object);
			this.on();
			this.pointer --;
		}

	},

	redo : function () {
		if (this.pointer < this.history.length - 1) {
			this.pointer ++;
			this.off();
			var h = this.history[this.pointer];
			var f = h.redofunc;
			var p = h.redoparams;
			var res = f.attempt(p, h.object);
			this.on();
		}
	},

	add : function (obj, undofunc, undoparams, redofunc, redoparams) {
		if (this.recording) {
			// remove history which is newer than current pointer location
			var newh = this.history.filter(function (h, x) {
				return x <= this.pointer;
			}.bind(this));
			this.history = newh;
			this.history.push({
				'object' : obj,
				'undofunc' : undofunc,
				'undoparams' : undoparams,
				'redofunc' : redofunc,
				'redoparams' : redoparams
			});
			this.pointer++;
		}
	},

	on : function () {
		this.recording = true;
	},

	off : function () {
		this.recording = false;
	}
});
