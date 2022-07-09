/**
 * Consent
 *
 * @copyright: Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
define(['jquery', 'fab/fabrik'], function (jQuery, Fabrik) {
	'use strict';
	var Zoom = new Class({

		options: {
			'renderOrder': ''
		},


		/**
		 * Initialize
		 * @param {object} options
		 */
		initialize: function (options) {
			if (options.editable) {
				return;
			}

			var self = this;
			this.options = jQuery.extend(this.options, options);
			this.form = Fabrik.getBlock('details_' + this.options.formid);
			this.watchButtons();
		},

		updateForm: function(json) {
			if (json.err === '') {
				jQuery('.zoomAttendingError').addClass('fabrikHide');
				if (this.options.attending) {
					jQuery('button.zoomAttending').parent().addClass('fabrikHide');
					jQuery('.zoomOptInNotConfirmed').parent().removeClass('fabrikHide');
					this.options.attending = false;
				}
				else {
					jQuery('button.zoomAttending').parent().removeClass('fabrikHide');
					jQuery('.zoomOptInNotConfirmed').parent().addClass('fabrikHide');
					this.options.attending = true;
				}
			}
			else {
				jQuery('.zoomAttendingError').html('<p>' + json.err + '</p>');
				jQuery('.zoomAttendingError').removeClass('fabrikHide');
			}
		},

		doConfirm: function (target) {
			jQuery('.zoomOptInNotConfirmed').addClass('fabrikHide');
			jQuery('.zoomOptIn').removeClass('fabrikHide');
		},

		doCancel: function (target) {
			jQuery('.zoomOptIn').addClass('fabrikHide');
			jQuery('.zoomOptInNotConfirmed').removeClass('fabrikHide');
		},

		doAttending: function (target) {
			var attend = target.get('data-attending'),
				self = this;

			Fabrik.loader.start(this.form.getBlock());

			jQuery.ajax({
				url     : Fabrik.liveSite + 'index.php',
				method  : 'post',
				dataType: 'json',
				'data'  : {
					'option'               : 'com_fabrik',
					'format'               : 'raw',
					'task'                 : 'plugin.pluginAjax',
					'plugin'               : 'zoom',
					'method'               : 'ajax_attending',
					'userId'               : this.options.userId,
					'thingId'				: this.options.thingId,
					'zoomId'				: this.options.zoomId,
					'attending'				: attend,
					'g'                    : 'form',
					'formid'               : this.options.formid,
					'renderOrder'          : this.options.renderOrder
				}

			}).always(function () {
				Fabrik.loader.stop(self.form.getBlock());
			}).fail(function (jqXHR, textStatus, errorThrown) {
				window.alert(textStatus);
			}).done(function (json) {
				self.updateForm(json);
			});

		},

		watchButtons: function () {
			var form = this.form.getForm();
			form.addEvent('click:relay(.zoomNotAttending)', function (e, target) {
				e.preventDefault();
				this.doConfirm(target);
			}.bind(this));
			form.addEvent('click:relay(.zoomOptInCancel)', function (e, target) {
				e.preventDefault();
				this.doCancel(target);
			}.bind(this));
			form.addEvent('click:relay(*[data-attending])', function (e, target) {
				e.preventDefault();
				this.doAttending(target);
			}.bind(this));
		}
	});

	return Zoom;
});