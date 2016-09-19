/**
 * File Upload Element
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license: GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */


define(['jquery', 'fab/fileelement'], function (jQuery, FbFileElement) {
    window.FbFileUpload = new Class({
        Extends   : FbFileElement,
        options : {
            folderSelect: false,
            ajax_upload: false
        },
        initialize: function (element, options) {
            var self = this;
            this.setPlugin('fileupload');
            this.parent(element, options);
            this.container = jQuery(this.container);
            this.toppath = this.options.dir;
            if (this.options.folderSelect === '1' && this.options.editable === true) {
                this.ajaxFolder();
            }

            this.doBrowseEvent = null;
            this.watchBrowseButton();

            if (this.options.ajax_upload && this.options.editable !== false) {
                Fabrik.fireEvent('fabrik.fileupload.plupload.build.start', this);
                this.watchAjax();
                if (Object.keys(this.options.files).length !== 0) {
                    this.uploader.trigger('FilesAdded', this.options.files);
                    jQuery.each(this.options.files, function (key, file) {
                        var response = {
                                filepath  : file.path,
                                uri       : file.url,
                                showWidget: false
                            },
                            newBar = jQuery(Fabrik.jLayouts['fabrik-progress-bar-success'])[0],
                            bar = jQuery('#' + file.id).find('.bar')[0];
                        self.uploader.trigger('UploadProgress', file);
                        self.uploader.trigger('FileUploaded', file, {
                            response: JSON.encode(response)
                        });

                        jQuery(bar).replaceWith(newBar);
                    });
                }
                this.redraw();
            }

            this.doDeleteEvent = null;
            this.watchDeleteButton();
            this.watchTab();
        },

        /**
         * Reposition the hidden input field over the 'add' button. Called on initiate and if in a tab
         * and the tab is activated. Triggered from element.watchTab()
         */
        redraw: function () {
            var el = jQuery(this.element);
            if (this.options.ajax_upload) {
                var browseButton = jQuery('#' + el.prop('id') + '_browseButton'),
                    c = jQuery('#' + this.options.element + '_container'),
                    diff = browseButton.position().left - c.position().left;
                // $$$ hugh - working on some IE issues
                var file_element = c.closest('.fabrikElement').find('input[type=file]');
                if (file_element.length > 0) {
                    var fileContainer = file_element.parent();
                    fileContainer.css({
                        'width' : browseButton.width(),
                        'height': browseButton.height()
                    });
                    fileContainer.css('top', diff);
                }
            }
        },

        doBrowse: function (evt) {
            if (window.File && window.FileReader && window.FileList && window.Blob) {
                var reader, self = this,
                    files = evt.target.files,
                    f = files[0];

                // Only process image files.
                if (f.type.match('image.*')) {
                    reader = new FileReader();
                    // Closure to capture the file information.
                    reader.onload = (function (theFile) {
                        return function (e) {
                            var c = jQuery(self.getContainer()),
                                b = c.find('img');
                            b.attr('src', e.target.result);
                            var d = b.closest('.fabrikHide');
                            d.removeClass('fabrikHide');
                            var db = c.find('[data-file]');
                            db.addClass('fabrikHide');
                        };
                    }.bind(this))(f);
                    // Read in the image file as a data URL.
                    reader.readAsDataURL(f);
                }
                else if (f.type.match('video.*')) {
                    var c = jQuery(this.getContainer()),
                        video = c.find('video');
                    if (video.length > 0) {
                        video = this.makeVideoPreview();
                        video.appendTo(c);
                    }

                    reader = new window.FileReader();
                    var url;

                    reader = window.URL || window.webKitURL;

                    if (reader && reader.createObjectURL) {
                        url = reader.createObjectURL(f);
                        video.attr('src', url);
                        return;
                    }

                    if (!window.FileReader) {
                        console.log('Sorry, not so much');
                        return;
                    }

                    reader = new window.FileReader();
                    reader.onload = function (eo) {
                        video.attr('src', eo.target.result);
                    };
                    reader.readAsDataURL(f);
                }
            }
        },

        watchBrowseButton: function () {
            var el = jQuery(this.element);
            if (this.options.useWIP && !this.options.ajax_upload && this.options.editable !== false) {
                el.off('change', this.doBrowseEvent);
                this.doBrowseEvent = this.doBrowse.bind(this);
                el.on('change', this.doBrowseEvent);
            }
        },

        /**
         * Called from watchDeleteButton
         *
         * @param {Event} e
         */
        doDelete: function (e) {
            e.preventDefault();
            var c = jQuery(this.getContainer()),
                self = this,
                b = c.find('[data-file]');
            if (window.confirm(Joomla.JText._('PLG_ELEMENT_FILEUPLOAD_CONFIRM_SOFT_DELETE'))) {
                var joinPkVal = b.data('join-pk-val');
                new jQuery.ajax({
                    url : '',
                    data: {
                        'option'    : 'com_fabrik',
                        'format'    : 'raw',
                        'task'      : 'plugin.pluginAjax',
                        'plugin'    : 'fileupload',
                        'method'    : 'ajax_clearFileReference',
                        'element_id': this.options.id,
                        'formid'    : this.form.id,
                        'rowid'     : this.form.options.rowid,
                        'joinPkVal' : joinPkVal
                    }
                }).done(function () {
                    Fabrik.trigger('fabrik.fileupload.clearfileref.complete', self);
                });

                if (window.confirm(Joomla.JText._('PLG_ELEMENT_FILEUPLOAD_CONFIRM_HARD_DELETE'))) {
                    this.makeDeletedImageField(this.groupid, b.data('file')).appendTo(c);
                    Fabrik.fireEvent('fabrik.fileupload.delete.complete', this);
                }

                b.remove();
	            var el = jQuery(this.element);
	            var i = el.closest('.fabrikElement').find('img');
	            i.attr('src', this.options.defaultImage !== '' ? Fabrik.liveSite + this.options.defaultImage : '');
            }
        },

        /**
         * Single file uploads can allow the user to delete the reference and/or file
         */
        watchDeleteButton: function () {
            var c = jQuery(this.getContainer()),
                b = c.find('[data-file]');
            b.off('click', this.doDeleteEvent);
            this.doDeleteEvent = this.doDelete.bind(this);
            b.on('click', this.doDeleteEvent);
        },

        /**
         * Sets the element key used in Fabrik.blocks.form_X.formElements overwritten by dbjoin rendered as checkbox
         *
         * @since 3.0.7
         *
         * @return string
         */
        getFormElementsKey: function (elId) {
            this.baseElementId = elId;
            if (this.options.ajax_upload && this.options.ajax_max > 1) {
                return this.options.listName + '___' + this.options.elementShortName;
            } else {
                return this.parent(elId);
            }
        },

        /**
         * When in ajax form, on submit the list will call this, so we can remove the submit event if we dont do that, upon a second form submission the
         * original submitEvent is used causing a js error as it still references the files uploaded in the first form
         */
        removeCustomEvents: function () {
            // Fabrik.removeEvent('fabrik.form.submit.start', this.submitEvent);
        },

        cloned: function (c) {
            var el = jQuery(this.element);
            // replaced cloned image with default image
            if (el.closest('.fabrikElement').length === 0) {
                return;
            }
            var i = el.closest('.fabrikElement').find('img');
            i.attr('src', this.options.defaultImage !== '' ? Fabrik.liveSite + this.options.defaultImage : '');
            jQuery(this.getContainer()).find('[data-file]').remove();
            this.watchBrowseButton();
            this.parent(c);
        },

        decloned: function (groupid) {
            var i = jQuery('#form_' + this.form.id).find('input[name=fabrik_deletedimages[' + groupid + ']');
            if (i.length > 0) {
                this.makeDeletedImageField(groupid, this.options.value).inject(this.form.form);
            }
        },

        /**
         * Create a hidden input which will tell fabrik, upon form submission, to delete the file
         *
         * @param {int} groupId group id
         * @param {string} value file to delete
         *
         * @return Element DOM Node - hidden input
         */
        makeDeletedImageField: function (groupId, value) {
            return jQuery(document.createElement('input')).attr({
                'type' : 'hidden',
                'name' : 'fabrik_fileupload_deletedfile[' + groupId + '][]',
                'value': value
            });
        },

        makeVideoPreview: function () {
            var el = jQuery(this.element);
            return jQuery(document.createElement('video')).attr({
                'id'      : el.prop('id') + '_video_preview',
                'controls': true
            });
        },

        update: function (val) {
            if (this.element) {
                var el = jQuery(this.element);
                if (val === '') {
                    if (this.options.ajax_upload) {
                        this.uploader.files = [];
                        el.parent().find('[id$=_dropList] tr').remove();
                    } else {
                        el.val('');
                    }
                } else {
                    el.find('img').prop('src', val);
                }
            }
        },

        addDropArea: function () {
            if (!Fabrik.bootstraped) {
                return;
            }
            var dropTxt = this.container.find('tr.plupload_droptext'), tr;
            if (dropTxt.length > 0) {
                dropTxt.show();
            } else {
                tr = jQuery(document.createElementget('tr')).addClass('plupload_droptext').html('<td colspan="4"><i class="icon-move"></i> ' + Joomla.JText
                        ._('PLG_ELEMENT_FILEUPLOAD_DRAG_FILES_HERE') + ' </td>');
                this.container.find('tbody').append(tr);
            }
            this.container.find('thead').hide();
        },

        removeDropArea: function () {
            this.container.find('tr.plupload_droptext').hide();
        },

        watchAjax: function () {
            if (this.options.editable === false) {
                return;
            }
            var a, self = this,
                elementId = jQuery(this.element).prop('id'),
                el = jQuery(this.getElement());
            if (el.length === 0) {
                return;
            }
            var c = el.closest('.fabrikSubElementContainer');
            this.container = c;

            if (this.options.canvasSupport !== false) {
                this.widget = new ImageWidget(this.options.modalId, {

                    'imagedim': {
                        x: 200,
                        y: 200,
                        w: this.options.winWidth,
                        h: this.options.winHeight
                    },

                    'cropdim': {
                        w: this.options.cropwidth,
                        h: this.options.cropheight,
                        x: this.options.winWidth / 2,
                        y: this.options.winHeight / 2
                    },
                    crop     : this.options.crop,
                    modalId  : this.options.modalId,
                    quality  : this.options.quality
                });
            }
            this.pluploadContainer = c.find('.plupload_container');
            this.pluploadFallback = c.find('.plupload_fallback');
            this.droplist = c.find('.plupload_filelist');

            var plupopts = {
                runtimes           : this.options.ajax_runtime,
                browse_button      : elementId + '_browseButton',
                container          : elementId + '_container',
                drop_element       : elementId + '_dropList_container',
                url                : 'index.php?option=com_fabrik&format=raw&task=plugin.pluginAjax&plugin=fileupload&method=ajax_upload&element_id=' + this.options.elid,
                max_file_size      : this.options.max_file_size + 'kb',
                unique_names       : false,
                flash_swf_url      : this.options.ajax_flash_path,
                silverlight_xap_url: this.options.ajax_silverlight_path,
                chunk_size         : this.options.ajax_chunk_size + 'kb',
                dragdrop           : true,
                multipart          : true,
                filters            : this.options.filters,
                page_url           : this.options.page_url
            };
            this.uploader = new plupload.Uploader(plupopts);

            // (1) INIT ACTIONS
            this.uploader.bind('Init', function (up, params) {
                // FORCEFULLY NUKE GRACEFUL DEGRADING FALLBACK ON INIT
                self.pluploadFallback.remove();
                self.pluploadContainer.removeClass('fabrikHide');

                if (up.features.dragdrop && up.settings.dragdrop) {
                    self.addDropArea();
                }

            });

            /*
             */
            this.uploader.bind('FilesRemoved', function (up, files) {
            });

            // (2) ON FILES ADDED ACTION
            this.uploader.bind('FilesAdded', function (up, files) {
                self.removeDropArea();
                var rElement = Fabrik.bootstrapped ? 'tr' : 'li', count;
                self.lastAddedFiles = files;
                if (Fabrik.bootstrapped) {
                    self.container.find('thead').css('display', '');
                }
                count = self.droplist.find(rElement).length;
                jQuery.each(files, function (key, file) {
                    //files.each(function (file, idx) {
                    if (file.size > self.options.max_file_size * 1000) {
                        window.alert(Joomla.JText._('PLG_ELEMENT_FILEUPLOAD_FILE_TOO_LARGE_SHORT'));
                    } else {
                        if (count >= self.options.ajax_max) {
                            window.alert(Joomla.JText._('PLG_ELEMENT_FILEUPLOAD_MAX_UPLOAD_REACHED'));
                        } else {
                            count++;
                            var a, title, innerLi;
                            if (self.isImage(file)) {
                                a = self.editImgButton();
                                if (self.options.crop) {
                                    a.html(self.options.resizeButton);
                                } else {
                                    a.html(self.options.previewButton);
                                }
                                title = jQuery(document.createElement('span')).text(file.name);
                            } else {
                                a = jQuery(document.createElement('span'));
                                title = jQuery(document.createElement('a')).attr({
                                    'href': file.url
                                }).text(file.name);
                            }

                            innerLi = self.imageCells(file, title, a);

                            self.droplist.append(jQuery(document.createElement(rElement)).attr({
                                id     : file.id,
                                'class': 'plupload_delete'
                            }).append(innerLi));
                        }
                    }
                });

                // Automatically start the upload - need delay to ensure up.files is populated
                setTimeout(function () {
                    up.start();
                }, 100);
            });

            // (3) ON FILE UPLOAD PROGRESS ACTION
            this.uploader.bind('UploadProgress', function (up, file) {
                var f = jQuery('#' + file.id);
                if (f.length > 0) {
                    if (Fabrik.bootstrapped) {
                        var bar = f.find('.plupload_file_status .bar');
                        bar.css('width', file.percent + '%');
                        if (file.percent === 100) {
                            var newBar = jQuery(Fabrik.jLayouts['fabrik-progress-bar-success']);
                            bar.replaceWith(newBar);
                        }
                    } else {
                        f.find('.plupload_file_status').text(file.percent + '%');
                    }
                }
            });

            this.uploader.bind('Error', function (up, err) {
                self.lastAddedFiles.each(function (file) {
                    var row = jQuery('#' + file.id);
                    if (row.length > 0) {
                        row.remove();
                        window.alert(err.message);
                    }
                    self.addDropArea();
                });
            });

            this.uploader.bind('ChunkUploaded', function (up, file, response) {
                response = JSON.parse(response.response);
                if (typeof(response) === 'object') {
                    if (response.error) {
                        fconsole(response.error.message);
                    }
                }
            });

            this.uploader.bind('FileUploaded', function (up, file, response) {
                var name, showWidget, f, resizeButton, idValue,
                    f = jQuery('#' + file.id)
                response = JSON.parse(response.response);
                if (response.error) {
                    window.alert(response.error);
                    f.remove();
                    return;
                }

                if (f.length === 0) {
                    fconsole('Filuploaded didnt find: ' + file.id);
                    return;
                }
                resizeButton = f.find('.plupload_resize a');
                resizeButton.show();
                resizeButton.attr({
                    href: response.uri,
                    id  : 'resizebutton_' + file.id
                });

                resizeButton.data('filepath', response.filepath);

                if (self.widget) {
                    showWidget = response.showWidget === false ? false : true;
                    self.widget.setImage(response.uri, response.filepath, file.params, showWidget);
                }

                if (self.options.inRepeatGroup) {
                    name = self.options.elementName.replace(/\[\d*\]/, '[' + self.getRepeatNum() + ']');
                } else {
                    name = self.options.elementName;
                }
                // Stores the cropparams which we need to reload the crop widget in the correct state (rotation, zoom, etc)
                jQuery(document.createElement('input')).attr({
                    'type' : 'hidden',
                    name   : name + '[crop][' + response.filepath + ']',
                    'id'   : 'coords_' + file.id,
                    'value': JSON.encode(file.params)
                }).insertAfter(self.pluploadContainer);


                // Stores the actual crop image data retrieved from the canvas
                jQuery(document.createElement('input')).attr({
                    type: 'hidden',
                    name: name + '[cropdata][' + response.filepath + ']',
                    'id': 'data_' + file.id
                }).insertAfter(self.pluploadContainer);

                // Stores the image id if > 1 fileupload
                idValue = [file.recordid, '0'].pick();
                jQuery(document.createElement('input')).attr({
                    'type' : 'hidden',
                    name   : name + '[id][' + response.filepath + ']',
                    'id'   : 'id_' + file.id,
                    'value': idValue
                }).insertAfter(self.pluploadContainer);

                f.removeClass('plupload_file_action').addClass('plupload_done');

                self.isSubmitDone();
            });

            // (5) KICK-START PLUPLOAD
            this.uploader.init();
        },

        /**
         * Create an array of the dom elements to inject into a row representing an uploaded file
         *
         * @return {array}
         */
        imageCells: function (file, title, a) {
            var del = this.deleteImgButton(), filename, status, progress, icon;
            if (Fabrik.bootstrapped) {
                icon = jQuery(document.createElement('td')).addClass('span1 plupload_resize').append(a);
                progress = Fabrik.jLayouts['fabrik-progress-bar'];
                status = jQuery(document.createElement('td')).addClass('span5 plupload_file_status').html(progress);
                filename = jQuery(document.createElement('td')).addClass('span6 plupload_file_name').append(title);

                return [filename, icon, status, del];
            } else {
                filename = new Element('div', {
                    'class': 'plupload_file_name'
                }).adopt([title, new Element('div', {
                    'class': 'plupload_resize',
                    style  : 'display:none'
                }).adopt(a)]);
                status = new Element('div', {
                    'class': 'plupload_file_status'
                }).set('text', '0%');
                var size = new Element('div', {
                    'class': 'plupload_file_size'
                }).set('text', file.size);

                return [filename, del, status, size, new Element('div', {
                    'class': 'plupload_clearer'
                })];
            }
        },

        /**
         * Create edit image button
         *
         * @return {jQuery}
         */
        editImgButton: function () {
            var self = this;
            if (Fabrik.bootstrapped) {
                return jQuery(document.createElement('a')).addClass('editImage').attr({
                    'href': '#',
                    alt   : Joomla.JText._('PLG_ELEMENT_FILEUPLOAD_RESIZE')
                }).css({
                    'display': 'none'
                }).on('click', function (e) {
                    e.preventDefault();
                    //var a = e.target.getParent();
                    self.pluploadResize(jQuery(this));
                });

            } else {
                return new Element('a', {
                    'href': '#',
                    alt   : Joomla.JText._('PLG_ELEMENT_FILEUPLOAD_RESIZE'),
                    events: {
                        'click': function (e) {
                            e.stop();
                            var a = e.target.getParent();
                            this.pluploadResize(jQuery(a));
                        }.bind(this)
                    }
                });
            }
        },

        /**
         * Create delete image button
         *
         * @return {jQuery}
         */
        deleteImgButton: function () {
            if (Fabrik.bootstrapped) {

                var icon = Fabrik.jLayouts['fabrik-icon-delete'],
                    self = this;
                return jQuery(document.createElement('td')).addClass('span1 plupload_file_action').append(
                    jQuery(document.createElement('a'))
                        .html(icon)
                        .attr({
                            'href' : '#',
                            'class': 'icon-delete'
                        })
                        .on('click', function (e) {
                            e.stopPropagation();
                            self.pluploadRemoveFile(e);
                        })
                );

            } else {
                return new Element('div', {
                    'class': 'plupload_file_action'
                }).adopt(new Element('a', {
                    'href' : '#',
                    'style': 'display:block',
                    events : {
                        'click': function (e) {
                            this.pluploadRemoveFile(e);
                        }.bind(this)
                    }
                }));
            }
        },

        /**
         * Test if the plupload file object contains an image.
         * @param {object} file
         * @returns {*}
         */
        isImage: function (file) {
            if (file.type !== undefined) {
                return file.type === 'image';
            }
            var ext = file.name.split('.').pop().toLowerCase();
            return ['jpg', 'jpeg', 'png', 'gif'].contains(ext);
        },

        pluploadRemoveFile: function (e) {
            e.stopPropagation();
            if (!window.confirm(Joomla.JText._('PLG_ELEMENT_FILEUPLOAD_CONFIRM_HARD_DELETE'))) {
                return;
            }

            var id = jQuery(e.target).closest('tr').prop('id').split('_').pop();// alreadyuploaded_8_13
            // $$$ hugh - removed ' span' from the find(), as this blows up on some templates
            var f = jQuery(e.target).closest('tr').find('.plupload_file_name').text();

            // Get a list of all of the uploaders files except the one to be deleted
            var newFiles = [];
            this.uploader.files.each(function (f) {
                if (f.id !== id) {
                    newFiles.push(f);
                }
            });

            // Update the uploader's files with the new list.
            this.uploader.files = newFiles;

            // Send a request to delete the file from the server.
            jQuery.ajax({
                url : '',
                data: {
                    'option'       : 'com_fabrik',
                    'format'       : 'raw',
                    'task'         : 'plugin.pluginAjax',
                    'plugin'       : 'fileupload',
                    'method'       : 'ajax_deleteFile',
                    'element_id'   : this.options.id,
                    'file'         : f,
                    'recordid'     : id,
                    'repeatCounter': this.options.repeatCounter
                }
            });
            var li = e.target.closest('.plupload_delete');
            li.remove();

            // Remove hidden fields as well
            jQuery('#id_alreadyuploaded_' + this.options.id + '_' + id).remove();
            jQuery('#coords_alreadyuploaded_' + this.options.id + '_' + id).remove();

            if (jQuery(this.getContainer()).find('table tbody tr.plupload_delete').length === 0) {
                this.addDropArea();
            }
        },

        /**
         *
         * @param {jQuery} a
         */
        pluploadResize: function (a) {
            if (this.widget) {
                this.widget.setImage(a.attr('href'), a.data('filepath'), {}, true);
            }
        },

        /**
         * Once the upload fires a FileUploaded bound function we test if all images for this element have been
         * uploaded If they have then we save the
         * crop widget state and fire the callback - which is handled by FbFormSubmit()
         */
        isSubmitDone: function () {
            if (this.allUploaded() && typeof (this.submitCallBack) === 'function') {
                this.saveWidgetState();
                this.submitCallBack(true);
                delete this.submitCallBack;
            }
        },

        /**
         * Called from FbFormSubmit.submit() handles testing. If not yet uploaded, triggers the
         * upload and defers the callback until the upload is
         * complete. If complete then saves widget state and calls parent onsubmit().
         */
        onsubmit: function (cb) {
            this.submitCallBack = cb;
            if (!this.allUploaded()) {
                this.uploader.start();
            } else {
                this.saveWidgetState();
                this.parent(cb);
            }
        },

        /**
         * Save the crop widget state as a json object
         */
        saveWidgetState: function () {
            if (this.widget !== undefined) {
                jQuery.each(this.widget.images, function (key, image) {
                    key = key.split('\\').pop();
                    var f = jQuery('input[name*="' + key + '"]').filter(function (i, fld) {
                        return fld.name.contains('[crop]');
                    });
                    f = f.last();

                    // $$$ rob - seems reloading ajax fileupload element in ajax form (e.g. from db join add record)
                    // is producing odd effects where old fileupload object contains info to previously uploaded image?
                    if (f.length > 0) {

                        // Avoid circular reference in chrome when saving in ajax form
                        var i = image.img;
                        delete (image.img);
                        f.val(JSON.encode(image));
                        image.img = i;
                    }
                });
            }
        },

        allUploaded: function () {
            var uploaded = true;
            if (this.uploader) {
                this.uploader.files.each(function (file) {
                    if (file.loaded === 0) {
                        uploaded = false;
                    }
                });
            }
            return uploaded;
        }
    });

    var ImageWidget = new Class({

        initialize: function (modalId, opts) {
            this.modalId = modalId;

            // When element is in modal window it renders fine the first time. But the second time
            // the original window is still there - so we end up with 2 dom structures and one window object.
            // To get round this set the first window to be destroyed and close it.
            if (Fabrik.Windows[this.modalId]) {
                Fabrik.Windows[this.modalId].options.destroy = true;
                Fabrik.Windows[this.modalId].close();
            }

            this.imageDefault = {
                'rotation': 0,
                'scale'   : 100,
                'imagedim': {
                    x: 200,
                    y: 200,
                    w: 400,
                    h: 400
                },
                'cropdim' : {
                    x: 75,
                    y: 25,
                    w: 150,
                    h: 50
                }
            };

            jQuery.extend(this.imageDefault, opts);

            this.windowopts = {
                'id'             : this.modalId,
                'type'           : 'modal',
                loadMethod       : 'html',
                width            : parseInt(this.imageDefault.imagedim.w, 10) + 40,
                height           : parseInt(this.imageDefault.imagedim.h, 10) + 170,
                storeOnClose     : true,
                createShowOverLay: false,
                crop             : opts.crop,
                destroy          : false,
                modalId          : opts.modalId,
                quality          : opts.quality,
                onClose          : function () {
                    this.storeActiveImageData();
                }.bind(this),
                onContentLoaded  : function () {
                    this.center();
                },
                onOpen           : function () {
                    this.center();
                }
            };
            this.windowopts.title = opts.crop ? Joomla.JText._('PLG_ELEMENT_FILEUPLOAD_CROP_AND_SCALE') : Joomla.JText
                ._('PLG_ELEMENT_FILEUPLOAD_PREVIEW');
            this.showWin();

            this.canvas = jQuery(this.window).find('canvas')[0];

            this.images = {};
            this.CANVAS = new FbCanvas({
                canvasElement: this.canvas,
                enableMouse  : true,
                cacheCtxPos  : false
            });

            this.CANVAS.layers.add(new Layer({
                id: 'bg-layer'
            }));
            this.CANVAS.layers.add(new Layer({
                id: 'image-layer'
            }));
            if (opts.crop) {
                this.CANVAS.layers.add(new Layer({
                    id: 'overlay-layer'
                }));
                this.CANVAS.layers.add(new Layer({
                    id: 'crop-layer'
                }));
            }
            var bg = new CanvasItem({
                id    : 'bg',
                scale : 1,
                events: {
                    onDraw: function (ctx) {
                        if (ctx === undefined) {
                            ctx = this.CANVAS.ctx;
                        }
                        ctx.fillStyle = '#DFDFDF';
                        ctx.fillRect(0, 0, this.imageDefault.imagedim.w / this.scale, this.imageDefault.imagedim.h / this.scale);
                    }.bind(this)
                }
            });

            this.CANVAS.layers.get('bg-layer').add(bg);
            if (opts.crop) {
                this.overlay = new CanvasItem({
                    id    : 'overlay',
                    events: {
                        onDraw: function (ctx) {
                            if (ctx === undefined) {
                                ctx = this.CANVAS.ctx;
                            }
                            this.withinCrop = true;
                            if (this.withinCrop) {
                                var top = {
                                    x: 0,
                                    y: 0
                                };
                                var bottom = {
                                    x: this.imageDefault.imagedim.w,
                                    y: this.imageDefault.imagedim.h
                                };
                                ctx.fillStyle = 'rgba(0, 0, 0, 0.3)';
                                var cropper = this.cropperCanvas;
                                ctx.fillRect(top.x, top.y, bottom.x, cropper.y - (cropper.h / 2));// top
                                ctx.fillRect(top.x - (cropper.w / 2), top.y + cropper.y - (cropper.h / 2), top.x + cropper.x, cropper.h);// left
                                ctx.fillRect(top.x + cropper.x + cropper.w - (cropper.w / 2), top.y + cropper.y - (cropper.h / 2), bottom.x, cropper.h);// right
                                ctx.fillRect(top.x, top.y + (cropper.y + cropper.h) - (cropper.h / 2), bottom.x, bottom.y);// bottom
                            }
                        }.bind(this)
                    }
                });

                this.CANVAS.layers.get('overlay-layer').add(this.overlay);
            }

            this.imgCanvas = this.makeImgCanvas();

            this.CANVAS.layers.get('image-layer').add(this.imgCanvas);

            this.cropperCanvas = this.makeCropperCanvas();
            if (opts.crop) {
                // add an item
                this.CANVAS.layers.get('crop-layer').add(this.cropperCanvas);
            }
            this.makeThread();
            this.watchZoom();
            this.watchRotate();
            this.watchClose();
            this.win.close();
        },

        /**
         * Add or make active an image in the editor
         *
         * @param {string} uri Image URI
         * @param {string} filepath Path to file
         * @param {object} params Initial parameters
         * @param {Boolean} showWin
         */
        setImage: function (uri, filepath, params, showWin) {
            showWin = showWin ? showWin : false;
            this.activeFilePath = filepath;
            if (!this.images.hasOwnProperty(filepath)) {

                // Needed to ensure they are available in onLoad
                var tmpParams = params;

                // New image
                var img = Asset.image(uri, {
                    onLoad: function () {

                        var params = this.storeImageDimensions(filepath, jQuery(img), tmpParams);
                        this.img = params.img;
                        this.setInterfaceDimensions(params);
                        this.showWin();
                        this.storeActiveImageData(filepath);
                        if (!showWin) {
                            this.win.close();
                        }
                    }.bind(this)
                });
            } else {

                // Previously set up image
                params = this.images[filepath];
                this.img = params.img;
                this.setInterfaceDimensions(params);
                if (showWin) {
                    this.showWin();
                }
            }
        },

        /**
         * Set rotate, scale, image and crop values for a given image
         *
         * @param object params Image parameters
         */
        setInterfaceDimensions: function (params) {
            if (this.scaleSlide) {
                this.scaleSlide.set(params.scale);
            }
            if (this.rotateSlide) {
                this.rotateSlide.set(params.rotation);
            }

            if (this.cropperCanvas && params.cropdim) {
                this.cropperCanvas.x = params.cropdim.x;
                this.cropperCanvas.y = params.cropdim.y;
                this.cropperCanvas.w = params.cropdim.w;
                this.cropperCanvas.h = params.cropdim.h;
            }
            this.imgCanvas.w = params.mainimagedim.w;
            this.imgCanvas.h = params.mainimagedim.h;
            this.imgCanvas.x = params.imagedim !== undefined ? params.imagedim.x : 0;
            this.imgCanvas.y = params.imagedim !== undefined ? params.imagedim.y : 0;
        },

        /**
         * One time call to store initial image crop info in this.images
         *
         * @param {string} filepath Path to image
         * @param {jQuery} img Image - just created
         * @param {object} params object Image parameters
         *
         * @return object Update image parameters
         */

        storeImageDimensions: function (filepath, img, params) {
            // .hide() not working in UIKit
            img.appendTo(document.body).css({'display': 'none'});
            params = params ? params : new CloneObject(this.imageDefault, true, []);
            var s = img[0].getDimensions(true);
            if (!params.imagedim) {
                params.mainimagedim = {};
            } else {
                params.mainimagedim = params.imagedim;
            }
            params.mainimagedim.w = s.width;
            params.mainimagedim.h = s.height;
            params.img = img[0];
            this.images[filepath] = params;

            return params;
        },

        makeImgCanvas: function () {
            var parent = this;
            return new CanvasItem({
                id         : 'imgtocrop',
                w          : this.imageDefault.imagedim.w,
                h          : this.imageDefault.imagedim.h,
                x          : 200,
                y          : 200,
                interactive: true,
                rotation   : 0,
                scale      : 1,
                offset     : [0, 0],
                events     : {
                    onMousemove: function (x, y) {
                        if (this.dragging) {
                            var w = this.w * this.scale;
                            var h = this.h * this.scale;
                            this.x = x - this.offset[0] + w * 0.5;
                            this.y = y - this.offset[1] + h * 0.5;
                        }
                    },
                    onDraw     : function (ctx) {
                        ctx = parent.CANVAS.ctx;
                        if (parent.img === undefined) {
                            // console.log('no parent img', parent);
                            return;
                        }

                        var w = this.w * this.scale;
                        var h = this.h * this.scale;
                        var x = this.x - w * 0.5;
                        var y = this.y - h * 0.5;

                        // standard Canvas rotation operation
                        ctx.save();
                        ctx.translate(this.x, this.y);
                        ctx.rotate(this.rotation * Math.PI / 180);

                        this.hover ? ctx.strokeStyle = '#f00' : ctx.strokeStyle = '#000';
                        ctx.strokeRect(w * -0.5, h * -0.5, w, h);
                        if (parent.img !== undefined) {
                            try {
                                ctx.drawImage(parent.img, w * -0.5, h * -0.5, w, h);
                            } catch (err) {
                                // only show this for debugging as if we upload a pdf then we get shown lots of these errors.
                                // fconsole(err, parent.img, w * -0.5, h * -0.5, w, h);
                            }
                        }
                        ctx.restore();
                        if (parent.img !== undefined && parent.images.hasOwnProperty(parent.activeFilePath)) {
                            parent.images[parent.activeFilePath].imagedim = {
                                x: this.x,
                                y: this.y,
                                w: w,
                                h: h
                            };

                        }
                        this.setDims(x, y, w, h);
                    },

                    onMousedown: function (x, y) {
                        parent.CANVAS.setDrag(this);
                        this.offset = [x - this.dims[0], y - this.dims[1]];
                        this.dragging = true;
                    },

                    onMouseup: function () {
                        parent.CANVAS.clearDrag();
                        this.dragging = false;
                    },

                    onMouseover: function () {
                        parent.overImg = true;
                        document.body.style.cursor = "move";
                    },

                    onMouseout: function () {
                        parent.overImg = false;
                        if (!parent.overCrop) {
                            document.body.style.cursor = "default";
                        }
                    }
                }
            });
        },

        makeCropperCanvas: function () {
            var parent = this;
            return new CanvasItem({
                id         : 'item',
                x          : 175,
                y          : 175,
                w          : 150,
                h          : 50,
                interactive: true,
                offset     : [0, 0],
                events     : {
                    onDraw: function (ctx) {
                        ctx = parent.CANVAS.ctx;
                        if (ctx === undefined) {
                            return;
                        }
                        /*
                         * calculate dimensions locally because they are have to be translated in order to use translate and rotate with the desired
                         * effect: rotate the item around its visual center
                         */

                        var w = this.w;
                        var h = this.h;
                        var x = this.x - w * 0.5;
                        var y = this.y - h * 0.5;

                        // standard Canvas rotation operation

                        ctx.save();
                        ctx.translate(this.x, this.y);

                        this.hover ? ctx.strokeStyle = '#f00' : ctx.strokeStyle = '#000';
                        ctx.strokeRect(w * -0.5, h * -0.5, w, h);
                        ctx.restore();

                        /*
                         * used to determine the whether the mouse is over an item or not.
                         */

                        if (parent.img !== undefined && parent.images.hasOwnProperty(parent.activeFilePath)) {
                            parent.images[parent.activeFilePath].cropdim = {
                                x: this.x,
                                y: this.y,
                                w: w,
                                h: h
                            };
                        }
                        this.setDims(x, y, w, h);
                    },

                    onMousedown: function (x, y) {
                        parent.CANVAS.setDrag(this);
                        this.offset = [x - this.dims[0], y - this.dims[1]];
                        this.dragging = true;
                        parent.overlay.withinCrop = true;
                    },

                    onMousemove: function (x, y) {
                        document.body.style.cursor = "move";
                        if (this.dragging) {
                            var w = this.w;
                            var h = this.h;
                            this.x = x - this.offset[0] + w * 0.5;
                            this.y = y - this.offset[1] + h * 0.5;
                        }
                    },

                    onMouseup: function () {
                        parent.CANVAS.clearDrag();
                        this.dragging = false;
                        parent.overlay.withinCrop = false;
                    },

                    onMouseover: function () {
                        this.hover = true;
                        parent.overCrop = true;

                    },

                    onMouseout: function () {
                        if (!parent.overImg) {
                            document.body.style.cursor = 'default';
                        }
                        parent.overCrop = false;
                        this.hover = false;
                    }
                }
            });
        },

        makeThread: function () {
            var self = this;
            this.CANVAS.addThread(new Thread({
                id    : 'myThread',
                onExec: function () {
                    if (self.CANVAS !== undefined) {
                        if (self.CANVAS.ctxEl !== undefined) {
                            self.CANVAS.clear().draw();
                        }
                    }
                }
            }));
        },

        /**
         * watch the close button
         */
        watchClose: function () {
            var self = this;
            this.window.find('input[name=close-crop]').on('click', function (e) {
                self.storeActiveImageData();
                self.win.close();
            });
        },

        /**
         * Takes the current active image and creates cropped image data via a canvas element
         *
         * @param {string} filepath File path to image to crop. If blank use this.activeFilePath
         */
        storeActiveImageData: function (filepath) {
            filepath = filepath ? filepath : this.activeFilePath;
            if (filepath === undefined) {
                return;
            }
            var x = this.cropperCanvas.x;
            var y = this.cropperCanvas.y;
            var w = this.cropperCanvas.w - 2;
            var h = this.cropperCanvas.h - 2;
            x = x - (w / 2);
            y = y - (h / 2);

            var win = jQuery('#' + this.windowopts.id);
            if (win.length === 0) {
                fconsole('storeActiveImageData no window found for ' + this.windowopts.id);
                return;
            }
            var canvas = win.find('canvas');

            var target = jQuery(document.createElement('canvas')).attr({
                'width' : w + 'px',
                'height': h + 'px'
            }).appendTo(document.body);
            var ctx = target[0].getContext('2d');

            var file = filepath.split('\\').pop();
            var f = jQuery('input[name*="' + file + '"]').filter(function (index, fld) {
                return fld.name.contains('cropdata');
            });

            ctx.drawImage(canvas[0], x, y, w, h, 0, 0, w, h);
            f.val(target[0].toDataURL({quality: this.windowopts.quality}));
            target.remove();
        },

        /**
         * Set up and watch the zoom slide and input field
         */
        watchZoom: function () {
            var self = this;

            if (!this.windowopts.crop) {
                return;
            }
            var scaleField = this.window.find('input[name=zoom-val]');
            this.scaleSlide = new Slider(this.window.find('.fabrikslider-line')[0], this.window.find('.knob')[0], {
                range   : [20, 300],
                onChange: function (pos) {
                    self.imgCanvas.scale = pos / 100;
                    if (self.img !== undefined) {
                        try {
                            self.images[self.activeFilePath].scale = pos;
                        } catch (err) {
                            fconsole('didnt get active file path:' + self.activeFilePath);
                        }
                    }
                    scaleField.val(pos);
                }
            }).set(100);

            scaleField.on('change', function (e) {
                self.scaleSlide.set(jQuery(this).val());
            });
        },

        /**
         * Set up and watch the rotate slide and input field
         */
        watchRotate: function () {
            if (!this.windowopts.crop) {
                return;
            }
            var self = this,
                r = this.window.find('.rotate'),
                rotateField = this.window.find('input[name=rotate-val]');
            this.rotateSlide = new Slider(r.find('.fabrikslider-line')[0], r.find('.knob')[0], {
                onChange: function (pos) {
                    self.imgCanvas.rotation = pos;
                    if (self.img !== undefined) {
                        try {
                            self.images[self.activeFilePath].rotation = pos;
                        } catch (err) {
                            fconsole('rorate err' + self.activeFilePath);
                        }
                    }
                    rotateField.val(pos);
                },
                steps   : 360
            }).set(0);
            rotateField.on('change', function () {
                self.rotateSlide.set(jQuery(this).val());
            });
        },

        /**
         * Show the window - creating it if its not found
         */
        showWin: function () {
            this.win = Fabrik.getWindow(this.windowopts);
            this.window = jQuery('#' + this.modalId);
            if (this.CANVAS === undefined) {
                return;
            }
            if (this.CANVAS.ctxEl !== undefined) {
                this.CANVAS.ctxPos = document.id(this.CANVAS.ctxEl).getPosition();
            }

            if (this.CANVAS.threads !== undefined) {
                if (this.CANVAS.threads.get('myThread') !== undefined) {

                    // Fixes issue where sometime canvas thread is not started/running so nothing is drawn
                    this.CANVAS.threads.get('myThread').start();
                }
            }
            this.win.drawWindow();
            this.win.center();
        }
    });

    return  window.FbFileUpload;
});