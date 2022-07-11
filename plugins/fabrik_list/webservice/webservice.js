/**
 * List Webservice
 *
 * @copyright: Copyright (C) 2005-2016, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
define(['jquery', 'fab/list-plugin'], function (jQuery, FbListPlugin) {
	var FbListWebservice = new Class({
		Extends   : FbListPlugin,
		initialize: function (options) {
			this.parent(options);
		},

		buttonAction: function () {
			this.list.submit('list.doPlugin');
		}
	});
	return FbListWebservice;
});
