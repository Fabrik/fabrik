/**
 * List Webservice
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbListWebservice = new Class({
	Extends: FbListPlugin,
	initialize: function (options) {
		this.parent(options);
	},

	buttonAction: function () {
		this.list.submit('list.doPlugin');
	}
});