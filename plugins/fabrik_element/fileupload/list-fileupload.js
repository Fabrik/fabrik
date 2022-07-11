/**
 * Thumbs Element - List
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery'], function (jQuery) {
	var FbFileuploadList = new Class({

		options: {
			'isCarousel': false
		},

		Implements: [Events, Options],

		initialize: function (id, options) {
			this.setOptions(options);

			if (this.options.isCarousel) {
				jQuery('.slickCarousel').slick();
				jQuery('.slickCarouselImage').css('opacity', '1');
			}
		}
	});

	return FbFileuploadList;
});