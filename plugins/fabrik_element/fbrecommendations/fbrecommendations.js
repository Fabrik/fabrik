/**
 * Facebook Recommendations Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbRecommendations = new Class({
	Extends: FbElement,
	initialize: function (element, options) {
		this.plugin = 'fbRecommendations';
		this.parent(element, options);
	}
});