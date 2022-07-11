/**
 * Admin SwapList Editor
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var SwapList = new Class({

	initialize: function (from, to, addbutton, removebutton, upbutton, downbutton) {
		this.from = document.id(from);
		this.to = document.id(to);
		if (typeOf(document.id(addbutton)) !== 'null') {
			document.id(addbutton).addEvent('click', function (e) {
				e.stop();
				document.id('jform__createGroup0').checked = true;
				this.addSelectedToList(this.from, this.to);
				this.delSelectedFromList(this.from);
			}.bind(this));

			document.id(removebutton).addEvent('click', function (e) {
				e.stop();
				this.addSelectedToList(this.to, this.from);
				this.delSelectedFromList(this.to);
			}.bind(this));

			document.id(upbutton).addEvent('click', function (e) {
				e.stop();
				this.moveInList(-1);
			}.bind(this));

			document.id(downbutton).addEvent('click', function (e) {
				e.stop();
				this.moveInList(+1);
			}.bind(this));

			document.id('adminForm').onsubmit = function (e) {
				this.to.getElements('option').each(function (opt) {
					opt.selected = true;
				});
				return true;
			}.bind(this);
		}
	},

	addSelectedToList: function (from, to) {
		var i;
		var srcLen = from.length;
		var tgtLen = to.length;
		var tgt = "x";

		// Build array of target items
		for (i = tgtLen - 1; i > -1; i--) {
			tgt += "," + to.options[i].value + ",";
		}

		// Pull selected resources and add them to list
		for (i = 0; i < srcLen; i++) {
			if (from.options[i].selected && tgt.indexOf("," + from.options[i].value + ",") === -1) {
				opt = new Option(from.options[i].text, from.options[i].value);
				to.options[to.length] = opt;
			}
		}
	},

	delSelectedFromList: function (from) {
		var srcLen = from.length;
		for (var i = srcLen - 1; i > -1; i--) {
			if (from.options[i].selected) {
				from.options[i] = null;
			}
		}
	},

	moveInList: function (to) {
		var srcList = this.to;
		var index = this.to.selectedIndex;
		var total = srcList.options.length - 1;

		if (index === -1) {
			return false;
		}
		if (to === +1 && index === total) {
			return false;
		}
		if (to === -1 && index === 0) {
			return false;
		}

		var items = [];
		var values = [];

		for (i = total; i >= 0; i--) {
			items[i] = srcList.options[i].text;
			values[i] = srcList.options[i].value;
		}
		for (i = total; i >= 0; i--) {
			if (index === i) {
				srcList.options[i + to] = new Option(items[i], values[i], 0, 1);
				srcList.options[i] = new Option(items[i + to], values[i + to]);
				i--;
			} else {
				srcList.options[i] = new Option(items[i], values[i]);
			}
		}
		srcList.focus();
		return true;
	}
});