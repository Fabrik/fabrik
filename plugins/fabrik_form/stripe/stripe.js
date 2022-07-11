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
            'failedValidation': false,
            'stripeTokenId': '',
            'stripeTokenEmail': '',
            'stripeTokenOpts': '',
			'billingAddress': false,
			'couponElement': '',
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
			Fabrik.FabrikStripeForm = null;
			Fabrik.FabrikStripeFormSubmitting = false;

            if (this.options.productElement !== '')
            {
                this.productElement = self.form.formElements.get(this.options.productElement);
                var productElEvnt;

                if (this.productElement.hasSubElements()) {
                    productElEvnt = this.productElement.getChangeEvent();
                }
                else {
                    productElEvnt = this.productElement.getBlurEvent();
                }

                self.form.dispatchEvent(
                    '',
                    this.options.productElement,
                    productElEvnt,
                    function (e) {
                        self.getCost(e);
                    }
                );

                if (this.options.qtyElement !== '')
                {
                    this.qtyElement = self.form.formElements.get(this.options.qtyElement);
                    var qtyElEvnt;

                    if (this.productElement.hasSubElements()) {
                        qtyElEvnt = this.productElement.getChangeEvent();
                    }
                    else {
                        qtyElEvnt = this.productElement.getBlurEvent();
                    }

                    self.form.dispatchEvent(
                        '',
                        this.options.qtyElement,
                        qtyElEvnt,
                        function (e) {
                            self.getCost(e);
                        }
                    );

                    // if it's a field, watch for input events, like HTML 5 'number' controls, etc
                    if (this.qtyElement.plugin === 'fabrikfield') {
                        self.form.dispatchEvent(
                            '',
                            this.options.qtyElement,
                            'input',
                            function (e) {
                                self.getCost(e);
                            }
                        );
                    }
                }
                else
                {
                    this.qtyElement = false;
                }
            }
            else
			{
				this.productElement = false;
                this.qtyElement = false;
			}

            if (this.options.couponElement !== '')
            {
                this.couponElement = self.form.formElements.get(this.options.couponElement);
                var couponElEvnt = this.couponElement.getBlurEvent();

                self.form.dispatchEvent(
                    '',
                    this.options.couponElement,
                    couponElEvnt,
                    function (e) {
                        if (self.options.productElement !== '') {
                            self.getCost(e);
                        }
                        else {
                            self.getCoupon(e);
                        }
                    }
                );
            }
            else
            {
                this.couponElement = false;
            }

            if (this.options.totalElement !== '')
            {
                this.totalElement = self.form.formElements.get(this.options.totalElement);
            }
            else {
				this.totalElement = false;
			}

			if (this.options.useCheckout) {
			    if (!this.options.failedValidation) {
                    requirejs(['https://checkout.stripe.com/checkout.js?'], function (Stripe) {
                        self.handler = StripeCheckout.configure({
                            key: self.options.publicKey,
                            image: 'https://stripe.com/img/documentation/checkout/marketplace.png',
                            locale: 'auto',
                            currency: self.options.currencyCode,
                            token: function (token, opts) {
                                Fabrik.FabrikStripeForm.form.adopt(new Element('input', {
                                    'name': 'stripe_token_id',
                                    'value': token.id,
                                    'type': 'hidden'
                                }));
                                Fabrik.FabrikStripeForm.form.adopt(new Element('input', {
                                    'name': 'stripe_token_email',
                                    'value': token.email,
                                    'type': 'hidden'
                                }));
                                Fabrik.FabrikStripeForm.form.adopt(new Element('input', {
                                    'name': 'stripe_token_opts',
                                    'value': JSON.stringify(opts),
                                    'type': 'hidden'
                                }));
                                Fabrik.FabrikStripeForm.mockSubmit();
                            },
                            closed: function () {
                                Fabrik.FabrikStripeFormSubmitting = true;
                            }
                        });
                    });
                }

				Fabrik.addEvent('fabrik.form.submit.start', function (form, event, btn) {
				    if (!this.options.useCheckout) {
				        return;
                    }

					if (!this.options.ccOnFree && this.options.amount == 0)
					{
						return;
					}

					if (!this.options.failedValidation) {
                        if (
                            typeof Fabrik.FabrikStripeForm === 'undefined' ||
                            Fabrik.FabrikStripeFormSubmitting !== true ||
                            jQuery('input[name=stripe_token_id]').length === 0
                        ) {
                            Fabrik.FabrikStripeForm = form;
                            this.handler.open({
                                name: this.options.name,
                                description: this.options.item,
                                amount: this.options.amount,
                                zipCode: this.options.zipCode,
                                allowRememberMe: this.options.allowRememberMe,
                                email: this.options.email,
                                panelLabel: this.options.panelLabel,
                                billingAddress: this.options.billingAddress
                            });
                            event.preventDefault();
                            form.result = false;
                        }
                    }
					else {
                        this.form.form.adopt(new Element('input', {
                            'name': 'stripe_token_id',
                            'value': this.options.stripeTokenId,
                            'type': 'hidden'
                        }));
                        this.form.form.adopt(new Element('input', {
                            'name': 'stripe_token_email',
                            'value': this.options.stripeTokenEmail,
                            'type': 'hidden'
                        }));
                        this.form.form.adopt(new Element('input', {
                            'name': 'stripe_token_opts',
                            'value': this.options.stripeTokenOpts,
                            'type': 'hidden'
                        }));
                        form.result = true;
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
                    requirejs(['https://checkout.stripe.com/checkout.js?'], function (Stripe) {
                        self.handler = StripeCheckout.configure({
                            key: self.options.publicKey,
                            image: 'https://stripe.com/img/documentation/checkout/marketplace.png',
                            locale: 'auto',
                            currency: self.options.currencyCode,
                            token: function (token, opts) {
                                Fabrik.FabrikStripeForm.form.adopt(new Element('input', {
                                    'name': 'stripe_token_id',
                                    'value': token.id,
                                    'type': 'hidden'
                                }));
                                Fabrik.FabrikStripeForm.form.adopt(new Element('input', {
                                    'name': 'stripe_token_email',
                                    'value': token.email,
                                    'type': 'hidden'
                                }));
                                Fabrik.FabrikStripeForm.form.adopt(new Element('input', {
                                    'name': 'stripe_token_opts',
                                    'value': JSON.stringify(opts),
                                    'type': 'hidden'
                                }));
                                jQuery('.fabrikStripeLast4').text(Joomla.JText._('PLG_FORM_STRIPE_CUSTOMERS_UPDATE_CC_UPDATED'));
                            }
                        });
                        changeBtn.addEvent('click', function (e) {
                            e.preventDefault();
                            Fabrik.FabrikStripeForm = self.form;
                            self.handler.open({
                                name: self.options.name,
                                description: self.options.item,
                                zipCode: self.options.zipCode,
                                allowRememberMe: self.options.allowRememberMe,
                                email: self.options.email,
                                panelLabel: self.options.panelLabel,
                                billingAddress: self.options.billingAddress
                            });
                        }.bind(this));
                    });
				}
			}
		},

		getCoupon: function (e) {
            Fabrik.loader.start('form_' + this.options.formid, Joomla.JText._('PLG_FORM_STRIPE_CALCULATING'));

            var v = this.couponElement.getValue(),
                formid = this.options.formid,
                self = this;

            jQuery.ajax({
                url     : Fabrik.liveSite + 'index.php',
                method  : 'post',
                dataType: 'json',
                'data'  : {
                    'option'               : 'com_fabrik',
                    'format'               : 'raw',
                    'task'                 : 'plugin.pluginAjax',
                    'plugin'               : 'stripe',
                    'method'               : 'ajax_getCoupon',
					'amount'               : this.options.origAmount,
                    'g'                    : 'form',
                    'v'                    : v,
                    'formid'               : formid,
					'renderOrder'          : this.options.renderOrder
                }

            }).always(function () {
                Fabrik.loader.stop('form_' + self.options.formid);
            }).fail(function (jqXHR, textStatus, errorThrown) {
				window.alert(textStatus);
			}).done(function (json) {
				self.updateForm(json);
			});
        },

        getCost: function (e) {
			if (this.totalElement) {
                Fabrik.loader.start(this.options.totalElement, Joomla.JText._('PLG_FORM_STRIPE_CALCULATING'));
			}
            var productId = this.options.productElement !== '' ? this.productElement.getValue() : '',
                qty = this.options.qtyElement !== '' ? this.qtyElement.getValue() : '',
                coupon = this.options.couponElement !== '' ? this.couponElement.getValue() : '',
                formid = this.options.formid,
                self = this;

            jQuery.ajax({
                dataType: 'json',
                url     : Fabrik.liveSite + 'index.php',
                method  : 'post',
                'data'  : {
                    'option'               : 'com_fabrik',
                    'format'               : 'raw',
                    'task'                 : 'plugin.pluginAjax',
                    'plugin'               : 'stripe',
                    'method'               : 'ajax_getCost',
                    'amount'               : this.options.origAmount,
                    'g'                    : 'form',
                    'productId'            : productId,
					'qty'                  : qty,
					'coupon'               : coupon,
                    'formid'               : formid,
                    'renderOrder'          : this.options.renderOrder
                }

            }).always(function () {
                if (self.totalElement) {
                    Fabrik.loader.stop(self.options.totalElement);
                }
            }).fail(function (jqXHR, textStatus, errorThrown) {
				window.alert(textStatus);
			}).done(function (json) {
				self.updateForm(json);
			});
        },

		updateForm: function (json) {
			this.options.amount = json.stripe_amount;
			jQuery('.fabrikStripePrice').html(json.display_amount);
            jQuery('.fabrikStripeItem').html(json.product_name);
			jQuery('.fabrikStripeCouponText').html(json.msg);

			if (this.totalElement) {
				this.totalElement.update(json.display_amount);
			}
		}

	});

	return Stripe;
});