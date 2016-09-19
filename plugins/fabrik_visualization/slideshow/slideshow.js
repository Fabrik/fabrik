/**
 * Visualization Slideshow
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbSlideshowViz = new Class({

	Implements: [Options],
	
	options: {},
	
	initialize: function (element, options) {
		this.setOptions(options);
		var opts = {
			controller: true,
			delay: parseInt(this.options.slideshow_delay, 10),
			duration: parseInt(this.options.slideshow_duration, 10),
			height: parseInt(this.options.slideshow_height, 10),
			width: parseInt(this.options.slideshow_width, 10),
			//hu: Fabrik.liveSite,
			hu: this.options.liveSite,
			thumbnails: this.options.slideshow_thumbnails,
			captions: this.options.slideshow_captions
		};
		switch (this.options.slideshow_type) {
		case 1:
			opts = Object.append(opts, {fast: true});
			this.slideshow = new Slideshow(this.options.html_id, this.options.slideshow_data, opts);
			break;
		case 2:
			opts = Object.append(opts, {
				zoom : parseInt(this.options.slideshow_zoom, 10),
				pan : parseInt(this.options.slideshow_pan, 10)
			});
			this.slideshow = new Slideshow.KenBurns(this.options.html_id, this.options.slideshow_data, opts);
			break;
		case 3:
			this.slideshow = new Slideshow.Push(this.options.html_id, this.options.slideshow_data, opts);
			break;
		case 4:
			this.slideshow = new Slideshow.Fold(this.options.html_id, this.options.slideshow_data, opts);
			break;
		}

		this.mediaScan();
	},

	mediaScan: function () {
		if (typeof(Slimbox) !== 'undefined') {
			Slimbox.scanPage();
		}
		if (typeof(Lightbox) !== 'undefined') {
			Lightbox.init();
		}
		if (typeof(Mediabox) !== 'undefined') {
			Mediabox.scanPage();
		}
	}
});