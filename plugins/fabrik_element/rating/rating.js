/**
 * Rating Element
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'fab/element'], function (jQuery, FbElement) {
	window.FbRating = new Class({
		Extends   : FbElement,
		initialize: function (element, options) {
			this.field = document.id(element);
			this.parent(element, options);
			if (this.options.canRate === false) {
				return;
			}
			if (this.options.mode === 'creator-rating' && this.options.view === 'details') {

				// Deactivate if in detail view and only the record creator can rate
				return;
			}
			this.rating = this.options.rating;
			Fabrik.addEvent('fabrik.form.refresh', function (e) {
				this.setup(e);
			}.bind(this));
			this.setup(this.options.row_id);
			this.setStars();
		},

		setup: function (rowid) {
			this.options.row_id = rowid;
			this.element = document.id(this.options.element + '_div');
			this.spinner = new Asset.image(Fabrik.liveSite + 'media/com_fabrik/images/ajax-loader.gif', {
				'alt'  : 'loading',
				'class': 'ajax-loader'
			});
			this.stars = this.element.getElements('.starRating');
			this.ratingMessage = this.element.getElement('.ratingMessage');
			this.stars.each(function (i) {
				i.addEvent('mouseover', function (e) {
					this.stars.each(function (ii) {
						if (this._getRating(i) >= this._getRating(ii)) {
							if (Fabrik.bootstrapped) {
								ii.removeClass(this.options.starIconEmpty).addClass(this.options.starIcon);
							} else {
								ii.src = this.options.insrc;
							}
						} else {
							if (Fabrik.bootstrapped) {
								ii.addClass(this.options.starIconEmpty).removeClass(this.options.starIcon);
							} else {
								ii.src = this.options.insrc;
							}
						}
					}.bind(this));
					this.ratingMessage.innerHTML = i.get('data-rating');
				}.bind(this));
			}.bind(this));

			this.stars.each(function (i) {
				i.addEvent('mouseout', function (e) {
					this.stars.each(function (ii) {
						if (Fabrik.bootstrapped) {
							ii.removeClass(this.options.starIcon).addClass(this.options.starIconEmpty);
						} else {
							ii.src = this.options.outsrc;
						}
					}.bind(this));
					this.ratingMessage.innerHTML = '&nbsp;';
				}.bind(this));
			}.bind(this));

			this.stars.each(function (i) {
				i.addEvent('click', function (e) {
					this.rating = this._getRating(i);
					this.field.value = this.rating;
					this.doAjax();
				}.bind(this));
			}.bind(this));
			var clearButton = this.getClearButton();
			this.element.addEvent('mouseout', function (e) {
				this.setStars();
			}.bind(this));

			this.element.addEvent('mouseover', function (e) {
				if (typeOf(clearButton) !== 'null') {
					clearButton.setStyles({
						visibility: 'visible'
					});
				}
			}.bind(this));

			if (typeOf(clearButton) !== 'null') {
				clearButton.addEvent('mouseover', function (e) {
					if (!Fabrik.bootstrapped) {
						e.target.src = this.options.clearinsrc;
					}
                    this.stars.each(function (ii) {
						ii.removeClass(this.options.starIcon).addClass(this.options.starIconEmpty);
                    }.bind(this));
					this.ratingMessage.set('html', Joomla.JText._('PLG_ELEMENT_RATING_NO_RATING'));
				}.bind(this));

				clearButton.addEvent('mouseout', function (e) {
					if (!Fabrik.bootstrapped && this.rating !== -1) {
						e.target.src = this.options.clearoutsrc;
					}
					this.ratingMessage.innerHTML = '&nbsp;';
				}.bind(this));

				clearButton.addEvent('click', function (e) {
					this.rating = -1;
					this.field.value = '';
					this.stars.each(function (ii) {
						if (Fabrik.bootstrapped) {
							ii.removeClass(this.options.starIcon).addClass(this.options.starIconEmpty);
						} else {
							ii.src = this.options.outsrc;
						}
					}.bind(this));
					if (!Fabrik.bootstrapped) {
						this.getClearButton().src = this.options.clearinsrc;
					}

					this.doAjax();
				}.bind(this));
			}
			this.setStars();
		},

		doAjax: function () {
			if (this.options.canRate === false || this.options.doAjax === false) {
				return;
			}

			this.spinner.inject(this.ratingMessage);
			var data = {
				'option'     : 'com_fabrik',
				'format'     : 'raw',
				'task'       : 'plugin.pluginAjax',
				'plugin'     : 'rating',
				'method'     : 'ajax_rate',
				'g'          : 'element',
				'element_id' : this.options.elid,
				'formid'     : this.options.formid,
				'row_id'     : this.options.row_id,
				'elementname': this.options.elid,
				'userid'     : this.options.userid,
				'rating'     : this.rating,
				'listid'     : this.options.listid
			};

			var closeFn = new Request({
				url       : '',
				'data'    : data,
				onComplete: function (r) {
					this.spinner.dispose();
                    this.update(r);
				}.bind(this)
			}).send();
		},

		_getRating: function (i) {
			var r = i.get('data-rating');
			return r.toInt();
		},

		setStars: function () {
			if (typeOf(this.stars) === 'null') {
				return;
			}
			this.stars.each(function (ii) {
				var starScore = this._getRating(ii);
				if (Fabrik.bootstrapped) {
					if (starScore <= this.rating) {
						ii.removeClass(this.options.starIconEmpty).addClass(this.options.starIcon);
					} else {
						ii.removeClass(this.options.starIconEmpty).addClass(this.options.starIconEmpty);
					}

				} else {
					ii.src = starScore <= this.rating ? this.options.insrc : this.options.outsrc;
				}
			}.bind(this));

			if (!Fabrik.bootstrapped && typeOf(clearButton) !== 'null') {
                var clearButton = this.getClearButton();
				clearButton.src = this.rating !== -1 ? this.options.clearoutsrc : this.options.clearinsrc;
			}
		},

		getClearButton: function () {
			return this.element.getElement('span[data-rating=-1]');
		},

		update: function (val) {
			this.rating = Math.round(parseFloat(val));;
			this.field.value = this.rating;
			var s = this.element.getParent('.fabrikElementContainer').getElement('.ratingScore');
			if (typeOf(s) !== 'null') {
				s.set('text', val);
			}
			this.setStars();
		},

		cloned: function (c) {
			this.element.getParent('.fabrikElementContainer').getElement('.fabrikSubElementContainer').id = this.options.element + '_div';
			this.field = document.id(this.options.element);
			this.setup();
			this.parent();
		},

		reset: function () {
			this.resetEvents();
			this.update(this.options.defaultVal);
		}
	});

	return window.FbRating;
});