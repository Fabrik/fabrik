/**
 * @package Joomla!
 * @subpackage JavaScript
 * @since 1.5
 */
var FbRatingList = new Class({

	options: {
		'userid': 0,
		'mode' : ''
	},

	Implements: [Events, Options],

	initialize: function (id, options) {
		options.element = id;
		this.setOptions(options);
		if (this.options.mode === 'creator-rating') {
			return;
		}
		this.col = $$('.' + id);
		this.origRating = {};
		this.col.each(function (tr) {
			var stars = tr.getElements('.starRating');

			stars.each(function (star) {
				star.addEvent('mouseover', function (e) {
					this.origRating[tr.id] = star.getParent('.fabrik_element').getElement('.ratingMessage').innerHTML.toInt();
					stars.each(function (ii) {
						if (this._getRating(star) >= this._getRating(ii)) {
							ii.src = this.options.insrc;
						} else {
							ii.src = this.options.outsrc;
						}
					}.bind(this));
					star.getParent('.fabrik_element').getElement('.ratingMessage').innerHTML = star.alt;
				}.bind(this));

				star.addEvent('mouseout', function (e) {
					stars.each(function (ii) {
						if (this.origRating[tr.id] >= this._getRating(ii)) {
							ii.src = this.options.insrc;
						} else {
							ii.src = this.options.outsrc;
						}
					}.bind(this));
					star.getParent('.fabrik_element').getElement('.ratingMessage').innerHTML = this.origRating[tr.id];
				}.bind(this));
			}.bind(this));

			stars.each(function (star) {
				star.addEvent('click', this.doAjax.bindWithEvent(this, [ star ]));
			}.bind(this));

		}.bind(this));

	},

	_getRating : function (i) {
		r = i.className.replace("rate_", "").replace("starRating ", "");
		return r.toInt();
	},

	doAjax : function (e, star) {
		e.stop();
		this.rating = this._getRating(star);
		var ratingmsg = star.getParent('.fabrik_element').getElement('.ratingMessage');
		Fabrik.loader.start(ratingmsg);
		
		var starRatingCover = new Element('div', {id: 'starRatingCover', styles: {bottom: 0, top: 0, right: 0, left: 0, position: 'absolute', cursor: 'progress'} });
		var starRatingContainer = star.getParent('.fabrik_element').getElement('div');
		starRatingContainer.grab(starRatingCover, 'top');
		
		var row = document.id(star).getParent('.fabrik_row');
		var rowid = row.id.replace('list_' + document.fabrikList.elements['listref'].value + '_row_', '');
		var data = {
			'option': 'com_fabrik',
			'format': 'raw',
			'task': 'plugin.pluginAjax',
			'plugin': 'rating',
			'g': 'element',
			'method': 'ajax_rate',
			'element_id': this.options.elid,
			'row_id' : rowid,
			'elementname' : this.options.elid,
			'userid' : this.options.userid,
			'rating' : this.rating,
			'mode' : this.options.mode
		};
		new Request({url: '',
			'data': data,
			onComplete: function (r) {
				r = r.toInt();
				this.rating = r;
				ratingmsg.set('html', this.rating);
				Fabrik.loader.stop(ratingmsg);
				star.getParent('.fabrik_element').getElements('img').each(function (i, x) {
					i.src = (x < r) ? this.options.insrc : this.options.outsrc;
				}.bind(this))
				document.id('starRatingCover').destroy();
			}.bind(this)
		}).send();
	}
});