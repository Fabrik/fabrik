/**
 * Form Autofill
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
define(['jquery', 'fab/fabrik'], function (jQuery, Fabrik) {
	'use strict';
	var Stripe = new Class({

		options: {
			'publicKey' : '',
			'item' : '',
			'zipCode': true,
			'allowRememberMe': false,
			'email': '',
			'name': '',
			'panelLabel': '',
			'useCheckout': true,
			'billingAddress': false
		},


		/**
		 * Initialize
		 * @param {object} options
		 */
		initialize: function (options) {
			var self = this;
			this.options = jQuery.extend(this.options, options);
			this.form = Fabrik.getBlock('form_' + this.options.formid);
			Fabrik.FabrikStripeForm = null;
			Fabrik.FabrikStripeFormSubmitting = false;

			if (this.options.useCheckout) {
				this.handler = StripeCheckout.configure({
					key   : this.options.publicKey,
					image : 'https://stripe.com/img/documentation/checkout/marketplace.png',
					locale: 'auto',
					token : function (token, opts) {
						Fabrik.FabrikStripeForm.form.adopt(new Element('input', {
							'name' : 'stripe_token_id',
							'value': token.id,
							'type' : 'hidden'
						}));
						Fabrik.FabrikStripeForm.form.adopt(new Element('input', {
							'name' : 'stripe_token_email',
							'value': token.email,
							'type' : 'hidden'
						}));
						Fabrik.FabrikStripeForm.form.adopt(new Element('input', {
							'name' : 'stripe_token_opts',
							'value': JSON.stringify(opts),
							'type' : 'hidden'
						}));
						Fabrik.FabrikStripeForm.mockSubmit();
					},
					closed : function () {
						Fabrik.FabrikStripeFormSubmitting = true;
					}
				});

				Fabrik.addEvent('fabrik.form.submit.start', function (form, event, btn) {
					if (
						typeof Fabrik.FabrikStripeForm === 'undefined' ||
						Fabrik.FabrikStripeFormSubmitting !== true ||
						jQuery('input[name=stripe_token_id]').length === 0
					) {
						Fabrik.FabrikStripeForm = form;
						this.handler.open({
							name           : this.options.name,
							description    : this.options.item,
							amount         : this.options.amount,
							zipCode        : this.options.zipCode,
							allowRememberMe: this.options.allowRememberMe,
							email          : this.options.email,
							panelLabel     : this.options.panelLabel,
							billingAddress : this.options.billingAddress
						});
						event.preventDefault();
						form.result = false;
					}
				}.bind(this));

				window.addEventListener('popstate', function () {
					this.handler.close();
				});
			}
			else if (this.options.updateCheckout)
			{
				var changeBtn = this.form.form.getElement('.fabrikStripeChange');
				if (typeOf(changeBtn) !== 'null') {
					this.handler = StripeCheckout.configure({
						key   : this.options.publicKey,
						image : 'https://stripe.com/img/documentation/checkout/marketplace.png',
						locale: 'auto',
						token : function (token, opts) {
							Fabrik.FabrikStripeForm.form.adopt(new Element('input', {
								'name' : 'stripe_token_id',
								'value': token.id,
								'type' : 'hidden'
							}));
							Fabrik.FabrikStripeForm.form.adopt(new Element('input', {
								'name' : 'stripe_token_email',
								'value': token.email,
								'type' : 'hidden'
							}));
							Fabrik.FabrikStripeForm.form.adopt(new Element('input', {
								'name' : 'stripe_token_opts',
								'value': JSON.stringify(opts),
								'type' : 'hidden'
							}));
							jQuery('.fabrikStripeLast4').text(Joomla.JText._('PLG_FORM_STRIPE_CUSTOMERS_UPDATE_CC_UPDATED'));
						}
					});
					changeBtn.addEvent('click', function (e) {
						e.preventDefault();
						Fabrik.FabrikStripeForm = this.form;
						this.handler.open({
							name           : this.options.name,
							description    : this.options.item,
							zipCode        : this.options.zipCode,
							allowRememberMe: this.options.allowRememberMe,
							email          : this.options.email,
							panelLabel     : this.options.panelLabel,
							billingAddress : this.options.billingAddress
						});
					}.bind(this));
				}
			}
		}

	});

	return Stripe;
});