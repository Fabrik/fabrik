/**
 * Password Element
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'fab/element'], function (jQuery, FbElement) {
    window.FbPassword = new Class({
        Extends: FbElement,

        options: {
            progressbar: false
        },

        initialize: function (element, options) {
            this.parent(element, options);
            if (!this.options.editable) {
                return;
            }
            this.ini();
        },

        ini: function () {
            if (this.element) {
                this.element.addEvent('keyup', function (e) {
                    this.passwordChanged(e);
                }.bind(this));
            }
            if (this.options.ajax_validation === true) {
                this.getConfirmationField().addEvent('blur', function (e) {
                    this.callvalidation(e);
                }.bind(this));
            }

            if (this.getConfirmationField().get('value') === '') {
                this.getConfirmationField().value = this.element.value;
            }

            Fabrik.addEvent('fabrik.form.doelementfx', function(form, method, id, groupfx) {
                if (form === this.form && id === this.strElement)
                {
                    switch (method) {
                        case 'disable':
                            jQuery(this.getConfirmationField()).prop('disabled', true);
                            break;
                        case 'enable':
                            jQuery(this.getConfirmationField()).prop('disabled', false);
                            break;
                        case 'readonly':
                            jQuery(this.getConfirmationField()).prop('readonly', true);
                            break;
                        case 'notreadonly':
                            jQuery(this.getConfirmationField()).prop('readonly', false);
                            break;
                    }
                }
            }.bind(this));
        },

        callvalidation: function (e) {
            this.form.doElementValidation(e, false, '_check');
        },

        cloned: function (c) {
            console.log('cloned');
            this.parent(c);
            this.ini();
        },

        passwordChanged: function () {
            var strength = this.getContainer().getElement('.strength');
            if (typeOf(strength) === 'null') {
                return;
            }
            var strongRegex = new RegExp("^(?=.{6,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$", "g");
            var mediumRegex = new RegExp("^(?=.{6,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$", "g");
            var enoughRegex = new RegExp("(?=.{6,}).*", "g");
            var pwd = this.element;
            var html = '';
            if (!this.options.progressbar) {
                if (false === enoughRegex.test(pwd.value)) {
                    html = '<span>' + Joomla.JText._('PLG_ELEMENT_PASSWORD_MORE_CHARACTERS') + '</span>';
                } else if (strongRegex.test(pwd.value)) {
                    html = '<span style="color:green">' + Joomla.JText._('PLG_ELEMENT_PASSWORD_STRONG') + '</span>';
                } else if (mediumRegex.test(pwd.value)) {
                    html = '<span style="color:orange">' + Joomla.JText._('PLG_ELEMENT_PASSWORD_MEDIUM') + '</span>';
                } else {
                    html = '<span style="color:red">' + Joomla.JText._('PLG_ELEMENT_PASSWORD_WEAK') + '</span>';
                }
	            strength.set('html', html);
            } else {
                // Bootstrap progress bar
                var tipTitle = '', newBar;
                if (strongRegex.test(pwd.value)) {
	                tipTitle = Joomla.JText._('PLG_ELEMENT_PASSWORD_STRONG');
	                newBar = jQuery(Fabrik.jLayouts['fabrik-progress-bar-strong']);
                }
                else if (mediumRegex.test(pwd.value)) {
                    tipTitle = Joomla.JText._('PLG_ELEMENT_PASSWORD_MEDIUM');
	                newBar = jQuery(Fabrik.jLayouts['fabrik-progress-bar-medium']);
                }
	            else if (enoughRegex.test(pwd.value)) {
		            tipTitle = Joomla.JText._('PLG_ELEMENT_PASSWORD_WEAK');
		            newBar = jQuery(Fabrik.jLayouts['fabrik-progress-bar-weak']);
	            }
                else {
	                tipTitle = Joomla.JText._('PLG_ELEMENT_PASSWORD_MORE_CHARACTERS');
	                newBar = jQuery(Fabrik.jLayouts['fabrik-progress-bar-more']);

                }
                var options = {
                    title: tipTitle
                };
                jQuery(newBar).tooltip(options);
                jQuery(strength).replaceWith(newBar);
            }
        },

        getConfirmationField: function () {
            return this.getContainer().getElement('input[name*=check]');
        }
    });

    return  window.FbPassword;
});