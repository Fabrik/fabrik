/**
 * Field Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// Define variable outside of require js so that the form class can initialize it

function geolocateLoad() {
    if (document.body) {
        window.fireEvent('google.geolocate.loaded');
    } else {
        console.log('no body');
    }
}

// Wrap in require js to ensure we always load the same version of jQuery
// Multiple instances can be loaded an ajax pages are added and removed. However we always want
// to get the same version as plugins are only assigned to this jQuery instance
define(['jquery', 'fab/element', 'components/com_fabrik/libs/masked_input/jquery.maskedinput'],
    function (jQuery, FbElement, Mask) {

    window.FbField = new Class({
        Extends: FbElement,

        options: {
            use_input_mask         : false,
            input_mask_definitions : '',
            input_mask_autoclear   : false,
            geocomplete            : false,
            mapKey                 : false,
            language               : ''
        },

        initialize: function (element, options) {
            var definitions;
            this.setPlugin('fabrikfield');
            this.parent(element, options);
            /*
             * $$$ hugh - testing new masking feature, uses this jQuery widget:
             * http://digitalbush.com/projects/masked-input-plugin/
             */
            if (this.options.use_input_mask) {
                if (this.options.input_mask_definitions !== '') {
                    definitions = JSON.parse(this.options.input_mask_definitions);
                    jQuery.extend(jQuery.mask.definitions, definitions);
                }
                jQuery('#' + element).mask(this.options.input_mask, {autoclear: this.options.input_mask_autoclear});
            }
            if (this.options.geocomplete) {
                this.gcMade = false;
                this.loadFn = function () {
                    if (this.gcMade === false) {
                        var self = this;
                        jQuery('#' + this.element.id).geocomplete({}).bind(
                            'geocode:result',
                            function(event, result){
                                Fabrik.fireEvent('fabrik.element.field.geocode', [self, result]);
                            }
                        );
                        this.gcMade = true;
                    }
                }.bind(this);
                window.addEvent('google.geolocate.loaded', this.loadFn);
                Fabrik.loadGoogleMap(this.options.mapKey, 'geolocateLoad', this.options.language);
            }

            if (this.options.editable && this.options.scanQR) {
                this.qrBtn = document.id(element + '_qr_upload');
                this.qrBtn.addEvent('change', function (e) {
                    var node = e.target;
                    var reader = new FileReader();
                    var self = this;
                    reader.onload = function() {
                        node.value = "";
                        qrcode.callback = function(res) {
                            if(res instanceof Error) {
                                alert("No QR code found. Please make sure the QR code is within the camera's frame and try again.");
                            } else {
                                self.update(res);
                            }
                        }.bind(this);
                        qrcode.decode(reader.result);
                    };
                    reader.readAsDataURL(node.files[0]);
                }.bind(this));
            }
        },

        select: function () {
            var element = this.getElement();
            if (element) {
                this.getElement().select();
            }
        },

        focus: function () {
            var element = this.getElement();
            if (element) {
                this.getElement().focus();
            }
            this.parent();
        },

        cloned: function (c) {
            var element = this.getElement();
            if (this.options.use_input_mask) {
                if (element) {
                    if (this.options.input_mask_definitions !== '') {
                        var definitions = JSON.parse(this.options.input_mask_definitions);
                        $H(definitions).each(function (v, k) {
                            jQuery.mask.definitions[k] = v;
                        });
                    }
                    jQuery('#' + element.id).mask(this.options.input_mask, {autoclear: this.options.input_mask_autoclear});
                }
            }
            if (this.options.geocomplete) {
                if (element) {
                    var self = this;
                    jQuery('#' + this.element.id).geocomplete().bind(
                        'geocode:result',
                        function(event, result){
                            Fabrik.fireEvent('fabrik.element.field.geocode', [self, result]);
                        }
                    );
                }
            }
            this.parent(c);
        }

    });

    return window.FbField;
});