/**
 * Thumbs Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbThumbs =  new Class({
	Extends : FbElement,
	initialize: function (element, options, thumb) {
		this.field = document.id(element);
		this.imagepath = Fabrik.liveSite + 'plugins/fabrik_element/thumbs/images/';
		this.parent(element, options);

		this.element = document.id(element + '_div');
		this.thumb = thumb;
		this.spinner = new Asset.image(Fabrik.liveSite + 'media/com_fabrik/images/ajax-loader.gif', {'alt': 'loading', 'class': 'ajax-loader'});
		this.thumbup = document.id('thumbup');
		this.thumbdown = document.id('thumbdown');
		this.thumbup.addEvent('mouseover', function (e) {
			this.thumbup.setStyle('cursor', 'pointer');
			this.thumbup.src = this.imagepath + "thumb_up_in.gif";
		}.bind(this));
		this.thumbdown.addEvent('mouseover', function (e) {
			this.thumbdown.setStyle('cursor', 'pointer');
			this.thumbdown.src = this.imagepath + "thumb_down_in.gif";
		}.bind(this));

		this.thumbup.addEvent('mouseout', function (e) {
			this.thumbup.setStyle('cursor', '');
			if (this.options.myThumb === 'up') {
				this.thumbup.src = this.imagepath + "thumb_up_in.gif";
			} else {
				this.thumbup.src = this.imagepath + "thumb_up_out.gif";
			}
		}.bind(this));
		this.thumbdown.addEvent('mouseout', function (e) {
			this.thumbdown.setStyle('cursor', '');
			if (this.options.myThumb === 'down') {
				this.thumbdown.src = this.imagepath + "thumb_down_in.gif";
			} else {
				this.thumbdown.src = this.imagepath + "thumb_down_out.gif";
			}
		}.bind(this));

		this.thumbup.addEvent('click', function (e) {
			this.doAjax('up');
		}.bind(this));
		this.thumbdown.addEvent('click', function (e) {
			this.doAjax('down');
		}.bind(this));
	},

	doAjax: function (th) {
		if (this.options.editable === false) {
			var forspin = document.id('count_thumb' + th);
			this.spinner.inject(forspin);
			var data = {
				'option': 'com_fabrik',
				'format': 'raw',
				'task': 'plugin.pluginAjax',
				'plugin': 'thumbs',
				'method': 'ajax_rate',
				'g': 'element',
				'element_id': this.options.elid,
				'row_id': this.options.row_id,
				'elementname': this.options.elid,
				'userid': this.options.userid,
				'thumb': th,
				'listid': this.options.listid
			};

			new Request({url: '',
				'data': data,
				onComplete: function (r) {
					r = JSON.decode(r);
					this.spinner.dispose();
					if (r.error) {
						console.log(r.error);
					} else {
						if (r !== '') {
							var count_thumbup = document.id('count_thumbup');
							var count_thumbdown = document.id('count_thumbdown');
							var thumbup = document.id('thumbup');
							var thumbdown = document.id('thumbdown');
							count_thumbup.set('html', r[0]);
							count_thumbdown.set('html', r[1]);
							// Well since the element can't be rendered in form view I guess this isn't really needed
							document.id(this.element.id).getElement('.' + this.field.id).value = r[0].toFloat() - r[1].toFloat();
							if (r[0] === 1) {
								thumbup.src = this.imagepath + "thumb_up_in.gif";
								thumbdown.src = this.imagepath + "thumb_down_out.gif";
							} else {
								thumbup.src = this.imagepath + "thumb_up_out.gif";
								thumbdown.src = this.imagepath + "thumb_down_in.gif";
							}
						}
					}
				}.bind(this)
			}).send();
		}
	}
});