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

		var slickOptions = {
            slidesToShow: 1,
            slidesToScroll: 1,
            autoplay: this.options.slideshow_delay === 0 ? false : true,
            autoplaySpeed: this.options.slideshow_delay,
            //variableWidth: true,
            arrows: true,
            dots: false,
            cssEase: 'linear',
            infinite: true,
            speed: this.options.slideshow_duration
		};

		var slickJSON = JSON.parse(this.options.slideshow_options);
		jQuery.extend(slickOptions, slickJSON);

        var $slider = jQuery('.slider');

		if (this.options.slideshow_thumbnails)
		{
		    var thumbOptions = {
		        asNavFor: '.slider-nav'
            };

		    jQuery.extend(slickOptions, thumbOptions);

            $slider.slick(slickOptions);

            jQuery('.slider-nav').slick({
                slidesToShow: 3,
                slidesToScroll: 1,
                //variableWidth: true,
                arrows: true,
                dots: true,
                centerMode: true,
                focusOnSelect: true,
                asNavFor: '.slider'
            });

		}
		else {
            var noThumbOptions = {
            };

            jQuery.extend(slickOptions, noThumbOptions);

            $slider.slick(slickOptions);
        }

        $slider.on('wheel', function(e) {
            e.preventDefault();

            if (e.originalEvent.deltaY < 0) {
                jQuery(this).slick('slickNext');
            } else {
                jQuery(this).slick('slickPrev');
            }
        });

        /**
         * Hide the loading div, and fire a setPosition to have Slick recalculate sizing (it can't get image sizes
         * when inside a hidden element.  Could probably do this by positioning slider offscreen rather than
         * display none.
         */
        jQuery('.slider_loading').hide();
        $slider.slick('setPosition');

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