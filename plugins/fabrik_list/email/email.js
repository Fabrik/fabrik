/**
 * List Email
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
define(['jquery', 'fab/list-plugin', 'fab/fabrik'], function (jQuery, FbListPlugin, Fabrik) {
    var FbListEmail = new Class({

        Extends: FbListPlugin,

        initialize: function (options) {
            this.parent(options);
        },

        watchSubmit: function () {
            var form = jQuery('#emailtable');
            var self = this;
            form.submit(function (event) {
                if (typeof WFEditor !== 'undefined') {
                    WFEditor.getContent('message');
                }
                else if (typeof tinymce !== 'undefined') {
                    if (tinyMCE.activeEditor) {
                        tinyMCE.activeEditor.save();
                    }
                }
                var url = Fabrik.liveSite + '/index.php';
                if (self.options.additionalQS !== '') {
                    url += '?' + self.options.additionalQS;
                }
                Fabrik.loader.start(form);
                jQuery.ajax({
                    type  : 'POST', // define the type of HTTP verb we want to use (POST for our form)
                    url   : url, // the url where we want to POST
                    //data  : jQuery(this).serialize(), // our data object
                    data: new FormData(this),
                    encode: true,
                    processData: false,
                    contentType: false
                })
                    .done(function (data) {
                        form.html(data);
                        Fabrik.loader.stop(form);
                    });

                event.preventDefault();
            });
        },

        watchAttachments: function () {
            jQuery(document.body).on('click', '.addattachment', function (e) {
                e.preventDefault();
                var li = jQuery(this).closest('.attachment');
                li.clone().insertAfter(li);
            });

            jQuery(document.body).on('click', '.delattachment', function (e) {
                e.preventDefault();
                if (jQuery('.addattachment').length > 1) {
                   jQuery(this).closest('.attachment').remove();
                }
            });
        },

        /**
         * Watch the 2 select lists to add/remove addresses from the address book
         */
        watchAddEmail: function () {
            jQuery('#email_add').on('click', function (e) {
                e.preventDefault();
                jQuery('#email_to_selectfrom option:selected').each(function (x, opt) {
                    jQuery(opt).appendTo(jQuery('#list_email_to'));
                });
            });
            jQuery('#email_remove').on('click', function (e) {
                e.preventDefault();
                jQuery('#list_email_to option:selected').each(function (x, opt) {
                    jQuery(opt).appendTo(jQuery('#email_to_selectfrom'));
                });
            });
        },

        buttonAction: function () {
            var url = this.options.popupUrl;
            var self = this;
            this.listform.getElements('input[name^=ids]').each(function (id) {
                if (id.get('value') !== false && id.checked !== false) {
                    url += '&ids[]=' + id.get('value');
                }
            });

            var chxall = this.list.form.getElement('input[name=checkAll]');
            if (typeOf(chxall) !== 'null') {
                if (this.listform.getElement('input[name=checkAll]').checked) {
                    url += '&checkAll=1';
                }
                else {
                    url += '&checkAll=0';
                }
            }

            url += '&task=popupwin';
            var id = 'email-list-plugin';
            this.windowopts = {
                id             : id,
                title          : 'Email',
                loadMethod     : 'xhr',
                contentURL     : url,
                width          : 520,
                height         : 470,
                evalScripts    : true,
                minimizable    : false,
                collapsible    : true,
                onContentLoaded: function () {
                    self.watchSubmit();
                    self.watchAttachments();
                    self.watchAddEmail();
                    this.fitToContent(false);
                }
            };
            Fabrik.getWindow(this.windowopts);
        }

    });

    return FbListEmail;
});
