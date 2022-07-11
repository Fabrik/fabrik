/**
 * Captcha Element
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'fab/element'], function (jQuery, FbElement) {
    window.FbCaptcha = new Class({
        Extends   : FbElement,
        initialize: function (element, options) {
            if (options.method === 'invisible') {
                var self = this;
                window.fabrikCaptureLoaded = function() {
                    self.widgetId = grecaptcha.render(self.options.element, {
                        'sitekey': self.options.siteKey,
                        'size': 'invisible',
                        'callback': self.captureCompleted,
                    });
                };

                requirejs(['https://www.google.com/recaptcha/api.js?onload=fabrikCaptureLoaded&render=explicit']);
            }
            this.parent(element, options);
        },

        captureCompleted: function (response)
        {
            window.fabrikCaptchaSubmitCallBack(true);
            delete window.fabrikCaptchaSubmitCallBack;
        },

        /**
         * Called from FbFormSubmit
         *
         * @params   function  cb  Callback function to run when the element is in an acceptable state for the form processing to continue
         *
         * @return  void
         */
        onsubmit: function (cb) {
            if (typeof cb === 'undefined')
            {
                return;
            }

            if (this.options.method === 'invisible')
            {
                if (!grecaptcha.getResponse()) {
                    window.fabrikCaptchaSubmitCallBack = cb;
                    var response = grecaptcha.execute(this.widgetId);
                }
            }
            else {
                this.parent(cb);
            }
        }
    });

    return window.FbCaptcha;
});