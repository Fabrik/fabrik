/**
 * Consent
 *
 * @copyright: Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
define(['jquery', 'fab/fabrik'], function (jQuery, Fabrik) {
	'use strict';
	var Consent = new Class({

		options: {
			'renderOrder': ''
		},


		/**
		 * Initialize
		 * @param {object} options
		 */
		initialize: function (options) {
			var self = this;
			this.options = jQuery.extend(this.options, options);
			this.form = Fabrik.getBlock('form_' + this.options.formid);

			Fabrik.addEvent('fabrik.form.submit.failed', function (form, json) {
				if (form === this.form) {
				    // show the appropriate message
				    if (typeOf(json.errors.consent_required) !== 'null') {
                        jQuery('.consentError.requireConsent').removeClass('fabrikHide');
                    }
                    else if (typeOf(json.errors.consent_remove) !== 'null') {
                        jQuery('.consentError.removeConsent').removeClass('fabrikHide');
                    }
                    this.form.showMainError(this.form.options.error);
                }
			}.bind(this));

            Fabrik.addEvent('fabrik.form.submitted', function (form, event, btn) {
            	if (form === this.form) {
                    jQuery('.consentError').addClass('fabrikHide');
                }
            }.bind(this));
		}
	});

	return Consent;
});