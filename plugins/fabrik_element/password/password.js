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

            this.pwRequirementsIndicator = this.getContainer().getElement('#pw-requirements-indicator');
            if (typeOf(this.pwRequirementsIndicator) === 'null') {
                console.log("Password requirements indicator not found in DOM!");
            }

            this.pwMinLen = Joomla.getOptions('passwordMinLength');
            this.pwMinInts = Joomla.getOptions('passwordMinIntegers');
            this.pwMinSymbols = Joomla.getOptions('passwordMinSymbols');
            this.pwMinUppercase = Joomla.getOptions('passwordMinUppercase');
            this.pwMinLowercase = Joomla.getOptions('passwordMinLowercase');

            this.pwRequirementMinLenText = Joomla.JText._('PLG_ELEMENT_PASSWORD_MIN_LENGTH') + ': ' + this.pwMinLen;
            this.pwRequirementMinIntsText = Joomla.JText._('PLG_ELEMENT_PASSWORD_MIN_INTEGERS') + ': ' + this.pwMinInts;
            this.pwRequirementMinSymbolsText = Joomla.JText._('PLG_ELEMENT_PASSWORD_MIN_SYMBOLS') + ': ' + this.pwMinSymbols + ' (' + Joomla.JText._('PLG_ELEMENT_PASSWORD_ONE_OF') + ': @$!%*#?&)';
            this.pwRequirementMinUppercaseText = Joomla.JText._('PLG_ELEMENT_PASSWORD_MIN_UPPERCASE') + ': ' + this.pwMinUppercase;
            this.pwRequirementMinLowercaseText = Joomla.JText._('PLG_ELEMENT_PASSWORD_MIN_LOWERCASE') + ': ' + this.pwMinLowercase;

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
            this.getConfirmationField().addEvent('keyup', function (e) {
                this.passwordChanged(e);
            }.bind(this));

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
            
            var pwd = this.element;
            var pwd_check = this.getConfirmationField();
            var html = '';
            
            /* Update the password requirements indicator */
            if (typeOf(this.pwRequirementsIndicator) !== 'null') 
            {
                let regexExpr, count = 0;

                // check if password length is sufficient
                if (pwd.value.length >= this.pwMinLen) {
                    html += '<span style="color: #10D23E;"><span class="icon-checkmark"> </span> ' + this.pwRequirementMinLenText + '</span><br/>';
                } else {
                    html += '<span style="color: #DB1717;"><span class="icon-cancel-2"> </span> ' + this.pwRequirementMinLenText + '</span><br/>';
                }

                // check if password contains enough digits
                count = 0;
                regexExpr = new RegExp("[0-9]", 'g');
                while(regexExpr.test(pwd.value)) count++;

                if (count >= this.pwMinInts) {
                    html += '<span style="color: #10D23E;"><span class="icon-checkmark"> </span> ' + this.pwRequirementMinIntsText + '</span><br/>';
                } else {
                    html += '<span style="color: #DB1717;"><span class="icon-cancel-2"> </span> ' + this.pwRequirementMinIntsText + '</span><br/>';
                }

                // check if password contains enough symbols
                count = 0;
                regexExpr = new RegExp("[@$!%*#?&]", 'g');
                while(regexExpr.test(pwd.value)) count++;
                
                if (count >= this.pwMinSymbols) {
                    html += '<span style="color: #10D23E;"><span class="icon-checkmark"> </span> ' + this.pwRequirementMinSymbolsText + '</span><br/>';
                } else {
                    html += '<span style="color: #DB1717;"><span class="icon-cancel-2"> </span> ' + this.pwRequirementMinSymbolsText + '</span><br/>';
                }

                // check if password contains enough upper case characters
                count = 0;
                regexExpr = new RegExp("[A-Z]", 'g');
                while(regexExpr.test(pwd.value)) count++;

                if (count >= this.pwMinUppercase) {
                    html += '<span style="color: #10D23E;"><span class="icon-checkmark"> </span> ' + this.pwRequirementMinUppercaseText + '</span><br/>';
                } else {
                    html += '<span style="color: #DB1717;"><span class="icon-cancel-2"> </span> ' + this.pwRequirementMinUppercaseText + '</span><br/>';
                }

                // check if password contains enough lower case characters
                count = 0;
                regexExpr = new RegExp("[a-z]", 'g');
                while(regexExpr.test(pwd.value)) count++;

                if (count >= this.pwMinLowercase) {
                    html += '<span style="color: #10D23E;"><span class="icon-checkmark"> </span> ' + this.pwRequirementMinLowercaseText + '</span><br/>';
                } else {
                    html += '<span style="color: #DB1717;"><span class="icon-cancel-2"> </span> ' + this.pwRequirementMinLowercaseText + '</span><br/>';
                }

                // check if passwords match
                if (pwd.value != '' && pwd_check.value == pwd.value) {
                    html += '<span style="color: #10D23E;"><span class="icon-checkmark"> </span> ' + Joomla.JText._('PLG_ELEMENT_PASSWORD_MATCH') + '</span>';
                } else {
                    html += '<span style="color: #DB1717;"><span class="icon-cancel-2"> </span> ' + Joomla.JText._('PLG_ELEMENT_PASSWORD_DONT_MATCH') + '</span>';
                }

                this.pwRequirementsIndicator.set('html', html);
            }
            
            /* Update the password strength meter */
            var strength = this.getContainer().getElement('.strength');
            if (typeOf(strength) === 'null') {
                return;
            }

            html = '';
            var strongRegex = new RegExp("^(?=.{6,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$", "g");
            var mediumRegex = new RegExp("^(?=.{6,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$", "g");
            var enoughRegex = new RegExp("(?=.{6,}).*", "g");
            
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