/**
 * Thumbs Element - List
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery'], function (jQuery) {
	var FbThumbsList = new Class({

		options: {
			'imageover'  : '',
			'imageout'   : '',
			'userid'     : '',
			'formid'     : 0,
			'noAccessMsg': '',
			'canUse'     : true
		},

		Implements: [Events, Options],

		initialize: function (id, options) {
			this.setOptions(options);
			//if (this.options.canUse) {
			if (true) {
				this.col = document.getElements('.' + id);
				this.origThumbUp = {};
				this.origThumbDown = {};
				if (Fabrik.bootstrapped || this.options.j3) {
					if (this.options.voteType === 'comment') {
						this.setUpBootstrappedComments();
					} else {
						this.setUpBootstrapped();
					}
				} else {
					this.col.each(function (tr) {
						var row = tr.getParent('.fabrik_row');
						if (row) {
							var rowid = row.id.replace('list_' + this.options.renderContext + '_row_', '');
							var thumbup = tr.getElements('.thumbup');
							var thumbdown = tr.getElements('.thumbdown');
							thumbup.each(function (thumbup) {
								if (this.options.canUse) {
									thumbup.addEvent('mouseover', function (e) {
										thumbup.setStyle('cursor', 'pointer');
										thumbup.src = this.options.imagepath + "thumb_up_in.gif";
									}.bind(this));
									thumbup.addEvent('mouseout', function (e) {
										thumbup.setStyle('cursor', '');
										if (this.options.myThumbs[rowid] === 'up') {
											thumbup.src = this.options.imagepath + "thumb_up_in.gif";
										} else {
											thumbup.src = this.options.imagepath + "thumb_up_out.gif";
										}
									}.bind(this));
									thumbup.addEvent('click', function (e) {
										this.doAjax(thumbup, 'up');
									}.bind(this));
								}
								else {
									thumbup.addEvent('click', function (e) {
										e.stop();
										this.doNoAccess();
									}.bind(this));
								}
							}.bind(this));

							thumbdown.each(function (thumbdown) {
								if (this.options.canUse) {
									thumbdown.addEvent('mouseover', function (e) {
										thumbdown.setStyle('cursor', 'pointer');
										thumbdown.src = this.options.imagepath + "thumb_down_in.gif";
									}.bind(this));

									thumbdown.addEvent('mouseout', function (e) {
										thumbdown.setStyle('cursor', '');
										if (this.options.myThumbs[rowid] === 'down') {
											thumbdown.src = this.options.imagepath + "thumb_down_in.gif";
										} else {
											thumbdown.src = this.options.imagepath + "thumb_down_out.gif";
										}
									}.bind(this));
									thumbdown.addEvent('click', function (e) {
										this.doAjax(thumbdown, 'down');
									}.bind(this));
								}
								else {
									thumbup.addEvent('click', function (e) {
										e.stop();
										this.doNoAccess();
									}.bind(this));
								}
							}.bind(this));
						}
					}.bind(this));
				}
			}
		},

		setUpBootstrappedComments: function () {
			document.addEvent('click:relay(*[data-fabrik-thumb])', function (e, target) {
				if (this.options.canUse) {
					var add = target.hasClass('btn-success') ? false : true;
					var dir = target.get('data-fabrik-thumb');
					var formid = target.get('data-fabrik-thumb-formid');
					var rowid = target.get('data-fabrik-thumb-rowid');

					this.doAjax(target, dir, add);
					if (dir === 'up') {
						if (!add) {
							target.removeClass('btn-success');
						} else {
							target.addClass('btn-success');
							var down = document.getElements('button[data-fabrik-thumb-formid=' + formid + '][data-fabrik-thumb-rowid=' + rowid + '][data-fabrik-thumb=down]');
							down.removeClass('btn-danger');
						}
					} else {
						var up = document.getElements('button[data-fabrik-thumb-formid=' + formid + '][data-fabrik-thumb-rowid=' + rowid + '][data-fabrik-thumb=up]');
						if (!add) {
							target.removeClass('btn-danger');
						} else {
							target.addClass('btn-danger');
							up.removeClass('btn-success');
						}
					}
				}
				else {
					e.stop();
					this.doNoAccess();
				}
			}.bind(this));

		},

		setUpBootstrapped: function () {
			this.col.each(function (td) {
				var row = td.getParent('.fabrik_row');

				if (row) {
					var rowid = row.id.replace('list_' + this.options.renderContext + '_row_', '');
					var up = td.getElement('button.thumb-up'),
						down = td.getElement('button.thumb-down');

					up.addEvent('click', function (e) {
						e.stop();
						if (this.options.canUse) {
							var add = up.hasClass('btn-success') ? false : true;
							this.doAjax(up, 'up', add);

							if (!add) {
								up.removeClass('btn-success');
							} else {
								up.addClass('btn-success');

								if (typeOf(down) !== 'null') {
									down.removeClass('btn-danger');
								}
							}
						}
						else {
							this.doNoAccess();
						}

					}.bind(this));

					if (typeOf(down) !== 'null') {
						down.addEvent('click', function (e) {
							e.stop();
							if (this.options.canUse) {
								var add = down.hasClass('btn-danger') ? false : true;
								this.doAjax(down, 'down', add);

								if (!add) {
									down.removeClass('btn-danger');
								} else {
									down.addClass('btn-danger');
									up.removeClass('btn-success');
								}
							}
							else {
								this.doNoAccess();
							}
						}.bind(this));
					}
				}
			}.bind(this));
		},

		doAjax: function (e, thumb, add) {
			// We shouldn't get here if they didn't have access, but doesn't hurt to check
			if (!this.options.canUse) {
				this.doNoAccess();
			}
			else {
				add = add ? true : false;
				var row = e.getParent();
				var rowid = e.get('data-fabrik-thumb-rowid');
				var count_thumb = document.id('count_thumb' + thumb + rowid);
				Fabrik.loader.start(row);
				this.thumb = thumb;

				var data = {
					'option'     : 'com_fabrik',
					'format'     : 'raw',
					'task'       : 'plugin.pluginAjax',
					'plugin'     : 'thumbs',
					'method'     : 'ajax_rate',
					'g'          : 'element',
					'element_id' : this.options.elid,
					'row_id'     : rowid,
					'elementname': this.options.elid,
					'userid'     : this.options.userid,
					'thumb'      : this.thumb,
					'listid'     : this.options.listid,
					'formid'     : this.options.formid,
					'add'        : add
				};

				if (this.options.voteType === 'comment') {
					data.special = 'comments_' + this.options.formid;
				}

				new Request({
					url       : '',
					'data'    : data,
					onComplete: function (r) {
						var count_thumbup = document.id('count_thumbup' + rowid);
						var count_thumbdown = document.id('count_thumbdown' + rowid);
						var thumbup = row.getElements('.thumbup');
						var thumbdown = row.getElements('.thumbdown');
						Fabrik.loader.stop(row);
						r = JSON.decode(r);

						if (r.error) {
							console.log(r.error);
						} else {
							if (Fabrik.bootstrapped) {
								row.getElement('button.thumb-up .thumb-count').set('text', r[0]);

								if (typeOf(row.getElement('button.thumb-down')) !== 'null') {
									row.getElement('button.thumb-down .thumb-count').set('text', r[1]);
								}
							} else {
								count_thumbup.set('html', r[0]);
								count_thumbdown.set('html', r[1]);
							}
						}
					}.bind(this)
				}).send();
			}
		},

		doNoAccess: function () {
			if (this.options.noAccessMsg !== '') {
				window.alert(this.options.noAccessMsg);
			}
		}
	});

	return FbThumbsList;
});