/**
 * Kaltura Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbKaltura = new Class({
	Extends : FbElement,
	initialize : function (element, options) {
		this.plugin = 'kaltura';
		this.parent(element, options);
		swfobject.embedSWF("http://www.kaltura.com/kcw/ui_conf_id/36200", "kcw", "680", "360", "9.0.0", false, this.options.flash, this.options.uploader);
	},

	doneUploading : function (e, entries) {

	}
});